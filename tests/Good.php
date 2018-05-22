<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuqiang
 * Date: 5/21/18
 * Time: 2:17 PM
 */

namespace Foryoufeng\Elasticsearch\Tests;


use Foryoufeng\Elasticsearch\Model;

class Good extends Model
{
  protected $index='tdd';
  public $highlight=['goods_name'];
}
