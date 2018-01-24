<?php
/**
 * 557 Bussiness model for Table : dd_admin_user
 * 
 * @author WiconWang@gmail.com
 * @copyright 2018-01-19  11:30:00
 * @file User.php
 */

 
class Bussiness_Admin_UserModel
{
    private $_obj = null;


    /**
     * 返回一个空的结构
     */
    public function EmptyData()
    {
        return array(
            'username'  => ''  ,
            'password'  => ''  ,
            'ugroup'  => 0     ,
            'status'  => 0    ,
        );
    }

    /**
     * 验证用户帐号密码
     * @param $checkinfo
     * @return bool
     */
    public function CheckUserPass($checkinfo)
    {
        $data=$this->Data_Check($checkinfo);
        $res = $this->Info(array('username' => $data['username'], ));
        if (empty($res)) {
            return false;
        }

        $password = Comm_Tools::EncryptPassword($data['password'],$res[0]['salt']);

        if ($password != $res[0]['password']) {
            return false;
        }
        return $res[0]['id'];
    }

    /**
     * 取指定用户的详细资料和权限
     * @param $id
     * @return mixed
     */
    public function getUserDetailByID($id)
    {

// if (Comm_Redis::get('dd_manage_uid_'.$id)) {
//     return json_decode(Comm_Redis::get('dd_manage_uid_'.$id),true);
// }else{
        $GroupModel =  new Bussiness_Admin_GroupModel();
        $RoleModel =  new Bussiness_Admin_RoleModel();

        $info = $this->InfoByID($id);
        $groupList = $GroupModel->getNames();
        $roleList = $RoleModel->getNames();
        $userRole = $groupList[$info['usergroup']]['roles'];
        $urls = array();
        if (!empty($userRole)) {
            foreach ($userRole as  $v) {
                if (!empty($roleList[$v]['urls'])) {
                    foreach ($roleList[$v]['urls'] as  $m) {
                        array_push($urls, $m);
                    }
                }

            }
        }
        $info['roles']=array_flip(array_flip($urls));
        unset($info['salt']);

        return $info;
// }
    }


    /**
     * 初始化Mod层
     * @return object
     */
    public function getObj() {
        if (!isset($this->_obj) || empty($this->_obj)) {
            $this->_obj = Mod_Default_Admin_UserModel::instance();
        }
        return $this->_obj;
    }


    /**
     * 按条件及页数检索数据
     *
     * @param array $where
     * @param int $page
     * @param int $page_size
     * @param string $order_by
     * @return array
     */
    public function PageInfo($where = array(),$page = 1, $page_size = 4, $order_by = "created_at desc")
    {
        $page = empty($page) ? 1 : $page;
        $offset = ($page-1) * $page_size;

        $result = array();
        $result["total"] = $this->getObj()->count_all_results();
        $result["data"] = $this->getObj()
            -> field()
            -> where($where)
            -> order_by($order_by)
            -> limit($page_size)
            -> offset($offset)
            -> findAll();

        return $result;
    }

    /**
     * 直接取出指定ID的数据
     *
     * @param $id
     * @return mixed
     */
    public function InfoByID($id)
    {
        $rows =  $this->getObj()->field()-> where(array("id" => $id))-> findOne();
        return $rows;
    }

    /**
     * 指定Where条件正常检索
     *
     * @param $where
     * @return mixed
     */
    public function Info($where)
    {
        return $this->getObj()->field()-> where($where)-> findAll();
    }

    /**
     * 新加、修改主流程
     * 此过程中会进行数据过滤和校验
     *
     * @param  array  $data 完整数据
     * @param  integer $id   数据库ID
     * @return array code为0时为错误
     */
    public function Save($data,$id = 0)
    {
        // 校验数据
        $SaveDate = $this->Data_Check($data);
        if (isset($SaveDate["code"])) {
            return array("code" => 0, "msg" => $SaveDate["msg"]);
        }
        // 写入数据
        $res = $this->_save($SaveDate,$id);
        if ($res) {
            return array("code" => 1, "msg" => "操作成功","data" =>$res);
        }else{
            return array("code" => 0, "msg" => $res["msg"]);
        }
    }

    /**
     * 保存参数 校验规则
     *
     * @param array $data
     * @return array
     */
    private function Data_Check($data=array())
    {
        $res = array("code" => 1, "msg" => "" );
        $SaveDate = array();

        // 字段规则 
        
        if (isset($data["username"])){$SaveDate["username"] = htmlspecialchars($data["username"], ENT_QUOTES);}
        if (isset($data["password"])){$SaveDate["password"] = htmlspecialchars($data["password"], ENT_QUOTES);}
        // 当同时带有未加密的pass和salt时，进行验证和加密
        if (isset($data['orgin_password']) && isset($data['salt'])) {
            $isPasswd = Helper_Validate::isPasswd($data['orgin_password']);
            if (!$isPasswd) {
                return array('code' => 0, 'msg' => '密码应该是6~20个字母和数字组成' );
            }
            $SaveDate['password'] = Comm_Tools::EncryptPassword($data['orgin_password'],$data['salt']);
            $SaveDate['salt'] = $data['salt'];
        }


        if (isset($data["usergroup"])){$SaveDate["usergroup"] = intval($data["usergroup"]);}
        if (isset($data["status"])){$SaveDate["status"] = intval($data["status"]);}
        if (isset($data["update_time"])){$SaveDate["update_time"] = htmlspecialchars($data["update_time"], ENT_QUOTES);}
        if (isset($data["update_ip"])){$SaveDate["update_ip"] = htmlspecialchars($data["update_ip"], ENT_QUOTES);}
        if (isset($data["login_time"])){$SaveDate["login_time"] = htmlspecialchars($data["login_time"], ENT_QUOTES);}
        if (isset($data["login_ip"])){$SaveDate["login_ip"] = htmlspecialchars($data["login_ip"], ENT_QUOTES);}


        if (empty($SaveDate)) {
            return array("code" => 0, "msg" => "没有有效的数据" );
        }
        return $SaveDate;
    }
    /**
     * 内部方法，数据记录的添加和修改 uid存在则更新，否则为新建
     *
     * @param  array  $data 完整数据
     * @param  integer $id   数据库ID
     * @return boolean/integer    1/0
     */
    private function _save($data,$id)
    {
        if (empty($this->InfoByID($id))) {
            $data['create_time']=date('Y-m-d H:i:s',time());
            $data['create_ip'] = $_SERVER["REMOTE_ADDR"];
            return $this->getObj()->insert($data);
        }else{
            return $this->getObj()->update($data,array("id"=>$id),false);
        }
    }

    /**
     * 内部程序更新专用，请不要暴露给外部逻辑使用
     *
     * @param  array  $data 完整数据
     * @param  int   $id   数据库ID
     * @return boolean/integer    1/0
     */
    public function update($data,$id)
    {
        return $this->getObj()->update($data,array("id"=>$id),false);
    }

    /**
     * 内部程序增加专用，请不要暴露给外部逻辑使用
     *
     * @param  array  $data 完整数据
     * @return boolean/integer    1/0
     */
    public function insert($data)
    {
        return $this->getObj()->insert($data);
    }
    /**
     * 删除记录
     *
     * @param $id
     * @return mixed
     */
    public function del($id)
    {
        return $this->getObj()->delete(array('id'=>$id));
    }

}
