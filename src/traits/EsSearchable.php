<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuqiang
 * Date: 5/21/18
 * Time: 7:15 PM
 */

namespace Foryoufeng\Elasticsearch\traits;


trait EsSearchable
{
    public $searchSettings = [
        'attributesToHighlight' => [
            '*'
        ]
    ];

    public $highlight = [];
}
