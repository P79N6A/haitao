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
class BaseAction extends Action
{
	protected   $Config ,$sysConfig,$categorys,$module,$moduleid,$mod,$dao,$Type,$Role ,$forward ,$user_menu,$Lang,$member_config;
	public $_userid,$_groupid,$_email,$_username,$_shopid,$_auth,$_openid,$_unionid,$_checkOrder,$qqcms_auth_key;
    public function _initialize() {
    		// $this->decrypt_licensed(); //authorization
    		header("Content-type: text/html; charset=UTF-8");
			$this->sysConfig = F('sys.config');
			$this->module = F('Module');
			$this->Role = F('Role');
			$this->Type =F('Type');
			$this->mod = F('Mod');
			$this->moduleid = $this->mod[MODULE_NAME];
			$this->qqcms_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
			$this->$_checkOrder=0;

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
 
			$auth_res = array();
			/*##获取微信用户信息##*/
			if (!empty($_SESSION['wechat_auth_info']))
			{
				$auth_res = $_SESSION['wechat_auth_info'];
			}
			else
			{
				if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!= false || strpos($_SERVER['HTTP_USER_AGENT'], 'Windows Phone') != false )
				{ 
					$gh = M('wechat')->field('gh_id,appId,appSecret')->find();
					$gh ? $this->gh_id = $gh['gh_id']:exit('查无公众号');
					//实例化一个 内部对象
					import ('@.ORG.MP');
					$this->mp = new MP($gh['appId'],$gh['appSecret']);
					$auth_res = $this->mp->mpAuth('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 'snsapi_userinfo');
				}
			}

			/*加密微信信息*/
			if (!empty($auth_res['nickname']) && !empty($auth_res['openid']))
			{
				$wechat_auth = authcode($auth_res['openid']."|-|".$auth_res['unionid']."|-|".$auth_res['nickname']."|-|".$auth_res['headimgurl'], 'ENCODE', $this->qqcms_auth_key);
				$_SESSION['wechat_auth'] = $wechat_auth;
				$_SESSION['wechat_auth_info'] = $auth_res;
			}

			if($_COOKIE['YP_auth']){
				$this->_auth = $_COOKIE['YP_auth'];
				list($userid,$groupid,$openid,$unionid,$mobile,$wechat_name,$realname,$email) = explode("|-|", authcode($this->_auth, 'DECODE', $this->qqcms_auth_key));
				/*F('list_user', $userid.','.$groupid.','.$openid.','.$unionid.','.$mobile.','.$wechat_name.','.$realname.','.$email);*/
				##防止微信用户切换登录信息  再次获取OPENID验证用户信息##
				/*if (!empty($openid))
				{
					$gh = M('wechat')->field('gh_id,appId,appSecret')->find();
					$gh ? $this->gh_id = $gh['gh_id']:exit('查无公众号');
					//实例化一个 内部对象
					import ( '@.ORG.MP' );
					$this->mp = new MP($gh['appId'],$gh['appSecret']);
					$auth_openid = $this->mp->mpAuth('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
					$openid = $auth_openid;
				}*/

				if(!empty($userid) || !empty($openid))
				{
					if ($openid != 'openid')
					$user_info = M('User')->field('id,mobile,groupid,parent_id,wechat_openid,unionid,newAccount,createtime')->where('wechat_openid=\''.$openid.'\'')->find();
					else
					$user_info = M('User')->field('id,mobile,groupid,parent_id,wechat_openid,unionid,newAccount,createtime')->where('wechat_openid=\''.$openid.'\'')->find();
					if (empty($user_info))
					{
						cookie(null,'YP_');
						//当出现账号已删除的情况
						$this->_shopid = 0;
						unset($this->_userid);
						unset($this->_openid);
						cookie(null,'YP_');
						unset($_SESSION['auth']);
						if (!empty($_SESSION['wechat_auth_info']))
						{
							$auth_res = $_SESSION['wechat_auth_info'];
						}
						else
						{
							if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!= false || strpos($_SERVER['HTTP_USER_AGENT'], 'Windows Phone') != false )
							{ 
								$gh = M('wechat')->field('gh_id,appId,appSecret')->find();
								$gh ? $this->gh_id = $gh['gh_id']:exit('查无公众号');
								//实例化一个 内部对象
								import ('@.ORG.MP');
								$this->mp = new MP($gh['appId'],$gh['appSecret']);
								$auth_res = $this->mp->mpAuth('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 'snsapi_userinfo');
							}
						}
					}
					else
					{
						##计算加入时间##
						$joinTime = time() - $user_info['createtime'];
						$oneminute = strtotime('+1 minutes') - time();
						if ($user_info['newAccount'] == 1 && $joinTime > $oneminute)
						{
							$_con['id'] = $user_info['id'];
							$_con['newAccount'] = 2; //去除新用户标识
							M("user")->save($_con);
						}

						if(empty($user_info['unionid'])){
							$_con['id'] = $user_info['id'];
							$_con['unionid'] = $unionid;
							M("user")->save($_con);
						}

						$this->_openid = $user_info['wechat_openid'];
						$this->_unionid = $user_info['unionid'];
						$this->_userid = $user_info['id'];
						$this->_groupid = $user_info['groupid'];
						$this->_email = $user_info['email'];
						$this->assign("userid",$this->_userid);
						$this->assign("user_mobile",$user_info['mobile']);
						if($user_info['groupid']>5 && $user_info['groupid']<14)$this->_shopid =$user_info['id'];
							else $this->_shopid =$user_info['parent_id'];
					}
				}
			}else{
				$this->_shopid = 0;
				unset($this->_userid);
				unset($this->_openid);
				cookie(null,'YP_');
				unset($_SESSION['auth']);
			}

			$this->_shopid=$this->_shopid?$this->_shopid:0;

			if(isset($_GET['shop_id'])&&empty($_SESSION['parent_shopid'])){
				//保存当前商家id
    			$_SESSION['parent_shopid']=intval($_GET['shop_id']);
			}

			$this->assign("shop_id",$this->_shopid);

			/*微信检测与登录*/
			$this->wechat_login();
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
				$column_type[$key]['link'].="&shop_id=".$this->_shopid;
			}else{
				$column_type[$key]['link'].="?shop_id=".$this->_shopid;
			}
		}
			$this->assign("slider_list",$slider_list);
			$slider_bottom_list=M("slide_data")->field('pic,title,link')->where('fid=12')->select();
			$this->assign("slider_bottom_list",$slider_bottom_list);
			/**/
			/*获取购物车数量*/
		//$cart_sessionid = $_COOKIE['YP_onlineid'];
        $shopping_cart=M("cart")->field("number")->where("userid='{$this->_userid}'")->select();
         $shopping_count=0;
        foreach ($shopping_cart as $key => $value) {
            $shopping_count+=$value['number'];
        }
        $this->assign("shopping_count",$shopping_count);
			/**/

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
			$TaskOrder = M('Order')->field('id,userid,cash_coupon,end_time')->where($ordwhere)->select();
			foreach ($TaskOrder as $kz => $vz) {
				$st = time();
				if ($vz['end_time'] < $st)
				{
					$iwhere['order_id'] = $vz['id'];
					// $iwhere['goods_attr_id']=array('NEQ','NULL');
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

    public function index($catid='',$module='')
    {				//如果进入首页没有shopid则跳转多一次加上shopid
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
			if($bcid == '') $bcid = intval($catid);
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
			$product_property = M('Product_property')->where('product_oversea_id='.$id)->select();
			if (!empty($product_property)){
				foreach ($product_property as $ka => $va) {
					$extend_id_arr = unserialize($va['attribute_group']);
					$extend_id_arr = implode('_', $extend_id_arr);
					$va['product_price'] = $va['price'];
					$product_property[$extend_id_arr] = $va;
					unset($product_property[$ka]);
				}

				$_all_specs_group = array();
				foreach ($product_property as $ki => $vi) {
					if($vi['stock']>0){
						$extend_id_arr = unserialize($vi['attribute_group']);
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
							$product_property[$ki]['attribute_group_info'] = $specs_group;
						}
					}
				}

				/*组装当前产品所有属性*/
				$all_specs_group = array();
				if(!empty($_all_specs_group)){
					foreach ($_all_specs_group as $kk => $vv) {
						$all_specs_group[$vv['specs_id']]['specsname'] = $vv['specsname'];
						$all_specs_group[$vv['specs_id']]['attribute_group'][$vv['extend_id']] = $vv;
					}
					$data['stock']=M('Product_property')->where('product_oversea_id='.$id)->sum("stock");
					$this->assign('all_specs_group',$all_specs_group);
				}
				
				$this->assign('product_property',$product_property);
			}
			// 计算运费

			$freight_num = ($data['fee_price']+$data['oversea_freight']+$data['country_freight']+$data['pack_freight']);
			// $freight_num = sprintf ("%.2f", $freight_num);
			if ($freight_num > 0)
			$data_info['freight_num'] = $freight_num;
			$data_info['post_rate'] = intval($data['post_rate']);
			if (!empty($data['post_rate']) && $data['post_rate']>0)
			{
				$data_info['post_price'] = '&yen;'.$data['member_price']*($data['post_rate']/100);
				
				// $data_info[post_price] = '&yen;'.sprintf ("%.2f", $data_info[post_price]);
			}
			$this->assign('data_info',$data_info);
			
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
		
		$this->assign('catid',$catid);
		$this->assign ($cat);
		$this->assign('bcid',$bcid);
		$this->assign ($data);

		$this->display($module.':'.$template); 
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
 		$good_type=M('type')->where('parentid=8')->order('typeid desc')->select();
 		foreach ($good_type as $k => $v) {
			if ($v['typeid']==46)
				unset($good_type[$k]);
		}
 		$this->assign("goods_type",$good_type);
 		return true;
 	}

 	//微信接入默认登录
    protected function wechat_login(){
		$cookietime = strtotime('+1 days')-time();
		if(!$this->_userid || !$this->_openid){
			/*登录操作*/
			if (!empty($_SESSION['wechat_auth']))
			list($wechat_openid,$wechat_unionid,$wechat_nickname,$headimgurl) = explode("|-|", authcode($_SESSION['wechat_auth'], 'DECODE', $this->qqcms_auth_key));

			if(!empty($wechat_openid)){
				$where['wechat_openid']=$wechat_openid;
				$authInfo = M("user")->where($where)->find();
				if(empty($authInfo)){
				 	//自动注册
				 	if (!empty($wechat_nickname) || !empty($headimgurl))
				 	{
				 		$con['wechat_name'] = $wechat_nickname;
				 		$con['wechat_pic'] = $headimgurl;
				 	}

				 	if (!$con['wechat_name'] || !$con['wechat_pic'])
				 	{
				 		##检查用户是否已关注##
					 	$follow_user = M('Wechat_follow')->field('openid,nickname,avatar')->where('openid=\''.$wechat_u.'\'')->find();

					 	if (!empty($follow_user))
					 	{
					 		$con['wechat_name'] = $follow_user['nickname'];
					 		$con['wechat_pic'] = $follow_user['avatar'];
					 	}
				 	}

					$con['wechat_openid'] = $wechat_openid;
					$con['unionid'] = $wechat_unionid;
					$con['createtime']=mktime();
					$con['status'] = 1;
					$con['groupid'] = 3;
					$con['newAccount'] = 1; //新加入用户
					$con['parent_id'] = $_SESSION['parent_shopid']?$_SESSION['parent_shopid']:0;
					##判断用户是否是被推荐的##
					if ($_GET['toshare'])
					{
						$con['cash_use'] = 100;
						$con['share'] = 1;
					}

					if ($_GET['iswallet'])
					{
						$con['cash_use'] = 100;
						$con['share'] = 1;
					}

					$userid = M("user")->add($con);

					if ($_GET['toshare'] && !empty($_GET['sharer']))
					{
						##保存推荐数据##
						$share['sharer'] = $_GET['sharer'];
						$share['entrants'] = $userid;
						$share['createtime'] = time();
						M("Rcshare")->add($share);
					}
					else if($_GET['iswallet'] && !empty($_GET['enveloper']))
					{
						##保存推荐数据##
						$share['sharer'] = $_GET['enveloper'];
						$share['entrants'] = $userid;
						$share['createtime'] = time();
						M("Rcshare")->add($share);
					}

					$authInfo['id'] = $userid;
					$authInfo['groupid'] = $con['groupid'];
					$authInfo['wechat_name'] = $wechat_nickname;
				}else{
					##计算加入时间##
					$joinTime = time() - $authInfo['createtime'];
					$oneminute = strtotime('+1 minutes') - time();
					if ($authInfo['newAccount'] == 1 && $joinTime > $oneminute)
					{
						$_con['id'] = $authInfo['id'];
						$_con['newAccount'] = 2; //去除新用户标识
						M("user")->save($_con);
					}
				}

				$qqcms_auth = authcode($authInfo['id']."|-|".$authInfo['groupid']."|-|".$wechat_openid."|-|".$wechat_unionid."|-|".$authInfo['mobile']."|-|".$authInfo['wechat_name']."|-|".$authInfo['realname']."-".$authInfo['email'], 'ENCODE', $this->qqcms_auth_key);
	 			$_SESSION['auth']=$qqcms_auth;
				cookie('auth',$qqcms_auth,$cookietime);

	            //保存登录信息
				$dao = M('User');
				$data = array();
				$data['id']	= $authInfo['id'];
				$data['last_logintime']	= time();
				$data['last_ip'] = get_client_ip();
				$data['login_count'] = array('exp','login_count+1');
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
 	protected function check_shop(){
 		//如果进入首页没有shopid则跳转多一次加上shopid
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

	 /**/	
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

	public function put_consume($cash=0,$source=0,$user_id=0,$type=0){
			$data['user_id']=$user_id;
			$data['source']=$source;
			$data['pay_type']=$type;
			$data['cash']=floatval($cash);
			$data['create_time']=mktime();
			M("consume")->add($data);
			return true;
	}


	public function getSignature($jsapi,$time,$nonceStr='',$url='')
	{
		$noncestr= $nonceStr;
		$jsapi_ticket= $jsapi;
		$timestamp=$time;
		$url=$url;
		$and = "jsapi_ticket=".$jsapi_ticket."&noncestr=".$noncestr."&timestamp=".$timestamp."&url=".$url."";
		$signature = sha1($and);
		return $signature;
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

	/*获取通联支付配置信息*/
	public function getAllinpay($pay_code='',$order=array(),$Payment=array())
	{
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
		##END##
		
		import("@.Pay.".$pay_code);
		$pay = new $pay_code($allinpay_config);
		$allinpayInfo = $pay->get_signMsg();
		$allinpayInfo['gateway_url_m'] = $pay->config['gateway_url_m'];
		$allinpayInfo['gateway_method'] = $pay->config['gateway_method'];
		return $allinpayInfo;
	}

	/*public function decrypt_licensed(){
		$url='http://xueji.aiweiwang.cn/accredit.php';
		$return=$this->sp_cURLGet($url);
		$plain_key=authcode($return);
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