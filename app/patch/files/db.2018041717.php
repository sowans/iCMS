<?php
@set_time_limit(0);
defined('iPHP') OR require (dirname(__FILE__).'/../../../iCMS.php');

return patch::upgrade(function(){
  iDB::query("
  ALTER TABLE `#iCMS@__user`
    DROP INDEX `username`,
    DROP INDEX `email`,
    ADD  KEY `username` (`username`);
  ");
  return '更新用户表索引';
});

