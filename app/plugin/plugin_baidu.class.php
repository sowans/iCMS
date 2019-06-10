<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/
class plugin_baidu{
    public static $out = null;
    /**
     * [百度站长平台 主动推送(实时)]
     */
    public static function ping($urls,$type=null) {
        $site          = iCMS::$config['api']['baidu']['sitemap']['site'];
        $access_token  = iCMS::$config['api']['baidu']['sitemap']['access_token'];
        if(empty($site)||empty($access_token)){
            return false;
        }
        $api ='http://data.zz.baidu.com/urls?site='.$site.'&token='.$access_token;
        $type && $api.='&type='.$type;
        $ch = curl_init();
        $options =  array(
            CURLOPT_URL            => $api,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => implode("\n",(array)$urls),
            CURLOPT_HTTPHEADER     => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        self::$out = json_decode($result);
        if(self::$out->success){
            return true;
        }
        return false;
    }

    public static function xzh($urls,$type='realtime',&$out=null) {
        $appid = iCMS::$config['plugin']['baidu']['xzh']['appid'];
        $token = iCMS::$config['plugin']['baidu']['xzh']['token'];

        if(empty($appid)||empty($token)){
            return false;
        }
        $api ='http://data.zz.baidu.com/urls?appid='.$appid.'&token='.$token;
        $type && $api.='&type='.$type;
        $ch = curl_init();
        $options =  array(
            CURLOPT_URL            => $api,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => implode("\n",(array)$urls),
            CURLOPT_HTTPHEADER     => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $out   = json_decode($result,true);
        if($out['error']){
            return false;
        }
        return true;
    }
    /**
     * http://ping.baidu.com/ping.html
     * http://help.baidu.com/question?prod_id=99&class=0&id=3046
     * @param [type] $url [description]
     */
    public static function RPC2($url){
        $pingRpc  = 'http://ping.baidu.com/ping/RPC2';
        $baiduXML = '<?xmlversion="1.0"?>';
        $baiduXML .= '<methodCall>';
        $baiduXML .= '<methodName>weblogUpdates.ping</methodName>';
        $baiduXML .= '<params>';
        $baiduXML .= '<param><value><string>' . $url . '</string></value></param>';
        $baiduXML .= '<param><value><string>' . $url . '</string></value></param>';
        $baiduXML .= '</params>' . "\n";
        $baiduXML .= '</methodCall>';
        $header   = array(
            'Accept: */*',
            'Referer: http://ping.baidu.com/ping.html',
            'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.139 Safari/537.36',
            'Host:ping.baidu.com',
            'Content-Type:text/xml',
        );
        $curl     = curl_init();
        curl_setopt($curl, CURLOPT_URL, $pingRpc);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $baiduXML);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

}
