<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/

class favorite {
    public static function check($iid,$uid,$appid){
        $id  = iDB::value("
            SELECT `id` FROM `#iCMS@__favorite_data`
            WHERE `uid`='$uid'
            AND `iid`='$iid'
            AND `appid`='$appid'
            LIMIT 1
        ");
        return $id?true:false;
    }
    public static function update_count($id=0,$field='count',$math='+',$count='1'){
        $math=='-' && $sql = " AND `{$field}`>0";
        iDB::query("
            UPDATE `#iCMS@__favorite`
            SET `{$field}` = {$field}{$math}{$count}
            WHERE `id`='{$id}' {$sql}
        ");
    }
}
