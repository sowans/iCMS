<?php
@set_time_limit(0);
defined('iPHP') OR require (dirname(__FILE__).'/../../../iCMS.php');

return patch::upgrade(function(){
  $members = iDB::all("SELECT * FROM `#iCMS@__members` order by uid DESC");
  foreach ($members as $key => $value) {
      membersAdmincp::clone_touser($value['uid'],$value);
  }
  $msg.='完成在用户表创建管理员克隆账号<iCMS>';
  return $msg;
});

