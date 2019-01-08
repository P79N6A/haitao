<?php
/*
  +----------------------------------------------------------------+
  * 微信公众平台--支付接口 
  * Wechat($gh_id)
  +----------------------------------------------------------------+
 */
class WechatPay extends Think {
	private $gh_id;
	private $mp;
	function __construct($gh_id){
		define(APPID , "wx043ee7bba73960f5");  //appid
		define(APPKEY ,"iI3X9TfTgLCt5S5JTi7dbqedDkJsDSTo4mM9IsuXHxmcTRoI9qThO7qSmDNsW9hrqx38ss1AsNfMD5sHskUcmj3bnM6hnkGek0Oe5ffYwYd6t8KSnCvHowjJLmXza5gV"); //paysign key
		define(SIGNTYPE, "sha1"); //method
		define(PARTNERKEY,"j5810052390701440105000255886win");//通加密串
		define(APPSERCERT, "834e74326be51153a4a553261f455d12");
		//检验本地是否有这个gh_id(token)
		$gh_id = "gh_9ca1790ccc2a";
		$gh = M('wechat')->field('gh_id,appId,appSecret')->where(array('gh_id'=>$gh_id))->find();
		$gh ? $this->gh_id = $gh['gh_id']:exit('查无公众号');
		//实例化一个 内部对象
		import ( '@.ORG.MP' );
		$this->mp = new MP($gh['appId'],$gh['appSecret']);
	}	
/*生成支付签名（paySign）
a. 对所有待签名参数按照字段名的 ASCII ASCII ASCII ASCII 码从小到大排序（字典序） 码从小到大排序（字典序） 码从小到大排序（字典序） 码从小到大排序（字典序） 后，使用 URL 键
值对的格式（即 key1=value1&key2=value2 … ）拼接成字符串 string1 。这里需要注意的是所有参数名均为小写字符 有参数名均为小写字符 有参数名均为小写字符 有参数名均为小写字符 ，例如 appId 在排序后字符串则为 appid ；
b. 对 string1 作签名算法 ， 字段名和字段值都采用原始值 （ 此时此时此时此时 package package package package 的 的 的 的 value value value value 就对应 就对应 就对应 就对应
了使用 了使用 了使用 了使用 2.6 2.6 2.6 2.6 中描述的方式生成的 中描述的方式生成的 中描述的方式生成的 中描述的方式生成的 package package package package ） ，不进行URL转义 。具体签名算法为 paySign =
SHA1(string) 。*/	
protected function create_noncestr( $length = 16 ) {  
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";  
		$str ="";  
		for ( $i = 0; $i < $length; $i++ )  {  
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
			//$str .= $chars[ mt_rand(0, strlen($chars) - 1) ];  
		}  
		return $str;  
	}
	public function get_biz_sign($bizObj){
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
	public function formatQueryParaMap($paraMap, $urlencode){
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v){
			if (null != $v && "null" != $v && "sign" != $k) {
			    if($urlencode){
				   $v = urlencode($v);
				}
				$buff .= $k . "=" . $v . "&";
			}
		}
		$reqPar;
		if (strlen($buff) > 0) {
			$reqPar = substr($buff, 0, strlen($buff)-1);
		}
		return $reqPar;
	}
/*签名 end*/
}
