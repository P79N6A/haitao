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
	protected   $Config ,$sysConfig,$categorys,$module,$moduleid,$mod,$dao,$Type,$Role,$_userid,$_groupid,$_email,$_username ,$forward ,$user_menu,$Lang,$member_config,$_shopid;
    public function _initialize() {
			$this->sysConfig = F('sys.config');
			$this->module = F('Module');
			$this->Role = F('Role');
			$this->Type =F('Type');
			$this->mod= F('Mod');
			$this->moduleid=$this->mod[MODULE_NAME];

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
 
			
			if($_SESSION['auth']){
				$qqcms_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
				list($userid,$groupid, $password) = explode("-", authcode($_SESSION['auth'], 'DECODE', $qqcms_auth_key));
				$this->_userid = $userid;
				$this->_username = $_COOKIE['YP_username'];
				$this->_groupid = $groupid; 
				$this->_email = $_COOKIE['YP_email'];
				$parent_shop=M("user")->field("id,groupid,parent_id")->where("id=".$this->_userid)->find();
				if(empty($parent_shop)){//当出现账号已删除的情况
					unset($this->_userid);
				}
				if($parent_shop['groupid']>5 && $parent_shop['groupid']<14)$this->_shopid =$parent_shop['id'];
					else $this->_shopid =$parent_shop['parent_id'];
			}else{
				//$this->_groupid = $_COOKIE['YP_groupid']=4;
				$this->_userid =0;
				$this->_shopid =0;
			}
			$this->_shopid=$this->_shopid?$this->_shopid:0;
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
			$this->assign("slider_list",$slider_list);
			$slider_bottom_list=M("slide_data")->field('pic,title,link')->where('fid=12')->select();
			$this->assign("slider_bottom_list",$slider_bottom_list);
			/**/
			/*获取购物车数量*/
		$cart_sessionid = $_COOKIE['YP_onlineid'];
        $shopping_cart=M("cart")->field("number")->where("sessionid='{$cart_sessionid}'")->select();
         $shopping_count=0;
        foreach ($shopping_cart as $key => $value) {
            $shopping_count+=$value['number'];
        }
        $this->assign("shopping_count",$shopping_count);
			/**/
	}

    public function index($catid='',$module='')
    {
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
    {
		$this->Urlrule =F('Urlrule');
		$p= max(intval($_REQUEST[C('VAR_PAGE')]),1);		
		$id = $id ? $id : intval($_REQUEST['id']);
		$module = $module ? $module : MODULE_NAME;
		$this->assign('module_name',$module);
		$this->dao= M($module);;
		$data = $this->dao->find($id);
		
		
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
 		$this->assign("goods_type",$good_type);
 		return true;
 	}
 	//微信接入默认登录
    protected function wechat_login(){
		if(empty($this->_userid)){
    	unset($_SESSION['wechat_auth']);
    	unset($_SESSION['auth']);
		cookie(null,'YP_');//每一次登录失效时必须清空了YP组数据才可以有查到微信
			$cookietime =432000;
    	/*********************/
		/*获取微信用户信息*/ 
		//$useragent = addslashes($_SERVER['HTTP_USER_AGENT']); 
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!= false || strpos($_SERVER['HTTP_USER_AGENT'], 'Windows Phone') != false ){ 
		
		if(!$_SESSION['wechat_auth']){
		$gh = M('wechat')->field('gh_id,appId,appSecret')->where(array('id'=>'2'))->find();
		$gh ? $this->gh_id = $gh['gh_id']:exit('查无公众号');
		//实例化一个 内部对象
		import ( '@.ORG.MP' );
		$this->mp = new MP($gh['appId'],$gh['appSecret']);
		$res=$this->mp->mpAuth('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'],'snsapi_userinfo');
		/*$reg = '/[^\w\(\)\[\]\}\{\'\":&%\?@,\.\|\+\*\$\=-~`\/\\\x7f-\xff]/';
		$res = preg_replace($reg,'',$res);*/
		/*加密微信信息*/
		$auth_res=(array)json_decode($res);
		$wechat_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
		$wechat_auth = authcode($auth_res['openid'].":::".$auth_res['nickname'].":::".$auth_res['headimgurl'], 'ENCODE', $wechat_auth_key);
		$_SESSION['wechat_auth']=$wechat_auth;
		//cookie('wechat_auth',$wechat_auth,$cookietime);
		/*end*/
		//$_SESSION['wechat_user']=$res;
			}

		}/*else{
			$this->assign("title","抱歉!");
			$this->assign("message","只能用微信进入我们的网站");
			$this->display("Index:error");exit();
		} */
	/*end*/
	/*登录操作*/
		$wechat_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
		list($wechat_u['openid'],$wechat_u['nickname'],$wechat_u['headimgurl']) = explode(":::", authcode($_SESSION['wechat_auth'], 'DECODE', $wechat_auth_key));
		if(!empty($wechat_u) && $wechat_u['openid']){
			$where['wechat_openid']=$wechat_u['openid'];
			 $authInfo = M("user")->where($where)->find();
		 if(empty($authInfo)){
		 	//自动注册
			$con['wechat_pic']=$wechat_u['headimgurl'];
			$con['wechat_openid']=$wechat_u['openid'];
			$con['wechat_name']=$wechat_u['nickname'];
			$con['createtime']=mktime();
			//$con['sex']=$user_info['sex'];
			$con['status']=1;
			$con['groupid']=3;
			$con['parent_id']=$_SESSION['parent_shopid']?$_SESSION['parent_shopid']:0;
			M("user")->add($con);
			echo "<script>window.location.reload();</script>";exit();
			$this->assign("title","抱歉!");
			$this->assign("message","请先关注我们再进入网站");
			$this->display("Index:error");exit();
		 }
			$qqcms_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
			$qqcms_auth = authcode($authInfo['id']."-".$authInfo['groupid']."-".$authInfo['password'], 'ENCODE', $qqcms_auth_key);

			
 			$_SESSION['auth']=$qqcms_auth;
			//cookie('auth',$qqcms_auth,$cookietime);
			cookie('username',$authInfo['username'],$cookietime);
			cookie('groupid',$authInfo['groupid'],$cookietime);
			cookie('userid',$authInfo['id'],$cookietime);
			cookie('email',$authInfo['email'],$cookietime);

            //保存登录信息
			$dao = M('User');
			$data = array();
			$data['id']	=	$authInfo['id'];
			$data['wechat_name']=$wechat_u['nickname'];
			$data['wechat_pic']=$wechat_u['headimgurl'];
			$data['last_logintime']	=	time();
			$data['last_ip']	=	 get_client_ip();
			$data['login_count']	=	array('exp','login_count+1');
			$dao->save($data);
			echo "<script>window.location.reload();</script>";exit();
			}
		/*end*/
		}
		return true;
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
}
?>