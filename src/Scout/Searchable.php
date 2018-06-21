<?php
/**
 * Created by PhpStorm.
 * User: wuqiang
 * Date: 6/14/18
 * Time: 11:52 AM
 */

namespace Foryoufeng\Elasticsearch\Scout;
use Laravel\Scout\Searchable as BaseSearchable;

trait Searchable
{
    use BaseSearchable;


    /**
     * Perform a search against the model's indexed data.
     * @example
     * search()->where(...)->get(['*'])
     * search('shold')->where(...)->get(['*'])
     * search()->where(...)->keys()
     * search()->where(...)->count()
     *
     * @note
     * querystring,bool,match_all is only one effective
     *
     * @param string $boolOccur  [must]|should|filter|must_not https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html
     * @param null $callback
     * @return Builder
     */
    public static function search($query, $callback = null)
    {
        $builder = new Builder(new static, $callback);
        $builder->setQueryString($query);
        $builder->setBool('must');
        return $builder;
    }


    /**
     * Make all instances of the model searchable.
     * @param int $min
     * @param int $max
     */
    public static function makeAllSearchable($min = 0, $max = 0): void
    {
        $self = new static();

        $builder = $self->newQuery();
        if (!empty($min)) $builder->where($self->getKeyName(), '>=', $min);
        if (!empty($max) && $max >= $min) $builder->where($self->getKeyName(), '<=', $max);

        $builder->orderBy($self->getKeyName())
            ->searchable();
    }

}