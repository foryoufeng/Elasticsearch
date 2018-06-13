<?php

namespace Foryoufeng\Elasticsearch\Tests;

use Elasticsearch\ClientBuilder;
use Foryoufeng\Elasticsearch\Tests\Traits\ESQueryTrait;

class ScoutSearchTest extends TestCase
{

    /**
     * how to search by between at  model
     */
    public function testBetween()
    {
        $res=Goods::search('花瓣雨')
            ->where('shop_price', ['between'=>[650,5560]])
            ->paginate(10);
        $res->each(function ($item) {
            print_r($item->goods_name.'===>'.$item->shop_price."\n");
        });
        $this->assertNotEmpty($res);
    }

    /**
     * how to search by in at model
     */
    public function testIn()
    {
        $res=Goods::search('花瓣雨')
            //->where('cat_id',['in'=>[1092,12]])
            ->where('cat_id', 1206)
            ->where('goods_id', 998)
            ->where('shop_price', ['between'=>[650,5560]])
            ->paginate(10);
        $res->each(function ($item) {
            print_r($item->cat_id.':'.$item->goods_id.'===>'.$item->shop_price."\n");
        });
        $this->assertNotEmpty($res);
    }

    public function testGtOrLt()
    {
        $res=Goods::search('花瓣雨')
            ->where('shop_price', ['gt'=>650])
            ->where('goods_id', ['lt'=>384])
            ->paginate(10);
        $res->each(function ($item) {
            print_r($item->goods_id.'===>'.$item->shop_price."\n");
        });
        $this->assertNotEmpty($res);
    }

    public function testOr()
    {
        $res=Goods::search('花瓣雨')
            ->where('goods_id', ['or'=>[383,377]])
            ->paginate(10);
        $res->each(function ($item) {
            print_r($item->goods_id.'===>'.$item->shop_price."\n");
        });
        $this->assertNotEmpty($res);
    }
}
