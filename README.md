# 数据库操作

对 `Medoo Version: 2.1.10` 再封装，让操作更简单。

~~~
composer require thefunpower/pg_sql
~~~

~~~
/**
* 数据库连接
*/
$pg_config['name'] = 'ec'; 
$pg_config['host'] = '127.0.0.1';
$pg_config['user'] = 'root';
$pg_config['pwd']  = '111111';
$pg_config['port'] = 3306; 
$pg_config['type'] = 'pgsql';  // mysql pgsql
//main read default
new_pg($pg_config,'default');

$one = pg_get("article",['id[>]'=>1],1);
$all = pg_get("article",['id[>]'=>1,'LIMIT'=>2]);
$all = pg_get("article","id",['id[>]'=>1,'LIMIT'=>2]);
$all = pg_get("article","id,title",['id[>]'=>1,'LIMIT'=>2]);
$all = pg_get("article",["id","title"],['id[>]'=>1,'LIMIT'=>2]);
pg_insert("article",['title'=>'ttt']);
pg_update("article",['title'=>'ttt'],['id'=>1]);

~~~

## $where条件

~~~
'user_name[REGEXP]' => '[a-z0-9]*'
'user_name[FIND_IN_SET]'=>(string)10
'user_name[RAW]' => '[a-z0-9]*'
~~~

~~~
$where = [
	//like查寻
	'product_num[~]' => 379, 
	//等于查寻
	'product_num' => 3669, 
	//大于查寻
	'id[>]' => 1,
	'id[>=]' => 1,
	'id[<]' => 1,
	'id[<=]' => 1,
]; 
$where = []; 
$where['OR'] = [
	'product_num[~]'=>379,
	'product_num[>]'=>366,
];
$where['LIMIT'] = 10;
$where['ORDER'] = ['id'=>'DESC']; 
~~~

## where字段两个日期之间

字段是datetime类型  
~~~
$date1 = '2022-11-01';
$date2 = '2022-12-14';
pg_between_date($field,$date1,$date2)
~~~

## where 两个月份之间

~~~
$date1 = '2022-11';
$date2 = '2022-12';
pg_between_month($field,$date1,$date2
~~~

## 查寻一条记录

~~~
$res = pg_get_one("products","*",$where);
$res = pg_get_one("products",$where);
~~~

## 所有记录

~~~
$res = pg_get("products","*",$where);
$res = pg_get("products",$where);
~~~

## 分页

~~~
$res  = pg_pager("products","*",$where);
~~~

## 使用原生方法 

原生方法将不会触发`action`

https://medoo.in/api/where

~~~
$res = pg()->select("products",['id'],[]); 
~~~ 

## 查寻某个字段

~~~
$res  = pg_get("qr_rule","qr_num",['GROUP'=>'qr_num']);
print_r($res); 
~~~

## 写入记录

~~~
pg_insert($table, $data = [],$don_run_action = false)
~~~

## 更新记录

~~~
pg_update($table, $data = [], $where = [],$don_run_action = false)
~~~

## 取最小值

~~~
pg_get_min($table, $join  = "*", $column = null, $where = null)
~~~

其他一些如取最大值等

~~~
pg_get_max
pg_get_count
pg_get_has
pg_get_rand
pg_get_sum
pg_get_avg 
~~~

## 删除 

~~~
pg_del($table, $where)
~~~


##  action 

### 写入记录前

~~~
do_action("pg_insert.$table.before", $data);
do_action("pg_save.$table.before", $data);
~~~

### 写入记录后

其中`$data`有 `id` 及 `data`

~~~
do_action("pg_insert.$table.after", $action_data);
do_action("pg_save.$table.after", $action_data);
~~~

### 更新记录前

~~~
do_action("pg_update.$table.before", $data);
do_action("pg_save.$table.before", $data);
~~~

### 更新记录后

其中`$data`有 `id`   `data` `where`
~~~
do_action("pg_update.$table.after", $action_data);
do_action("pg_save.$table.after", $action_data); 
~~~

~~~
do_action("pg_get_one.$table", $v); 
~~~

## 删除前

~~~
do_action("pg_insert.$table.del", $where);
~~~


## 显示所有表名

~~~
show_tables($table)
~~~

## 取表中字段

~~~
get_table_fields($table, $has_key  = true)
~~~

## 返回数据库允许的数据，传入其他字段自动忽略

~~~
pg_allow($table, $data)
~~~

## 显示数据库表结构，支持markdown格式

~~~
database_tables($name = null, $show_markdown = false)
~~~

## 数组排序

~~~
array_order_by($row,$order,SORT_DESC);
~~~

## 判断是json数据

~~~
is_json($data)
~~~


## SQL查寻

~~~
pg_query($sql, $raw = null)
do_action("pg_query", $all) 
~~~

其中`$sql`为`select * from table_name where user_id=:user_id`

`$raw` 为 `[':user_id'=>1]`




## 事务

需要`inner pg`支持

~~~
pg_action(function()use($data)){

});
~~~

## id锁

~~~
pg_for_update($table,$id)
~~~

## 设置分页总记录数

~~~
pg_pager_count($nums = null)
~~~ 

## 连表查寻

~~~
$data = pg_pager("do_order",
["[><]do_mini_user" => ["uid" => "id"]],
[
    "do_order.id",
    "do_order.uid",
    "user" => [
        "do_mini_user.nickName",
        "do_mini_user.avatarUrl",
        "do_mini_user.openid",
    ]
],
$where);
~~~

## pg_get复杂查寻

~~~
$lists = pg_get('do_order', [ 
    'count' => 'COUNT(`id`)',
    'total' => 'SUM(`total_fee`)',
    'date'  => "FROM_UNIXTIME(`inserttime`, '%Y-%m-%d')"
], 
~~~

## 其他where条件请查看

https://medoo.in/api/where


## License

The [MIT](LICENSE) License (MIT)


