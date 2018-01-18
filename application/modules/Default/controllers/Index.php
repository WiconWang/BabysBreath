<?php
class IndexController extends Abstract_C {
    public function indexAction() {
        echo "Hello World!.";
        if (isset($_SERVER['SERVER_ENV']) &&  $_SERVER['SERVER_ENV']=='development'){
            echo " | This is development environment";
            // header("location:http://agent.doudeqipai.com/app/activity/start?openid=412731");
        }

    }


}
?>
