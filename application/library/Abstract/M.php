<?php
/**
 * Model抽象类
 *
 * @package abstract
 * @author  shimin <shimin1@staff.sina.com.cn>
 */
abstract class Abstract_M {

    const CACHE_LONG = 86400;
    const CACHE_SHORT = 3600;
    const CACHE_MICRO = 600;
    //表
    protected $_tbl_name;
    protected $_tbl_alis_name = null;
    //数据库配置信息
    protected $dbConfig = 'default';

    var $ar_select              = array();
    var $ar_distinct            = FALSE;
    var $ar_from                = array();
    var $ar_join                = array();
    var $ar_where               = array();
    var $ar_like                = array();
    var $ar_groupby             = array();
    var $ar_having              = array();
    var $ar_keys                = array();
    var $ar_limit               = FALSE;
    var $ar_offset              = FALSE;
    var $ar_order               = FALSE;
    var $ar_orderby             = array();
    var $ar_set                 = array();
    var $ar_wherein             = array();
    var $ar_aliased_tables      = array();
    var $ar_store_array         = array();
    var $rec_all_count          = 0;

    // Private variables
    var $_protect_identifiers   = TRUE;
    var $_reserved_identifiers  = array('*'); // Identifiers that should NOT be escaped
    var $swap_pre       = '';
    // The character used for escaping
    var $_escape_char = '`';
    var $_count_string = 'SELECT COUNT(*) AS ';
    var $_random_keyword = ' RAND()'; // database specific random keyword

    //valid data
    protected $_validate = array();
    var $_err = '';//最近错误信息

    var $_truncate_strig = 'TRUNCATE TABLE ';
    var $dbprefix = '';

    var $predefined = array('DATE_FORMAT');
    /**
     * 禁止实例化该类，只能是静态调用
     */
    protected function __construct() {

    }

    public function set_escape_char($char='`'){
        $this->_escape_char = '';
        return $this;
    }
    /**
     * 切库
     * @param $dbConfig
     */
    public function switchDb($dbConfig) {
        $this->dbConfig = $dbConfig;
    }

    //开启事务
    public function startTrans(){
        return Comm_Db::getDbWrite($this->dbConfig)->autocommit(false);
    }

    //事务回滚
    public function rollback(){
        return Comm_Db::getDbWrite($this->dbConfig)->rollback();
    }

    //事务提交
    public function commit(){
        Comm_Db::getDbWrite($this->dbConfig)->commit();
        Comm_Db::getDbWrite($this->dbConfig)->autocommit(true);
    }

    // --------------------------------------------------------------------

    /**
     * 查询方法
     *
     * @param   string
     * @return  object
     *
     * 单表查询时：
     *
        $db = BizInfoModel::instance();
        $result = $db -> field('id,account,password')....
     *
     * 或者
     *
        $db = BizInfoModel::instance();
        $field = array( 'id','title');
        $result = $db -> field('id,account,password')....
     *
     *
     * JOIN查询时：
     *
     * 最初的时候，需要直接写表名和表字段。
     *
        $db = ArticleModel::instance();
        $db->join('dealer_article_content content', 'content.aid = article.id','left');
        $result = $db -> field('article.id, article.title, content.content')
     *
     * 为配合join方法改进，可使用以下方式传入$select
     *
        $db = Mod_ArticleModel::instance();
        $db2 = Mod_ArticleContentModel::instance();
        $field = array(
            $db->_tbl_alis_name  => 'id,title',
            $db2->_tbl_alis_name => 'aid,content'
            );
        $result = $db-> field($field).......
     *
     */
    public function field($select = '*', $escape = NULL)
    {
        if(is_string($select)){
            foreach ($this->predefined as $v) {
                if(stripos($select, $v) !== FALSE){
                    $this->ar_select[] = $select;
                    return $this;
                }
            }
        }
        /*557 By: WangWeiqiang <weiqiang6@staff.sina.com.cn> At:2016-04-08 16:55:56 Mark:添加新逻辑，如果$select为数组时遍历条件并拼接成完整的搜索字段 */
        if (is_array($select))
        {
            if (!empty($select))
            {
                foreach ($select as $k => $v)
                {
                    $prefix= is_numeric($k)?'':$k.'.';
                    if ($v == '*')
                    {
                        $this->ar_select[] = $prefix.'*';
                        $this->ar_no_escape[] = $escape;
                    }
                    else
                    {
                        $v2 = explode(',', $v);
                        if ($v2 != '')
                        {
                            foreach ($v2 as $k2 => $v2)
                            {
                                $this->ar_select[] = $prefix.$v2;
                                $this->ar_no_escape[] = $escape;
                            }
                        }
                    }
                }
            }
        } else {

            if (is_string($select))
            {
                $select = explode(',', $select);
            }

            foreach ($select as $val)
            {
                $val = trim($val);

                if ($val != '')
                {
                $this->ar_select[] = $val;//存放待查询的字段
                $this->ar_no_escape[] = $escape;
            }
        }
    }
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * 查询最大值
     *
     * @param   string  the field
     * @param   string  an alias
     * @return  object
     */
    public function select_max($select = '', $alias = '')
    {
        return $this->_max_min_avg_sum($select, $alias, 'MAX');
    }

    // --------------------------------------------------------------------

    /**
     * 查询最小值
     *
     * Generates a SELECT MIN(field) portion of a query
     *
     * @param   string  the field
     * @param   string  an alias
     * @return  object
     */
    public function select_min($select = '', $alias = '')
    {
        return $this->_max_min_avg_sum($select, $alias, 'MIN');
    }

    // --------------------------------------------------------------------

    /**
     * 查询平均值
     *
     * Generates a SELECT AVG(field) portion of a query
     *
     * @param   string  the field
     * @param   string  an alias
     * @return  object
     */
    public function select_avg($select = '', $alias = '')
    {
        return $this->_max_min_avg_sum($select, $alias, 'AVG');
    }

    // --------------------------------------------------------------------

    /**
     * 查询和
     *
     * Generates a SELECT SUM(field) portion of a query
     *
     * @param   string  the field
     * @param   string  an alias
     * @return  object
     */
    public function select_sum($select = '', $alias = '')
    {
        return $this->_max_min_avg_sum($select, $alias, 'SUM');
    }

    // --------------------------------------------------------------------

    /**
     * 处理4种类型函数
     *
     *  select_max()
     *  select_min()
     *  select_avg()
     *  select_sum()
     *
     * @param   string  the field
     * @param   string  an alias
     * @return  object
     */
    protected function _max_min_avg_sum($select = '', $alias = '', $type = 'MAX')
    {
        if ( ! is_string($select) OR $select == '')
        {
            $this->display_error('db_invalid_query');
        }

        $type = strtoupper($type);

        if ( ! in_array($type, array('MAX', 'MIN', 'AVG', 'SUM')))
        {
            $this->display_error('Invalid function type: '.$type);
        }

        if ($alias == '')
        {
            $alias = $this->_create_alias_from_table(trim($select));
        }

        $sql = 'SELECT '.$type.'('.$this->_protect_identifiers(trim($select)).') AS '.$alias;

        $sql = $this->_compile_select($sql);

        $ret = Comm_Db::getDbRead($this->dbConfig)->getRow($sql);
        return $ret[$alias];
    }

    // --------------------------------------------------------------------

    /**
     * 构造别名
     *
     * @param   string
     * @return  string
     */
    protected function _create_alias_from_table($item)
    {
        if (strpos($item, '.') !== FALSE)
        {
            return end(explode('.', $item));
        }

        return $item;
    }

    // --------------------------------------------------------------------

    /**
     * 唯一值
     *
     * Sets a flag which tells the query string compiler to add DISTINCT
     *
     * @param   bool
     * @return  object
     */
    public function distinct($val = TRUE)
    {
        $this->ar_distinct = (is_bool($val)) ? $val : TRUE;
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Join 关联表
     *
     * @param   string
     * @param   string  the join condition
     * @param   string  the type of join
     * @return  object
     *
     * 最初的c层join写法为：
     *   $db->join('dealer_article_content content', 'content.aid = article.id','left');
     *
     * 为了减少C层直接写表名的问题。重新改进，并可以使用以下写法
     *   $TB_extend = Mod_ArticleContentModel::instance();
     *   $TB->join($TB_extend,array('id' => 'aid'),'left');
     * 先初始一个扩展表对象，然后把对象、主表关联字段和扩展表关联字段组成的数组、连接方式 传入进来
     *
     */
    public function join($table, $cond, $type = '')
    {
        if ($type != '')
        {
            $type = strtoupper(trim($type));

            if ( ! in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER')))
            {
                $type = '';
            }
            else
            {
                $type .= ' ';
            }
        }

        // Extract any aliases that might exist.  We use this information
        // in the _protect_identifiers to know whether to add a table prefix
        /*557 By: WangWeiqiang <weiqiang6@staff.sina.com.cn> At:2016-04-08 15:55:43
            判断$table是否是初始化后的对象，如果是对象，取出tablename，如果不是则直接认为是表名
            */
        if (is_object($table)) {
           $this->_track_aliases($table->_tbl_name.' '.$table->_tbl_alis_name);
           $table_name=$table->_tbl_alis_name;
           $table=$table->_tbl_name.' '.$table->_tbl_alis_name;
       } else {
           $this->_track_aliases($table);
       }

        /*557 By: WangWeiqiang <weiqiang6@staff.sina.com.cn> At:2016-04-08 15:55:43
        Mark:判断join条件是否是数组，是则从key和value中分别取出两个连接字段，否则用正则取出 */
        if (is_array($cond)) {
                $match[1] = $this->_protect_identifiers(key($cond));
                $match[3] = $this->_protect_identifiers($cond[key($cond)]);
                $cond = $this->_tbl_alis_name.'.'.$match[1]." = ".$table_name.'.'.$match[3];
        } else {
                    // Strip apart the condition and protect the identifiers
            if (preg_match('/([\w\.]+)([\W\s]+)(.+)/', $cond, $match))
            {
                $match[1] = $this->_protect_identifiers($match[1]);
                $match[3] = $this->_protect_identifiers($match[3]);

                $cond = $match[1].$match[2].$match[3];
            }
        }


        // Assemble the JOIN statement
        $join = $type.'JOIN '.$this->_protect_identifiers($table, TRUE, NULL, FALSE).' ON '.$cond;

        $this->ar_join[] = $join;


        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * 查询条件
     *
     * @param   mixed
     * @param   mixed
     * @return  object
     */
    public function where($key, $value = NULL, $escape = TRUE)
    {
        return $this->_where($key, $value, 'AND ', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * 查询条件
     *
     * @param   mixed
     * @param   mixed
     * @return  object
     */
    public function or_where($key, $value = NULL, $escape = TRUE)
    {
        return $this->_where($key, $value, 'OR ', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * Where
     *
     * Called by where() or or_where()
     *
     * @param   mixed
     * @param   mixed
     * @param   string
     * @return  object
     */
    protected function _where($key, $value = NULL, $type = 'AND ', $escape = NULL)
    {
        //557 增加一个清除的方法防止干扰
        $this->ar_where = array();

        if ( ! is_array($key))
        {
            $key = array($key => $value);
        }

        // If the escape value was not set will will base it on the global setting
        if ( ! is_bool($escape))
        {
            $escape = $this->_protect_identifiers;
        }

        foreach ($key as $k => $v)
        {
            $prefix = count($this->ar_where) == 0 ? '' : $type;

            if (is_null($v) && ! $this->_has_operator($k))
            {
                // value appears not to have been set, assign the test to IS NULL
                $k .= ' IS NULL';
            }

            if ( ! is_null($v))
            {
                if ($escape === TRUE)
                {
                    $k = $this->_protect_identifiers($k, FALSE, $escape);
                    /*557  Mark:添加判断是否为数字，以防止未加引号的字符串进入SQL引起BUG By: WangWeiqiang <weiqiang6@staff.sina.com.cn> At:2016-04-14 12:16:30 */
                    if (is_numeric($v)) {
                        $v = ' '.$this->escape($v);
                    }elseif (substr($k, -3) == ' in') {
                        $v = ' ('.$this->escape($v).')';
                    } else {
                        $v = ' "'.$this->escape($v).'" ';
                    }

                }

                if ( ! $this->_has_operator($k))
                {
                    $k .= ' = ';
                }
            }
            else
            {
                $k = $this->_protect_identifiers($k, FALSE, $escape);
            }

            $this->ar_where[] = $prefix.$k.$v;

        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Where_in
     *
     * Generates a WHERE field IN ('item', 'item') SQL query joined with
     * AND if appropriate
     *
     * @param   string  The field to search
     * @param   array   The values searched on
     * @return  object
     */
    public function where_in($key = NULL, $values = NULL)
    {
        return $this->_where_in($key, $values);
    }

    // --------------------------------------------------------------------

    /**
     * Where_in_or
     *
     * Generates a WHERE field IN ('item', 'item') SQL query joined with
     * OR if appropriate
     *
     * @param   string  The field to search
     * @param   array   The values searched on
     * @return  object
     */
    public function or_where_in($key = NULL, $values = NULL)
    {
        return $this->_where_in($key, $values, FALSE, 'OR ');
    }

    // --------------------------------------------------------------------

    /**
     * Where_not_in
     *
     * Generates a WHERE field NOT IN ('item', 'item') SQL query joined
     * with AND if appropriate
     *
     * @param   string  The field to search
     * @param   array   The values searched on
     * @return  object
     */
    public function where_not_in($key = NULL, $values = NULL)
    {
        return $this->_where_in($key, $values, TRUE);
    }

    // --------------------------------------------------------------------

    /**
     * Where_not_in_or
     *
     * Generates a WHERE field NOT IN ('item', 'item') SQL query joined
     * with OR if appropriate
     *
     * @param   string  The field to search
     * @param   array   The values searched on
     * @return  object
     */
    public function or_where_not_in($key = NULL, $values = NULL)
    {
        return $this->_where_in($key, $values, TRUE, 'OR ');
    }

    // --------------------------------------------------------------------

    /**
     * Where_in
     *
     * Called by where_in, where_in_or, where_not_in, where_not_in_or
     *
     * @param   string  The field to search
     * @param   array   The values searched on
     * @param   boolean If the statement would be IN or NOT IN
     * @param   string
     * @return  object
     */
    protected function _where_in($key = NULL, $values = NULL, $not = FALSE, $type = 'AND ')
    {
        if ($key === NULL OR $values === NULL)
        {
            return;
        }

        if ( ! is_array($values))
        {
            $values = array($values);
        }

        $not = ($not) ? ' NOT' : '';

        foreach ($values as $value)
        {
            $this->ar_wherein[] = $this->escape($value);
        }

        $prefix = (count($this->ar_where) == 0) ? '' : $type;

        $where_in = $prefix . $this->_protect_identifiers($key) . $not . " IN (" . implode(", ", $this->ar_wherein) . ") ";

        $this->ar_where[] = $where_in;

        // reset the array for multiple calls
        $this->ar_wherein = array();
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Like
     *
     * Generates a %LIKE% portion of the query. Separates
     * multiple calls with AND
     *
     * @param   mixed
     * @param   mixed
     * @return  object
     */
    public function like($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'AND ', $side);
    }

    // --------------------------------------------------------------------

    /**
     * Not Like
     *
     * Generates a NOT LIKE portion of the query. Separates
     * multiple calls with AND
     *
     * @param   mixed
     * @param   mixed
     * @return  object
     */
    public function not_like($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'AND ', $side, 'NOT');
    }

    // --------------------------------------------------------------------

    /**
     * OR Like
     *
     * Generates a %LIKE% portion of the query. Separates
     * multiple calls with OR
     *
     * @param   mixed
     * @param   mixed
     * @return  object
     */
    public function or_like($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'OR ', $side);
    }

    // --------------------------------------------------------------------

    /**
     * OR Not Like
     *
     * Generates a NOT LIKE portion of the query. Separates
     * multiple calls with OR
     *
     * @param   mixed
     * @param   mixed
     * @return  object
     */
    public function or_not_like($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'OR ', $side, 'NOT');
    }

    // --------------------------------------------------------------------

    /**
     * Like
     *
     * Called by like() or orlike()
     *
     * @param   mixed
     * @param   mixed
     * @param   string
     * @return  object
     */
    protected function _like($field, $match = '', $type = 'AND ', $side = 'both', $not = '')
    {
        if ( ! is_array($field))
        {
            $field = array($field => $match);
        }

        foreach ($field as $k => $v)
        {
            $k = $this->_protect_identifiers($k);

            $prefix = (count($this->ar_like) == 0) ? '' : $type;

            $v = $this->escape_like_str($v);

            if ($side == 'none')
            {
                $like_statement = $prefix." $k $not LIKE '{$v}'";
            }
            elseif ($side == 'before')
            {
                $like_statement = $prefix." $k $not LIKE '%{$v}'";
            }
            elseif ($side == 'after')
            {
                $like_statement = $prefix." $k $not LIKE '{$v}%'";
            }
            else
            {
                $like_statement = $prefix." $k $not LIKE '%{$v}%'";
            }

            // some platforms require an escape sequence definition for LIKE wildcards
            if ($this->_like_escape_str != '')
            {
                $like_statement = $like_statement.sprintf($this->_like_escape_str, $this->_like_escape_chr);
            }

            $this->ar_like[] = $like_statement;
        }
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * GROUP BY
     *
     * @param   string
     * @return  object
     */
    public function group_by($by)
    {
        if (is_string($by))
        {
            $by = explode(',', $by);
        }

        foreach ($by as $val)
        {
            $val = trim($val);

            if ($val != '')
            {
                $this->ar_groupby[] = $this->_protect_identifiers($val);
            }
        }
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Sets the HAVING value
     *
     * Separates multiple calls with AND
     *
     * @param   string
     * @param   string
     * @return  object
     */
    public function having($key, $value = '', $escape = TRUE)
    {
        return $this->_having($key, $value, 'AND ', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * Sets the OR HAVING value
     *
     * Separates multiple calls with OR
     *
     * @param   string
     * @param   string
     * @return  object
     */
    public function or_having($key, $value = '', $escape = TRUE)
    {
        return $this->_having($key, $value, 'OR ', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * Sets the HAVING values
     *
     * Called by having() or or_having()
     *
     * @param   string
     * @param   string
     * @return  object
     */
    protected function _having($key, $value = '', $type = 'AND ', $escape = TRUE)
    {
        if ( ! is_array($key))
        {
            $key = array($key => $value);
        }

        foreach ($key as $k => $v)
        {
            $prefix = (count($this->ar_having) == 0) ? '' : $type;

            if ($escape === TRUE)
            {
                $k = $this->_protect_identifiers($k);
            }

            if ( ! $this->_has_operator($k))
            {
                $k .= ' = ';
            }

            if ($v != '')
            {
                $v = ' '.$this->escape($v);
            }

            $this->ar_having[] = $prefix.$k.$v;
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Sets the ORDER BY value
     *
     * @param   string
     * @param   string  direction: asc or desc
     * @return  object
     */
    public function order_by($orderby, $direction = '')
    {
        if (strtolower($direction) == 'random')
        {
            $orderby = ''; // Random results want or don't need a field name
            $direction = $this->_random_keyword;
        }
        elseif (trim($direction) != '')
        {
            $direction = (in_array(strtoupper(trim($direction)), array('ASC', 'DESC'), TRUE)) ? ' '.$direction : ' ASC';
        }

        //如果排序中使用了if函数，则不再用逗号来处理 add by 2016-07-05
        if (strpos($orderby, ',') !== FALSE && strpos($orderby,'if')===false)
        {
            $temp = array();
            foreach (explode(',', $orderby) as $part)
            {
                $part = trim($part);
                if ( ! in_array($part, $this->ar_aliased_tables))
                {
                    $part = $this->_protect_identifiers(trim($part));
                }

                $temp[] = $part;
            }

            $orderby = implode(', ', $temp);
        }
        else if ($direction != $this->_random_keyword)
        {
            $orderby = $this->_protect_identifiers($orderby);
        }

        $orderby_statement = $orderby.$direction;

        $this->ar_orderby[] = $orderby_statement;

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Sets the LIMIT value
     *
     * @param   integer the limit value
     * @param   integer the offset value
     * @return  object
     */
    public function limit($value, $offset = '')
    {
        $this->ar_limit = (int) $value;

        if ($offset != '')
        {
            $this->ar_offset = (int) $offset;
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Sets the OFFSET value
     *
     * @param   integer the offset value
     * @return  object
     */
    public function offset($offset)
    {
        $this->ar_offset = $offset;
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * The "set" function.  Allows key/value pairs to be set for inserting or updating
     *
     * @param   mixed
     * @param   string
     * @param   boolean
     * @return  object
     */
    public function set($key, $value = '', $escape = TRUE)
    {
        $key = $this->_object_to_array($key);

        if ( ! is_array($key))
        {
            $key = array($key => $value);
        }

        foreach ($key as $k => $v)
        {
            if ($escape === FALSE)
            {
                $this->ar_set[$this->_protect_identifiers($k)] = $v;
            }
            else
            {
                $this->ar_set[$this->_protect_identifiers($k, FALSE, TRUE)] = $this->escape($v);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Get
     *
     * Compiles the select statement based on the other functions called
     * and runs the query
     * @param   string  the limit clause
     * @param   string  the offset clause
     * @return  object
     */
    public function findAll($limit = null, $offset = null)
    {
        if ( ! is_null($limit))
        {
            $this->limit($limit, $offset);
        }

        $sql = $this->_compile_select();
        // echo $sql . '<br>';
        $result = Comm_Db::getDbRead($this->dbConfig)->getAll($sql);
        $this->_reset_select();

        return $result;
    }
/**
     * Get
     * 从主库查数据
     * Compiles the select statement based on the other functions called
     * and runs the query
     * @param   string  the limit clause
     * @param   string  the offset clause
     * @return  object
     */
    public function findAllMain($limit = null, $offset = null)
    {
        if ( ! is_null($limit))
        {
            $this->limit($limit, $offset);
        }

        $sql = $this->_compile_select();
        // echo $sql . '<br>';
        $result = Comm_Db::getDbWrite($this->dbConfig)->getAll($sql);
        $this->_reset_select();

        return $result;
    }
    /**
     * Get
     *
     * Compiles the select statement based on the other functions called
     * and runs the query
     * @return  object
     */
    public function findOne()
    {
        $result = $this->findAll(1);
        return empty($result) ? array() : current($result);
    }

    /**
     * 直接执行SQL语句
     * @param $sql
     */
    public function findAllBySql($sql) {
        return Comm_Db::getDbRead($this->dbConfig)->getAll($sql);
    }

    /**
     * "Count All Results" query
     *
     * Generates a platform-specific query string that counts all records
     * returned by an Active Record query.
     *
     * @param   string
     * @return  string
     */
    public function count_all_results()
    {
        $sql = $this->_compile_select($this->_count_string . $this->_protect_identifiers('numrows'));
        $num = Comm_Db::getDbRead($this->dbConfig)->getOne($sql);
        $this->_reset_select();

        return (int) $num;
    }

    // --------------------------------------------------------------------

    // --------------------------------------------------------------------

    /**
     * Insert_Batch
     *
     * Compiles batch insert strings and runs the queries
     *
     * @param   string  the table to retrieve the results from
     * @param   array   an associative array of insert values
     * @return  object
     */
    public function insert_batch($set = NULL)
    {
        if ( ! is_null($set))
        {
            $this->set_insert_batch($set);
        }

        if (count($this->ar_set) == 0)
        {
            if ($this->db_debug)
            {
                //No valid data array.  Folds in cases where keys and values did not match up
                return $this->display_error('db_must_use_set');
            }
            return FALSE;
        }

        // Batch this baby
        for ($i = 0, $total = count($this->ar_set); $i < $total; $i = $i + 1000)
        {
            $sql = "INSERT INTO ".$this->_protect_identifiers($this->_tbl_name, TRUE, NULL, FALSE)." (".implode(', ', $this->ar_keys).") VALUES ".implode(', ', array_slice($this->ar_set, $i, 1000));

            //echo $sql;

            Comm_Db::getDbWrite($this->dbConfig)->query($sql);
        }

        $this->_reset_write();


        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * The "set_insert_batch" function.  Allows key/value pairs to be set for batch inserts
     *
     * @param   mixed
     * @param   string
     * @param   boolean
     * @return  object
     */
    public function set_insert_batch($key, $value = '', $escape = TRUE)
    {
        $key = $this->_object_to_array_batch($key);

        if ( ! is_array($key))
        {
            $key = array($key => $value);
        }

        $keys = array_keys(current($key));
        sort($keys);

        foreach ($key as $row)
        {
            if (count(array_diff($keys, array_keys($row))) > 0 OR count(array_diff(array_keys($row), $keys)) > 0)
            {
                // batch function above returns an error on an empty array
                $this->ar_set[] = array();
                return;
            }

            ksort($row); // puts $row in the same order as our keys

            if ($escape === FALSE)
            {
                $this->ar_set[] =  '('.implode(',', $row).')';
            }
            else
            {
                $clean = array();

                foreach ($row as $value)
                {
                    $clean[] = $this->escape($value);
                }
				$cleanSql = $comma = '';
				foreach ($clean as $k=>$v) {
					$cleanSql .= $comma."'".$v."'";
					$comma = ',';
				}
                $this->ar_set[] =  '('.$cleanSql.')';
            }
        }

        foreach ($keys as $k)
        {
            $this->ar_keys[] = $this->_protect_identifiers($k);
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Insert
     *
     * Compiles an insert string and runs the query
     *
     * @param   string  the table to insert data into
     * @param   array   an associative array of insert values
     * @return  object
     */
    function insert($set = NULL)
    {
        $insert_id = Comm_Db::getDbWrite($this->dbConfig)->save( $this->_tbl_name, $set );

        $this->_reset_write();

        return $insert_id;
    }

    // --------------------------------------------------------------------

    /**
     * Replace
     *
     * Compiles an replace into string and runs the query
     *
     * @param   string  the table to replace data into
     * @param   array   an associative array of insert values
     * @return  object
     */
    public function replace($set = NULL)
    {
        if ( ! is_null($set))
        {
            $this->set($set);
        }

        if (count($this->ar_set) == 0)
        {
            if ($this->db_debug)
            {
                return $this->display_error('db_must_use_set');
            }
            return FALSE;
        }

        $sql = "REPLACE INTO ".$this->_protect_identifiers($this->_tbl_name, TRUE, NULL, FALSE)." (".implode(', ', array_keys($this->ar_set)).") VALUES ('".implode("', '", array_values($this->ar_set))."')";
        $this->_reset_write();
        return Comm_Db::getDbWrite($this->dbConfig)->query($sql);
    }

    // --------------------------------------------------------------------

    /**
     * Update
     *
     * Compiles an update string and runs the query
     *
     * @param   string  the table to retrieve the results from
     * @param   array   an associative array of update values
     * @param   mixed   the where clause
     * @return  object
     */
    public function update($set = NULL, $where = NULL,$escape=TRUE)
    {
        if ( ! is_null($set))
        {
            $this->set($set,'',$escape);
        }

        if (count($this->ar_set) == 0)
        {
            if ($this->db_debug)
            {
                return $this->display_error('db_must_use_set');
            }
            return FALSE;
        }

        if ($where != NULL)
        {
            $this->where($where);
        }

        $result = Comm_Db::getDbWrite($this->dbConfig)->update($this->_protect_identifiers($this->_tbl_name, TRUE, NULL, FALSE), $this->ar_set, implode(" ", $this->ar_where));

         $this->_reset_write();

        return $result;
    }


    // --------------------------------------------------------------------

    /**
     * Delete
     *
     * Compiles a delete string and runs the query
     *
     * @param   mixed   the table(s) to delete from. String or array
     * @param   mixed   the where clause
     * @param   boolean
     * @return  object
     */
    public function delete($where = '',$limit = NULL)
    {
        if ($where != '')
        {
            $this->where($where);
        }

        if (count($this->ar_where) == 0 && count($this->ar_wherein) == 0 && count($this->ar_like) == 0)
        {
            if ($this->db_debug)
            {
                return $this->display_error('db_del_must_use_where');
            }

            return FALSE;
        }

        if (count($this->ar_where) > 0 OR count($this->ar_like) > 0)
        {
            $conditions = "\nWHERE ";
            $conditions .= implode("\n", $this->ar_where);

            if (count($this->ar_where) > 0 && count($this->ar_like) > 0)
            {
                $conditions .= " AND ";
            }
            $conditions .= implode("\n", $this->ar_like);
        }

        $limit = ( ! $limit) ? '' : ' LIMIT '.$limit;

        $sql = "DELETE FROM ".$this->_protect_identifiers($this->_tbl_name, TRUE, NULL, FALSE).$conditions.$limit;

        $this->_reset_write();

        return Comm_Db::getDbWrite($this->dbConfig)->query($sql);
    }

    // --------------------------------------------------------------------

    /**
     * Track Aliases
     *
     * Used to track SQL statements written with aliased tables.
     *
     * @param   string  The table to inspect
     * @return  string
     */
    protected function _track_aliases($table)
    {
        if (is_array($table))
        {
            foreach ($table as $t)
            {
                $this->_track_aliases($t);
            }
            return;
        }

        // Does the string contain a comma?  If so, we need to separate
        // the string into discreet statements
        if (strpos($table, ',') !== FALSE)
        {
            return $this->_track_aliases(explode(',', $table));
        }

        // if a table alias is used we can recognize it by a space
        if (strpos($table, " ") !== FALSE)
        {
            // if the alias is written with the AS keyword, remove it
            $table = preg_replace('/\s+AS\s+/i', ' ', $table);

            // Grab the alias
            $table = trim(strrchr($table, " "));

            // Store the alias, if it doesn't already exist
            if ( ! in_array($table, $this->ar_aliased_tables))
            {
                $this->ar_aliased_tables[] = $table;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * 编译sql查询语句
     * get()方法调用它
     *
     * @return  string
     */
    protected function _compile_select($select_override = FALSE)
    {
        if ($select_override !== FALSE)
        {
            $sql = $select_override;
        }
        else
        {
            $sql = ( ! $this->ar_distinct) ? 'SELECT ' : 'SELECT DISTINCT ';

            if (count($this->ar_select) == 0)
            {
                $sql .= '*';
            }
            else
            {
                // Cycle through the "select" portion of the query and prep each column name.
                // The reason we protect identifiers here rather then in the select() function
                // is because until the user calls the from() function we don't know if there are aliases
                foreach ($this->ar_select as $key => $val)
                {
                    $no_escape = isset($this->ar_no_escape[$key]) ? $this->ar_no_escape[$key] : NULL;
                    $this->ar_select[$key] = $this->_protect_identifiers($val, FALSE, $no_escape);
                }

                $sql .= implode(', ', $this->ar_select);
            }
        }

        // ----------------------------------------------------------------

        // Write the "FROM" portion of the query

        $sql .= "\nFROM ";
        if (count($this->ar_join) > 0)
        {
            $alis_name = is_null($this->_tbl_alis_name) ? $this->_tbl_name : $this->_tbl_alis_name;
            $sql .= $this->_tbl_name.' '.$alis_name;
        } else {
            $sql .= $this->_tbl_name;
        }


        // ----------------------------------------------------------------

        // Write the "JOIN" portion of the query

        if (count($this->ar_join) > 0)
        {
            $sql .= "\n";

            $sql .= implode("\n", $this->ar_join);
        }

        // ----------------------------------------------------------------

        // Write the "WHERE" portion of the query

        if (count($this->ar_where) > 0 OR count($this->ar_like) > 0)
        {
            $sql .= "\nWHERE ";
        }

        $sql .= implode("\n", $this->ar_where);

        // ----------------------------------------------------------------

        // Write the "LIKE" portion of the query

        if (count($this->ar_like) > 0)
        {
            if (count($this->ar_where) > 0)
            {
                $sql .= "\nAND ";
            }

            $sql .= implode("\n", $this->ar_like);
        }

        // ----------------------------------------------------------------

        // Write the "GROUP BY" portion of the query

        if (count($this->ar_groupby) > 0)
        {
            $sql .= "\nGROUP BY ";

            $sql .= implode(', ', $this->ar_groupby);
        }

        // ----------------------------------------------------------------

        // Write the "HAVING" portion of the query

        if (count($this->ar_having) > 0)
        {
            $sql .= "\nHAVING ";
            $sql .= implode("\n", $this->ar_having);
        }

        // ----------------------------------------------------------------

        // Write the "ORDER BY" portion of the query

        if (count($this->ar_orderby) > 0)
        {
            $sql .= "\nORDER BY ";
            $sql .= implode(', ', $this->ar_orderby);

            if ($this->ar_order !== FALSE)
            {
                $sql .= ($this->ar_order == 'desc') ? ' DESC' : ' ASC';
            }
        }

        // ----------------------------------------------------------------

        // Write the "LIMIT" portion of the query

        if (is_numeric($this->ar_limit))
        {
            $sql .= "\n";
            $sql = $this->_limit($sql, $this->ar_limit, $this->ar_offset);
        }
        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * 对象转数组
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @param   object
     * @return  array
     */
    public function _object_to_array($object)
    {
        if ( ! is_object($object))
        {
            return $object;
        }

        $array = array();
        foreach (get_object_vars($object) as $key => $val)
        {
            // There are some built in keys we need to ignore for this conversion
            if ( ! is_object($val) && ! is_array($val) && $key != '_parent_name')
            {
                $array[$key] = $val;
            }
        }

        return $array;
    }

    // --------------------------------------------------------------------

    /**
     * 对象批量转数组
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @param   object
     * @return  array
     */
    public function _object_to_array_batch($object)
    {
        if ( ! is_object($object))
        {
            return $object;
        }

        $array = array();
        $out = get_object_vars($object);
        $fields = array_keys($out);

        foreach ($fields as $val)
        {
            // There are some built in keys we need to ignore for this conversion
            if ($val != '_parent_name')
            {

                $i = 0;
                foreach ($out[$val] as $data)
                {
                    $array[$i][$val] = $data;
                    $i++;
                }
            }
        }

        return $array;
    }

    // --------------------------------------------------------------------

    /**
     * 重置查询的记录，get()方法调用
     *
     * @param   array   An array of fields to reset
     * @return  void
     */
    protected function _reset_run($ar_reset_items)
    {
        foreach ($ar_reset_items as $item => $default_value)
        {
            if ( ! in_array($item, $this->ar_store_array))
            {
                $this->$item = $default_value;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * 重置查询的记录，get()方法调用
     *
     * @return  void
     */
    protected function _reset_select()
    {
        $ar_reset_items = array(
            'ar_select'         => array(),
            'ar_from'           => array(),
            'ar_join'           => array(),
            'ar_where'          => array(),
            'ar_like'           => array(),
            'ar_groupby'        => array(),
            'ar_having'         => array(),
            'ar_orderby'        => array(),
            'ar_wherein'        => array(),
            'ar_aliased_tables' => array(),
            'ar_no_escape'      => array(),
            'ar_distinct'       => FALSE,
            'ar_limit'          => FALSE,
            'ar_offset'         => FALSE,
            'ar_order'          => FALSE,
        );

        $this->_reset_run($ar_reset_items);
    }

    // --------------------------------------------------------------------

    /**
     * 重置更新操作后属性数组存放的值，insert() update() insert_batch() update_batch() and delete()方法调用
     *
     * Called by the insert() update() insert_batch() update_batch() and delete() functions
     *
     * @return  void
     */
    protected function _reset_write()
    {
        $ar_reset_items = array(
            'ar_set'        => array(),
            'ar_from'       => array(),
            'ar_where'      => array(),
            'ar_like'       => array(),
            'ar_orderby'    => array(),
            'ar_keys'       => array(),
            'ar_limit'      => FALSE,
            'ar_order'      => FALSE
        );

        $this->_reset_run($ar_reset_items);
    }


    /**
     *
     * SELECT * FROM hostname.database.table.column AS c FROM hostname.database.table
     *
     * Or a query with aliasing:
     *
     * SELECT m.member_id, m.member_name FROM members AS m
     *
     * Since the column name can include up to four segments (host, DB, table, column)
     * or also have an alias prefix, we need to do a bit of work to figure this out and
     * insert the table prefix (if it exists) in the proper position, and escape only
     * the correct identifiers.
     *
     * @access  private
     * @param   string
     * @param   bool
     * @param   mixed
     * @param   bool
     * @return  string
     */
    function _protect_identifiers($item, $prefix_single = FALSE, $protect_identifiers = NULL, $field_exists = TRUE)
    {
        if ( ! is_bool($protect_identifiers))
        {
            $protect_identifiers = $this->_protect_identifiers;
        }

        if (is_array($item))
        {
            $escaped_array = array();

            foreach ($item as $k => $v)
            {
                $escaped_array[$this->_protect_identifiers($k)] = $this->_protect_identifiers($v);
            }

            return $escaped_array;
        }

        // Convert tabs or multiple spaces into single spaces
        $item = preg_replace('/[\t ]+/', ' ', $item);

        // If the item has an alias declaration we remove it and set it aside.
        // Basically we remove everything to the right of the first space
        if (strpos($item, ' ') !== FALSE)
        {
            $alias = strstr($item, ' ');
            $item = substr($item, 0, - strlen($alias));
        }
        else
        {
            $alias = '';
        }

        // This is basically a bug fix for queries that use MAX, MIN, etc.
        // If a parenthesis is found we know that we do not need to
        // escape the data or add a prefix.  There's probably a more graceful
        // way to deal with this, but I'm not thinking of it -- Rick
        if (strpos($item, '(') !== FALSE)
        {
            return $item.$alias;
        }

        // Break the string apart if it contains periods, then insert the table prefix
        // in the correct location, assuming the period doesn't indicate that we're dealing
        // with an alias. While we're at it, we will escape the components
        if (strpos($item, '.') !== FALSE)
        {
            $parts  = explode('.', $item);

            // Does the first segment of the exploded item match
            // one of the aliases previously identified?  If so,
            // we have nothing more to do other than escape the item
            if (in_array($parts[0], $this->ar_aliased_tables))
            {
                if ($protect_identifiers === TRUE)
                {
                    foreach ($parts as $key => $val)
                    {
                        if ( ! in_array($val, $this->_reserved_identifiers))
                        {
                            $parts[$key] = $this->_escape_identifiers($val);
                        }
                    }

                    $item = implode('.', $parts);
                }
                return $item.$alias;
            }

            // Is there a table prefix defined in the config file?  If not, no need to do anything
            if ($this->dbprefix != '')
            {
                // We now add the table prefix based on some logic.
                // Do we have 4 segments (hostname.database.table.column)?
                // If so, we add the table prefix to the column name in the 3rd segment.
                if (isset($parts[3]))
                {
                    $i = 2;
                }
                // Do we have 3 segments (database.table.column)?
                // If so, we add the table prefix to the column name in 2nd position
                elseif (isset($parts[2]))
                {
                    $i = 1;
                }
                // Do we have 2 segments (table.column)?
                // If so, we add the table prefix to the column name in 1st segment
                else
                {
                    $i = 0;
                }

                // This flag is set when the supplied $item does not contain a field name.
                // This can happen when this function is being called from a JOIN.
                if ($field_exists == FALSE)
                {
                    $i++;
                }

                // Verify table prefix and replace if necessary
                if ($this->swap_pre != '' && strncmp($parts[$i], $this->swap_pre, strlen($this->swap_pre)) === 0)
                {
                    $parts[$i] = preg_replace("/^".$this->swap_pre."(\S+?)/", $this->dbprefix."\\1", $parts[$i]);
                }

                // We only add the table prefix if it does not already exist
                if (substr($parts[$i], 0, strlen($this->dbprefix)) != $this->dbprefix)
                {
                    $parts[$i] = $this->dbprefix.$parts[$i];
                }

                // Put the parts back together
                $item = implode('.', $parts);
            }

            if ($protect_identifiers === TRUE)
            {
                $item = $this->_escape_identifiers($item);
            }

            return $item.$alias;
        }

        // Is there a table prefix?  If not, no need to insert it
        if ($this->dbprefix != '')
        {
            // Verify table prefix and replace if necessary
            if ($this->swap_pre != '' && strncmp($item, $this->swap_pre, strlen($this->swap_pre)) === 0)
            {
                $item = preg_replace("/^".$this->swap_pre."(\S+?)/", $this->dbprefix."\\1", $item);
            }

            // Do we prefix an item with no segments?
            if ($prefix_single == TRUE AND substr($item, 0, strlen($this->dbprefix)) != $this->dbprefix)
            {
                $item = $this->dbprefix.$item;
            }
        }

        if ($protect_identifiers === TRUE AND ! in_array($item, $this->_reserved_identifiers))
        {
            $item = $this->_escape_identifiers($item);
        }

        return $item.$alias;
    }

    /**
     * Tests whether the string has an SQL operator
     *
     * @access  private
     * @param   string
     * @return  bool
     */
    function _has_operator($str)
    {
        $str = trim($str);
        if ( ! preg_match("/(\s|<|>|!|=|is null|is not null)/i", $str))
        {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Limit string
     *
     * Generates a platform-specific LIMIT clause
     *
     * @access  public
     * @param   string  the sql query string
     * @param   integer the number of rows to limit the query to
     * @param   integer the offset value
     * @return  string
     */
    function _limit($sql, $limit, $offset)
    {
        if ($offset == 0)
        {
            $offset = '';
        }
        else
        {
            $offset .= ", ";
        }

        return $sql."LIMIT ".$offset.$limit;
    }

    /**
     * Escape the SQL Identifiers
     *
     * This function escapes column and table names
     *
     * @access  private
     * @param   string
     * @return  string
     */
    function _escape_identifiers($item)
    {
        if ($this->_escape_char == '')
        {
            return $item;
        }

        foreach ($this->_reserved_identifiers as $id)
        {
            if (strpos($item, '.'.$id) !== FALSE)
            {
                $str = $this->_escape_char. str_replace('.', $this->_escape_char.'.', $item);

                // remove duplicates if the user already included the escape
                return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $str);
            }
        }

        if (strpos($item, '.') !== FALSE)
        {
            $str = $this->_escape_char.str_replace('.', $this->_escape_char.'.'.$this->_escape_char, $item).$this->_escape_char;
        }
        else
        {
            $str = $this->_escape_char.$item.$this->_escape_char;
        }

        // remove duplicates if the user already included the escape
        return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $str);
    }


    function escape_like_str($str)
    {
        return Comm_Db::getDbRead($this->dbConfig)->escape_str($str, TRUE);
    }

    /**
     * 转义
     * @param   string $str 需要转义的字符串
     * @access  public
     * @return  string
     */
    function escape($str)
    {
        return Comm_Db::getDbWrite($this->dbConfig)->escape_str($str);
    }

    /**
     * 使用正则验证数据
     * @access public
     * @param string $value  要验证的数据
     * @param string $rule 验证规则
     * @return boolean
     */
    public function regex($value,$rule) {
        $validate = array(
            'email'     =>  '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'url'       =>  '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
            'currency'  =>  '/^\d+(\.\d+)?$/',
            'number'    =>  '/^\d+$/',
            'zip'       =>  '/^\d{6}$/',
            'integer'   =>  '/^[-\+]?\d+$/',
            'double'    =>  '/^[-\+]?\d+(\.\d+)?$/',
            'english'   =>  '/^[A-Za-z]+$/',
            'mobile'    =>  '/^1[3578][0-9]{9}$/',
            'password'  =>  '/^[.A-Za-z_0-9-!@#$%\^&*()]{6,20}$/ui'
        );
        // 检查是否有内置的正则表达式
        if(isset($validate[strtolower($rule)]))
            $rule = $validate[strtolower($rule)];
        return preg_match($rule,$value)===1;
    }

    /**
     * 提交数据是否通过验证
     * @access protected
     * @param array $data 创建数据
     * @return boolean
     */
    public function valid($data) {
        $_validate   =   $this->_validate;
        // 属性验证
        if(!empty($_validate)) {
            foreach($_validate as $key=>$val) {
                // 验证因子定义格式 array(field, rule，tip, type) type为1必须验证  2表示不为空要满足条件
                $type = $val[3];
                if ($type==1 && !$data[$val[0]]) {
                    $this->_err = $val[2];
                    return false;
                } elseif($type==2) {
                    if (isset($data[$val[0]]) && $data[$val[0]] && false===$this->regex($data[$val[0]],$val[1])) {
                        $this->_err = $val[2];
                        return false;
                    }
                }
            }
        }
        return false;
    }

    public function query($sql){
        return Comm_Db::getDbRead($this->dbConfig)->getAll($sql);
    }
    public function truncate($table){
        $sql = $this->_truncate_strig . $table;
        return Comm_Db::getDbWrite($this->dbConfig)->query($sql);
    }


    function display_error($error = '', $swap = '', $native = FALSE)
    {
        echo '<h2>'.$error.'<h2>';
        die;
    }
}
