<?php

namespace Foryoufeng\Elasticsearch\Tests;

use Foryoufeng\Elasticsearch\Tests\Traits\ESQueryTrait;

class ModelTest extends TestCase
{

    use ESQueryTrait;


    public function testSearch()
    {
        $good=Good::find(1);
        dump($good);
    }
    /**
     * add
     */
    public function testAddDocument()
    {
//        $good=new Good();
//        $good->name='1';
//        $good->_id='1';
//        $good->goods_id=12;
//        $good->save();
        $good=Good::id(3)->insert([
            'name'=>'店铺提醒',
            'goods_id'=>3
        ]);
        $this->assertNotEmpty($good);
    }

    public function testDeleteIndex()
    {

    }
    /**
     * delete
     */
    public function testDeleteDocument()
    {
        $good=Good::where('goods_id',1)->first();
        $good->delete();
        $this->assertEquals(123,$good->goods_id);
    }

    public function testDeleteAllDocument()
    {
        Good::take(4000)->get()->map(function ($item){
            echo $item->goods_id;
           $item->delete();
        });
        echo 'success';
    }
    public function testHighlight()
    {
        $good=Good::where('goods_name','like','泥漆')->get();
        dump($good->toArray());
    }

    public function testUpdate()
    {
        $good=Good::find(2077);
        dd($good);
    }
}
