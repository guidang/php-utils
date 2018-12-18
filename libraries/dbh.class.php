<?php

/**
 * PDO 扩展
 * File:   dbh.class.php
 * Author: Skiychan <dev@skiy.net>
 * Created: 2018/09/18
 */

class Dbh extends PDO {
    public $cache;
    public $count = 0;
    public $last_query = '';
    public $insert_id = 0; //新增值的主键ID

    public $expire = -2; //缓存时间 秒(-2不缓存，-1永久，0默认配置，>0指定时长)
    public $force = false; //强制更新 - 越过缓存，直接重新获取数据,并设置到缓存
    public $cache_key = ''; //自定义缓存KEY

    public $pre = '';

    protected $sqlstr = '';

    /**
     * 获取所有数据
     * @param $sql
     * @param int $expire >0指定时间, 0默认, -2不设置缓存, -1永久, 其它不设置缓存
     * @return array
     */
    public function sql_fetch_all($sql, $expire = -2) {
        $this->cache();
        $key = $this->set_cache_key($sql);
        $result = $this->cache_get($key);

        if ($this->force) {
            $result = null;
        }

        if (empty($result)) {
            $stmt = $this->prepare_sql($sql);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $result = $stmt->fetchAll();

            $this->cache_set($key, $result, $expire);
        }

        $this->count = count($result);

        $this->clean();
        return $result;
    }

    /**
     * 获取一条数据 = sql_select
     * @param $sql
     * @param int $expire
     * @return array|mixed
     */
    public function sql_fetch_one($sql, $expire = -2) {
        $this->cache();
        $key = $this->set_cache_key($sql);
        $result = $this->cache_get($key);

        if ($this->force) {
            $result = null;
        }

        if (empty($result)) {
            $stmt = $this->prepare_sql($sql);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $result = $stmt->fetch();

            $this->cache_set($key, $result, $expire);
        }

        $this->clean();
        return $result;
    }

    /**
     * 获取数据总数
     * @param $sql
     * @param int $expire
     * @return array|mixed
     */
    public function sql_fetch_count($sql, $expire = -2) {
        $this->cache();
        $key = $this->set_cache_key($sql . '_count');
        $result = $this->cache_get($key);

        if ($this->force) {
            $result = null;
        }

        if (empty($result)) {
            $stmt = $this->prepare_sql($sql);
            $stmt->execute();
            $result = $stmt->rowCount(); //count
//            $result = $stmt->fetchColumn();
            $this->cache_set($key, $result, $expire);
        }

        $this->clean();
        return $result;
    }

    /**
     * 插入数据
     * @param $table
     * @param array $data
     * @return bool
     */
    public function db_insert($table, $data = array()) {
        if (empty($table) || empty($data)) {
            return false;
        }

        $db = $this->add($table)
            ->data($data);

        $result = $this->insert_sql($db->sqlstr, $data);

        $this->clean();
        return $result;
    }

    /**
     * 根据条件更新数据
     * @param $table
     * @param array $data
     * @param array $where
     * @param string $orderby
     * @param int $limit
     * @param array $where_ext
     * @return bool
     */
    public function db_update($table, $data = array(), $where=array(), $orderby = '', $limit = 0, $where_ext = array()) {
        $where_ext_str = $this->make_and_wh($where_ext);

        $db = $this->update($table)
            ->set($data)
            ->where($where)
            ->where($where_ext_str)
            ->orderby($orderby);

        $limit = $this->make_limit($limit);
        if (!empty($limit)) {
            $db->limit($limit[0], $limit[1]);
        }

        $stmt = $this->prepare_sql($db->sqlstr);
        $result = $stmt->execute();

        $this->count = $stmt->rowCount();

        $this->clean();
        return $result;
    }

    /**
     * 重置表
     * @param $table
     * @return bool|PDOStatement
     */
    public function db_truncate($table) {
        if (empty($table)) {
            return false;
        }

        $db = $this->truncate($table);

        $result = $this->exec_sql($db->sqlstr);

        $this->clean();
        return $result;
    }

    /**
     * 根据条件删除数据
     * @param $table
     * @param array $where
     * @param string $orderby
     * @param int $limit
     * @param array $where_ext
     * @return bool
     */
    public function db_delete($table, $where=array(), $orderby = '', $limit = 0, $where_ext = array()) {
        $where_ext_str = $this->make_and_wh($where_ext);

        $db = $this->delete($table)
            ->where($where)
            ->where($where_ext_str)
            ->orderby($orderby);

        $limit = $this->make_limit($limit);
        if (!empty($limit)) {
            $db->limit($limit[0], $limit[1]);
        }

        $stmt = $this->prepare_sql($db->sqlstr);
        $result = $stmt->execute();

        $this->count = $stmt->rowCount();

        $this->clean();
        return $result;
    }

    /**
     * 根据条件查找全部数据
     * @param $table
     * @param string $field
     * @param array $where
     * @param string $orderby
     * @param array $limit
     * @param array $where_ext
     * @param string $groupby
     * @param bool|int $expire
     * @return array|mixed
     */
    public function db_find_all($table, $field='*', $where = array(), $orderby = '', $limit = array(), $where_ext = array(), $groupby='', $expire = false) {
        ($expire === false) || $this->expire = $expire;

        $where_ext_str = $this->make_and_wh($where_ext);

        empty($field) && $field = '*';

        $db = $this->field($field)
            ->from($table)
            ->where($where)
            ->where($where_ext_str);

        if (!empty($limit) && ($limit == "rows")) {
            $result = $db->count_all_results();
            return $result;
        }

        if (! empty($groupby)) {
            $db->groupby($groupby);
        }

        if (!empty($orderby)) {
            $db->orderby($orderby);
        }

        $limit = $this->make_limit($limit);
        if (!empty($limit)) {
            $db->limit($limit[0], $limit[1]);
        }

        $result = $db->result_array();
        return $result;
    }

    /**
     * 根据条件查找一条数据
     * @param $table
     * @param string $field
     * @param array $where
     * @param string $orderby
     * @param array $where_ext
     * @param string $groupby
     * @param bool|int $expire
     * @return array|mixed
     */
    public function db_find_one($table, $field='*', $where = array(), $orderby = '', $where_ext = array(), $groupby = '', $expire = false) {
        ($expire === false) || $this->expire = $expire;

        $where_ext_str = $this->make_and_wh($where_ext);

        if (empty($field)) {
            $field = '*';
        }

        $db = $this->field($field)
            ->from($table)
            ->where($where)
            ->where($where_ext_str);

        if (! empty($groupby)) {
            $db->groupby($groupby);
        }

        if (!empty($orderby)) {
            $db->orderby($orderby);
        }

        $result = $db->row_array();
        return $result;
    }

    /**
     * 获取数量
     * @param $table 表名(不带前缀)
     * @param array $where
     * @param array $where_ext
     * @param bool|int $expire
     * @return array|mixed
     */
    public function db_find_count($table, $where=array(), $where_ext=array(), $expire = false) {
        ($expire === false) || $this->expire = $expire;

        $field = '*';
        $where_ext_str = $this->make_and_wh($where_ext);

        $result = $this->field($field)
            ->from($table)
            ->where($where)
            ->where($where_ext_str)
            ->count_all_results();

        return $result;
    }

    /**
     * 构造重置表
     * @param $table
     * @return $this
     */
    protected function truncate($table) {
        $table = $this->pre . $table;
        $this->sqlstr = "TRUNCATE TABLE {$table}";
        return $this;
    }

    /**
     * 构造插入表
     * @param $table
     * @return $this
     */
    protected function add($table) {
        $table = $this->pre . $table;
        $this->sqlstr = "INSERT INTO {$table} ";
        return $this;
    }

    /**
     * 构造更新表
     * @param $table
     * @return $this
     */
    protected function update($table) {
        $table = $this->pre . $table;
        $this->sqlstr = "UPDATE {$table} ";
        return $this;
    }

    /**
     * 构造删除表
     * @param $table
     * @return $this
     */
    protected function delete($table) {
        $this->sqlstr = 'DELETE ';
        $this->from($table);

        return $this;
    }

    /**
     * 设置字段
     * @param $field
     * @return $this
     */
    protected function field($field) {
        $this->sqlstr = "SELECT {$field} ";
        return $this;
    }

    /**
     * 设置 table
     * @param $table
     * @return $this
     */
    protected function from($table) {
        $table = $this->pre . $table;
        $this->sqlstr .= "FROM `{$table}` ";
        return $this;
    }

    /**
     * 设置数据 - insert 使用
     * @param $data
     * @return $this
     */
    protected function data($data) {
        if (empty($data)) {
            return $this;
        }

        $sql = '';
        if (is_string($data)) {
            $sql = $data;
        }

        if (is_array($data)) {
            $keys = array();
            $values = array();
            foreach ($data as $key => $value) {
                $keys[] = $this->backquote($key);
                // $values[] = $this->value($value); //方式1
                // $values[] = '?';//方式2,写法1
                $values[] = ":{$key}";//方式2,写法2
            }

            //方式1
//            $sql = sprintf("( %s ) VALUES ( '%s' )", implode(' , ', $keys), implode("' , '", $values));

            //bindParam 写法
            $place_holders = implode(',', $values);

            $sql = sprintf("( %s ) VALUES ( %s )", implode(' , ', $keys), $place_holders);  
//            var_dump($sql);
        }

        if (strstr(strtolower($this->sqlstr), 'into')) {
            $this->sqlstr .= ' ' . $sql . ' ';
        }

        return $this;
    }

    /**
     * 设置数据 - update 使用
     * @param $data
     * @return $this
     */
    protected function set($data) {
        if (empty($data)) {
            return $this;
        }

        $sql = '';
        if (is_string($data)) {
            $sql = $data;
        }

        if (is_array($data)) {
            $arr = array();
            foreach ($data as $key => $value) {
                $arr[] = sprintf("%s = '%s'", $this->backquote($key), $this->value($value));
            }

            if (!empty($arr)) {
                $sql = implode(' , ', $arr);
            }
        }

        if (strstr(strtolower($this->sqlstr), 'set')) {
            $this->sqlstr .= ', ' . $sql . ' ';
        } else {
            $this->sqlstr .= ' SET ' . $sql . ' ';
        }

        return $this;
    }

    /**
     * 构造 where 语句
     * @param array $where
     * @return $this
     */
    protected function where($where = array()) {
        if (empty($where)) {
            return $this;
        }

        $sql = '';
        if (is_string($where)) {
            $sql = $where;
        }

        if (is_array($where)) {
            $arr = array();
            foreach ($where as $key => $value) {
                $arr[] = sprintf("%s = '%s'", $this->backquote($key), $this->value($value));
            }

            if (!empty($arr)) {
                $sql = implode(' AND ', $arr);
            }
        }

        if (strstr(strtolower($this->sqlstr), 'where')) {
            $this->sqlstr .= ' AND ' . $sql . ' ';
        } else {
            $this->sqlstr .= ' WHERE ' . $sql . ' ';
        }

        return $this;
    }

    /**
     * 构造排序
     * @param string $orderby
     * @return $this
     */
    protected function orderby($orderby = '') {
        if (empty($orderby)) {
            return $this;
        }

        if (strstr(strtolower($orderby), 'desc') || strstr(strtolower($orderby), 'asc')) {
            $this->sqlstr .= 'ORDER BY ' . $orderby . ' ';
        }
        return $this;
    }

    /**
     * 构造分组
     * @param string $groupby
     * @return $this
     */
    protected function groupby($groupby = '') {
        if (empty($groupby)) {
            return $this;
        }

        $this->sqlstr .= 'GROUP BY ' . $groupby . ' ';
        return $this;
    }

    /**
     * 构造 limit
     * @param $count
     * @param $offset
     * @return $this
     */
    protected function limit($count, $offset=0) {
        if (empty($offset)) {
            $this->sqlstr .= "LIMIT {$count} ";
        } else {
            $this->sqlstr .= "LIMIT {$offset}, {$count} ";
        }
        return $this;
    }

    /**
     * 查找全部数据
     * @return array
     */
    protected function result_array() {
        $result = $this->sql_fetch_all($this->sqlstr, $this->expire);
        return $result;
    }

    /**
     * 查找一条数据
     * @return array|mixed
     */
    protected function row_array() {
        $result = $this->sql_fetch_one($this->sqlstr, $this->expire);
        return $result;
    }

    /**
     * 查找数量
     * @return array|mixed
     */
    protected function count_all_results() {
        $result = $this->sql_fetch_count($this->sqlstr, $this->expire);
        return $result;
    }

    /**
     * 预处理SQL (写入日志)
     * @param $sql
     * @return bool|PDOStatement
     */
    protected function prepare_sql($sql) {
        if (defined('SQL_DEBUG') && SQL_DEBUG) {
            defined("LOGPATH") || define('LOGPATH', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'logs/');
            file_put_contents(LOGPATH . "sql_prapare.log", PHP_EOL . date('Y-m-d H:i:s') . PHP_EOL . $sql . PHP_EOL, FILE_APPEND);
        }
        $this->last_query = $sql;
        return $this->prepare($sql);
    }

    /**
     * 插入数据
     * @param $sql SQL 语句
     * @param $data 数组数据
     * @return bool
     */
    protected function insert_sql($sql, $data) {
        if (defined('SQL_DEBUG') && SQL_DEBUG) {
            defined("LOGPATH") || define('LOGPATH', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'logs/');
            file_put_contents(LOGPATH . "sql_insert.log", PHP_EOL . date('Y-m-d H:i:s') . PHP_EOL . $sql . PHP_EOL, FILE_APPEND);
        }

        try {

            $this->last_query = $sql;
            $stmt = $this->prepare($sql);

            if (is_array($data)) {
                file_put_contents(LOGPATH . "sql_insert.log", implode(',', array_values($data)) . PHP_EOL, FILE_APPEND);
                // $values = array_values($data); //方式2写法1
                $values = []; //方式2写法2
                foreach ($data as $key => $value) {
                    $values[":{$key}"] = $this->value($value);
                }
                $result = $stmt->execute($values);
            } else {
                $result = $stmt->execute();
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            $result = false;
            file_put_contents(LOGPATH . "sql_insert.log", $e->getMessage() . PHP_EOL, FILE_APPEND);
        }

        return $result;
    }

    /**
     * 执行一条语句
     * @param $sql
     * @return bool|PDOStatement
     */
    public function query_sql($sql) {
        if (defined('SQL_DEBUG') && SQL_DEBUG) {
            defined("LOGPATH") || define('LOGPATH', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'logs/');
            file_put_contents(LOGPATH . "sql_query.log", PHP_EOL . date('Y-m-d H:i:s') . PHP_EOL . $sql . PHP_EOL, FILE_APPEND);
        }
        $this->last_query = $sql;
        return $this->query($sql);
    }

    /**
     * 执行一条语句
     * @param $sql
     * @return bool|PDOStatement
     */
    public function exec_sql($sql) {
        if (defined('SQL_DEBUG') && SQL_DEBUG) {
            defined("LOGPATH") || define('LOGPATH', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'logs/');
            file_put_contents(LOGPATH . "sql_exec.log", PHP_EOL . date('Y-m-d H:i:s') . PHP_EOL . $sql . PHP_EOL, FILE_APPEND);
        }
        $this->last_query = $sql;
        return $this->exec($sql);
    }

    /**
     * 复位
     */
    protected function clean() {
        $this->sqlstr = '';
        $this->expire = -2;
        $this->cache_key = '';
        $this->force = false;
    }

    /**
     * 设置自定义缓存KEY
     * @param $str
     * @return string
     */
    protected function set_cache_key($str) {
        $key = md5($str);
        if (! empty($this->cache_key)) {
            $key = $this->cache_key;
        }

        $this->cache_key = '';
        return CACHE_PRE . $key;
    }

    /**
     * 构造反引号
     * @param $key a => `a`
     * @return string
     */
    protected function backquote($key) {
        $key = str_replace('`', '', $key);
        if (strstr($key, '.')) {
            $key = '`' . str_replace('.', '`.`', $key) . '`';
        } else {
            $key = "`{$key}`";
        }
        return $key;
    }

    /**
     * 构造值
     * @param $value
     * @return mixed
     */
    protected function value($value) {
        $value = str_replace("'", "\'", $value);
        return $value;
    }

    /**
     * 判断是否为查询总数
     * @param bool $limit
     * @return bool
     */
    protected function is_count($limit = FALSE) {
        if (!empty($limit) && ($limit == "rows")) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 多重条件
     * @param array $where_arr
     * @return array|string
     */
    protected function make_and_wh($where_arr = array()) {
        $result = array();
        if (!empty($where_arr)) {
            if (is_string($where_arr)) {
                $result = $where_arr;
            }

            if (is_array($where_arr)) {
                return implode(' AND ', $where_arr);
            }
        }

        return $result;
    }

    /**
     * 设置查询数量 或 偏移量
     * @param $limit 数量 或 偏移量
     * @return mixed
     */
    protected function make_limit($limit) {
        if (empty($limit)) {
            return false;
        }

        (is_string($limit) or is_numeric($limit)) && $limit = explode(",", $limit);
        if (is_array($limit)) {
            count($limit) == 1 && $limit[] = 0;
        }

        if (empty($limit) || !is_array($limit)) {
            $limit = false;
        }

        return $limit;
    }

    /**
     * WHERE IN 构造
     * @param $field
     * @param array $data
     * @return bool|string
     */
    public function where_in($field, $data=[]) {
        return $this->_where_in_or_not($field, $data, true);
    }

    /**
     * WHERE IN 构造
     * @param $field
     * @param array $data
     * @return bool|string
     */
    public function where_not_in($field, $data=[]) {
        return $this->_where_in_or_not($field, $data, false);
    }

    /**
     * where in / where not in
     * @param $field
     * @param $data
     * @param bool $is_in 为 in 还是 not in
     * @return bool|string
     */
    protected function _where_in_or_not($field, $data, $is_in=true) {
        if (empty($data)) {
            return false;
        }

        $sql = '';
        if (is_string($data)) {
            $sql = " ( {$data} ) ";
        }

        if (is_array($data)) {
            $sql = " ('" . implode("','", $data) . "') ";
        }

        if (empty($sql)) {
            return false;
        }

        $is_in_str = $is_in ? 'IN' : 'NOT IN';
        $sql = $this->backquote($field) . " {$is_in_str} " . $sql;
        return $sql;
    }

    /**
     * 配置缓存
     */
    public function cache() {
        switch (CACHE) {
            case 'REDIS':
                try {
                    $redis = new Redis();
                    $redis->connect(REDIS_HOST, REDIS_PORT, REDIS_TIMEOUT);
                    $redis->auth(REDIS_PWD);
                    $is_connect = $redis->ping();
                    if ($is_connect != '+PONG') {
                        throw new Exception('Redis is not pong');
                    }

                    $redis->select(REDIS_DB);
                    $this->cache = $redis;
                } catch (Exception $e) {
                    Common::Log($e->getMessage(), 'redis_error');
                }
                break;
        }
    }

    /**
     * 获取缓存
     * @param $key
     * @return array
     */
    public function cache_get($key) {
        $cache = $this->cache;
        $result = array();

        if (empty($cache)) {
            return $result;
        }

        switch (CACHE) {
            case 'REDIS':
//                $cache = new Redis();
                $resp_str = $cache->get($key);
                $result = json_decode($resp_str, true);
                ($result === null) && $result = $resp_str;
                //$result = unserialize($resp_str);
                break;
        }

        return $result;
    }

    /**
     * 设置缓存
     * @param $key
     * @param $value
     * @param int $expire 超时时间 (>0.指定时间, 0.默认时间, -1.永久, >-1.不设置缓存)
     * @return bool
     */
    public function cache_set($key, $value, $expire = -2) {
        $cache = $this->cache;

        $is_do = false;
        if (empty($cache)) {
            return $is_do;
        }

        (is_string($value) || is_numeric($value)) || $value = json_encode($value);
//        $value = serialize($value);

        if ($expire > 0) {
            $expire_time = $expire;
        } else if ($expire == 0) {
            $expire_time = CACHE_EXPIRE;
        } else if ($expire == -1) {
            $expire_time = -1;
        } else {
            return $is_do;
        }

        switch (CACHE) {
            case 'REDIS':
//                $cache = new Redis();
                if ($expire_time <= 0) {
                    $is_do = $cache->set($key, $value);
                } else {
                    $is_do = $cache->setex($key, $expire_time, $value);
                }
                break;
        }

        return $is_do;
    }

    /**
     * 删除缓存
     * @param $key
     * @return bool
     */
    public function cache_del($key) {
        $cache = $this->cache;

        $is_do = false;
        if (empty($cache)) {
            return $is_do;
        }

        switch (CACHE) {
            case 'REDIS':
//                $cache = new Redis();
                $is_do = $cache->del($key);
                break;
        }

        return $is_do;
    }

    /**
     * 清空缓存
     * @return bool
     */
    public function cache_flush() {
        $cache = $this->cache;

        $is_do = false;
        if (empty($cache)) {
            return $is_do;
        }

        switch (CACHE) {
            case 'REDIS':
//                $cache = new Redis();
                $is_do = $cache->flushDB();
                break;
        }

        return $is_do;
    }
}