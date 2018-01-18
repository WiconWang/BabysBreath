<?php
$config = array(
    //定义此项目用到的模块与域名资源对应关系
    'domain' =>
        array(
            'default' =>['www.demo.com','demo.com'],
            'qd' =>['qd.demo.com','clear.demo.com'],
            'admin' =>['admin.demo.com'],
        ),

    //定义每个模块默认的首页
    'default' =>
        array(
            'default' =>
                array(
                    'modules' => 'default',
                    'controller' => 'Index',
                    'action' => 'Index',
                ),
        ),
    'qd' =>
        array(
            'default' =>
                array(
                    'modules' => 'Qd',
                    'controller' => 'Index',
                    'action' => 'Index',
                ),
        ),
    'admin' =>
        array(
            'default' =>
                array(
                    'modules' => 'Admin',
                    'controller' => 'Login',
                    'action' => 'login',
                ),
        ),
    //定义不需要认证的模块
    'nologin' =>
        array(
            'qd' =>[ 'index/index','index/signin', 'index/signup', 'index/signout'],
            'admin' =>[ 'login/login','login/outin'],
        ),
    //SMTP邮件服务器
    //注意此处为了防止群发邮件封号，此处user是个数组,而且这些用户名的密码应该相同。
    //程序会随机从其中选择一个用户名做为此次的发件方
    'smtp' => array(
            'server' => 'smtp.sina.com',
            'port' => '25',
            'user' => array('a1@sina.com','a2@sina.com','a3@sina.com'),
            'pass' => 'demo#.',
        ),
);
return $config;