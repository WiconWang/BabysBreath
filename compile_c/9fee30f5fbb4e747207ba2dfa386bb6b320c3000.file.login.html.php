<?php /* Smarty version Smarty-3.1.14, created on 2018-01-19 17:13:53
         compiled from "/sites/GitHub/BabysBreath/application/modules/Admin/views/login/login.html" */ ?>
<?php /*%%SmartyHeaderCode:13659501855a61b6d1398525-52001440%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '9fee30f5fbb4e747207ba2dfa386bb6b320c3000' => 
    array (
      0 => '/sites/GitHub/BabysBreath/application/modules/Admin/views/login/login.html',
      1 => 1516257696,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '13659501855a61b6d1398525-52001440',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_5a61b6d1421963_84808792',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5a61b6d1421963_84808792')) {function content_5a61b6d1421963_84808792($_smarty_tpl) {?><!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>bb后台</title>
    <meta name="description" content="这是一个 index 页面">
    <meta name="keywords" content="index">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="renderer" content="webkit">
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <link rel="icon" type="image/png" href="<?php echo @constant('STATIC_PATH');?>
/assets/i/favicon.png">
    <link rel="apple-touch-icon-precomposed" href="<?php echo @constant('STATIC_PATH');?>
/assets/i/app-icon72x72@2x.png">
    <meta name="apple-mobile-web-app-title" content="Amaze UI" />
    <link rel="stylesheet" href="<?php echo @constant('STATIC_PATH');?>
/assets/css/amazeui.min.css" />
    <link rel="stylesheet" href="<?php echo @constant('STATIC_PATH');?>
/assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo @constant('STATIC_PATH');?>
/assets/css/app.css">
</head>

<body data-type="login">


  <div class="am-g myapp-login">
    <div class="myapp-login-logo-block  tpl-login-max">
        <div class="myapp-login-logo-text">
            <div class="myapp-login-logo-text">
                <?php if ($_SERVER['SERVER_ENV']=='development'){?>测试环境<?php }else{ ?>bb<?php }?> <i class="am-icon-skyatlas"></i> 管理平台

            </div>
        </div>

        <div class="login-font">
            <i>登录到</i><span>后台系统</span>
        </div>
        <div class="am-u-sm-10 login-am-center">
            <form class="am-form">
                <fieldset>
                    <div class="am-form-group">
                        <input type="text" class="username" id="doc-ipt-email-1" placeholder="输入用户名">
                    </div>
                    <div class="am-form-group">
                        <input type="password" class="password" id="doc-ipt-pwd-1" placeholder="设置个密码吧">
                    </div>
                    <p><button type="button" id="submit" class="am-btn am-btn-default">登录</button></p>
                </fieldset>
            </form>
        </div>
    </div>
</div>
    <script src="<?php echo @constant('STATIC_PATH');?>
/assets/js/jquery.min.js"></script>
    <script>
        $(document).ready(function(){
            $("#submit").click(function(){
                $.ajax({
                        url: "/admin/login/login" ,
                        type: "post",
                        dataType: "json",
                        data:{username:$('.username').val(),password:$('.password').val(),},
                        async: false,
                        success: function(json){
                            // console.log(json);
                            if (json.status == 1) {
                                window.location.href='/admin/admin/admin';
                            }else{
                            alert(json.msg);
                        }
                        },
                        error: function(){
                            console.log( "AJAX fail");
                        }
                       });
            });
        });
    </script>
</body>

</html>
<?php }} ?>