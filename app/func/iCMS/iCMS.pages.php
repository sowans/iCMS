<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/
function iCMS_pages($vars){
	$c = iPages::$config;
	if(isset($vars['url'])){
		$c['url'] = $vars['url'];
		if(strtolower($vars['url'])==='self'){
			$c['url'] = $_SERVER['REQUEST_URI'];
		}
	}
	$query = array('page'=>'{P}');
	$vars['query'] && $query = array_merge($query,$vars['query']);

	iPages::$setting['index'] = iURL::make(array('page'=>null),$c['url']);
	$c['url'] = iURL::make($query,$c['url']);

	return iPagination::assign($c);
}
