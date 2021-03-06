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
class ContentAction extends AdminbaseAction
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
    	if (MODULE_NAME=='Promotion') $this->GetUserGroup();
		$form=new Form();
		$this->assign ( 'form', $form );

		/*获取产品属性规格信息*/
		if (MODULE_NAME=='Product')
		{
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
		} 
		/*end*/

		$template =  file_exists(THEME_PATH.MODULE_NAME.'_edit.html') ? MODULE_NAME.':edit' : 'Content:edit';
		$this->display ( $template);
	}


	public function edit()
    {
		
		$id = $_REQUEST ['id'];		
		if(MODULE_NAME=='Page'){
					$Page=D('Page');
					$p = $Page->find($id);
					if(empty($p)){
					$data['id']=$id;
					$data['title'] = $this->categorys[$id]['catname'];
					$data['keywords'] = $this->categorys[$id]['keywords'];
					$Page->add($data);	
					}
		}
		
		$vo = $this->dao->getById ( $id );
		$vo['content'] = htmlspecialchars($vo['content']);
		$this->assign ('Product_id', $vo[id]);
 		$form=new Form($vo);
 		/*站内信 by dension*/
	 	if(MODULE_NAME=='Message_text'){
			$isall=$vo['is_all']?$vo['is_all']:0;
			if($isall==1){
				$mess_list=M('message_user')->field(" `qq_user`.realname")->join("`qq_user` on `qq_user`.id=`qq_message_user`.userid ")->where(' `qq_message_user`.message_id='.$vo['id'])->select();
			}
		}

		/*获取产品属性规格信息*/
		if (MODULE_NAME=='Product')
		{
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
			$product_property = M('Product_property')->where('product_id='.$id)->select();
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
		} 
		/*end*/



		/*获取会员与会员组信息*/
		if (MODULE_NAME=='Promotion')
		{
			$field = 'id,username,realname';
			$where[id] = $vo[eventser];
			$where[status] = 1;
			$uInfo = M('User')->field($field)->where($where)->find();
			$this->assign ('uInfo', $uInfo);
			$this->GetUserGroup();
		} 

		$this->assign('mess_list',$mess_list);
		/*站内信 end*/
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
 		/*站内信 by dension*/
		if(MODULE_NAME=='Message_text'){
		$isall=$_POST['is_all']?intval($_POST['is_all']):0;
		if($isall==1){
			if($_POST['now_select']){
				$message_menber=array();
				$message_menber=$_POST['now_select'];
			}else{
				$this->error("请先选择好会员再发送站内信");
			}
		}
		if($_POST['now_select'])unset($_POST['now_select']);
		}
 		/*站内信 end*/
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

		if ($id !==false) {
			$catid = $module =='Page' ? $id : $_POST['catid'];


 		/*站内信 by dension*/
		if(MODULE_NAME=='Message_text'){
			//如果不是整站发信
			if($isall==1){
				foreach ($message_menber as $key => $value) {
					$con['userid']=$value;
					$con['message_id']=$id;
					$re=array();
					$re=M('message_user')->field('id')->where($con)->find();
					if(!$re){
					$con['createtime']=time();
					M('message_user')->add($con);
					}
				}
			}
		}
 		/*站内信 end*/
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
		
 		/*站内信 by dension*/
 		if(MODULE_NAME=='Message_text'){
		$isall=$_POST['is_all']?intval($_POST['is_all']):0;
		if($isall==1){
			if($_POST['now_select']){
				$message_menber=array();
				$message_menber=$_POST['now_select'];
			}/*else{
				$this->error("请先选择好会员再发送站内信");
			}*/
		}
		if($_POST['now_select'])unset($_POST['now_select']);
		}
 		/*站内信 end*/
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
 			/*站内信 by dension*/
			if(MODULE_NAME=='Message_text'){
				//如果不是整站发信
				if($isall==1){
					foreach ($message_menber as $key => $value) {
						$con['userid']=$value;
						$con['message_id']=$id;
						$re=array();
						$re=M('message_user')->field('id')->where($con)->find();
						if(!$re){
						$con['createtime']=time();
						M('message_user')->add($con);}
					}
				}
			}
 			/*站内信 end*/

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
 					$where = 'product_id='.$_POST['id'].' AND attribute_group=\''.$attribute_group.'\'';
 					$save_extend = $_POST['save_extend'.$value];

 					$property[product_id] = $_POST['id'];
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


}?>