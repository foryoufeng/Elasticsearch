<?php

namespace Foryoufeng\Elasticsearch\Tests;

use Foryoufeng\Elasticsearch\Tests\Traits\ESQueryTrait;

class BodyTest extends TestCase
{

    use ESQueryTrait;


    /**
     * Test the body() method.
     * @return void
     */
    public function testBodyMethod()
    {
       $good= Good::where('name','123');
       dump($good->query());
       $this->assertArrayHasKey('highlight',$good->getBody());
    }

    /**
     * Get The expected results.
     * @param $body array
     * @return array
     */
    protected function getExpected($body = [])
    {
        $query = $this->getQueryArray();

        $query["body"] = $body;

        return $query;
    }


    /**
     * Get The actual results.
     * @param $body array
     * @return mixed
     */
    protected function getActual($body = [])
    {
        return $this->getQueryObject()->body($body)->query();
    }
}
