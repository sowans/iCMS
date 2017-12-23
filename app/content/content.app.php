<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/
class contentApp {
    public $methods = array('iCMS','clink','hits','vote', 'good', 'bad', 'like_comment', 'comment');
    public $appid   = null;
    public $app     = null; //应用名
    public $tables  = null;
    public $table   = null;

    public function __construct($data) {
        $this->data    = $data;
        $this->appid   = $data['id'];
        $this->app     = $data['app'];
        $this->tables  = apps::get_table($data,false);
        $this->table   = reset($this->tables);
        $this->primary = $this->table['primary'];
        $this->id      = (int)$_GET[$this->primary];
        unset($data);
    }
    public function do_iCMS($a = null) {
        list($v,$p,$f) = apps_common::getting($this->primary);
        return $this->content($v,$p,$f);
    }
    public function API_iCMS(){
        return $this->do_iCMS();
    }
    public function do_clink($a = null) {
        return $this->do_iCMS($a);
    }
    public function API_clink() {
        return $this->do_clink();
    }
    public function API_search($a = null) {
        $app = iPHP::app("search");
        return $app->search("{iTPL}/{$this->app}.search.htm");
    }
    public function API_hits($id = null) {
        apps_common::api_hits('content',$id,$this->primary,$this->table['table']);
    }
    public function ACTION_vote() {
        apps_common::action_vote('content',$this->primary,$this->table['table']);
    }
    public function hooked(&$data){
        iPHP::hook($this->app,$data,iCMS::$config['hooks'][$this->app]);
    }
    public function content($fvar, $page = 1,$field='id',$tpl = true) {
        $rs = iDB::row("
            SELECT * FROM `".$this->table['table']."`
            WHERE `".$field."`='".$fvar. "'
            AND `status` ='1' LIMIT 1;",
        ARRAY_A);

        $rs OR iPHP::error_404('找不到'.$this->data['title'].': <b>'.$field.':' . $fvar . '</b>', 10001);
        $id = $rs[$this->primary];

        if ($rs['url']) {
            if (iView::$gateway == "html") {
                return false;
            } else {
                $this->API_hits($id);
                iPHP::redirect($rs['url']);
            }
        }

        $vars = array(
            'tag'  => true,
            'user' => true,
        );
        $rs+= $this->data($id);
        $rs = $this->value($rs,$cdata,$vars,$page,$tpl);
        if ($rs === false) {
            return false;
        }
        $rs+=(array)apps_meta::data($this->app,$id);
        $this->hooked($rs);

        if ($tpl) {
            $app = apps::get_app_lite($this->data);
            //自定义应用模板信息
            $app['type']=="2" && iPHP::callback(array("contentFunc","interfaced"),array($app));
            $content = $rs; unset($content['category']);
            iView::assign('APP', $app);
            iView::assign('content', $content);
            unset($content);
        }

        return apps_common::render($rs,$this->app,$tpl);
    }

    public function value($rs, $vars = array(),$page = 1, $tpl = false) {
        $rs['appid'] = $this->appid;
        $category = categoryApp::category($rs['cid'],false);

        if ($tpl) {
            $category OR iPHP::error_404('找不到该'.$this->data['title'].'的栏目缓存<b>cid:' . $rs['cid'] . '</b> 请更新栏目缓存或者确认栏目是否存在', 10002);
        } else {
            if (empty($category)) {
                return false;
            }

        }

        if ($category['status'] == 0) {
            return false;
        }
        if(iCMS::check_view_html($tpl,$category,$this->app)){
            return false;
        }

        $rs['category'] = categoryApp::get_lite($category);

        $rs['iurl'] = (array)iURL::get($this->app, array($rs, $category));
        $rs['url'] OR $rs['url'] = $rs['iurl']['href'];

        ($tpl && $category['mode'] == '1') && iCMS::redirect_html($rs['iurl']);

        if($category['mode'] && stripos($rs['url'], '.php?')===false){
            iURL::page_url($rs['iurl']);
        }

        $vars['tag'] && tagApp::get_array($rs,$category['name'],'tags');

        apps_common::init($rs,$this->app,$vars,$this->primary);
        apps_common::link();
        apps_common::text2link();
        apps_common::user();
        apps_common::comment();
        apps_common::pic();
        apps_common::hits();
        apps_common::param();

        if($this->data['fields']){
            $fields = former::fields($this->data['fields']);
            foreach ((array)$fields as $key => $field) {
                formerApp::vars($field,$key,$rs,$vars,$category,$this->app);
            }
        }
        return $rs;
    }
    public function data($ids=0){
        if(empty($ids)) return array();

        $dtn = apps_mod::data_table_name($this->app);
        $cdata_table = $this->tables[$dtn];
        if(empty($cdata_table)){
            return array();
        }
        $union_key   = $cdata_table['union'];
        $table_name  = $cdata_table['name'];

        list($ids,$is_multi)  = iSQL::multi_var($ids);
        $sql  = iSQL::in($ids,$union_key,false,true);
        $data = array();
        $rs   = iDB::all("SELECT * FROM `#iCMS@__{$table_name}` where {$sql}");
        if($rs){
            $_count = count($rs);
            for ($i=0; $i < $_count; $i++) {
                $data[$rs[$i][$union_key]]= $rs[$i];
            }
            $is_multi OR $data = $data[$ids];
        }
        if(empty($data)){
            return array();
        }
        return $data;
    }
    /**
     * [iPHP::run回调]
     * @param  [type] $app [description]
     * @return [type]      [description]
     */
    public static function run($app){
        $data = apps::get_app($app);
        if($data){
            iPHP::$app_path = iPHP_APP_DIR . '/content';
            iPHP::$app_file = iPHP::$app_path . '/content.app.php';
            iPHP::$app      = new contentApp($data);
        }else{
            iPHP::error_404('Unable to find custom application <b>' . $app . '.app.php</b>', '0003');
        }
    }
}
