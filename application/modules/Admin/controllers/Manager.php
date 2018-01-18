<?php
/**
 * 557
 * 后台用户和权限组相关
 * @author WiconWang@gmail.com
 * @copyright 2017-06-14 18:37:35
 */
class ManagerController extends Abstract_C {
    protected $id = 0;
    protected $UserModel = '';
    protected $GroupModel = '';
    protected $RoleModel = '';
    protected $LayoutData = array();

    public function init()
    {
        $this->UserModel =  new Business_AdminManage_UserModel();
        $this->GroupModel =  new Business_AdminManage_GroupModel();
        $this->RoleModel =  new Business_AdminManage_RoleModel();
        // 初始化此用户的ID
        $this->id = Yaf_Session::getInstance()->__get( "MANAGE_ID");
        // if (!$this->uid) {die('已开启登录验证，请先登录');}
        $this->LayoutData['crumb'] = array(
            'home' => array('text' => '首页','url' => '/admin', ),
            'category' => array('text' => '管理员和权限','url' => '', ),
            'page' => array('text' => '','url' => '', ),
            );
    }

    /**
     * 用户资料检索页面
     */
    public function UserListAction()
    {
        $where = array();
        if ($this->get('ugroup')){$where['ugroup'] = intval($this->get('ugroup'));}
        $this->LayoutData['list'] = $this->UserModel->Info($where);
        $this->LayoutData['crumb']['category']['url'] = '/admin/manager/userlist';
        $this->LayoutData['crumb']['page']['text'] = '管理用户配置';
        $this->LayoutData['group'] = $this->GroupModel->getNames();
        $this->layout('manager/user_list.html',$this->LayoutData);
    }

    /**
     * 用户资料修改页面
     */
    public function UserAction()
    {
        $id = $this->get('id')?intval($this->get('id')):0;

        // 数据的修改和保存
        if ($_POST) {

            $post = $this->_postParams();
            $savedata = $post;
            unset($savedata['id']);
            $savedata['update_time'] = date('Y-m-d H:i:s',time());
            $savedata['update_ip'] = $_SERVER["REMOTE_ADDR"];
            if (!empty($savedata['password'])) {
                if (empty($post['id'])) {
                    $savedata['salt'] = Comm_Tools::getRandomString(4);
                }else{
                    $info = $this->UserModel->InfoByID($post['id']);
                    $savedata['salt'] =$info['salt'];
                }
                $savedata['orgin_password'] = $savedata['password'];
                unset($savedata['password']);
            }

            $res = $this->UserModel->Save($savedata,$post['id']);

            if ($res['error'] == 1) {
                $this->jsonResponse(0,$res['msg']);
            }else{
                $this->jsonResponse(1,$res['msg']);
            }
            exit;
        }
        $this->LayoutData['info'] = $this->UserModel->InfoByID($id);
        if (empty($this->LayoutData['info'])) {$this->LayoutData['info'] = $this->UserModel->EmptyData();}
        $this->LayoutData['group'] = $this->GroupModel->getNames();
        $this->LayoutData['crumb']['category']['url'] = '/admin/manager/userlist';
        $this->LayoutData['crumb']['page']['text'] = '管理用户配置';
        $this->layout('manager/user_edit.html',$this->LayoutData);
    }


    /**
     * 用户组资料检索页面
     */
    public function GroupListAction()
    {
        $where = array();
        if ($this->get('group')){$where['group'] = intval($this->get('group'));}
        $list = $this->GroupModel->Info($where);
        $rolelist = $this->RoleModel->getNames();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['roles'] = array();
                $r = '';
                $r = explode(',', $v['roles']);
                foreach ($r as $m => $n) {
                    if (isset($rolelist[$n])) {
                        $list[$k]['roles'][$n] = $rolelist[$n]['name'];
                    }
                }
            }
        }
        // 如果需求导出json则
        if ($this->get('ajax')) {
            $this->response(count($list),$list,1 );
        }
        $this->LayoutData['list'] = $list;
        $this->LayoutData['crumb']['category']['url'] = '/admin/manager/grouplist';
        $this->LayoutData['crumb']['page']['text'] = '用户组配置';
        $this->layout('manager/group_list.html',$this->LayoutData);
    }

    /**
     * 用户组修改页面
     */
    public function GroupAction()
    {
        $id = $this->get('id')?intval($this->get('id')):0;

        // 数据的修改和保存
        if ($_POST) {

            $post = $this->_postParams();
            $savedata = $post;
            unset($savedata['id']);
            $savedata['update_time'] = date('Y-m-d H:i:s',time());
            $savedata['update_ip'] = $_SERVER["REMOTE_ADDR"];
            $res = $this->GroupModel->Save($savedata,$post['id']);
            if ($res['error'] == 1) {
                $this->jsonResponse(0,$res['msg']);
            }else{
                $this->jsonResponse(1,$res['msg']);
            }
            exit;
        }
        $this->LayoutData['info'] = $this->GroupModel->InfoByID($id);
        if (empty($this->LayoutData['info'])) {$this->LayoutData['info'] = $this->GroupModel->EmptyData();}
        $this->LayoutData['roles'] = $this->RoleModel->getNames();
        $this->LayoutData['crumb']['category']['url'] = '/admin/manager/grouplist';
        $this->LayoutData['crumb']['page']['text'] = '用户组配置';

        $this->layout('manager/group_edit.html',$this->LayoutData);
    }




    /**
     * 权限资料检索页面
     */
    public function RoleListAction()
    {

        $where = array();
        if ($this->get('group')){$where['group'] = intval($this->get('group'));}
        $list = $this->RoleModel->Info($where);
        // 如果需求导出json则
        if ($this->get('ajax')) {
            $this->response(count($list),$list,1 );
        }
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['urls'] = explode(',', $v['urls']);
            }
        }
        $this->LayoutData['crumb']['category']['url'] = '/admin/manager/rolelist';
        $this->LayoutData['crumb']['page']['text'] = '权限配置';
        $this->LayoutData['list'] = $list;
        $this->layout('manager/role_list.html',$this->LayoutData);
    }

    /**
     * 权限修改页面
     */
    public function RoleAction()
    {
        $id = $this->get('id')?intval($this->get('id')):0;

        // 数据的修改和保存
        if ($_POST) {
            $post = $this->_postParams();

            if (!empty($post['urls'])) {
                $post['urls'] = preg_replace('/\r\n/',',',$post['urls']);
                $post['urls'] = preg_replace('/\n/',',',$post['urls']);
                $post['urls'] = rtrim(str_replace(',,', ',', $post['urls']), ',');
            }
            $savedata = $post;
            unset($savedata['id']);
            $savedata['update_time'] = date('Y-m-d H:i:s',time());
            $savedata['update_ip'] = $_SERVER["REMOTE_ADDR"];
            $res = $this->RoleModel->Save($savedata,$post['id']);
            if ($res['error'] == 1) {
                $this->jsonResponse(0,$res['msg']);
            }else{
                $this->jsonResponse(1,$res['msg']);
            }
            exit;
        }
        $this->LayoutData['info'] = $this->RoleModel->InfoByID($id);
        if (empty($this->LayoutData['info'])) {$this->LayoutData['info'] = $this->RoleModel->EmptyData();}
        $this->LayoutData['crumb']['category']['url'] = '/admin/manager/rolelist';
        $this->LayoutData['crumb']['page']['text'] = '权限配置';
        $this->layout('manager/role_edit.html',$this->LayoutData);
    }

}
