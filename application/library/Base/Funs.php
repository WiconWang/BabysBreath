<?php
/**
 * 557
 * 定位系统级共用方法
 * @author WiconWang@gmail.com
 * @copyright 2018/1/18 上午11:25
 * @file Funs.php
 */
class Base_Funs
{
    /**
     * 取得系统核心参数
     * @return [type] [description]
     */
    public static function getSystemParam()
    {
        return array();
        if (Comm_Redis::get('SystemParam')) {
            return json_decode(Comm_Redis::get('SystemParam'), true);
        } else {
            $res = array();
            $SettingsModel = new Business_Setpeiz_setpeizModel();
            $res = $SettingsModel->GetAllRecord();
            $parsed = date_parse($res['time']);
            $res['settlestamp'] = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
            if (empty($res)) {
                return false;
            }
            Comm_Redis::set('SystemParam', json_encode($res), 60 * 60 * 24);
            return $res;
        }
    }

    /**
     * 根据内容生成二维码
     * @param string $content
     * @return FileContent
     */
    public static function MakeQRcode($content = '')
    {
        return (new PHPQrcode())->MakeQRcode($content);
    }

    /**
     * 根据数据库配置和表名自动生成MOD层 和Bussiness层
     * @param $table
     * @return bool
     */
    public static function MakeMod($configDB,$table)
    {
        $info = Comm_Db::getDbWrite($configDB)->getAll("SHOW COLUMNS FROM $table;");
        if(!$info){
            echo '表不存在';
            return false;
        }
        $str ="\n";
//        foreach($info as $v){
//            $str .="\t"."public $".$v['Field'].";\n";
//        }

        $path = APPLICATION_PATH."/application/models/Mod/".ucwords($configDB)."/";
        if(empty($table)){
            die('no tablename');
        }
        $tmp = explode('_', $table);
        $pre = $tmp[0];
        $alisname = str_replace($pre . "_", "", $table);
        $tmp3 = explode('_', $alisname);
        foreach ($tmp3 as $key => $val){
            $tmp4[$key] = ucfirst($val);
        }
//        $filename =  implode('', $tmp4);

        $filename =  end($tmp4);
        array_pop ( $tmp4 );
        $path .=implode('/', $tmp4);

        if (substr($path, -1) !='/'){
            $path .= '/';
        }

        if(!is_dir($path)){
            mkdir($path,0755,true);
        }


        $classname = "Mod_" . ucwords($configDB) ."_" . implode('_', $tmp4) ."_" .$filename. "Model";
        $tablename = $table;
        //检测文件是否已存在，如果已经存在则不再生成
        if(file_exists($path.$filename.".php")){
            echo '已经存在'.$path.$filename.".php";
            return false;
        }

        $now = date('Y-m-d  H:i:s',time());
        $modelFile = fopen($filename . ".php", "w") or die("Unable to open file!");
        $txt = '<?php
/**
 * 557 Model for database tables
 * 
 * @author WiconWang@gmail.com
 * @copyright '.$now.'
 * @file '.$filename.'.php
 */
class ' . $classname . ' extends Abstract_M{
	protected $_tbl_name = ' . "'" . $tablename . "'" . ';
	protected $_tbl_alis_name = ' . "'" . $alisname . "'" . ';
	protected static $_instance = null;'.$str .
            '        public static function instance()
        {
            if (!self::$_instance) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
}
?>
';
        fwrite($modelFile, $txt);
//        p('生成文件路径：'.$path.$filename.".php");
        fclose($modelFile);
        copy($filename.".php", $path.$filename.".php");
        unlink($filename.".php");
        return $path.$filename.".php";
    }




    /**
     * 根据数据库配置和表名自动生成MOD层 和Bussiness层
     * @param $table
     * @return bool
     */
    public static function MakBusiness($configDB,$table)
    {
        $info = Comm_Db::getDbWrite($configDB)->getAll("SHOW COLUMNS FROM $table;");
        if(!$info){
            echo '表不存在';
            return false;
        }

        $str ="\n";
        $path = APPLICATION_PATH."/application/models/Bussiness/".ucwords($configDB)."/";
        if(empty($table)){
            die('no tablename');
        }
        $tmp = explode('_', $table);
        $pre = $tmp[0];
        $alisname = str_replace($pre . "_", "", $table);
        $tmp3 = explode('_', $alisname);
        foreach ($tmp3 as $key => $val){
            $tmp4[$key] = ucfirst($val);
        }
        $modname = "Mod_" . ucwords($configDB) ."_" . implode('_', $tmp4) . "Model";
        $businessClassName = "Bussiness_" . ucwords($configDB) ."_" . implode('_', $tmp4) . "Model";
        $filename =  end($tmp4);
        array_pop ( $tmp4 );
        $path .=implode('/', $tmp4);
        if(!is_dir($path)){
            mkdir($path,0755,true);
        }
        if (substr($path, -1) !='/'){
            $path .= '/';
        }
        //检测文件是否已存在，如果已经存在则不再生成
        if(file_exists($path.$filename.".php")){
            echo '已经存在'.$path.$filename.".php";
            return false;
        }

        $tablename = $table;

        $now = date('Y-m-d  H:i:s',time());

        //字段验证规则
        //拼接字段过滤机制
        $fieldFiter = '';
        foreach ($info as $m=>$n) {
            if(strpos('..'.$n['Extra'], 'auto_increment')){ continue;}
            if(strpos('..'.$n['Type'], 'int')){
                $fieldFiter .= '
        if (isset($data["'.$n['Field'].'"])){$SaveDate["'.$n['Field'].'"] = intval($data["'.$n['Field'].'"]);}';
            }elseif(strpos('..'.$n['Type'], 'float') || strpos('..'.$n['Type'], 'double')){
                $fieldFiter .= '
        if (isset($data["'.$n['Field'].'"])){$SaveDate["'.$n['Field'].'"] = floatval($data["'.$n['Field'].'"]);}';
            }else{
                $fieldFiter .= '
        if (isset($data["'.$n['Field'].'"])){$SaveDate["'.$n['Field'].'"] = htmlspecialchars($data["'.$n['Field'].'"], ENT_QUOTES);}';
            }
        }

        $fileContent = '<?php
/**
 * 557 <{{MARK_INFO}}>
 * 
 * @author WiconWang@gmail.com
 * @copyright <{{MARK_TIME}}>
 * @file <{{MARK_FILENAME}}>.php
 */

 
class <{{MODEL_NAME}}>
{
    private $_obj = null;

    /*
     **********************
     *
     *
     *
     * Add your codes here
     *
     *
     *
     **********************
     */

    /**
     * 初始化Mod层
     * @return object
     */
    public function getObj() {
        if (!isset($this->_obj) || empty($this->_obj)) {
            $this->_obj = <{{MODEL_CLASS}}>::instance();
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
        <{{FIELD_CHECK}}>


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


}
';
        //替换信息到代码中

        $fileContent = str_replace('<{{MARK_INFO}}>','Bussiness model for Table : '.$tablename, $fileContent);
        $fileContent = str_replace('<{{MARK_INFO}}>','Bussiness model for Table : '.$tablename, $fileContent);
        $fileContent = str_replace('<{{MARK_TIME}}>',$now, $fileContent);
        $fileContent = str_replace('<{{MARK_FILENAME}}>',$filename, $fileContent);
        $fileContent = str_replace('<{{MODEL_CLASS}}>',$modname, $fileContent);
        $fileContent = str_replace('<{{MODEL_NAME}}>',$businessClassName, $fileContent);
        $fileContent = str_replace('<{{FIELD_CHECK}}>',$fieldFiter, $fileContent);

        $modelFile = fopen($filename . ".php", "w") or die("Unable to open file!");
        fwrite($modelFile, $fileContent);
//        p('生成文件路径：'.$path.$filename.".php");
        fclose($modelFile);
        copy($filename.".php", $path.$filename.".php");
        unlink($filename.".php");
        return $path.$filename.".php";
    }

    private   function bussinessTemplate(){
       return '
';
    }

}