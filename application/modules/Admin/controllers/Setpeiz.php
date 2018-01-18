<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/28/028
 * Time: 11:53
 */
class SetpeizController extends Abstract_C{
    const PAGESIZE = 10;
    public function init(){
//        Business_Setpeiz_setpeizModel
        $this->mod = new Business_Setpeiz_setpeizModel();
    }
    public function object2array($object) {
        if (is_object($object)) {
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
        }
        else {
            $array = $object;
        }
        return $array;
    }
//    Advert 添加操作
    public function addAction(){
        if($this->post('dosubmit')){
            $time = date("Y-m-d H:i:s", time());//商品添加时间
            $param = array(
                '`key`' => $this->post('key'),
                '`value`' => $this->post('val'),
                'create_time' => $time
            );
                $query = $this->mod->add($param);
                if ($query) {
                    echo "<script>alert('ok');</script>";
                } else {
                    echo "<script>alert('error');</script>";
                }
            }
        $this->layout('setpeiz/add.html');
    }
//    Advert 删除操作
    public function delAction(){
        if(!$this->get('id')){
            echo "<script>alert('参数不正确')</script>";
            exit;
        }

        $gid = $this->get('id');
        $param = array('id' => $gid);
        $query = $this->mod->del($param);
        if ($query) {
            echo '<script language="javascript">alert("删除成功");window.history.back(-1);</script>';
        } else {
            echo '<script language="javascript">alert("删除失败");window.history.back(-1);</script>';
        }
    }
//   列表操作
    public function selectAction(){
        $page = $this->get('page');
        empty($page) ? $page = '1' : $page;

        $result = $this->mod->select($page);
        // $pageProviderUrl  正常页码的链接
        // $amount 页码总数
        // $currentIndex  当前页码
        // $sectionId  刷新区域
        // $pageSizeShow  显示出来的页码数
        if($result){
            //URL参数模式使用  如：http://dealer.sina.maiche.com.cn/index.php?a=page&page=4
            $pageProviderUrl = Comm_Page::getQueryUrl('/admin/advert/select/',$this->_get);
            //URL路径模式使用 如：http://dealer.sina.maiche.com.cn/index.php/index/page
            // $pageProviderUrl = '';
            $data['amount'] = ceil($result['total']/self::PAGESIZE);
            $data['currentIndex']=$page;
            $data['pageProviderUrl'] = $pageProviderUrl;
            $data['pageSizeShow'] = 5;
            $data['conment'] = $result['data'];
        }else{
            echo "<script>alert('当前列表为空')</script>";
        }
        $this->layout('setpeiz/list.html',$data);
    }
//    修改操作
    public function updateAction(){
        if($this->post('dosubmit')) {

            $gid = $this->post('id');
            $where = array('id' => $gid);
            $data['key'] = $this->post('key');
            $data['value'] = $this->post('value');
            $query = $this->mod->update($data,$where);
            if ($query) {
                echo "<script>alert('ok');self.location='select';</script>";
                exit;
            } else {
                echo "<script>alert('error');</script>";
                exit;
            }
        }
        if(!$this->get('id')){
            echo "<script>alert('参数不正确')</script>";
            exit;
        }
        $gid = $this->get('id');
        $param = array('id' => $gid);
        $data = $this->mod->select_one($param);
//        echo "<pre>";
//        var_dump($data);
        if (!$data) {
            echo "<script>alert('不存在');</script>";
        }
        $this->layout('setpeiz/update.html',$data);
    }
    public function listorderAction()
    {
        if ($this->post('id')) {

            $data['order'] = $_POST['rid'];

            $where = array('id' => $_POST['id']);

            $query = $this->mod->update($data,$where);

            if($query){
                echo 1;
            } else {
                echo 0;
            }

        }
    }
}
