<?php
/**
 * 557
 * MARK
 * @author WiconWang@gmail.com
 * @copyright 2018/1/18 上午11:55
 * @file df1.php
 */
class LogsController extends Abstract_C {
    protected $id = 0;

    public function init()
    {
        $this->LogsModel =  new Bussiness_Admin_Operation_LogModel();
        // 初始化此用户的ID
        $this->id = Yaf_Session::getInstance()->__get( "MANAGE_ID");
        // if (!$this->uid) {die('已开启登录验证，请先登录');}
        $this->LayoutData['crumb'] = array(
            'home' => array('text' => '首页','url' => '/admin', ),
            'category' => array('text' => '日志','url' => '', ),
            'page' => array('text' => '','url' => '', ),
            );
    }

    /**
     * 用户默认页面
     * @return layout
     */
    public function indexAction() {
        $where = array();
        $page =intval($this->get('page'));
        $pagesize = $this->get('pagesize')?intval($this->get('pagesize')):20;
        $where['log_type'] = $this->get('type')?intval($this->get('type')):1;
        $this->LayoutData['list'] = $this->LogsModel->Info($where,$page,$pagesize);
        switch ($where['log_type']) {
            case '1':
            $this->LayoutData['crumb']['category']['url'] = '/admin/logs/index?type=1';
                $this->LayoutData['crumb']['page']['text'] = '登录日志';
                break;
            case '2':
            $this->LayoutData['crumb']['category']['url'] = '/admin/logs/index?type=2';
                $this->LayoutData['crumb']['page']['text'] = '后台操作日志';
                break;

            default:
                $this->LayoutData['crumb']['category']['url'] = '/admin/logs/index?type=1';
                $this->LayoutData['crumb']['page']['text'] = '后台登录日志';
                break;
        }
        $gparam = $this->_getParams();
        unset($gparam['admin/logs/index']);
        $pageProviderUrl = Comm_Page::getQueryUrl('/admin/logs/index',$this->_getParams());
        $this->LayoutData['amount'] = ceil($this->LayoutData['list']['total']/$pagesize);
        $this->LayoutData['currentIndex']=$page;
        $this->LayoutData['pageProviderUrl'] = $pageProviderUrl;
        $this->LayoutData['pageSizeShow'] = 5;

        $this->layout('logs/index.html',$this->LayoutData);
    }
}
