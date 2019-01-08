<?php
/**
 * 
 * Bank.php (银行汇款支付模块)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2012-01-09 qqcms.net $
 * @此注解信息不能修改或删除,请尊重我们的劳动成果,你的修改请注解在此注解下面。
 */
class Bank extends Think {
	public $config = array()  ;
    public function __construct($config=array()) {
         $this->config = $config;
    }
	public function setup(){

		$modules['pay_name']    = L('Bank_pay_name');   
		$modules['pay_code']    = 'Bank';
		$modules['pay_desc']    = L('Bank_pay_desc');
		$modules['is_cod']  = '0';
		$modules['is_online']  = '1';
		$modules['author']  = 'QQCMS';
		$modules['website'] = '';
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