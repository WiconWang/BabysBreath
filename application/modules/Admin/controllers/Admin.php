<?php
/**
 * 557
 * MARK
 * @author WiconWang@gmail.com
 * @copyright 2018/1/18 上午11:55
 * @file df1.php
 */
class AdminController extends Abstract_C {
    //admin
    public function adminAction(){
        $data = array();
        $this->layout('admin/index.html',$data);
    }

}
