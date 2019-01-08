<?php
/**
 *
 * Content(内容管理模块)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <79441928@qq.com>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
class Product_overseaAction extends AdminbaseAction
{
    protected  $dao,$fields;
    public function _initialize()
    {
        parent::_initialize();
		$this->dao = D(MODULE_NAME);

		$fields = F($this->moduleid.'_Field');
		
		foreach($fields as $key => $res){
			$res['setup']=string2array($res['setup']);
			$this->fields[$key]=$res;
		}
		unset($fields);
		unset($res);

		$this->assign ('fields',$this->fields);

    }

    public function replace_thumb(){
    	$list=M('Article')->select();
    	foreach ($list as $key => $vo) {
			if(strpos($vo['thumb'],OSS_TEST_BUCKET)===false && !empty($vo['thumb'])){
				$vo['thumb']='http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.$vo['thumb'];
				$list[$key]['thumb']=$vo['thumb'];
			}
			if(!empty($vo['content'])){
				$vo['content']=str_replace('src="/Uploads/','src="'.'http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.'/Uploads/',$vo['content']);
				$list[$key]['content']=$vo['content'];
			}
			$data['id']=$vo['id'];
			$data['thumb']=$vo['thumb'];
			$data['content']=$vo['content'];
			M('Article')->save($data);
    	}
    	var_dump($list);

		$list1=M('Attachment')->select();
    	foreach ($list1 as $key => $vo) {
			if(strpos($vo['filepath'],OSS_TEST_BUCKET)===false && !empty($vo['filepath'])){
				$vo['filepath']='http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.$vo['filepath'];
			}

			$data1['aid']=$vo['aid'];
			$data1['filepath']=$vo['filepath'];
			M('Attachment')->save($data1);
    	}
    	var_dump($list1);

    	$list2=M('Cart')->select();
    	foreach ($list2 as $key => $vo) {
			if(strpos($vo['product_thumb'],OSS_TEST_BUCKET)===false && !empty($vo['product_thumb'])){
				$vo['product_thumb']='http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.$vo['product_thumb'];
			}

			$data2['id']=$vo['id'];
			$data2['product_thumb']=$vo['product_thumb'];
			M('Cart')->save($data2);
    	}
    	var_dump($list2);

		$list3=M('OrderData')->select();
    	foreach ($list3 as $key => $vo) {
			if(strpos($vo['product_thumb'],OSS_TEST_BUCKET)===false && !empty($vo['product_thumb'])){
				$vo['product_thumb']='http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.$vo['product_thumb'];
			}

			$data3['id']=$vo['id'];
			$data3['product_thumb']=$vo['product_thumb'];
			M('OrderData')->save($data3);
    	}
    	var_dump($list3);

		$list4=M('Page')->select();
    	foreach ($list4 as $key => $vo) {
			if(!empty($vo['content'])){
				$vo['content']=str_replace('src="/Uploads/','src="'.'http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.'/Uploads/',$vo['content']);
			}

			$data4['id']=$vo['id'];
			$data4['content']=$vo['content'];
			M('Page')->save($data4);
    	}
    	var_dump($list4);

		$list5=M('PcslideData')->select();
    	foreach ($list5 as $key => $vo) {
			if(strpos($vo['pic'],OSS_TEST_BUCKET)===false && !empty($vo['pic'])){
				$vo['pic']='http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.$vo['pic'];
			}

			if(strpos($vo['small'],OSS_TEST_BUCKET)===false && !empty($vo['small'])){
				$vo['small']='http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.$vo['small'];
			}

			$data5['id']=$vo['id'];
			$data5['pic']=$vo['pic'];
			$data5['small']=$vo['small'];
			M('PcslideData')->save($data5);
    	}
    	var_dump($list5);

		$list6=M('ProductOversea')->select();
    	foreach ($list6 as $key => $vo) {
			if(strpos($vo['thumb'],OSS_TEST_BUCKET)===false && !empty($vo['thumb'])){
				$vo['thumb']='http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.$vo['thumb'];
			}

			if(strpos($vo['top_pics'],OSS_TEST_BUCKET)===false && !empty($vo['top_pics'])){
				$vo['top_pics']=str_replace('/Uploads/','http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.'/Uploads/',$vo['top_pics']);
			}

			if(!empty($vo['content'])){
				$vo['content']=str_replace('src="/Uploads/','src="'.'http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.'/Uploads/',$vo['content']);
			}

			$data6['id']=$vo['id'];
			$data6['thumb']=$vo['thumb'];
			$data6['top_pics']=$vo['top_pics'];
			$data6['content']=$vo['content'];
			M('ProductOversea')->save($data6);
    	}
    	var_dump($list6);

    	$list7=M('Redpaper')->select();
    	foreach ($list7 as $key => $vo) {
			if(strpos($vo['img'],OSS_TEST_BUCKET)===false && !empty($vo['img'])){
				$vo['img']='http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.$vo['img'];
			}

			$data7['id']=$vo['id'];
			$data7['img']=$vo['img'];
			M('Redpaper')->save($data7);
    	}
    	var_dump($list7);

		$list8=M('SlideData')->select();
    	foreach ($list8 as $key => $vo) {
			if(strpos($vo['pic'],OSS_TEST_BUCKET)===false && !empty($vo['pic'])){
				$vo['pic']='http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.$vo['pic'];
			}

			if(strpos($vo['small'],OSS_TEST_BUCKET)===false && !empty($vo['small'])){
				$vo['small']='http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.$vo['small'];
			}

			$data8['id']=$vo['id'];
			$data8['pic']=$vo['pic'];
			$data8['small']=$vo['small'];
			M('SlideData')->save($data8);
    	}
    	var_dump($list8);

    	$list9=M('Type')->select();
    	foreach ($list9 as $key => $vo) {
			if(strpos($vo['pic'],OSS_TEST_BUCKET)===false && !empty($vo['pic'])){
				$vo['pic']='http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.$vo['pic'];
			}

			$data9['typeid']=$vo['typeid'];
			$data9['pic']=$vo['pic'];
			M('Type')->save($data9);
    	}
    	var_dump($list9);exit;
    }

    /**
	 * 列表
	 *
	 */
    public function index()
    {
		$template =  file_exists(THEME_PATH.MODULE_NAME.'_index.html') ? MODULE_NAME.':index' : 'Content:index';
	    $this->_list(MODULE_NAME);
	    
        $this->display ($template);
    }

	public function add()
    {
		$form=new Form();
		$this->assign ( 'form', $form );

		/*获取产品属性规格信息*/
		$where[status] = 1;
		$specsInfo = M('Specs')->where($where)->order('specs_id desc')->select();

		if (!empty($specsInfo)){
			// 获取属性值
			$specsArr = array(); $extendarr = array();
			foreach ($specsInfo as $key => $val) {
				$where[specs_id] = $val[specs_id];
				$extendInfo = M('Property_extend')->where($where)->order('extend_id desc')->select();
				$specsInfo[$key][extend] = $extendInfo;
			}
			$this->assign ('specsInfo', $specsInfo);
		}
		/*end*/

		$template =  file_exists(THEME_PATH.MODULE_NAME.'_edit.html') ? MODULE_NAME.':edit' : 'Content:edit';

		$this->display ( $template);
	}


	public function edit()
    {
		
		$id = $_REQUEST ['id'];		
		
		$vo = $this->dao->getById ( $id );
		/*if(strpos($vo['thumb'],OSS_TEST_BUCKET)===false){
			$vo['thumb']='http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.$vo['thumb'];
		}
		$vo['content']=str_replace('src="/Uploads/','src="'.'http://'.OSS_TEST_BUCKET.'.'.OSS_ENDPOINT.'/Uploads/',$vo['content']);*/
		$vo['content'] = htmlspecialchars($vo['content']);
		$this->assign ('product_oversea_id', $vo[id]);
 		$form=new Form($vo);

		/*获取产品属性规格信息*/
		$where[status] = 1;
		$specsInfo = M('Specs')->where($where)->order('specs_id desc')->select();

		if (!empty($specsInfo)){
			// 获取属性值
			$specsArr = array(); $extendarr = array();
			foreach ($specsInfo as $key => $val) {
				$where[specs_id] = $val[specs_id];
				$extendInfo = M('Property_extend')->where($where)->order('extend_id desc')->select();
				$specsInfo[$key][extend] = $extendInfo;
			}
			$this->assign ('specsInfo', $specsInfo);
		}

		//已添加属性规格
		$product_property = M('Product_property')->where('product_oversea_id='.$id)->select();
		foreach ($product_property as $key => $value) {
			$attribute_group = unserialize($value[attribute_group]);
			if (is_array($attribute_group) && !empty($attribute_group))
			{
				$extend_info = array();
				foreach ($attribute_group as $k => $v) {
					$property_extend = M('Property_extend')->find($v);
					if (!empty($property_extend))
					$extend_info[$v] = $property_extend;
				}
				$product_property[$key][specs_extend] = $extend_info;
			}
		}
		$this->assign ('product_property', $product_property);
		/*end*/

		$this->assign ( 'vo', $vo );
		$this->assign ( 'form', $form );
		$template =  file_exists(THEME_PATH.MODULE_NAME.'_edit.html') ? MODULE_NAME.':edit' : 'Content:edit';
		$this->display ( $template);
	}

	public function main($extendarr=array(),$the_key = 0){
		foreach($extendarr[$the_key] as $v){
			$specs0 = $this->getsulie($extendarr,$v,1);
		}
		return $specs0;
		
	}
	function getsulie($list,$content,$deep){
		$i=0;
		static $arr = array();
		if($deep>count($list)){
			return;
		}
		foreach($list as $k=>$v){
			if($i==$deep){
				foreach($list[$k] as $vv){
					$vv = $content.','.$vv;
					if($deep==count($list)-1){
						$ct = '';
						$arrvv = explode(',', $vv);
						foreach ($arrvv as $vo) {
							$ct .= $vo.'_';
						}
						$arr[$ct] = $vv;
					}else {
						$this->getsulie($list,$vv,$deep+1);
					}
				}
				break;
			}
			$i++;
		}
		return $arr;
	}

    /**
     * 录入
     *
     */
    public function insert($module='',$fields=array(),$userid=0,$username='',$groupid=0)
    {
		$model = $module ?  M($module) : $this->dao;
		$fields = $fields ? $fields : $this->fields ;

		if($fields['verifyCode']['status'] && (md5($_POST['verifyCode']) != $_SESSION['verify'])){
			$this->assign ( 'jumpUrl','javascript:history.go(-1);');
			$this->error(L('error_verify'));
        }

		$_POST = checkfield($fields,$_POST);

		if(empty($_POST)) $this->error (L('do_empty'));
		$_POST['createtime'] = time();		 
		$_POST['updatetime'] = $_POST['createtime'];	
        $_POST['userid'] = $module ? $userid : $_SESSION['userid'];
		$_POST['username'] = $module ? $username : $_SESSION['username'];
		if($_POST['style_color']) $_POST['style_color'] = 'color:'.$_POST['style_color'];
		if($_POST['style_bold']) $_POST['style_bold'] =  ';font-weight:'.$_POST['style_bold'];
		if($_POST['style_color'] || $_POST['style_bold'] ) $_POST['title_style'] = $_POST['style_color'].$_POST['style_bold'];
 
		$module = $module? $module : MODULE_NAME ;
		if(GROUP_NAME=='User')$_POST['status'] = $this->Role[$groupid]['allowpostverify'] ? 1 : 0;
		
		if (false === $model->create ()) {
			$this->error ( $model->getError () );
		}
		$_POST['id'] = $id= $model->add();

		/*更新/添加商品属性*/
		$lock_extend = $_POST[lock_extend];
		if (is_array($lock_extend) && !empty($lock_extend)){
			foreach ($lock_extend as $key => $value) {
				$attribute_group = $_POST['attribute_group'.$value];
				if (!empty($attribute_group))
					$attribute_group = trim(serialize($attribute_group));
				$price = trim($_POST['price'.$value]); //市场价
				$member_price = trim($_POST['member_price'.$value]); //会员价
				$stock = trim($_POST['stock'.$value]); //库存
				$where = 'product_oversea_id='.$_POST['id'].' AND attribute_group=\''.$attribute_group.'\'';
				$save_extend = $_POST['save_extend'.$value];

				$property[product_oversea_id] = $_POST['id'];
				$property[price] = $price;
				$property[member_price] = $member_price;
				$property[stock] = $stock;
				$property[attribute_group] = $attribute_group;

				if ($save_extend==1)
				{
					$pro_id = $_POST['property_id'.$value];
					$property_id = M('Product_property')->where('property_id='.$pro_id)->save($property);
				}
				else{
					$property_id = M('Product_property')->add($property);
				}

				if ($property_id===false)
				{
					$this->error ("更新商品属性出错！");exit;
				}
			}
		}
		/*更新/添加商品属性end*/

		if ($id !==false) {
			$catid = $module =='Page' ? $id : $_POST['catid'];

			if($_POST['aid']) {
				$Attachment =M('Attachment');		
				$aids =  implode(',',$_POST['aid']);
				$data['id']=$id;
				$data['catid']= $catid;
				$data['status']= '1';
				$Attachment->where("aid in (".$aids.")")->save($data);
			}

			$data='';
			$cat = $this->categorys[$catid];
			$url = geturl($cat,$_POST,$this->Urlrule);
			$data['id']= $id;
			$data['url']= $url[0];
			$model->save($data);

			
			if($_POST['keywords'] && $module !='Page'){
				$keywordsarr=explode(',',$_POST['keywords']);
				$i=0;
				$tagsdata =M('Tags_data');
				$tagsdata->where("id=".$id)->delete();
				foreach((array)$keywordsarr as $tagname){
					if($tagname){
						$tagidarr=$tagdatas=$where=array();
						$where['name']=array('eq',$tagname);
						$where['moduleid']=array('eq',$cat['moduleid']);
						$tagid=M('Tags')->where($where)->field('id')->find();
						$tagidarr['id']=$id;
						if($tagid){
							$num = $tagsdata->where("tagid=".$tagid[id])->count();
							$tagdatas['num']=$num+1;
							M('Tags')->where("id=".$tagid[id])->save($tagdatas);
							$tagidarr['tagid']=$tagid['id'];
						}else{
							$tagdatas['moduleid']=$cat['moduleid'];
							$tagdatas['name'] = $tagname;
							$tagdatas['slug'] = Pinyin($tagname);
							$tagdatas['num']=1;
							$tagdatas['lang']=$_POST['lang'];
							$tagdatas['module']= $cat['module'];
							$tagidarr['tagid']=M('Tags')->add($tagdatas);
						}
						$i++;
						$tagsdata->add($tagidarr);
					}
				}
			}

			if($cat['presentpoint']){
				$user =M('User');
				if($cat['presentpoint']>0) $user->where("id=".$_POST['userid'])->setInc('point',$cat['presentpoint']);
				if($cat['presentpoint']<0) $user->where("id=".$_POST['userid'])->setDec('point',$cat['presentpoint']);
			}
 
			if($cat['ishtml'] && $_POST['status']){
				if($module!='Page'   && $_POST['status'])	$this->create_show($id,$module);
				if($this->sysConfig['HOME_ISHTML']) $this->create_index();
				$arrparentid = array_filter(explode(',',$cat['arrparentid'].','.$cat['id']));
				foreach($arrparentid as $catid) {
					if($this->categorys[$catid]['ishtml'])	$this->clisthtml($catid);					
				}
 			}
			if(GROUP_NAME=='Admin'){
				$this->assign ( 'jumpUrl', U($module.'/index') );
			}elseif(GROUP_NAME=='User'){
				$this->assign ( 'jumpUrl',$_SERVER['HTTP_REFERER']);
				//$this->assign ( 'jumpUrl', U(GROUP_NAME.'-'.MODULE_NAME.'/add?moduleid='.$cat['moduleid']) );
			}
			$this->success (L('add_ok'));
		} else {
			$this->error (L('add_error').': '.$model->getDbError());
		}
	
    }

	function update($module='',$fields=array(),$userid=0,$username='')
	{
		$model = $module ?  M($module) : $this->dao;
		$fields = $fields ? $fields : $this->fields ;
		if($fields['verifyCode']['status'] && (md5($_POST['verifyCode']) != $_SESSION['verify'])){
			$this->assign ( 'jumpUrl','javascript:history.go(-1);');
			$this->error(L('error_verify'));
        }

		$_POST = checkfield($fields,$_POST);
		if(empty($_POST)) $this->error (L('do_empty'));

		$_POST['updatetime'] = time();		
		if($_POST['style_color']) $_POST['style_color'] = 'color:'.$_POST['style_color'];
		if($_POST['style_bold']) $_POST['style_bold'] =  ';font-weight:'.$_POST['style_bold'];
		if($_POST['style_color'] || $_POST['style_bold'] ) $_POST['title_style'] = $_POST['style_color'].$_POST['style_bold'];

		$cat = $this->categorys[$_POST['catid']];
		$module = $module? $module : MODULE_NAME ;
		$_POST['url'] = geturl($cat,$_POST,$this->Urlrule);
		$_POST['url'] =$_POST['url'][0];

		if (false === $model->create ()) {
			$this->error ( $model->getError () );
		}
		// 更新数据
		$list=$model->save ();
		if (false !== $list) {
 			/*更新/添加商品属性*/
 			$lock_extend = $_POST[lock_extend];
 			if (is_array($lock_extend) && !empty($lock_extend)){
 				foreach ($lock_extend as $key => $value) {
 					$attribute_group = $_POST['attribute_group'.$value];
 					if (!empty($attribute_group))
 						$attribute_group = trim(serialize($attribute_group));
 					$price = trim($_POST['price'.$value]); //市场价
 					$member_price = trim($_POST['member_price'.$value]); //会员价
 					$stock = trim($_POST['stock'.$value]); //库存
 					$where = 'product_oversea_id='.$_POST['id'].' AND attribute_group=\''.$attribute_group.'\'';
 					$save_extend = $_POST['save_extend'.$value];

 					$property[product_oversea_id] = $_POST['id'];
 					$property[price] = $price;
 					$property[member_price] = $member_price;
 					$property[stock] = $stock;
 					$property[attribute_group] = $attribute_group;

 					if ($save_extend==1)
 					{
 						$pro_id = $_POST['property_id'.$value];
 						$property_id = M('Product_property')->where('property_id='.$pro_id)->save($property);
 					}
 					else{
 						$property_id = M('Product_property')->add($property);
 					}

 					if ($property_id===false)
 					{
 						$this->error ("更新商品属性出错！");exit;
 					}
 				}
 			}
 			/*更新/添加商品属性end*/


			$id= $_POST['id'];

			$catid = $module =='Page' ? $id : $_POST['catid'];

			if($_POST['keywords']  && $module !='Page'){
				$keywordsarr=explode(',',$_POST['keywords']);
				$i=0;
				$tagsdata =M('Tags_data');
				$tagsdata->where("id=".$id)->delete();
				foreach((array)$keywordsarr as $tagname){
					if($tagname){
						$tagidarr=$tagdatas=$where=array();
						$where['name']=array('eq',$tagname);
						$where['moduleid']=array('eq',$cat['moduleid']);
						$tagid=M('Tags')->where($where)->field('id')->find();
						$tagidarr['id']=$id;
						if($tagid['id']>0){
						
							$num = $tagsdata->where("tagid=".$tagid[id])->count();
							$tagdatas['num']=$num+1;
							M('Tags')->where("id=".$tagid[id])->save($tagdatas);
							$tagidarr['tagid']=$tagid['id'];
						}else{
							$tagdatas['moduleid']=$cat['moduleid'];
							$tagdatas['name'] = $tagname;
							$tagdatas['slug'] = Pinyin($tagname);
							$tagdatas['num']=1;
							$tagdatas['lang']=$_POST['lang'];
							$tagdatas['module']= $cat['module'];
							$tagidarr['tagid']=M('Tags')->add($tagdatas);
						}
						$i++;
						$tagsdata->add($tagidarr);
					}
				}
			}

			if($_POST['aid']) {
				$Attachment =M('Attachment');		
				$aids =  implode(',',$_POST['aid']);
				$data['id']= $id;
				$data['catid']= $catid;
				$data['status']= '1';
				$Attachment->where("aid in (".$aids.")")->save($data);
			}
			$cat = $this->categorys[$catid];
			if($cat['ishtml']){
				if($module!='Page'  && $_POST['status'])	$this->create_show($_POST['id'],$module);				
				if($this->sysConfig['HOME_ISHTML']) $this->create_index();
				$arrparentid = array_filter(explode(',',$cat['arrparentid'].','.$cat['id']));
				foreach($arrparentid as $catid) {
					if($this->categorys[$catid]['ishtml'])	$this->clisthtml($catid);					
				}
 			}
			$this->assign ( 'jumpUrl', $_POST['forward'] );
			$this->success (L('edit_ok'));
		} else {
			//错误提示
			$this->success (L('edit_error').': '.$model->getDbError());
		}
	}

 
	function statusallok(){

		$module = MODULE_NAME;
		$model = M ( $module );
		$ids=$_POST['ids'];
		if(!empty($ids) && is_array($ids)){
			$id=implode(',',$ids);
			$data = $model->select($id);
			if($data){				
				foreach($data as $key=>$r){	
					$model->save(array(id=>$r['id'],status=>1));
					if($this->categorys[$r['catid']]['ishtml'] && $r['status'])$this->create_show($r['id'],$module);	
				}
				$cat =  $this->categorys[$r['catid']];
				if($cat['ishtml']){			
					if($this->sysConfig['HOME_ISHTML']) $this->create_index();
					$arrparentid = array_filter(explode(',',$cat['arrparentid'].','.$cat['id']));
					foreach($arrparentid as $catid) {
						if($this->categorys[$catid]['ishtml'])	$this->clisthtml($catid);					
					}
				}
				$this->success(L('do_ok'));
			}else{
				$this->error(L('do_error').': '.$model->getDbError());
			}
		}else{
			$this->error(L('do_empty'));
		}
	}

	/*状态*/

	public function status(){
		$module = MODULE_NAME;
		$model = D ($module);
		if($model->save($_GET)){
			$_POST ='';
			$_POST = $model->find($_GET['id']);
			$cat =  $this->categorys[$_POST['catid']];
			if($cat['ishtml']){
				if($module!='Page'  && $_POST['status'])	$this->create_show($_POST['id'],$module);				
				if($this->sysConfig['HOME_ISHTML']) $this->create_index();
				$arrparentid = array_filter(explode(',',$cat['arrparentid'].','.$cat['id']));
				foreach($arrparentid as $catid) {
					if($this->categorys[$catid]['ishtml'])	$this->clisthtml($catid);					
				}
 			}

			$this->success(L('do_ok'));
		}else{
			$this->error(L('do_error'));
		}
	}

    ##导出BBC订单信息##
    public function excelProduct()
    {
	    $model = M("ProductOversea");
        /*$star_time = $_POST['star'].' 00:00:00';
        $end_time = $_POST['end'].' 23:59:59';
        $star_time = strtotime($star_time);
		$end_time = strtotime($end_time);
		$where['createtime'] = array(array('gt',$star_time),array('lt',$end_time));*/
		$field = 'title,member_price,url';
        $OrdersData = $model->field($field)->where('status=1')->select();  //查询数据得到$OrdersData二维数组  
        // F('OrdersData',$model->getlastsql());
        vendor("PHPExcel.PHPExcel");
	    $objPHPExcel = new PHPExcel();
        // Create new PHPExcel object  
        // Set properties  
        $objPHPExcel->getProperties()->setCreator("ctos")  
            ->setLastModifiedBy("ctos")  
            ->setTitle("Office 2007 XLSX Test Document")  
            ->setSubject("Office 2007 XLSX Test Document")  
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")  
            ->setKeywords("office 2007 openxml php")  
            ->setCategory("Test result file");  
  
        //set width  
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(50);  
        
        //设置行高度  
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(22);  
  
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);
  
        //设置水平居中  
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);  
        $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        

       	//设置单元格颜色
       	$objPHPExcel->getActiveSheet()->getStyle( 'A1:C1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
       	$objPHPExcel->getActiveSheet()->getStyle( 'A1:C1')->getFill()->getStartColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);

		//加粗单元格边框
		$objPHPExcel->getActiveSheet()->getStyle('A1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  
        //合并cell
        // $objPHPExcel->getActiveSheet()->mergeCells('B1:E1');
  
        // set table header content  
        $objPHPExcel->setActiveSheetIndex(0)
        	->setCellValue('A1', '上架品名')
            ->setCellValue('B1', '价格')
            ->setCellValue('C1', '网站链接');
  
        for($i = 0; $i < count($OrdersData); $i++){ 
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+2), $OrdersData[$i]['title']);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('B'.($i+2), $OrdersData[$i]['member_price']);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+2), $OrdersData[$i]['url']);
            $objPHPExcel->getActiveSheet()->getRowDimension($i+2)->setRowHeight(16);  
        } 

        //  sheet命名  
        $objPHPExcel->getActiveSheet()->setTitle('上架商品信息表格');
  
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet  
        $objPHPExcel->setActiveSheetIndex(0);
  
        // excel头参数  
        header('Content-Type: application/vnd.ms-excel');  
        header('Content-Disposition: attachment;filename="上架商品信息表格'.date('YmdHis').'.xls"');  //日期为文件名后缀  
        header('Cache-Control: max-age=0');  
  
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  //excel5为xls格式，excel2007为xlsx格式  
        $objWriter->save('php://output'); 
    }
}
?>