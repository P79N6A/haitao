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
class RcshareAction extends BaseAction
{
	protected   $moduleid,$usermod;
	public function _initialize() 
	{
		parent::_initialize();
		if(!$this->_userid){
			header("location:".U('Home/Index/index'));
		}
                $this->usermod = M('User');
                $where['wechat_openid'] = $this->_openid;
                $where['id'] = $this->_userid;
                $field = 'id,wechat_openid,newAccount';
                $User = $this->usermod->field($field)->where($where)->find();
                $this->assign("User",$User);

	}

        public function index()
        {
                $this->display();  
        }
	
	public function recommend()
	{
                /*设置jssdk*/
                $gh = M('wechat')->field('gh_id,appId,appSecret')->where(array('id'=>'1'))->find();
                $gh ? $this->gh_id = $gh['gh_id']:exit('查无公众号');
                //实例化一个 内部对象
                import ( '@.ORG.MP' );
                $_newMp = new MP($gh['appId'],$gh['appSecret']);

                //获取JS-SDK使用权限签名
                $JsapiTicket = $_newMp->getJsapiTicket();
                $time = time();
                $nonceStr= $this->randCode(16,0);
                $timestamp = $time;
                $_url= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $share_url= $this->site_url.U('User/Rcshare/index',array('toshare'=>1, 'sharer'=>$this->_userid));
                $signature = $this->getSignature($JsapiTicket['ticket'],$time,$nonceStr,$_url);
                $this->assign("gh_id",$gh['gh_id']);
                $this->assign("appId",$gh['appId']);
                $this->assign("nonceStr",$nonceStr);
                $this->assign("timestamp",$timestamp);
                $this->assign("signature",$signature);
                $this->assign("_url",$_url);
                $this->assign("share_url",$share_url);
                $this->assign("site_url",$this->Config['site_url']);
		$this->display();
	}
}