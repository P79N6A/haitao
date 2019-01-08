<?php
/**
 * 
 * Posid (推荐位管理)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
class PaymentAction extends AdminbaseAction {

	protected $dao,$path;
    function _initialize()
    {	
		parent::_initialize();
		$this->path = './QQCMS_PC/Lib/Pay/';
		$this->dao= M('Payment');
    }

	function index()
    {
		$tempfiles = dir_list($this->path,'php');
		// print_r($tempfiles);
		$list = $this->dao->Field('id,pay_code,status,listorder,pay_name')->select();

		foreach((array)$list as $key=>$r){
			 $installed[$r['pay_code']] = $r;
		}

		foreach($tempfiles as $r){
			$filename = basename($r);
			$pay_code = str_replace('.class.php','',$filename);
			import("@.Pay.".$pay_code);
			$pay=new $pay_code();
			$paylist[$pay_code] = $pay->setup();
			if($installed[$pay_code]){
				$paylist[$pay_code]['id'] = $installed[$pay_code]['id'];
				$paylist[$pay_code]['status'] = $installed[$pay_code]['status'];
				$paylist[$pay_code]['listorder'] = $installed[$pay_code]['listorder'];
				$paylist[$pay_code]['pay_name'] = $installed[$pay_code]['pay_name'];
			}
		}
		
		// print_r($paylist);exit();
		$this->assign('list',$paylist);
	    $this->display();
    }
	function add()
    {
		$code = $_REQUEST['code'];
		if(is_file($this->path.$code.'.class.php')){
			import("@.Pay.".$code);
			$pay=new $code();
			$setup = $pay->setup();
			$this->assign('vo',$setup);
		}else{
			$this->error(L('do_empty'));
		}
	 	$this->assign('allinpay_code',$code);
		$this->display ('edit');
	}
	function edit()
	{
		$id=intval($_REQUEST['id']);
		$data = $this->dao->find($id);
		$data['pay_config'] = unserialize($data['pay_config']);
		$data['customs_config'] = unserialize($data['customs_config']);
		$code= $data['pay_code'];
		if(is_file($this->path.$code.'.class.php')){
				import("@.Pay.".$code);
				$pay=new $code();
				$setup = $pay->setup();
		}
		foreach($setup['config'] as $key=>$r){
			$r['value'] = $data['pay_config'][$r['name']];
			$setup['config'][$key] = $r;
		}
		foreach($setup['_config'] as $key=>$r){
			$r['value'] = $data['customs_config'][$r['name']];
			$setup['_config'][$key] = $r;
		}
		$data = $data+$setup;
		$this->assign('allinpay_code',$code);
		$this->assign('vo',$data);
		$this->display ();
	}
	function _before_insert()
	{
			$_POST['pay_config']=serialize($_POST['pay_config']);
			$_POST['customs_config']=serialize($_POST['customs_config']);
			$_POST['pay_fee'] = $_POST['pay_fee_type'] ? $_POST['pay_fix'] : $_POST['pay_rate'] ;
			 
	}

	function _before_update()
	{
		$_POST['pay_config']=serialize($_POST['pay_config']);
		$_POST['customs_config']=serialize($_POST['customs_config']);
		$_POST['pay_fee'] = $_POST['pay_fee_type'] ? $_POST['pay_fix'] : $_POST['pay_rate'] ;
		
	}
}
?>