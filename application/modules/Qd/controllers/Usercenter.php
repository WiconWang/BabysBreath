<?php
/**
 * 557
 * 用户后台模块
 * @author WiconWang@gmail.com
 * @copyright 2017-05-09 16:59:25
 *
 *
 */
class UserCenterController extends Abstract_C {

    public function init()
    {
        // 初始化此用户的ID
        $this->uid = Yaf_Session::getInstance()->__get( "ADM_LOGIN_ID");
//        $this->uid = 35;
        // if (!$this->uid) {die('已开启登录验证，请先登录');}

    }

    public function indexAction()
    {
        $UserModel = new Business_User_AccountModel();
        // 根据UID去取用户详细资料 带用户树的最大记录
        $userinfo =$UserModel -> GetUserinfoByUid($this->uid,2,1);
        $config = Comm_Config::getConfig('config');
        $this_level_score = $config['AgentLevel'][$userinfo['agent_level']];
        $next_level_score = $config['AgentLevel'][strval($userinfo['agent_level']+1)];
        $userinfo['agent_process'] =intval(($userinfo['agent_score']-$this_level_score)/($next_level_score-$this_level_score)*100);
        $userinfo['myvcode'] = Comm_Tools::UidToInvite($userinfo['uid']);
        $userinfo['visa'] =''; // 暂时无银联卡渠道

        if (empty($userinfo['headimgurl'])) {
            $userinfo['headimgurl'] = "/uploads/gl.jpg";
        }elseif (substr($userinfo['headimgurl'],0, 4) != 'http') {
            $userinfo['headimgurl'] = '/'.$userinfo['headimgurl'];
        }else{

        }

        if ($userinfo['type'] == 4) {
            $stars = $userinfo['agent_level']%5;
            $userinfo['agent_big_level'] =$config['AgentBigLevel'][floor(($userinfo['agent_level']-1)/5)+1];
            $userinfo['agent_small_level'] = empty($stars)?5:$stars;
            if ($userinfo['agent_level'] >30) {$userinfo['agent_big_level']=$config['AgentBigLevel']["7"];$userinfo['agent_small_level']=5;        }

            $this->layout('usercenter/home.html',array('info' => $userinfo));
        }else{

            $LevelModel = new Business_Game_LevelModel();
            $gamelevel = $LevelModel->GetLevelByMoney($userinfo['all_cost']);

            $this->layout('usercenter/home_notagent.html',array('info' => $userinfo,'gamelevel' => $gamelevel));
        }

    }

    /**
     * 取用户的游戏资料
     * @return [type] [description]
     */
    public function gameAction()
    {
        $data = array();

        //代理商游戏部分人数实时显示
        $data['today_player'] = 0;
        $today_users = Comm_Tools::Cache_Count_Users(date('Ymd',time()));
        if ($today_users && isset($today_users['ids'][$this->uid])) {
            $data['today_player'] = $today_users['ids'][$this->uid];
        }

        $UserModel = new Business_User_AccountModel();
        $GameModel = new Business_GameInfo_GamesModel();
        $BonusModel = new Business_Fund_BonusModel();

        $data['user'] =$UserModel -> GetUserinfoByUid($this->uid,2);

                // 取今日人数缓存，如果还没有结算，则把缓存的数量也加上
        $data['today_player'] = 0;
        $SystemParam = Comm_Tools::getSystemParam();
        if ($SystemParam['settlestamp'] > (time() - strtotime(date('Y-m-d'.' 00:00:00',time())))) {
            $today_users = Comm_Tools::Cache_Count_Users(date('Ymd',time()));
            if ($today_users && isset($today_users['ids'][$this->uid])) {
                $data['today_player'] = $today_users['ids'][$this->uid];
            }
            $data['user']['myplayer_num'] += $data['today_player'];
        }



        $Bonusinfo =$BonusModel -> Info(array('uid' => $this->uid),$this->uid);
        $data['Bonus'] = $Bonusinfo;

        // 每日结算
        // $data['settle'] = array();
        // for ($i=0; $i < 7; $i++) {
        //     $data['settle'][$i] = $BonusModel -> GetSettlement($this->uid,1);

        // }
        //
        $orign_settle= $BonusModel -> GetSettlement($this->uid,7);
        $settle =  array_reverse($orign_settle) ;
        $data['settle'] = array();
        if (empty($settle)) {
            $data['settle']['daycount'] = '0, 0, 0, 0, 0, 0, 0';
            $data['settle']['daymoney'] = '0, 0, 0, 0, 0, 0, 0';
            $data['settle']['date'] =  '\''.date('md',strtotime('-6 day')).'\',\''.date('md',strtotime('-5 day')).'\',\''.date('md',strtotime('-4 day')).'\',\''.date('md',strtotime('-3 day')).'\',\''.date('md',strtotime('-2 day')).'\',\''.date('md',strtotime('-1 day')).'\',\''.date('md',time()).'\'';
        }else{
            $data['settle']['daycount'] = '';
            $data['settle']['daymoney'] = '';
            $data['settle']['date'] = '';
            foreach ($settle as $k => $v) {
                $data['settle']['daycount'] .= $v['today_player'].',';
                $data['settle']['daymoney'] .= $v['today_money'].',';
                $data['settle']['date'] .= '\''.$v['tday'].'\',';
            }
            $data['settle']['daycount'] = rtrim($data['settle']['daycount'], ",");
            $data['settle']['daymoney'] = rtrim($data['settle']['daymoney'], ",");
            $data['settle']['date'] = rtrim($data['settle']['date'], ",");

        }

        $gid = $this->get('gid')?intval($this->get('gid')):0;
        if ($gid) {
            $data['ThisGame'] = $GameModel -> InfoById($gid);
        }else{
            $data['ThisGame'] = Comm_Tools::getDefaultGame();
            $gid = $data['ThisGame']['gid'];
        }


        foreach ($Bonusinfo  as $k => $v) {
            if ($v['is_buy'] == 1) {
                if (empty($gid)) {$gid = $v['gid'];}
            }
            if ($v['gid'] == $gid) {
                $data['ThisBonus'] = $v;
            }
        }
        if (empty($data['ThisBonus'])) {
            $data['ThisBonus'] =array(
                'uid' => $this->uid,
                'gid' => $gid,
                'is_buy' => 0,
                'pool' => 0,
                'vip_pool' => 0,
                'count_down_users' => 0,
                'count_down_money' => '0.00',
                'count_down_divide' => '0.00',
                'create_time' => '0',
                'agent_time' => '0',
                'count_share' => 0,
            );
        }


        // 统计今天新增金额，注意要要合并多游戏
        $data['today_money'] = $data['today_divide'] =  $data['today_money_num'] = 0;
        $today_money = Comm_Tools::Cache_Count_Money(date('Ymd',time()));
        foreach ($today_money as $k => $v) {
            if ($k != 'new') {
                $data['today_money'] += $v['downline'][$this->uid];
                $data['today_divide'] += $v['money'][$this->uid];
                $data['today_money_num'] += $v['count'][$this->uid];
            }
        }
        $data['today_divide'] = floor($data['today_divide']*100)/100;
        $data['today_player'] = 0;
        $today_users = Comm_Tools::Cache_Count_Users(date('Ymd',time()));
        if ($today_users && isset($today_users['ids'][$this->uid])) {
            $data['today_player'] = $today_users['ids'][$this->uid];
        }

        $data['draw'] = (new Business_Fund_WithdrawAuditModel()) -> SumOrginMoney(array('uid' => $this->uid));
        if (empty($data['draw'])) {$data['draw'] = 0;}

        $this->layout('usercenter/game.html',$data);
    }


    /**
     * 后微信后台跳转到支付页面
     * @return [type] [description]
     */
    public function BuyAgentOnlineAction()
    {
        $gid = $this->post('gid')?$this->post('gid'):0;
        $payway = $this->post('payway')?$this->post('payway'):'';
        $sid = $this->post('sid')?$this->post('sid'):0;
        $total_fee = empty($this->post('total_fee'))?0.01:$this->post('total_fee');

        if ($payway == 'wxpay') {
            header("Location:/default/pay/pay/?source=weixin&gid=".$gid."&sid=".$sid."&total_fee=".$total_fee);
            exit;
        }
    }



    /**
     * 取用户的统计资料
     * @return [type] [description]
     */
    public function countAction()
    {
        $data = array();
        $UserModel = new Business_User_AccountModel();
        // 根据UID去取用户详细资料 带用户树的最大记录
        $userinfo=$UserModel -> GetUserinfoByUid($this->uid,2);
        $config = Comm_Config::getConfig('config');

        // 代理商取详细情况
        if ($userinfo['type'] == 4) {
            $this_level_score = $config['AgentLevel'][$userinfo['agent_level']];
            $next_level_score = $config['AgentLevel'][strval($userinfo['agent_level']+1)];
            $userinfo['agent_process'] =intval(($userinfo['agent_score']-$this_level_score)/($next_level_score-$this_level_score)*100);
            $stars = $userinfo['agent_level']%5;
            $userinfo['agent_big_level'] =$config['AgentBigLevel'][floor(($userinfo['agent_level']-1)/5)+1];
            $userinfo['agent_small_level'] = empty($stars)?5:$stars;
            if ($userinfo['agent_level'] >30) {
                $userinfo['agent_big_level']=$config['AgentBigLevel']["7"];
                $userinfo['agent_small_level']=5;
            }

            $TaskModel = new Business_Task_ProcessModel();
            $data['level'] = $TaskModel->GetAgentLevelInfo($userinfo['agent_level']);
            $data['level']['next']['title'] = $config['AgentBigLevel'][floor(($userinfo['agent_level']-1)/5)+2];
        }
        $data['info'] = $userinfo;

        $BonusModel = new Business_Fund_BonusModel();
        $data['Bonus'] = $BonusModel->CountDownline($this->uid);
        $data['Bonus']['days'] = round((time()-strtotime($data['Bonus']['agent_time']))/3600/24);

        // 取今日人数缓存，如果还没有结算，则把缓存的数量也加上
        $data['today_player'] = 0;
        $SystemParam = Comm_Tools::getSystemParam();
        if ($SystemParam['settlestamp'] > (time() - strtotime(date('Y-m-d'.' 00:00:00',time())))) {
            $today_users = Comm_Tools::Cache_Count_Users(date('Ymd',time()));
            if ($today_users && isset($today_users['ids'][$this->uid])) {
                $data['today_player'] = $today_users['ids'][$this->uid];
            }
            $data['info']['myplayer_num'] += $data['today_player'];
        }
        if ($this->uid ==33) {
            $data['tempmoney'] = '60.07元';
        }else{
            $data['tempmoney'] = '待结算';
        }
        if ($data['info']['type'] == 4) {
            $this->layout('usercenter/count.html',$data);
        }else{
            $this->layout('usercenter/count_notagent.html',$data);
        }
    }


    /**
     * 删除用户
     * @return [type] [description]
     */
    public function destory1Action()
    {
        $data = array();
        $BonusModel = new Business_Fund_BonusModel();
        $data['count'] = $BonusModel -> CountDownline($this->uid);
        // 取昨天的进账数量

        $SettleModel = new Business_Fund_BonusModel();
        $Settle = $SettleModel -> GetSettlement($this->uid,1);
        if (!isset($Settle[0]) || empty($Settle[0])) {
            $data['yesmoney'] = 0;
        }else{
            $data['yesmoney'] = $Settle[0]['money'];
        }
        $this->layout('usercenter/destroy_step1.html',$data);
    }

    /**
     * 删除用户
     * @return [type] [description]
     */
    public function destory2Action()
    {
        $pool = 0;
        $UserModel = new Business_User_AccountModel();
        $userinfo =$UserModel -> GetUserinfoByUid($this->uid,2,1);
        $userinfo['wallet'] = floor($userinfo['wallet'] * 100)/100;
        $userpoolModel = new Business_Fund_BonusModel();
        $poolarr = $userpoolModel->InfoByUid($this->uid);
        if (!empty($poolarr)) {
            foreach ($poolarr as $k => $v) {
                $pool += $v['pool'];
            }

        }
        $usermoney = $userinfo['wallet']+$pool;

        $this->layout('usercenter/destroy_step2.html',array('uinfo' => $userinfo,'allmoney' => $usermoney));
    }

    public function questionAction()
    {
        $this->layout('usercenter/question.html');
    }



    private function object2array($object) {
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


    public function userAvaterAction()
    {
        $upload = new Business_Upload_uploadModel();
        $ass = $this->object2array(json_decode($upload->index($_FILES)));

        if($ass['success'] == 1){
            $path = $this->object2array($ass['files'][0]);
            $param['pic'] = "http://".$_SERVER['SERVER_NAME']."/".$path['path'];
            $UserModel = new Business_User_InfoModel();
            $res=$UserModel ->Update(array('headimgurl' =>  $param['pic']),$this->uid);
            if ($res) {
                echo 1;
				//上传成功
            }else{
                echo 0;
				//上传失败
            }
        }else{
            echo 3;
			//系统故障
        }
    }

}
?>
