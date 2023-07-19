<?php

/*
    Copyright (c) 2021-2031, All rights reserved.
    This is  a library, use is under MIT license.
*/
/**
*  对数据库操作的封装
*  https://medoo.in/api/where 
*/   
/**
复杂的查寻，(...  AND ...) OR (...  AND ...)
"OR #1" => [
    "AND #2" => $where,
    "AND #3" => $or_where
    ]
];  

 */
/**
* 数据库对象
* 建议使用 medoo_pg()
*/
global $_pg,$_pg_active,$_pg_connects; 
$_pg_active  = 'default'; 
/**
* 数据参数用于分页后生成分页HTML代码
*/
global $_pg_par;
/**
* 错误信息
*/
global $_pg_error; 
/**
 * 激活平台数据库连接，平台数据库不支持从库读
 */
function pg_active_main()
{
    pg_active('main');
}
/**
 * 激活读数据库连接
 */
function pg_active_read()
{
    pg_active('read');
}
/**
 * 激活默认数据库连接
 */
function pg_active_default()
{
    pg_active('default');
}

/**
 * 激活当前使用哪个数据库
 */
function pg_active($name = 'default')
{
    global $_pg_active;
    $_pg_active  = $name;
}
/**
* 获取当前启用的数据库连接 
*/
function get_pg_active_name()
{
    global $_pg_active;
    return $_pg_active;
}
/**
* 判断是否可运行action
*/
function pg_can_run_action(){
    $name = get_pg_active_name();
    //数据库连接平台时是不能使用action的
    if($name == 'main'){
        return false;
    } 
    return true;
}
/**
* 数据库是否可执行更新操作
*/
function pg_can_run_update($sql = ''){
    $name = get_pg_active_name();
    if($name == 'read'){
        if($sql){
            if(strpos(strtoupper($sql),'UPDATE') !== false){
                return false;
            }else{
                return true;
            }
        }
        return false;
    } 
    return true;
}

/** 
 * 连接数据库
 */
function new_pg($config = [],$name = '')
{
    global $_pg_connects;
    $_pg = new \Medoo\Medoo([
        'type' => 'mysql',
        'host' => $config['host'],
        'database' => $config['name'],
        'username' => $config['user'],
        'password' => $config['pwd'],
        // [optional]
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_general_ci',
        'port'      => $config['port'],
        'prefix'    => '',
        'error'     => PDO::ERRMODE_SILENT,
        // Read more from http://www.php.net/manual/en/pdo.setattribute.php.
        'option'    => [
            PDO::ATTR_CASE => PDO::CASE_NATURAL
        ],
        'command' => [
            'SET SQL_MODE=ANSI_QUOTES'
        ]
    ]);
    if($name){
        $_pg_connects[$name] = $_pg;
    }
    return $_pg;
}
/**
 * 数据库实例
 *
 * @return object
 */
function medoo_pg()
{
    global $_pg_connects,$_pg_active; 
    $pg_connect =  $_pg_connects[$_pg_active];
    if(!$pg_connect){
        exit("Lost connect");
    }else{
        return $pg_connect;
    }
}
if(!function_exists('pg')){
    function pg(){
        return medoo_pg();
    }
}
/***
 * 分页查寻 
 JOIN
 $where = [ 
    //"do_order.id"=>1,
    'ORDER'=>[
        'do_order.id'=>'DESC'
    ]
]; 

int date
$where['printer_refund_apply.created_at[<>]']  = [
 $dates[0] / 1000, $dates[1] / 1000
];
datetime
$where['printer_refund_apply.created_at[<>]']  = [
 date('Y-m-d H:i:s',$dates[0] / 1000), date('Y-m-d H:i:s',$dates[1] / 1000)
];

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

 * @param string $table 表名
 * @param string $column 字段 
 * @param array $where  条件 [LIMIT=>1]  
 * @return array
 */ 
function pg_pager($table, $join, $columns = null, $where = null)
{
    if(!$columns){
        $columns = $join;
        $join = '*'; 
    } 
    global $_pg_par;
    $flag = true;
    if (!$where) {
        $where   = $columns;
        $columns = $join ?: "*";
        $join    = '';
        $count   = pg_pager_count() ?: pg_get_count($table, $where);
    } else if ($join && $where) {
        $flag    = false;
        $count   = pg_pager_count() ?: pg_get_count($table, $join, "$table.id", $where);
    }
    $current_page  = (int)(g('page')?:1);
    $per_page      = (int)(g('per_page')?:20); 
    $count         = (int)$count;
    $last_page     = ceil($count / $per_page);
    $has_next_page = $last_page > $current_page ? true : false;
    $start         = ($current_page - 1) * $per_page;
    if (is_object($where)) {
        $where->value =  $where->value . " LIMIT $start, $per_page";
    } else {
        $where['LIMIT'] = [$start, $per_page];
    } 
    if ($flag) {
        $data  =  pg_get($table, $columns, $where);
    } else {
        $data  =  pg_get($table, $join, $columns, $where);
    }
    $_pg_par['size'] = $per_page;
    $_pg_par['count'] = $count;
    return [
        'current_page' => $current_page,
        'last_page'    => $last_page,
        'per_page'     => $per_page, 
        'total'        => $count,
        'has_next_page' => $has_next_page,
        'data'         => $data,
        'data_count'   => count($data ?: []),
    ];
}
/**
 *  设置分页总记录数
 *  一般情况用不到
 */
function pg_pager_count($nums = null)
{
    static $_page_count;
    if ($nums && $nums >= 0) {
        $_page_count = $nums;
    } else {
        return $_page_count;
    }
}
/**
 * 显示分页
 * 调用pg_pager后，再调用。
 * pg_pager_html([
 *      'url'=>'',
 * ]);
 */
function pg_pager_html($arr = [])
{
    global $_pg_par;
    if (isset($arr['count'])) {
        $count  = $arr['count'];
    } else {
        $count = $_pg_par['count'];
    }
    $page_url = isset($arr['url'])?$arr['url']:'';
    if (isset($arr['size'])) {
        $size  = $arr['size'];
    } else {
        $size = $_pg_par['size'] ?: 20;
    }
    $paginate = new pg_pager_html($count, $size);
    if ($page_url) {
        $paginate->url = $page_url;
    }
    $limit  = $paginate->limit;
    $offset = $paginate->offset;
    return $paginate->show();
}
 

/**
* 添加错误信息
*/
function pg_add_error($str)
{ 
    global $_pg_error;
    if(function_exists('write_log_error')){ 
        write_log_error($str);
    }
    $_pg_error[] = $str; 
}
/**
* 获取错误信息
*/
function pg_get_error()
{
    global $_pg_error;
    if ($_pg_error)
        return $_pg_error;
}

/***
 * 根据表名、字段 、条件 查寻多条记录
 * where https://medoo.in/api/where
 * select($table, $columns)
 * select($table, $columns, $where)
 * select($table, $join, $columns, $where)
 * @param string $table 表名
 * @param string $column 字段 
 * @param array $where  条件 [LIMIT=>1]  
 * @return array
 */
function pg_get($table, $join = null, $columns = null, $where = null){ 
    if(is_numeric($columns)){ 
        if($columns == 1){
            $columns = [];
            $is_field = false;
            if(is_array($join)){
                foreach($join as $k=>$v){
                    if(is_numeric($k)){
                        $is_field = true;
                        break;
                    }
                }
            }
            if(is_string($join)){
                $is_field = true;
            }
            if(!$join){
                $join = '*';
                $columns = []; 
                $where   = [];
            }else{
                if(!$is_field){  
                    $columns = $join; 
                    $where = [];
                } 
            } 
            return pg_get_one($table, $join,$columns, $where);
        } else{
           $join['LIMIT'] = $columns;
        } 
    }  
    return _pg_get($table, $join, $columns, $where);  
}

function _pg_get($table, $join = null, $columns = null, $where = null)
{ 
    $support_join = [
        '[>]',  // LEFT JOIN
        '[<]',  // RIGHT JOIN
        '[<>]', // FULL JOIN
        '[><]', //INNER JOIN
    ];
    /*
    pg_get("post", [ 
        "[>]account" => ["author_id" => "user_id"]
    ], [
        "post.title",
        "account.city"
    ]);
    */
    $is_join  = false;
    $is_field = false;
    if (is_array($join)) {
        foreach ($join as $k => $v) {
            if(in_array($k,$support_join)){
                $is_join = true;
            }
            if (is_string($v) && strpos($v, '(') !== false) {
                $join[$k] = pg_raw($v);
            }
            if(is_numeric($k)){
                $is_field = true;
            }
        }
        if(!$is_field && !$is_join){
            $columns = $join;
            $join    = "*";
        } 
    }  else if( is_string($join) && $join != "*"){
        if(strpos($join,',')!==false){
            $join =  explode(',',$join);
        }else{
            $join =  [$join];    
        }
        
    }
    if (is_string($columns) && strpos($columns, 'WHERE') !== FALSE) {
        $columns = pg_raw($columns);
    }  
    $all =  medoo_pg()->select($table, $join, $columns, $where); 
    if($all){
        foreach($all as &$v){
            pg_row_json_to_array($table,$v);
        }
    } 
    //查寻数据 
    if(pg_can_run_action()){ 
        foreach($all as &$v){
            if($v && is_array($v))
            do_action("pg_get_one.$table", $v);    
        }
    } 
    if(medoo_pg()->error){
        pg_add_error(medoo_pg()->errorInfo[2]); 
    }
    return $all; 
    
}
/** 
$lists = pg_select('do_order', [ 
            'count' => 'COUNT(`id`)',
            'total' => 'SUM(`total_fee`)',
            'date'  => "FROM_UNIXTIME(`inserttime`, '%Y-%m-%d')"
        ], 
        'WHERE `status` = 1 GROUP BY `date` LIMIT 30'
);  
 */
function pg_select($table, $join = "*", $columns = null, $where = null)
{
    return pg_get($table, $join, $columns, $where);
}
/**
 * 写入记录
 *
 * @param string $table 表名 
 * @param array  $data  数据 
 * @return array
 */
function pg_insert($table, $data = [],$don_run_action = false)
{
    foreach ($data as $k => $v) {
        if (substr($k, 0, 1) == "_") {
            unset($data[$k]);
        }
    } 
    //写入数据前 
    if(pg_can_run_action() && !$don_run_action){
        do_action("pg_insert.$table.before", $data);
        do_action("pg_save.$table.before", $data);
    }
    foreach($data as $k=>$v){ 
        if(pg_field_is_json($table,$k)){
            if($v && !is_array($v)){
                $arr = json_decode($v,true);
                if($arr){
                    $v = $arr;
                }else{
                    $v = yaml($v);
                } 
            }
            if(!$v){
                $v = [];
            }
        }
        if(is_array($v)){
            $data[$k] = json_encode($v,JSON_UNESCAPED_UNICODE);   
        }else{
            $data[$k] = addslashes($v);  
        }          
    }  
    $_pg    = medoo_pg()->insert($table, $data);
    $id = medoo_pg()->id();
    //写入数据后
    $action_data = [];
    $action_data['id'] = $id;
    $action_data['data'] = $data;
    if(pg_can_run_action() && !$don_run_action){
        do_action("pg_insert.$table.after", $action_data);
        do_action("pg_save.$table.after", $action_data);
    }
    if(medoo_pg()->error){
        pg_add_error(medoo_pg()->errorInfo[2]); 
    }
    return $id; 
}

/**
 * 更新记录
 *
 * @param string $table 表名 
 * @param array  $data  数据 
 * @return array
 */
function pg_update($table, $data = [], $where = [],$don_run_action = false)
{
    if(!pg_can_run_update()){
        exit('从库禁止运行update操作');
    }
    global $_pg_where;
    $_pg_where = $where;

    foreach ($data as $k => $v) {
        if (substr($k, 0, 1) == "_") {
            unset($data[$k]);
        }
    } 
    //更新数据前 
    if(pg_can_run_action() && !$don_run_action){
        do_action("pg_update.$table.before", $data);
        do_action("pg_save.$table.before", $data);
    }
    foreach($data as $k=>$v){
        if(pg_field_is_json($table,$k)){
            if($v && !is_array($v)){
                $arr = json_decode($v,true);
                if($arr){
                    $v = $arr;
                }else{
                    $v = yaml($v);
                } 
            }
            if(!$v){
                $v = [];
            }
        }
        if(is_array($v)){
            $data[$k] = json_encode($v,JSON_UNESCAPED_UNICODE);   
        }else if(is_string($v)){ 
            $data[$k] = addslashes($v);  
        }          
    } 
    $_pg    = medoo_pg()->update($table, $data, $where);
    $error = medoo_pg()->error;
    if ($error) { 
        throw new Exception($error);
    }
    $count =  $_pg->rowCount();
    //更新数据后
    $action_data = [];
    $action_data['where'] = $where; 
    $action_data['id']    = $where['id']?:'';
    $action_data['data']  = $data;
    if(pg_can_run_action() && !$don_run_action ){
        do_action("pg_update.$table.after", $action_data);
        do_action("pg_save.$table.after", $action_data);
    }
    if(medoo_pg()->error){
        pg_add_error(medoo_pg()->errorInfo[2]); 
    }
    return $count;
     
}

/**
 * 数据库事务
 *
 */
function pg_action($call)
{
    global $is_pg_action;
    $is_pg_action = true;
    $result = "";
    $_pg     = medoo_pg();
    $_pg->action(function ($_pg) use (&$result, $call) { 
        $call();
    });
}
/**
* 对数据进行
*/
function pg_for_update($table,$id){
    global $is_pg_action;
    if(!$is_pg_action){
        exit('query error:<br> pg_action(function(){<br> pg_for_update($table,$id);<br>});');
    }
    return pg_get($table,"*",[
        'id'=>$id,
        'FOR UPDATE'=>TRUE,
    ]);  
}
/**
 * 根据表名、字段 、条件 查寻一条记录
 *
 * @param string $table 表名
 * @param string $column 字段 
 * @param array  $where 条件 
 * @return array
 */
function pg_get_one($table, $join  = "*", $columns = null, $where = null)
{
    if (!$where) {
        $columns['LIMIT'] = 1;
    } else {
        $where['LIMIT']   = 1;
    }
    $_pg = pg_get($table, $join, $columns, $where);
    if ($_pg) {
        $one =  $_pg[0];
        if($one){
            pg_row_json_to_array($table,$one);    
        }        
        //查寻数据
        if(pg_can_run_action()){
            if($one && is_array($one))
            do_action("pg_get_one.$table", $one);
        }
        return $one;
    }
    return;
}  
/**
 * SQL查寻
 */
function pg_query($sql, $raw = null)
{
    if(!pg_can_run_update($sql)){
        exit('从库禁止运行update操作');
    }
    if ($raw === null) {
        return medoo_pg()->query($sql);
    }
    $q = medoo_pg()->query($sql, $raw);
    if ($q) {
        $all =  $q->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        //查寻数据
        if(pg_can_run_action()){
            do_action("pg_query", $all);
        }
        return $all;
    } else {
        return [];
    }
}
/**
 * 取最小值 
 * https://medoo.in/api/min
 * min($table, $column, $where)
 * min($table, $join, $column, $where)
 * @param string $table  表名
 * @param string $column 字段 
 * @param array $where   条件
 * @return void
 */
function pg_get_min($table, $join  = "*", $column = null, $where = null)
{
    return medoo_pg()->min($table, $join, $column, $where);
}

/**
 * 取最大值  
 * max($table, $column, $where)
 * max($table, $join, $column, $where)
 * @param string $table  表名
 * @param string $column 字段 
 * @param array $where   条件
 * @return void
 */
function pg_get_max($table, $join =  "*", $column = null, $where = null)
{
    return medoo_pg()->max($table, $join, $column, $where);
}

/**
 * 总数  
 * count($table, $where)
 * count($table, $join, $column, $where)
 * @param string $table  表名 
 * @param array $where   条件
 * @return void
 */
function pg_get_count($table, $join =  "*", $column = null, $where = null)
{
    return medoo_pg()->count($table, $join, $column, $where)?:0;
}

/**
 * 是否有记录
 * has($table, $where)
 * has($table, $join, $where)
 * @param string $table  表名 
 * @param array $where   条件
 * @return void
 */
function pg_get_has($table, $join = null, $where = null)
{
    return medoo_pg()->has($table, $join, $where);
}

/**
 * 随机取多条记录  
 * rand($table, $column, $where)
 * rand($table, $join, $column, $where)
 * @param string $table  表名
 * @param string $column 字段 
 * @param array $where   条件
 * @return void
 */
function pg_get_rand($table, $join = "*", $column = null, $where = null)
{
    return medoo_pg()->rand($table, $join, $column, $where);
}

/**
 * 取总和
 * sum($table, $column, $where)
 * sum($table, $join, $column, $where)
 * @param string $table  表名
 * @param string $column 字段 
 * @param array $where   条件
 * @return void
 */
function pg_get_sum($table, $join = "*", $column = null, $where = null)
{
    return medoo_pg()->sum($table, $join, $column, $where)?:0;
}

/**
 * 取平均值 
 * avg($table, $column, $where)
 * avg($table, $join, $column, $where)
 * @param string $table  表名
 * @param string $column 字段 
 * @param array $where   条件
 * @return void
 */
function pg_get_avg($table, $join = "*", $column = null, $where = null)
{
    return medoo_pg()->avg($table, $join, $column, $where);
}

/**
 * RAW
 * https://medoo.in/api/raw
 * raw('NOW()')
 * raw('RAND()')
 * raw('AVG(<age>)') 
 * @param string $raw
 * @return  
 */
function pg_raw($raw)
{
    return \Medoo\Medoo::raw($raw);
}

//删除
function pg_del($table, $where)
{
    //删除数据前
    if(pg_can_run_action()){
        do_action("pg_insert.$table.del", $where);
    }
    return medoo_pg()->delete($table, $where);
}

function pg_delete($table, $where)
{
    return pg_del($table, $where);
}

/**
 * 显示所有表名
 *
 * @param string $table 表名
 * @version 1.0.0
 * @author sun <sunkangchina@163.com>
 * @return void
 */
function pg_tables($table)
{
    $sql = "SHOW TABLES LIKE '%$table%';";
    $all = medoo_pg()->query($sql);
    foreach ($all as $v) {
        foreach ($v as $v1) {
            $list[] = $v1;
        }
    }
    return $list;
}
/**
 * 取表中字段
 */
function pg_fields($table, $has_key  = true)
{
    $sql   = "SHOW FULL FIELDS FROM `".$table."`";
    $lists = medoo_pg()->query($sql);
    $arr   = [];
    foreach ($lists as $vo) {
        if ($has_key) {
            $arr[$vo['Field']] = $vo;
        } else {
            $arr[] = $vo['Field'];
        }
    }
    return $arr;
}
/**
 *返回数据库允许的数据，传入其他字段自动忽略
 */ 
function pg_allow($table, $data)
{
    $fields = pg_fields($table);
    foreach ($data as $k => $v) {
        if (!$fields[$k]) {
            unset($data[$k]);
        }
    }
    return $data;
}

/**
 * 显示数据库表结构，支持markdown格式
 * @param string $name 数据库名
 * @version 1.0.0
 * @author sun <sunkangchina@163.com>
 */
function pg_tables_markdown($name = null, $show_markdown = false)
{
    global $config;
    if (!$name) {
        $name = $config['name'];
    }
    $sql  = "SHOW TABLE STATUS FROM `{$name}`";
    $all  = pg_query($sql, []);
    foreach ($all as $k => $v) {
        $sql   = "SHOW FULL FIELDS FROM `" . $v['Name'] . "`";
        $lists = medoo_pg()->query($sql, []);
        $all[$k]['FIELDS'] = $lists;
    }
    if (!$show_markdown) {
        return $all;
    }
    $str = "";
    foreach ($all as $v) {
        $str .= "###### " . $v['Name'] . " " . $v['Comment'] . "\n";
        $str .= "| 字段  |  类型 | 备注|\n";
        $str .= "| ------------ | ------------ |------------ |\n";
        foreach ($v['FIELDS'] as $vo) {
            $str .= "|  " . $vo['Field'] . " |  " . $vo['Type'] . " |" . $vo['Comment'] . "|\n";
        }
        $str .= "\n\n";
    }
    return $str;
}
/**
 * 取表中json字段
 */
function pg_field_json($table){
    static $table_fields;
    if(!isset($table_fields[$table])){
      $all = pg_fields($table); 
      $table_fields_row = [];
      foreach($all as $k=>$v){
        if($v['Type'] == 'json'){
            $table_fields_row[$k] = true; 
        }
      } 
      $table_fields[$table] = $table_fields_row;
    }
    return $table_fields[$table];
}
/**
 * 判断表中的字段是不是json
 */
function pg_field_is_json($table,$field){
    $table_fields = pg_field_json($table); 
    if(isset($table_fields[$field])){
      return true;
    }else{
      return false;
    }    
}

/**
* 把数据库中json字段转成array
* @param $table_name 表名
* @param $row_data 一行记录
*/
function pg_row_json_to_array($table_name,&$row_data = []){ 
    if(is_array($row_data)){
        foreach ($row_data as $key=>$val) {
            if(is_string($val) && pg_field_is_json($table_name,$key)){  
                $row_data[$key] = json_decode($val,true)?:[];   
            }
            else if(is_string($val) && is_json($val)){
                $row_data[$key] = json_decode($val,true);
            }else if(is_string($val)){
                $row_data[$key] = stripslashes($val);
            }
        } 
    }
}


/**
 * 数组排序
 * array_order_by($row,$order,SORT_DESC);
 */
if(!function_exists('array_order_by')){
    function array_order_by()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                if (!$data) return;
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        if ($args) {
            call_user_func_array('array_multisort', $args);
            return array_pop($args);
        }
        return;
    }
}


/**
 * 判断是否为json 
 */
if(!function_exists('is_json')){
    function is_json($data, $assoc = false)
    { 
        $data = json_decode($data, $assoc);
        if ($data && (is_object($data)) || (is_array($data) && !empty(current($data)))) {
            return $data;
        }
        return false;
    }
}


/**
 * 添加动作
 * @param string $name 动作名
 * @param couser $call function
 * @version 1.0.0
 * @author sun <sunkangchina@163.com>
 * @return mixed
 */
if(!function_exists("add_action")){
    function add_action($name, $call,$level = 20)
    {
        global $_app;
        if (strpos($name, '|') !== false) {
            $arr = explode('|', $name);
            foreach ($arr as $v) {
                add_action($v, $call,$level);
            }
            return;
        }
        $_app['actions'][$name][] = ['func'=>$call,'level'=>$level];  
    }
}

/**
 * 执行动作
 * @param  string $name 动作名
 * @param  array &$par  参数
 * @version 1.0.0
 * @author sun <sunkangchina@163.com>
 * @return  mixed
 */
if(!function_exists('do_action')){
    function do_action($name, &$par = null)
    {
        global $_app;
        if (!is_array($_app)) {
            return;
        }
        $calls  = $_app['actions'][$name]; 
        $calls  = array_order_by($calls,'level',SORT_DESC);  
        if ($calls) {
            foreach ($calls as $v) {
                $func = $v['func'];
                $func($par);
            }
        }
    }
}




/**
 *  分页 
 *  类似淘宝分页
 *  　　 
 * @since 2014-2015
 */
/**
 *<code>
 *类似淘宝分页 
 *  
 *   
 *$paginate = new pg_pager_html($row->num,1); 
 *$paginate->url = $this->url;
 *$limit = $paginate->limit;
 *$offset = $paginate->offset;
 *  
 *$paginate = $paginate->show(); 
 * 
 * 
.pagination li{
    list-style: none;
    float: left;
    display: inline-block;
    border: 1px solid #ff523b;
    margin-left: 10px;
    width: 40px;
    height: 40px;
    text-align: center;
    line-height: 40px;
    cursor: pointer;
}
.pagination .active{
   background: #eee; 
   border: 1px solid #000;
}

 *</code>   
 *
 */
 

class pg_pager_html
{
    public $page;
    public $pages;
    public $url;
    public $size;
    public $count;
    public $limit;
    public $offset;
    public $get = [];
    static $class;
    public $query = 'page';
    /**
     * 构造函数  
     */
    public function __construct($count, $size = 10)
    {
        $this->count = $count;
        $this->size = $size;
        //总页数
        $this->pages = ceil($this->count / $this->size);
        //当前页面
        $this->page = isset($_GET[$this->query])?(int)$_GET[$this->query]:'';
        if ($this->pages < 1) return;
        if ($this->page <= 1)
            $this->page = 1;
        if ($this->page >= $this->pages)
            $this->page = $this->pages;

        $this->offset = $this->size * ($this->page - 1);
        $this->limit = $this->size;
    }
    /**
     * 生成URL函数，如有需要，可自行改写
     * 调用函数 ($url,$par);
     * @param string $url 　 
     * @param string $par 　 
     * @return  string
     */
    public function url($url, $par = [])
    {
        $url = $url . '?' . http_build_query($par);
        return $url;
    }
    public function next($class = 'pagination')
    {
        $next = $this->page + 1;
        $p = $_GET;
        $p[$this->query] = $next;
        if ($next <= $this->pages) {
            return '<a rel="' . $next . '" class="' . $class . '" href="' . $this->url($this->url, $p) . '">下一页</a>';
        }
        return;
    }

    /**
     * 显示分页 pagination
     * @param string $class 　 
     * @return  string
     */
    public function show($class = 'pagination')
    {
        if (static::$class) $class = static::$class;
        $str = '<ul class="' . $class . '">';
        $pre = $this->page - 1;
        $p = $_GET;
        $p[$this->query] = $pre > 0 ? $pre : 1;
        if ($pre > 0)
            $str .= '<li><a href="' . $this->url($this->url, $p) . '">&laquo;</a></li>';
        if ($this->pages < 2) return;
        $pages[1] = 1;
        $pages[2] = 2;
        $i = $this->page - 2 <= 1 ? 1 : $this->page - 2;
        $e = $this->page + 2 >= $this->pages ? $this->pages : $this->page + 2;
        if ($e < 5 && $this->pages >= 5)
            $e = 5;
        $pages['s'] = null;
        if ($i > 0) {
            for ($i; $i < $e + 1; $i++) {
                $pages[$i] = $i;
            }
        }
        $j = 0;
        foreach ($pages as $k => $v) {
            if ($j == 3) $n = $k;
            $j++;
        }

        if ($this->pages > 5) {
            if ($n != 3)
                $pages['s'] = "...";
            if ($e < $this->pages)
                $pages['e'] = "...";
        }
        $p = $_GET;
        if ($this->get) {
            foreach ($this->get as $d) {
                unset($p[$d]);
            }
        }

        foreach ($pages as $j) {
            $active = null;
            if ($j == $this->page)
                $active = "class='active'";
            if (!$j) continue;
            $p[$this->query] = $j;
            if ($j == '...')
                $str .= "<li $active><a href='javascript:void(0);' class='no'>$j</a></li>";
            else
                $str .= "<li $active><a href='" . $this->url($this->url, $p) . "'>$j</a></li>";
        }

        if ($this->page + 3 < $this->pages && $this->pages > 6) {
            $str .= "<li><a href='" . $this->url($this->url, [$this->query => $this->pages - 1] + $p) . "'>" . ($this->pages - 1) . "</a></li>";
        }
        if ($this->page + 2 < $this->pages && $this->pages > 6) {
            $str .= "<li><a href='" . $this->url($this->url, [$this->query => $this->pages] + $p) . "'>$this->pages</a></li>";
        }
        $p[$this->query] = $next = $this->page + 1;
        if ($next <= $this->pages)
            $str .= '<li><a href="' . $this->url($this->url, $p) . '">&raquo;</a></li>';


        $str .= "</ul>";
        return $str;
    }
}


/**
* 返回两个日期之间
* $date1 = '2022-11-01';
* $date2 = '2022-12-14';
* 字段是datetime类型
*/
function pg_between_date($field,$date1,$date2){
    $start_time = date("Y-m-d 00:00:01",strtotime($date1));
    $end_time   = date("Y-m-d 23:59:59",strtotime($date2));
    $where = [];
    $where[$field.'[>=]'] = $start_time;
    $where[$field.'[<]'] = $end_time;
    return $where;
}
/**
* 返回两个月份之间
* $date1 = '2022-11';
* $date2 = '2022-12';
* 字段是datetime类型
*/
function pg_between_month($field,$date1,$date2){
    $start_time = date("Y-m-d 00:00:01",strtotime($date1."-01"));
    $end_time   = date("Y-m-d 00:00:00",strtotime($date2."-01"." +1 month"));
    $where = [];
    $where[$field.'[>=]'] = $start_time;
    $where[$field.'[<]'] = $end_time;
    return $where;
}
