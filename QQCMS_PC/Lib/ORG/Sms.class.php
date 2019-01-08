<?php
/*
  +----------------------------------------------------------------+
  * 短信平台接口 
  * Oauth($appID,$appsecret)
  +----------------------------------------------------------------+
 * @初始化所需参数
 *  parameters string $apikey		短信平台应用apikey
 *  parameters string $username	短信平台应用用户账号
 *  parameters string $password	短信平台应用密码
  +----------------------------------------------------------------+
 */
class Sms extends Think {
	private $apikey;
	private $username;
	private $password;
	// url先放这里，清晰一共用到哪些url
	const SEND_URL='http://m.5c.com.cn/api/send/?';
	function __construct($apikey,$username,$password){
		if(!isset($apikey) || !isset($username) || !isset($password)){
			$this->error('请先配置apikey，appsecret，用户账号，密码');exit;
		}	
		$this->apikey = $apikey;
		$this->username = $username;
		$this->password = $password;
	}
	
	/**
	  * 发送短信
	  * 即时发送
	  * @param	boolen	是否强制更新
	  */
	public function sendSMS($mobile,$content)
	{
		$url = self::SEND_URL;
		$data = array
			(
				'username'=>$this->username,					//用户账号
				'password'=>$this->password,				//密码
				'mobile'=>$mobile,					//号码
				'content'=>$content,				//内容
				'apikey'=>$this->apikey,				    //apikey
			);
		$result= $this->cURLPost($url,$data);			//POST方式提交
		return $result;
	}

	protected function curlSMS($url,$post_fields=array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3600); //60秒 
        curl_setopt($ch, CURLOPT_HEADER,1);
        curl_setopt($ch, CURLOPT_REFERER,'http://www.yourdomain.com');
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$post_fields);
        $data = curl_exec($ch);
        curl_close($ch);
        $res = explode("\r\n\r\n",$data);
        return $res[2]; 
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
