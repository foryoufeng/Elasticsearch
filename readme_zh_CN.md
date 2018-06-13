## 这是laravel| Lumen 通过elasticsearch来实现全文检索的客户端

- 可以使用laravel的scout驱动来进行全文检索，具体请看 [laravel scout](https://laravel.com/docs/5.4/scout)的文档
- 可用的分页组件 基于[LengthAwarePagination](https://github.com/illuminate/pagination).
- 可以使用Laravel的缓存驱动将查询的结果缓存 [laravel cache](https://laravel.com/docs/5.4/cache).

## 安装要求

- `php` >= 5.6.6 
  
- `laravel/laravel` >= 5.* or `laravel/lumen` >= 5.* or `composer application`


## 文档

## 安装

### <u>Laravel 安装</u>


##### 1) 通过composer安装

```bash
$ composer require foryoufeng/elasticsearch
```

##### 2) 在config/app.php中添加service provider (< laravel 5.5).

```php
Foryoufeng\Elasticsearch\ElasticsearchServiceProvider::class
```

##### 3) 在config/app.php alias中添加 (< laravel 5.5).

```php
'ES' => Foryoufeng\Elasticsearch\Facades\ES::class
```
	
##### 4) 配置文件

```bash
$ php artisan vendor:publish --provider="Foryoufeng\Elasticsearch\ElasticsearchServiceProvider"
```

## 配置 (Laravel & Lumen)

  
运行public后在config.php中会有2个文件
  
  - `config/es.php` 进行elasticsearch连接的配置
  
  - `config/scout.php` scout使用es驱动配置

```php
# es.php 定义你的elasticsearch连接

'default' => env('ELASTIC_CONNECTION', 'default'),

# elasticsearch连接

'connections' => [
	'default' => [
	    'servers' => [
	        [
	            "host" => env("ELASTIC_HOST", "127.0.0.1"),
	            "port" => env("ELASTIC_PORT", 9200),
	            'user' => env('ELASTIC_USER', ''),
	            'pass' => env('ELASTIC_PASS', ''),
	            'scheme' => env('ELASTIC_SCHEME', 'http'),
	        ]
	    ],
	    
		// 定义handlers
		// 'handler' => new MyCustomHandler(),
		
		'index' => env('ELASTIC_INDEX', 'my_index')
	]
],
 
# 定义你的indices.
 
'indices' => [
	'my_index_1' => [
	    "aliases" => [
	        "my_index"
	    ],
	    'settings' => [
	        "number_of_shards" => 1,
	        "number_of_replicas" => 0,
	    ],
	    'mappings' => [
	        'posts' => [
                'properties' => [
                    'title' => [
                        'type' => 'string'
                    ]
                ]
	        ]
	    ]
	]
]

```
 
## 使用Laravel Scout驱动

查看官方文档 [Laravel Scout installation](https://laravel.com/docs/5.4/scout#installation).

修改你的配置文件`config/scout.php`

```php
# 将默认驱动改为`es`
	
'driver' => env('SCOUT_DRIVER', 'es'),
	
# link `es` driver with default elasticsearch connection in config/es.php
	
'es' => [
    'connection' => env('ELASTIC_CONNECTION', 'default'),
],
```

## Scout 搜索

现在你可以在你的model中使用between或者or等方法了，例子如下

```

$res=Goods::search('test')
            ->where('shop_price', ['between'=>[650,5560]])
            ->paginate(10);
        $res->each(function ($item) {
            print_r($item->goods_name.'===>'.$item->shop_price."\n");
        });
```
// 更多示例请参考测试代码

[ScoutSearchTest.php](./tests/ScoutSearchTest.php).


## Elasticsearch data model

生成的索引是 `es.php`中配置的'index',如'test_'+Model的名称如'Post'，那么产生的索引是'test_posts'
所以每一个索引里只有一个文档'type'

##### 基本用法
```php
<?php

namespace App;

use Foryoufeng\Elasticsearch\Model;

class Post extends Model
{
        
    protected $type = "posts";
    
}
```

上面使用的默认连接和默认索引在 `es.php` 配置的

```php
<?php

namespace App;

use Foryoufeng\Elasticsearch\Model;

class Post extends Model
{
    //修改连接
    protected $connection = "my_connection";
    //修改索引
    protected $index = "my_index";
    //修改type
    protected $type = "posts";
    
}
```

##### 获取数据

```php
<?php

use App\Post;

$posts = App\Post::all();

foreach ($posts as $post) {
    echo $post->title;
}

```

##### 添加查询条件

```php
$posts = App\Post::where('status', 1)
               ->orderBy('created_at', 'desc')
               ->take(10)
               ->get();

```


##### 获取单条数据

```php
// 通过id进行查找
$posts = App\Post::find("AVp_tCaAoV7YQD3Esfmp");
```


##### 插入数据

```php
<?php

namespace App\Http\Controllers;

use App\Post;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $post = new Post;

        $post->title = $request->title;

        $post->save();
    }
}
```

##### 更新文档

```php
$post = App\Post::find(1);

$post->title = 'New Post Title';

$post->save();
```

##### 删除文档

To delete a model, call the `delete()` method on a model instance:

```php
$post = App\Post::find(1);

$post->delete();
```

##### 查询作用域

```php
<?php

namespace App;

use Foryoufeng\Elasticsearch\Model;

class Post extends Model
{
    /**
     * Scope a query to only include popular posts.
     *
     * @param \Foryoufeng\Elasticsearch\Query $query
     * @return \Foryoufeng\Elasticsearch\Query
     */
    public function scopePopular($query, $votes)
    {
        return $query->where('votes', '>', $votes);
    }

    /**
     * Scope a query to only include active posts.
     *
     * @param \Foryoufeng\Elasticsearch\Query $query
     * @return \Foryoufeng\Elasticsearch\Query
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }
}
```

定义好之后你就可以通过作用域来进行查询

```php
$posts = App\Post::popular(100)->active()->orderBy('created_at')->get();
```


##### 

###### 访问器

```php
<?php

namespace App;

use Foryoufeng\Elasticsearch\Model;

class post extends Model
{
    /**
     * Get the post title.
     *
     * @param  string  $value
     * @return string
     */
    public function getTitleAttribute($value)
    {
        return ucfirst($value);
    }
}
```


```php
$post = App\Post::find(1);

$title = $post->title;
```

```php
public function getIsPublishedAttribute()
{
    return $this->attributes['status'] == 1;
}
```


```php
protected $appends = ['is_published'];
```


###### 修饰器


```php
<?php

namespace App;

use Foryoufeng\Elasticsearch\Model;

class post extends Model
{
    /**
     * Set the post title.
     *
     * @param  string  $value
     * @return void
     */
    public function setTitleAttribute($value)
    {
        return strtolower($value);
    }
}
```


```php
$post = App\Post::find(1);

$post->title = 'Awesome post to read';
```

In this example, the setTitleAttribute function will be called with the value `Awesome post to read`. The mutator will then apply the strtolower function to the name and set its resulting value in the internal $attributes array.



##### Attribute Casting



For example, let's cast the `is_published` attribute, which is stored in our index as an integer (0 or  1) to a `boolean` value:

```php
<?php

namespace App;

use Foryoufeng\Elasticsearch\Model;

class Post extends Model
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_published' => 'boolean',
    ];
}

```

Now the `is_published` attribute will always be cast to a `boolean` when you access it, even if the underlying value is stored in the index as an integer:


```php
$post = App\Post::find(1);

if ($post->is_published) {
    //
}
```



#### 创建索引

```php
ES::create("my_index");
    
# or 
    
ES::index("my_index")->create();
```
    
##### 自定义选项进行创建索引
   
```php
ES::index("my_index")->create(function($index){
        
    $index->shards(5)->replicas(1)->mapping([
        'my_type' => [
            'properties' => [
                'first_name' => [
                    'type' => 'string',
                ],
                'age' => [
                    'type' => 'integer'
                ]
            ]
        ]
    ])
    
});
    
# or
    
ES::create("my_index", function($index){
  
      $index->shards(5)->replicas(1)->mapping([
          'my_type' => [
              'properties' => [
                  'first_name' => [
                      'type' => 'string',
                  ],
                  'age' => [
                      'type' => 'integer'
                  ]
              ]
          ]
      ])
  
});

```
#### 删除索引

```php
ES::drop("my_index");
    
# or
    
ES::index("my_index")->drop();
```
#### 查询
```php
$documents = ES::connection("default")
                ->index("my_index")
                ->type("my_type")
                ->get();    
```


```php
$documents = ES::type("my_type")->get();  


ES::type("my_type")->id(3)->first();
    

    
ES::type("my_type")->_id(3)->first();

ES::type("my_type")->orderBy("created_at", "desc")->get();
    

ES::type("my_type")->orderBy("_score")->get();

ES::type("my_type")->take(10)->skip(5)->get();
   
ES::type("my_type")->select("title", "content")->take(10)->skip(5)->get();
  
ES::type("my_type")->where("status", "published")->get();

ES::type("my_type")->where("status", "=", "published")->get();

ES::type("my_type")->where("views", ">", 150)->get();

ES::type("my_type")->where("views", ">=", 150)->get();

ES::type("my_type")->where("views", "<", 150)->get();

ES::type("my_type")->where("views", "<=", 150)->get();

ES::type("my_type")->where("title", "like", "foo")->get();

ES::type("my_type")->where("hobbies", "exists", true)->get(); 


ES::type("my_type")->whereExists("hobbies", true)->get();
   
ES::type("my_type")->whereIn("id", [100, 150])->get();
   
ES::type("my_type")->whereBetween("id", 100, 150)->get();


ES::type("my_type")->whereBetween("id", [100, 150])->get();
   
ES::type("my_type")->whereNot("status", "published")->get(); 



ES::type("my_type")->whereNot("status", "=", "published")->get();

ES::type("my_type")->whereNot("views", ">", 150)->get();

ES::type("my_type")->whereNot("views", ">=", 150)->get();

ES::type("my_type")->whereNot("views", "<", 150)->get();

ES::type("my_type")->whereNot("views", "<=", 150)->get();

ES::type("my_type")->whereNot("title", "like", "foo")->get();

ES::type("my_type")->whereNot("hobbies", "exists", true)->get(); 



ES::type("my_type")->whereExists("hobbies", true)->get();
  
ES::type("my_type")->whereNotIn("id", [100, 150])->get();
   
ES::type("my_type")->whereNotBetween("id", 100, 150)->get();


ES::type("my_type")->whereNotBetween("id", [100, 150])->get();
```
   
##### 查询
```php  
ES::type("my_type")->distance("location", ["lat" => -33.8688197, "lon" => 151.20929550000005], "10km")->get();


ES::type("my_type")->distance("location", "-33.8688197,151.20929550000005", "10km")->get();


ES::type("my_type")->distance("location", [151.20929550000005, -33.8688197], "10km")->get();  
```
  
  
##### 通过指定数据进行查询
      
```php
ES::type("my_type")->body([
    "query" => [
         "bool" => [
             "must" => [
                 [ "match" => [ "address" => "mill" ] ],
                 [ "match" => [ "address" => "lane" ] ]
             ]
         ]
     ]
])->get();


ES::type("my_type")->body([

	"_source" => ["content"]
	
	"query" => [
	     "bool" => [
	         "must" => [
	             [ "match" => [ "address" => "mill" ] ]
	         ]
	     ]
	],
	   
	"sort" => [
		"_score"
	]
     
])->select("name")->orderBy("created_at", "desc")->take(10)->skip(5)->get();

# 查询结果
/*
Array
(
    [index] => my_index
    [type] => my_type
    [body] => Array
        (
            [_source] => Array
                (
                    [0] => content
                    [1] => name
                )
            [query] => Array
                (
                    [bool] => Array
                        (
                            [must] => Array
                                (
                                    [0] => Array
                                        (
                                            [match] => Array
                                                (
                                                    [address] => mill
                                                )
                                        )
                                )
                        )
                )
            [sort] => Array
                (
                    [0] => _score
                    [1] => Array
                        (
                            [created_at] => desc
                        )
                )
        )
    [from] => 5
    [size] => 10
    [client] => Array
        (
            [ignore] => Array
                (
                )
        )
)
*/

```
  
##### 搜索数据
    
```php
ES::type("my_type")->search("hello")->get();
    
ES::type("my_type")->search("hello", 2)->get();

ES::type("my_type")->search("hello", function($search){
	$search->boost(2)->fields(["title" => 2, "content" => 1])
})->get();
```

##### 获取第一条

```php    
ES::type("my_type")->search("hello")->first();
```
  
##### 统计
```php    
ES::type("my_type")->search("hello")->count();
```
    
##### Scan-and-Scroll queries
    


```php
    
$documents = ES::type("my_type")->search("hello")
                 ->scroll("2m")
                 ->take(1000)
                 ->get();


$documents = ES::type("my_type")->search("hello")
                 ->scroll("2m")
                 ->scrollID("DnF1ZXJ5VGhlbkZldGNoBQAAAAAAAAFMFlJQOEtTdnJIUklhcU1FX2VqS0EwZncAAAAAAAABSxZSUDhLU3ZySFJJYXFNRV9laktBMGZ3AAAAAAAAAU4WUlA4S1N2ckhSSWFxTUVfZWpLQTBmdwAAAAAAAAFPFlJQOEtTdnJIUklhcU1FX2VqS0EwZncAAAAAAAABTRZSUDhLU3ZySFJJYXFNRV9laktBMGZ3")
                 ->get();

ES::type("my_type")->scrollID("DnF1ZXJ5VGhlbkZldGNoBQAAAAAAAAFMFlJQOEtTdnJIUklhcU1FX2VqS0EwZncAAAAAAAABSxZSUDhLU3ZySFJJYXFNRV9laktBMGZ3AAAAAAAAAU4WUlA4S1N2ckhSSWFxTUVfZWpLQTBmdwAAAAAAAAFPFlJQOEtTdnJIUklhcU1FX2VqS0EwZncAAAAAAAABTRZSUDhLU3ZySFJJYXFNRV9laktBMGZ3")
        ->clear();
```
    
##### 分页

```php   
$documents = ES::type("my_type")->search("hello")->paginate(5);
        
$documents->links();


$documents->links("bootstrap-4");


$documents->links("simple-bootstrap-4");


$documents->links("simple-default");
```

可以使用的分页方法

```php
$documents->count()
$documents->currentPage()
$documents->firstItem()
$documents->hasMorePages()
$documents->lastItem()
$documents->lastPage()
$documents->nextPageUrl()
$documents->perPage()
$documents->previousPageUrl()
$documents->total()
$documents->url($page)
```

##### 获取查询的数据，并不执行查询

```php
ES::type("my_type")->search("hello")->where("views", ">", 150)->query();
```

##### 获取elasticsearch本来的响应数据

```php
ES::type("my_type")->search("hello")->where("views", ">", 150)->response();
```

##### 忽略错误的http响应

```php      
ES::type("my_type")->ignore(404, 500)->id(5)->first();
```

##### 缓存


```php
ES::type("my_type")->search("hello")->remember(10)->get();
	
# 使用缓存

ES::type("my_type")->search("hello")->remember(10, "last_documents")->get();
	
ES::type("my_type")->search("hello")->cacheDriver("redis")->remember(10, "last_documents")->get();
	
ES::type("my_type")->search("hello")->cacheDriver("redis")->cachePrefix("docs")->remember(10, "last_documents")->get();
```

##### 执行elasticsearch查询

```php
ES::raw()->search([
    "index" => "my_index",
    "type"  => "my_type",
    "body" => [
        "query" => [
            "bool" => [
                "must" => [
                    [ "match" => [ "address" => "mill" ] ],
                    [ "match" => [ "address" => "lane" ] ]
                ]
            ]
        ]
    ]
]);
```
   
##### 插入文档
    
```php
ES::type("my_type")->id(3)->insert([
    "title" => "Test document",
    "content" => "Sample content"
]);
     
# _id=3将会被插入
  
# [id可选的] 不定义的话将生成一个唯一的_id
```
  >
    
##### 分块处理
     
```php
# Main query

ES::index("my_index")->type("my_type")->bulk(function ($bulk){


	$bulk->index("my_index_1")->type("my_type_1")->id(10)->insert(["title" => "Test document 1","content" => "Sample content 1"]);
	$bulk->index("my_index_2")->id(11)->insert(["title" => "Test document 2","content" => "Sample content 2"]);
	$bulk->id(12)->insert(["title" => "Test document 3", "content" => "Sample content 3"]);
	
});

 
ES::type("my_type")->bulk([
 
	10 => [
		"title" => "Test document 1",
		"content" => "Sample content 1"
	],
	 
	11 => [
		"title" => "Test document 2",
		"content" => "Sample content 2"
	]
 
]);
 
# The two given documents will be inserted with its associated ids
```

##### Update an existing document
```php     
ES::type("my_type")->id(3)->update([
   "title" => "Test document",
   "content" => "sample content"
]);
    
# Document has _id = 3 will be updated.
    
# [id is required]
```

```php
# Bulk update

ES::type("my_type")->bulk(function ($bulk){
    $bulk->id(10)->update(["title" => "Test document 1","content" => "Sample content 1"]);
    $bulk->id(11)->update(["title" => "Test document 2","content" => "Sample content 2"]);
});
```
   
##### Incrementing field
```php
ES::type("my_type")->id(3)->increment("views");
    
# Document has _id = 3 will be incremented by 1.
    
ES::type("my_type")->id(3)->increment("views", 3);
    
# Document has _id = 3 will be incremented by 3.

# [id is required]
```
   
##### Decrementing field
```php 
ES::type("my_type")->id(3)->decrement("views");
    
# Document has _id = 3 will be decremented by 1.
    
ES::type("my_type")->id(3)->decrement("views", 3);
    
# Document has _id = 3 will be decremented by 3.

# [id is required]
```
   
##### Update using script
       
```php
# increment field by script
    
ES::type("my_type")->id(3)->script(
    "ctx._source.$field += params.count",
    ["count" => 1]
);
    
# add php tag to tags array list
    
ES::type("my_type")->id(3)->script(
    "ctx._source.tags.add(params.tag)",
    ["tag" => "php"]
);
    
# delete the doc if the tags field contain mongodb, otherwise it does nothing (noop)
    
ES::type("my_type")->id(3)->script(
    "if (ctx._source.tags.contains(params.tag)) { ctx.op = 'delete' } else { ctx.op = 'none' }",
    ["tag" => "mongodb"]
);
```
   
##### 删除一个文档
```php
ES::type("my_type")->id(3)->delete();
    
# [id是必须的]
```

```php
# 删除

ES::type("my_type")->bulk(function ($bulk){
    $bulk->id(10)->delete();
    $bulk->id(11)->delete();
});
```

## 版本管理

  查看[更新日志](https://github.com/Foryoufeng/elasticsearch/blob/master/CHANGELOG.md).

## 关于作者
[Foryoufeng](http://doc.linkgoup.com) - [Foryoufeng@gmail.com](mailto:Foryoufeng@gmail.com) - [@Foryoufeng](https://twitter.com/Foryoufeng)  


## bug或者建议


请使用[Github](https://github.com/Foryoufeng/elasticsearch) 提bug或者建议

## License

MIT

`Have a happy searching..`
