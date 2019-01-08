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
class AjaxAction extends AdminbaseAction {

	protected $dao,$path;
    function _initialize()
    {	
		parent::_initialize();
		$this->path = './QQCMS_PC/Lib/Pay/';
		$this->dao= M('Property_extend');
    }

	function saveextend()
    {
    	$strval = $_POST[strval];
    	$specsname = $_POST[specsname];
    	$strvalarr = explode(',',$strval);
    	if (is_array($strvalarr) && !empty($specsname))
    	{
    		$specsInfo = M('Specs')->where("specsname='$specsname'")->find();
			
    		if (!empty($specsInfo))
    			$this->ajaxReturn($specsInfo,'当前属性名已存在',0);
    		$specs['specsname'] = $specsname;
    		$specs['status'] = 1;
    		$specs_id = M('Specs')->add($specs);
    		if ($specs_id === false)
    			$this->ajaxReturn($specs_id,'属性名添加失败！',0);
			// 查询当前添加的属性规格的属性，需要传到前端
    		$specs_data = M('Specs')->find($specs_id);
    		foreach ($strvalarr as $key => $val) {
    			$extend[specs_id] = $specs_id;
    			$extend[propertyvalue] = $val;
    			$extend[status] = 1;
    			$extendData = $this->dao->where(array('specs_id'=>$specs_id,'propertyvalue'=>$val))->find();
    			if (!empty($extendData)) continue;
    			$extend_id = $this->dao->add($extend);
    			if ($extend_id === false)
    			$this->ajaxReturn($extend_id,'属性值添加失败！',0);
    			// 查询当前添加的属性规格的属性，需要传到前端
    			$extend_data = $this->dao->find($extend_id);
    			//附加属性值
    			$specs_data[extend][] = $extend_data;
    		}
    	}
    	else{
    		$this->ajaxReturn(NULL,'参数错误',0);
    	}

    	if ($specs_data)
    		$this->ajaxReturn($specs_data,'成功',1);
    	else
    		$this->ajaxReturn(null,'添加失败',0);
    }

	function removeTag()
    {
		$extend_id = $_POST['extend_id']; //属性值ID
		$specs_id = $_POST['specs_id']; //属性名称ID

		/*
		*判断删除类型 
		*if $extend_id == 0 ## 同步删除属性值属性名称
		*if $extend_id <> 0 ## 继续执行下一步
		*/
		if (!$extend_id){
			$extendcount = $this->dao->where(array('specs_id'=>$specs_id,'status'=>1))->count();
			if (!$extendcount)
				$this->ajaxReturn(null,'数据不存在',0);
			$extend = $this->dao->where('specs_id='.$specs_id)->delete();
			if ($extend===false) $this->ajaxReturn(null,'删除属性值失败',0);
			$specs = M('Specs')->where('specs_id='.$specs_id)->delete();
			if ($specs===false) $this->ajaxReturn(null,'删除属性名称失败',0);
		}
		else{
			/*
			* 检查当前属性名称下属性值是否只剩下最后一条
			* 是##同步删除属性值属性名称
			* 否##删除当前属性值
			*/
			$extendcount = $this->dao->where(array('specs_id'=>$specs_id,'status'=>1))->count();
			$extend = $this->dao->where('extend_id='.$extend_id)->delete(); 
			if ($extend===false) $this->ajaxReturn(null,'删除属性值失败',0);
			if ($extendcount == 1)
			{
				$specs = M('Specs')->where('specs_id='.$specs_id)->delete();
				if ($specs===false) $this->ajaxReturn(null,'删除属性名称失败',0);
			}
		}
		$this->ajaxReturn($specs,'删除成功',1);
	}

	function delOnePro(){
		$property_id = trim($_POST[property_id]);
		if (!$property_id)
			$this->ajaxReturn(null,'参数错误',0);
		$id = M('Product_property')->where('property_id='.$property_id)->delete();
		if ($id===false)
		$this->ajaxReturn(NULL,'删除失败',0);
		else
		$this->ajaxReturn($id,'删除成功',1);
	}

	function delOneSpecs(){
		$specs_id = trim($_POST[specs_id]);
		if (!$specs_id)
			$this->ajaxReturn(null,'参数错误',0);
		$specsId = M('Specs')->where('specs_id='.$specs_id)->delete();
		if ($specsId===false)
			$this->ajaxReturn(null,'删除属性名称失败',0);

		$extend_id = M('Property_extend')->where('specs_id='.$specs_id)->delete();
		if ($extend_id===false)
			$this->ajaxReturn(null,'删除属性值失败',0);

		$this->ajaxReturn($extend_id,'删除成功',1);
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