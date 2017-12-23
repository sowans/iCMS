<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/

class apps_common {
    public static $primary ='id';
    public static $data    = array();
    public static $vars    = array();
    public static $name    = null;


    public static function init(&$data,$name,$vars,$primary='id') {
        self::$data    = &$data;
        self::$name    = $name;
        self::$vars    = $vars;
        self::$primary = $primary;
    }
    public static function getting($idkey='id') {
        $v = (int) $_GET[$idkey];
        $p = isset($_GET['p']) ? (int) $_GET['p'] : 1;
        $f = $idkey;
        if(isset($_GET['clink'])){
            $v = iSecurity::escapeStr($_GET['clink']);
            $f = 'clink';
        }
        return array($v,$p,$f);
    }
    public static function api_hits($name,$id=null,$primary='id',$table=null) {
        $id===null && $id = (int)$_GET['id'];
        if($id){
            $sql = iSQL::update_hits();
            $table===null && $table='`#iCMS@__'.$name.'`';

            iDB::query("
                UPDATE {$table}
                SET {$sql}
                WHERE `{$primary}` ='$id'
            ");
        }
    }
    public static function action_vote($name,$primary='id',$table=null) {
        // user::get_cookie() OR iUI::code(0,'iCMS:!login',0,'json');
        $type = $_POST['type'];
        $iid  = (int) $_POST['iid'];
        $iid OR iUI::code(0, $name.':empty_id', 0, 'json');

        $ackey = $name.'_' . $type . '_' . $iid;
        $vote = iPHP::get_cookie($ackey);
        $vote && iUI::code(0, $name.':!' . $type, 0, 'json');

        if ($type == 'good') {
            $sql = '`good`=good+1';
        } else {
            $sql = '`bad`=bad+1';
        }
        $table===null && $table='`#iCMS@__'.$name.'`';

        iDB::query("
            UPDATE {$table}
            SET {$sql}
            WHERE `{$primary}` ='{$iid}'
        ");

        iPHP::set_cookie($ackey, $_SERVER['REQUEST_TIME'], 86400);
        iUI::code(1, $name.':'. $type, 0, 'json');
    }
    public static function render($data,$name,$tpl,$view_app=null) {
        if (!$tpl) return $data;

        $view_app===null && $view_app=$name;
        $view_tpl = $data['tpl'];
        $view_tpl OR $view_tpl = $data['category']['template'][$name];
        strstr($tpl, '.htm') && $view_tpl = $tpl;

        iView::set_iVARS($data['iurl'],'iURL');
        if($data['category']){
            iView::assign('APP', $data['category']['app']); //绑定的应用信息
            unset($category['app']);
            iView::assign('category', $data['category']);
            unset($data['category']);
        }
        iView::assign($name, $data);
        $view = iView::render($view_tpl,$view_app);
        if($view) return array($view,$data);
    }
    public static function custom() {

    }
    public static function link($title=null) {
        $title===null && $title = self::$data['title'];
        self::$data['link']  = '<a href="'.self::$data['url'].'" class="'.self::$name.'_link" target="_blank">'.$title.'</a>';
    }
    public static function text2link() {
        self::$data['source'] = text2link(self::$data['source']);
        self::$data['author'] = text2link(self::$data['author']);
    }

    public static function comment() {
        self::$data['comment'] = array(
            'url' => iCMS_API . "?app=".self::$name."&do=comment&appid=".self::$data['appid']."&iid=".self::$data[self::$primary]."&cid=".self::$data['cid'],
            'count' => self::$data['comments'],
        );
    }
    public static function pic(){
        $picArray = array();
        isset(self::$data['picdata']) && $picArray = filesApp::get_picdata(self::$data['picdata']);

        if(isset(self::$data['pic'])){
            self::$data['pic']  = filesApp::get_pic(
                self::$data['pic'],
                $picArray['p'],
                filesApp::get_twh(
                    self::$vars['ptw'],
                    self::$vars['pth']
                )
            );
        }
        $sizeMap = array('b','m','s');
        foreach ($sizeMap as $key => $size) {
            $k = $size.'pic';
            if(isset(self::$data[$k])){
                self::$data[$k] = filesApp::get_pic(
                    self::$data[$k],
                    $picArray[$size],
                    filesApp::get_twh(
                        self::$vars[$size.'tw'],
                        self::$vars[$size.'th']
                    )
                );
            }
        }
        unset(self::$data['picdata'],$picArray);
    }
    public static function user() {
        if (self::$vars['user']) {
            if (self::$data['postype']) {
                self::$data['user'] = user::empty_info(self::$data['userid'], '#' . self::$data['editor']);
            } else {
                self::$data['user'] = user::info(self::$data['userid'], self::$data['author']);
            }
        }
    }
    public static function hits() {
        self::$data['hits']   = array(
            'script' => iCMS_API . '?app='.self::$name.'&do=hits&cid=' . self::$data['cid'] . '&id=' . self::$data[self::$primary],
            'count'  => self::$data['hits'],
            'today'  => self::$data['hits_today'],
            'yday'   => self::$data['hits_yday'],
            'week'   => self::$data['hits_week'],
            'month'  => self::$data['hits_month'],
        );
    }
    public static function param() {
        self::$data['param'] = array(
            "appid" => self::$data['appid'],
            "iid"   => self::$data['id'],
            "cid"   => self::$data['cid'],
            "suid"  => self::$data['userid'],
            "title" => self::$data['title'],
            "url"   => self::$data['url'],
        );
    }
}
