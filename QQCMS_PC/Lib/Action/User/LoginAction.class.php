<?php
/**
 * 
 * User/LoginAction.class.php (前台会员登陆)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if(!defined("QQCMS")) exit("Access Denied");
class LoginAction extends BaseAction
{
	public $wxappId,$wxappSecret,$oauthcode,$oauth,$dao,$_mp;
	
	function _initialize()
    {
		parent::_initialize();
		$this->dao = M('User');
		$this->assign('bcid',0);
		$wxgh = M("wechat")->field("appId,appSecret")->find();
		if (!$wxgh) {$this->error("应用信息未配置");exit;}
		$this->wxappId = 'wx298a48a600565b2f';
		$this->wxappSecret = 'c43d586fe5f6560dabbc70e057e88058';
    }
    function index()
    {
		if($this->_userid){		
			$forward = $_POST['forward'] ? $_POST['forward'] :$this->forward ;
			$this->assign('jumpUrl',$forward);
			$this->success("您已经登录过了！");exit;
		}
		
        $this->display();
    }

   function testlog()
    {
		if($this->_userid){		
			$forward = $_POST['forward'] ? $_POST['forward'] :$this->forward ;
			$this->assign('jumpUrl',$forward);
			$this->success("您已经登录过了！");exit;
		}
		
        $this->display();
    }

    public function testlogs()
    {
    	F('hhhhhh','测试成功');
    }

	function testdolog()
	{
		$mobile = trim($_POST['mobile']);
        $password = trim($_POST['password']);
        $verifyCode = trim($_POST['verifyCode']);

        if(empty($mobile)){
           $this->error(L('empty_username_empty_password'));
        }
		
		if($this->member_config['member_login_verify'] && md5($verifyCode) != $_SESSION['verify']){
           $this->error(L('error_verify'));
        }

		$authInfo = $this->dao->getByMobile($mobile);
        //使用用户名、密码和状态的方式进行认证
		if($authInfo){
			unset($this->_userid);
			cookie(null,'YP_');
			unset($_SESSION['auth']);
			$cookietime = strtotime('+1 days')-time();
			$cookietime = $cookietime ? $cookietime : 0;
			$qqcms_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
			$qqcms_auth = authcode($authInfo['id']."|-|".$authInfo['groupid']."|-|".$authInfo['mobile']."|-|".$authInfo['mobile']."|-|".$authInfo['realname']."|-|".$authInfo['email'], 'ENCODE', $qqcms_auth_key);
			$_SESSION['auth']=$qqcms_auth;
			cookie('auth',$qqcms_auth,$cookietime);
			$this->_userid = $user['id'];
	
			//保存登录信息
			$dao = M('User');
			$data = array();
			$data['id']	=	$authInfo['id'];
			$data['last_logintime']	= time();
			$data['last_ip'] = get_client_ip();
			$data['login_count'] = array('exp','login_count+1');
			$dao->save($data);
           	$forward = U('Home/Index/index');
		   	$this->assign('jumpUrl',$forward);
		 	$this->success(L('login_ok'));
        }
		else
		{
			$this->assign('jumpUrl',U('Home/Index/index',array('wxreg'=>1)));
			$this->success("您还未注册，请通过扫描微信二维！");
		}
	 

	}
	
	
	function wxdologin()
	{
		$status = $this->randstr(16);
		$url = "http://wx.les-partageurs.com/redirect.php?act=Login";
		$redirect_uri = urlencode($url);
		$codeUrl = "https://open.weixin.qq.com/connect/qrconnect?appid=".$this->wxappId."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_login&state=".$status."#wechat_redirect";
		header("Location:".$codeUrl);
	}

	//获取随机字符串
	function randstr($length=8)
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
 
	function wechatlogin()
	{
		$this->oauthcode = $_GET['code'];
		import ( '@.ORG.Oauth' );
		$this->oauth = new Oauth($this->wxappId,$this->wxappSecret,$this->oauthcode);
		$wechatuser = $this->oauth->getUserInfo();
		$user = $this->dao->where(array("unionid"=>$wechatuser['unionid']))->find();
		if($user){
			$cookietime = strtotime('+1 days')-time();
			$cookietime = $cookietime ? $cookietime : 0;
			$qqcms_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
			$qqcms_auth = authcode($user['id']."|-|".$user['groupid']."|-|".$user['wechat_openid']."|-|".$user['unionid']."|-|".$user['mobile']."|-|".$user['wechat_name']."|-|".$user['realname']."|-|".$user['email'], 'ENCODE', $qqcms_auth_key);
			$_SESSION['auth']=$qqcms_auth;
			cookie('auth',$qqcms_auth,$cookietime);
			$this->_userid = $user['id'];
	
			//保存登录信息
			$dao = M('User');
			$data = array();
			$data['id']	=	$authInfo['id'];
			$data['last_logintime']	= time();
			$data['last_ip'] = get_client_ip();
			$data['login_count'] = array('exp','login_count+1');
			$dao->save($data);
           	$forward = $_POST['forward'] ? $_POST['forward'] :$this->forward;
		   	$this->assign('jumpUrl',$forward);
		 	$this->success(L('login_ok'));
        }
		else
		{
			$this->assign('jumpUrl',U('Home/Index/index',array('wxreg'=>1)));
			$this->success("您还未注册，请通过扫描微信二维！");
		}
	 

	}
	
	
	function dologin()
	{
		$username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $verifyCode = trim($_POST['verifyCode']);

        if(empty($username) || empty($password)){
           $this->error(L('empty_username_empty_password'));
        }
		
		if($this->member_config['member_login_verify'] && md5($verifyCode) != $_SESSION['verify']){
           $this->error(L('error_verify'));
        }

		 $authInfo = $this->dao->getByUsername($username);
        //使用用户名、密码和状态的方式进行认证
        if(empty($authInfo)) {
           $this->error(L('empty_userid'));
        }else {
            if($authInfo['password'] != sysmd5($_POST['password'])) {
            	$this->error(L('password_error'));
            }

			$cookietime =  $_REQUEST['cookietime'];
			$cookietime = $cookietime ? $cookietime : 0;

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
			$data['last_logintime']	=	time();
			$data['last_ip']	=	 get_client_ip();
			$data['login_count']	=	array('exp','login_count+1');
			$dao->save($data);

 			if ($_REQUEST[goodid])
 			$forward = U('Home/Product_oversea/show',array('id'=>$_REQUEST[goodid]));
 			else
 			$forward = U('Home/Index/index');

			$this->assign('jumpUrl',$forward);
			$this->success(L('login_ok'));
	 

		}
 
	}
 
	

	function getpass(){
		$this->display();
	}

	function repassword(){
		if($_POST['dosubmit']){
			$verifyCode = trim($_POST['verify']);
			if(md5($verifyCode) != $_SESSION['verify']){
			   $this->error(L('error_verify'));
			}
			if(trim($_POST['repassword'])!=trim($_POST['password'])){
				$this->error(L('password_repassword'));
			}
			list($userid,$username, $email) = explode("-", authcode($_POST['code'], 'DECODE', $this->sysConfig['ADMIN_ACCESS']));
			$user = M('User');
			//判断邮箱是用户是否正确
			$data =$user->where("id={$userid} and username='{$username}' and email='{$email}'")->find();
			if($data){
				$user->password	= sysmd5(trim($_POST['password']));
				$user->updatetime = time();
				$user->last_ip = get_client_ip();
				$user->save();
				$this->assign('jumpUrl',U('User/login/index'));
				$this->assign('waitSecond',3);
				$this->success(L('do_repassword_success'));
			}else{
				$this->error(L('check_url_error'));
			}
		
		}
		$code = str_replace(' ','+',$_REQUEST['code']);
		$this->assign('code',$code);
		$this->display();
	}
 

	function sendmail(){
		$verifyCode = trim($_POST['verifyCode']);
		$username = trim($_POST['username']);
		$email = trim($_POST['email']);


        if(empty($username) || empty($email)){
           $this->error(L('empty_username_empty_password'));
        }elseif(md5($verifyCode) != $_SESSION['verify']){
           $this->error(L('error_verify'));
        }

		$user = M('User');
		//判断邮箱是用户是否正确
		$data =$user->where("username='{$username}' and email='{$email}'")->find();
		if($data){
			$qqcms_auth = authcode($data['id']."-".$data['username']."-".$data['email'], 'ENCODE',$this->sysConfig['ADMIN_ACCESS'],3600*24*3);//3天有效期
			$username=$data['username'];
			$url =  'http://'.$_SERVER['HTTP_HOST'].U('User/Login/repassword?code='.$qqcms_auth);
			$message = str_replace(array('{username}','{url}','{sitename}'),array($username,$url,$this->Config['site_name']),$this->member_config['member_getpwdemaitpl']);

			$r = sendmail($email,L('USER_FORGOT_PASSWORD').'-'.$this->Config['site_name'],$message,$this->Config); 
			if($r){
				$returndata['username'] = $data['username'];
				$returndata['email'] = $data['email'];
				$this->ajaxReturn($returndata,L('USER_EMAIL_ERROR'),1);
			}else{
				$this->ajaxReturn(0,L('SENDMAIL_ERROR'),0);
			}
		}else{
			$this->ajaxReturn(0,L('USER_EMAIL_ERROR'),0);
		}
		//$this->ajaxReturn(1,L('login_ok'),1);
	}


	function emailcheck(){
		 
		if(!$this->_userid && !$this->_username && !$this->_groupid && !$this->_email){
			$this->assign('forward','');
			$this->assign('jumpUrl',U('User/Login/index'));
			$this->success(L('noogin'));
		}

		if($_REQUEST['resend']){
			$uid=$this->_userid;
			$username = $this->_username;
			$email = $this->_email;
			if($this->member_config['member_emailcheck']){
				$qqcms_auth = authcode($uid."-".$username."-".$email, 'ENCODE',$this->sysConfig['ADMIN_ACCESS'],3600*24*3);//3天有效期
				$url = 'http://'.$_SERVER['HTTP_HOST'].U('User/Login/regcheckemail?code='.$qqcms_auth);
				$click = "<a href=\"$url\" target=\"_blank\">".L('CLICK_THIS')."</a>";
				$message = str_replace(array('{click}','{url}','{sitename}'),array($click,$url,$this->Config['site_name']),$this->member_config['member_emailchecktpl']);
				$r = sendmail($email,L('USER_REGISTER_CHECKEMAIL').'-'.$this->Config['site_name'],$message,$this->Config);
				$this->assign('send_ok',1);
				$this->assign('username',$username);
				$this->assign('email',$email);
				$this->display();
				exit;
			}
		}
		if($this->_groupid==5){
			$this->display();
		}else{
			$this->error(L('do_empty'));
		}	
	}
	
	function regcheckemail(){
			$code = str_replace(' ','+',$_REQUEST['code']); 
			list($userid,$username, $email) = explode("-", authcode($code, 'DECODE', $this->sysConfig['ADMIN_ACCESS'])); 
			$user = M('User');
			//判断邮箱是用户是否正确
			$data =$user->where("id={$userid} and username='{$username}' and email='{$email}'")->find();
			if($data){
				$user->groupid = 3;
				$user->id = $userid;
				$user->save();
				$ru['role_id']=3;
				$roleuser=M('RoleUser');
				$roleuser->where("user_id=".$userid)->save($ru);
				$this->assign('jumpUrl',U('User/login/index'));
				$this->assign('waitSecond',10);
				$this->success(L('do_regcheckemail_success'));
			}else{
				$this->error(L('check_url_error'));
			}
	}
 

	function logout()
    {
		if($this->_userid) {
			cookie(null,'YP_');
			unset($_SESSION['auth']);
            $this->assign('jumpUrl',$this->forward);
			$this->success(L('loginouted'));
        }else {
			$this->assign('jumpUrl',$this->forward);
            $this->error(L('loginouted'));
        }
    }
}
?>