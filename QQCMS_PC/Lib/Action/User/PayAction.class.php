<?php
/**
 * PayAction.class.php (支付模块)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if(!defined("QQCMS")) exit("Access Denied");
class PayAction extends BaseAction
{

	function _initialize()
    {	
		parent::_initialize();
		if(!$this->_userid){
			//$this->assign('jumpUrl',U('User/Login/index'));
			//$this->error(L('nologin'));
		}
		$this->dao = M('User');
		$this->assign('bcid',0);
		$user = $this->dao->find($this->_userid);
		$this->assign('vo',$user);
    }

    public function index()
    {
        $this->display();
    }


	public function Recharge()
    {
        $this->display();
    }
	

	public function pay()
    {
        $this->display();
    }
	public function respond()
	{
		$pay_code = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : '';	
		$pay_code = ucfirst($pay_code);
		$Payment = M('Payment')->getByPayCode($pay_code);
		if(empty($Payment))$this->error(L('PAY CODE EROOR!'));
		$aliapy_config = unserialize($Payment['pay_config']);		 
		import("@.Pay.".$pay_code);
		$pay=new $pay_code($aliapy_config);
		$r = $pay->respond();	
		$this->assign('jumpUrl',URL('User-Order/index'));
		if($r){
			$this->error(L('PAY_OK'));
		}else{
			$this->error(L('PAY_FAIL'));
		}
	}

	public function hamc($key,$data)
	{
		//创建MD5的HMAC
		$b = 64;
		if (strlen($key)>$b){
			$key = pack("H*",md5($key));
		}
		$key = str_pad($key,$b,chr(0x00));
		$ipad = str_pad('', $b,chr(0x36));
		$opad = str_pad('', $b,chr(0x5c));
		$k_ipad = $key ^ $ipad;
		$k_opad = $key ^ $opad;
		return md5($k_opad . pack("H*",md5($k_ipad . $data)));
	}


	//通联支付，取货地址,客户的取货地址
	public function payPickup(){
		$sn = $_REQUEST['orderNo'];
		F('0804',$_REQUEST);
		if($sn){
			if ($_REQUEST['payResult'] == 1)
			{
				$this->assign('jumpUrl',URL('User-Order/index'));
				$this->error(L('PAY_OK'));
			}
			else
			{
				$this->error("支付订单已提交，未支付成功！");
			}
		}else{
			$this->error(L('PAY_FAIL'));
		}
	}

	public function genXml($order) {	
		$senderID = 'SYBS';
		$signkey = '39D0C748369D4F579B00334F5A4FCF5E';
		$orgSign = '<VnbMessage>';
		$orgSign .= '<MessageHead>';
		$orgSign .= '<MessageCode>VNB3PARTY_PAYVOUCHER</MessageCode>';
		$orgSign .= '<MessageID>'.$order[MessageID].'</MessageID>';
		$orgSign .= '<SenderID>'.$senderID.'</SenderID>';
		$orgSign .= '<SendTime>'.$order[SendTime].'</SendTime>';
		$orgSign .= '<Sign></Sign>';
		$orgSign .= '</MessageHead>';
		$orgSign .= '<MessageBodyList>';
		$orgSign .= '<MessageBody>';
		$orgSign .= '<customICP>'.$order[customICP].'</customICP>';
		$orgSign .= '<orderNo>'.$order[orderNo].'</orderNo>';
		$orgSign .= '<payTransactionNo>'.$order[payTransactionNo].'</payTransactionNo>';
		$orgSign .= '<payChnlID>'.$order[payChnlID].'</payChnlID>';
		$orgSign .= '<payTime>'.$order[payTime].'</payTime>';
		$orgSign .= '<payGoodsAmount>'.$order[payGoodsAmount].'</payGoodsAmount>';
		$orgSign .= '<payTaxAmount>'.$order[payTaxAmount].'</payTaxAmount>'; //税款
		$orgSign .= '<freight>'.$order[freight].'</freight>';
		$orgSign .= '<payCurrency>142</payCurrency>';
		$orgSign .= '<payerName>'.trim($order[payerName]).'</payerName>';
		$orgSign .= '<payerDocumentType>'.$order[payerDocumentType].'</payerDocumentType>';
		$orgSign .= '<payerDocumentNumber>'.$order[payerDocumentNumber].'</payerDocumentNumber>';
		$orgSign .= '</MessageBody>';
		$orgSign .= '</MessageBodyList>';
		$orgSign .= '</VnbMessage>';
		
		
		$md5_Sign = strtoupper(md5($orgSign.$signkey));
		$orgSign = preg_replace("/<Sign>(.*)<\/Sign>/", '<Sign>'.$md5_Sign.'</Sign>', $orgSign);
		$orgSign = '<?xml version="1.0" encoding="UTF-8"?>'.$orgSign;
		return $orgSign;
	}

	public function rechargeReceive()
	{
		$posinfostr = '##star##：'."\r\n";
        $posinfostr .= var_export($_REQUEST,true)."\r\n";
        $posstr = fopen("icp_log/payReceive.txt","a");
        $posinfostr .= '##end##：'."\r\n";
        fwrite($posstr, $posinfostr);
        fclose($posstr);
        $cart['status'] = 1;
		if($value == 2) $cart['pay_time'] = strtotime($_REQUEST['payDatetime']);
		$sn = $_REQUEST['orderNo'];
		if (!$sn) return false; //参数不完整，支付失败
		$order = M('Wechat_order')->where(array('sn'=>$sn))->find();

		/*查出订单，并更改订单状态*/
		if(!empty($order) && $_REQUEST['payResult']==1){
			$data['id'] = $order['id'];
			$data['pay_time'] = mktime();
			$data['status'] = 1;
			$res = M("wechat_order")->save($data);
			$consume['create_time'] = mktime();
			if($res){
				$user = M("user")->field("cash_use,receipt")->where("id=".$order['userid'])->find();
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

				$string = '订单号:'.$sn.', 支付方式：通联支付,  支付状态：成功, 订单状态保存结果：'.$res."\r\n";
				$_order_ = fopen("order_msg.txt","a");
				fwrite($_order_, $string);
				fclose($_order_);
				echo 'success';exit();
			}else{
				echo "fail";exit();
			}
		}
		else{
			echo "fail";exit();
		}
		/*订单操作 end*/
		$string = '订单号:'.$sn.', 支付方式：通联支付,  支付状态：失败, 订单状态保存结果：'.$res."\r\n";
		$_order_ = fopen("order_msg.txt","a");
		fwrite($_order_, $string);
		fclose($_order_);
	}

	public function payReceive(){
		$posinfostr = '##star##：'."\r\n";
        $posinfostr .= var_export($_REQUEST,true)."\r\n";
        $posstr = fopen("icp_log/payReceive.txt","a");
        $posinfostr .= '##end##：'."\r\n";
        fwrite($posstr, $posinfostr);
        fclose($posstr);
		$cart['status'] = 1;
		if($value == 2) $cart['pay_time'] = strtotime($_REQUEST['payDatetime']);
		$sn = $_REQUEST['orderNo'];
		if (!$sn) return false; //参数不完整，支付失败
		$order = M('Order')->where(array('sn'=>$sn))->find();

		switch ($_REQUEST['ext1']) {
			case 'mobile_pay':
				$order['payChnlID'] = '02';
				break;
			case 'pc_pay':
				$order['payChnlID'] = '01';
				break;
			default:
				$order['payChnlID'] = '01';
				break;
		}

		#订单支付网关交易流水号payTransactionNo  由支付渠道payChnlID确定#
		switch ($order['payChnlID']) {
			case '01': case '02':
				$order['payTransactionNo'] = $_REQUEST['paymentOrderId'];
				break;
			case '03':
				$order['payTransactionNo'] = $_REQUEST['trxid'];
				break;
			case '04':
				$order['payTransactionNo'] = $_REQUEST['payOrderId'];
				break;
			default:
				break;
		}

		##判断订单是否支付成功##
		if(!empty($order['sn']) && $_REQUEST['payResult'] == 1)
		{
			//$cart['pay_status'] =$value;
			//$r = M('Order')->where("sn='{$sn}'")->save($cart);
			//如果支付状态是未支付时就执行
			$this->put_consume($order['order_amount'],1,$order['userid'],1);//写入记录表

			$con['id'] = $order['id'];
			$con['status'] = 1;//订单状态变为确认
			$con['pay_id'] = 3;//订单支付类型为通联支付
			$con['pay_name'] = '通联支付';//订单支付类型为支付宝支付
			$con['pay_status'] = 2;//支付状态变为已支付
			$con['iswallet'] = 1; //设置改订单可发红包
			$con['cod_amount'] = 0.00;//货到付款金额清零
			$con['paymentOrderId'] = $order['payTransactionNo'];//订单支付网关交易流水号
			$con['pay_time']=strtotime($_REQUEST['payDatetime']);//支付时间
			$r=M('order')->save($con);
			//执行电子现金返还
			$order_data=M("order_data")->field("number,ratio,product_id")->where("order_id=".$order['id'])->select();
			$user_radio=0;
			foreach ($order_data as $key => $value) {
				$user_radio+=intval($value['number'])*floatval($value['ratio']);
			}
			
			$user = M("user")->field("id,cash_use,share")->where("id=".$order['userid'])->find();
			$user_data['id']=$order['userid'];
			$user_data['cash_use']=floatval($user['cash_use'])+$user_radio;
			$res_cash=M("user")->save($user_data);

			if($res_cash){
				$this->put_consume($user_radio,5,$order['userid'],1);//写入记录表
			}
			//电子现金end
			
			/*物流发货*/
			if($order['is_private']!=1 && $order['userid']!=5477)
			{
				//$shipping_res=$this->post_shipping($order['sn']);
				if(!$shipping_res['success'])
				{
					//$this->put_shipping_error($order['id'],"102",$shipping_res['message']);
				}
				else
				{
					//$this->put_shipping_error($order['id'],"101",$shipping_res['message']);
				}
				// $con['shipping_notify']=$shipping_res['success']? 1:2;//已通知发货标记
				//$con['shipping_notify']=1;//测试
			}

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

			$string = '订单号:'.$sn.', 支付方式：通联支付, 发货标记：'.$con['shipping_notify'].', 支付状态：'.$con['pay_status'].', 订单状态保存结果：'.$r."\r\n";
			$_order_ = fopen("order_msg.txt","a");
			fwrite($_order_, $string);
			fclose($_order_);				

		}
		/*订单操作 end*/

		#发送订单报文,必须支付已成功#
		$this->vnbapiwebpos($order);
	}

	##发送订单报文##
	public function vnbapiwebpos($order = array())
	{
		#发送订单报文,必须支付已成功#
		if (!empty($_REQUEST['ext2']) && $_REQUEST['payResult']==1)
		{
			$vnbapiweb['MessageID'] = date('YmdHis').'0001'; //报文编号
			$vnbapiweb['SendTime'] = date('Y-m-d H:i:s'); //时间戳

			$eshop_ent_code = M('Payment')->field('customs_config')->where('pay_code=\'Allinpay\'')->find();
			$payment_code = unserialize($eshop_ent_code['customs_config']);

			$vnbapiweb['customICP'] = $payment_code['customICP']; //电商对海关接入企业备案号

			$vnbapiweb['orderNo'] = $_REQUEST['orderNo']; //商户订单号

			$vnbapiweb['payTransactionNo'] = $order['payTransactionNo']; //通联支付流水号

			$vnbapiweb['payChnlID'] = $order['payChnlID']; //支付渠道

			$payDatetime = strtotime($_REQUEST['payDatetime']);

			$vnbapiweb['payTime'] = date('Y-m-d H:i:s',$payDatetime); //支付时间

			//计算支付货款
			$payGoodsAmount = $order['allinipay_amount']-($order['shipping_fee']+$order['direct_total']);

			$vnbapiweb['payGoodsAmount'] = $payGoodsAmount; //支付货款
			$vnbapiweb['payTaxAmount'] = $order['direct_total']; //支付税款
			$vnbapiweb['freight'] = $order['shipping_fee']; //支付运费
			$vnbapiweb['payerName'] = trim(str_replace('　', '  ', $order['identity_name'])); //电商订单注册人姓名
			$vnbapiweb['payerDocumentType'] = '01'; //注册人证件类型
			$vnbapiweb['payerDocumentNumber'] = trim(strtoupper($order['identity'])); //注册人证件号码

			/*计算签名  sign MD5 加密  大写显示*/
			$vnbwebstrpos = var_export($vnbapiweb,true)."\r\n";
			$orgSign = $this->genXml($vnbapiweb);
			$vnbwebstrpos .= $orgSign."\r\n";
            $vs = fopen("vnbwebstrpos.txt","a");
            fwrite($vs, $vnbwebstrpos);
            fclose($vs);
			$Signurl = 'http://113.108.182.4:8090/vnbcustoms/CustomsServlet';
			// http://113.108.182.4:8084/vnbapiweb/VnbApiServlet
			$signReturn = $this->cURLPost($Signurl,$orgSign);
			$signstrpos = $signReturn."\r\n";
            $vss = fopen("signstrpos.txt","a");
            fwrite($vss, $signstrpos);
            fclose($vss);
			##分析返回信息是否为 xml格式##
			$xml_parser = xml_parser_create(); #创建XML解析器#
			$xml_parse = xml_parse($xml_parser,$signReturn);

			if (!$xml_parse)
			{
				xml_parser_free($xml_parser); //释放XML解析器
				//保存日志
				$customs_log['orderId'] = $order['id'];
				$customs_log['orderSn'] = $order['sn'];
				$customs_log['userId'] = $order['userid'];
				$customs_log['retCode'] = $xml_parse;
				$customs_log['retInfo'] = '通讯报文接口返回信息错误！';
				M('Customs_log')->add($customs_log);
				return $xml_parse;
			}

			$signReturn = $this->parseXML($signReturn);
			$signReturn = $this->xmlToArr($signReturn);

			$MessageHeadarr = $signReturn['VnbMessage']['MessageHead'];
			$MessageBodyarr = $signReturn['VnbMessage']['MessageBodyList']['MessageBody'];

			//保存日志
			$customs_log['orderId'] = $order['id'];
			$customs_log['userId'] = $order['userid'];
			$customs_log['orderSn'] = $order['sn'];

			if (!empty($MessageHeadarr))
			{
				$customs_log['commcode'] = $MessageHeadarr['CommCode'];

				switch ($customs_log['commcode']) {
					case '000000':
						$customs_log['bizinfo'] = '通讯成功 || ';
						break;
					case 'HD0001':
						$customs_log['bizinfo'] = '无效的内容长度 || ';
						break;
					case 'HD0002':
						$customs_log['bizinfo'] = '请求报文为空 || ';
						break;
					case 'HD0003':
						$customs_log['bizinfo'] = '报文头格式错误 || ';
						break;
					case 'HD0004':
						$customs_log['bizinfo'] = '报文头必填字段为空 || ';
						break;
					case 'HD0005':
						$customs_log['bizinfo'] = '无效的报文消息码 || ';
						break;
					case 'HD0006':
						$customs_log['bizinfo'] = '无效的接入系统代码 || ';
						break;
					case 'HD0007':
						$customs_log['bizinfo'] = '无效的接入主机IP || ';
						break;
					case 'HD0008':
						$customs_log['bizinfo'] = '签名验签错误，报文签名域不正确 || ';
						break;
					default:
						break;
				}

				$customs_log['bizstatus'] = $MessageHeadarr['BizStatus'];
				switch ($customs_log['bizstatus']) {
					case 'ER0001':
						$customs_log['bizinfo'] .= '报文体格式错误';
						break;
					case 'ER0002':
						$customs_log['bizinfo'] .= '报文体必填字段为空';
						break;
					case 'ER0003':
						$customs_log['bizinfo'] .= '无效的报文体内容';
						break;
					case 'BZ0000':
						$customs_log['bizinfo'] .= '请求受理成功';
						break;
					case 'BZ0001':
						$customs_log['bizinfo'] .= '请求已受理';
						break;
					case 'ER9999':
						$customs_log['bizinfo'] .= '其它错误';
						break;
					default:
						break;
				}
				$customs_log['sendtime'] = $MessageHeadarr['SendTime'];
			}

			if (!empty($MessageBodyarr))
			{
				$customs_log['retCode'] = $MessageBodyarr['retCode'];
				$customs_log['retInfo'] = $MessageBodyarr['retInfo'];
			}
			$Customs_log_info = M('Customs_log')->field('id,orderId')->where('orderId='.$order['id'])->find();
			if (!empty($Customs_log_info))
			{
				$customs_log['id'] = $Customs_log_info['id'];
				$customs_log['bizinfo'] .= '②';
				$customs_log['retInfo'] .= '②';
				M('Customs_log')->save($customs_log);
				return true;
			}
			else
			{
				M('Customs_log')->add($customs_log);
				return true;
			}
		}
		else
		{
			//保存日志
			$customs_log['orderId'] = $order['id'];
			$customs_log['orderSn'] = $order['sn'];
			$customs_log['userId'] = $order['userid'];
			$customs_log['retCode'] = 'ER4500';
			$customs_log['retInfo'] = '订单已提交，未支付成功！';
			M('Customs_log')->add($customs_log);
			return false;
		}
	}

	public function xmlToArr($xml, $root = true) { 

		if (!$xml->children()) { 
			return (string) $xml; 
		} 
		$array = array(); 
		foreach ($xml->children() as $element => $node) { 
			$totalElement = count($xml->{$element}); 
			if (!isset($array[$element])) { 
				$array[$element] = ""; 
			} 
			// Has attributes 
			if ($attributes = $node->attributes()) { 
				$data = array( 
				'attributes' => array(), 
				'value' => (count($node) > 0) ? $this->xmlToArr($node, false) : (string) $node 
				); 
				foreach ($attributes as $attr => $value) { 
					$data['attributes'][$attr] = (string) $value; 
				} 
				if ($totalElement > 1) { 
					$array[$element][] = $data; 
				} else { 
					$array[$element] = $data; 
				} 
				// Just a value 
			} else { 
				if ($totalElement > 1) { 
					$array[$element][] = $this->xmlToArr($node, false); 
				} else { 
					$array[$element] = $this->xmlToArr($node, false); 
				} 
			} 
		}

		if ($root) { 
			return array($xml->getName() => $array); 
		} else { 
			return $array; 
		} 

	}


	public function customsReceipt()
	{
		// F('customsReceipt'.$_GET[data],$_GET);
		$customs_data = $_GET['data'];
		if (!empty($customs_data) && is_string($customs_data))
		{
			$_data = explode('|', $customs_data);
			if (!empty($_data) && is_array($_data))
			{
				$where['paymentOrderId'] = $_data[0];
				$order = M('Order')->field('id,sn')->where($where)->find();
				if (!empty($order))
				{
					$notice['orderId'] = $order['id'];
					$notice['payTransactionNo'] = $_data[0];
					$notice['returnCode'] = $_data[1];
					$notice['returnInfo'] = $_data[2];
					$receipt_notice = M('Receipt_notice')->where(array('payTransactionNo'=>$_data[0]))->find();
				}
				else
				{
					$notice['orderId'] = 0;
					$notice['payTransactionNo'] = 0;
					$notice['returnCode'] = false;
					$notice['returnInfo'] = '订单不存在';
				}

				if (!empty($receipt_notice))
				{
					$notice['id'] = $receipt_notice['id'];
					$notice['returnInfo'] = $_data[2].',订单支付网关交易流水号重复';
					M('Receipt_notice')->save($notice);
				}
				else
				{
					M('Receipt_notice')->add($notice);
				}
			}
			echo 'HTTPSQS_PUT_OK';
		}
		echo 'HTTPSQS_PUT_FAIL';
	}

	 /*
     * 解析xml
     * 独立一个方法出来，如果以后发现这解析太简单，有出错，直接在此修改
     * xml_str => xml_arr
     */
    private function parseXML($xmlStr){
        //$xmlStr = preg_replace('/<!\[CDATA\[(.*?)\]\]>/',"$1",$xmlStr);
        //return (array)simplexml_load_string($xmlStr); //得到对象,强转array

        // $xmlStr = simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xmlStr = simplexml_load_string($xmlStr);
        return $xmlStr;
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
}
?>