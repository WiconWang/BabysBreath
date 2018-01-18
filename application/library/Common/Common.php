<?php

/**
 * 公共函数库
 */
function debug() {
    $debugAll = debug_backtrace();
    //$debug = debug_backtrace();
    $debug = array_shift($debugAll);
    $paramNum = func_num_args();
    $flag = null;
    $show = false;
    echo "<hr><---调用位置：" . $debug['file'] . "第" . $debug['line'] . "行<br>
                       <---共传入<u style='color:red'>【" . $paramNum . "】</u>个参数";
    echo '<pre>';
    foreach (func_get_args() as $k => $v) {
        $num = $k + 1;
        echo "<---第<u style='color:red'>(" . $num . ")</u>个参数的值：<br/>";
        if ($v === 'exit') {
            $flag = 1;
        } elseif ($v === 'show') {
            $show = true;
        }
        var_dump($v);
    }
    if ($show) {
        foreach ($debugAll as $v1) {
            echo $v1['file'] . '第' . $v1['line'] . '行:' . $v1['class'] . $v1['type'] . $v1['function'] . PHP_EOL;
        }
    }
    echo '</pre>';
    echo "<---调用p函数结束---><hr>";
    if ($flag == 1) {
        exit();
    }
}
