<?php
/*
 * template_lite plugin
 *
 * Type:     function
 * Name:     array
 * Version:  0.1
 * Examples:
 * e1.{array key="value" key1="value1"}
 * e2.{array as="ar" flag="col" a[]="1" b[]="2"}
 * e3.{array as[]="ar" a="1" b="2"}
 * {array as="ar" {json}}
 * {array as="ar" []={json}}
 * {array as="ar" [a]='aa'}
 * {array as="ar" a[b][c]='aa'}
 */
function tpl_function_array($params, &$tpl){
    $key = $params['assign']?:'array';
    $params['as'] && $key = $params['as'];
    if($params['as[]']){
        $mas = $key = $params['as[]'];
        $array = $tpl->_vars[$key];
    }
    $merge = $params['merge']?true:false;
    unset($params['assign'],$params['merge'],$params['as'],$params['as[]']);
    // Examples:e1
    $merge && $params = array_merge((array)$tpl->_vars[$key],(array)$params);
    foreach ((array)$params as $pk => $pv) {
            $pk = trim($pk);
            if(strpos($pk, '[')!==false && substr($pk, -1)==']'){
                $pa = str_multi_array($pk,'[',$pv);
                unset($params[$pk]);
                $params = array_merge((array)$params,(array)$pa);
            }
    }
    // Examples:e2
    if($params['flag']=='col'){
        unset($params['flag']);
        $_array = $tpl->_vars[$key];
        foreach ((array)$params as $pk => $pv) {
            if(substr($pk, -2)=='[]'){
                $_pk = substr($pk,0,-2);
                if($_array[$_pk]){
                    array_push($_array[$_pk],$pv);
                }else{
                    $_array[$_pk] = array($pv);
                }
            }
        }
        $tpl->assign($key,$_array);
        return;
    }
    // Examples:e3
    if($mas){
        if($array){
            array_push($array,$params);
            $params = $array;
        }else{
            $params = array($params);
        }
    }

    // var_dump($value);
    $tpl->assign($key,$params);
}
