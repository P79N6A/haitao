<?php
/**
 * 
 * Base (前台公共模块)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if(defined('APP_PATH')!='./QQCMS' && !defined("QQCMS"))  exit("Access Denied");
include_once("pay/SDKRuntimeException.class.php");
define(APPID , "wx043ee7bba73960f5");  //appid
define(APPKEY ,"iI3X9TfTgLCt5S5JTi7dbqedDkJsDSTo4mM9IsuXHxmcTRoI9qThO7qSmDNsW9hrqx38ss1AsNfMD5sHskUcmj3bnM6hnkGek0Oe5ffYwYd6t8KSnCvHowjJLmXza5gV"); //paysign key
class BaseAction extends Action
{
	protected   $Config ,$sysConfig,$categorys,$module,$moduleid,$mod,$dao,$Type,$Role,$forward ,$user_menu,$Lang,$member_config;
	public $_userid,$_groupid,$_email,$_username,$wechat_name,$_realname,$_shopid,$_auth,$_checkOrder,$qqcms_auth_key;
    public function _initialize() {
    		// $this->decrypt_licensed(); //authorization
			$this->sysConfig = F('sys.config');
			$this->module = F('Module');
			$this->Role = F('Role');
			$this->Type =F('Type');
			$this->mod= F('Mod');
			$this->moduleid=$this->mod[MODULE_NAME];
			$this->qqcms_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
			$this->_checkOrder=0;

			if(APP_LANG){
				$this->Lang = F('Lang');
				$this->assign('Lang',$this->Lang);
				if($_GET['l']){
					if(!$this->Lang[$_GET['l']]['status'])$this->error ( L ( 'NO_LANG' ) );
					$lang=$_GET['l'];
				}else{
					$lang=$this->sysConfig['DEFAULT_LANG'];
				}
				define('LANG_NAME', $lang);
				define('LANG_ID', $this->Lang[$lang]['id']);
				$this->categorys = F('Category_'.$lang);
				$this->Config = F('Config_'.$lang);
				$this->assign('l',$lang);
				$this->assign('langid',LANG_ID);
				$T = F('config_'.$lang,'', './QQCMS/Tpl/Home/'.$this->sysConfig['DEFAULT_THEME'].'/');
				C('TMPL_CACHFILE_SUFFIX',$lang.C('TMPL_CACHFILE_SUFFIX'));
				cookie('think_language',$lang);
			}else{
				$T = F('config_'.$this->sysConfig['DEFAULT_LANG'],'', './QQCMS/Tpl/Home/'.$this->sysConfig['DEFAULT_THEME'].'/');
				$this->categorys = F('Category');
				$this->Config = F('Config');
				cookie('think_language',$this->sysConfig['DEFAULT_LANG']);
			}

			$this->assign('T',$T);
			$this->assign($this->Config);
			$this->assign('Role',$this->Role);
			$this->assign('Type',$this->Type);
			$this->assign('Module',$this->module);
			$this->assign('Categorys',$this->categorys);
			import("@.ORG.Form");			
			$this->assign ( 'form',new Form());
 

			C('PAGE_LISTROWS',$this->sysConfig['PAGE_LISTROWS']);
			C('URL_M',$this->sysConfig['URL_MODEL']);
			C('URL_M_PATHINFO_DEPR',$this->sysConfig['URL_PATHINFO_DEPR']);
			C('URL_M_HTML_SUFFIX',$this->sysConfig['URL_HTML_SUFFIX']);
			C('URL_LANG',$this->sysConfig['DEFAULT_LANG']);
			C('DEFAULT_THEME_NAME',$this->sysConfig['DEFAULT_THEME']);
			

			import("@.ORG.Online");
			$session = new Online();
 
			// F('auth',$_COOKIE['YP_auth']);
			if($_COOKIE['YP_auth']){
				$this->_auth = $_COOKIE['YP_auth'];
				list($userid,$groupid,$openid,$unionid,$mobile,$wechat_name,$realname,$email) = explode("|-|", authcode($this->_auth, 'DECODE', $this->qqcms_auth_key));
				$this->_userid = $userid;
				$this->wechat_name = $wechat_name;
				$this->_realname = $realname;
				$this->_groupid = $groupid; 
				$this->_email = $email;
				$parent_shop = M("user")->field("id,groupid,parent_id,createtime,newAccount")->where('wechat_openid=\''.$openid.'\'')->find();
				if(empty($parent_shop)){
					//当出现账号已删除的情况
					unset($this->_userid);
					unset($this->wechat_name);
					unset($this->_realname);
					unset($this->_groupid);
					unset($this->_email);
					cookie(null,'YP_');
					unset($_SESSION['auth']);
				}

				##计算加入时间##
				$joinTime = time() - $parent_shop['createtime'];
				$oneminute = strtotime('+1 minutes') - time();
				if ($parent_shop['newAccount'] == 1 && $joinTime > $oneminute)
				{
					$_con['id'] = $parent_shop['id'];
					$_con['newAccount'] = 2; //去除新用户标识
					M("user")->save($_con);
				}

				$this->assign('yp_realname',$this->_realname);
				$this->assign('yp_wechat_name',$this->wechat_name);
				$this->assign('yp_userid',$this->_userid);
				if($parent_shop['groupid']>5 && $parent_shop['groupid']<14)$this->_shopid =$parent_shop['id'];
					else $this->_shopid =$parent_shop['parent_id'];
			}else{
				//$this->_groupid = $_COOKIE['YP_groupid']=4;
				$this->_userid =0;
				$this->_shopid =0;
			}
			$this->_shopid=$this->_shopid?$this->_shopid:0;
			if(isset($_GET['shop_id'])&&empty($_SESSION['parent_shopid'])){//保存当前商家id
    		$_SESSION['parent_shopid']=intval($_GET['shop_id']);
			}
			$this->assign("shop_id",$this->_shopid);
			/*微信检测与登录*/
			/*$this->wechat_login();*/
			/**/
			foreach((array)$this->module as $r){
				if($r['issearch'])$search_module[$r['name']] = L($r['name']);
				if($r['ispost'] && (in_array($this->_groupid,explode(',',$r['postgroup']))))$this->user_menu[$r['id']]=$r;
			}
			if(GROUP_NAME=='User'){
				$langext = $lang ? '_'.$lang : '';
				$this->member_config=F('member.config'.$langext);
				$this->assign('member_config',$this->member_config);
				$this->assign('user_menu',$this->user_menu);
				if($this->_groupid=='5' &&  MODULE_NAME!='Login'){ 
					$this->assign('jumpUrl',URL('User-Login/emailcheck'));
					$this->assign('waitSecond',3);
					$this->success(L('no_regcheckemail'));
				}
				$this->assign('header',TMPL_PATH.'Home/'.THEME_NAME.'/Home_header.html');
			}
			if($_GET['forward'] || $_POST['forward']){	
				$this->forward = $_GET['forward'].$_POST['forward'];
			}else{
				if(MODULE_NAME!='Register' || MODULE_NAME!='Login' )
				$this->forward =isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] :  $this->Config['site_url'];
			}
			$this->assign('forward',$this->forward);

			$this->assign('search_module',$search_module);
			$this->assign('module_name',MODULE_NAME);
			$this->assign('action_name',ACTION_NAME);
			/*获取幻灯片*/
			$slider_list=M("slide_data")->field('pic,title,link')->where('fid=11')->select();
					/*转换url，加上shopid*/
			foreach ($slider_list as $key => $value) {
				if(strpos($value['link'], "?")){
					$slider_list[$key]['link'].="&shop_id=".$this->_shopid;
				}else{
					$slider_list[$key]['link'].="?shop_id=".$this->_shopid;
				}
			}
			$this->assign("slider_list",$slider_list);
			$slider_bottom_list=M("slide_data")->field('pic,title,link')->where('fid=12')->select();
			$this->assign("slider_bottom_list",$slider_bottom_list);
			/**/
		/*获取购物车数量*/
		$cart_sessionid = $_COOKIE['YP_onlineid'];
		//$session_info = M('Online')->find($this->sessionid);
		//var_dump(M('Online')->getLastSql());exit;
        $shopping_cart=M("cart")->field("number")->where("sessionid='{$cart_sessionid}'")->select();
         $shopping_count=0;
        foreach ($shopping_cart as $key => $value) {
            $shopping_count+=$value['number'];
        }
        $this->assign("shopping_count",$shopping_count);
			/**/
			
		$this->get_goods_type();//获取商品类别列表

		//获取评论列表
 		$comment = M("guestbook as g")->field("g.id,g.content,g.level,g.createtime,p.title,p.id as product_id,p.member_price,p.address")->join("qq_product as p ON g.product_id=p.id")->where("g.status=1")->order("g.createtime desc,g.level")->select();
 		// print_r($comment);exit;
 		$this->assign("comment",$comment);
		
		/*获取微信开放平台配置*/
		$oa_gh = M("wechat")->field("appId,appSecret")->find();
		if ($oa_gh)
		{
			/*$url = 'http://'.$_SERVER['HTTP_HOST'].'/redirect.php?act=Login';*/
			$url = 'http://'.$_SERVER['HTTP_HOST'].U('User/Login/wechatlogin');
			$redirect_uri = urlencode($url);
			$this->assign("redirect_uri",$redirect_uri);
			$this->assign("oa_appId",'wx298a48a600565b2f');
			$this->assign("oa_appSecret",'c43d586fe5f6560dabbc70e057e88058');
		}

		// ############
		$column_type=$this->get_column();//获取微店栏目
		$default_column=$this->get_default_column();//获取官方默认7个栏目
		$this->assign("default_column",$default_column);
		foreach ($column_type as $key => $value) {
			if(empty($value['url'])||empty($value['pic'])){
				$column_type[$key]=$default_column[$key];
			}
		}
		/*转换url，加上shopid*/
		foreach ($column_type as $key => $value) {
			if(strpos($value['url'], "?")){
				$column_type[$key]['url'].="&shop_id=".$this->_shopid;
			}else{
				$column_type[$key]['url'].="?shop_id=".$this->_shopid;
			}
		}
		$this->column_type = $column_type;
		
		$this->assign("column_data",$column_type);
		##########//

		//清理过期验证码
		$smsdata = M('Smsdata')->select();
		if (!empty($smsdata))
		{
			foreach ($smsdata as $key => $value) {
				if ($value['endtime'] <= time())
				{
					$swhere['endtime'] = $value['endtime'];
					M('Smsdata')->where($swhere)->delete();
				}
			}
		}

		##处理库存问题##
		$TaskStarTime = F('Task/starTime');
		$interval = intval(time() - $TaskStarTime);
		$quantum = 15;
		if (empty($TaskStarTime) || $interval>=$quantum)
		{
			F('Task/starTime',time());
			$ordwhere['status'] = 0;
			$ordwhere['pay_status'] = 0;
			$TaskOrder = M('order')->field('id,userid,cash_coupon,end_time')->where($ordwhere)->select();
			foreach ($TaskOrder as $kz => $vz) {
				if ($vz['end_time'] < time())
				{
					$iwhere['order_id'] = $vz['id'];
	        		$orderData = M('OrderData')->field('id,order_id,product_id,number,goods_attr_id')->where($iwhere)->select();
					foreach ($orderData as $kti => $vti) {
						if(!empty($vti['goods_attr_id']))
							M("ProductProperty")->where("property_id=".$vti['goods_attr_id'])->setInc('stock',$vti['number']);//返还库存
	        			else
	        				M("ProductOversea")->where("id=".$vti['product_id'])->setInc('stock',$vti['number']);//返还库存	
	        		}

	        		if($vz['cash_coupon']>0){
	        			M("User")->where('id='.$vz['userid'])->setInc('cash_use',intval($vz['cash_coupon']));
	        		}

	        		$orderd['id'] = $vz['id'];
	        		$orderd['status'] = 6;
	        		$or=M('Order')->save($orderd);
	        		if($or) $this->_checkOrder=1;
				}
			}
		}
	}


	//获取微店栏目
 	public function get_column(){
 		$shop_id=$this->_shopid?$this->_shopid:1;
		$column_type=M('shopcolumnpc_type')->where('status=1')->select();
 		$column=M('pcslide_data')->field(' `qq_shopcolumnpc`.*,`qq_pcslide_data`.pic,`qq_pcslide_data`.link ')->join(' `qq_shopcolumnpc` on `qq_shopcolumnpc`.slid_data_id=`qq_pcslide_data`.id ')->where(" `qq_shopcolumnpc`.uid=".$shop_id)->select();
 		foreach ($column_type as $key => $value) {
 			$column_type[$key]['pic']="";
 			$column_type[$key]['url']="";
 			foreach ($column as $k => $v) {
 				if($value['id']==$v['columntype_id']){
 					$column_type[$key]['pic']=$v['pic']?$v['pic']:"";
 					$column_type[$key]['url']=$v['link']?$v['link']:"";
 				}
 			}

 		}
		//$this->assign("column",$column_type);
		return $column_type;
 	}

 	//获取官方默认7个栏目
 	public function get_default_column(){
 		$shop_id=1;
		$column_type=M('shopcolumnpc_type')->select();
 		$column=M('Pcslide_data')->field(' `qq_shopcolumnpc`.*,`qq_pcslide_data`.pic,`qq_pcslide_data`.link ')->join(' `qq_shopcolumnpc` on `qq_shopcolumnpc`.slid_data_id=`qq_pcslide_data`.id ')->where(" `qq_shopcolumnpc`.uid=".$shop_id)->select();
 		foreach ($column_type as $key => $value) {
 			$column_type[$key]['pic']="";
 			$column_type[$key]['url']="";
 			foreach ($column as $k => $v) {
 				if($value['id']==$v['columntype_id']){
 					$column_type[$key]['pic']=$v['pic']?$v['pic']:"";
 					$column_type[$key]['url']=$v['link']?$v['link']:"";
 				}
 			}

 		}
		//print_r($column_type);exit;
		//$this->assign("default_column",$column_type);
		return $column_type;
 	}

    public function index($catid='',$module='')
    {				//如果进入首页没有shopid则跳转多一次加上shopid
    	$module=MODULE_NAME;
    	if(!isset($_GET['shop_id']) || $_GET['shop_id']==''){
    		$re_url='http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
    		if(!empty($_SERVER['QUERY_STRING']))
    			{$re_url.='?'.$_SERVER['QUERY_STRING']."&shop_id=".$this->_shopid;}else{
    				$re_url.="?shop_id=".$this->_shopid;
    			}
    		header("location:".$re_url);exit();
    	}
		$this->Urlrule =F('Urlrule');
		if(empty($catid)) $catid =  intval($_REQUEST['id']);
		$p= max(intval($_REQUEST[C('VAR_PAGE')]),1);
		if($catid){
			$cat = $this->categorys[$catid];
			$bcid = explode(",",$cat['arrparentid']); 
			$bcid = $bcid[1]; 
			if($bcid == '') $bcid=intval($catid);
			if(empty($module))$module=$cat['module'];
			$this->assign('module_name',$module);
			unset($cat['id']);
			$this->assign($cat);
			$cat['id']=$catid;
			$this->assign('catid',$catid);
			$this->assign('bcid',$bcid);
		}
		if($cat['readgroup'] && $this->_groupid!=1 && !in_array($this->_groupid,explode(',',$cat['readgroup']))){$this->assign('jumpUrl',URL('User-Login/index'));$this->error (L('NO_READ'));}
		$fields = F($this->mod[$module].'_Field');
		foreach($fields as $key=>$r){
			$fields[$key]['setup'] =string2array($fields[$key]['setup']);
		}
		$this->assign ( 'fields', $fields); 


		$seo_title = $cat['title'] ? $cat['title'] : $cat['catname'];
		$this->assign ('seo_title',$seo_title);
		$this->assign ('seo_keywords',$cat['keywords']);
		$this->assign ('seo_description',$cat['description']);
				

		if($module=='Guestbook'){
			$where['status']=array('eq',1);
			$this->dao= M($module);
			$count = $this->dao->where($where)->count();
			if($count){
				import ( "@.ORG.Page" );
				$listRows =  !empty($cat['pagesize']) ? $cat['pagesize'] : C('PAGE_LISTROWS');		
				$page = new Page ( $count, $listRows );
				$page->urlrule = geturl($cat,'');
				$pages = $page->show();
				$field =  $this->module[$cat['moduleid']]['listfields'];
				$field =  $field ? $field : '*';
				$list = $this->dao->field($field)->where($where)->order('listorder desc,id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
				$this->assign('pages',$pages);
				$this->assign('list',$list);
			}
			$template = $cat['module']=='Guestbook' && $cat['template_list'] ? $cat['template_list'] : 'index';
			$this->display(THEME_PATH.$module.'_'.$template.'.html');
		}elseif($module=='Feedback'){
			$template = $cat['module']=='Feedback' && $cat['template_list'] ? $cat['template_list'] : 'index' ;

			$this->display(THEME_PATH.$module.'_'.$template.'.html');
		}elseif($module=='Page'){
			$modle=M('Page');
			$data = $modle->find($catid);
			unset($data['id']);

			//分页
			$CONTENT_POS = strpos($data['content'], '[page]');
			if($CONTENT_POS !== false) {			
				$urlrule = geturl($cat,'',$this->Urlrule);
				$urlrule[0] =  urldecode($urlrule[0]);
				$urlrule[1] =  urldecode($urlrule[1]);
				$contents = array_filter(explode('[page]',$data['content']));
				$pagenumber = count($contents);
				for($i=1; $i<=$pagenumber; $i++) {
					$pageurls[$i] = str_replace('{$page}',$i,$urlrule);
				} 
				$pages = content_pages($pagenumber,$p, $pageurls);
				//判断[page]出现的位置
				if($CONTENT_POS<7) {
					$data['content'] = $contents[$p];
				} else {
					$data['content'] = $contents[$p-1];
				}
				$this->assign ('pages',$pages);	
			}

			$template = $cat['template_list'] ? $cat['template_list'] :  'index' ;
			$this->assign ($data);	
			$this->display(THEME_PATH.$module.'_'.$template.'.html');

		}else{
			
			if($catid){
				$seo_title = $cat['title'] ? $cat['title'] : $cat['catname'];
				$this->assign ('seo_title',$seo_title);
				$this->assign ('seo_keywords',$cat['keywords']);
				$this->assign ('seo_description',$cat['description']);
				

				$where = " status=1 ";
				if($cat['child']){							
					$where .= " and catid in(".$cat['arrchildid'].")";			
				}else{
					$where .=  " and catid=".$catid;			
				}
				if(empty($cat['listtype'])){
					$this->dao= M($module);
					$count = $this->dao->where($where)->count();
					if($count){
						import ( "@.ORG.Page" );
						$listRows =  !empty($cat['pagesize']) ? $cat['pagesize'] : C('PAGE_LISTROWS');
						$page = new Page ( $count, $listRows );
						$page->urlrule = geturl($cat,'',$this->Urlrule);
						$pages = $page->show();
						$field =  $this->module[$this->mod[$module]]['listfields'];
						$field =  $field ? $field : 'id,catid,userid,url,username,title,title_style,keywords,description,thumb,createtime,hits';
						$list = $this->dao->field($field)->where($where)->order('listorder desc,id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
						$this->assign('pages',$pages);
						$this->assign('list',$list);
					}
					$template_r = 'list';
				}else{
					$template_r = 'index';
				}
			}else{
				$template_r = 'list';
			}
			$template = $cat['template_list'] ? $cat['template_list'] : $template_r;
			$this->display($module.':'.$template);
		}
    }

 

	public function show($id='',$module='')
    {			//如果进入首页没有shopid则跳转多一次加上shopid
    	if(!isset($_GET['shop_id']) || $_GET['shop_id']==''){
    		$re_url='http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
    		if(!empty($_SERVER['QUERY_STRING']))
    			{$re_url.='?'.$_SERVER['QUERY_STRING']."&shop_id=".$this->_shopid;}else{
    				$re_url.="?shop_id=".$this->_shopid;
    			}
    		header("location:".$re_url);exit();
    	}
		$this->Urlrule =F('Urlrule');
		$p= max(intval($_REQUEST[C('VAR_PAGE')]),1);		
		$id = $id ? $id : intval($_REQUEST['id']);
		$module = $module ? $module : MODULE_NAME;
		$this->assign('module_name',$module);
		$this->dao= M($module);;
		$data = $this->dao->find($id);
		
		/*获取产品属性*/
		if ($module=='Product_oversea')
		{
			$data['member_price'] = intval($data['member_price']);
			$data['price'] = intval($data['price']);
			$discount = ($data['member_price']/$data['price']) * 10;
			$discount = number_format($discount,1);
			$data['discount'] = $discount;

			$product_property = M('Product_property')->where('product_oversea_id='.$id)->select();
			$prop_count = 0;
			if (!empty($product_property)){
				foreach ($product_property as $ka => $va) {
					$extend_id_arr = unserialize($va[attribute_group]);
					$prop_count = count($extend_id_arr);
					$extend_id_arr = implode('_', $extend_id_arr);
					$va[product_price] = $va[price];
					$product_property[$extend_id_arr] = $va;
					$product_property[$extend_id_arr]['prop_count'] = $prop_count;
					unset($product_property[$ka]);
				}

				$_all_specs_group = array();
				foreach ($product_property as $ki => $vi) {
					$extend_id_arr = unserialize($vi[attribute_group]);
					if (!empty($extend_id_arr) && is_array($extend_id_arr))
					{
						$specs_group = array();
						foreach ($extend_id_arr as $k => $v) {
							// $property_data = M('Property_extend')->find($v);
							//获取当前产品属性
							$join = 'qq_specs as sp ON pe.specs_id = sp.specs_id';
							$wehre_ex['pe.extend_id'] = $v;
							$specs_extend = M('Property_extend as pe')->join($join)->where($wehre_ex)->find();
							$specs_group[] = $specs_extend;
							$_all_specs_group[] = $specs_extend;
						}
						$product_property[$ki][attribute_group_info] = $specs_group;
					}
				}

				/*组装当前产品所有属性*/
				$all_specs_group = array();
				foreach ($_all_specs_group as $kk => $vv) {
					$all_specs_group[$vv[specs_id]][specsname] = $vv[specsname];
					$all_specs_group[$vv[specs_id]][attribute_group][$vv[extend_id]] = $vv;
				}
				
				$this->assign('product_property',$product_property);
				$this->assign('all_specs_group',$all_specs_group);
				
			}
			// 计算运费
			$data_info[freight_num] = ($data[fee_price]+$data[oversea_freight]+$data[country_freight]+$data[pack_freight]);
			$data_info[freight_num] = sprintf ("%.2f", $data_info[freight_num]);
			if (!empty($data[post_rate]) && $data[post_rate]>0)
			{
				$data_info[post_price] = $data[member_price]*($data[post_rate]/100);
				$data_info[post_rate] = $data[post_rate];
				$data_info[post_price] = $data_info[post_price];
			}
			$this->assign('data_info',$data_info);

			##获取商品评论##
			$gwhere['product_id']=$_REQUEST['id']?intval($_REQUEST['id']):0;
			$level = $_REQUEST['level']?intval($_REQUEST['level']):0;
			if ($level)
				$gwhere['level'] = $level;
	        $join = 'qq_user as u ON u.id=g.userid';
	        $gwhere['type'] = 1;
	        import ( "@.ORG.Page3" );
	        $listRows = 10;
	        $count = M('Guestbook')->alias('g')->join($join)->where($gwhere)->count();
	        $_count['allcount'] = $count;
	        $page = new Page ( $count, $listRows );
	        $guepages = $page->show();
	        $guestList =M('Guestbook')->alias('g')->field('u.wechat_name as realname,g.*')->join($join)->where($gwhere)->order('g.id desc')->limit($page->firstRow . ',' . $page->listRows)->select();

	        $gwhere['level'] = 1;
	        $count = M('Guestbook')->alias('g')->join($join)->where($gwhere)->count();
	        $_count['hcount'] = $count;

	        $gwhere['level'] = 2;
	        $count = M('Guestbook')->alias('g')->where($gwhere)->join($join)->count();
	        $_count['mcount'] = $count;

	        $gwhere['level'] = 3;
	        $count = M('Guestbook')->join($join)->alias('g')->where($gwhere)->count();
	        $_count['lcount'] = $count;

	        $product_id = $_REQUEST['id']?intval($_REQUEST['id']):0;
	        $this->assign('product_id',$product_id);
	        $this->assign('_count',$_count);
	        ##判断用户是否已收藏该商品##
	        if ($this->_userid)
	        {
	        	$colwhere['userid'] = $this->_userid;
                $colwhere['productid'] = $product_id;
                $res = M("pro_collect")->where($colwhere)->find();
                if ($res)
                	$this->assign('hascollect',1);
	        }
	        if (IS_AJAX)
	        {
	        	$ajax_data['guestList'] = $guestList;
	        	$ajax_data['guepages'] = $guepages;
	        	$this->pageAjaxList($ajax_data);
	        }
	        else{
	        	$this->assign('guepages',$guepages);
	        	$this->assign('guestList',$guestList);
	        }
		}
		
		$catid = $data['catid'];
		/*秒拍产品获取留言板信息 by dension*/
		if($module=='Product' && $catid==9){
			$guest_count=M('guestbook')->where('status=1 and product_id='.$id)->count();
			if($guest_count){
						import ( "@.ORG.Page2" );
						$listRows = 2;
						$page = new Page ( $count, $listRows );
						$pages = $page->show();
						$aa=M('guestbook');
						$list = $aa->field(" `qq_user`.realname,`qq_user`.pic,`qq_guestbook`.content")->join(" `qq_user` on `qq_user`.id=`qq_guestbook`.userid ")->where('`qq_guestbook`.status=1 and `qq_guestbook`.product_id='.$id)->order('`qq_guestbook`.listorder desc,`qq_guestbook`.id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
						$this->assign('guest_pages',$pages);
						$this->assign('guest_list',$list);
					}
		}
		/*秒拍产品留言板 end*/
		/*拍卖产品获取最新报价跟留言板信息 by dension*/
		if($module=='Product' && $catid==7){
			$list =M('auction_price')->field(" `qq_user`.realname,`qq_auction_price`.* ")->join(" `qq_user` on `qq_user`.id=`qq_auction_price`.userid ")->where(' `qq_auction_price`.productid='.$id)->order(' `qq_auction_price`.price desc')->find();
			$this->assign('auction_info',$list);
		}
		/*秒拍产品留言板 end*/
		$cat = $this->categorys[$data['catid']];
		if(empty($cat['ishtml']))$this->dao->where("id=".$id)->setInc('hits'); //添加点击次数
		$bcid = explode(",",$cat['arrparentid']); 
		$bcid = $bcid[1]; 
		if($bcid == '') $bcid=intval($catid);

		if($data['readgroup']){
			if($this->_groupid!=1 && !in_array($this->_groupid,explode(',',$data['readgroup'])) )$noread=1;
		}elseif($cat['readgroup']){
			if($this->_groupid!=1 && !in_array($this->_groupid,explode(',',$cat['readgroup'])) )$noread=1;
		}
		if($noread==1){$this->assign('jumpUrl',URL('User-Login/index'));$this->error (L('NO_READ'));}

		$chargepoint = $data['readpoint'] ? $data['readpoint'] : $cat['chargepoint']; 
		if($chargepoint && $data['userid'] !=$this->_userid){
			$user = M('User');
			$userdata =$user->find($this->_userid);
			if($cat['paytype']==1 && $userdata['point']>=$chargepoint){
				$chargepointok = $user->where("id=".$this->_userid)->setDec('point',$chargepoint);
			}elseif($cat['paytype']==2 && $userdata['amount']>=$chargepoint){
				$chargepointok = $user->where("id=".$this->_userid)->setDec('amount',$chargepoint);
			}else{
				$this->error (L('NO_READ'));
			}
		}
	
		$seo_title = $data['title'].'-'.$cat['catname'];
		$this->assign ('seo_title',$seo_title);
		$this->assign ('seo_keywords',$data['keywords']);
		$this->assign ('seo_description',$data['description']);
		$this->assign ( 'fields', F($cat['moduleid'].'_Field') ); 
		

		$fields = F($this->mod[$module].'_Field');
		foreach($data as $key=>$c_d){
			$setup='';
			$fields[$key]['setup'] =$setup=string2array($fields[$key]['setup']);
			if($setup['fieldtype']=='varchar' && $fields[$key]['type']!='text'){
				$data[$key.'_old_val'] =$data[$key];
				$data[$key]=fieldoption($fields[$key],$data[$key]);
			}elseif($fields[$key]['type']=='images' || $fields[$key]['type']=='files'){ 
				if(!empty($data[$key])){
					$p_data=explode(':::',$data[$key]);
					$data[$key]=array();
					foreach($p_data as $k=>$res){
						$p_data_arr=explode('|',$res);					
						$data[$key][$k]['filepath'] = $p_data_arr[0];
						$data[$key][$k]['filename'] = $p_data_arr[1];
					}
					unset($p_data);
					unset($p_data_arr);
				}
			}
			unset($setup);
		}
		$this->assign('fields',$fields); 


		//手动分页
		$CONTENT_POS = strpos($data['content'], '[page]');
		if($CONTENT_POS !== false) {
			
			$urlrule = geturl($cat,$data,$this->Urlrule);
			$urlrule =  str_replace('%7B%24page%7D','{$page}',$urlrule); 
			$contents = array_filter(explode('[page]',$data['content']));
			$pagenumber = count($contents);
			for($i=1; $i<=$pagenumber; $i++) {
				$pageurls[$i] = str_replace('{$page}',$i,$urlrule);
			} 
			$pages = content_pages($pagenumber,$p, $pageurls);
			//判断[page]出现的位置是否在文章开始
			if($CONTENT_POS<7) {
				$data['content'] = $contents[$p];
			} else {
				$data['content'] = $contents[$p-1];
			}
			$this->assign ('pages',$pages);	
		}

		if(!empty($data['template'])){
			$template = $data['template'];
		}elseif(!empty($cat['template_show'])){
			$template = $cat['template_show'];
		}else{
			$template =  'show';
		}
		
		$this->assign('moduleid',$this->moduleid);
		$this->assign('catid',$catid);
		$this->assign ($cat);
		$this->assign('bcid',$bcid);
		$this->assign ($data);

		$this->display($module.':'.$template); 
    }

   	##获取分页数据##
   	public function pageAjaxList($data = array())
   	{
   		/*$ajax_data['guestList'] = $guestList;
	    $ajax_data['guepages'] = $guepages;*/
	    $list_html = '';
	    ##组装列表##
   		if (!empty($data['guestList']))
   		{
            foreach ($data['guestList'] as $key => $item) {
            	$item['createtime'] = date('Y年m月d日',$item['createtime']);
            	$list_html .= '<div class="commWrap">';
            	$list_html .= '<dl class="eachInfo clearfix"><dt class="c_666"><span class="comm-from-app fr"></span>';
            	$list_html .= $item['realname'] ? $item['realname'] : '游客';
            	$list_html .= '　发表于　'.$item['createtime'].'</dt>';
            	/*$list_html .= '<span class="comm-left-icon"><div class="comm-good"></div>';
            	$list_html .= '<span class="emptyStar percentStar"><span class="fullStar smw10" style="width:100%"></span></span></span>';*/
            	$list_html .= '<dd><ul class="commItem"><li class="clearfix c_666">';
            	$list_html .= '<span class="itemDetail">'.$item['content'].'</span></li>';
            	$list_html .= '</ul></dd></dl></div>';
            }
   		}

   		##组装分页##
   		if (!empty($data['guepages']))
   		{ 
   			$p = $_GET['p'];
   			$p = explode('?', $p);
   			$p = $p[0];
            $list_html .= '<div id="pageNavWrap"><div id="pageNav"><div class="splitPages" id="pageBox" style="display: block;">';
            $list_html .= '<ul class="pageBox-ul">'.$data['guepages'].'</ul>';
            $list_html .= '<div class="jumptopage"><span class="jumptoTip">到第</span><input class="jumptoTxt" type="text" value="'.$p.'"><span class="jumptoTip">页</span><a class="jumptoBtn" onclick="jumpToPage(this);" href="javascript:void(0);">确定</a></div>';
            $list_html .= '</div></div></div>';
   		}
   		echo  $list_html;exit;
   	}

	public function down()
	{

		$module = $module ? $module : MODULE_NAME;
		$id = $id ? $id : intval($_REQUEST['id']);
		$this->dao= M($module);
		$filepath = $this->dao->where("id=".$id)->getField('file');
		$this->dao->where("id=".$id)->setInc('downs');

		if(strpos($filepath, ':/')) { 
			header("Location: $filepath");
		} else {			
			if(!$filename) $filename = basename($filepath);
			$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
			if(strpos($useragent, 'msie ') !== false) $filename = rawurlencode($filename);
			$filetype = strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
			$filesize = sprintf("%u", filesize($filepath));
			if(ob_get_length() !== false) @ob_end_clean();
			header('Pragma: public');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header('Content-Transfer-Encoding: binary');
			header('Content-Encoding: none');
			header('Content-type: '.$filetype);
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header('Content-length: '.$filesize);
			readfile($filepath);
		}
		exit;
	}

	public function hits()
	{
		$module = $module ? $module : MODULE_NAME;
		$id = $id ? $id : intval($_REQUEST['id']);
		$this->dao= M($module);
		$this->dao->where("id=".$id)->setInc('hits');

		if($module=='Download'){
			$r = $this->dao->find($id);
			echo '$("#hits").html('.$r['hits'].');$("#downs").html('.$r['downs'].');';
		}else{
			$hits = $this->dao->where("id=".$id)->getField('hits');
			echo '$("#hits").html('.$hits.');';
		}
		exit;
	}
	public function verify()
    {
		header('Content-type: image/gif');
        $type	 =	 isset($_GET['type'])?$_GET['type']:'gif';
        import("@.ORG.Image");
        Image::buildImageVerify(4,1,$type);
    }
    
 	//获取商品类别列表
 	public function get_goods_type(){
 		$good_type=M('type')->where('keyid=8')->order('typeid desc')->select();
 		$tree=$this->goods_type_tree($good_type,8);//分级
		foreach ($tree as $key => $vo)
		{
			static $tree = array();
			$tree[$vo['typeid']] = $vo;
		}
		foreach ($tree as $k => $v) {
			if ($v['typeid']==46)
				unset($tree[$k]);
		}
		// print_r($tree);exit;
 		$this->assign("goods_type",$tree);
 		return true;
 	}
 	//分类的无限分级
 	protected function goods_type_tree($arr,$pid){
 		$tree=array();
 		foreach ($arr as $key => $value) {
 			if($value['parentid']==$pid){
 				$tree[]=$value;
 				unset($arr[$key]);
 			}
 		}
 		foreach ($tree as $key => $value) {
 			$tree[$key]['next']=$this->goods_type_tree($arr,$value['typeid']);
 		}
 		return $tree;
 	}
 	//微信接入默认登录
    protected function wechat_login(){
		$cookietime =432000;
		if(empty($this->_userid)){
    	unset($_SESSION['wechat_auth']);
    	unset($_SESSION['auth']);
		cookie(null,'YP_');//每一次登录失效时必须清空了YP组数据才可以有查到微信
    	/*********************/
		/*获取微信用户信息*/ 
		//$useragent = addslashes($_SERVER['HTTP_USER_AGENT']); 
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!= false || strpos($_SERVER['HTTP_USER_AGENT'], 'Windows Phone') != false ){ 
		
		if(!$_SESSION['wechat_auth']){
		$gh = M('wechat')->field('gh_id,appId,appSecret')->find();
		$gh ? $this->gh_id = $gh['gh_id']:exit('查无公众号');
		//实例化一个 内部对象
		import ( '@.ORG.MP' );
		$this->mp = new MP($gh['appId'],$gh['appSecret']);
		$auth_res=$this->mp->mpAuth('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'],'snsapi_base');
/*		if(!empty($auth_res)){
			$auth_info=$this->mp->getUserInfo($auth_res);
		}*/
		/*加密微信信息*/
		$wechat_auth = authcode($auth_res, 'ENCODE', $this->qqcms_auth_key);
		$_SESSION['wechat_auth']=$wechat_auth;
			}

		}/*else{
			$this->assign("title","抱歉!");
			$this->assign("message","只能用微信进入我们的网站");
			$this->display("Index:error");exit();
		} */
	/*end*/
	/*登录操作*/
		$wechat_u = authcode($_SESSION['wechat_auth'], 'DECODE', $wechat_auth_key);
		if(!empty($wechat_u)){
			$where['wechat_openid']=$wechat_u;
			$authInfo = M("user")->where($where)->find();
			if(empty($authInfo)){
				//自动注册
				$con['wechat_openid']=$wechat_u;
				$con['createtime']=mktime();
				$con['status']=1;
				$con['groupid']=3;
				$con['parent_id']=$_SESSION['parent_shopid']?$_SESSION['parent_shopid']:0;
				$userid=M("user")->add($con);
				$authInfo['id']=$userid;
				$authInfo['groupid']=$con['groupid'];
				$authInfo['password']="";
			}
			$qqcms_auth = authcode($authInfo['id']."-".$authInfo['groupid']."-".$authInfo['password'], 'ENCODE', $this->qqcms_auth_key);
			
 			$_SESSION['auth']=$qqcms_auth;
			cookie('auth',$qqcms_auth,$cookietime);
			cookie('username',$authInfo['username'],$cookietime);
			cookie('groupid',$authInfo['groupid'],$cookietime);
			cookie('userid',$authInfo['id'],$cookietime);

            //保存登录信息
			$dao = M('User');
			$data = array();
			$data['id']	=	$authInfo['id'];
			$data['last_logintime']	=	time();
			$data['last_ip']	=	 get_client_ip();
			$data['login_count']	=	array('exp','login_count+1');
			$dao->save($data);
    		header("location:".'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);exit();
			}
		/*end*/
		}
		return true;
    }

	public function post_shipping($sn=0){
			$method="PostOrder";//方法
			$Key="B49e7d57ca6643102dbec749ae8c1b6e";//加密串
			$ordernum=$sn;//订单号
			$type=0;//0为新增订单
			$curl_url="http://wms.hans-trans.com/wms/interface/QueryService.aspx?method=".$method."&key=".$Key."&ordernum=".$ordernum."&type=".$type;
			$curl_result = file_get_contents($curl_url);
			$res=json_decode($curl_result,TRUE);
			return $res;
	}

 /*物流信息反馈*/
	public function put_shipping_error($id=0,$type=0,$msg=""){
		$data['order_id']=$id;
		$data['message']=$msg;
		$data['createtime']=mktime();
		$data['type']=$type;
		M("shipping_msg")->add($data);
		return true;
	}
 /**/

	public function put_consume($cash=0,$source=0,$user_id=0,$type=0){
			$data['user_id']=$user_id;
			$data['source']=$source;
			$data['pay_type']=$type;
			$data['cash']=floatval($cash);
			$data['create_time']=mktime();
			M("consume")->add($data);
			return true;
	}

	//自动升级
	public function menber_level($userid,$gold_fee,$time){
			$us=array();
		$us=M('user')->field('id,groupid,lastrecharge_time')->where('id='.$userid)->find();
		if($us['groupid']==3){
				/*升级*/
				$da['id']=$userid;
				$da['groupid']=4;//金会员为4
				$da['lastrecharge_time']=$time;
				M('user')->save($da);
		}
		return true;
	}

 	protected function check_shop(){
 		//如果进入首页没有shopid则跳转多一次加上shopid
		//print_r($_SERVER);exit;
    	if(!isset($_GET['shop_id']) || $_GET['shop_id']==''){
    		$re_url='http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
    		if(!empty($_SERVER['QUERY_STRING']))
    			{$re_url.='?'.$_SERVER['QUERY_STRING']."&shop_id=".$this->_shopid;}else{
    				$re_url.="?shop_id=".$this->_shopid;
    			}
    		header("location:".$re_url);exit();
    	}
    	return true;
 	}
 /**/

 	public function getWxqrcode()
 	{
 		$id=$_POST['orderid']?intval($_POST['orderid']):0;
 		$type=$_POST['type']?intval($_POST['type']):0;
 		if ($type > 0 && $type < 5){
 			$order=M('Wechat_order')->field('id,re_sn,userid,amount,type')->find($id);
 		}else{
 			$order=M('Order')->field('id,sn,userid,wechat_amount,type')->find($id);
 		}
 		if(!empty($order)){
 			$paylink=$this->get_qrcode($order,$type);
 			$this->success('OK','',$paylink);
 		}
 		$this->error('fail','','fail');
 	}


 	// 生成微信支付二维码
 	function get_qrcode($order,$type=0)
	{
		$wx_native_url = $this->_create_native_url($order);
		$native_url_code = $this->creatwxcode($wx_native_url,$order['id']);
		$qrcode['id'] = $order['id'];
		$qrcode['wechat_qrcode'] = $native_url_code['piclink'];
		M('Order')->save($qrcode);	
		return $native_url_code['piclink'];
		// if($order['userid']==1196)
		// echo '<img src="'.$this->site_url.$native_url_code['piclink'].'">';
	}
	
	
	function _create_native_url($order)
	{
		if ($order['type'] > 0 && $order['type'] < 5)
		{
			$productid = $order['re_sn'];
			$amount = $order['amount'];
		}
		else
		{
			$productid = $order['sn'];
			$amount = $order['wechat_amount'];
		}

		$amount = (int)$amount*100;
		switch ($order['userid']) {
			case 1196:case 1167:case 1856:case 1186:case 1524:case 1548:case 1527:
				$amount = 1;
				break;
			default:
				break;
		}
		
		import("@.Native.WxPayConfig");
		import("@.Native.WxPayDataBase");
		import("@.Native.WxPayUnifiedOrder");
		$_input = new WxPayUnifiedOrder;
		$_input->SetBody("上优舶");
		$_input->SetAttach(strval($order['type']));
		$_input->SetOut_trade_no(strval($productid));
		$_input->SetTotal_fee($amount);
		$_input->SetTime_start(date("YmdHis"));
		$_input->SetTime_expire(date("YmdHis", strtotime('+2 hours')));
		$_input->SetGoods_tag("商城商品");
		$_input->SetNotify_url("http://www.ubovip.com/wxpay/paynotice.php");
		$_input->SetTrade_type("NATIVE");
		$_input->SetProduct_id($productid);
		import("@.Native.NativePay");
		$notify = new NativePay;
		$result = $notify->GetPayUrl($_input);
		$amacStr = var_export($_input,true)."\r\n"."分割线"."\r\n".var_export($result,true)."结束"."\r\n";
		$_str = fopen("GetPayUrl.txt","a");
		fwrite($_str, $amacStr);
		fclose($_str);
		$url2 = $result["code_url"];
		return $url2;
	}

	//生成订单支付二维码
	function creatwxcode($url,$orderid)
	{
		import("@.ORG.QRcode");// 导入code类// 纠错级别：L、M、Q、H
		\QRcode::png($url, './shop_qrcode/native'.$orderid.'.png', 'H',4, 1); 
		$data['status'] = 1;
		$data['info'] = "微信支付二维码";
		$data['link'] = $url;
		$data['piclink'] = "/shop_qrcode/native".$orderid.".png";
		return $data;
		//echo '<img src="/shop_qrcode/test'.$id.'.png" /--><hr>';
	}
	
	
	//生成二维码
	function creatcode($url)
	{
		$randstr = $this->randstr(16);
		import("@.ORG.QRcode");// 导入code类// 纠错级别：L、M、Q、H  
		\QRcode::png($url, './shop_qrcode/native'.$randstr.'.png', 'H',8, 1); 
		$data['status'] = 1;
		$data['info'] = "微信支付二维码";
		$data['link'] = $url;
		$data['piclink'] = "/shop_qrcode/native".$randstr.".png";
		return $data;
		//echo '<img src="/shop_qrcode/test'.$id.'.png" /--><hr>';
	}
	
	//获取随机字符串
	function randstr($length=11)
	{
		$hash='';
		$chars= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz'; 
		$max=strlen($chars)-1;   
		mt_srand((double)microtime()*1000000);   
		for($i=0;$i<$length;$i++)   {   
			$hash.=$chars[mt_rand(0,$max)];   
		} 
		return $hash;   
	}

	 /**
  +----------------------------------------------------------
 * 生成随机字符串
  +----------------------------------------------------------
 * @param int       $length  要生成的随机字符串长度
 * @param string    $type    随机码类型：0，数字+大小写字母；1，数字；2，小写字母；3，大写字母；4，特殊字符；-1，数字+大小写字母+特殊字符
  +----------------------------------------------------------
 * @return string
  +----------------------------------------------------------
 */
	public function randCode($length = 5, $type = 0) {
		$arr = array(1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz", 3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4 => "~@#$%^&*(){}[]|");
		if ($type == 0) {
		    array_pop($arr);
		    $string = implode("", $arr);
		} elseif ($type == "-1") {
		    $string = implode("", $arr);
		} else {
		    $string = $arr[$type];
		}
		$count = strlen($string) - 1;
		$code = '';
		for ($i = 0; $i < $length; $i++) {
		    $code .= $string[rand(0, $count)];
		}
		return $code;
	}
	
	
	//Native（原生）支付URL签名方式
	function formatBizQueryParaMap($paraMap, $urlencode){
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v){
		//	if (null != $v && "null" != $v && "sign" != $k) {
			    if($urlencode){
				   $v = urlencode($v);
				}
				$buff .= strtolower($k) . "=" . $v . "&";
			//}
		}
		$reqPar;
		if (strlen($buff) > 0) {
			$reqPar = substr($buff, 0, strlen($buff)-1);
		}
		return $reqPar;
	}
	
	protected function create_noncestr( $length = 16 ) {  
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";  
		$str ="";  
		for ( $i = 0; $i < $length; $i++ )  {  
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
			//$str .= $chars[ mt_rand(0, strlen($chars) - 1) ];  
		}  
		return $str;  
	}
	
	protected function get_biz_sign($bizObj){
		 foreach ($bizObj as $k => $v){
			 $bizParameters[strtolower($k)] = $v;
		 }
		 try {
		 	if(APPKEY == ""){
		 		throw new SDKRuntimeException("APPKEY为空！" . "<br>");
		 	}
		 	$bizParameters["appkey"] = APPKEY;
		 	ksort($bizParameters);
		 	//var_dump($bizParameters);
		 	$bizString = $this->formatBizQueryParaMap($bizParameters, false);
		 	//var_dump($bizString);
		 	return sha1($bizString);
		 }catch (SDKRuntimeException $e)
		 {
			die($e->errorMessage());
		 }
	}


	/*生成支付宝按钮*/
	function getalipay($pay_code,$order)
	{
		
		$pay_code = $pay_code;
		$Payment=M("payment")->where(array("pay_code"=>$pay_code))->find();
		$aliapy_config = unserialize($Payment['pay_config']);
		$aliapy_config['order_sn']= $order['sn'];
		//判断订单类型
		switch ($order['type']) {
			case '0':
				$aliapy_config['order_amount']= $order['wechat_amount'];
				if ($order['alipay_amount'] > 0)
					$aliapy_config['order_amount']= $order['alipay_amount'];
				if ($order['userid']==5209 || $order['userid']==1217) 
					$aliapy_config['order_amount']=0.01;
				$ordre_info = M('Order_data')->where(array('order_id'=>$order['id']))->select();
				$body_info = '上优舶';
				if ($ordre_info)
				{
					$number = 0;
					$body_str = '';
					$appose = '、';
					foreach ($ordre_info as $key => $val) {
						if (!$ordre_info[$key+1]) $appose = '';
						$body_str .= $val['product_name'].$appose;
						$number += $val['number'];
					}
					$body_info = $body_str;
				}
				$aliapy_config['subject'] = $body_info;
				$aliapy_config['body'] = $body_info.'——广州市上优舶贸易有限公司！';
				break;
			case '1':
				$aliapy_config['subject'] = "充值缴费！";
				$aliapy_config['order_amount'] = $order['amount'];
				$aliapy_config['body'] = "充值缴费——广州市上优舶贸易有限公司！";
				break;
			case '2':
				$aliapy_config['subject'] = "平台管理费！";
				$aliapy_config['order_amount'] = $order['amount'];
				$aliapy_config['body'] = "平台管理费——广州市上优舶贸易有限公司！";
				break;
			case '3':
				$aliapy_config['subject'] = "年费！";
				$aliapy_config['order_amount'] = $order['amount'];
				$aliapy_config['body'] = "年费——广州市上优舶贸易有限公司！";
				break;
			case '4':
				$aliapy_config['subject'] = "押金！";
				$aliapy_config['order_amount'] = $order['amount'];
				$aliapy_config['body'] = "押金——广州市上优舶贸易有限公司！";
				break;
			default:
				break;
		}	
		
		import("@.Pay.".$pay_code);
		$pay= new $pay_code($aliapy_config);
		$paybutton = $pay->get_code();
		return $paybutton;
	}

	/*获取通联支付配置信息*/
	public function getAllinpay($pay_code='',$order=array(),$Payment=array())
	{
		//检验用户是否注册
		if (!$order['userid'])
		{
			$this->assign('jumpUrl',URL('User-Order/index'));
			$this->error(L('请先注册！'));exit;
		}
		
		$allinpay_config['pay_config'] = $Payment['pay_config'];
		if (!empty($Payment['customs_config']))
			$allinpay_config['customs_config'] = $Payment['customs_config'];
		$allinpay_config['orderNo']= $order['sn'];


		/*订单信息*/
		if (!empty($order['realname']))
		$allinpay_config['payerName']= $order['realname'];
		elseif (!empty($order['wechat_name']))
		$allinpay_config['payerName']= $order['wechat_name'];
		else
		$allinpay_config['payerName']= '微信用户';

		$allinpay_config['payerEmail']= !empty($order['email'])?$order['email']:'ER000000';
		$allinpay_config['product_amount']= $order['amount']*100;
		$allinpay_config['payerTelephone']= !empty($order['mobile'])?$order['mobile']:'00000000000';
		$allinpay_config['productNum'] = $order[goods_number];
		if ($order['allinipay_amount'] > 0)
			$allinpay_config['orderAmount']= $order['allinipay_amount']*100;
		
		if ($order['direct_total'] > 0)
			$allinpay_config['tax_fee']= $order['direct_total']*100;

		$allinpay_config['orderDatetime']= date('YmdHis',$order['add_time']);
		$allinpay_config['orderExpireDatetime'] = $order['end_time'];
		
		$product_info = '上优舶';
		switch ($order['type']) {
			case 0:
				$ordre_info = M('Order_data')->where(array('order_id'=>$order['id']))->select();
				if ($ordre_info)
				{
					$number = 0;
					$product_str = '';
					$appose = '、';
					foreach ($ordre_info as $key => $val) {
						if (!$ordre_info[$key+1]) $appose = '';
						$product_str .= $val['product_name'].$appose;
					}
					$product_info = $product_str;
				}
				break;
			case 1:
				$product_info = '上优舶-充值';
				break;
			case 2:
				$product_info = '上优舶-平台管理费';
				break;
			case 3:
				$product_info = '上优舶-年费';
				break;
			case 4:
				$product_info = '上优舶-押金';
				break;
			default:
				break;
		}/*缴费类型:1：充值，2：平台管理费，3：年费，4：押金*/

		$allinpay_config['paytype']= $order['type'];
		$allinpay_config['productName']= $product_info;
		$allinpay_config['productDesc']= $product_info;
		$allinpay_config['orderuserid'] = $order['userid'];
		switch ($order['userid']) {
			case 1196:case 1167:case 1856:case 1186:case 1524:case 1548:case 1527:
			$allinpay_config['orderAmount']=1;
				break;
			default:
				break;
		}
		
		
		import("@.Pay.".$pay_code);
		$pay = new $pay_code($allinpay_config);
		
		$allinpayInfo = $pay->get_signMsg();
		$allinpayInfo['gateway_url'] = $pay->config['gateway_url'];
		$allinpayInfo['gateway_method'] = $pay->config['gateway_method'];
		return $allinpayInfo;
	}

	/*public function decrypt_licensed(){
		$url='http://xueji.aiweiwang.cn/accredit.php';
		$return=$this->sp_cURLGet($url);
		$plain_key=authcode($return);
		F("plain_key",$plain_key);
		if($plain_key!="autor_knight"){
			echo 'error 999 Unauthorized access to the site';exit;
		}
	}*/

	protected function sp_cURLGet($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//这个是重点
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);
		$result =  curl_exec($ch);
		curl_close($ch);
		return $result;
	}
}
?>