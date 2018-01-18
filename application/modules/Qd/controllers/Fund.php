<?php
/**
 * 557
 * 用户资金相关模块
 * @author WiconWang@gmail.com
 * @copyright 2017-04-26 11:38:11
 *
 * 技术要点
 * 资金池的资金是分游戏的，需要完成任务才可以提现到钱包。
 * 资金池位于 dd_bonus_pool  的pool字段 。如果要总额度 需要做sum得出总资金池数量
 * 钱包位于dd_user_field 的wallet字段。允许提现转入
 */
class FundController extends Abstract_C {
    protected $uid = 0;

    public function init()
    {

        // 初始化缓存的前缀表
        $prefix = Comm_Config::getConf('config.'.DEVELOPMENT.'.prefix');
        $this->redis_guid_prefix =  $prefix['guid'];
        $this->redis_mobile_prefix =  $prefix['mobile'];
        $this->redis_userinfo_prefix =  $prefix['userinfo'];
        $this->redis_userfund_prefix =  $prefix['userfund'];
        $this->redis_count_prefix =  $prefix['count'];

        // 初始化此用户的ID
        $this->uid = Yaf_Session::getInstance()->__get( "ADM_LOGIN_ID");
        // if (!$this->uid) {die('已开启登录验证，请先登录');}
    }



    /**
     * 用户默认页面
     * @return layout
     */
    public function indexAction() {
        exit;
        echo "代理商资金相关模块，以下说明暂时P
        <br>传入GET参数 numbers，可以得到小数点五位数
        ";;
        echo "<pre>";
        echo "<hr>";
        if (!empty($_GET['numbers'])) {
            echo  '小数：'.Comm_Tools::FormatFloor($_GET['numbers']);
            echo "<hr>";
        }
        if (!empty($_GET['uid'])) {
            echo  '邀请码：'.Comm_Tools::UidToInvite($_GET['uid']);
            echo "<hr>";
            echo  '对外ID：'.Comm_Tools::EncryptUID($_GET['uid']);
            echo "<hr>";
            $UserModel = new Business_User_AccountModel();
            $result = $UserModel -> GetUserinfoByUid($_GET['uid']);
            print_r($result);
        }
        if (!empty($_GET['hexuid'])) {
            echo  'UID：'.Comm_Tools::InviteToUid($_GET['hexuid']);
        }

        echo "<hr>";
        echo "</pre>";
        print <<<EOT
        本模块相关的测试页面有：<br>
        用户购买： <a href="/user/fund/buyWeb">http://agent.doudeqipai.com/user/fund/buy</a> 此接口为JSON，需要提供uid gid sid num 另外代理权编号定为10000后期需要调整<br>
        用户充值： <a href="/user/fund/Recharge">http://agent.doudeqipai.com/user/fund/Recharge</a> 此接口为JSON，需要提供uid price<br>
        用户提现： <a href="/user/fund/Withdraw">http://agent.doudeqipai.com/user/fund/Withdraw</a> 此接口为JSON，需要提供 openid price<br>

EOT;

        exit;
    }


    public function buyWebAction()
    {
        $this->layout('fund/buy.html');
    }

    public function buyAgentWebAction()
    {
        $this->layout('fund/buyagent.html');
    }

        // UPDATE `dd_bonus_pool` SET `count_down_users` = `count_down_users` + 1  WHERE `id` in ('1','2','3','4','5');
    /**
     * 用户购买商品逻辑
     * @param int $uid 买东西用户的uid如果没有则为本用户
     * @param int $gid 所属于的游戏号
     * @param int $sid 商品的编号
     * @param int $num 数量，对于代理 权来说强制为1
     * @return Json
     */
    public function buyAction()
    {
        // $uid = $this->uid;
        $openid = intval($this->post('openid'));
        if (empty($openid)) {
            $uid = $this->post('uid')?intval($this->post('uid')):$this->uid;
        }else{
            $uid= Comm_Tools::DecryptUID($openid);
        }
        $gid = intval($this->post('gid'));
        $sid = intval($this->post('sid'));
        $num = $this->post('num')?intval($this->post('num')):1;
        if (empty($uid) || empty($num) || $uid<0) {
            $this->jsonResponse(0,'参数缺少');
        }


        $Model = new Business_Fund_BonusModel;

            // 取得此游戏的分成比例 注意这个比例是千分位的
        $GameModel = new Business_GameInfo_GamesModel;
        $gameinfo= $GameModel->InfoById($gid);
        if (!$gameinfo['divide_list'] || !$gameinfo['price']) {
            $this->jsonResponse(0,'暂时无法购买，此游戏无法进行分成');
        }

        // 是否是代理权
        if ($sid == 10000) {

            // 如果这个商品是代理权的话
            $AgentRowId = $Model->Info(array('uid' =>$uid ,'gid' => $gid),$uid);
            if (isset($AgentRowId[0]['is_buy']) && $AgentRowId[0]['is_buy'] ==1) {
                $this->jsonResponse(0,'代理权只能购买一次');
            }

            //检测用户是否已经输入过了上线id
            $UserModel = new Business_User_AccountModel();
            $user_up = $UserModel -> GetUserinfoByUid($uid,2);
            if (!isset($user_up['up_layers_ids']) || empty($user_up['up_layers_ids'])) {
                $this->jsonResponse(0,'您没有绑定过邀请码');
            }
            //此游戏代理权价格为38
            $price = $gameinfo['price'];
            $result = $Model->BuyAgent($uid,$gid,$sid,$price);
            if ($result['error']) {
                $this->jsonResponse(0,$result['msg']);
            }else{


            //添加交易缓存统计
            $new_charge_num   = $this->redis_count_prefix.'new_charge_num_'.$gid.'_'.date("Y-m-d",time())  ;
            $all_charge_num   = $this->redis_count_prefix.'all_charge_num_'.$gid.'_'.date("Y-m-d",time())  ;
            Comm_Redis::set($new_charge_num,intval(Comm_Redis::get($new_charge_num))+1,60*60*24);
            Comm_Redis::set($all_charge_num,intval(Comm_Redis::get($all_charge_num))+1,60*60*24);
            $new_charge_money = $this->redis_count_prefix.'new_charge_money_'.$gid.'_'.date("Y-m-d",time());
            $all_charge_money = $this->redis_count_prefix.'all_charge_money_'.$gid.'_'.date("Y-m-d",time());
            Comm_Redis::set($new_charge_money,intval(Comm_Redis::get($new_charge_money))+$price,60*60*24);
            Comm_Redis::set($all_charge_money,intval(Comm_Redis::get($all_charge_money))+$price,60*60*24);

                $this->jsonResponse(1,'代理权已购买成功');
            }
        }else{
            $game_divide = explode('/', $gameinfo['divide_list']);

            //取得此物品的信息 注意这个表的model名字叫gamemodel
            $GoodsModel = new Business_Game_gameModel();
            $Good_info = $GoodsModel -> select_one(array('sid' => $sid ));
            if (!$Good_info['goods_price']) {
                $this->jsonResponse(0,'暂时无法购买，此商品价格不正确');
            }
            $price = $Good_info['goods_price'];



            // 购买流程, 写核心表逻辑
            $res = $Model->Buy($uid,$gid,$sid,$price,$num);
            if ($res['error']) {
                $this->jsonResponse(0,$res['msg']);
            }

            //给人分红
            $this->DivideAgentByUid($uid,$gid,$game_divide,$gameinfo['floor'],$price);
            //取得购买人上级列表,如果有上级则进行分红
            // 这里请注意，用户表中。距离用户最近的端是最右，最远的是最左一个
            // $UserModel = new Business_User_AccountModel();
            // $user_up = $UserModel -> GetUserinfoByUid($uid,2);
            // if ($user_up['up_layers_ids'] && !empty($user_up['up_layers_ids'])) {
            //     $layer_users = array_reverse(explode(',', $user_up['up_layers_ids']));
            //     // 把用户进行循环，逐个进行分红
            //     foreach ($layer_users as $k => $vid) {
            //         $vid_money =Comm_Tools::FormatFloor($game_divide[$k]*$price/1000);
            //         // 分红记录太多，不检测成功与否
            //         $Model->DivideStream($vid,$gid,$uid,$vid_money,$price,'商品购买');
            //     }
            // }


            // 购买成功后，如果此用户为代理商，进行等级提升
            if ($user_up['type'] == 4 ) {
                $UserModel -> UpAgentScore($uid,$num*$price);
            }


            //添加交易缓存统计
            $new_charge_num   = $this->redis_count_prefix.'new_charge_num_'.$gid.'_'.date("Y-m-d",time())  ;
            $all_charge_num   = $this->redis_count_prefix.'all_charge_num_'.$gid.'_'.date("Y-m-d",time())  ;
            Comm_Redis::set($new_charge_num,intval(Comm_Redis::get($new_charge_num))+1,60*60*24);
            Comm_Redis::set($all_charge_num,intval(Comm_Redis::get($all_charge_num))+1,60*60*24);
            $new_charge_money = $this->redis_count_prefix.'new_charge_money_'.$gid.'_'.date("Y-m-d",time());
            $all_charge_money = $this->redis_count_prefix.'all_charge_money_'.$gid.'_'.date("Y-m-d",time());
            Comm_Redis::set($new_charge_money,intval(Comm_Redis::get($new_charge_money))+$num*$price,60*60*24);
            Comm_Redis::set($all_charge_money,intval(Comm_Redis::get($all_charge_money))+$num*$price,60*60*24);



            $this->jsonResponse(1,'商品购买成功');
        }
    }

    // public function printmethod($method = 'json',)
    // {
    //     # code...
    // }

    // 用商品交易ID来购买后续流程
    public function  buysidAction()
    {
        $code = htmlentities($this->get('code'), ENT_QUOTES);
        if (empty($code)) {
            echo "<script>alert('订单号错误');history.back(-1);</script>";
            exit;
        }
        $i = $isok =  0;
        $tradeModel = new Business_Pay_payModel();

        // 进行三次检测，如果没有检测到支付则延迟0.5秒处理，以保证支付宝异步请求完成
        while ( $i <= 3) {
            $res = $tradeModel->select_one(array('code' => $code));
            if ($res['status'] == '已支付') {
                $isok =1;
                break;
            }
            usleep(500000);
            $i++;
        }


        if (DEVELOPMENT!='development'){
            if ($isok != 1) {
                echo "<script>alert('订单号 ".$code." 购买失败');history.back(-1);</script>";
                exit;
            }
        }
        if ($res['mission'] == 1) {
            echo "<script>alert('此订单已购买完成');history.back(-1);</script>";
            exit;
        }


        $uid= $res['uid'];
        $openid = Comm_Tools::EncryptUID($uid);
        $gid = $res['gid'];
        $sid = $res['sid'];
        $price = $res['price'];
        $num = 1;



        $Model = new Business_Fund_BonusModel;

        //当测试环境时。开启支付1分实际充值100元
        if (DEVELOPMENT=='development'){
            $price = $price * 100;
            if ($price <1) {$price = 1;}
        }
        //先为用户充值
        $res = $Model -> Recharge($uid,$price,$code);

        // 取得此游戏的分成比例 注意这个比例是千分位的
        $GameModel = new Business_GameInfo_GamesModel;
        $gameinfo= $GameModel->InfoById($gid);
        if (!$gameinfo['divide_list'] || !$gameinfo['price']) {
            echo "<script>alert('无法购买，此游戏无法进行分成');history.back(-1);</script>";
            exit;
            // $this->jsonResponse(0,'暂时无法购买，此游戏无法进行分成');
        }
        $game_divide = explode('/', $gameinfo['divide_list']);


            // 购买流程, 写核心表逻辑
        $res = $Model->Buy($uid,$gid,$sid,$price,$num,$code);
        if ($res['error']) {
            // $this->jsonResponse(0,$res['msg']);
            echo "<script>alert('错误 ".$res['msg']."');history.back(-1);</script>";
            exit;
        }

        // 标记订单表记录为已完成
        $tradeModel->update(array('mission' => 1), array('code' => $code));


            //给人分红
        $this->DivideAgentByUid($uid,$gid,$game_divide,$gameinfo['floor'],$price);

            //取得购买人上级列表,如果有上级则进行分红
            // 这里请注意，用户表中。距离用户最近的端是最右，最远的是最左一个
        $UserModel = new Business_User_AccountModel();
        $user_up = $UserModel -> GetUserinfoByUid($uid,2);
        // if ($user_up['up_layers_ids'] && !empty($user_up['up_layers_ids'])) {
        //     $layer_users = array_reverse(explode(',', $user_up['up_layers_ids']));
        //         // 把用户进行循环，逐个进行分红
        //     foreach ($layer_users as $k => $vid) {
        //         $vid_money =Comm_Tools::FormatFloor($game_divide[$k]*$price/1000);
        //             // 分红记录太多，不检测成功与否
        //         if ($vid_money >0) {
        //             $Model->DivideStream($vid,$gid,$uid,$vid_money,$price,'商品购买');
        //         }
        //     }
        // }


        // 购买成功后，如果此用户为代理商，进行等级提升
        if ($user_up['type'] == 4 ) {
            $UserModel -> UpAgentScore($uid,$num*intval($price));
        }
        $ccjs = "ccjs://updateGold";

        // 以下为赢牌送现金模块
        // 此模块的现金。在代理商购买时。会把已有的现金记录合并入资金池
        // 以后再次购买时。会同步更新奖金池和送现金模块
        // 如果停用 此模块。请在此模块文件顶部，把 ACTIVES更新为0
        $GameLevelModel = new Business_Game_LevelModel();
        //用户是否有升级
        $Cashnum = $GameLevelModel->GetDIFbyMoney($user_up['all_cost']- ($price*$num),$user_up['all_cost']);
        if (!empty($Cashnum['ticket_num'])) {
            $CashModel = new Business_Fund_GameCashTicketModel();
            $Cash_result = $CashModel->InsertRow($uid,$gid, '用户从VIP'.$Cashnum['level_from'].'升级到VIP'.$Cashnum['level_to'],$Cashnum['ticket_num']);
            //如果模块没有停用，进行模块逻辑
            if (isset($Cash_result['error']) && $Cash_result['error'] == 0) {
                $extend_money = $Cash_result['money'];
            }
        }
        // 赢牌送现金模块 END
        // 进入杀分流程
        $thisPrice = $num*intval($price);
        //向游戏服务器发送数据时，转换为游戏等级
        $newLevel = Comm_Tools::UserTypeToGameLevel($user_up['type'],$user_up['all_cost']);
        // $newLevel = $GameLevelModel->GetLevelByMoney($user_up['all_cost']);
        $SFres = $this->ShaFen($openid,$thisPrice,$newLevel );


        if ($SFres && $SFres['return_code'] == 0) {
            $ccjs = "ccjs://BuySuccess?".$SFres['msg'];
        }
        //杀分结束

        // 充值结束，清除救济计时
        $getBenefitsModel = new Business_Game_BenefitsModel();
        $Ymd = date('Ymd',time());
        @$getBenefitsModel -> ResetBenefits($uid,$Ymd);

        // $this->jsonResponse(1,'商品购买成功');
        // echo "<script>alert('商品购买成功');window.location.href='ccjs://updateGold';setTimeout(\"window.location.href='/app/shop/shop?openid=".$openid."&gid=".$gid."';\", 500 );</script>";
        echo "<script>alert('购买成功');window.location.href='".$ccjs."';setTimeout(\"window.location.href='/api/play.php';\", 500 );</script>";

        // echo "<script>alert('商品购买成功');window.location.href='ccjs://updateGold';setTimeout(\"window.location.href='ccjs://close';\", 500 );</script>";


        exit;
    }

        public function ShaFen($openid,$price,$gameLevel)
        {


// QPTreasureDB  ScoreControll
//
// UserID   QPaccountsDB AccountsInfo

// KindID 616
// UserType 10充值3， 11充值6，12充值30。此级别只增不减
// GoodCount  剩余好牌局数
// FreeCount   不杀局数

            $postFields = array(
                'openid' => $openid,
                'KindID' => 616,
                'UserType' => 0,
                'GoodCount' => 0,
                'FreeCount' => 0,
                'gameLevel' => $gameLevel,
            );
// 充值3元 7局不杀

            if ($price <=3) {
                $postFields['UserType'] = 10;
                $postFields['FreeCount'] = 7;
            }
    // 充值6元
            if ($price >3 && $price <=30) {
                $postFields['UserType'] = 11;
                $postFields['GoodCount'] = 2;
                $postFields['FreeCount'] = 10;
            }
            if ($price >30) {
                $postFields['UserType'] = 12;
                $postFields['GoodCount'] = 5;
                $postFields['FreeCount'] = 30;
            }
            $resr = @Comm_Tools::curl('http://'.$_SERVER['SERVER_NAME'].'/api/game_platform/buySuccess.php',  'POST', $postFields,null,1 );
            if (!empty($resr)) {
                $re = json_decode($resr,true);
                if ($re['return_code'] == 1) {
                    return array('return_code' => 1, 'msg' => $resr);
                    exit;
                }
            }
            return array('return_code' => 0, 'msg' => $gameLevel);
        }



    // 用商品交易ID来购买代理权
    public function buysidagentAction()
    {
        $code = htmlentities($this->get('code'), ENT_QUOTES);
        if (empty($code)) {
            echo "<script>alert('订单号错误');history.back(-1);</script>";
            exit;
        }
        $i = $isok =  0;
        $tradeModel = new Business_Pay_payModel();

        // 进行三次检测，如果没有检测到支付则延迟0.5秒处理，以保证支付宝异步请求完成
        while ( $i <= 3) {
            $res = $tradeModel->select_one(array('code' => $code));
            if ($res['status'] == '已支付') {
                $isok =1;
                break;
            }
            usleep(500000);
            $i++;
        }

        if ($isok != 1) {
            echo "<script>alert('订单号 ".$code." 购买失败');history.back(-1);</script>";
            exit;
        }
        if ($res['mission'] == 1) {
            echo "<script>alert('购买完成');history.back(-1);</script>";
            exit;
        }


        $uid= $res['uid'];
        $openid = Comm_Tools::EncryptUID($uid);
        $gid = $res['gid'];
        $sid = $res['sid'];
        $price = $res['price'];
        $num = 1;




        $Model = new Business_Fund_BonusModel;

        //当测试环境时。开启支付1分实际充值100倍
        if (DEVELOPMENT=='development'){
            $price = $price * 100;
        }
        //先为用户充值
        $res = $Model -> Recharge($uid,$price,$code);

        if ($res['error']) {
            $this->jsonResponse(0,$res['msg']);
        }

        // 取得此游戏的分成比例 注意这个比例是千分位的
        $GameModel = new Business_GameInfo_GamesModel;
        $gameinfo= $GameModel->InfoById($gid);
        if (!$gameinfo['divide_list'] || !$gameinfo['price']) {
            echo "<script>alert('无法购买，此游戏无法进行分成');history.back(-1);</script>";
            exit;
            // $this->jsonResponse(0,'暂时无法购买，此游戏无法进行分成');
        }
        $game_divide = explode('/', $gameinfo['divide_list']);

         // 如果这个商品是代理权的话
        $AgentRowId = $Model->Info(array('uid' =>$uid ,'gid' => $gid),$uid);
        if (isset($AgentRowId[0]['is_buy']) && $AgentRowId[0]['is_buy'] ==1) {
            $this->jsonResponse(0,'代理权只能购买一次');
        }

            //检测用户是否已经输入过了上线id
        $UserModel = new Business_User_AccountModel();
        $user_up = $UserModel -> GetUserinfoByUid($uid,2);
        if (!isset($user_up['up_layers_ids']) || empty($user_up['up_layers_ids'])) {
            $this->jsonResponse(0,'您没有绑定过邀请码');
        }


        $result = $Model->BuyAgent($uid,$gid,$sid,$price,0,$code);

        if ($res['error']) {
            // $this->jsonResponse(0,$res['msg']);
            echo "<script>alert('错误 ".$res['msg']."');history.back(-1);</script>";
            exit;
        }

        // 标记订单表记录为已完成
        $tradeModel->update(array('mission' => 1), array('code' => $code));


            //给人分红
        $this->DivideAgentByUid($uid,$gid,$game_divide,$gameinfo['floor'],$price);


        // 用户购买代理权。赠送50万金币
        $GameGoldModel = new Business_User_GameGoldModel();
        $GameGoldModel->SendGold(0,$uid,$gid,500000,2,0 ,'用户自行购买代理权，获赠50万金币');

        $callback = $this->get('callback')?$this->get('callback'):"/app/shop/shop?openid=".$openid."&gid=".$gid;


        $ccjs = "ccjs://updateGold";

               // 进入杀分流程
        $thisPrice = $num*intval($price);
        $SFres = $this->ShaFen($openid,$thisPrice,112);


        if ($SFres && $SFres['return_code'] == 0) {
            $ccjs = "ccjs://BuySuccess?".$SFres['msg'];
        }
        //杀分结束
        //
        //
        // $this->jsonResponse(1,'商品购买成功');
        echo "<script>alert('商品购买成功');window.location.href='".$ccjs."';setTimeout(\"window.location.href='".$callback."';\", 500 );</script>";

        exit;
    }



    /**
 * 用户充值
 * @param integer $uid   用户id
 * @param integer $price 金额
 */
    public function RechargeAction()
    {
        $uid = intval($this->post('uid'));
        if (empty($uid)) {
            $openid = intval($this->post('openid'));
            $uid = Comm_Tools::DecryptUID($openid);
        }
        $price = intval($this->post('price'));

        if (empty($uid) || $price<=0 || $price >2000) {
            $this->jsonResponse(0,'用户充值错误，请检查充值金额和用户ID，最多充值限额为2000');
        }


        //当测试环境时。开启支付1分实际充值100元
        if (DEVELOPMENT=='development'){
            $price = $price * 100;
        }

        $Model = new Business_Fund_BonusModel;
        $res =$Model -> Recharge($uid,$price);

        if ($res['error']) {
            $this->jsonResponse(0,'充值失败');
        } else {
            $this->jsonResponse(1,'充值成功');
        }
    }

    public function RechargeWebAction()
    {

        $this->layout('fund/recharge.html',array('uid' => $this->uid));
    }

    /**
 * 用户取现
 * @param integer $uid   用户id
 * @param integer $price 金额
 */
    public function WithdrawAction()
    {
        $openid = intval($this->post('openid'));
        $price = intval($this->post('price'));

        $uid = Comm_Tools::DecryptUID($openid);
        if (empty($uid)) {
            $this->jsonResponse(0,'用户取现失败，请检查用户信息');
        }
        if ($price<=0 || $price >2000) {
            $this->jsonResponse(0,'取现金额不正确，每笔限额2000元');
        }

        $Model = new Business_Fund_BonusModel;
        $res =$Model -> Withdraw($uid,$price);

        if ($res['error']) {
            $this->jsonResponse(0,'取现失败');
        } else {
            $this->jsonResponse(1,'取现成功，正在等待审核');
        }

    }


    /**
     * 查看用户    如果ticket有值则为给指定用户发放用户券
     * @param  integer $id [description]
     * @param  integer $ticket [description]
     * @return [type]          [description]
     */
    public function ticketAction()
    {
        $uid = intval($this->get('uid'));

        //557 waiting 暂时关添加ticket
        // $ticket = intval($this->get('ticket'));

        $Model = new Business_User_TicketModel();
        if (!empty($ticket)) {
            $data = array(
            'uid' => $uid,
            'type_id' => $ticket,
            'end_time' => date('Y-m-d H:i:s', strtotime('+1 month')),
            'validity' => 1
            );
            $res = $Model->Save($data,$uid);
            if ($res['error']) {
                $this->jsonResponse(0,'添加券失败');
            } else {
                $this->jsonResponse(1,'添加券成功');
            }
        }

        $res = $Model -> Ticket($uid);
        if (empty($res['count'])) {
            $this->jsonResponse(0,'无数据');
        } else {
            $this->jsonResponse(1,'成功',$res);
        }
    }


    /**
     * 列表
     * @return [type] [description]
     */
    public function ticketlistAction()
    {

        $UserModel = new Business_User_AccountModel();
        // 根据UID去取用户详细资料 带用户树的最大记录
        $userinfo =$UserModel -> GetUserinfoByUid($this->uid);

        // 当为手机用户时。采用游戏分级模块
        if ($userinfo['type'] == 3) {
            $LevelModel = new Business_Fund_GameCashTicketModel();
            $data['cashticket'] = $LevelModel->CountUnuseRow($this->uid,3);
            $this->layout('fund/ticket_notagent.html',$data);
            exit;
        }

        $data = array();
        $Model = new Business_User_TicketModel();
        $ticket = $Model->Ticket($this->uid);
        $tickettype = $Model->TicketType();
        $res_type = $res_tick = array();
        foreach ($tickettype as $k => $v) {
            $res_type[$v['id']]=$v['ticket_name'];
        }
        if (!empty($ticket['count'])) {
            foreach ($ticket['count'] as $key => $value) {
                $res_tick[$key] = array(
                'id' => $key,
                'name' => $res_type[$key],
                'num' => $value,
                );
            }
        }
        $data['ticket'] = $res_tick;


        $this->layout('fund/ticket.html',$data);
    }

    /**
     * 取用户的游戏资料
     * @return [type] [description]
     */
    public function moneyAction()
    {
        $UserModel = new Business_User_AccountModel();
        // 根据UID去取用户详细资料 带用户树的最大记录
        $data['user'] =$UserModel -> GetUserinfoByUid($this->uid,2,1);
        // $data['user']['wallet']  =  sprintf('%.2f', floor($data['user']['wallet']*100)/100);;
        $data['user']['wallet']  =  sprintf('%.2f', $data['user']['wallet']);
        $SettleModel = new Business_Fund_BonusModel();
        $Settle = $SettleModel -> GetSettlement($this->uid,1);
        if (!isset($Settle[0]) || empty($Settle[0])) {
            $data['yesmoney'] = 0;
        }else{
            $data['yesmoney'] = $Settle[0]['money'];
        }
        $data['ticket'] = 0;
        $Model = new Business_User_TicketModel();
        $res_ticket = $Model -> Ticket($this->uid);
        if (isset($res_ticket['count'][3])) {
            $data['ticket'] = $res_ticket['count'][3];
        }
        //当为手机用户时。采用游戏分级模块
        if ($data['user']['type'] == 3) {
            $LevelModel = new Business_Game_LevelModel();
            $data['gamelevel'] = $LevelModel->GetLevelByMoney($data['user']['all_cost']);
        }
        //取最后一笔提现。查看是否有异常
        $last = (new Business_Tix_tixModel())->find(array('uid'=>$this->uid));
        $data['withdraw_error'] = 0;
        if ($last && isset($last[0]) && $last[0]['mark'] != ''){
            $data['withdraw_error'] = 1;
        }
        $this->layout('fund/money.html',$data);
    }


    /**
     * 列表
     * @return [type] [description]
     */
    public function MoneyStreamAction($Ym = '')
    {
        if (empty($Ym)) {  $Ym=date('Ym',time());}
        $data = array();
        $Model = new Business_Fund_AssetStreamModel();
        $stream = $Model->InfoByUid($this->uid,date('Ym',time()));
        if (!empty($stream)) {

            foreach ($stream as $k => $v) {
                if (in_array($v['channel'], array(3,7,8,9,10))) {
                    //对于清零和每日结算来说，前台不显示
                    unset($stream[$k]);
                    continue;
                }
                $stream[$k]['money'] = round($v['money'],2);
                $stream[$k]['price'] = round($v['price'],2);
                $stream[$k]['orgin_price'] = round($v['orgin_price'],2);
                $stream[$k]['time'] = date('m-d',strtotime($v['create_time']));
                switch ($stream[$k]['channel']) {
                        // case 7: $stream[$k]['way'] ='分红汇总';  break;
                        case 6: $stream[$k]['way'] ='晚报';  break;
                        case 5: $stream[$k]['way'] ='清零';  break;
                        case 4: $stream[$k]['way'] ='充值';  break;
                        // case 3: $stream[$k]['way'] ='获得';  break;
                        case 2: $stream[$k]['way'] ='提现';  break;
                        case 1: $stream[$k]['way'] ='消费';  break;
                    default: $stream[$k]['way'] ='';break;
                }

                if ($stream[$k]['channel'] == 2) {
                   if ($stream[$k]['status'] == 0) {$stream[$k]['statuse'] = '审核中';}
                   if ($stream[$k]['status'] == 1) {$stream[$k]['statuse'] = '已完成';}
                   if ($stream[$k]['status'] == 2) {$stream[$k]['statuse'] = '已拒绝';}
                }

            }
        }
        $data['stream'] = $stream;
        $this->layout('fund/moneystream.html',$data);
    }

    /**
     * 取指定人的信息，并向他的上级进行分红
     * @param [type] $uid         指定人
     * @param [type] $gid         指定游戏
     * @param [type] $game_divide 分级情况
     * @param [type] $game_layer  分级层数
     * @param [type] $price       指定人支付的金额
     */
    private function DivideAgentByUid($uid,$gid,$game_divide,$game_layer,$price)
    {
        //取得特别价格的分红
        // 以下为赢牌送现金模块   如斗地主游戏，有玩游戏送现金的活动。因此，代理权58，实际按38进行分成
        // 如果停用 此模块。请在此模块文件顶部，把 ACTIVES更新为0，并注释掉 exit
        $CashModel = new Business_Fund_WinCashModel();
        $result = $CashModel->GetDividePriceOnCashWin($uid,$gid,$price);
        if (isset($result['error']) && $result['error'] == 0) {
             $price = $result['price'];
        }


        //取得购买人上级列表,如果有上级则进行分红
        // 这里请注意，用户表中。距离用户最近的端是最右，最远的是最左一个
        $UserModel = new Business_User_AccountModel();
        $Model = new Business_Fund_BonusModel();
        $user_up = $UserModel -> GetUserinfoByUid($uid,2);
        if (isset($user_up['up_layers_ids']) && !empty($user_up['up_layers_ids'])) {
            $layer_users = array_reverse(explode(',', $user_up['up_layers_ids']));
                // 把用户进行循环，逐个进行分红
            foreach ($layer_users as $k => $vid) {
                // 只分红到指定层数
                if ($k<$game_layer) {
                    $vid_money =Comm_Tools::FormatFloor($game_divide[$k]*$price/1000);
                    // 分红记录太多，不检测成功与否
                    if ($vid_money >0) {
                        $Model->DivideStream($vid,$gid,$uid,$vid_money,$price,'商品购买');
                    }
                }
            }
        }

    }

}
?>
