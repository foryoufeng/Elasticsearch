<?php

namespace Foryoufeng\Elasticsearch\Tests;

use Elasticsearch\ClientBuilder;
use Foryoufeng\Elasticsearch\Tests\Traits\ESQueryTrait;

class ConnectTest extends TestCase
{

    public function testConnect()
    {
        $clientBuilder = ClientBuilder::create();
        $client=$clientBuilder->build();
        dump($client);
        $deleteParams = [
            'index' => 'goods'
        ];
        $response = $client->indices()->delete($deleteParams);
        $this->assertNotEmpty($client);
    }

    public function testSearch()
    {
        $params['body'] = array(
            'highlight' => array(
                'fields' => array(
                    'name' => new \stdClass()
                )
            ),
            'query' => array(
                'match' => array(
                    'name' => '店铺'
                )
            )
        );
        $clientBuilder = ClientBuilder::create();
        $client = $clientBuilder->build();
        $results = $client->search($params);
        dd($results);
    }

}
