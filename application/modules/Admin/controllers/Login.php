<?php
/**
 * 557
 * MARK
 * @author WiconWang@gmail.com
 * @copyright 2018/1/18 上午11:55
 * @file df1.php
 */
class LoginController extends Abstract_C {
    /*
	登陆 admin/login/login/
	*/
    public function loginAction(){
        // session_destroy();
        if ($_POST) {
            $username = $this->post('username');
            $password = $this->post('password');
            if (empty($username) || empty($password)) {
                $this->jsonResponse(0,'用户名和密码不能为空');
            }
            $UserModel =  new Business_AdminManage_UserModel();
            $userid = $UserModel->CheckUserPass(array('username' => $username,'password' => $password, ));
            if ($userid) {
                $info = $UserModel->getUserDetailByID($userid);
                if (!empty($info['status'])) {
                    $this->jsonResponse(0,'用户名已经被禁用');
                }
                $_SESSION['MANAGE_INFO'] = $info;
                $_SESSION['MANAGE_NAME'] = $info['username'];
                $_SESSION['MANAGE_ID'] = $userid;
                Comm_Log::Log($info['username'],0, 1, $info['username'].'登录了后台');
                setcookie("username",$info['username'], time()+3600*24,'/');
                setcookie("groupname",$info['groupname'], time()+3600*24,'/');
                setcookie("userid",$info['id'], time()+3600*24,'/');
                $this->jsonResponse(1,'登录成功');
            }

            $this->jsonResponse(0,'登录失败,用户名和密码不一致');
        }
        $this->layout('login/login.html');
    }
	public function make_password( $length = 6 ){
		// 密码字符集，可任意添加你需要的字符
		$chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 
		'i', 'j', 'k', 'l','m', 'n', 'o', 'p', 'q', 'r', 's', 
		't', 'u', 'v', 'w', 'x', 'y','z', 'A', 'B', 'C', 'D', 
		'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L','M', 'N', 'O', 
		'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y','Z', 
		'0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
		// 在 $chars 中随机取 $length 个数组元素键名
		$keys = array_rand($chars, $length); 
		$password = '';
		for($i = 0; $i < $length; $i++)
		{
		// 将 $length 个数组元素连接成字符串
		$password .= $chars[$keys[$i]];
		}
		return $password;
	}
	/*
	退出
	*/
	public function loginoutAction(){
        Comm_Log::Log($_SESSION['MANAGE_NAME'],0, 1, $_SESSION['MANAGE_NAME'].'退出了后台');
        $_SESSION['MANAGE_INFO'] ='';
        $_SESSION['MANAGE_ID'] = '';
        session_unset();
        session_destroy();
        session_write_close();
        setcookie(session_name(),'',time()-3600);
        setcookie("username",'', time()-3600,'/');
        setcookie("groupname",'', time()-3600,'/');
        setcookie("userid",'', time()-3600,'/');
        header("Location:/admin/login/login");
    }
}
