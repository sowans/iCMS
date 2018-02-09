<?php
/**
 * iPHP - i PHP Framework
 * Copyright (c) 2012 iiiphp.com. All rights reserved.
 *
 * @author coolmoo <iiiphp@qq.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.0.0
 */
/**
 * 多终端适配
 * @param  [type] &$config [系统配置]
 * @return [type]          [description]
 */
class iDevice {
    public static $config   = null;
    public static function init(&$config) {

        define('iPHP_REQUEST_SCHEME',($_SERVER['SERVER_PORT'] == 443)?'https':'http');
        define('iPHP_REQUEST_HOST',iPHP_REQUEST_SCHEME.'://'.($_SERVER['HTTP_X_HTTP_HOST']?$_SERVER['HTTP_X_HTTP_HOST']:$_SERVER['HTTP_HOST']));
        define('iPHP_REQUEST_URI',$_SERVER['REQUEST_URI']);
        define('iPHP_REQUEST_URL',iPHP_REQUEST_HOST.iPHP_REQUEST_URI);

        self::$config = $config['template'];
        //有设置其它设备
        if(self::$config['device']){
            iPHP::PG('device')&& $device = self::check('device');   //判断指定设备
            empty($device)    && $device = self::check('ua');       //无指定设备 判断USER_AGENT
            empty($device)    && $device = self::check('domain');   //无指定USER_AGENT  判断域名模板
            $device && list($device_name, $device_tpl,$domain,$index_tpl) = $device;
        }

        iPHP::$mobile = false;
        if (empty($device_tpl)) {
            //检查是否移动设备 USER_AGENT 或者 域名
            $is_m_domain = (
                self::$config['mobile']['domain']==iPHP_REQUEST_HOST &&
                self::$config['mobile']['domain']!=$config['router']['url']
            );
            if (self::agent(self::$config['mobile']['agent'])||$is_m_domain) {
                iPHP::$mobile = true;
                $device_name = 'mobile';
                $device_tpl  = self::$config['mobile']['tpl'];
                $index_tpl   = self::$config['mobile']['index'];
                $domain      = self::$config['mobile']['domain'];
            }
        }

        if (empty($device_tpl)) {
            $device_name = 'desktop';
            $device_tpl  = self::$config['desktop']['tpl'];
            $index_tpl   = self::$config['desktop']['index'];
            $domain      = $config['router']['url'];
        }
        empty($index_tpl) && $index_tpl = $device_tpl.'/index.htm';

        define('iPHP_DEFAULT_TPL', $device_tpl);
        define('iPHP_INDEX_TPL', $index_tpl);
        // define('iPHP_DEVICE_TPL', $device_tpl);
        define('iPHP_ROUTER_URL', $config['router']['url']);
        define('iPHP_DEVICE', $device_name);
        define('iPHP_DOMAIN', $domain);
        define('iPHP_MOBILE', iPHP::$mobile);

        iPHP_DOMAIN == iPHP_ROUTER_URL OR self::router($config['router']);
        iPHP_DOMAIN == iPHP_ROUTER_URL OR self::router($config['FS']);
        $config['router']['redirect'] && self::redirect();
    }
    public static function router(&$router,$deep=false) {
        $router = is_array($router) && $deep ?
                array_map(array('iDevice','router'), $router) :
                str_replace(iPHP_ROUTER_URL, iPHP_DOMAIN, $router);

        return $router;
    }
    //所有设备网址
    public static function urls($array) {
        $array = (array)$array;
        $urls = array();
        if($array){
            $iurl = array(
                'url' => $array['href']
            );
            $array['pageurl'] && $iurl['pageurl'] = $array['pageurl'];

            if(self::$config['desktop']['domain']){
                $urls['desktop'] = str_replace(iPHP_DOMAIN, self::$config['desktop']['domain'], $iurl);
            }
            if(self::$config['mobile']['domain']){
                $urls['mobile'] = str_replace(iPHP_DOMAIN, self::$config['mobile']['domain'], $iurl);
            }
            if(self::$config['device'])foreach (self::$config['device'] as $key => $value) {
                if($value['domain']){
                    $name = trim($value['name']);
                    $urls[$name] = str_replace(iPHP_DOMAIN, $value['domain'], $iurl);
                }
            }
        }
        return $urls;
    }

    private static function redirect(){
        if(stripos(iPHP_REQUEST_URL, iPHP_DOMAIN) === false && !iPHP_SHELL){
            $redirect_url = str_replace(iPHP_REQUEST_HOST,iPHP_DOMAIN, iPHP_REQUEST_URL);
            header("Expires:1 January, 1970 00:00:01 GMT");
            header("Cache-Control: no-cache");
            header("Pragma: no-cache");
            header("X-REDIRECT-REF: ".iPHP_REQUEST_URL);
            header("X-iPHP-DOMAIN: ".iPHP_DOMAIN);
            header("X-REDIRECT-URL: ".$redirect_url);
            iPHP::http_status(301);
            iPHP::redirect($redirect_url);
        }
    }
    private static function check($flag = false) {
        foreach ((array) self::$config['device'] as $key => $device) {
            if ($device['tpl']) {
                $check = false;
                if ($flag == 'ua') {
                    $device['ua'] && $check = self::agent($device['ua']);
                } elseif ($flag == 'device') {
                    $_device = iPHP::PG('device');
                    if ($device['ua'] == $_device || $device['name'] == $_device) {
                        $check = true;
                    }
                } elseif ($flag == 'domain') {
                    if ($device['domain']==iPHP_REQUEST_HOST && empty($device['ua'])) {
                        $check = true;
                    }
                }
                if ($check) {
                    return array($device['name'], $device['tpl'], $device['domain'], $device['index']);
                }
            }
        }
    }
    private static function agent($user_agent) {
        $user_agent = str_replace(',','|',preg_quote($user_agent,'/'));
        return ($user_agent && preg_match('@'.$user_agent.'@i',$_SERVER["HTTP_USER_AGENT"]));
    }
}
