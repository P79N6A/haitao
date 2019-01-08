<?php
/**
 * 
 * Maxcard (前台扫描二维码注册模块)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if(defined('APP_PATH')!='./QQCMS' && !defined("QQCMS"))  exit("Access Denied");
class MaxcardAction extends Action
{
	protected   $moduleid;
	public function _initialize() 
	{
		$this->moduleid=$this->mod[MODULE_NAME];
		import ( '@.ORG.MP' );
		$gh = M('wechat')->field('gh_id,appId,appSecret')->find();
		if ($gh)
		{
			$this->gh_id = $gh['gh_id'];
		}
		else
		{
			$_POST['msgcode'] = 101; //未配置公众号
			M("shortuser")->data($_POST)->add();
			$this->error("查无公众号！");exit;
		}
		$this->mp = new MP($gh['appId'],$gh['appSecret']);
	}
	
	//扫描二维码动作
	function scancode()
    {
		//实例化一个 内部对象
		$_POST['createtime'] = time();
		$id = $_GET['id'];
		$this->assign('jumpUrl',URL('Home-Index/index'));
		if (!$id) 
		{
			$this->error("缺少参数！");exit;
		}
		$_POST['token'] = $id;
		$wx_user = array();
		$qqcms_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
		/*##获取微信用户信息##*/
		if (!empty($_SESSION['wechat_auth_info']))
		{
			$wx_user = $_SESSION['wechat_auth_info'];
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
				$wx_user = $this->mp->mpAuth('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 'snsapi_userinfo');
				if (!$_GET['code']) exit;
			}
		}

		/*加密微信信息*/
		if (!empty($wx_user['nickname']) && !empty($wx_user['openid']))
		{
			$wechat_auth = authcode($wx_user['openid']."|-|".$wx_user['unionid']."-".$wx_user['nickname']."-".$wx_user['headimgurl'], 'ENCODE', $qqcms_auth_key);
			$_SESSION['wechat_auth'] = $wechat_auth;
			$_SESSION['wechat_auth_info'] = $wx_user;
		}

		if (empty($wx_user) && $_GET['code'])
		{
			$_POST['msgcode'] = 102; //缺少openID
			$ider = M("shortuser")->data($_POST)->add();
			$this->error("信息抓取失败！");exit;
		}

		$user_info = M('User')->field('id,wechat_openid,mobile')->where('wechat_openid=\''.$wx_user['openid'].'\'')->find();
		if (!empty($user_info) && $_GET['code'])
		{
			$_POST['msgcode'] = 405; //已经注册
			$ider = M("shortuser")->data($_POST)->add();
			$this->error("您已经注册过了");exit;
		}
		else
		{
			if(empty($user_info)){
			 	//自动注册
			 	if (!empty($wx_user['nickname']) || !empty($wx_user['headimgurl']))
			 	{
			 		$con['wechat_name'] = $wx_user['nickname'];
			 		$con['wechat_pic'] = $wx_user['headimgurl'];
			 	}

			 	if (!$con['wechat_name'] || !$con['wechat_pic'])
			 	{
			 		##检查用户是否已关注##
				 	$follow_user = M('Wechat_follow')->field('openid,nickname,avatar')->where('openid=\''.$wx_user['openid'].'\'')->find();

				 	if (!empty($follow_user))
				 	{
				 		$con['wechat_name'] = $follow_user['nickname'];
				 		$con['wechat_pic'] = $follow_user['avatar'];
				 	}
			 	}

				$con['wechat_openid'] = $wx_user['openid'];
				$con['unionid'] = $wx_user['unionid'];
				$con['createtime'] = mktime();
				$con['status'] = 1;
				$con['groupid'] = 3;
				$con['parent_id'] = $_SESSION['parent_shopid']?$_SESSION['parent_shopid']:0;
				$userid = M("User")->add($con);
			}
		}
		
		$_POST['msgcode'] = 1;
		$_POST['openid'] = $wx_user['openid'];
		$_POST['unionid'] = $wx_user['unionid'];
		$ider = M("shortuser")->data($_POST)->add(); // 写入数据到数据库

		if ($ider === false)
		{
			$this->error("系统错误！");exit;
		}

		$this->success("注册成功！");exit;
    }
}