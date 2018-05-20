<?php
/*
 * template_lite plugin
 *
 * Type:     function
 * Name:     array
 * Version:  0.1
 * Examples: {array key="value" key1="value1"}

 */
function tpl_function_array($params, &$tpl){
    $assign = $params['assign']?:'array';
    unset($params['assign']);
    $tpl->assign($assign,$params);
}
