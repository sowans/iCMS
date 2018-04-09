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

class apps_storeAdmincp extends appsAdmincp {
    /**
     * [模板市场]
     * @return [type] [description]
     */
    public function do_template(){
      $title      = '模板';
      $storeArray = apps_store::get_array(array('type'=>'1'));
      $dataArray  = apps_store::remote_getall('template');
      include admincp::view("apps.store");
    }
    public function do_template_update(){
      $this->do_store_update('template','模板');
    }
    public function do_template_uninstall(){
      $sid   = (int)$_GET['sid'];
      $store = apps_store::get($sid);
      $dir   = iView::check_dir($store['app']);
      if($dir && $store['app']){
        foreach (iDevice::$config as $key => $value) {
          if($value['tpl']==$store['app']){
            iUI::alert('当前模板【'.$key.'】设备正在使用中,如果删除将出现错误','js:1',10);
          }
        }
        foreach ((array)iDevice::$config['device'] as $key => $value) {
          if($value['tpl']==$store['app']){
            iUI::alert('当前模板【'.$value['name'].'】设备正在使用中,如果删除将出现错误','js:1',10);
          }
        }
        iFS::rmdir($dir);
      }
      $sid && apps_store::del($sid,'sid');
      iUI::success('模板已删除','js:1');
    }
    /**
     * [从模板市场安装模板]
     * @return [type] [description]
     */
    public function do_template_install(){
      $this->do_store_install('template','模板');
    }
    /**
     * [付费安装模板]
     * @return [type] [description]
     */
    public function do_template_premium_install(){
      $this->do_store_premium_install('template');
    }
    /**
     * [应用市场]
     * @return [type] [description]
     */
    public function do_store($name=null){
      $title      = '应用';
      $storeArray = apps_store::get_array(array('type'=>'0'));
      $dataArray  = apps_store::remote_getall();
      include admincp::view("apps.store");
    }
    public function do_store_uninstall(){
      $this->do_uninstall();
    }
    public function do_store_update($type='app',$title='应用'){
      $sid   = (int)$_GET['sid'];
      $data  = apps_store::get($sid);

      $data['authkey'] && $_GET['authkey'] = $data['authkey'];
      $data['transaction_id'] && $_GET['transaction_id'] = $data['transaction_id'];

      $store = apps_store::remote_git('app_update_zip',$sid);
      apps_store::check_must($store);

      iCache::set('store/'.$sid,$store,3600);

      apps_store::$is_update = true;
      apps_store::$sid       = $sid;
      apps_store::$app_id    = $this->id;
      apps_store::$uptime    = $data['git_time'];

      apps_store::setup($store['zip_url'],$store['app'],$store['name']);
    }
    /**
     * [从应用市场安装应用]
     * @return [type] [description]
     */
    public function do_store_install($type='app',$title='应用',$update=false){
      $sid   = (int)$_GET['sid'];
      $store = apps_store::remote_get($sid);
      apps_store::check_must($store);

      if($type=='app'){
          $ag = apps::get($store['app'],'app');
          if($ag){
            $asg = apps_store::get($ag['id'],'appid');
            if(empty($asg)){
              apps_store::save(array(
                  'sid'      => $sid,
                  'appid'    => $ag['id'],
                  'app'      => $ag['app'],
                  'name'     => $ag['name'],
                  'git_time' => $ag['addtime'],
                  'version'  => substr($ag['config']['version'], 1),
                  'addtime'  => $ag['addtime'],
                  'authkey'  => $store['authkey'],
              ));
            }
            iUI::alert($store['name'].'['.$store['app'].'] 应用已存在','js:1',1000000);
          }

          if($store['data']['tables']){
            foreach ($store['data']['tables'] as $table) {
                iDB::check_table($table) && iUI::alert('['.$table.']数据表已经存在!','js:1',1000000);
            }
          }

          $path = iPHP_APP_DIR.'/'.$store['app'];
          if(iFS::checkDir($path) && empty($store['force'])){
            $ptext = iSecurity::filter_path($path);
            iUI::alert(
              $store['name'].'['.$store['app'].'] <br />应用['.$ptext.']目录已存在,<br />程序无法继续安装',
              'js:1',1000000
            );
          }
      }

      if($type=='template'){
        $path = iPHP_TPL_DIR.'/'.$store['app'];
        if(iFS::checkDir($path)){
          $asg = apps_store::get($sid,'sid');
          if(empty($asg)){
            apps_store::save(array(
                'sid'      => $sid,
                'appid'    => 0,
                'type'     => 1,
                'app'      => $store['app'],
                'name'     => $store['name'],
                'git_time' => filectime($path),
                'addtime'  => filectime($path),
                'authkey'  => $store['authkey'],
            ));
          }
          if(empty($store['force'])){
            $ptext = iSecurity::filter_path($path);
            iUI::alert(
              $store['name'].'['.$store['app'].'] <br /> 模板['.$ptext.']目录已存在,<br />程序无法继续安装',
              'js:1',1000000
            );
          }
        }
      }

      iCache::set('store/'.$sid,$store,3600);
      apps_store::$sid = $sid;

      if($store){
        if($store['premium']){
          apps_store::premium_dialog($sid,$store,$title);
        }else{
          apps_store::setup($store['url'],$store['app'],$store['name'],null,$type);
        }
      }
    }
    /**
     * [付费安装]
     * @return [type] [description]
     */
    public function do_store_premium_install($type='app'){
      $sapp    = $_GET['sapp'];
      $name    = $_GET['name'];
      $version = $_GET['version'];

      $url     = $_GET['url'];
      $key     = $_GET['key'];
      $sid     = $_GET['sid'];
      $tid     = $_GET['transaction_id'];
      $query   = compact(array('sid','key','tid'));
      $zipurl  = $url.'?'.http_build_query($query);

      apps_store::$sid = $sid;
      apps_store::setup($zipurl,$sapp,$name,$key.'.zip',$type);
    }
    public function do_pay_notify(){
      echo apps_store::pay_notify($_GET);
    }
}
