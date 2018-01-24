<?php
/**
 * 557 Bussiness model for Table : dd_admin_group
 * 
 * @author WiconWang@gmail.com
 * @copyright 2018-01-19  11:29:59
 * @file Group.php
 */

 
class Bussiness_Admin_GroupModel
{
    private $_obj = null;

    public function __construct()
    {
        // 初始化缓存的前缀表
        $this->prefix = Comm_Tools::getCachePrefix('admin');
        $this->groupCache = $this->prefix.'group_arr';
        $this->groupnameCache = $this->prefix.'group_name';


    }
    /**
     * 取得所有记录的名字和roles ，并用id做为键位
     * @return array|mixed
     */
    public function getNames()
    {
        if (Comm_Redis::get($this->groupCache)) {
            return json_decode(Comm_Redis::get($this->groupCache),true);
        }else{
            $res = array();
            $info = $this->getObj()->field()-> where(array('status' => 0))-> findAll();
            foreach ($info as $m => $n) {
                $res[$n['id']] = array('name' => $n['name'],'roles' => explode(',', $n['roles']) );
            }
            Comm_Redis::set($this->groupCache,json_encode($res));
            return $res;
        }
    }

    /**
     * 返回一个空的结构
     */
    public function EmptyData()
    {
        return array(
            'name'  => ''  ,
            'roles'  => array()  ,
            'status'  => 0    ,
        );
    }

    public function ClearCache(){
        Comm_Redis::remove($this->groupCache);
        Comm_Redis::remove($this->groupnameCache);
        return true;
    }


    /**
     * 初始化Mod层
     * @return object
     */
    public function getObj() {
        if (!isset($this->_obj) || empty($this->_obj)) {
            $this->_obj = Mod_Default_Admin_GroupModel::instance();
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
        $rows =  $this->getObj()->field()-> where(array('id' => $id))-> findOne();
        // 用户组下的权限为逗号分隔，需要转为数组
        if ($rows && !empty($rows['roles'])) {
            $rows['roles']=explode(',', $rows['roles']);
        }
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
        // 如果取的是全部记录，检索缓存 ，没有则生成一份缓存
        if (empty($where)) {
            if (Comm_Redis::get($this->groupnameCache)) {
                return json_decode(Comm_Redis::get($this->groupnameCache),true);
            }else{
                $info = $this->getObj()->field()-> where($where)-> findAll();
                Comm_Redis::set($this->groupnameCache,json_encode($info));
            }
        }else{
            $info = $this->getObj()->field()-> where($where)-> findAll();

        }
        return $info;
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
        
        if (isset($data["name"])){$SaveDate["name"] = htmlspecialchars($data["name"], ENT_QUOTES);}
        if (isset($data["roles"])){$SaveDate["roles"] = htmlspecialchars($data["roles"], ENT_QUOTES);}
        if (isset($data["status"])){$SaveDate["status"] = intval($data["status"]);}
        if (isset($data["update_time"])){$SaveDate["update_time"] = htmlspecialchars($data["update_time"], ENT_QUOTES);}
        if (isset($data["update_ip"])){$SaveDate["update_ip"] = htmlspecialchars($data["update_ip"], ENT_QUOTES);}


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
        $this->ClearCache();
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
        $res = $this->getObj()->update($data,array("id"=>$id),false);
        $this->ClearCache();
        return $res;
    }

    /**
     * 内部程序增加专用，请不要暴露给外部逻辑使用
     *
     * @param  array  $data 完整数据
     * @return boolean/integer    1/0
     */
    public function insert($data)
    {
        $res = $this->getObj()->insert($data);
        $this->ClearCache();
        return $res;
    }

    /**
     * 删除记录
     *
     * @param $id
     * @return mixed
     */
    public function del($id)
    {
        $res = $this->getObj()->delete(array('id'=>$id));
        $this->ClearCache();
        return $res;
    }

}
