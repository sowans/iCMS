<?php
/**
 * @package iCMS
 * @copyright 2007-2010, iDreamSoft
 * @license http://www.idreamsoft.com iDreamSoft
 * @author coolmoo <idreamsoft@qq.com>
 * @$Id: tag.tpl.php 159 2013-03-23 04:11:53Z coolmoo $
 */
function tag_list($vars){
	$where_sql=" status='1'";

	if(isset($vars['tcid'])){
        iPHP::import(iPHP_APP_CORE .'/iMAP.class.php');
        map::init('category',iCMS_APP_TAG);
        $where_sql.= map::exists($vars['tcid'],'`#iCMS@__tags`.id'); //map 表大的用exists
	}
	if(isset($vars['pid'])){
        iPHP::import(iPHP_APP_CORE .'/iMAP.class.php');
        map::init('prop',iCMS_APP_TAG);
        $where_sql.= map::exists($vars['pid'],'`#iCMS@__tags`.id'); //map 表大的用exists
	}

    if(isset($vars['cid!'])){
        $cids	= $vars['sub']?iCMS::get_category_ids($vars['cid!'],true):$vars['cid!'];
        $cids OR $cids	= $vars['cid!'];
        $where_sql.= iPHP::where($cids,'cid','not');
    }
    if(isset($vars['cid'])){
        $cids	= $vars['sub']?iCMS::get_category_ids($vars['cid'],true):$vars['cid'];
        $cids OR $cids	= $vars['cid'];
        $where_sql.= iPHP::where($cids,'cid');
    }

	$maxperpage	= isset($vars['row'])?(int)$vars['row']:"10";
	$cache_time	= isset($vars['time'])?(int)$vars['time']:-1;
	$by			= $vars['by']=='ASC'?"ASC":"DESC";
	switch ($vars['orderby']) {
		case "hot":		$orderSQL=" ORDER BY `count` $by";		break;
		case "new":		$orderSQL=" ORDER BY `id` $by";			break;
		case "order":	$orderSQL=" ORDER BY `ordernum` $by";	break;
//		case "rand":	$orderSQL=" ORDER BY rand() $by";		break;
		default:		$orderSQL=" ORDER BY `id` $by";
	}
	$md5	= md5($where_sql.$orderSQL);
	$offset	= 0;
	if($vars['page']){
		$total	= iPHP::total($md5,"SELECT count(*) FROM `#iCMS@__tags` WHERE {$where_sql} ");
		iPHP::assign("tags_total",$total);
        $multi	= iCMS::page(array('total'=>$total,'perpage'=>$maxperpage,'unit'=>iPHP::lang('iCMS:page:list'),'nowindex'=>$GLOBALS['page']));
        $offset	= $multi->offset;
	}
	if($vars['cache']){
		$cache_name = 'tags/'.$md5."/".(int)$GLOBALS['page'];
		$resource   = iCache::get($cache_name);
	}
	if(empty($resource)){
		iPHP::app('tag.class','include');
		$resource = iDB::all("SELECT * FROM `#iCMS@__tags` WHERE {$where_sql} {$orderSQL} LIMIT {$offset},{$maxperpage}");
		//iDB::debug(1);
		$resource = tag_array($vars,$resource);
		$vars['cache'] && iCache::set($cache_name,$resource,$cache_time);
	}

	return $resource;
}
function tag_flist($vars){
	$where_sql=" status='1'";

	isset($vars['tcid']) 	&& $where_sql.=" AND `tcid`='".(int)$vars['tcid']."'";
	isset($vars['pid']) 	&& $where_sql.=" AND `pid`='".(int)$vars['pid']."'";

    if(isset($vars['cid!'])){
        $cids	= $vars['sub']?iCMS::get_category_ids($vars['cid!'],true):$vars['cid!'];
        $cids OR $cids	= $vars['cid!'];
        $where_sql.= iPHP::where($cids,'cid','not');
    }
    if(isset($vars['cid'])){
        $cids	= $vars['sub']?iCMS::get_category_ids($vars['cid'],true):$vars['cid'];
        $cids OR $cids	= $vars['cid'];
        $where_sql.= iPHP::where($cids,'cid');
    }

	$maxperpage	= isset($vars['row'])?(int)$vars['row']:"10";
	$cache_time	= isset($vars['time'])?(int)$vars['time']:-1;
	$by			= $vars['by']=='ASC'?"ASC":"DESC";
	switch ($vars['orderby']) {
		case "hot":		$orderSQL=" ORDER BY `count` $by";		break;
		case "new":		$orderSQL=" ORDER BY `id` $by";			break;
		case "order":	$orderSQL=" ORDER BY `ordernum` $by";	break;
//		case "rand":	$orderSQL=" ORDER BY rand() $by";		break;
		default:		$orderSQL=" ORDER BY `id` $by";
	}
	$md5	= md5($where_sql.$orderSQL);
	$offset	= 0;
	if($vars['page']){
		$total	= iPHP::total($md5,"SELECT count(*) FROM `#iCMS@__ftags` WHERE {$where_sql} ");
		iPHP::assign("tags_total",$total);
        $multi	= iCMS::page(array('total'=>$total,'perpage'=>$maxperpage,'unit'=>iPHP::lang('iCMS:page:list'),'nowindex'=>$GLOBALS['page']));
        $offset	= $multi->offset;
	}
	if($vars['cache']){
		$cache_name = 'tags/'.$md5."/".(int)$GLOBALS['page'];
		$resource   = iCache::get($cache_name);
	}
	if(empty($resource)){
		iPHP::app('tag.class','include');
		$resource = iDB::all("SELECT * FROM `#iCMS@__ftags` WHERE {$where_sql} {$orderSQL} LIMIT {$offset},{$maxperpage}");
		$resource = tag_array($vars,$resource);
		$vars['cache'] && iCache::set($cache_name,$resource,$cache_time);
	}

	return $resource;
}
function tag_search($vars){
	$SPH	= iCMS::sphinx();
	$SPH->init();
	$SPH->SetArrayResult(true);
	if(isset($vars['weights'])){
		//weights='title:100,tags:80,keywords:60,name:50'
		$wa=explode(',',$vars['weights']);
		foreach($wa AS $wk=>$wv){
			$waa=explode(':',$wv);
			$FieldWeights[$waa[0]]=$waa[1];
		}
		$SPH->SetFieldWeights($FieldWeights);
	}
	$page		= (int)$_GET['page'];
    $maxperpage	= isset($vars['row'])?(int)$vars['row']:10;
	$start 		= ($page && isset($vars['page']))?($page-1)*$maxperpage:0;
	$SPH->SetMatchMode(SPH_MATCH_EXTENDED);
	if($vars['mode']){
		$vars['mode']=="SPH_MATCH_BOOLEAN" && $SPH->SetMatchMode(SPH_MATCH_BOOLEAN);
		$vars['mode']=="SPH_MATCH_ANY" && $SPH->SetMatchMode(SPH_MATCH_ANY);
		$vars['mode']=="SPH_MATCH_PHRASE" && $SPH->SetMatchMode(SPH_MATCH_PHRASE);
		$vars['mode']=="SPH_MATCH_ALL" && $SPH->SetMatchMode(SPH_MATCH_ALL);
		$vars['mode']=="SPH_MATCH_EXTENDED" && $SPH->SetMatchMode(SPH_MATCH_EXTENDED);
	}

    if(isset($vars['cid'])){
    	$cids	= $vars['sub']?iCMS::get_category_ids($vars['cid'],true):$vars['cid'];
    	$cids OR $cids = (array)$vars['cid'];
        $cids	= array_map("intval", $cids);
		$SPH->SetFilter('cid',$cids);
    }
    if(isset($vars['startdate'])){
		$startime = strtotime($vars['startdate']);
		$enddate  = empty($vars['enddate'])?time():strtotime($vars['enddate']);
    	$SPH->SetFilterRange('pubdate',$startime,$enddate);
    }
	$SPH->SetLimits($start,$maxperpage,10000);

	$orderBy  = '@id DESC, @weight DESC';
	$orderSQL = ' order by id DESC';

	$vars['orderBy'] && $orderBy	= $vars['orderBy'];
	$vars['orderSQL']&& $orderSQL= ' order by '.$vars['orderSQL'];

	$vars['pic'] && $SPH->SetFilter('haspic',array(1));
	$vars['id!'] && $SPH->SetFilter('@id',array($vars['id!']),true);

	$SPH->setSortMode(SPH_SORT_EXTENDED,$orderBy);

	$query = $vars['q'];
	$vars['acc']&& 	$query	= '"'.$vars['q'].'"';
	$vars['@']  && 	$query	= '@('.$vars['@'].') '.$query;

	$res = $SPH->Query($query,"ladyband_tag");

	if (is_array($res["matches"])){
		foreach ( $res["matches"] as $docinfo ){
			$tid[] = $docinfo['id'];
		}
		$tids=implode(',',(array)$tid);
	}
	if(empty($tids)) return;

	$where_sql = " `id` in($tids)";
	$offset    = 0;
	if($vars['page']){
		$total   = $res['total'];
		$pagenav = isset($vars['pagenav'])?$vars['pagenav']:"pagenav";
		$pnstyle = isset($vars['pnstyle'])?$vars['pnstyle']:0;
		$multi   = iCMS::page(array('total'=>$total,'perpage'=>$maxperpage,'unit'=>iPHP::lang('iCMS:page:list'),'nowindex'=>$GLOBALS['page']));
		$offset  = $multi->offset;
	}
	$resource = iDB::all("SELECT * FROM `#iCMS@__tags` WHERE {$where_sql} {$orderSQL} LIMIT {$maxperpage}");
	$resource = tag_array($vars,$resource);
	return $resource;
}
function tag_array($vars,$resource){
    if($resource)foreach ($resource as $key => $value) {
		$category  = iCache::get('iCMS/category/'.$value['cid']);
		$tcategory = iCache::get('iCMS/category/'.$value['tcid']);

		$value['category']['name']    = $category['name'];
		$value['category']['subname'] = $category['subname'];
		$value['category']['url']     = $category['iurl']->href;
		$value['category']['link']    = "<a href='{$value['category']['url']}'>{$value['category']['name']}</a>";

		$value['iurl']                = iURL::get('tag',array($value,$category,$tcategory));

		empty($value['url']) &&	$value['url'] = $value['iurl']->href;
		$value['pic'] && $value['pic']=iFS::fp($value['pic'],'+http');
		$value['link']  = '<a href="'.$value['url'].'" class="tag" target="_self">'.$value['name'].'</a> ';
		$resource[$key] = $value;
    }
    return $resource;
}
