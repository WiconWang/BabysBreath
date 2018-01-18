<?php
/**
 * 557
 * 用户模块入口汇总模块 这个页面是没有全局登录验证的
 * @author WiconWang@gmail.com
 * @copyright 2017-04-18 09:59:43
 *
 *  * FQ:
 *
 * Web页面：
 * WEB&&JSON 默认登录页面：http://agent.doudeqipai.com/user/index/signin
 * json游客登录页：/user/index/signin POST  ?guid=4f9619ff-8b25-d212-b42d-10c04fc9631c
 * json正常登录:     /user/index/signin  POST ?mobile=123213&password=123123
 *
 * Web: http://agent.doudeqipai.com/user/index/GuestToUserWeb
 * json发送手机验证码： /user/index/GetMobileCode POST ?mobile=13111111111
 * json游客转用户登录页：/user/index/GuestToUser POST  ?guid=1211111f-8b25-d212-b42d-10c04fc9631c&mobile=13111111111&password=AAA123&vcode=B2mA
 *
 * WEB 用户退出登录页  /user/index/signout
 *
 * http://agent.doudeqipai.com/user/index/ChagePass
 * WEB&&JSON用户改密码 /user/index/ChagePass  POST   ?mobile=13111111111&old_password=AAA123&password=A12233&vcode=teIn
 *
 * Web: http://agent.doudeqipai.com/user/index/UserBindInviteWeb
 * JSON用户绑定邀请码  /user/index/UserBindInvite  POST   mobile=13111111111&incode=112d#
 *
 */
class IndexController extends Abstract_C {

    public function init()
    {
        // 初始化缓存的前缀表
        $prefix = Comm_Config::getConf('config.'.DEVELOPMENT.'.prefix');
        $this->redis_guid_prefix =  $prefix['guid'];
        $this->redis_mobile_prefix =  $prefix['mobile'];
        $this->redis_userinfo_prefix =  $prefix['userinfo'];
        $this->redis_userfund_prefix =  $prefix['userfund'];
        $this->redis_userlogin_prefix =  $prefix['userlogin'];
        $this->redis_count_prefix =  $prefix['count'];
        $this->redis_system_prefix =  $prefix['system'];
    }

    /**
     * 用户默认页面
     * @return layout
     */
    public function indexAction() {
        if (!isset($_SERVER['SERVER_ENV'])||  $_SERVER['SERVER_ENV']!='development'){
            exit;
        }
        $_SESSION=array();
        echo "代理商相关参数转换方法
        <br>传入GET参数 uid，可以得到 用户邀请码 和 用户资料
        <br>传入GET参数 openid，可以得到 用户邀请码 和 用户资料
        <br>传入GET参数 mobile,取得当前的 手机验证码
        <br>传入GET参数 invcode，可以把此 邀请码 反推成uid
        <br>传入GET参数 pass和salt，可以得到 加密结果
        ";;
        echo "<pre>";
        echo "<hr>";
        if (!empty($_GET['pass'] && !empty($_GET['salt']))) {
            print_r('密码：'.$_GET['pass']);
            echo "<hr>";
            print_r('SALT：'.$_GET['salt']);
            echo "<hr>";
            echo  '结果：'.Comm_Tools::EncryptPassword($_GET['pass'],$_GET['salt']);
            echo "<hr>";
        }
        if (!empty($_GET['uid'])) {
            echo  '邀请码：'.Comm_Tools::UidToInvite($_GET['uid']);
            echo "<hr>";
            echo  '对外ID：'.Comm_Tools::EncryptUID($_GET['uid']);
            echo "<hr>";
            $UserModel = new Business_User_AccountModel();
            $result = $UserModel -> GetUserinfoByUid($_GET['uid'],2);
            print_r($result);
        }
        if (!empty($_GET['openid'])) {
            $uid = Comm_Tools::DecryptUID($_GET['openid']);
            echo  '邀请码：'.Comm_Tools::UidToInvite($uid);
            echo "<hr>";
            echo  'UID：'.$uid;
            echo "<hr>";
            $UserModel = new Business_User_AccountModel();
            $result = $UserModel -> GetUserinfoByUid($uid,2);
            print_r($result);
        }
        if (!empty($_GET['invcode'])) {
            echo  'UID：'.Comm_Tools::InviteToUid($_GET['invcode']);
        }
        if (!empty($_GET['mobile'])) {
            echo  '短信验证码：'.Comm_Redis::get('VCode_'.$_GET['mobile']);
        }

        echo "</pre>";
        echo "<hr>SESSION 和 COOKIE<br>";
        echo "<pre>";
        var_dump($_SESSION);
        var_dump($_COOKIE);

        exit;
    }

    /**
     * 用户登录测试
     */
    public function TestGuestWebAction()
    {
        $guid = $this->get('guid')?$this->get('guid'):'';
        if ($guid) {
            setcookie("guid",$guid, time()+3600*24*30,'/');
        }else{
            if (!empty($_COOKIE['guid'])) {
                $guid = $_COOKIE['guid'];
            }else{
                $guid = rand(10000000,99999999)."-80ac-443b-9b33-4ccb0cfd67c8";
                setcookie("guid",$guid, time()+3600*24*30,'/');
            }
        }

        $this->layout('index/test-guid.html',array('guid' => $guid));
    }

    /**
     * 用户登录后台页面
     */
    public function TestUserWebAction()
    {
        setcookie("DEBUGOPENID");
        setcookie("guid");
        $this->layout('index/test_userlogin.html');
    }

    /**
     * 用户登录后台页面
     */
    public function TestMainAction()
    {
        if (empty($_COOKIE['DEBUGOPENID'])) {
            header("location:/user/index/TestUserWeb");
            exit;
        }

        $gameModel = new Business_GameInfo_GamesModel();
        $data['game'] = $gameModel -> Info( array());
        $uid = Comm_Tools::DecryptUID($_COOKIE['DEBUGOPENID']);
        $UserModel = new Business_User_AccountModel();
        $data['info'] = $UserModel -> GetUserinfoByUid($uid,2);

        $data['invite'] = Comm_Tools::UidToInvite($uid);

        if (!empty($_GET['gid'])) {
            $thisgame =  $gameModel -> Info( array('gid' => $_GET['gid']));
            $data['thisgame'] = $thisgame[0];
            $this->layout('index/test_main_game.html',$data);
            exit;
        }
        $this->layout('index/test_main.html',$data);
    }

    /**
     * 用户绑定页面
     */
    public function BindingWebAction()
    {
        if ($this->get('openid')) {
            $this->layout('index/binding.html',array('openid' => $this->get('openid')));
        }else{
            echo "未检测到微信的授权openid，绑定失败，请联系管理员";
        }
    }

    /**
     * 用户绑定微信公众号
     */
    public function BindingAction()
    {
        $mobile = $this->get('mobile')?$this->get('mobile'):'';
        $openid = $this->get('openid')?$this->get('openid'):'';
        $password = $this->get('password')?$this->get('password'):'';
        // APP返回数组
        $return_json  = array('return_code' => 1,'message' => '');
              // 当mobile和password都有的时候进入正常登录流程
        if (!empty($mobile) && !empty($password) && !empty($openid)) {
            if (!Comm_Validate::isMobilePhone($mobile)) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '手机号格式不正确'));
            }
            $uid=0;
            $UserModel = new Business_User_AccountModel();
            // 按mobile查到用户的uid
            $uid =$UserModel -> GetUidByMobile($mobile);
            if (!$uid) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '未能找到此手机号对应的用户'));
            }

            // 根据UID去取用户详细资料
            $result =$UserModel -> GetUserinfoByUid($uid,0,1);
            if (empty($result)) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '无法取到用户资料，数据库故障'));
            }

            //注意API过来的数据本身就进行了一次md5加密。因此不需要再次加密验证
            $db_pass_orgin = Comm_Tools::EncryptPassword($password,$result['salt']);
            // if ($result['password'] != Comm_Tools::EncryptPassword($password,$result['salt'])) {
            if ($result['password'] != $db_pass_orgin) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '密码错误'));
            }else{
                if (!empty($_SESSION['userinfo']['nickname']) && empty($result['nickname'])) {
                    $UserInfoModel = new Business_User_InfoModel();
                    //把默认头像存为头像
                    $sex = $_SESSION['userinfo']['sex'] == 1?1:2;
                    $UserInfoModel->Save(array('nickname' => $_SESSION['userinfo']['nickname'],'headimgurl' => $_SESSION['userinfo']['headimgurl'],'sex' =>$sex),$uid);
                }
                unset($result['id'],$result['uid'],$result['password'],$result['salt']);

                // 当web为3时，认为是登录代理商后台，需要验证下是否为代理身份
                // 登录成功，如果WEB登陆，则下放session和cookie
                if ($result['type'] <= 1) {
                    $this->jsonCommResponse(array('return_code' => 1,'message' => '您还没有绑定手机号。'));
                }
                if ($result['type'] == 2 ) {
                    $this->jsonCommResponse(array('return_code' => 1,'message' => '您还没有绑定邀请码。'));
                }

                 //回插uid数据到DB
                $wxModel = new Business_Wx_UserModel();
                // 清理其它 相同uid的记录
                @$wxModel-> del(array('uid' => $uid));
                $wxquery = $wxModel->update(array('uid' => $uid),array('openid' => $openid));
                if ($wxquery) {
                    setcookie("openid",$result['openid'], time()+3600*24*30,'/');
                    Yaf_Session::getInstance()->__set ( "ADM_LOGIN_ID" , $uid );
                    $this->jsonCommResponse(array('return_code' => 0,'message' => '授权成功'));
                }else{
                    $this->jsonCommResponse(array('return_code' => 1,'message' => '授权失败，请联系管理员，OPENID为'.$openid));
                }


                // 登录成功后向redis发放一个key 以后如果 有其它操作，验证一下这个key ;
                $result['key'] = Comm_Tools::getRandomString(12);
                Comm_Redis::set($this->redis_userlogin_prefix.$uid,$result['key'],60*60*2);

                //557 Mark:系统模拟后台 By:WiconWang@gmail.com At:2017-05-22 14:49:28
                setcookie("DEBUGOPENID",$result['openid'], time()+3600*24,'/');

                $this->jsonCommResponse(array_merge_recursive(array('return_code' => 0,'message' => ''),$result));
            }

        }
    }

    /**
     * 用户登录页面
     */
    public function SignInWebAction()
    {
        $this->layout('index/signin.html');
    }

    /**
     * 用户登录
     * 游客登录时传入guid。会验证是否已经存在，存在则返回id。不存在则新建
     * 标准用户传入手机号和密码，验证是否存在
     * @param Char $guid 用户游客号
     * @param Char $mobile 用户手机号
     * @param Char md5&salt $password 用户密码
     * @param int  $web 是否通过web登录，默认0为APP登陆，如果为1则认为Web登录则下放cookie和session
     */
    public function SignInAction()
    {

        $guid = $this->get('guid')?strtolower($this->get('guid')):'';
        $mobile = $this->get('mobile')?$this->get('mobile'):'';
        $password = $this->get('password')?$this->get('password'):'';
        $web = $this->get('web')?$this->get('web'):0;

        // APP返回数组
        $return_json  = array('return_code' => 1,'message' => '');

        // 用户登录的验证码部分，暂时无用
        // if ($this->get('captcha')) {
        //     Comm_Captcha::generate(4);
        //     exit;
        // }


        // 当mobile和password都有的时候进入正常登录流程
        if (!empty($mobile) && !empty($password)) {

            if (!Comm_Validate::isMobilePhone($mobile)) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '手机号格式不正确'));
            }
            $uid=0;
            $UserModel = new Business_User_AccountModel();
            // 按mobile查到用户的uid
            $uid =$UserModel -> GetUidByMobile($mobile);
            if (!$uid) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '未能找到此手机号对应的用户'));
            }

            // 根据UID去取用户详细资料
            $result =$UserModel -> GetUserinfoByUid($uid,1,1);
            if (empty($result)) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '无法取到用户资料，数据库故障'));
            }



            //注意API过来的数据本身就进行了一次md5加密。因此不需要再次加密验证
            $db_pass_orgin = empty($web)?Comm_Tools::EncryptApiPassword($password,$result['salt']):Comm_Tools::EncryptPassword($password,$result['salt']);
            // if ($result['password'] != Comm_Tools::EncryptPassword($password,$result['salt'])) {
            if ($result['password'] != $db_pass_orgin) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '密码错误'));
            }else{
                unset($result['id'],$result['uid'],$result['password'],$result['salt']);
            // 当web为3时，认为是登录代理商后台，需要验证下是否为代理身份
            // 登录成功，如果WEB登陆，则下放session和cookie
                if ($web == 3 ) {
                    if ($result['type'] >= 3) {
                        //对于type 3的人来说。查下现金池，如果不够10元。禁止登陆


                        setcookie("openid",$result['openid'], time()+3600*24*30,'/');
                        Yaf_Session::getInstance()->__set ( "ADM_LOGIN_ID" , $uid );
                    }else{
                        $this->jsonCommResponse(array('return_code' => 1,'message' => '您还没有绑定邀请码，绑定后才可以登陆此后台，请在游戏个人资料中绑定邀请码。'));
                    }
                }

                // 登录成功后向redis发放一个key 以后如果 有其它操作，验证一下这个key ;
                $result['key'] = Comm_Tools::getRandomString(12);
                Comm_Redis::set($this->redis_userlogin_prefix.$uid,$result['key'],60*60*2);

                //557 Mark:系统模拟后台 By:WiconWang@gmail.com At:2017-05-22 14:49:28
                setcookie("DEBUGOPENID",$result['openid'], time()+3600*24,'/');

                //更新用户的登录记录回数据库
                $UserInfoModel = new Business_User_InfoModel();
                @$UserInfoModel ->  Update(array('login_time' => date('Y-m-d H:i:s',time())),$uid);


                // 以下为赢牌送现金模块   返回return_code 0 时提示给APP否则返回任何内容
                // 如果停用 此模块。请在此模块文件顶部，把 ACTIVES更新为0，并注释掉 exit
                //
                // 根据此次登录时间，对于已绑定邀请码的用户，发放每天两张现金券
                if ($result['type'] == 3) {
                    $CashTicketModel = new Business_Fund_GameCashTicketModel();
                    @$CashTicketModel->SendCashTicketOnLogin($uid);
                }
                // 赢牌End

                // 如果非页面登录，则进行用户类型转换
                if ($web != 3 ) {
                    $result['type'] = Comm_Tools::UserTypeToGameLevel($result['type'],$result['all_cost']);
                }

                $ItemsModel = new Business_Game_ItemsModel();
                $result['Items'] = $ItemsModel -> GetItemsByUid($uid);

                $this->jsonCommResponse(array_merge_recursive(array('return_code' => 0,'message' => ''),$result));
            }



        // 当只有guid时认为是游客登入，需要验证一次是否需要注册
        } elseif (!empty($guid) && Comm_Validate::isGUID($guid)) {

            $uid=0;
            $UserModel = new Business_User_AccountModel();

            // 按guid查到用户的uid
            $uid =$UserModel -> GetUidByGuid($guid);

            // 如果取不到uid 则进入游客新建流程
            if (empty($uid)) {
                $uid = $this->CreatByGuid($guid);
                if (!$uid) {
                    $this->jsonCommResponse(array('return_code' => 1,'message' => '操作失败，无法将新用户唯一标记写入数据库'));
                }

                //新开游戏用户，送30张券
                $CashModel = new Business_Fund_GameCashTicketModel();
                @$CashModel->InsertRow($uid,3, '新加用户',30);
            }

            // 根据UID去取用户详细资料
            $result =$UserModel -> GetUserinfoByUid($uid);
            if ($result) {
                // 游客不需要登录web
                //登录成功，如果WEB登陆，则下放session和cookie
                // if ($web) {
                //     setcookie("openid",$result['openid'], time()+3600*24*30,'/');
                //     Yaf_Session::getInstance()->__set ( "ADM_LOGIN_ID" , $uid );
                // }
                if (empty($result['nickname'])) {
                    $result['nickname'] = '游客';
                }
                if (empty($result['headimgurl'])) {
                    $result['headimgurl'] = 'http://agent.doudeqipai.com/uploads/gl.jpg';
                }

                $result['type'] = intval($result['type']);
                $result['status'] = intval($result['status']);
                $result['sex'] = intval($result['sex']);

                //557 Mark:系统模拟后台 By:WiconWang@gmail.com At:2017-05-22 14:49:28
                setcookie("DEBUGOPENID",$result['openid'], time()+3600*24,'/');

                $result['key'] = Comm_Tools::getRandomString(12);
                Comm_Redis::set($this->redis_userlogin_prefix.$uid,$result['key'],60*60*2);

                //更新用户的登录记录回数据库
                $UserInfoModel = new Business_User_InfoModel();
                @$UserInfoModel ->  Update(array('login_time' => date('Y-m-d H:i:s',time())),$uid);


                // 以下为赢牌送现金模块   返回return_code 0 时提示给APP否则返回任何内容
                // 如果停用 此模块。请在此模块文件顶部，把 ACTIVES更新为0，并注释掉 exit
                //
                // 根据此次登录时间隔，发放每天两张现金券
                if ($result['type'] == 3) {
                $CashTicketModel = new Business_Fund_GameCashTicketModel();
                @$CashTicketModel->SendCashTicketOnLogin($uid);
                }
                // 赢牌End


                // 如果非页面登录，则进行用户类型转换
                if ($web != 3 ) {
                    $result['type'] = Comm_Tools::UserTypeToGameLevel($result['type'],$result['all_cost']);
                }

                $ItemsModel = new Business_Game_ItemsModel();
                $result['Items'] = $ItemsModel -> GetItemsByUid($uid);
                $this->jsonCommResponse(array_merge_recursive(array('return_code' => 0,'message' => ''),$result));
            }else{
                $this->jsonCommResponse(array('return_code' => 1,'message' => '无法取到用户资料，数据库故障'));
            }

        // 其它无正常数据状态
        }else{
            $this->jsonCommResponse(array('return_code' => 1,'message' => '参数不正确请检查'));
            // $this->layout('index/signin.html',$data);
        }


    }

    /**
     * 通过GUID注册用户
     * @param char $guid GUID
     * @return int UID值/false
     */
    private function CreatByGuid($guid){
        // 新建
        // DB写自增ID
        $UserModel = new Business_User_AccountModel();
        $uid =$UserModel -> AutoUIDSave();

        // 添加到guid2uid表
        $res =$UserModel -> Guid2UidSave($guid,$uid);

        if (!$res) {
            return false;
        }

        // 添加缓存检索记录
        Comm_Redis::set($this->redis_guid_prefix.$guid,$uid);
        // 初始化个人资料
        $UserInfoModel = new Business_User_InfoModel();
        $res1 =$UserInfoModel -> Save(array('salt' => Comm_Tools::getRandomString(4),'type' => 1,'sex' =>0,'status' => 0 ),$uid);
        $UserFieldModel = new Business_User_FieldModel();
        $res2 =$UserFieldModel -> Save(array('create_time' =>date('Y-m-d H:i:s')),$uid);
        // $UserTreeModel = new Business_User_TreeModel();
        // $uid =$UserTreeModel -> Save(array(),$uid);

        // 拼接空数据并提供给缓存
        $arr_userinfo = array('openid' => Comm_Tools::EncryptUID($uid),'type' => 1,'nickname' => '','headimgurl' => '' );
        Comm_Redis::set($this->redis_userinfo_prefix.$uid,json_encode($arr_userinfo));
                    // 返回成功
        if (!$res1 || !$res2) {
            return false;
        }
        return $uid;
    }

    /**
     * 直接用手机号注册
     * @param char $guid GUID
     * @return int UID值/false
     */
    public function SignUpAction(){
        $mobile = $this->get('mobile')?$this->get('mobile'):'';
        $password = $this->get('password')?$this->get('password'):'';
        $vcode = $this->get('vcode')?$this->get('vcode'):'';
        if (!empty($mobile) && !empty($password) && !empty($vcode)) {

            if (!Comm_Validate::isMobilePhone($mobile)) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '手机号格式不正确'));
            }
            // 验证码验证
            $Truevcode = Comm_Tools::MobileCodeCheck($mobile);
            if (!$Truevcode || $Truevcode != $vcode) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '验证码错误或者已过期'));
            }

            $UserModel = new Business_User_AccountModel();
            //验证此手机号是否已被绑定
            if ($UserModel -> GetUidByMobile($mobile)) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '此手机号已被其它号码绑定，请更换手机号再试'));
            }

            $res =$UserModel -> UserSignup($mobile,$password);
            if (!$res) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '注册失败'));
            }
            $this->jsonCommResponse(array('return_code' => 0,'message' => ''));
        }else{
            $this->jsonCommResponse(array('return_code' => 1,'message' => '参数不正确请检查'));
            // $this->layout('index/signup.html',$data);
        }


    }



    public function GuestToUserWebAction()
    {
        $this->layout('index/guest2user.html');
    }

    /**
     *
     * 用户游客转用户
     * @param char $guid GUID
     * @param char $mobile 手机号
     * @param char $password 用户密码
     * @param vode $vcode 用户验证码
     */
    public function GuestToUserAction()
    {

        $guid = $this->get('guid')?strtolower($this->get('guid')):'';
        $mobile = $this->get('mobile')?$this->get('mobile'):'';
        $password = $this->get('password')?$this->get('password'):'';
        $vcode = $this->get('vcode')?$this->get('vcode'):'';
        $openid = $this->get('openid')?intval($this->get('openid')):0;

        if (empty($mobile) || empty($password) || empty($vcode)) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '数据缺少'));
        }
        $UserModel = new Business_User_AccountModel();
        $uid = 0;
        // 如果GUID为空。则查询openid,把openid转为uid和GUID，此部分要遍历多库，因为不建议使用
        if (empty($guid)) {
            if (empty($openid)) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => 'openid数据缺少'));
            }
            $uid = Comm_Tools::DecryptUID($openid);
            $guid = $UserModel -> GetGUIDByUid($uid);
            if (empty($guid)) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => 'guid数据缺少'));
            }
        }else{
            if (!Comm_Validate::isGUID($guid)) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => 'GUID格式不正确'));
            }
        }

        //验证此手机号是否已被绑定
        if ($UserModel -> GetUidByMobile($mobile)) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '此手机号已被其它号码绑定，请更换手机号再试'));
        }

        $Truevcode = Comm_Tools::MobileCodeCheck($mobile);
        if (!$Truevcode || $Truevcode != $vcode) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '验证码错误或者已过期'));
        }
        $res =$UserModel -> GuestToUser($guid,$mobile,$password,$uid);
        if (!$res) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '无法绑定用户手机号和密码，请检查GUID是否已使用过或清理缓存'));
        }
        $uid = Comm_Tools::DecryptUID($res['openid']);
        setcookie("guid",0, time()-1,'/');

        $CashModel = new Business_Fund_GameCashTicketModel();
        @$CashModel->InsertRow($uid,3, '用户绑定手机号',5);

        $gold = $this->getGoldOnBlindMobile();
        $GameGoldModel = new Business_User_GameGoldModel();
        @$GameGoldModel->SendGold(0,$uid,0,$gold,2,0 ,'游客转用户赠送金币'.$gold);

        $this->jsonCommResponse(array('return_code' => 0,'message' => '您已获得'.$gold.'个金币'));
    }

    public function UserBindInviteWebAction()
    {
        $this->layout('index/UserBindInvite_web.html');
    }
    /**
     * 用户绑定邀请码，并生成用户树功能
     * 首先，用户得是已经绑定了手机号的正常用户
     * @param int $openid 用户id
     * @param char $incode 用户邀请码
     */
    public function UserBindInviteAction()
    {

        // $mobile = $this->get('mobile')?$this->get('mobile'):'';
        $incode = $this->get('incode')?$this->get('incode'):'';
        $incode = str_replace(' ', '', $incode);
        // if (empty($mobile) || empty($incode)) {
            // $this->jsonCommResponse(array('return_code' => 1,'message' => '参数有误'));
        // }
        $UserModel = new Business_User_AccountModel();
        // 按mobile查到用户的uid
        // $uid =$UserModel -> GetUidByMobile($mobile);

        $openid = $this->get('openid')?$this->get('openid'):'';
        if (!$openid) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '激活失败,未找到openid'));
        }
        $uid= Comm_Tools::DecryptUID(intval($openid));
        if ($uid <=0) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '激活失败,openid不正确'));
        }


        if (!$uid || empty($incode)) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '未能找到您提供的手机号对应的用户'));
        }

        // 根据UID去取用户详细资料 带用户树的最大记录
        $result =$UserModel -> GetUserinfoByUid($uid,2);
        if ($result['type'] <2 ) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '游客无法绑定邀请码，请先到个人信息中绑定手机号码','url' => '/app/index/GuestToUserWeb'));
        }
        if (!empty($result['up_layers_ids'])) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '用户已经绑定过邀请码了'));
        }
        // 解析用户邀请码
        $inuid = Comm_Tools::InviteToUid($incode);
        if (!$inuid) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '邀请码格式错误'));
        }
        if ($inuid < 32) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '邀请码暂不可用'));
        }
        if ($inuid == $uid) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '您无法绑定自己的邀请码'));
        }
        $in_result =$UserModel -> GetUserinfoByUid($inuid,2);
        if (!$in_result) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '您的邀请人不存在，请核对邀请码'));
        }


        //拼接最新的30个名单
        $up_layersIds_arr = array();
        if (empty($in_result['up_layers_ids'])) {
            $up_layersIds_arr = array($inuid);
        }else{
            // 取一下系统的层数
            $config = Comm_Config::getConfig('config');
            //1-$config['AgentLayer']  因为以后不按固定分成了，所以这里写了30，支持最多三十级分成。
            //具体的分成层数 购买时按游戏设置进行
            $up_layersIds_arr = array_slice(explode(',',$in_result['up_layers_ids']), -25);
            array_push($up_layersIds_arr, $inuid);
        }
        $new_up_layersIds =implode(',', $up_layersIds_arr);



        // 把最新的名单更新回db，并同时刷新缓存
        $UserFieldModel = new Business_User_FieldModel();
        $UserInfoModel = new Business_User_InfoModel();

        $data = array('up_id' => $inuid, 'up_layers_ids' => implode(',', $up_layersIds_arr) );



        $UserFieldModel->getObj(Comm_Db::getDbNum($uid))->startTrans();
        $res = $UserFieldModel->Treesave($data,$uid);
        $res2 = $UserInfoModel->Update(array('type' => 3),$uid);

        foreach ($up_layersIds_arr as $v) {
                    //给代理商每日结算的缓存中的 今天的新用户统计数也加1，。此数据会在定时任务中写入到数据库中
            $day = date('Ymd',time());
            $ids = array();
            $ids[] = $v;
            Comm_Tools::Cache_Count_Users($day,$ids);
        }

        // 检测是否已经有了游戏，如果有游戏则把这些进行处理
        if (!empty($result['mygames'])) {
            $gamelist = explode(',', $result['mygames']);
            foreach ($gamelist as $gid) {
        // 检索用户上级的记录是否已创建,如果没有则进行创建
                $FundModel  = new Business_Fund_BonusModel();
                foreach ($up_layersIds_arr as $v) {
            // 因为数量不固定，这里不再检测一次成功了
            // 后期需要评估提交失败的情况决定是否用回滚
                    $FundModel->CreateBonus($v,$gid);
            // 更新积分
                    $vinfo = array();
                    $vinfo =$UserModel -> GetUserinfoByUid($v,2);
            //557  waiting 这里更新积分没区分游戏 否则需要多连查一表，配对mygames
                    if ($vinfo['type'] == 4 ) {
                // 给上级代理商统计加1
                        if ($vinfo['agent_score'] >100 ) {
                            $UserModel -> UpAgentScore($v,1);
                        }

                        // $CountCacheName = $this->redis_userinfo_prefix.'_Count'.date('Y-m-d',time()).'_'.$v;
                        // $CountCache = empty(Comm_Redis::get($CountCacheName))?1:Comm_Redis::get($CountCacheName)+1;
                        // Comm_Redis::set($CountCacheName,$CountCache,60*60*24);
                    }

                }

            }
        }


        if ($res && $res2) {
            $data['utreeid'] = $res;
            unset($result['utreeid']);
            // 新数据丢入缓存
            $UserFieldModel->getObj(Comm_Db::getDbNum($uid))->commit();
            $result['type'] = 3;
            Comm_Redis::set($this->redis_userinfo_prefix.$uid,json_encode(array_merge_recursive($result,$data)));



            $gold = $this->getGoldOnBlindinvide();
            $GameGoldModel = new Business_User_GameGoldModel();
            @$GameGoldModel->SendGold(0,$uid,0,$gold,2,0 ,'用户绑定邀请码赠送'.$gold.'金币');

            //向用户发放资金券 绑定邀请码发放五张
            $CashModel = new Business_Fund_GameCashTicketModel();
            @$CashModel->InsertRow($uid,3, '用户绑定邀请码',5);
            // 给上一级用户发放奖金券 $inuid
            @$CashModel->SendCashTicketOnBind($inuid,$uid,10);

            $userGameLevel = Comm_Tools::UserTypeToGameLevel($result['type'],$result['all_cost']);
            // 通知游戏服务器更新用户组为VIP1
            $postFields = array(
                'openid' => $openid,
                'KindID' => 616,
                'UserType' => 0,
                'GoodCount' => 0,
                'FreeCount' => 0,
                'gameLevel' => $userGameLevel,
            );
            @Comm_Tools::curl('http://'.$_SERVER['SERVER_NAME'].'/api/game_platform/buySuccess.php',  'POST', $postFields,null,1 );

            $this->jsonCommResponse(array('return_code' => 0,'message' => '您已获得'.$gold.'个金币'));
        } else {
            $UserFieldModel->getObj(Comm_Db::getDbNum($uid))->rollback();
            $this->jsonCommResponse(array('return_code' => 1,'message' => '邀请码保存失败'));
        }
    }




    /**
     * 给手机号发送验证码并做缓存
     * @param char $mobile 手机号
     */
    public function GetMobileCodeAction()
    {
        $mobile = $this->get('mobile')?$this->get('mobile'):'';

        //检索手机号格式是否正确
        if (!Comm_Validate::isMobilePhone($mobile)) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '手机号格式不正确'));
        }

        //检测60秒过期标记是否已经到，60秒内不允许再次发送同一手机号的短信
        $exist = Comm_Tools::MobileCodeExist($mobile);
        if ($exist) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '短信正在发送中，请稍等'));
        }
        // 新生成手机验证码
        if ($mobile) {
            $makeCode = Comm_Tools::MobileCodeMake($mobile);
        }
        // 开始发送短信
        $res = Comm_Tools::SendMessage($mobile,array('code' => $makeCode,'product' => 'bb'),'SMS_46195220');


        $msgcode = '';
        if (DEVELOPMENT == 'development'){
            $msgcode = ',为测试方便，直接下发验证码：'.$makeCode;
        }


        if ($res == 1) {
            $this->jsonCommResponse(array('return_code' => 0,'message' => '短信发送成功'.$msgcode));
        }else{
            $this->jsonCommResponse(array('return_code' => 1,'message' => '短信发送失败'.$msgcode));
        }
    }




    /**
     * 微信登录机制
     * 首次登录后进入登录页，并绑定微信与uid
     * 再次进入微信页，调用自动登录
     * 如果客户退出登录并切换其它帐号，则先解绑帮微信uid关系。重新绑定
     */
    public function WeichatLoginAction()
    {
        //取得信息登录凭证

        //检索凭证与uid绑定关系是否已存在，存在则调用登录流程，

        //若不存在则进行关联操作，需要在SignInAction中添加微信登录的参数。并在登录成功后返回此参数，并写绑定关系库


    }

    /**
     * 修改密码
     * @param int $mobile 用户手机号
     * @param char $vcode 用户手机验证码
     * @param char $old_password 原手机号密码
     * @param char $password 新密码
     */
    public function ChagePassAction()
    {
        $mobile = $this->get('mobile')?$this->get('mobile'):'';
        $vcode = $this->get('vcode')?$this->get('vcode'):'';
        $old_password = $this->get('old_password')?$this->get('old_password'):'';
        $password = $this->get('password')?$this->get('password'):'';
        $App = $this->get('App')?$this->get('App'):'';
        if ($App == 'is') {
            $old_password = 'xxx';
        }

        if (!empty($mobile) && !empty($vcode) && !empty($old_password) && !empty($password)) {
            //验证手机号码是否正确
            $Truevcode = Comm_Tools::MobileCodeCheck($mobile);
            if (!$Truevcode || $Truevcode != $vcode) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '验证码错误或者已过期'));
            }

            $UserModel = new Business_User_AccountModel();
            // 取用户
            $uid = $UserModel -> GetUidByMobile($mobile);
            if (!$uid) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '未找到此用户'));
            }
            $result = $UserModel -> GetUserinfoByUid($uid,0,1);
            // 如果是APP的找回密码功能则不用再验证旧密码
            if ($App != 'is') {
                // 验证旧密码是否正确
                if ($result['password'] != Comm_Tools::EncryptPassword($old_password,$result['salt'])) {
                    $this->jsonCommResponse(array('return_code' => 1,'message' => '旧密码错误'));
                }
            }
            // 保存新密码到数据库
            $UserInfoModel = new Business_User_InfoModel();
            $data = array('orgin_password' => $password,'salt' => $result['salt']);
            $res = $UserInfoModel ->Save($data,$uid);
            if (isset($res['error']) && $res['error'] != 0) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => $res['msg']));
            }
            //更新密码不需要更新缓存
            $this->jsonCommResponse(array('return_code' => 0,'message' =>''));
        }

        $this->jsonCommResponse(array('return_code' => 1,'message' => '参数不正确请检查'));
        // $this->layout('index/changepass.html');
    }


    /**
     * 用老的密码来 修改密码 用于代理 商后台
     */
    public function ChagePassWithOldAction()
    {
        $uid = Yaf_Session::getInstance()->__get( "ADM_LOGIN_ID");
        $old_password = $this->post('old_password')?$this->post('old_password'):'';
        $password = $this->post('password')?$this->post('password'):'';

        if (!empty($uid) && intval($uid) >0 && !empty($old_password) && !empty($password)) {
            // 验证旧密码是否正确
            $UserModel = new Business_User_AccountModel();
            $result = $UserModel -> GetUserinfoByUid($uid,2,1);
            if ($result['password'] != Comm_Tools::EncryptPassword($old_password,$result['salt'])) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '旧密码错误'));
            }
            // 保存新密码到数据库
            $UserInfoModel = new Business_User_InfoModel();
            $data = array('orgin_password' => $password,'salt' => $result['salt']);
            $res = $UserInfoModel ->Save($data,$uid);
            if (isset($res['error']) && $res['error'] != 0) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => $res['msg']));
            }
            //更新密码不需要更新缓存
            $this->jsonCommResponse(array('return_code' => 0,'message' =>''));
        }

        $this->jsonCommResponse(array('return_code' => 1,'message' => '参数不正确请检查'));
        // $this->layout('index/changepass.html');
    }

    /**
     * 更新一些资料 专给代理商后台用
     */
    public function EditInfosAction()
    {
        $saveinfo = array();
        $uid = Yaf_Session::getInstance()->__get( "ADM_LOGIN_ID");
        if ($this->post('nickname')) {
            $saveinfo['nickname'] = $this->post('nickname');
            $UserInfoModel = new Business_User_InfoModel();
            $res = $UserInfoModel ->Save($saveinfo,$uid);
        }
        if ($this->post('alipay') || $this->post('wechat')) {
            $mobile = $this->post('mobile');
            $vcode = $this->post('vcode');
            $password = $this->post('password');
            // 验证码验证
            $Truevcode = Comm_Tools::MobileCodeCheck($mobile);
            if (!$Truevcode || $Truevcode != $vcode) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '验证码与预留手机号不符，请重新发送短信再试'));
            }

            $UserModel = new Business_User_AccountModel();
            $result = $UserModel -> GetUserinfoByUid($uid,2,1);
            if ($result['password'] != Comm_Tools::EncryptPassword($password,$result['salt'])) {
                $this->jsonCommResponse(array('return_code' => 1,'message' => '密码验证失败'));
            }

            if ($this->post('alipay')) {
                $saveinfo['alipay'] = $this->post('alipay');
            }
            if ($this->post('wechat')) {
                $saveinfo['wechat'] = $this->post('wechat');
            }
            $UserInfoModel = new Business_User_FieldModel();
            $res = $UserInfoModel ->Save($saveinfo,$uid);
        }
        if (isset($res['error']) && $res['error'] != 0) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => $res['msg']));
        }
        $this->jsonCommResponse(array('return_code' => 0,'message' => '保存成功'));
    }

    public function EditMobileAction()
    {
        $mobile = $this->post('mobile');
        $vcode = $this->post('old_vcode');
        $new_mobile = $this->post('newphone');
        $new_vcode = $this->post('vcode');
        $password = $this->post('password');

            // 旧验证码验证
        $Truevcode = Comm_Tools::MobileCodeCheck($mobile);
        if (!$Truevcode || $Truevcode != $vcode) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '验证码与预留手机号不符，请重新发送短信再试'));
        }

            // 新验证码验证
        $Truevcode2 = Comm_Tools::MobileCodeCheck($new_mobile);
        if (!$Truevcode2 || $Truevcode2 != $new_vcode) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '新手机验证码与新手机号不符'));
        }


        $uid = Yaf_Session::getInstance()->__get( "ADM_LOGIN_ID");
        $UserModel = new Business_User_AccountModel();
        $uinfo = $UserModel -> GetUserinfoByUid($uid,2,1);

            //手机号是否已用验证

        $if_exist =$UserModel -> GetUidByMobile($new_mobile);
        if ($if_exist) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '您新填写的手机号已被注册'));
        }


            // 密码验证
        if ($uinfo['password'] != Comm_Tools::EncryptPassword($password,$uinfo['salt'])) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '密码验证失败'));
        }
        $result =  $UserModel -> ChangeMobile($uid,$mobile,$new_mobile);
        if ($result) {
            $this->jsonCommResponse(array('return_code' => 0,'message' => '保存成功'));
        }else{
            $this->jsonCommResponse(array('return_code' => 1,'message' => '保存失败，请重新尝试'));

        }
    }

    /**
     * 用户退出
     * @param Char $mobile 用户登录名
     */
    public function SignOutAction()
    {
        Yaf_Session::getInstance()->del( "ADM_LOGIN_ID");
        setcookie("openid",'', -1,'/');
        header('location:/user/index/signinweb');
    }


    /**
 * 用户销毁，用户不允许真实注销，因此主要的逻辑就是解绑定用户手机号
 * @param [type] $openid    用户公开id
 * @param [type] $mobile  用户手机号
 */
    public function UserDestroyAction()
    {

        $mobile = $this->post('mobile');
        $vcode = $this->post('vcode');
        $password = $this->post('password');
        $suggest = $this->post('suggest');
        $payway = $this->post('payway');

        $uid = Yaf_Session::getInstance()->__get( "ADM_LOGIN_ID");

            // 验证码验证
        $Truevcode = Comm_Tools::MobileCodeCheck($mobile);
        if (!$Truevcode || $Truevcode != $vcode) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '验证码与预留手机号不符，请重新发送短信再试'));
        }

        $UserModel = new Business_User_AccountModel();
        $userinfo = $UserModel -> GetUserinfoByUid($uid,2,1);
        if ($userinfo['password'] != Comm_Tools::EncryptPassword($password,$userinfo['salt'])) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '密码验证失败'));
        }

        $percent = 0;
        $TicketID = 0;


        //查询 是否为员工
        $person_model = new Business_Personnel_personnelModel();
        $person = $person_model -> select_one(array('uid' => $uid ));
        if (!empty($person)) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '员工禁止销户'));
        }


        $txmodel = new Business_Tix_tixModel();
        $TxOrder = $txmodel->select_one(array('uid' => $uid,'type' => 9));
        if (!empty($TxOrder)) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '您已经提交过销户申请了，请等待处理'));
        }
        $price = $userinfo['wallet'];
        //用户奖金池

        $pool = 0;
        $userpoolModel = new Business_Fund_BonusModel();
        $poolarr = $userpoolModel->InfoByUid($uid);
        if (!empty($poolarr)) {
            foreach ($poolarr as $k => $v) {
                $price += $v['pool'];
            }
        }
        $orgin_money = $price;

            // 查询用户的用户券
        $TicketModel = new Business_User_TicketModel();
        $TicketRes = $TicketModel -> Info(array('type_id' => 3, 'uid' => $uid,'validity' => 1),$uid);
            //如果有则用，没有则用手续费
        if (!empty($TicketRes)) {
            $TicketID = $TicketRes[0]['id'];
            $TicketModel -> _save(array('validity' => 0 ),$uid,$TicketID);
            $percent = 0;

            $prefix = Comm_Config::getConf('config.'.DEVELOPMENT.'.prefix');
            Comm_Redis::remove($prefix['usertick'].$uid);
        }else{
            $Interest = $price*0.03;
            if ($Interest < 1) {
                $Interest = 1;
            }
            $price = $price - $Interest;
            $percent = 0.03;
        }


        //提现订单流程
        $nonce_str = $this -> createNoncestr();
        //随机数
        $openid = $_SESSION['userinfo']['openid'];
        //'oMAipw6FIQwuU1ltVjnVwZbCuumw';//用户唯一标识
        $re_user_name = $_SESSION['userinfo']['nickname'];
        //'大男孩';//用户姓名
        //组织添加参数
        $param = array();
        $param['uid'] = $uid;
        $param['money'] = $price;
        $param['orgin_money'] = $orgin_money;
        $param['percent'] = $percent;
        $param['create_time'] = date('Y-m-d H:i:s', time());
        $param['openid'] = $openid;
        $param['rand_str'] = $nonce_str;
        $param['code'] = $this -> pay_get_dingdan_code('X');
        $param['tixuser'] = '[销户]'.$re_user_name;
        $param['ticket_id'] =   $TicketID ;
        $param['type'] = 9;

        //$param['mobile'] = $userinfo['mobile'];
        $query = $txmodel -> add($param);
        if (!$query) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '订单生成失败'));
        }


        $UserFieldModel = new Business_User_FieldModel();
        //减少用户金额
        $res1 = $UserFieldModel -> update(array('wallet' => 0), $uid);
        if ($res1['error']) {
            $this -> jsonResponse(0, $res1['msg']);
        }
        // 日志
        Comm_Redis::remove($prefix['userinfo'].$uid);
        Comm_Tools::LogsApi(1, '钱包', '消户-', $uid, array('wallet' => 0));

        //2添加金额流水
        $FundAssetModel = new Business_Fund_AssetStreamModel();
        $InsertAsset = array(
            'gid' => 0,
            'sid' => 0,
         'money' => 0, //钱包总金额
         'orgin_price' => -$orgin_money,
        'price' => -$price, //当前交易的金额 对于消费来说 日志按负数记录
        'channel' => 2, //渠道 2为提现
        'status' => 0, //状态，正常为0  对于提现有审核为1
        'order_id' => $param['code'],
        );
        $res3 = $FundAssetModel -> Save($InsertAsset, $uid);
        // 日志

        Comm_Tools::LogsApi(1, '资金', '消户-提现流水', $uid, $InsertAsset);



            // 清理用户信息
        $result = $UserModel -> UserDestroy($uid,$mobile);



            // 清理用户缓存

        if ($result) {
            $this->jsonCommResponse(array('return_code' => 0,'message' => '销毁成功'));
        }else{
            $this->jsonCommResponse(array('return_code' => 1,'message' => '销毁失败'));

        }
    }


    /**
     * 用户每一次进入游戏时激活游戏
     * 激活游戏这一步最重要的作用是初始化用户分级表,以便后期能为用户线下人数统计和分红做准备
     * @param integer $uid [description]
     * @param integer $gid [description]
     */
    public function ActiveGamesAction()
    {
        $gid = $this->get('gid')?$this->get('gid'):0;
        $openid = $this->get('openid')?$this->get('openid'):'';
        if (!$openid) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '激活失败,未找到openid'));
        }
        $uid= Comm_Tools::DecryptUID(intval($openid));
        if ($uid <=0) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '激活失败,openid不正确'));
        }
        $UserModel = new Business_User_AccountModel();
        $uinfo=  $UserModel ->GetUserinfoByUid($uid,2);
        if (empty($uinfo['mygames'])) {
            $gamelist = array();
        }else{
            $gamelist = explode(',', $uinfo['mygames']);
        }

        //记录此用户登录，如果没今天的登录日志，则进行统计
        $is_user_login_cache =$this->redis_userlogin_prefix.$uid.'ok_'.$gid.'_'.date("Y-m-d",time());
        if (Comm_Redis::get($is_user_login_cache)) {
        }else{
            Comm_Redis::set($is_user_login_cache,1,60*60*24);
            $cache_login_count =$this->redis_count_prefix.$uinfo['type'].'login_'.$gid.'_'.date("Y-m-d",time());
            Comm_Redis::set($cache_login_count,intval(Comm_Redis::get($cache_login_count))+1,60*60*24);
        }

        //当此游戏未记录时,开始进行记录流程
        if (in_array($gid, $gamelist)) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '此用户已激活过此游戏了'));
        }
        // 追加新游戏
        $gamelist[] = $gid;
        $mygames = implode(',', $gamelist);

        // 更新回用户树中
        $UserFieldModel = new Business_User_FieldModel();
        $res1 =  $UserFieldModel ->update(array('mygames' => $mygames ),$uid);

        // 更新回缓存区中
        $uinfo['mygames'] = $mygames;
        Comm_Redis::set($this->redis_userinfo_prefix.$uid,json_encode($uinfo));

        // 更新 今天的新增 统计数据
        $cache_new_count =$this->redis_count_prefix.$uinfo['type'].'new_'.$gid.'_'.date("Y-m-d",time());
        Comm_Redis::set($cache_new_count,intval(Comm_Redis::get($cache_new_count))+1,60*60*24);


        // 检索用户上级的记录是否已创建,如果没有则进行创建
        $FundModel  = new Business_Fund_BonusModel();
        if (!empty($uinfo['up_layers_ids'])) {
            $UpUserlist = explode(',', $uinfo['up_layers_ids']);
            foreach ($UpUserlist as $v) {
            // 因为数量不固定，这里不再检测一次成功了
            // 后期需要评估提交失败的情况决定是否用回滚
                $FundModel->CreateBonus($v,$gid);
            // 更新积分
                $vinfo = array();
                $vinfo =$UserModel -> GetUserinfoByUid($v,2);
            //557  waiting 这里更新积分没区分游戏 否则需要多连查一表，配对mygames
                if ($vinfo['type'] == 4 ) {
                // 给上级代理商统计加1
                    if ($vinfo['agent_score'] >100 ) {
                        $UserModel -> UpAgentScore($v,1);
                    }

                //给代理商每日结算的缓存中的 今天的新用户统计数也加1，。此数据会在晚九点一并写入到数据库中
                    $CountCacheName = $this->redis_userinfo_prefix.'_Count'.date('Y-m-d',time()).'_'.$v;
                    $CountCache = empty(Comm_Redis::get($CountCacheName))?1:Comm_Redis::get($CountCacheName)+1;
                    Comm_Redis::set($CountCacheName,$CountCache,60*60*24);
                }
            }
        }

        if ($res1) {
            $this->jsonCommResponse(array('return_code' => 1,'message' => '激活成功'));
        }



    }

    public function TestActiveGamesAction()
    {
        $this->layout('index/TestActiveGames.html');
    }

    private function getGoldOnBlindMobile()
    {
        // 取金币数
        $getSystemParam = Comm_Tools::getSystemParam();
        return $getSystemParam['gold_on_blind_mobile'];

        // $CacheName_gold =$this->redis_system_prefix.'gold_on_blind_mobile';
        // if (Comm_Redis::get($CacheName_gold)) {
        //     return Comm_Redis::get($CacheName_gold);
        // }else{
        //     $SettingsModel = new Business_Setpeiz_setpeizModel();
        //     $golds = $SettingsModel->select_one(array('key' => 'gold_on_blind_mobile'));
        //     Comm_Redis::set($CacheName_gold,$golds['value']);
        //     return $golds['value'];
        // }
    }

    private function getGoldOnBlindinvide()
    {
        $getSystemParam = Comm_Tools::getSystemParam();
        return $getSystemParam['gold_on_blind_invide'];
        // $CacheName_gold =$this->redis_system_prefix.'gold_on_blind_invide';
        // if (Comm_Redis::get($CacheName_gold)) {
        //     return Comm_Redis::get($CacheName_gold);
        // }else{
        //     $SettingsModel = new Business_Setpeiz_setpeizModel();
        //     $golds = $SettingsModel->select_one(array('key' => 'gold_on_blind_invide'));
        //     Comm_Redis::set($CacheName_gold,$golds['value']);
        //     return $golds['value'];
        // }
    }

    public function createNoncestr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    //生成订单号
    public function pay_get_dingdan_code($dingdanzhui = '') {
        return $dingdanzhui . time() . substr(microtime(), 2, 6) . rand(0, 9);
    }

}
?>
