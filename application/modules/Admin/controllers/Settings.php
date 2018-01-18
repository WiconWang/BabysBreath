<?php
/**
 * 557
 * 后台设置
 * @author WiconWang@gmail.com
 * @copyright 2017-06-14 18:37:35
 */
class SettingsController extends Abstract_C {
    protected $id = 0;
    protected $LayoutData = array();

    public function init()
    {
        // 初始化此用户的ID
        $this->id = Yaf_Session::getInstance()->__get( "MANAGE_ID");
        // if (!$this->uid) {die('已开启登录验证，请先登录');}
        $this->LayoutData['crumb'] = array(
            'home' => array('text' => '首页','url' => '/admin', ),
            'category' => array('text' => '系统设置','url' => '', ),
            'page' => array('text' => '','url' => '', ),
            );
    }

    /**
     * 用户资料检索页面
     */
    public function LevelAction()
    {

        $config = Comm_Config::getConfig('config');
        $post = $this->_postParams();
        if (!empty($post)) {
            if (!empty($post['level'])) {
                $config['AgentLevel'] = $post['level'];
                $path = 'conf/config.php';
                $myfile = fopen($path, "w") or die("无法写入配置文件请检查");
                $txt = '<?php
                $config = '.var_export($config, TRUE).';
                return $config;';
                $v = fwrite($myfile, $txt);
                fclose($myfile);
                Comm_Log::Log($_SESSION['MANAGE_NAME'],0, 2, '修改代理商等级,内容：'.json_encode($post));
                header("Location:/admin/settings/Level");
                exit;
            }
        }

        $this->LayoutData['config'] = $config;
        $where = array();
        $this->LayoutData['crumb']['category']['url'] = '/admin/settings/level';
        $this->LayoutData['crumb']['page']['text'] = '代理商级别设置';
        $this->layout('settings/level.html',$this->LayoutData);
    }

    public function WelfareAction()
    {

        $config = Comm_Config::getConfig('config');
        $post = $this->_postParams();
        if (!empty($post)) {
            $config['AgentBigLevel'] = array_filter($post['AgentBigLevel']);
            $config['AgentPrecent'] = array_filter($post['AgentPrecent']);
            $config['TaskAutoGame'] = array_filter($post['TaskAutoGame']);
            $config['TaskAutoShare'] = array_filter($post['TaskAutoShare']);
            $config['MonthTicketGame'] = array_filter($post['MonthTicketGame']);
            $config['MonthTicketTask'] = array_filter($post['MonthTicketTask']);
            $config['MonthTicketDraw'] = array_filter($post['MonthTicketDraw']);

            $path = 'conf/config.php';
            $myfile = fopen($path, "w") or die("无法写入配置文件请检查");
            $txt = '<?php
            $config = '.var_export($config, TRUE).';
            return $config;';
            $v = fwrite($myfile, $txt);
            fclose($myfile);
            Comm_Log::Log($_SESSION['MANAGE_NAME'],0, 2, '修改代理商福利,内容：'.json_encode($post));

            header("Location:/admin/settings/welfare");
            exit;
        }

        $this->LayoutData['config'] = $config;
        $where = array();
        $this->LayoutData['crumb']['category']['url'] = '/admin/settings/welfare';
        $this->LayoutData['crumb']['page']['text'] = '代理商福利设置';
        $this->layout('settings/welfare.html',$this->LayoutData);
    }

}
