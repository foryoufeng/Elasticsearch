<?php
/**
 * Created by PhpStorm.
 * User: wuqiang
 * Date: 6/13/18
 * Time: 10:48 AM.
 */

namespace Foryoufeng\Elasticsearch\Tests;

use Illuminate\Database\Eloquent\Model;
use Foryoufeng\Elasticsearch\Scout\Searchable;
use Foryoufeng\Elasticsearch\traits\EsSearchable;

class Good extends Model
{
    use Searchable,EsSearchable;
    public $timestamps = false;
    //in your database add goods
    protected $primaryKey = 'goods_id';
    protected $table = 'goods';
    protected $guarded = [];

    public function toSearchableArray()
    {
        $array = $this->toArray();

        return [
            'goods_id' => $array['goods_id'],
            'goods_name' => $array['goods_name'],
            'seller_id' => $array['seller_id'],
            'cat_id' => $array['cat_id'],
            'brand_id' => $array['brand_id'],
            'is_on_sale' => $this->is_on_sale,
            'is_delete' => $this->is_delete,
            'check_status' => $this->check_status,
            'shop_price' => $this->shop_price,
        ];
    }
}
