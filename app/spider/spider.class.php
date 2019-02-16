<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/
class spider{
    public static $cid      = null;
    public static $rid      = null;
    public static $pid      = null;
    public static $sid      = null;
    public static $poid     = null;
    public static $title    = null;
    public static $url      = null;
    public static $work     = false;
    public static $urlslast = null;
    public static $allHtml  = array();
    public static $indexid  = null;

    public static $dataTest  = false;
    public static $ruleTest  = false;

	public static $content_right_code = false;
	public static $content_error_code = false;

    public static $referer     = null;
    public static $encoding    = null;
    public static $useragent   = null;
    public static $cookie      = null;
    public static $charset     = null;
    public static $curl_proxy  = false;
    public static $proxy_array = array();
    public static $PROXY_URL   = false;
    public static $callback    = array();

    public static $spider_url_ids   = array();

    public static function rule($id) {
        return spider_rule::get($id);
    }
    public static function project($id) {
        return spider_project::get($id);
    }
    public static function postArgs($id) {
        return spider_post::get($id);
    }

    public static function checker($work = null,$pid=null,$url=null,$title=null){
        $pid   ===null && $pid = spider::$pid;
        $url   ===null && $url = spider::$url;
        $title ===null && $title = spider::$title;
        $project = spider_project::get($pid);
        $indexid = self::get_indexid();
        if(($project['checker'] && empty($indexid)) || $work=="DATA@RULE"){
            $title = addslashes($title);
            $url   = addslashes($url);
            $mode  = $project['checker'];
            $work=="DATA@RULE" && $mode = '1';
            spider::$callback['checker:mode'] && $mode  = spider::$callback['checker:mode'];
            spider::$callback['checker:url']  && $url   = spider::$callback['checker:url'];
            spider::$callback['checker:title']&& $title = spider::$callback['checker:title'];
            $hash = md5($url);
            switch ($mode) {
                case '1'://按网址检查
                case '4'://按网址检查更新
                    $scheme = parse_url($url, PHP_URL_SCHEME);
                    if($scheme){
                        $_url  = str_replace($scheme.'://', '', $url);
                        $urls = array($_url,'http://'.$_url,'https://'.$_url);
                        $sql  = "`url` IN ('".implode("','", $urls)."')";
                    }else{
                        $sql  = "`url` = '$url'";
                    }
                    $label = $url.PHP_EOL;
                    $msg   = $label.'该网址的文章已经发布过!请检查是否重复';
                break;
                case '2'://按标题检查
                case '5'://按标题检查更新
                    $sql   = "`title` = '$title'";
                    $label = $title.PHP_EOL;
                    $msg   = $label.'该标题的文章已经发布过!请检查是否重复';
                break;
                case '3'://网址和标题
                case '6'://网址和标题更新
                    $sql   = "`url` = '$url' AND `title` = '$title'";
                    $label = $title.PHP_EOL.$url;
                    $msg   = $label.'该网址和标题的文章已经发布过!请检查是否重复';
                break;
            }
            switch ($project['self']) {
                case '1':
                    $sql.=" AND `pid`='".$pid."'";
                break;
                case '2':
                    $sql.=" AND `rid`='".spider::$rid."'";
                break;
            }
            $ret = array();
            $sql && $ret = iDB::row("SELECT `id`,`publish`,`indexid` FROM `#iCMS@__spider_url` where {$sql} ",ARRAY_A);

            spider::$callback['url:id']      = $ret['id'];
            spider::$callback['url:indexid'] = $ret['indexid'];

            if($ret){
                if(in_array($mode, array("1","2","3"))) {
                    if(in_array($ret['publish'], array("1","2"))) {
                        $work===NULL && iUI::alert($msg, 'js:parent.$("#' . $hash . '").remove();');
                        if($work=='shell'){
                            echo date("Y-m-d H:i:s ")."\n\033[35m".$msg."\033[0m\n\n";
                            return false;
                        }
                        if($work=="WEB@AUTO"){
                            return '-1';
                        }
                        return false;
                    }
                }else{
                    // spider::$surlData = $ret;
                }
            }else{
                return true;
            }
        }
        return true;
    }

    public static function update_spider_url_indexid($suid,$indexid){
        iDB::update('spider_url',array(
            //'publish' => '1',
            'indexid' => $indexid,
            //'pubdate' => time()
        ),array('id'=>$suid));
        self::update_spider_url_ids($indexid);
    }

    public static function update_spider_url_publish($suid){
        iDB::update('spider_url',array(
            'publish' => '1',
            'pubdate' => time()
        ),array('id'=>$suid));
        self::update_spider_url_ids();
    }

    public static function update_spider_url_ids($indexid=0){
        foreach ((array)spider::$spider_url_ids as $key => $suid) {
            if($indexid){
                $data = array(
                    'indexid' => $indexid,
                );
            }else{
                $data = array(
                    'pid'     => spider::$pid,
                    'publish' => '1',
                    'status'  => '1',
                    'pubdate' => time()
                );
            }
            iDB::update('spider_url',$data,array('id'=>$suid));
        }
    }
    public static function spider_url($app,$post=null) {
        $post===null && $post=$_POST;
        $appid  = $app['id'];
        $url    = $post['reurl'];
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $surl   = str_replace($scheme.'://', '', $url);
        $urls   = array($surl,'http://'.$surl,'https://'.$surl);
        $sql    = "`url` IN ('".implode("','", $urls)."')";
        $row    = iDB::row("
            SELECT `id`,`publish`,`indexid`
            FROM `#iCMS@__spider_url`
            WHERE {$sql}
        ",ARRAY_A);

        if($row){
            $row['indexid'] && self::get_app_pdata($row['indexid'],$app);
            return $row['id'];
        }
        return iDB::insert('spider_url',array(
            'appid'   => $appid,
            'cid'     => $post['cid'],
            'rid'     => spider::$rid,'pid'=> spider::$pid,
            'title'   => addslashes($post['title']),
            'url'     => addslashes($post['reurl']),
            'hash'    => md5($post['reurl']),
            'status'  => '1',
            'addtime' => time(),
            'publish' => '0',
            'indexid' => '0',
            'pubdate' => ''
        ));
    }

    public static function publish($work = null,$check=false) {
        @set_time_limit(0);
        spider::$callback['STATUS'] = 'publish';

        if($check){
            $checker = spider::checker($work,spider::$pid,spider::$url,spider::$title);
            if($checker!==true){
                return $checker;
            }
        }
        $_POST = spider::$callback['_POST'];
        empty($_POST) && $_POST = spider_data::crawl();

        if($_POST===false) return false;

        foreach ((array)$_POST as $key => $value) {
            if($value===null && $key!='__title__'){
                spider_error::log("publish:$key:null",$_POST['reurl'],"publish:$key:null");
                return null;
            }
        }

        if(spider::$work && $work===null) $work = spider::$work;

        $checker = spider::checker($work,spider::$pid,$_POST['reurl'],$_POST['title']);

        if($checker!==true){
            return $checker;
        }

        $project = spider_project::get(spider::$pid);
        isset($_POST['cid']) OR $_POST['cid'] = $project['cid'];
        $poid  = spider::$poid?:$project['poid'];
        $spost = spider_post::get($poid);
        $appid = $_POST['appid']?:$spost->app;
        $app   = apps::get_app($appid);

        $indexid = self::get_indexid();
        $indexid && self::get_app_pdata($indexid,$app);

        if (spider::$callback['post'] && is_callable(spider::$callback['post'])) {
            $_POST = call_user_func_array(spider::$callback['post'],array($_POST,spider::$callback['post:data'],spider::$sid));
            if($_POST['callback']){
                return $_POST;
            }
        }

        if($_POST===false) return false;

        $sid = spider::$sid;
        spider::$callback['url:id'] && $sid = spider::$callback['url:id'];
        empty($sid) && $sid = spider::spider_url($app);

        $rcode  = '1001';
        $result = spider_post::commit($rcode,$sid,$spost);

        if($_POST['commit:callData']){
            var_dump($result,$_POST['commit:callData']);
            $callData = $_POST['commit:callData'];
            list($_s,$poid) = explode('@', $callData['POST']);
            foreach ($callData as $key => $value) {
                $value['URLS'] && $subData[] = self::sub_crawl($value,$rule);
            }
        }
            // if(spider::$callback['STATUS']==='publish'){
            //     if($res['POST'] && strpos($res['POST'], 'poid@')!==false){
            //         list($_s,$poid) = explode('@', $res['POST']);
            //         unset($res['POST']);
            //         spider::$callback['_POST'] = $res;
            //         var_dump(spider::$callback['_POST']);
            //         exit;
            //         spider::$poid = $poid;
            //         $result = spider::publish();
            //         var_dump($result);
            //         spider::$poid = null;
            //     }
            //     exit;
            // }
        spider::$callback['save'] && spider::$callback['commit'] = spider::$callback['save'];
        if (spider::$callback['commit'] && is_callable(spider::$callback['commit'])) {
            $ret = call_user_func_array(spider::$callback['commit'],array($result,$_POST));
            if($ret['callback']||$ret['return']){
                return $ret;
            }
        }
        spider::$callback['STATUS'] = null;

        // if (spider::$callback['save'] && is_callable(spider::$callback['save'])) {
        //     $ret = call_user_func_array(spider::$callback['save'],array($result,$_POST));
        //     if($ret['callback']){
        //         return $ret;
        //     }
        // }

        if ($result['code'] == $rcode && $work===NULL) {
            $msg = ($indexid?'更新':'发布').'成功!';
            if (spider::$sid) {
                iUI::success($msg,'js:1');
            } else {
                iUI::success($msg, 'js:parent.$("#' . md5($_POST['reurl']) . '").remove();');
            }
        }
        if($work=="shell"||$work=="WEB@AUTO"){
            $result && $result['work']=$work;
            return $result;
        }
    }

    public static function callback($obj,$indexid,$type = null) {
        if ($type === null || $type == 'primary') {
            if ($obj->callback['primary']) {
                $PCB = $obj->callback['primary'];
                $handler = $PCB[0];
                $params = (array) $PCB[1];
                $indexid && $params+= array('indexid' => $indexid);

                $obj->callback['return'] =
                $obj->callback['save:return'] = array(
                    "code" => $obj->callback['code']
                )+$params;
                if (is_callable($handler)) {
                    call_user_func_array($handler, $params);
                }
            }
        }
        if ($type === null || $type == 'data') {
            if ($obj->callback['data']) {
                $DCB = $obj->callback['data'];
                $handler = $DCB[0];
                $params = (array) $DCB[1];
                if (is_callable($handler)) {
                    call_user_func_array($handler, $params);
                }
            }
        }
    }
    public static function get_indexid() {
        $indexid = spider::$indexid?:(int)$_GET['indexid'];
        // if(spider::$callback['url:indexid']){
        //     $indexid = spider::$callback['url:indexid'];
        // }
        return (int)$indexid;
    }
    public static function get_app_pdata($indexid,$app) {
        if(empty($indexid)) return;

        if($app['app']=='article'){
            $_POST['article_id']  = $indexid;
            $_POST['data_id'] = iDB::value("SELECT `id` FROM `#iCMS@__article_data` WHERE aid='".$indexid."'");
        }else{
            $table           = apps::get_table($app);
            $primary         = $table['primary'];
            $_POST[$primary] = $indexid;
            $data_table      = apps_mod::get_data_table($app['table']);
            if($data_table){
                $data_id_key = $data_table['primary'];
                $union_key   = $data_table['union'];
                $table_name  = $data_table['name'];
                $_POST[$union_key]   = $indexid;
                $_POST[$data_id_key] = iDB::value("SELECT `{$data_id_key}` FROM `#iCMS@__{$table_name}` WHERE `{$union_key}`='{$indexid}'");
            }
        }
    }
}
