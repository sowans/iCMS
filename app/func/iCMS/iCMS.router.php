<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/
function iCMS_router($vars){
	if(empty($vars['url'])){
		echo 'javascript:;';
		return;
	}
	$key = $vars['key'];
	$router = $vars['url'];
	unset($vars['url'],$vars['app'],$vars['key']);
	$url = iURL::router($router);
	$vars['query'] && $url = iURL::make($vars['query'],$url);

	if($url && !iFS::checkHttp($url) && $vars['host']){
		$url = rtrim(iCMS_URL,'/').'/'.ltrim($url, '/');;
	}
	empty($url) && $url = 'javascript:;';
	if($key){
		return iView::assign($key,$url);
	}
	echo $url;
}
