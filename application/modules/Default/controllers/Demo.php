<?php

/**
 * 首页TEST
 */
class DemoController extends Abstract_C
{

    const PAGESIZE = 2;                        //每页显示数


    /**
     * 这里是一个向视图渲染内容的例子
     *     */
    public function mvcAction()
    {
        // 要显示的数组
        $datalist = array(
            0 => array(
                'id' => 1,
                'nickname' => '张三',
            ),
            1 => array(
                'id' => 2,
                'nickname' => '李四',
            ),
            2 => array(
                'id' => 3,
                'nickname' => '王五',
            ),
        );

        // 向视图传输的最终数据
        $data = array(
            'title' => '这里是文件标题',
            'content' => '文件内容',
            'table_data' => $datalist,
        );

        $this->layout('demo/content.html', $data);

    }

    /**
     * 这里是一个直接调用数据库层的例子。注意一般来说这些内容是要写在Model层的
     * 也就是说 C层分离业务， M层的bussiess实现业务，由M层的mod来对数据库操作 *
     * @return [type] [description]
     */
    public function sqlAction()
    {
        //http://dealer.sina.maiche.com.cn/?c=index&a=sql
        echo "SQL格式参考";
        echo "<hr><br>";
        /*
        1: 添加

        INSERT INTO dealer_biz_brand(biz_id,brand_id) VALUES(122,332)
        ——————————————————

        $param = array('biz_id'=>122,'brand_id'=>332);
        $db = BizSellModel::instance();
        $result = $db->insert($param);

        2: 更新

        UPDATE `dealer_biz_brand` SET `biz_id` = '100',`brand_id` = '100' WHERE `id` = 1
        ——————————————————

        $param = array('biz_id'=>100,'brand_id'=>100);
        $db = BizSellModel::instance();
        $result = $db->update($param,array('id'=>1));

        3: 删除

        DELETE FROM `dealer_biz_brand` WHERE `id` = 1 LIMIT 2
        ——————————————————

        $where = array('id'=>1);
        $limit =2;
        $db = BizSellModel::instance();
        $result = $db->delete($where,$limit);



        4:   and条件的检索
        SELECT `id`, `account`, `password` FROM dealer_biz_info WHERE `city_id` = 1 AND `province_id` = 1 GROUP BY `province_id` LIMIT 0,2
        ——————————————————

         $db = Mod_BizInfoModel::instance();
        $result = $db
            -> field('id,account,password')
            -> where(array('city_id' => 1, 'province_id' =>1 ))
            -> group_by('province_id')
            -> limit(2)
            -> offset(0)
            -> findAll();


        5:   or条件的检索
        SELECT `id`, `account`, `password`, `city_id` FROM dealer_biz_info WHERE `city_id` = 1 OR `province_id` = 2 LIMIT 2
        -------------

         $db = Mod_BizInfoModel::instance();
        $result = $db
            -> field('id,account,password,city_id')
            -> or_where(array('city_id' => 1, 'province_id' =>2 ))
            -> limit(2)
            -> offset(0)
            -> findAll();


        6：
        其它常用where筛选：
        where_in($key = NULL, $values = NULL)
        or_where_in($key = NULL, $values = NULL)
        where_not_in($key = NULL, $values = NULL)
        or_where_not_in($key = NULL, $values = NULL)
        like($field, $match = '', $side = 'both')

        其它结果选项
        findAll() 检索出所有记录组成二维数组
        findOne() 检索出一条记录组成一维数组



        7: 连表检索
        SELECT `article`.`id`, `article`.`title`, `extend`.`content` FROM dealer_article article LEFT JOIN `dealer_article_extend` extend ON `extend`.`aid` = `article`.`id` WHERE `audited` = 2 LIMIT 2
        -----------------

        方式一：直接书写 连表名和连表字段

        $db = ArticleModel::instance();
        $db->join('dealer_article_extend extend', 'extend.aid = article.id','left');
        $result = $db
            -> field('article.id, article.title, extend.content')
            -> where(array('audited' =>2))
            -> limit(2)
            -> offset(0)
            -> findAll();

        方式二：使用表对象

        $db = Mod_ArticleModel::instance();
        $db2 = Mod_ArticleExtendModel::instance();
        $field = array(
            $db->_tbl_alis_name  => 'id,title', //表一的检索字段
            $db2->_tbl_alis_name => 'aid,content' //表二的检索字段
            );
        $db->join($db2,array('id' => 'aid'),'left');  //把对象、主表关联字段和扩展表关联字段组成的数组、连接方式 传入
        $result = $db
            -> field($field)
            -> where(array('audited' =>2))
            -> limit(2)
            -> offset(0)
            -> findAll();

        ps:构建$field数组的时候 请把表对象的_tbl_alis_name做为key

        8: 统计

        SELECT COUNT(*) AS `numrows` FROM dealer_biz_info WHERE `city_id` = 1 AND `province_id` = 1
        ——————————————————

        $db = Mod_BizInfoModel::instance();
        $result = $db
            -> field('id,account,password')
            -> where(array('city_id' => 1, 'province_id' =>1 ))
            -> count_all_results();


        9：事务处理

        $param = array('biz_id'=>777,'brand_id'=>221);
        $db = Mod_BizBrandModel::instance();

        $db->startTrans();
            $result = $db->insert($param);
        $db->commit();

        // $db->rollback();


        Model层使用事务处理
            $UserFieldModel->getObj(Comm_Db::getDbNum($uid))->startTrans();
            $UserFieldModel->getObj(Comm_Db::getDbNum($uid))->commit();
            $UserFieldModel->getObj(Comm_Db::getDbNum($uid))->rollback();


*/
    }

    /**
     * 这里是一个列表的分页实现方案
     * 注意他也直接操作了db。和上方sql的例子一样，请灵活使用
     * @return [type] [description]
     */
    public function indexAction()
    {
        $result = array();
        //Pages
        $page = abs(intval($this->get('page')));
        $page = empty($page) ? 1 : $page;
        $offset = ($page - 1) * self::PAGESIZE;

        //db
        $db = Mod_BizInfoModel::instance();

        //where
        $db->where(array('city_id' => 1));

        //count
        $clone_db = clone($db);
        $result['total'] = $clone_db->count_all_results();

        //result
        $result['data'] = $db
            ->field('id,account,password,province_id,city_id')
            ->order_by('id desc,province_id asc')
            ->limit(self::PAGESIZE)
            ->offset($offset)
            ->findAll();


        // $pageProviderUrl  正常页码的链接
        // $amount 页码总数
        // $currentIndex  当前页码
        // $sectionId  刷新区域
        // $pageSizeShow  显示出来的页码数

        //URL参数模式使用  如：http://dealer.sina.maiche.com.cn/index.php?a=page&page=4
        $pageProviderUrl = Comm_Page::getQueryUrl('/', $this->_get);
        //URL路径模式使用 如：http://dealer.sina.maiche.com.cn/index.php/index/page
        // $pageProviderUrl = '';
        $data['amount'] = ceil($result['total'] / self::PAGESIZE);
        $data['currentIndex'] = $page;
        $data['pageProviderUrl'] = $pageProviderUrl;
        $data['pageSizeShow'] = 5;

        $this->layout('demo/pages.html', $data);
    }

    /**
     * 检测数据库配置，并对库表里的表生成对应的MOD
     */
    public function MakeModByTableAction()
    {
        $configDB = 'default';
        $tableList = Comm_Db::getDbWrite($configDB)->getAll("SHOW TABLES");
        foreach ($tableList as $k =>$v) {
            foreach ($v as $m=>$n) {
                Base_Funs::MakeMod($configDB,$n);
                Base_Funs::MakBusiness($configDB,$n);
            }
        }
        echo 'Success';
//        Base_Funs::MakeMod($this->get('db'),$this->get('table'));
    }

}
