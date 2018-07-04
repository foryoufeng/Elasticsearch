<?php

namespace Foryoufeng\Elasticsearch\Tests;

class GoodTest extends TestCase
{
    public function testWhere()
    {
        $good = Good::search('花瓣雨')
            ->whereIn('goods_id', [373, 383])
            ->orderBy('shop_price')->get();
//        $good=Good::search('花瓣雨')->take(2)->get();
        dump($good->toArray());
    }

    public function testSearch()
    {
        $good = Good::search('花瓣雨')->first();
        $this->assertNotEmpty($good);
    }

    public function testBetween()
    {
        $goods = Good::search('花瓣雨')
            //->whereBetween('goods_id', [372, 375])
//            ->whereBetween('shop_price', [300, 511])
            ->take(100)->get();
        dump($goods->toArray());
        $this->assertNotEmpty($goods);
    }

    public function testOrder()
    {
        $goods = Good::search('花瓣雨')
            //->whereBetween('goods_id', [372, 375])
//            ->whereBetween('shop_price', [300, 511])
            ->orderBy('shop_price','desc')
            ->get();
        //dump($goods->toArray());
        $this->assertNotEmpty($goods);
    }
}
