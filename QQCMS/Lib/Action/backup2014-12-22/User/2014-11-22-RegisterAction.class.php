<?php
/**
 * RegisterAction.class.php (前台会员注册模块)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if(!defined("QQCMS")) exit("Access Denied");
class RegisterAction extends BaseAction
{
	
	function _initialize()
    {
		parent::_initialize();
		$this->dao = M('User');
		if(empty($this->member_config['member_register'])) $this->error(L('close_reg'));
    }
    public function index()
    {
		/*if($_COOKIE['YP_auth']){
			$this->assign('forward','');
			$this->assign('jumpUrl','/');
			$this->success(L('login_ok'));
		}*/
		$this->assign('bcid',0);
		$shop_id=$this->_shopid?$this->_shopid:0;
		$mobile=M('user')->field('mobile')->where('id='.$shop_id)->find();
		$this->assign('mobile',$mobile['mobile']);
		/*如果微信用户授权，获取地址*/
		$user=M("user")->where("id=".$this->_userid)->find();
		if(empty($user)){
			header("location:".U('Home/Index/index'));exit();
			}
		$this->assign("user",$user);
		/**/
        $this->display();
    }


	public function doreg()
	{	
		/*根据推荐号码查找微店*/
			$parent_mobile=trim($_POST['parent_mobile']);
			unset($_POST['parent_mobile']);
			if(!empty($parent_mobile)){
			$parent_id=M('user')->field("id,mobile")->where("mobile='".$parent_mobile."' and groupid >5 and groupid< 14")->find();
			}
			if($parent_id && !empty($parent_id['mobile'])){
				$_POST['parent_id']=intval($parent_id['id']);
			}else{
				$_POST['parent_id']=0;
			}
			//$data['info']=$parent_mobile;
			
		/**/
		/*如果是在微信中且用户授予权限则绑定微信号*/
			if($_SESSION['wechat_user']){
				$wechat_user=json_decode($_SESSION['wechat_user'],true);
				$_POST['wechat_openid']=$wechat_user['openid'];
				$_POST['wechat_pic']=$wechat_user['headimgurl'];
				$_POST['wechat_name']=$wechat_user['nickname'];
			}
		/**/
		$username = trim($_POST['username']);
        $password = trim($_POST['password']);
		$mobile = intval($_POST['mobile']);
        $verifyCode = trim($_POST['verifyCode']);
        if(empty($username) || empty($password) || empty($mobile)){
           $this->error(L('empty_username_empty_password_empty_email'));
        }
		if($this->member_config['member_login_verify'] && md5($verifyCode) != $_SESSION['verify']){
           $this->error(L('error_verify'));
        }
		//$status = $this->member_config['member_registecheck'] ? 0 : 1;
		//if($this->member_config['member_emailcheck']){ $status = 1; $groupid=5 ;}
		$status=1;//默认激活
		$groupid = $groupid ? $groupid : 3;
		$_POST['groupid']=$groupid;
		$_POST['login_count']=1;
		$_POST['createtime']=time();
		$_POST['updatetime']=time();
		$_POST['last_logintime']=time();
		$_POST['reg_ip']=get_client_ip();
		$_POST['status']=$status;
		$authInfo['password'] = $_POST['password'] = sysmd5($_POST['password']);
		$user=$this->dao;
		if($data=$user->create()){
			if(false!==$user->add()){
				$authInfo['id'] = $uid=$user->getLastInsID();
				$authInfo['groupid'] = $ru['role_id']=$_POST['groupid'];
				$ru['user_id']=$uid;
				$roleuser=M('RoleUser');
				$roleuser->add($ru);

/*				if($this->member_config['member_emailcheck']){
					$qqcms_auth = authcode($uid."-".$username."-".$email, 'ENCODE',$this->sysConfig['ADMIN_ACCESS'],3600*24*3);//3天有效期
					$url = 'http://'.$_SERVER['HTTP_HOST'].U('User-Login/regcheckemail?code='.$qqcms_auth);
					$click = "<a href=\"$url\" target=\"_blank\">".L('CLICK_THIS')."</a>";
					$message = str_replace(array('{click}','{url}','{sitename}'),array($click,$url,$this->Config['site_name']),$this->member_config['member_emailchecktpl']);
					$r = sendmail($email,L('USER_REGISTER_CHECKEMAIL').'-'.$this->Config['site_name'],$message,$this->Config);
					$this->assign('send_ok',1);
					$this->assign('username',$username);
					$this->assign('email',$email);
					$this->display('Login_emailcheck');
					exit;
				}*/
				
				$qqcms_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
				$qqcms_auth = authcode($authInfo['id']."-".$authInfo['groupid']."-".$authInfo['password'], 'ENCODE', $qqcms_auth_key);
				

				$authInfo['username'] = $_POST['username'];
				$authInfo['email'] = $_POST['email'];
				cookie('auth',$qqcms_auth,$cookietime);
				cookie('username',$authInfo['username'],$cookietime);
				cookie('groupid',$authInfo['groupid'],$cookietime);
				cookie('userid',$authInfo['id'],$cookietime);
				cookie('email',$authInfo['email'],$cookietime);
/*
				$this->assign('jumpUrl',$this->forward);
				$this->success(L('reg_ok'));*/

				$data['status']=1;
				echo json_encode($data);exit();
			}else{/*
				$this->error(L('reg_error'));*/
				$data['status']=0;
				echo json_encode($data);exit();
			}
		}else{/*
			$this->error($user->getError());*/
				$data['status']=0;
				echo json_encode($data);exit();
		}
 
	}
public function wechat_doreg()
	{	
		/*根据推荐号码查找微店*/
			$parent_mobile=trim($_POST['parent_mobile']);
			unset($_POST['parent_mobile']);
			if(!empty($parent_mobile)){
			$parent_id=M('user')->field("id,mobile")->where("mobile='".$parent_mobile."' and groupid >5 and groupid< 14")->find();
			if($parent_id && !empty($parent_id['mobile'])){
				$_POST['parent_id']=intval($parent_id['id']);
			}else{
				$_POST['parent_id']=0;
			}
			}
			//$data['info']=$parent_mobile;
			
		/**/
		/*如果是在微信中且用户授予权限则绑定微信号*/
		$wechat_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
		list($wechat_u['openid'],$wechat_u['nickname']) = explode(":::", authcode($_SESSION['wechat_auth'], 'DECODE', $wechat_auth_key));
			
		/**/
		$username = trim($_POST['username']);
        $password = trim($_POST['password']);
		$mobile = intval($_POST['mobile']);
        $verifyCode = trim($_POST['verifyCode']);
        if(empty($username) || empty($password) ){
           $this->error(L('empty_username_empty_password_empty_email'));
        }
		if($this->member_config['member_login_verify'] && md5($verifyCode) != $_SESSION['verify']){
           $this->error(L('error_verify'));
        }
		$status = $this->member_config['member_registecheck'] ? 0 : 1;
		//if($this->member_config['member_emailcheck']){ $status = 1; $groupid=5 ;}
		$status=$status;//默认激活
		$groupid = $groupid ? $groupid : 3;
		$_POST['groupid']=$groupid;
		$_POST['login_count']=1;
		$_POST['createtime']=time();
		$_POST['updatetime']=time();
		$_POST['last_logintime']=time();
		$_POST['reg_ip']=get_client_ip();
		$_POST['status']=$status;
		$authInfo['password'] = $_POST['password'] = sysmd5($_POST['password']);
		$user=$this->dao;
		$flat=0;
		if($wechat_u['openid']){
			$wechat_where['wechat_openid']=$wechat_u['openid'];
			$wechat=$user->field("id,username,parent_id,groupid")->where($wechat_where)->find();
			/*检查是否第一次注册*/
			$is_firsttime=empty($wechat['username']) ? 1:0;
			/**/
			if($wechat){
				$_POST['groupid']=$wechat['groupid'];
				$_POST['parent_id']=$wechat['parent_id']?$wechat['parent_id']:$_POST['parent_id'];
				$flat=$user->data($_POST)->where("id=".$wechat['id'])->save();
				$flat=2;//标记是修改信息
			}else{
				$_POST['wechat_openid']=$wechat_u['openid'];
				$flat=$user->create();
				$flat=1;
			}
			}else{
				$flat=$user->create();
				$flat=1;
			}
			if(false!==$user->add() || $flat){
				if($flat==2){
				$authInfo['id'] = $uid=$wechat['id'];
				$authInfo['groupid'] = $wechat['groupid'];
				}else{
				$authInfo['id'] = $uid=$user->getLastInsID();
				$authInfo['groupid'] = $ru['role_id']=$_POST['groupid'];
				$ru['user_id']=$uid;
				$roleuser=M('RoleUser');
				$roleuser->add($ru);
				}
				if($flat==1||$is_firsttime==1){
				/*第一次注册可获电子现金*/
				if($this->member_config['menber_register']==1){
					$fee=floatval($this->member_config['menber_register_fee']);
					$user_fee=M("user")->field("cash_use")->where("id=".$uid)->find();
					$total['id']=$uid;
					$total['cash_use']=$fee+floatval($user_fee['cash_use']);
					M("user")->save($total);
				}
				if($this->member_config['menber_register_up']==1){//自动升级为会员
					$this->menber_level($authInfo);
				}
				/*end*/
				}
				
				$qqcms_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
				$qqcms_auth = authcode($authInfo['id']."-".$authInfo['groupid']."-".$authInfo['password'], 'ENCODE', $qqcms_auth_key);
				

				$authInfo['username'] = $_POST['username'];
				$authInfo['email'] = $_POST['email'];
				//cookie('auth',$qqcms_auth,$cookietime);	
				$_SESSION['auth']=$qqcms_auth;
				cookie('username',$authInfo['username'],$cookietime);
				cookie('groupid',$authInfo['groupid'],$cookietime);
				cookie('userid',$authInfo['id'],$cookietime);
				cookie('email',$authInfo['email'],$cookietime);

				$data['status']=1;
				echo json_encode($data);exit();
			}else{
				$data['status']=0;
				echo json_encode($data);exit();
			}
 
	}
	//自动升级
	public function menber_level($user){
	$gold=M('role')->field('gold_money,gold_fee')->where("id=4")->find();
	$gold_fee=$gold ? floatval($gold['gold_fee']):0;//一次性缴纳，不能变为电子现金
			$us=array();
			$level_data=array();
		$us=M('user')->field('id,groupid,lastrecharge_time')->where('id='.$user['id'])->find();
		if($us['groupid']==3){
				/*升级*/
				$level_data['user_id']=$user['id'];
				$level_data['cash']=$gold_fee;
				$level_data['source']=2;
				$level_data['create_time']=time();
				$level_data['level_flat']=1;//充值标记，1为系统充值
				M('consume')->add($level_data);
				$da['id']=$user['id'];
				$da['groupid']=4;//金会员为4
				$da['lastrecharge_time']=$level_data['create_time'];
				M('user')->save($da);
		}
		return true;
	}
	function checkEmail(){

        $email=$_GET['email'];
		$userid=intval($_GET['userid']);
		if(empty($userid)){
			if($this->dao->getByEmail($email)){
				 echo 'false';
			}else{
				echo 'true';
			}
		}else{
			//判断邮箱是否已经使用
			if($this->dao->where("id!={$userid} and email='{$email}'")->find()){
				 echo 'false';
			}else{
				echo 'true';
			}
		}
        exit;
	}

	function checkusername(){
		$username=$_GET['username'];
		if($this->dao->getByUsername($username)){
				 echo 'false';
			}else{
				echo 'true';
		}
		exit;
	}	
	function checkmobile(){
		$mobile=$_GET['mobile'];
		if($this->dao->getByMobile($mobile)){
				 echo 'false';
			}else{
				echo 'true';
		}
		exit;
	}
}
?>