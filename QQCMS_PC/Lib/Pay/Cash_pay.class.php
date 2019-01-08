<?php
/**
 * 
 * Cash_pay.php (电子现金支付模块)
 */
class Cash_pay extends Think {
	public $config = array()  ;
    public function __construct($config=array()) {
         $this->config = $config;
    }
	public function setup(){

		$modules['pay_name']    = "电子现金";   
		$modules['pay_code']    = 'Cash_pay';
		$modules['pay_desc']    = "电子现金支付";
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