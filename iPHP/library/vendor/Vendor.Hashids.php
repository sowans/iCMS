<?php
/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.1.0
 */
defined('iPHP') OR exit('What are you doing?');
defined('iPHP_LIB') OR exit('iPHP vendor need define iPHP_LIB');

iVendor::register('Hashids');

use Hashids\Hashids;

class Vendor_Hashids {
	public $instance;
	public function __construct($param=array()){
        empty($param['salt'])&& $param['salt'] = iPHP_KEY;
        empty($param['len']) && $param['len'] = 16;
    	$this->instance = new Hashids($param['salt'],$param['len']);
	}
    public function encode($id) {
    	return $this->instance->encode(array($id));
    }
    public function decode($hash) {
    	return $this->instance->decode($hash);
    }
}

