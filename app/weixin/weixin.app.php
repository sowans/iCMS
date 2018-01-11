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

if(iPHP_DEBUG){
    $input = file_get_contents("php://input");
    weixinApp::DEBUG($_SERVER['REQUEST_URI'],'input');
    weixinApp::DEBUG($input,'input');
}

class weixinApp {
    public $methods = array('interface','wxapp');
    public $FromUserName = null;
    public $ToUserName   = null;
    public $encrypt_type = null;
    public $XML = null;
    public function __construct($config=null) {
        $config===null && $config = iCMS::$config['weixin'];
        weixin::$config = $config;
    }
    public function API_wxapp(){
        $method = $_GET['method'];
        if (method_exists("weixin_wxapp", $method)) {
            weixin_wxapp::init(weixin::$config['wxapp']);
            weixin_wxapp::$method();
        }
    }
    public function API_interface(){
        if(iPHP_DEBUG){
            ob_start();
            iDB::$show_errors = true;
        }
        if(empty(weixin::$config['token'])){
            trigger_error('TOKEN is empty',E_USER_ERROR);
        }

        if ($_GET["api_token"]!=weixin::$config['token']) {
            trigger_error('TOKEN is error!',E_USER_ERROR);
        }

        if($_GET["echostr"] && !$_GET['msg_signature']){
            if(weixin::checkSignature()){
                echo $_GET["echostr"];
                exit;
            }
        }
        $signature     = $_GET["signature"];
        $timestamp     = $_GET["timestamp"];
        $nonce         = $_GET["nonce"];
        $openid        = $_GET["openid"];
        $encrypt_type  = $_GET["encrypt_type"];
        $msg_signature = $_GET["msg_signature"];

        $input = null;
        if($encrypt_type=="aes"){
            weixin_crypt::$token     = weixin::$config['token'];
            weixin_crypt::$aeskey    = weixin::$config['AESKey'];
            weixin_crypt::$appId     = weixin::$config['appid'];
            weixin_crypt::$timeStamp = $timestamp;
            weixin_crypt::$nonce     = $nonce;
            $errCode = weixin_crypt::decrypt($msg_signature, $input);
            if ($errCode == 0) {
            } else {
                exit($errCode . "\n");
            }
        }
        $this->XML = weixin::input($input);
        if (is_object($this->XML)){
            $msgType = $this->XML->MsgType;
            $event   = $this->XML->Event;
            $this->api_log();

            //接收信息类型
            if($msgType=="event"){//事件
                iPHP::callback(array($this,'event_'.strtolower($event)),array($this->XML));
            }else{
                iPHP::callback(array($this,'msg_'.strtolower($msgType)),array($this->XML));
            }
            self::DEBUG();
            //查找空白事件
            $this->get_event('null','keyword','eq',true);
            //默认回复
            $this->send('对不起，没找到相关内容[1]');
        }
        self::DEBUG();
    }
    public function event_scancode_push($xml){
    }
    public function event_scancode_waitmsg($xml){
    }
    public function event_pic_sysphoto($xml){
    }
    public function event_pic_photo_or_album($xml){
    }
    public function event_pic_weixin($xml){
    }
    /**
     * [上报地理位置]
     */
    public function event_location_select($xml){
    }
    public function event_media_id($xml){
    }
    public function event_view_limited($xml){
    }
    /**
     * [跳转URL]
     */
    public function event_view($xml){
    }
    /**
     * [点击事件]
     */
    public function event_click($xml){
        $eventkey = $xml->EventKey;
        $this->get_event($xml->EventKey,'click',null);
    }
    /**
     * [关注]
     */
    public function event_subscribe(){
        $event = $this->get(array(
            'eventype'=>'subscribe'
        ));
        $event && $this->send($event);
    }
    /**
     * [取消关注]
     */
    public function event_unsubscribe(){
        $event = $this->get(array(
            'eventype'=>'unsubscribe'
        ));
        $event && $this->send($event);
    }
    /**
     * [关键词]
     */
    public function msg_text($xml){
        $content = trim($xml->Content);
        $this->get_event($content);

        if (in_array($content,array("1", "2", "3", "？","?","你好"))) {
            $site_name = addslashes(iCMS::$config['site']['name']);
            $site_desc = addslashes(iCMS::$config['site']['description']);
            $site_key  = addslashes(iCMS::$config['site']['keywords']);
            $site_host = str_replace('http://', '', iCMS_URL);
            $this->send($site_name.' ('.$site_host.') '.$site_desc."\n回复:".$site_key.' 将会收到我们最新为您准备的信息');
        }
    }
    public function get_event($eventkey=null,$eventype='keyword',$operator='eq',$ret=null){
        $where = array(
            // 'operator' =>'eq', //完全匹配模式
            'eventype' =>$eventype,
            'eventkey' =>$eventkey,
        );
        $operator && $where['operator'] = $operator;
        $event = $this->get($where);
        // var_dump($event);
        $event && $this->send($event);

        if($ret){
            return;
        }
        //所有关键词
        $event = iDB::all("
            SELECT `msg`,`operator`,`eventkey`,`msgtype`
            FROM `#iCMS@__weixin_event`
            WHERE `eventype`='".$eventype."'
            AND `operator`!='eq'
            AND `status` = '1'
            ORDER BY `id` DESC
        ");

        if($event)foreach ($event as $key => $value) {
            $value['msg'] = $this->msg_decode($value['msg']);
            if($value['operator']=='in'){
                if (stripos($eventkey, $value['eventkey']) !== false) {
                    $this->send($value);
                }
            }
            if($value['operator']=='re'){
                $value['eventkey'] = str_replace('@', '\@', $value['eventkey']);
                if (preg_match('@'.$value['eventkey'].'@is',$eventkey)) {
                    $this->send($value);
                }
            }
        }
    }
    public function get($vars=array(),$field="*",$orderby='id DESC'){
        $_vars = array('status'=>'1');
        $sql = iSQL::where(array_merge($_vars,(array)$vars));
        $sql.= ' order by '.$orderby;
        $row = iDB::row("
            SELECT {$field}
            FROM `#iCMS@__weixin_event`
            WHERE {$sql} ",ARRAY_A);
        // echo iDB::$last_query;
        if($row){
            $row['msg'] = $this->msg_decode($row['msg']);
        }
        return $row;
    }

    public function send($event){
        // var_dump($event);
        if(is_array($event)){
            if(isset($event['msgtype']) && $event['msgtype']=='tpl'){
                iView::assign('weixin',self::object2array($this->XML));
                iView::display($event['msg']['Tpl']);
                exit;
            }
            $msg = $event['msg'];
        }else{
            $msg = $event;
        }
        weixin::msg_xml($msg,$this->XML->FromUserName,$this->XML->ToUserName);
        exit;
    }
    public function msg_decode($msg=null){
        $msg && $msg = json_decode($msg,true);
        return $msg;
    }
    public function api_log(){
        $data = array(
            'ToUserName'   => $this->XML->ToUserName,
            'FromUserName' => $this->XML->FromUserName,
            'CreateTime'   => $this->XML->CreateTime,
            'content'      => $this->XML->Content,
            'dayline'      => get_date(null,'Y-m-d H:i:s'),
        );
        $array  = self::object2array($this->XML);
        unset($array['ToUserName'],$array['FromUserName'],$array['CreateTime']);
        $data['content'] = addslashes(json_encode($array));
        iDB::insert('weixin_api_log',$data);
    }
    public static function DEBUG($output=null,$name='debug'){
        if(iPHP_DEBUG){
            if($output===null){
                $output = ob_get_contents();
                ob_end_clean();
            }
            $sub = substr(sha1(md5(iPHP_KEY).md5(iCMS_URL)), 8,16);
            iFS::write(iPHP_APP_CACHE.'/weixin.api.'.$name.'.'.$sub.'.log',$output."\n",1,'ab+');
        }
    }
    public static function object2array($object) {
        return @json_decode(@json_encode($object),true);
    }
}
