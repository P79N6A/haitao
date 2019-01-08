<?php
/*
  +----------------------------------------------------------------+
  * 微信开放平台接口 
  * Oauth($appID,$appsecret)
  +----------------------------------------------------------------+
 * @初始化所需参数
 *  parameters string $appID		开放平台应用appID
 *  parameters string $appsecret	开放平台应用appsecret
 *  parameters string $code	开放平台应用code
  +----------------------------------------------------------------+
 */
class Oauth extends Think {
	private $appID;
	private $appsecret;
	private $code;
	private $oauth_openid;
	public  $access_token;
	public  $expires_time;
	/*public  $headers = array(
		"Content-type: text/xml;charset=\"utf-8\"", 
		"Accept: text/xml", 
		"Cache-Control: no-cache",
	);*/
	// url先放这里，清晰一共用到哪些url
	const GET_OAUTHACCESS_TOKEN_URL='https://api.weixin.qq.com/sns/oauth2/access_token?';
	const GET_OAUTHUSER_URL='https://api.weixin.qq.com/sns/userinfo?access_token=';
	const GET_OPENID='https://api.weixin.qq.com/sns/auth?access_token=';

	function __construct($appID,$appsecret,$code){
		//echo __CLASS__;die();
		if(!isset($appID) || !isset($appsecret)){
			exit('请先配置appID，appsecret');
			$this->error('请先配置appID，appsecret');
		}	
		$this->appID = $appID;
		$this->appsecret = $appsecret;
		$this->code = $code;
		$this->access_token = $_SESSION['oauth_access_token'];
		$this->expires_time = $_SESSION['oauth_expires_time'];
	}
	
	/**
	  * 开放平台accessToken
	  * 具有缓存功能的accessToken
	  * @param	boolen	是否强制更新
	  */
	public function getAccessToken($flag=false){
		if($flag){//强制更新 access_token
			$url = self::GET_OAUTHACCESS_TOKEN_URL.'appid='.$this->appID.'&secret='.$this->appsecret.'&code='.$this->code.'&grant_type=authorization_code';
//echo $url;die();
			$return = json_decode($this->cURLGet($url),true);
			if(is_array($return) && $return['errcode']){
				$return['MPError'] = __METHOD__;
				$this->showError($return);
			}else{
				$this->access_token = $return['access_token'];
				$this->oauth_openid = $return['openid'];
				$this->expires_time = time()+$return['expires_in'];
				return $return;
			}
		}else{
			if(!isset($this->access_token) || $this->expires_time < time() || !$this->oauth_openid){
				$this->getAccessToken(true);
			}	
		}
	}
	
	
	/**
	  * 获取单个用户信息
	  * @param	string url=>openid
	  * 已使用缓存优化，如果用户特别多，有可能占用比较大的内存，待验证
	  */
	public function getUserInfo(){
		$this->getAccessToken(true);
		$url = self::GET_OAUTHUSER_URL.$this->access_token.'&openid='.$this->oauth_openid.'&lang=zh_CN';
		$return = $this->cURLGet($url);
		/*$reg = '/[^A-Za-z0-9_\}\{\'\":&%\?@,\/\\\x7f-\xff]/';
		$return = preg_replace($reg,'',$return);*/
		$return = json_decode($return,true);
		if(is_array($return) && $return['errcode']){
			$return['MPError'] = __METHOD__;
			$this->showError($return);
		}else{
			return $return;
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
		$_SESSION['oauth_access_token'] = $this->access_token;
		$_SESSION['oauth_expires_time'] = $this->expires_time;
		$_SESSION['oauth_openid'] = $this->oauth_openid;
	}
}
