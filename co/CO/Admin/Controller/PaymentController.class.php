<?php
/*微信原生支付生成URL
/**/
namespace Admin\Controller;
use Think\Controller;

define(APPID , "wx043ee7bba73960f5");  //appid
define(APPKEY ,"iI3X9TfTgLCt5S5JTi7dbqedDkJsDSTo4mM9IsuXHxmcTRoI9qThO7qSmDNsW9hrqx38ss1AsNfMD5sHskUcmj3bnM6hnkGek0Oe5ffYwYd6t8KSnCvHowjJLmXza5gV"); //paysign key

class PaymentController extends CommController {

	public function index(){
		$id = session('uid');
		$user=M("user")->where("id=".$id)->find();
		/*if(!intval($user['test_status'])){$receipt_order=$this->get_recepit($id);
		$receipt_url="http://".$_SERVER['HTTP_HOST'].'/qr/shop_pay_code.php?shop_id='.$id."&url=".urlencode($receipt_order);
		$this->assign("receipt_url",$receipt_url);
		}else{
			$order=M("wechat_order")->where("userid=".$id." and type=4")->order("id desc")->find();
			if($order['status']==0||empty($order)){
				$consume=M("consume")->where("user_id=".$id." and source=3 and pay_type=3")->order(" id desc")->find();
			$this->assign("consume",$consume);
			}
			else
			$this->assign("order",$order);
		}*/
		//$url=$this->create_native_url(12);
		$this->display();
	}
	public function manage_pay(){
		$id = session('uid');
		$user=M("user")->where("id=".$id)->find();
		if($this->_istime_pay){
			//$this->_istime_pay在CommController类里面定义
			$role=M("role")->where("id=".$user['groupid'])->find();
			if(in_array($role['id'],array(6,8,9,10,11,12,13))){
				if(floatval($role['payment'])<=0){$this->error("抱歉,我们还没设置您的缴费金额");die();}
				$manage_order=$this->get_manage($this->_pay_for_time,$id,floatval($role['payment']),$user['parent_id'],floatval($role['parent_splitt']));
				$manage_url="http://".$_SERVER['HTTP_HOST'].'/qr/shop_pay_code.php?shop_id='.$id."&url=".urlencode($manage_order);
				$this->assign("manage_url",$manage_url);
			}else{
				$this->error("抱歉，您的等级不需支付平台管理费");die();
			}
		}
		$order=M("wechat_order")->where("userid=".$id." and type=2")->order("id desc")->select();
		$this->assign("order",$order);
		$this->display();
	}	
	public function get_manage($time=0,$userid=0,$amount=0,$parent_id=0,$parent_splitt=0){
		$model=M("wechat_order");
		$manage_order=$model->where("status=0 and type=2 and userid=".$userid." and pay_for_time=".$time)->find();
		if(!empty($manage_order)){
			$url=$this->create_native_url($manage_order['id']);
		}else{//生成订单
			$order['userid']=$userid;
			$order['amount']=$amount;
			$order['pay_for_time']=$time;
			$order['parent_amount']=$amount*$parent_splitt;
			$order['status']=0;
			$order['type']=2;
			$order['parent_shopid']=$parent_id;
			$order['createtime']=mktime();
			$orderid=$model->add($order);
			if($orderid){
				$order['sn'] = date("Ymd"). sprintf('%06d',$orderid);
				$model->save(array('id'=>$orderid,'sn'=>$order['sn'])); 
				$url=$this->create_native_url($orderid);
			}else{
				$this->error("网络错误");
			}
		}
		return $url;
	}
	public function get_recepit($userid){
		$model=M("wechat_order");
		$receipt_order=$model->where("status=0 and type=4 and userid=".$userid)->find();
		if(!empty($receipt_order)){
			$url=$this->create_native_url($receipt_order['id']);
		}else{//生成订单
			$amount=M("config")->field("value")->where(array("varname"=>"shop_receipt_fee"))->find();
			$order['userid']=$userid;
			$order['amount']=floatval($amount['value']);//押金
			$order['status']=0;
			$order['type']=4;
			$order['createtime']=mktime();
			$orderid=$model->add($order);
			if($orderid){
				$order['sn'] = date("Ymd"). sprintf('%06d',$orderid);
				$model->save(array('id'=>$orderid,'sn'=>$order['sn'])); 
				$url=$this->create_native_url($orderid);
			}else{
				$this->error("网络错误");
			}
		}
		return $url;
	}
	public function create_native_url($productid){
		    $nativeObj["appid"] = APPID;
		    $nativeObj["productid"] = urlencode($productid);
		    $nativeObj["timestamp"] = time();
		    $nativeObj["noncestr"] = $this->create_noncestr();
		    $nativeObj["sign"] = $this->get_biz_sign($nativeObj);
		    $bizString = $this->formatBizQueryParaMap($nativeObj, false);
		    return "weixin://wxpay/bizpayurl?".$bizString;
		    
	}
/*以下微信签名函数*/
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
		 			die("APPKEY为空！" . "<br>");
		 			//throw new SDKRuntimeException("APPKEY为空！" . "<br>");
		 	}
		 	$bizParameters["appkey"] = APPKEY;
		 	ksort($bizParameters);
		 	$bizString = $this->formatBizQueryParaMap($bizParameters, false);
		 	//var_dump($bizString);
		 	return sha1($bizString);
		 }catch (SDKRuntimeException $e)
		 {
		 	die("生成签名出错了");
			die($e->errorMessage());
		 }
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

}

?>