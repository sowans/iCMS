<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/
class configAdmincp{
    public function __construct() {}
    /**
     * [配置管理]
     */
    public function do_iCMS(){
    	$config	= $this->get();
        $redis    = extension_loaded('redis');
        $memcache = extension_loaded('memcached');
        menu::$url = __ADMINCP__.'='.admincp::$APP_NAME;
    	include admincp::view("config");
    }
    /**
     * [保存配置]
     */
    public function do_save(){
        $config = iSecurity::escapeStr($_POST['config']);

        iFS::allow_files($config['FS']['allow_ext']) OR iUI::alert("附件设置 > 允许上传类型设置不合法!");
        iFS::allow_files(trim($config['router']['ext'],'.')) OR iUI::alert('URL设置 > 文件后缀设置不合法!');

        $desktop_tpl_ext = iFS::get_ext($config['template']['desktop']['tpl']);
        if($desktop_tpl_ext) iFS::allow_files($desktop_tpl_ext) OR iUI::alert("桌面端模板不合法!");

        $config['router']['ext']    = '.'.trim($config['router']['ext'],'.');
        $config['router']['url']    = trim($config['router']['url'],'/');
        $config['router']['public'] = rtrim($config['router']['public'],'/');
        $config['router']['user']   = rtrim($config['router']['user'],'/');
        $config['router']['dir']    = rtrim($config['router']['dir'],'/').'/';
        $config['FS']['url']        = trim($config['FS']['url'],'/').'/';
        $config['router']['config'] = json_decode(stripcslashes($_POST['config']['router']['config']),true);
        $config['template']['desktop']['domain'] = $config['router']['url'];

        if($config['cache']['engine']!='file'){
            iPHP::callback(array("cacheAdmincp","test"),array($config['cache']));
        }

        if(json_last_error()){
            $error = json_last_error_msg();
            $error && iUI::alert("REWRITE配置出错 > JSON -{$error}");
        }

    	foreach($config AS $n=>$v){
    		$this->set($v,$n,0);
    	}
    	configAdmincp::cache();
    	iUI::success('更新完成','js:1');
    }

    /**
     * [cache 更新配置]
     * @return [type] [description]
     */
    public static function cache(){
        $config         = self::get();
        $config['apps'] = apps::get_appsid();
        $config['iurl'] = apps::get_iurl();
        self::write($config);
    }
    public static function head($title=null,$action="config"){
        include admincp::view("config.head","config");
    }
    public static function foot(){
        include admincp::view("config.foot","config");
    }
    /**
     * [app 其它应用配置接口]
     * @param  integer $appid [应用ID]
     * @param  [sting] $name   [应用名]
     */
    public static  function app($appid=0,$name=null,$ret=false,$suffix="config"){
        $name===null && $name = admincp::$APP_NAME;
        $config = self::get($appid,$name);
        if($ret){
            return $config;
        }
        include admincp::view($name.'.'.$suffix);
    }
    /**
     * [save 其它应用配置保存]
     * @param  integer $appid [应用ID]
     * @param  [sting] $app   [应用名]
     */
    public static function save($appid=0,$name=null,$handler=null,$dialog=true){
        $name===null   && $name = admincp::$APP_NAME;
        empty($appid) && iUI::alert("配置程序出错缺少APPID!");
        $config = iSecurity::escapeStr($_POST['config']);
        self::set($config,$name,$appid,false);
        $handler && iPHP::callback($handler,array($config));
        configAdmincp::cache();
        $dialog && iUI::success('配置更新完成','js:1');
    }
    /**
     * [get 获取配置]
     * @param  integer $appid [应用ID]
     * @param  [type]  $name   [description]
     * @return [type]       [description]
     */
    public static function get($appid = NULL, $name = NULL) {
        if ($name === NULL) {
            $sql = "appid< '999999'";
            $appid === NULL OR $sql = " AND `appid`='$appid'";
            $rs  = iDB::all("SELECT * FROM `#iCMS@__config` WHERE $sql");
            foreach ((array)$rs AS $c) {
                $value = $c['value'];
                // strpos($c['value'], 'a:')===false OR $value = serialize($c['value']);
                $value = (array)json_decode($value,true);
                $config[$c['name']] = $value;
            }
            return $config;
        } else {
            $value = iDB::value("SELECT `value` FROM `#iCMS@__config` WHERE `appid`='$appid' AND `name` ='$name'");
            // strpos($value, 'a:')===false OR $value = unserialize($value);
            $value = (array)json_decode($value,true);
            return $value;
        }
    }
    /**
     * [set 更新配置]
     * @param [type]  $v     [description]
     * @param [type]  $n     [description]
     * @param [type]  $appid   [description]
     * @param boolean $cache [description]
     */
    public static function set($value, $name, $appid, $cache = false) {
        $cache && iCache::set('config/' . $name, $value, 0);
        // is_array($value) && $value = addslashes(serialize($value));
        is_array($value) && $value = addslashes(cnjson_encode($value));
        $check  = iDB::value("SELECT `name` FROM `#iCMS@__config` WHERE `appid` ='$appid' AND `name` ='$name'");
        $fields = array('appid','name','value');
        $data   = compact ($fields);
        if($check===null){
            iDB::insert('config',$data);
        }else{
            iDB::update('config', $data, array('appid'=>$appid,'name'=>$name));
        }
    }
    public static function del($name, $appid) {
        if($name &&$appid){
            iDB::query("DELETE FROM `#iCMS@__config` WHERE `appid` ='$appid' AND `name` ='$name'");
        }
    }
    /**
     * [write 配置写入文件]
     * @param  [type] $config [description]
     * @return [type]         [description]
     */
    public static function write($config=null){
        $config===null && $config = self::get();
        $output = "<?php\ndefined('iPHP') OR exit('Access Denied');\nreturn ";
        $output.= var_export($config,true);
        $output.= ';';
        iFS::write(iPHP_APP_CONFIG,$output);
    }
    /**
     * [update 单个配置更新]
     * @param  [type] $k [description]
     * @return [type]    [description]
     */
    public static function update($k,$appid=0){
        self::set(iCMS::$config[$k],$k,$appid);
        configAdmincp::cache();
    }
    public static function view(){
        include admincp::view('config',null,true);
    }
}
