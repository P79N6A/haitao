<?php
/**
 * 微信原生支付回调
 */
if(!defined("QQCMS")) exit("Access Denied"); 
include_once("pay/SDKRuntimeException.class.php");
class WcpayAction extends Action
{

	protected $parameters; //cft 参数
	protected $xml_data = array();
	function __construct(){
		parent::__construct();
		define(APPID , "wx043ee7bba73960f5");  //appid
		define(APPKEY ,"iI3X9TfTgLCt5S5JTi7dbqedDkJsDSTo4mM9IsuXHxmcTRoI9qThO7qSmDNsW9hrqx38ss1AsNfMD5sHskUcmj3bnM6hnkGek0Oe5ffYwYd6t8KSnCvHowjJLmXza5gV"); //paysign key
		define(SIGNTYPE, "sha1"); //method
		define(PARTNERKEY,"j5810052390701440105000255886win");//通加密串
		define(APPSERCERT, "834e74326be51153a4a553261f455d12");
	}
	public function index(){
		$xml = file_get_contents("php://input"); 
		$this->xml_data = $this->parseXML($xml);
		//$this->writelog($_REQUEST,$this->xml_data,"test_pay0.txt");//写入测试文档
		$this->setParameter("bank_type", "WX");
		$this->setParameter("partner", "1218154401");
		$this->setParameter("fee_type", "1");
		$this->setParameter("notify_url", "http://121.40.82.20/index.php?a=native_back&m=Getresult");
		$this->setParameter("spbill_create_ip", "127.0.0.1");
		$this->setParameter("input_charset", "GBK");
		$this->checkSign($this->xml_data);//检查签名
		$order=M("wechat_order")->where(" status=0 and id=".$this->xml_data['ProductId'])->find();
		//file_put_contents("test22.txt", $order);
		if(empty($order)||empty($order['sn'])){
			$this->setParameter("body", "test");
			$this->setParameter("total_fee", "1");
		$this->setParameter("out_trade_no", $this->create_noncestr());
			echo $this->create_native_package(1,"抱歉，您的订单已过期");die();
		}
		$str_body="有酒派-";
		switch ($order['type']) {
			case '1':
				$str_body.="充值";
				break;
			
			case '2':
				$str_body.="缴纳平台管理费";
				break;
			
			case '3':
				$str_body.="缴纳年费";
				break;
			
			case '4':
				$str_body.="缴纳平台保证金";
				break;
			
			default:
				$str_body.="未知收费，请退出支付";
				break;
		}
		$this->setParameter("body", $str_body);
		$order_fee=$order['amount']*100;
		$this->setParameter("total_fee", (string)$order_fee);
		file_put_contents("test23", $order['amount']);
		$this->setParameter("out_trade_no", $order['sn']);
		echo $this->create_native_package(0,"");die();

	}
function writelog($arr,$str1,$file)
{
	$str="";
	foreach ($arr as $key => $value) {
			$str.=$key.":".$value."===";
		}
	foreach ($str1 as $key => $value) {
			$str.=$key.":".$value."===";
		}
	$open=fopen($file,"a");
	fwrite($open,$str);
	fclose($open);
	return true;
}
	/*
	 * 解析xml
	 * 独立一个方法出来，如果以后发现这解析太简单，有出错，直接在此修改
	 * xml_str => xml_arr
	 */
	private function parseXML($xmlStr){
		$xmlStr = (array)simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA);
		return $xmlStr;
	}
	protected function checkSign($data){
		    $nativeObj["appid"] = APPID;
		    $nativeObj["productid"] = $data['ProductId'];
		    $nativeObj["timestamp"] = $data['TimeStamp'];
		    $nativeObj["noncestr"] = $data['NonceStr'];
		    $nativeObj["openid"] = $data['OpenId'];
		    $nativeObj["issubscribe"] = $data['IsSubscribe'];
		    $sign= $this->get_biz_sign($nativeObj);
		    if($sign!=$data['AppSignature']){
		    	echo $this->create_native_package(1,"验证出错");die();
		    }
		    return true;
	}

/*原微信 WxpayHelper.php demo*/

	function setParameter($parameter, $parameterValue) {
		$this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
	}
	function getParameter($parameter) {
		return $this->parameters[$parameter];
	}
	protected function create_noncestr( $length = 16 ) {  
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";  
		$str ="";  
		for ( $i = 0; $i < $length; $i++ )  {  
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
			//$str .= $chars[ mt_rand(0, strlen($chars) - 1) ];  
		}  
		return $str;  
	}
	function check_cft_parameters(){
		if($this->parameters["bank_type"] == null || $this->parameters["body"] == null || $this->parameters["partner"] == null || 
			$this->parameters["out_trade_no"] == null || $this->parameters["total_fee"] == null || $this->parameters["fee_type"] == null ||
			$this->parameters["notify_url"] == null || $this->parameters["spbill_create_ip"] == null || $this->parameters["input_charset"] == null
			)
		{
			return false;
		}
		return true;

	}
	protected function get_cft_package(){
		try {
			
			if (null == PARTNERKEY || "" == PARTNERKEY ) {
				throw new SDKRuntimeException("密钥不能为空！" . "<br>");
			}
			ksort($this->parameters);
			$unSignParaString = $this->formatQueryParaMap($this->parameters, false);
			$paraString = $this->formatQueryParaMap($this->parameters, true);

			return $paraString . "&sign=" . $this->sign($unSignParaString,$this->trimString(PARTNERKEY));
		}catch (SDKRuntimeException $e)
		{
			die($e->errorMessage());
		}

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
	//生成原生支付url
	/*
	weixin://wxpay/bizpayurl?sign=XXXXX&appid=XXXXXX&productid=XXXXXX&timestamp=XXXXXX&noncestr=XXXXXX
	*/
	function create_native_url($productid){
		    $nativeObj["appid"] = APPID;
		    $nativeObj["productid"] = urlencode($productid);
		    $nativeObj["timestamp"] = time();
		    $nativeObj["noncestr"] = $this->create_noncestr();
		    $nativeObj["sign"] = $this->get_biz_sign($nativeObj);
		    $bizString = $this->formatBizQueryParaMap($nativeObj, false);
		    return "weixin://wxpay/bizpayurl?".$bizString;
		    
	}
	//生成原生支付请求xml
	/*
	<xml>
    <AppId><![CDATA[wwwwb4f85f3a797777]]></AppId>
    <Package><![CDATA[a=1&url=http%3A%2F%2Fwww.qq.com]]></Package>
    <TimeStamp> 1369745073</TimeStamp>
    <NonceStr><![CDATA[iuytxA0cH6PyTAVISB28]]></NonceStr>
    <RetCode>0</RetCode>
    <RetErrMsg><![CDATA[ok]]></ RetErrMsg>
    <AppSignature><![CDATA[53cca9d47b883bd4a5c85a9300df3da0cb48565c]]>
    </AppSignature>
    <SignMethod><![CDATA[sha1]]></ SignMethod >
    </xml>
	*/
	function create_native_package($retcode = 0, $reterrmsg = "ok"){
		 try {
		   if($this->check_cft_parameters() == false && $retcode == 0) {   //如果是正常的返回， 检查财付通的参数
			   throw new SDKRuntimeException("生成package参数缺失！" . "<br>");
		    }
		    $nativeObj["AppId"] = APPID;
		    $nativeObj["Package"] = $this->get_cft_package();
		    $nativeObj["TimeStamp"] = time();
		    $nativeObj["NonceStr"] = $this->create_noncestr();
		    $nativeObj["RetCode"] = $retcode;
		    $nativeObj["RetErrMsg"] = $reterrmsg;
		    $nativeObj["AppSignature"] = $this->get_biz_sign($nativeObj);
		    $nativeObj["SignMethod"] = SIGNTYPE;

		    return  $this->arrayToXml($nativeObj);
		   
		}catch (SDKRuntimeException $e)
		{
			die($e->errorMessage());
		}		

	}

/*end*/
/*commonUtil.php demo */
	function genAllUrl($toURL, $paras) {
		$allUrl = null;
		if(null == $toURL){
			die("toURL is null");
		}
		if (strripos($toURL,"?") =="") {
			$allUrl = $toURL . "?" . $paras;
		}else {
			$allUrl = $toURL . "&" . $paras;
		}

		return $allUrl;
	}

	/**
	 * 
	 * 
	 * @param src
	 * @param token
	 * @return
	 */
	function splitParaStr($src, $token) {
		$resMap = array();
		$items = explode($token,$src);
		foreach ($items as $item){
			$paraAndValue = explode("=",$item);
			if ($paraAndValue != "") {
				$resMap[$paraAndValue[0]] = $parameterValue[1];
			}
		}
		return $resMap;
	}
	
	/**
	 * trim 
	 * 
	 * @param value
	 * @return
	 */
	function trimString($value){
		$ret = null;
		if (null != $value) {
			$ret = $value;
			if (strlen($ret) == 0) {
				$ret = null;
			}
		}
		return $ret;
	}
	
	function formatQueryParaMap($paraMap, $urlencode){
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
	function formatBizQueryParaMap($paraMap, $urlencode){
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v){
		//	if (null != $v && "null" != $v && "sign" != $k) {
			    if($urlencode){
				   $v = urlencode($v);
				}
				$buff .= strtolower($k) . "=" . $v . "&";
			//}
		}
		$reqPar;
		if (strlen($buff) > 0) {
			$reqPar = substr($buff, 0, strlen($buff)-1);
		}
		return $reqPar;
	}
	function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
        	 if (is_numeric($val))
        	 {
        	 	$xml.="<".$key.">".$val."</".$key.">"; 

        	 }
        	 else
        	 	$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";  
        }
        $xml.="</xml>";
        return $xml; 
    }
	
/**/
/*MD5SignUtil.php demo*/
	
	function sign($content, $key) {
	    try {
		    if (null == $key) {
			   throw new SDKRuntimeException("财付通签名key不能为空！" . "<br>");
		    }
			if (null == $content) {
			   throw new SDKRuntimeException("财付通签名内容不能为空" . "<br>");
		    }
		    $signStr = $content . "&key=" . $key;
		
		    return strtoupper(md5($signStr));
		}catch (SDKRuntimeException $e)
		{
			die($e->errorMessage());
		}
	}
	
	function verifySignature($content, $sign, $md5Key) {
		$signStr = $content . "&key=" . $md5Key;
		$calculateSign = strtolower(md5($signStr));
		$tenpaySign = strtolower($sign);
		return $calculateSign == $tenpaySign;
	}
/*end*/
}
?>