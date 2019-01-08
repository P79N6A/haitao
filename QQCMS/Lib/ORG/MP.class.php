<?php
/*
  +----------------------------------------------------------------+
  * 微信公众平台接口 
  * MP($appID,$appsecret)
  +----------------------------------------------------------------+
 * @初始化所需参数
 *  parameters string $appID		公众号appID
 *  parameters string $appsecret	公众号appsecret
  +----------------------------------------------------------------+
 */
class MP extends Think {
	private $appID;
	private $appsecret;
	public  $access_token;
	public  $expires_time;
	public  $groups;
	public  $isGroupsNew;
	public  $isCoded;
	public  $follow_info;
	public  $follow_group;
	public  $JsapiTicket;
	public  $Jsapi_expires_time;
	public  $headers = array(
		"Content-type: text/xml;charset=\"utf-8\"", 
		"Accept: text/xml", 
		"Cache-Control: no-cache",
	);
	// url先放这里，清晰一共用到哪些url
	const GET_ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&';
	const GET_USER_URL = 'https://api.weixin.qq.com/cgi-bin/user/get?';
	const GET_USER_INFO = 'https://api.weixin.qq.com/cgi-bin/user/info?lang=zh_CN&';
	const GET_OAUTH_TOKEN = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=';
	const GET_USER_GROUP = 'https://api.weixin.qq.com/cgi-bin/groups/getid?access_token=';
	const MOVE_USER = 'https://api.weixin.qq.com/cgi-bin/groups/members/update?access_token=';
	const GET_GROUP = 'https://api.weixin.qq.com/cgi-bin/groups/get?access_token=';
	const UPDATE_GROUP = 'https://api.weixin.qq.com/cgi-bin/groups/update?access_token=';
	const CREATE_GROUP = 'https://api.weixin.qq.com/cgi-bin/groups/create?access_token=';
	const DELETE_MENU = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=';
	const MP_AUTH = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=';
	const GET_MENU = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token=';
	const CREATE_MENU = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=';
	const QRCODE='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=';
	const JSAPI='https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=';
	function __construct($appID,$appsecret){
		//验证是否支持curl
		if (ini_get('allow_url_fopen') != 1 || !function_exists('curl_init')){
			exit('空间不支持curl,无法使用微信功能！');
		}
		//echo __CLASS__;die();
		if(!isset($appID) || !isset($appsecret)){
			exit('请先配置appID，appsecret');
			$this->error('请先配置appID，appsecret');
		}	
		$this->appID = $appID;
		$this->appsecret = $appsecret;
		$this->access_token = $_SESSION['MP_access_token'];
		$this->expires_time = $_SESSION['MP_expires_time'];
		$this->groups = $_SESSION['MP_groups'];
		$this->isGroupsNew = $_SESSION['MP_isGroupsNew'];
		$this->isCoded = $_SESSION['MP_isCoded'];
		$this->follow_info =$_SESSION['MP_follow_info'];
		$this->follow_group =$_SESSION['MP_follow_group'];
		//$this->getAccessToken();
	}
	/**
	  * 具有缓存功能的accessToken
	  * @param	boolen	是否强制更新
	  */
	public function getAccessToken($flag=false){
		if($flag){//强制更新 access_token
			$url = self::GET_ACCESS_TOKEN_URL.'appid='.$this->appID.'&secret='.$this->appsecret;
//echo $url;die();
			$return = json_decode($this->cURLGet($url),true);
			if(is_array($return) && $return['errcode']){
				$return['MPError'] = __METHOD__;
				$this->showError($return);
			}else{
				$this->access_token = $return['access_token'];
				$this->expires_time = time()+$return['expires_in'];
				return $return['access_token'];
			}
		}else{
			if(!isset($this->access_token) || $this->expires_time < time()){
				$this->getAccessToken(true);
			}	
		}
	}

	/**
	  * 具有缓存功能的accessToken
	  * @param	boolen	是否强制更新
	  */
	public function getAccessTokenAgain($flag=false){
		if($flag){//强制更新 access_token
			$url = self::GET_ACCESS_TOKEN_URL.'appid='.$this->appID.'&secret='.$this->appsecret;

			$return = json_decode($this->cURLGet($url),true);
			if(is_array($return) && $return['errcode']){
				return $return['errmsg'];
			}else{
				$this->access_token = $return['access_token'];
				$this->expires_time = time()+$return['expires_in'];
				$data_access_token['access_token'] = $this->access_token;
				$data_access_token['expires_in'] = $this->expires_time;
				F('getAccessTokenAgain', $data_access_token);
				return $return['access_token'];
			}
		}else{
			$accessTokenAgain = F('getAccessTokenAgain');
			$this->access_token = $accessTokenAgain['access_token'];
			$this->expires_time = $accessTokenAgain['expires_in'];
			if(!isset($this->access_token) || $this->expires_time < time()){
				$this->getAccessTokenAgain(true);
			}	
		}
	}


	//JS-SDK获得jsapi_ticket
	public function getJsapiTicket($_flag=false)
	{
		$this->getAccessTokenAgain();
		//强制更新
		if ($_flag)
		{
			$url = self::JSAPI.$this->access_token.'&type=jsapi';
			$return = json_decode($this->cURLGet($url),true);
			if(is_array($return) && !$return['errcode']){
				$this->JsapiTicket = $return['ticket'];
				$this->Jsapi_expires_time = time()+$return['expires_in'];
				$dataJsapiTicket['ticket'] = $this->JsapiTicket;
				$dataJsapiTicket['expires_in'] = $this->Jsapi_expires_time;
				F('getJsapiTicket',$dataJsapiTicket);
			}
			return $return;
		}
		else
		{
			$return = F('getJsapiTicket');
			$this->JsapiTicket = $return['access_token'];
			$this->Jsapi_expires_time = $return['expires_in'];
			if(!isset($this->JsapiTicket) || $this->Jsapi_expires_time < time()){
				$this->getJsapiTicket(true);
			}
			return $return;
		}
	}
	
	/**
	  * 循环获取全部用户openid
	  * 为保证最新，暂时不考虑使用SESSION
	  * @param	string ''	即使超过10000个，也循环获取出来
	  */
	public function getUser($next=''){
		$this->getAccessToken();
		static $arr = array();
		$url = self::GET_USER_URL.'access_token='.$this->access_token.'&next_openid='.$next;
		$return = json_decode($this->cURLGet($url),true);
		if(is_array($return) && $return['errcode']){
			$return['MPError'] = __METHOD__;
			$this->showError($return);
		}else{
			$arr = array_merge($arr,$return['data']['openid']);
			if(10000 == $return['count']){
				$this->getUser($return['next_openid']);
			}else{
				return $arr;
			}
		}
	}
	
	/**
	  * 获取单个用户信息
	  * @param	string url=>openid
	  * 已使用缓存优化，如果用户特别多，有可能占用比较大的内存，待验证
	  */
	public function getUserInfo($openid){
		if($this->userInfo[$openid]){
			return $this->userInfo[$openid];
		}
		$this->getAccessToken();
		$url = self::GET_USER_INFO.'access_token='.$this->access_token.'&openid='.$openid;
		$return = $this->cURLGet($url);
		/*$reg = '/[^A-Za-z0-9_\}\{\'\":&%\?@,\/\\\x7f-\xff]/';
		$return = preg_replace($reg,'',$return);*/
		$return = json_decode($return,true);
		if(is_array($return) && $return['errcode']){
			$return['MPError'] = __METHOD__;
			$this->showError($return);
		}else{
			$this->userInfo[$openid] = $return;

			return $return;
		}
	}

	/**
	  * 循环获取用户分组
	  * 已使用session优化，此数据属于中间表，数据量不大特别适合缓存
	  */
	public function getUserGroup($openid_arr=array()){
		$this->getAccessToken();
		$url = self::GET_USER_GROUP.$this->access_token;
		$user = $openid_arr ? $openid_arr : $this->getUser();//如果是空数组，拉取所有的用户
		if(is_array($openid_arr) && !$openid_arr)
				$force = true;//如果只提供少数的openid ，强制拉取信息
		$arr = array();
		foreach($user as $v){
			if($force || !$this->follow_group[$v]){//如果强制拉取 获取 缓存没有信息
				$json_param = '{"openid":"'.$v.'"}';
				$return = json_decode($this->cURLPost($url,$json_param),true);
			}else{
				$return = array('groupid'=>$this->follow_group[$v]);//模拟微信接口返回的数据
			}
			if(is_array($return) && $return['errcode']){
				$return['MPError'] = __METHOD__;
				$this->showError($return);
			}else{
				$arr[$v] = $return['groupid'];
			}
		}
		$this->follow_group = $arr;
		return $arr;
	}
	/**
	  * 循环移动用户分组
	  * @param	array $arr	形如openid=>groupid
	  * @return	array		正确移动的数组
	  */
	public function moveUser($arr){
		static $return_arr = array();
		$this->getAccessToken();
		$url = self::MOVE_USER.$this->access_token;
		foreach($arr as $k=>$v){
			$json_param = '{"openid":"'.$k.'","to_groupid":'.$v.'}';
			$return = json_decode($this->cURLPost($url,$json_param),true);
			if(is_array($return) && $return['errcode']){
				$return['MPError'] = __METHOD__;
				$this->showError($return);
			}else{
				$this->follow_group[$k]=$v;//移动成功,也更新缓存
				$return_arr[$k] = $v;
			}
		}
		return $return_arr;
	}

	/**
	  * 获取全部分组
	  * @param	string url=>token
	  */
	public function getGroup(){
		if($this->isGroupsNew && $this->groups){
			return $this->groups;
		}
		$this->getAccessToken();
		$url = self::GET_GROUP.$this->access_token;
		$return = json_decode($this->cURLGet($url),true);
		if(is_array($return) && $return['errcode']){
			$return['MPError'] = __METHOD__;
			$this->showError($return);
		}else{
			$this->groups = $return['groups'];
			$this->isGroupsNew = true;
			return $return['groups'];
		}
	}

	/**
	  * 创建分组
	  * @param	string {"group":{"name":"test"}}
	  * @return	string {"group": {"id": 107,  "name": "test" }}
	  */
	public function createGroup($json_param){
		$this->getAccessToken();
		$url = self::CREATE_GROUP.$this->access_token;
		$return = json_decode($this->cURLPost($url,$json_param),true);
		if(is_array($return) && $return['errcode']){
			$return['MPError'] = __METHOD__;
			$this->showError($return);
		}else{
			$this->isGroupsNew = false;//增加group后，标记本地存储的groups不是最新
			return $return['group'];
		}
	}

	/**
	  * 修改分组名称。
	  * @param	string {"group":{"id":108,"name":"test2_modify2"}} json格式字符串
	  * @return	string {"errcode": 0, "errmsg": "ok"}
	  */
	public function updateGroup($json_param){
		$this->getAccessToken();
		$url = self::UPDATE_GROUP.$this->access_token;
		$return = json_decode($this->cURLPost($url,$json_param),true);
		if(is_array($return) && $return['errcode']){
			$return['MPError'] = __METHOD__;
			$this->showError($return);
		}else{
			$this->isGroupsNew = false;
			return $return;
		}
	}
	/**
	  * 网页授权。（需要在认证服务号:"OAuth2.0网页授权"中设置 “授权回调页面域名”）
	  *
	  * +---特别注意，网页授权出错不提示---+
	  * +---使用$_SESSION 而不是retun 记录openid---+
	  *
	  * @param	string {"group":{"id":108,"name":"test2_modify2"}} json格式字符串
	  * @return	string {"errcode": 0, "errmsg": "ok"}
	  */
	public function mpAuth($url,$scope='snsapi_base',$state=''){
		$url = self::MP_AUTH.$this->appID.'&redirect_uri='.urlencode($url).'&response_type=code&scope='.$scope.'&state='.$state.'#wechat_redirect';
		$userAgent = $_SERVER["HTTP_USER_AGENT"];


		preg_match("/MicroMessenger/i",$userAgent,$match);
		if($match && !$_GET['code']){//判断是否请求过Code，防止死循环
			$this->isCoded = true;
			
			header('Location:'.$url);exit();
		}
		if($_GET['code'] && $scope=='snsapi_base'){
			$url = self::GET_OAUTH_TOKEN.$this->appID."&secret=".$this->appsecret."&code=".$_GET['code']."&grant_type=authorization_code";
			$return = json_decode($this->cURLGet($url),true);
			if(is_array($return) && $return['openid']){
				//$_SESSION['MP_active_openid'] = $return['openid'];
				return $return['openid'];
			}
		}elseif($_GET['code'] && $scope=='snsapi_userinfo'){
			//检查本地是否有保存refresh_token，有的话直接使用refresh_token刷新
			$token_return = $_SESSION['refresh_token'];
			if (empty($token_return) || $token_return['expires_in'] < time())
			{
				$url = self::GET_OAUTH_TOKEN.$this->appID."&secret=".$this->appsecret."&code=".$_GET['code']."&grant_type=authorization_code";
				$token_return = json_decode($this->cURLGet($url),true);	
				$token_info['access_token'] = $token_return['access_token'];
				$token_info['expires_in'] = time()+$token_return['expires_in'];
				$token_info['openid'] = $token_return['openid'];
				$token_info['refresh_token'] = $token_return['refresh_token'];
				$_SESSION['refresh_token'] = $token_info;
			}
			else
			{
				$url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=".$this->appID."&grant_type=refresh_token&refresh_token=".$token_info['refresh_token']."";
				$token_return = json_decode($this->cURLGet($url),true);
			}

			if(is_array($token_return) && $token_return['refresh_token']){
				$info_url="https://api.weixin.qq.com/sns/userinfo?access_token=".$token_return['access_token']."&openid=".$token_return['openid']."&lang=zh_CN";
				$return = json_decode($this->cURLGet($info_url),true);
				return $return;
				//file_put_contents('/public/mpAuth/'.$this->appID.'.php',var_export($return,true));
			}
		}
		//当前只完成 openid 的拉取
		//获取基本信息的授权还没做
	
	}
	/*生成永久二维码*/
	public function create_code($scene_id){
		$this->getAccessToken(true);
		$url=self::QRCODE.$this->access_token;
		$data['action_name']="QR_LIMIT_SCENE";
		$data['action_info']['scene']['scene_id']=$scene_id;
		$json_data=json_encode($data);
		$return = json_decode($this->cURLPost($url,$json_data),true);
		if(is_array($return) && $return['errcode']){
			$return['MPError'] = __METHOD__;
			$this->showError($return);
		}else{
			return $return;
		}
	}


	/**/
	/*
	 * $menu是json格式的二维数组数据
	 */
	public function createMenu($json_menu){
		$this->getAccessToken(true);
		$url = self::CREATE_MENU.$this->access_token;
		$return = json_decode($this->cURLPost($url,$json_menu),true);

		if(is_array($return) && $return['errcode']){
			$return['MPError'] = __METHOD__;
			$this->showError($return);
		}else{
			$this->isGroupsNew = false;
			return $return;
		}

	}
	public function menuList(){
		$this->getAccessToken();
		$url = self::GET_MENU.$this->access_token;
		$return = json_decode($this->cURLGet($url),true);
		if(is_array($return) && $return['errcode']){
			$return['MPError'] = __METHOD__;
			array_push($return,ACTION_NAME);
			$this->showError($return);
		}else{
			return $return['menu']['button'];
		}
		
	}
	/**
	  * 删除自定义菜单。（需一般服务号 或者 认证的订阅号）
	  */
	public function deleteMenu(){
		$this->getAccessToken();
		$url = self::DELETE_MENU.$this->access_token;
		$return = json_decode($this->cURLGet($url),true);
		if(is_array($return) && $return['errcode']){
			$return['MPError'] = __METHOD__;
			array_push($return,ACTION_NAME);
			$this->showError($return);
		}else{
			return $return;
		}
	}

	/**
	  * JS接口：隐藏菜单
	  * 其实只是照抄微信提供的js,建议直接将JS放到网页底部。
	  */
	public function hideButton($where='bottom'){
		if($where == 'right'){
			return "document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {WeixinJSBridge.call('hideToolbar');});";
		}else{
			return "document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {WeixinJSBridge.call('hideOptionMenu');});";
		}
	}

	protected function showError($return){
//		//待人性化，当前直接返回 元素的错误信息。
		dump($return);
		die();
	}
	protected function cURLGet($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//这个是重点
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);
		$result =  curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	protected function cURLPost($url,$parameter,$header=array()){
		$header = $header ? $header : $this->headers;
		$curlhandle = curl_init();
		curl_setopt($curlhandle, CURLOPT_URL, $url);
		curl_setopt($curlhandle, CURLOPT_HTTPHEADER, $header); //设置HTTP头字段的数组
		curl_setopt($curlhandle, CURLOPT_SSL_VERIFYPEER, 0); //对认证证书来源的检查
		curl_setopt($curlhandle, CURLOPT_SSL_VERIFYHOST, 1); //从证书中检查SSL加密算法是否存在
		curl_setopt($curlhandle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:23.0) Gecko/20100101 Firefox/23.0'); 
		curl_setopt($curlhandle, CURLOPT_FOLLOWLOCATION, 0); //使用自动跳转
		curl_setopt($curlhandle, CURLOPT_AUTOREFERER, 0); //自动设置Referer
		curl_setopt($curlhandle, CURLOPT_POST, 1); //发送一个常规的Post请求
		curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $parameter);//微信接口要就json数据
		curl_setopt($curlhandle, CURLOPT_COOKIE, ''); //读取储存的Cookie信息
		curl_setopt($curlhandle, CURLOPT_TIMEOUT, 30); //设置超时限制防止死循环
		curl_setopt($curlhandle, CURLOPT_HEADER, 0); //显示返回的Header区域内容
		curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, 1); //获取的信息以文件流的形式返回
		$result = curl_exec($curlhandle);
		curl_close($curlhandle);
		return $result;
	}

	function __destruct(){
		$_SESSION['MP_access_token'] = $this->access_token;
		$_SESSION['MP_expires_time'] = $this->expires_time;
		$_SESSION['MP_follow_info'] = $this->follow_info;
		$_SESSION['MP_follow_group'] = $this->follow_group;

		if($this->isGroupsNew && $this->groups){
			$_SESSION['MP_groups'] = $this->groups;
			$_SESSION['MP_isGroupsNew'] = $this->isGroupsNew;
		}
		if($this->isCoded){
			$_SESSION['MP_isCoded'] = $this->isCoded;
		}
	}
}
