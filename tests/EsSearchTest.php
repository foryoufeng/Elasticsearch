<?php

namespace Foryoufeng\Elasticsearch\Tests;

use Elasticsearch\ClientBuilder;
use Foryoufeng\Elasticsearch\Tests\Traits\ESQueryTrait;

class EsSearchTest extends TestCase
{
    public function testOr()
    {
        $clientBuilder = ClientBuilder::create();
        $config=config('es.connections.default');
        if (!empty($config['handler'])) {
            $clientBuilder->setHandler($config['handler']);
        }
        $clientBuilder->setHosts($config["servers"]);
        $client = $clientBuilder->build();
        $params = [
            'index' => $config['index'].'goods',
            'type' => 'goods',
            'body' => [
                'query' => [
                    'bool'=>[
                        'must'=>[
                            [
                                "match"=>[
                                    "goods_name"=>"花瓣雨"
                                ]
                            ],
                        ],
                        "filter"=>[
                            "or"=>[
                                [
                                    "term"=>[
                                        "goods_id"=>1470
                                    ]
                                ],
                                [
                                    "term"=>[
                                        "goods_id"=>3904
                                    ]
                                ],
                            ]
                        ]
                    ]
                ],
                "highlight"=>[
                    "fields"=>[
                        "goods_name"=>new \stdClass()
                    ]
                ],
                "sort"=>[
                    [
                        "sale_volume"=>"desc"
                    ]
                ],
                "from"=>0,
                "size"=>10
            ],

        ];
        $response = $client->search($params);
        dump($response);
        $this->assertNotEmpty($response);
    }

    /**
     *
     */
    public function testIn()
    {
        $clientBuilder = ClientBuilder::create();
        $config=config('es.connections.default');
        if (!empty($config['handler'])) {
            $clientBuilder->setHandler($config['handler']);
        }
        $clientBuilder->setHosts($config["servers"]);
        $client = $clientBuilder->build();
        $params = [
            'index' => $config['index'].'goods',
            'type' => 'goods',
            'body' => [
                'query' => [
                    'bool'=>[
                        'must'=>[
                            [
                                'match'=>[
                                    'goods_name'=>'花瓣雨'
                                ]
                            ],
                            [
                                'terms'=>[
                                    'cat_id'=>[1092,12],
                                ]
                            ],
                            [
                                'terms'=>[
                                    'goods_id'=>[3904],
                                ]
                            ],
                        ],
                    ]
                ],
                'highlight'=>[
                    'fields'=>[
                        'goods_name'=>new \stdClass()
                    ]
                ],
                'sort'=>[
                    [
                        'sale_volume'=>'desc'
                    ]
                ],
                'from'=>0,
                'size'=>10
            ],

        ];
        $response = $client->search($params);
        dump($response);
        $this->assertNotEmpty($response);
    }

    public function testBetween()
    {
        $clientBuilder = ClientBuilder::create();
        $config=config('es.connections.default');
        if (!empty($config['handler'])) {
            $clientBuilder->setHandler($config['handler']);
        }
        $clientBuilder->setHosts($config['servers']);
        $client = $clientBuilder->build();
        $params = [
            'index' => $config['index'].'goods',
            'type' => 'goods',
            'body' => [
                'query' => [
                    'bool'=>[
                        'must'=>[
                            [
                                'match'=>[
                                    'goods_name'=>'花瓣雨'
                                ]
                            ],
                            [
                                'range'=>[
                                    'shop_price'=>[
                                        'gt'=>650,
                                        'lt'=>5560,
                                    ]
                            ],
                           ]
                        ],
                    ]
                ],
                'highlight'=>[
                    'fields'=>[
                        'goods_name'=>new \stdClass()
                    ]
                ],
                'sort'=>[
                    [
                        'sale_volume'=>'desc'
                    ]
                ],
                'from'=>0,
                'size'=>10
            ],

        ];
        $response = $client->search($params);
        dump($response);
        $this->assertNotEmpty($response);
    }
}
