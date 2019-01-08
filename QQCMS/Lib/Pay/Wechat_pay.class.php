<?php
/**
 * 
 * Wechat_pay.php (余额支付模块)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2012-01-09 qqcms.net $
 * @此注解信息不能修改或删除,请尊重我们的劳动成果,你的修改请注解在此注解下面。
 */
class Wechat_pay extends Think {
	public $config = array()  ;
    public function __construct($config=array()) {
         $this->config = $config;
    }
	public function setup(){

		$modules['pay_name']    = "微信支付";   
		$modules['pay_code']    = 'Wechat_pay';
		$modules['pay_desc']    = "微信支付";
		$modules['is_cod']  = '0';
		$modules['is_online']  = '1';
		$modules['author']  = 'QQCMS';
		$modules['website'] = 'http://www.qqcms.net';
		$modules['version'] = '1.0.0';
		$modules['config']  = array();
		return $modules;
	}

	public function get_code(){
		return;
	}
	public function respond()
    {
		return;
	}
}
?>