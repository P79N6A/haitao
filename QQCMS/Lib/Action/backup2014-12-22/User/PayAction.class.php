<?php
/**
 * PayAction.class.php (支付模块)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if(!defined("QQCMS")) exit("Access Denied");
class PayAction extends BaseAction
{

	function _initialize()
    {	
		parent::_initialize();
		if(!$this->_userid){
			//$this->assign('jumpUrl',U('User/Login/index'));
			//$this->error(L('nologin'));
		}
		$this->dao = M('User');
		$this->assign('bcid',0);
		$user = $this->dao->find($this->_userid);
		$this->assign('vo',$user);
    }

    public function index()
    {
        $this->display();
    }


	public function Recharge()
    {
        $this->display();
    }
	

	public function pay()
    {
        $this->display();
    }
	public function respond()
	{
		$pay_code = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : '';	
		$pay_code = ucfirst($pay_code);
		$Payment = M('Payment')->getByPayCode($pay_code);
		if(empty($Payment))$this->error(L('PAY CODE EROOR!'));
		$aliapy_config = unserialize($Payment['pay_config']);		 
		import("@.Pay.".$pay_code);
		$pay=new $pay_code($aliapy_config);
		$r = $pay->respond();	
		$this->assign('jumpUrl',URL('User-Order/index'));
		if($r){
			$this->error(L('PAY_OK'));
		}else{
			$this->error(L('PAY_FAIL'));
		}
	}
 
}
?>