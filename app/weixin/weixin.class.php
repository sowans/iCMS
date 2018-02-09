<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/
define('iCMS_WEIXIN_COMPONENT',"https://weixin.icmsdev.com");//iCMS微信第三方平台

class weixin {
    public static $debug       = true;
    public static $component   = false;
    public static $accessToken = null;
    public static $config      = array();
    public static $API_URL     = 'https://api.weixin.qq.com/cgi-bin';

    protected static $appId          = null;
    protected static $appSecret      = null;
    protected static $accessTokenKey = 'weixin/token';

    public static function init($config=null){
        $config && self::$config = $config;
        if(self::$config){
            self::$appId     = self::$config['appid'];
            self::$appSecret = self::$config['appsecret'];
        }

        if(self::$component){
            self::$API_URL = iCMS_WEIXIN_COMPONENT.'/cgi-bin';
        }

        self::$accessTokenKey = 'weixin/token_'.substr(md5(self::$appId.self::$appSecret), 8,16);
        self::$accessToken===null && self::$accessToken = iCache::get(self::$accessTokenKey,null,0);
        self::$accessToken OR self::get_access_token();
    }
    public static function get_access_token(){
        $url = self::$API_URL.'/token?grant_type=client_credential'.
        '&appid='.self::$appId.
        '&secret='.self::$appSecret;
        $response = self::http($url);
        if($response->errcode){
            self::error($response,__METHOD__);
        }
        self::$accessToken = $response->access_token;
        iCache::set(self::$accessTokenKey,self::$accessToken,$response->expires_in);
    }
    public static function error($e,$method=''){
        //if(self::$debug){
            iUI::error("<span>errcode:".$e->errcode." errmsg:".$e->errmsg.' IN '.$method."</span>");
        //}
    }
    public static function url($uri,$query=null){
        $url = self::$API_URL.'/'.$uri.'?access_token='.self::$accessToken;
        self::$component && $url.= '&appid='.self::$appId;
        $query && $url.= '&'.http_build_query((array)$query);
        // self::$debug && var_dump($url);
        return $url;
    }
    public static function setMenu($param=null){
        $param===null && $param = weixin::$config['menu'];
        $param    = array('button'=>self::cn_urlencode($param));
        $param    = json_encode($param);
        $param    = urldecode($param);
        $url      = self::url('menu/create');
        $response = self::http($url,$param);
        // if($response->errcode){
        //     self::error($response,__METHOD__);
        // }
        return $response;
    }
    protected static function cn_urlencode($variable){
        foreach ((array)$variable as $i => $param) {
            foreach ((array)$param as $key => $value) {
                if($key=='name'){
                    $value = trim($value);
                    if(empty($value)){
                        unset($variable[$i]);
                        continue;
                    }

                    $variable[$i][$key] = urlencode(trim($value));
                }
                if($key=='sub_button'){
                    $variable[$i][$key] = self::cn_urlencode($value);
                }
            }
        }
        return $variable;
    }
    public static function getMenu(){
        $url      = self::url('menu/get');
        $response = self::http($url);
        // if($response->errcode=="46003"){
        //     return false;
        // }else if($response->errcode){
        //     self::error($response,__METHOD__);
        // }
        return $response;
    }
    /**
     * [mediaList 获取素材列表]
     * @param  integer $offset [从全部素材的该偏移位置开始返回，0表示从第一个素材 返回]
     * @param  integer $count  [返回素材的数量，取值在1到20之间]
     * @param  string  $type   [素材的类型，图片(image)、视频(video)、语音 (voice)、图文(news)]
     * @return [array]  [永久图文消息素材列表]
     */
    public static function mediaList($type='news',$offset=0,$count=20){
        $url   = self::url('material/batchget_material');
        $param = array(
            'type'   => $type,
            'offset' => $offset,
            'count'  => $count,
        );
        $cache_name = 'weixin/media_'.$type.'_list_'.$offset.'_'.$count;
        $post_data  = json_encode($param);
        $response   = iCache::get($cache_name);
        if(empty($response)){
            $response  = self::http($url,$post_data);
            iCache::set($cache_name,$response,300);
        }
// print_r($response);
        if($response->errcode){
            self::error($response,__METHOD__);
        }
        if($response->total_count){
            $media_list_array = array();
            $media_list_array['total_count'] = $response->total_count;
            $media_list_array['item_count']  = $response->item_count;
            $items = array();
            foreach ($response->item as $key => $value) {
                $items[$key]['media_id']    = $value->media_id;
                $items[$key]['name']        = $value->name;
                $items[$key]['url']         = $value->url;
                $items[$key]['update_time'] = $value->update_time;
                if(isset($value->content->news_item)){
                    $items[$key]['content'] = self::media_item($value->content->news_item);
                }
            }
            $media_list_array['items']  = $items;
            return $media_list_array;
        }
        return $response;
    }
    public static function media_item($itemArray){
        $items = array();
        if($itemArray)foreach ($itemArray as $k => $v) {
            $items[$k] = (array)$v;
        }
        return $items;
    }
    public static function qrcode_create($info) {
        $param =  array(
            'expire_seconds' => 2592000,
            'action_name'    => 'QR_LIMIT_STR_SCENE',
            'action_info'    => array('scene'=>
                array(
                    'scene_id'       => '1',
                    'scene_str'      => $info
                )
            )
        );
        $param    = json_encode($param);
        $param    = urldecode($param);
        $url      = self::url('qrcode/create');
        $response = self::http($url,$param);
        return $response;
    }

    public static function http($url, $POSTFIELDS=null,$raw=false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

        if($POSTFIELDS){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
        }

        $response = curl_exec ($ch);
        // self::$debug && var_dump($response);
        curl_close ($ch);

        if(empty($response)){
            return '-100000';
        }
        if($raw){
            return $response;
        }
        return json_decode($response);
    }
    public static function msg_xml($content,$FromUserName,$ToUserName){
        $CreateTime = time();
        echo "<xml>";
        echo "<ToUserName><![CDATA[".$FromUserName."]]></ToUserName>";
        echo "<FromUserName><![CDATA[".$ToUserName."]]></FromUserName>";
        echo "<CreateTime>".$CreateTime."</CreateTime>";
        if(is_array($content)){
            foreach ($content as $key => $value) {
                if($key=='Articles'){
                    echo "<MsgType><![CDATA[news]]></MsgType>";
                    echo "<ArticleCount>".count($value)."</ArticleCount>";
                    echo "<Articles>";
                    foreach ($value as $kk => $vv) {
                        echo "<item>";
                        foreach ($vv['item'] as $k => $v) {
                            echo "<{$k}><![CDATA[".$v."]]></{$k}>";
                        }
                        echo "</item>";
                    }
                    echo "</Articles>";
                }else{
                    echo "<MsgType><![CDATA[".strtolower($key)."]]></MsgType>";
                    if(is_array($value)){
                        echo "<{$key}>";
                        foreach ($value as $k => $v) {
                            echo "<{$k}><![CDATA[".$v."]]></{$k}>";
                        }
                        echo "</{$key}>";
                    }else{
                        echo "<Content><![CDATA[".$value."]]></Content>";
                    }
                }
            }
        }else{
            echo "<MsgType><![CDATA[text]]></MsgType>";
            echo "<Content><![CDATA[".$content."]]></Content>";
        }
        echo "</xml>";
        exit;
    }
    public static  function checkSignature(){
        // you must define TOKEN by yourself
        if (!self::$config['token']) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce     = $_GET["nonce"];

        $token  = self::$config['token'];
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    public static  function input($input=null,$json=false){
        $input===null && $input = file_get_contents("php://input");
        if ($input){
            if($json){
                $data = json_decode($input,true);
            }else{
                libxml_disable_entity_loader(true);
                $data = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
            }
            iSecurity::_addslashes($data);
            iWAF::check_data($data);
            return $data;
        }else{
            return false;
        }
    }
    public static function is_wxapp() {
        return (
            strpos($_SERVER['HTTP_REFERER'],'servicewechat.com') !== false
            &&(
                strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false
                ||strpos($_SERVER['HTTP_USER_AGENT'],'wechatdevtools') !== false
            )
        );
    }
}
