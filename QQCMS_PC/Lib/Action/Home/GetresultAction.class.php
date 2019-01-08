<?php
/**
 * 微支付接收返回数据
 */
if(!defined("QQCMS")) exit("Access Denied"); 
include_once("pay/SDKRuntimeException.class.php");
class GetresultAction extends Action
{
	protected $parameters; //cft 参数
	protected $xml_data = array();
	protected $appid="wx024d0a4cfb7d58eb";//用此来验证
	protected $partner="1240081302";//用此来验证
	function __construct(){
		parent::__construct();
		define(APPID , "wx043ee7bba73960f5");  //appid
		define(APPKEY ,"iI3X9TfTgLCt5S5JTi7dbqedDkJsDSTo4mM9IsuXHxmcTRoI9qThO7qSmDNsW9hrqx38ss1AsNfMD5sHskUcmj3bnM6hnkGek0Oe5ffYwYd6t8KSnCvHowjJLmXza5gV"); //paysign key
		define(SIGNTYPE, "sha1"); //method
		define(PARTNERKEY,"j5810052390701440105000255886win");//通加密串
		define(APPSERCERT, "834e74326be51153a4a553261f455d12");
	}
	
	
	/*商品支付完成异步通知*/
	public  function index(){
		$data=$_REQUEST;
		//$this->writelog($_REQUEST,$xml_data=array(),"test_pay.txt");//写入测试文档
		$xml=file_get_contents('php://input');
		$xml_data=$this->get_xml($xml);//转换成数组
		if($data['partner']==$this->partner && $xml_data['AppId']==$this->appid){
		/*通过验证*/
		$order=M('order')->field("id,sn,userid,pay_status,wechat_amount,is_private")->where("sn=".$_REQUEST['out_trade_no'])->find();
		/*查出订单，并更改订单状态*/
		if(!empty($order) && $order['pay_status']==0){//如果支付状态是未支付时就执行
			$this->put_consume($order['wechat_amount'],1,$order['userid'],1);//写入记录表
			/*物流发货*/
			if($order['is_private']!=1)
			{
				$shipping_res=$this->post_shipping($order['sn']);
				if(!$shipping_res['success']){
				$this->put_shipping_error($order['id'],"102",$shipping_res['message']);
				}else{ $this->put_shipping_error($order['id'],"101",$res['message']);}
				$con['shipping_notify']=$shipping_res['success']? 1:2;//已通知发货标记
			}
			$con['id']=$order['id'];
			$con['status']=1;//订单状态变为确认
			$con['pay_status']=2;//支付状态变为已支付
			$con['pay_name']='微信支付';//支付方式变为微信支付
			$con['pay_time']=mktime();//支付时间
			$res=M('order')->save($con);
			//执行电子现金返还
			$order_data=M("order_data")->field("number,ratio,product_id")->where("order_id=".$order['id'])->select();
			$user_radio=0;
			foreach ($order_data as $key => $value) {
				$user_radio+=intval($value['number'])*floatval($value['ratio']);
				$product_data = M("Product")->field("id,stock")->where("id=".$order_data['product_id'])->find();
				M("product")->where("id=".$product_data['id'])->setDec('stock',$value['number']);//扣库存
			}
			$user=M("user")->field("cash_use")->where("id=".$order['userid'])->find();
			$user_data['id']=$order['userid'];
			$user_data['cash_use']=floatval($user['cash_use'])+$user_radio;
			$res_cash=M("user")->save($user_data);
			if($res_cash){
			$this->put_consume($user_radio,5,$order['userid'],1);//写入记录表
			}
			//电子现金end
			$string = '订单号:'.$_REQUEST['out_trade_no'].', 支付方式：微信支付, 发货标记：'.$con['shipping_notify'].', 支付状态：'.$con['pay_status'].', 订单状态保存结果：'.$res."\r\n";
			$_order_ = fopen("order_msg_m.txt","a");
			fwrite($_order_, $string);
			fclose($_order_);
		}
			/*订单操作 end*/
		echo 'success';exit();
		}else{
			echo "fail";exit();
		}
	}	
	
	/*其他原生支付异步通知*/
	public  function native_back(){
		$data=$_REQUEST;
		$xml=file_get_contents('php://input');
		$xml_data=$this->get_xml($xml);//转换成数组
		//$this->writelog($_REQUEST,$xml_data,"test_native0.txt");//写入测试文档
		if($data['partner']==$this->partner && $xml_data['AppId']==$this->appid){
			/*通过验证*/
			$order=M('wechat_order')->where("sn=".$_REQUEST['out_trade_no'])->find();
			
			/*查出订单，并更改订单状态*/
			if(!empty($order)){
				$data['id']=$order['id'];
				$data['pay_time']=mktime();
				$data['status']=1;
				$res=M("wechat_order")->save($data);
				$consume['create_time']=mktime();
				if($res){
					$user=M("user")->field("cash_use,receipt")->where("id=".$order['userid'])->find();
					switch ($order['type']) {
						case '1'://充值
							$data_user['cash_use']=floatval($user['cash_use'])+floatval($order['amount']);
							$consume['source']=4;
							$consume['cash']=floatval($order['amount']);
							/*检查充值是否足够升级*/
							$gold=M('role')->field('gold_money,gold_fee')->where("id=4")->find();
							$gold_money=$gold ? floatval($gold['gold_money']):0;//一次性充值，变为电子现金
							if($consume['cash']>=$gold_money){
							$this->menber_level($order['userid'],$consume['cash'],$consume['create_time']);
							}
							break;
						case '4'://押金
							$data_user['receipt']=floatval($user['receipt'])+floatval($order['amount']);
							$data_user['test_status']=1;
							$data_user['status']=1;
							$consume['source']=3;
							$consume['cash']=floatval($order['amount']);
							break;
						case '2'://平台管理费
							$consume['source']=6;
							$consume['pay_for_time']=$order['pay_for_time'];
							$consume['cash']=floatval($order['amount']);
							break;
						case '3'://年费
							$consume['source']=2;
							$consume['pay_for_time']=$order['pay_for_time'];
							$consume['cash']=floatval($order['amount']);
							$this->menber_level($order['userid'],$consume['cash'],$consume['create_time']);
							break;
						default:
							# code...
							break;
					}
					if(!empty($data_user)){
						$data_user['id']=$order['userid'];
						M("user")->save($data_user);
					}
					if(!empty($consume)){
						$consume['order_id']=$order['id'];
						$consume['user_id']=$order['userid'];
						$consume['pay_type']=1;
						M("consume")->add($consume);
					}
				}else{echo "fail";exit();}
			}
			/*订单操作 end*/
			echo 'success';exit();
		}else{
			echo "fail";exit();
		}
	}


	###微信支付统一下单通知###
	public function wxNotice(){
		$data=$_REQUEST['xml_data'];
		$pay_data = json_decode($data,true);
		$MakeSign = $this->MakeSign($pay_data);//检查签名
		$signStr = '返回参数：'.var_export($pay_data,true)."\r\n"."++++++++++++++++分割线++++++++++++++++"."\r\n"."返回签名：".$pay_data['sign']."++++++++++++++++分割线++++++++++++++++"."\r\n"."\r\n"."计算签名：".$MakeSign."==================结束=================="."\r\n";
		$pay_data_sign = fopen("pay_data_sign.txt","a");
		fwrite($pay_data_sign, $signStr);
		fclose($pay_data_sign);

		if ($pay_data['sign'] != $MakeSign)
		{
			echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';exit();
		}
		
		if($pay_data['result_code'] == 'SUCCESS' && $pay_data['appid'] == $this->appid && $pay_data['mch_id'] == $this->partner){
			/*通过验证*/
			if ($pay_data['attach'] > 0 && $pay_data['attach'] < 5)
			{
				$whereor['re_sn'] = $pay_data['out_trade_no'];
				$whereor['status']=0;
				$order=M('Wechat_order')->where($whereor)->find();
				F('Wechat_order',$order);
			}
			else
			{
				$whereor['sn'] = $pay_data['out_trade_no'];
				$whereor['status']=0;
				$whereor['pay_status']=0;
				$order=M('order')->field("id,sn,type,userid,pay_status,wechat_amount,is_private")->where($whereor)->find();
			}

			if (empty($order)){
				echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';exit();
			}
			
			/*查出订单，并更改订单状态*/
			$user=M("user")->field("id,cash_use,receipt,share")->where("id=".$order['userid'])->find();
			switch ($order['type']) {
				case 1://充值
					$_data['id']=$order['id'];
					$_data['pay_time']=mktime();
					$_data['status']=1;
					$res=M("wechat_order")->save($_data);
					$consume['create_time']=mktime();
					$data_user['cash_use']=floatval($user['cash_use'])+floatval($order['amount']);
					$consume['source']=4;
					$consume['cash']=floatval($order['amount']);
					/*检查充值是否足够升级*/
					$gold=M('role')->field('gold_money,gold_fee')->where("id=4")->find();
					$gold_money=$gold ? floatval($gold['gold_money']):0;//一次性充值，变为电子现金
					if($consume['cash']>=$gold_money){
						$this->menber_level($order['userid'],$consume['cash'],$consume['create_time']);
					}
					break;
				case 4://押金
					$_data['id']=$order['id'];
					$_data['pay_time']=mktime();
					$_data['status']=1;
					$res=M("wechat_order")->save($_data);
					$consume['create_time']=mktime();

					$data_user['receipt']=floatval($user['receipt'])+floatval($order['amount']);
					$data_user['test_status']=1;
					$data_user['status']=1;
					$consume['source']=3;
					$consume['cash']=floatval($order['amount']);
					break;
				case 2://平台管理费
					$_data['id']=$order['id'];
					$_data['pay_time']=mktime();
					$_data['status']=1;
					$res=M("wechat_order")->save($_data);
					$consume['create_time']=mktime();

					$consume['source']=6;
					$consume['pay_for_time']=$order['pay_for_time'];
					$consume['cash']=floatval($order['amount']);
					break;
				case 3://年费
					$_data['id']=$order['id'];
					$_data['pay_time']=mktime();
					$_data['status']=1;
					$res=M("wechat_order")->save($_data);
					$consume['create_time']=mktime();

					$consume['source']=2;
					$consume['pay_for_time']=$order['pay_for_time'];
					$consume['cash']=floatval($order['amount']);
					$this->menber_level($order['userid'],$consume['cash'],$consume['create_time']);
					break;
				default:
					if(!empty($order) && !$order['pay_status']){
						//如果支付状态是未支付时就执行
						$this->put_consume($order['order_amount'],1,$order['userid'],1);//写入记录表
						
						$con['id']=$order['id'];
						$con['status']=1;//订单状态变为确认
						$con['pay_status']=2;//支付状态变为已支付
						$con['pay_id'] = 1;//订单支付类型为微信支付
						$con['pay_name'] = '微信支付';//订单支付类型为微信支付
						$con['pay_time']=mktime();//支付时间
						$res=M('order')->save($con);
						//执行电子现金返还
						$order_data=M("order_data")->field("product_id,number,ratio")->where("order_id=".$order['id'])->select();
						$user_radio=0;
						foreach ($order_data as $key => $value) {
							$user_radio+=intval($value['number'])*floatval($value['ratio']);
						}

						$user_data['id']=$order['userid'];
						$user_data['cash_use']=floatval($user['cash_use'])+$user_radio;
						$res_cash=M("user")->save($user_data);
						if($res_cash){
							$this->put_consume($user_radio,5,$order['userid'],1);//写入记录表
						}
						//电子现金end
						##判断用户是否首次消费##
						if ($user['share'] == 1)
						{
							##改变消费状态##
							$rcwhere['entrants'] = $user['id'];
							$rcwhere['status'] = 0;
							$rcshare = M('Rcshare')->where($rcwhere)->find();
							if (!empty($rcshare))
							{
								##推荐用户##
								$sharer = M('User')->field('id,cash_use')->find($rcshare['sharer']);
								$sharer['cash_use'] += 100;
								M("User")->save($sharer);
								$rcshare['status'] = 1;
								M('Rcshare')->save($rcshare);
							}
							$share['id'] = $user['id'];
							$share['share'] = 2;
							M("User")->save($share);
						}	
					}					
				break;
			}
			$string = '订单号:'.$pay_data['out_trade_no'].', 支付方式：微信支付, 发货标记：'.$con['shipping_notify'].', 支付状态：'.$con['pay_status'].', 订单状态保存结果：'.$res."\r\n";
			$_order_ = fopen("order_wxmsg.txt","a");
			fwrite($_order_, $string);
			fclose($_order_);
			if(!empty($data_user)){
				$data_user['id']=$order['userid'];
				M("user")->save($data_user);
			}
			if(!empty($consume)){
				$consume['order_id']=$order['id'];
				$consume['user_id']=$order['userid'];
				$consume['pay_type']=1;
				M("consume")->add($consume);
			}
			/*订单操作 end*/
			echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg></return_msg></xml>';exit();
		}
		else
		{
			echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[参数格式校验错误]]></return_msg></xml>';exit();
		}
	}
	
	
	###商品原生支付异步通知###
	public function anweisung(){
		$data=$_REQUEST;
		$xml=file_get_contents('php://input');
		$xml_data=$this->get_xml($xml);//转换成数组
		$this->checkSign($xml_data);//检查签名
		if($data['partner']==$this->partner && $xml_data['AppId']==$this->appid){
			/*通过验证*/
			$whereor['sn'] = $_REQUEST['out_trade_no'];
			$order=M('order')->field("id,sn,userid,pay_status,wechat_amount,is_private")->where($whereor)->find();
			/*查出订单，并更改订单状态*/
			if(!empty($order) && $order['pay_status']==0){//如果支付状态是未支付时就执行
				$this->put_consume($order['wechat_amount'],1,$order['userid'],1);//写入记录表
				
				$con['id']=$order['id'];
				$con['status']=1;//订单状态变为确认
				$con['pay_status']=2;//支付状态变为已支付
				$con['pay_time']=mktime();//支付时间
				$res=M('order')->save($con);
				//执行电子现金返还,扣库存
				$order_data=M("order_data")->field("product_id,number,ratio")->where("order_id=".$order['id'])->select();
				$user_radio=0;
				foreach ($order_data as $key => $value) {
					$user_radio+=intval($value['number'])*floatval($value['ratio']);
					M("product")->where("id=".$value['product_id'])->setDec('stock',$value['number']);//扣库存
				}

				$user=M("user")->field("cash_use")->where("id=".$order['userid'])->find();
				$user_data['id']=$order['userid'];
				$user_data['cash_use']=floatval($user['cash_use'])+$user_radio;
				$res_cash=M("user")->save($user_data);
				if($res_cash){
					$this->put_consume($user_radio,5,$order['userid'],1);//写入记录表
				}
				//电子现金end
				
				/*物流发货*/
				if($order['is_private']!=1 && $order['userid']!=5209)
				{
					//$shipping_res=$this->post_shipping($order['sn']);
					if(!$shipping_res['success']){
						$this->put_shipping_error($order['id'],"102",$shipping_res['message']);
					}else{$this->put_shipping_error($order['id'],"101",$shipping_res['message']);}
					$con['shipping_notify']=$shipping_res['success']? 1:2;//已通知发货标记
					//$con['shipping_notify']=1;//测试
				}

				$string = '订单号:'.$_REQUEST['out_trade_no'].', 支付方式：微信支付, 发货标记：'.$con['shipping_notify'].', 支付状态：'.$con['pay_status'].', 订单状态保存结果：'.$res."\r\n";
				$_order_ = fopen("order_msg.txt","a");
				fwrite($_order_, $string);
				fclose($_order_);	
			}
			/*订单操作 end*/
			echo 'success';exit();
		}
		else
		{
			echo "fail";exit();
		}
	}

/**/	
//自动升级
public function menber_level($userid,$gold_fee,$time){
		$us=array();
	$us=M('user')->field('id,groupid,lastrecharge_time')->where('id='.$userid)->find();
	if($us['groupid']==3){
			/*升级*/
			$da['id']=$userid;
			$da['groupid']=4;//金会员为4
			$da['lastrecharge_time']=$time;
			M('user')->save($da);
	}
	return true;
}
public function put_consume($cash=0,$source=0,$user_id=0,$type=0){
			$data['user_id']=$user_id;
			$data['source']=$source;
			$data['pay_type']=$type;
			$data['cash']=floatval($cash);
			$data['create_time']=mktime();
			M("consume")->add($data);
			return true;
	}
public function post_shipping($sn=0){
			$method="PostOrder";//方法
			$Key="B49e7d57ca6643102dbec749ae8c1b6e";//加密串
			$ordernum=$sn;//订单号
			$type=0;//0为新增订单
			$curl_url="http://wms.hans-trans.com/wms/interface/QueryService.aspx?method=".$method."&key=".$Key."&ordernum=".$ordernum."&type=".$type;
			$curl_result = file_get_contents($curl_url);
			$res=json_decode($curl_result,TRUE);
			return $res;
}
	function get_xml($str){
	$xmlStr = (array)simplexml_load_string($str, 'SimpleXMLElement', LIBXML_NOCDATA);
	return $xmlStr;
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
	function get_alarm(){
		echo "success";exit();
	}

 /*物流信息反馈*/
	public function put_shipping_error($id=0,$type=0,$msg=""){
		$data['order_id']=$id;
		$data['message']=$msg;
		$data['createtime']=mktime();
		$data['type']=$type;
		M("shipping_msg")->add($data);
		return true;
	}
 /**/
 
 	###验证签名###
 	protected function checkSign($data){
			$nativeObj["appid"] = APPID;
			$nativeObj["timestamp"] = $data['TimeStamp'];
			$nativeObj["noncestr"] = $data['NonceStr'];
			$nativeObj["openid"] = $data['OpenId'];
			$nativeObj["issubscribe"] = $data['IsSubscribe'];
			$sign= $this->get_biz_sign($nativeObj);
			if($sign!=$data['AppSignature']){
				echo "fail";exit();
			}
			return true;
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