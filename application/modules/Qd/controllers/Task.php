<?php
/**
 * 557
 * 用户资金相关模块
 * @author WiconWang@gmail.com
 * @copyright 2017-04-26 11:38:11
 *
 *
 */
class TaskController extends Abstract_C {
    protected $uid = 0;

    public function init()
    {

        // 初始化缓存的前缀表
        $prefix = Comm_Config::getConf('config.'.DEVELOPMENT.'.prefix');
        $this->redis_userTask_prefix =  $prefix['usertask'];

        // 初始化此用户的ID
        // $this->uid = 349;
        $this->uid = Yaf_Session::getInstance()->__get( "ADM_LOGIN_ID");
        // if (!$this->uid) {die('已开启登录验证，请先登录');}
    }

    /**
     * 加载初始任务页面
     * @return [type] [description]
     */
    public function indexAction()
    {
        // 计算时间如果已经超过了今天的结算时间，则把记录完成量算到明天上
        $nighttime = strtotime(date('Y-m-d',time()).' 22:00:00');
        if (time() > $nighttime) {
            $Ymd= date('Y-m-d',time()+4*60*60);
        }else{
            $Ymd= date('Y-m-d',time());
        }


        // $Ymd = date('Y-m-d',time());
        $data = array();
        //取广告模块
        $advModel = new Business_Advert_advertModel();
        $data['adv'] = $advModel->Info(array());

        //取任务模块
        $TaskProcessModel = new Business_Task_ProcessModel();
        $task_pct = $TaskProcessModel->GetTaskCategory();
        $data['Process'] = $TaskProcessModel->getTaskProcess($this->uid,0,$Ymd);

        //取一下当前任务下一级的完成标准
        foreach ($data['Process']['rows'] as $k => $v) {
            foreach ($task_pct[$k] as $m => $n) {
                if ($v['next_pct'] == $n) {
                    $data['Process']['rows'][$k]['next_limit'] =  $m;
                }
            }
        }

        if (!isset($data['Process']['showcount'])) {
            echo "系统故障，无法取得任务记录";exit;
        }
        $next_count = 0;
        $next_count = ceil(intval($data['Process']['showcount'])/10)*10;
        $next_count =$next_count + 5;
        if ($data['Process']['showcount'] == 0) {
         $data['Process_text'] = "请完成以下任务获取奖励!";
     }elseif ($data['Process']['showcount'] >72) {
         $data['Process_text'] = "恭喜你已经完成今天全部任务";
     }elseif ($data['Process']['showcount'] > 30) {
         $data['Process_text'] = "再接再厉，向" . $next_count ."%出发";
     }else{
         $data['Process_text'] = "你真棒！下一站" . $next_count ."%！";
     }

        // 以下为赢牌送现金模块
        // 此模块的现金。在代理商购买时。会把已有的现金记录合并入资金池
        // 以后再次购买时。会同步更新奖金池和送现金模块
        // 如果停用 此模块。请在此模块文件顶部，把 ACTIVES更新为0
        //
        // $CashModel = new Business_Fund_WinCashModel();
        // $Cash_result = $CashModel->GetCashToPool($uid,$gid);
        $CashModel = new Business_Fund_GameCashTicketModel();
        @$Cash_result = $CashModel->SettlementToPool($this->uid,3);
        // 赢牌送现金模块 END

        //用户资料
     $UserinfoModel = new Business_User_AccountModel();
     $data['userinfo'] =$UserinfoModel->GetUserinfoByUid($this->uid);


        // 取资金模块
     $BonusModel = new Business_Fund_BonusModel();
     $data['bonus'] =$BonusModel->CountDownline($this->uid);


        // 取用户券模块
     $TicketModel = new Business_User_TicketModel();
     $data['ticket'] =$TicketModel->Ticket($this->uid);
        // 取用户已使用一键的次数
     $data['autonum'] = $TaskProcessModel->getCompleteAutoNum($this->uid);


     $data['autogameprice'] = Comm_Tools::getTaskPriceByLevel($data['userinfo']['agent_level'],2,$data['bonus']['money']);;
     $data['autoshareprice'] = Comm_Tools::getTaskPriceByLevel($data['userinfo']['agent_level'],1,$data['bonus']['money']);;

        // 检索微信是否已经达到1 小时 时间限制，如果已到时间限制解锁页面
        $data['Process']['rows'][5]['lock'] = 0;
        if ($data['Process']['rows'][5]['update_time'] != '' ) {
            $unlocktime=strtotime($data['Process']['rows'][5]['update_time']) + 60*60*1;
            if ($unlocktime > time()) {
                $data['Process']['rows'][5]['lock'] = $unlocktime;
            }
        }
        $data['Process']['rows'][6]['lock'] = 0;
        if ($data['Process']['rows'][6]['update_time'] != '' ) {
            $unlocktime=strtotime($data['Process']['rows'][6]['update_time']) + 60*60*1;
            if ($unlocktime > time()) {
                $data['Process']['rows'][6]['lock'] = $unlocktime;
            }
        }
                $data['Process']['rows'][7]['lock'] = 0;
        if ($data['Process']['rows'][7]['update_time'] != '' ) {
            $unlocktime=strtotime($data['Process']['rows'][7]['update_time']) + 60*60*1;
            if ($unlocktime > time()) {
                $data['Process']['rows'][7]['lock'] = $unlocktime;
            }
        }
        $data['Process']['rows'][8]['lock'] = 0;
        if ($data['Process']['rows'][8]['update_time'] != '' ) {
            $unlocktime=strtotime($data['Process']['rows'][8]['update_time']) + 60*60*1;
            if ($unlocktime > time()) {
                $data['Process']['rows'][8]['lock'] = $unlocktime;
            }
        }


     $this->layout('task/index.html',$data);
 }

    /**
     * 加载初始任务页面
     * @return [type] [description]
     */
    public function TestCompleteTaskAction()
    {
        $data['uid'] = $this->uid;
        $this->layout('task/TestCompleteTask.html',$data);
    }


    /**
     * 完成任务模块
     * @param int $uid 用户号
     * @param int $taskid 任务号
     */
    public function CompleteTaskAction()
    {
        $openid = intval($this->post('openid'));
        if (empty($openid)) {
            $uid = intval($this->post('uid'));
        }else{
            $uid= Comm_Tools::DecryptUID(intval($openid));
        }
        if ($uid <=0) {
            $this->jsonResponse(0,'openid不正确');
        }


        $taskid = intval($this->post('taskid'));
        if (empty($uid) || empty($taskid)) {
            $this->jsonResponse(0,'请检查参数');
        }

        $Model = new Business_Task_ProcessModel();

        $res = $Model->CompleteTask($uid,$taskid);

        if ($res['error']) {
            $this->jsonResponse(0,'更新失败');
        } else {
            $this->jsonResponse(1,'更新成功');
        }
    }


    /**
     * 使用一键批量完成工具
     */
    public function CompleteTaskByTicketAndMoneyAction()
    {
        $taskid = intval($this->get('task'));
        $way = intval($this->get('way'));
        $TicketModel = new Business_Task_ProcessModel();
        $Ticket_Category = 1;
        $res =$TicketModel->CompleteTaskByTicketAndMoney($this->uid,$taskid,$way);
        if ($res['error']) {
            $this->jsonResponse(0,'更新失败');
        } else {
            $this->jsonResponse(1,'更新成功');
        }


    }



    /**
     * 暂停使用
     * 是否可以使用一键完成
     * @param int $uid 用户号
     * @param int $taskid 任务号
     */
    public function CompleteTaskAutoAllowAction()
    {
        $uid = intval($this->post('uid'));
        $taskid = intval($this->post('taskid'));
        if (empty($uid) || empty($taskid)) {
            $this->jsonResponse(0,'请检查参数');
        }

        $Model = new Business_Task_ProcessModel();
        $res = $Model->CompleteTaskAuto($uid,$taskid,0,1);

        if ($res['error']) {
            $this->jsonResponse(0,$res['msg']);
        } else {
            $this->jsonResponse(1,'可以使用',$res['data']);
        }
    }

    /**暂停使用
     * 一键完成任务模块
     * @param int $uid 用户号
     * @param int $taskid 任务号
     */
    public function CompleteTaskAutoAction()
    {
        $uid = intval($this->post('uid'));
        $taskid = intval($this->post('taskid'));
        if (empty($uid) || empty($taskid)) {
            $this->jsonResponse(0,'请检查参数');
        }

        $Model = new Business_Task_ProcessModel();
        $res = $Model->CompleteTaskAuto($uid,$taskid);

        if ($res['error']) {
            $this->jsonResponse(0,$res['msg']);
        } else {
            $this->jsonResponse(1,'成功');
        }
    }

    /**
     * 任务流水
     * @return [type] [description]
     */
    public function TaskStreamAction()
    {
        //用户资料
     $UserinfoModel = new Business_User_AccountModel();
     $data['userinfo'] =$UserinfoModel->GetUserinfoByUid($this->uid);
        $Model = new Business_Fund_BonusModel();
        $data['list'] = $Model->GetSettlement($this->uid);
        $this->layout('task/taskstream.html',$data);
    }

    /**
     * App任务内页
     */

    public function AppTaskAction($value='')
    {
        # code...
    }


}
?>
