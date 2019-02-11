<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') OR exit('What are you doing?');

class spider_error {
    public static function log($msg,$url=null,$type=0,$a=null) {
        $data = array(
            'work'    => spider::$work,
            'rid'     => (int)spider::$rid,
            'sid'     => (int)spider::$sid,
            'pid'     => (int)spider::$pid,
            'url'     => ($url?$url:spider::$url),
            'msg'     => addslashes($msg),
            'date'    => date("Y-m-d H:i:s"),
            'addtime' => time(),
            'type'    => $type
        );
        $a && $data = array_merge($data,(array)$a);
        iDB::insert('spider_error',$data);
        if(iPHP_SHELL){
            echo $data['date']." \033[31m".$msg."\033[0m".PHP_EOL;
        }else{
            echo '<b>'.$msg.'</b><hr />';
        }
    }
}
