<?php

namespace Foryoufeng\Elasticsearch;

use Illuminate\Support\Str;
use Foryoufeng\Elasticsearch\Scout\Builder;
use Elasticsearch\Client as Elastic;
use Illuminate\Database\Eloquent\Collection;

class ScoutEngine
{

    /**
     * Index where the models will be saved.
     * @var string
     */
    protected $index;

    /**
     * ScoutEngine constructor.
     * @param Elastic $elastic
     * @param $index
     */
    public function __construct(Elastic $elastic, $index)
    {
        $this->elastic = $elastic;
        $this->index = $index;
    }

    /**
     * Update the given model in the index.
     * @param  Collection  $models
     * @return void
     */
    public function update($models)
    {
        $params['body'] = [];

        $models->each(function ($model) use (&$params) {
            $index=str_replace('\\', '', Str::snake(Str::plural(class_basename($model))));
            $params['body'][] = [
                'update' => [
                    '_id' => $model->getKey(),
                    '_index' => $this->index.$index,
                    '_type' => $model->searchableAs(),
                ]
            ];

            $params['body'][] = [
                'doc' => $model->toSearchableArray(),
                'doc_as_upsert' => true
            ];
        });

        $this->elastic->bulk($params);
    }

    /**
     * Remove the given model from the index.
     * @param  Collection  $models
     * @return void
     */
    public function delete($models)
    {
        $params['body'] = [];

        $models->each(function ($model) use (&$params) {
            $index=str_replace('\\', '', Str::snake(Str::plural(class_basename($model))));
            $params['body'][] = [
                'delete' => [
                    '_id' => $model->getKey(),
                    '_index' => $this->index.$index,
                    '_type' => $model->searchableAs(),
                ]
            ];
        });

        $this->elastic->bulk($params);
    }

    /**
     * Perform the given search on the engine.
     * @param  Builder  $builder
     * @return mixed
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder,null!==$builder->limit?['size'=>$builder->limit]:[]);
//        return $this->performSearch($builder, array_filter([
//            'numericFilters' => $this->filters($builder),
//            'size' => $builder->limit,
//        ]));
    }

    public function count(Builder $builder)
    {
        $result = $this->performCount($builder);
        return isset($result['count']) ?? false;
    }

    public function keys(Builder $builder)
    {
        return $this->getIds($this->search($builder));
    }

    /**
     * Get the results of the given query mapped onto models.
     * @param Builder $builder
     * @return Collection
     */
    public function get(Builder $builder): Collection
    {
        return Collection::make($this->map(
            $this->search($builder), $builder->model
        ));
    }

    /**
     * Get the aggregations of the give aggs
     * [warning] excute a search with each 'aggregations'
     *
     * @param Builder $builder
     * @param null $key
     * @return mixed
     */
    public function aggregations(Builder $builder, $key = null)
    {
        $result = $this->execute($builder);
        return null===$key ? $result['aggregations'] : array_get($result['aggregations'], $key);
    }
    /**
     * Perform the given search on the engine.
     * @param  Builder  $builder
     * @param  int  $perPage
     * @param  int  $page
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $result = $this->performSearch($builder, [
            //'numericFilters' => $this->filters($builder),
            'from' => (($page * $perPage) - $perPage),
            'size' => $perPage,
        ]);

        $result['nbPages'] = (int) ceil($result['hits']['total']/$perPage);

        return $result;
    }

    private function parseBody(Builder $builder)
    {
        $body = [];

        $body['query'] = $builder->bool->toArray();

        foreach(['query_string', 'match_all'] as $var)
        {
            if (null!==$builder->$var)
            {
                $body['query']['bool']['must'][][$var] = $builder->$var;
                // break;
            }
        }
        foreach(['_source', 'aggs', 'track_scores', 'stored_fields', 'docvalue_fields', 'highlight', 'rescore', 'explain', 'version', 'indices_boost', 'min_score', 'search_after'] as $var)
            null!==$builder->$var && $body[$var] = $builder->$var;

        return $body;
    }


    protected function performCount(Builder $builder, array $options = [])
    {
        $query = [
            'index' =>  $this->index,
            'type'  =>  $builder->model->searchableAs(),
            'body' => $this->parseBody($builder),
        ];
        return $this->elastic->count($query);
    }

    /**
     * Perform the given search on the engine.
     * @param  Builder  $builder
     * @param  array  $options
     * @return mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $index=str_replace('\\', '', Str::snake(Str::plural(class_basename($builder->model))));
        $params = [
            'index' => $this->index.$index,
            'type' => $builder->model->searchableAs(),
            'body' => $this->parseBody($builder),
        ];
        /**
         * 这里使用了 highlight 的配置
         */
        if ($builder->model->searchSettings
            && isset($builder->model->searchSettings['attributesToHighlight'])
        ) {
            $attributes = $builder->model->searchSettings['attributesToHighlight'];
            foreach ($attributes as $attribute) {
                $params['body']['highlight']['fields'][$attribute] = new \stdClass();
            }
        }
        if ($sort = $this->sort($builder)) {
            $params['body']['sort'] = $sort;
        }
        if (array_key_exists('from', $options)) {
            $params['body']['from'] = $options['from'];
        }

        if (array_key_exists('size', $options)) {
            $params['body']['size'] = $options['size'];
        }

        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                $this->elasticsearch,
                $params
            );
        }
        return $this->elastic->search($params);
    }

    /**
     * Get the filter array for the query.
     * @param  Builder  $builder
     * @return array
     */
    protected function filters(Builder $builder)
    {
        return collect($builder->wheres)->map(function ($value, $key) {
            if (\is_array($value)) {
                if (array_key_exists('between', $value)) {
                    $filter=[
                        'range'=>[
                            $key=>[
                                'gte'=>$value['between'][0],
                                'lte'=>$value['between'][1]
                            ]
                        ]
                    ];
                } elseif (array_key_exists('in', $value)) {
                    $filter=[
                        'terms'=>[
                            $key=>$value['in']
                        ]
                    ];
                } elseif (array_key_exists('or', $value)) {
                    foreach ($value['or'] as $k=>$v) {
                        $filter['bool']['should'][$k]['term'][$key]=$v;
                    }
                } else {
                    //default is range such as  gt/lt/gte/lte example
                    // $model->where('attr',['gt'=>100])
                    $keys=array_keys($value);
                    $filter=[
                        'range'=>[
                            $key=>[
                                $keys[0]=>$value[$keys[0]],
                            ]
                        ]
                    ];
                }
                return $filter;
            } else {
                return ['match_phrase' => [$key => $value]];
            }
        })->values()->all();
    }

    /**
     * Map the given results to instances of the given model.
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return Collection
     */
    public function map($results, $model)
    {
        if ($results['hits']['total'] === 0) {
            return Collection::make();
        }

        $keys = collect($results['hits']['hits'])
            ->pluck('_id')->values()->all();

        $models = $model->whereIn(
            $model->getKeyName(),
            $keys
        )->get()->keyBy($model->getKeyName());

        return collect($results['hits']['hits'])->map(function ($hit) use ($model, $models) {
            $one = $models[$hit['_id']];
            /**
             * 这里返回的数据，如果有 highlight，就把对应的  highlight 设置到对象上面
             */
            if (isset($hit['highlight'])) {
                $one->highlight = $hit['highlight'];
            }
            return $one;
        });
    }

    /**
     *
     * Pluck and return the primary keys of the results.
     *
     * @param  mixed  $results
     * @return \Illuminate\Support\Collection
     */
    public function getIds($results) {

        return collect($results['hits']['hits'])
            ->pluck('_id')
            ->values()
            ->all();

    }

    public function mapIds($results)
    {
    }

    /**
     * Get the total count from a raw result returned by the engine.
     * @param  mixed  $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['hits']['total'];
    }
    /**
     * Generates the sort if theres any.
     *
     * @param  Builder $builder
     * @return array|null
     */
    protected function sort($builder)
    {
        if (count($builder->orders) == 0) {
            return null;
        }
        return collect($builder->orders)->map(function ($order) {
            return [$order['column'] => $order['direction']];
        })->toArray();
    }
}
