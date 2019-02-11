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

class spider_project {
    public static function get($id) {
        $project = iDB::row("SELECT * FROM `#iCMS@__spider_project` WHERE `id`='$id' LIMIT 1;", ARRAY_A);
        if($project['config']){
			$project['config']  = (array)json_decode($project['config'],true);
			$project+= $project['config'];
        }
        return $project;
    }
}
