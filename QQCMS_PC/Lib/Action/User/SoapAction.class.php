<?php
/**
 * SoapAction.class.php (POS机处理模块)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if(!defined("QQCMS")) exit("Access Denied");
class SoapAction extends BaseAction
{
    public function __construct() 
    {
        header('Content-Type:text/html; charset=utf-8');
    }

	public function handleOrder()
    {
    	if (is_array($_POST) && !empty($_POST))
    	{
    		$_POST = implode('', $_POST);
    	}

    	$pos = strpos($_POST,'>');
		$sub = substr($_POST, $pos+1);
		$XMLstr= preg_replace ('/\s/','', $sub);
    	$xmlstr = $this->parseXML($XMLstr);
		$xmlstr = $this->xmlToArr($xmlstr);
		$posinfo = $xmlstr['VSPPEXReq'];

    	//获取通联支付信息
    	/*$payment = M('Payment')->field('pay_config')->where('pay_code=\'Allinpay\'')->find();
    	$pay_config = unserialize($payment['pay_config']);*/
    	$md5Key = '39D0C748369D4F579B00334F5A4FCF5E'; //约定测试MD5 key
    	$oldmac = strtoupper($posinfo['mac']);

    	// 计算加密MAC,验证MAC
    	$mac = $this->macBde($posinfo,$md5Key);

    	$posinfo['rspcode'] = '0000';
    	$posinfo['rspmsg'] = '成功';

    	if ($oldmac != $mac){
    		$posinfo['rspcode'] = '6666';
    		$posinfo['rspmsg'] = '校验不通过';
    	}

        if ($posinfo['entinst'] != '020440148160708' && $posinfo['rspcode'] == '0000')
        {
            $posinfo['rspcode'] = '9996';
            $posinfo['rspmsg'] = '商户号不正确';
        }

		$orderNo = $posinfo['bizseq']; //订单号
    	$OrderInfo = M('Order')->where('sn='.$orderNo)->find();
    	if (empty($OrderInfo)) //如果为空则查询通联订单号
    	$OrderInfo = M('Order')->where('paymentOrderId='.$orderNo)->find();

    	if (empty($OrderInfo))
    	{
    		$posinfo['rspcode'] = '9990';
    		$posinfo['rspmsg'] = '非法订单号';
    	}
    	else
    	{
    		/*if ($OrderInfo['pay_status']!=2 || $OrderInfo['end_time'] < time())
	    	{
	    		$posinfo['rspcode'] = '1111';
	    		$posinfo['rspmsg'] = '订单未支付';
	    	}*/
	    	$posinfo['amount'] = $OrderInfo['allinipay_amount']*100;
    	}

    	// 计算加密MAC
    	$mac = $this->macBde($posinfo,$md5Key);
    	$posinfo['mac'] = $mac;
    	$result = json_encode($posinfo);
    	echo $result;
    }

	public function resultPay()
    {
    	if (is_array($_POST) && !empty($_POST))
    	{
    		$_POST = implode('', $_POST);
    	}

    	$pos = strpos($_POST,'>');
		$sub = substr($_POST, $pos+1);
		$XMLstr= preg_replace('/\s/','', $sub);
    	$xmlstr = $this->parseXML($XMLstr);
		$xmlstr = $this->xmlToArr($xmlstr);
		$posinfo = $xmlstr['VSPPEXReq'];
        $md5Key = '39D0C748369D4F579B00334F5A4FCF5E'; //约定测试MD5 key
        $oldmac = strtoupper($posinfo['mac']);
        // 计算加密MAC,验证MAC
        $mac = $this->macBde($posinfo,$md5Key,true);
        $posinfo['rspcode'] = '0000';
        $posinfo['rspmsg'] = '成功';
        $OrderInfo = array();
        if ($oldmac != $mac){
            $posinfo['rspcode'] = '6666';
            $posinfo['rspmsg'] = '校验不通过';
        }
        else
        {
            $orderNo = $posinfo['bizseq']; //订单号
            $OrderInfo = M('Order')->where('sn='.$orderNo)->find();
            if (empty($OrderInfo)) //如果为空则查询通联订单号
            $OrderInfo = M('Order')->where('paymentOrderId='.$orderNo)->find(); 
        }

        if ($posinfo['entinst'] != '020440148160708' && $posinfo['rspcode'] == '0000')
        {
            $posinfo['rspcode'] = '9996';
            $posinfo['rspmsg'] = '商户号不正确';
        }
        
        switch ($posinfo['trxcod']) 
        {
            case 'A0000002':
                if (empty($OrderInfo))
                {
                    if ($posinfo['rspcode'] == '0000')
                    {
                        $posinfo['rspcode'] = '9990';
                        $posinfo['rspmsg'] = '非法订单号';
                    }
                }
                elseif ($posinfo['rspcode'] == '0000')
                {
                    
                    $orderAmount = $OrderInfo['allinipay_amount']*100;
                    if ($posinfo['amount'] != $orderAmount)
                    {
                        $posinfo['amount'] = $orderAmount;
                        $posinfo['rspcode'] = '9997';
                        $posinfo['rspmsg'] = '金额不一致';
                    }
                    else
                    {
                        if ($OrderInfo['status'] == 0 || $OrderInfo['status'] == 0)
                        {
                            //如果支付状态是未支付时就执行
                            $this->put_consume($posinfo['amount'],1,$OrderInfo['userid'],3);//写入记录表

                            $con['id'] = $OrderInfo['id'];
                            $con['status'] = 1;//订单状态变为确认
                            $con['pay_id'] = 13;//订单支付类型为线下支付
                            $con['pay_name'] = '线下支付';//订单支付类型为支付宝支付
                            $con['pay_status'] = 2;//支付状态变为已支付
                            $con['cod_amount'] = 0.00;//货到付款金额清零
                            $con['paymentOrderId'] = $orderNo;//订单支付网关交易流水号
                            $con['pay_time'] = strtotime($posinfo['timestamp']);//支付时间
                            $r = M('Order')->save($con);
                            //执行电子现金返还
                            $order_data=M("order_data")->field("number,ratio,product_id")->where("order_id=".$OrderInfo['id'])->select();
                            $user_radio=0;
                            foreach ($order_data as $key => $value) {
                                $user_radio += intval($value['number'])*floatval($value['ratio']);
                                if ($OrderInfo['userid'] != 1)
                                {
                                    M("Product_oversea")->where("id=".$value['product_id'])->setDec('stock',$value['number']);//扣库存
                                }
                            }
                            
                            $user = M("user")->field("cash_use")->where("id=".$OrderInfo['userid'])->find();
                            $user_data['id']=$OrderInfo['userid'];
                            $user_data['cash_use']=floatval($user['cash_use'])+$user_radio;
                            $res_cash=M("user")->save($user_data);
                            if($res_cash){
                                $this->put_consume($user_radio,5,$OrderInfo['userid'],1);//写入记录表
                            }
                            //电子现金end
                        }
                        elseif ($OrderInfo['status'] == 5)
                        {
                            $posinfo['rspcode'] = '9996';
                            $posinfo['rspmsg'] = '交易已撤销';
                        }
                        else{
                            $posinfo['rspcode'] = '9999';
                            $posinfo['rspmsg'] = '已缴费';
                        }
                    }
                }
                break;
            case 'A0000003':
                if ($posinfo['rspcode'] == '0000')
                {
                    $posinfo['rspmsg'] = '交易冲正';
                }

                if (!empty($OrderInfo) && $posinfo['rspcode'] == '0000')
                {
                    //如果支付状态是未支付时就执行
                    $this->put_consume($posinfo['amount'],1,$OrderInfo['userid'],3);//写入记录表

                    $con['id'] = $OrderInfo['id'];
                    $con['status'] = 0;//订单状态变为未确认
                    $con['pay_id'] = 12;//订单支付类型改为通联支付
                    $con['pay_name'] = '线下支付';//订单支付类型为支付宝支付
                    $con['pay_status'] = 0;//支付状态变为未支付
                    $con['paymentOrderId'] = $orderNo;//订单支付网关交易流水号
                    $r = M('Order')->save($con);

                    ##如果订单状态已支付，执行电子现金撤回##
                    if ($OrderInfo['status'] == 1 || $OrderInfo['pay_status'] == 2)
                    {
                        //执行电子现金撤回
                        $order_data=M("order_data")->field("number,ratio,product_id")->where("order_id=".$OrderInfo['id'])->select();
                        $user_radio=0;
                        foreach ($order_data as $key => $value) 
                        {
                            $user_radio += intval($value['number'])*floatval($value['ratio']);
                        }
                        
                        $user = M("user")->field("cash_use")->where("id=".$OrderInfo['userid'])->find();
                        $user_data['id'] = $OrderInfo['userid'];
                        $user_data['cash_use'] = floatval($user['cash_use'])-$user_radio;
                        $res_cash = M("user")->save($user_data);
                        if($res_cash)
                        {
                            $this->put_consume($user_radio,5,$OrderInfo['userid'],1);//写入记录表
                        }
                        //电子现金end
                    }
                }
                break;
            case 'A0000004':
                if ($posinfo['rspcode'] == '0000')
                {
                    $posinfo['rspmsg'] = '交易撤销';
                }

                if (!empty($OrderInfo) && $posinfo['rspcode'] == '0000')
                {
                    //如果支付状态是未支付时就执行
                    $this->put_consume($posinfo['amount'],1,$OrderInfo['userid'],3);//写入记录表

                    $con['id'] = $OrderInfo['id'];
                    $con['status'] = 5;//订单状态变为取消
                    $con['pay_id'] = 12;//订单支付类型改为通联支付
                    $con['pay_name'] = '线下支付';//订单支付类型为支付宝支付
                    $con['pay_status'] = 0;//支付状态变为未支付
                    $con['paymentOrderId'] = $orderNo;//订单支付网关交易流水号
                    $r = M('Order')->save($con);

                    ##如果订单状态已支付，执行电子现金撤回##
                    if ($OrderInfo['status'] == 1 || $OrderInfo['pay_status'] == 2)
                    {
                        //执行电子现金撤回
                        $order_data=M("order_data")->field("number,ratio,product_id")->where("order_id=".$OrderInfo['id'])->select();
                        $user_radio=0;
                        foreach ($order_data as $key => $value) 
                        {
                            $user_radio += intval($value['number'])*floatval($value['ratio']);
                        }
                        
                        $user = M("user")->field("cash_use")->where("id=".$OrderInfo['userid'])->find();
                        $user_data['id'] = $OrderInfo['userid'];
                        $user_data['cash_use'] = floatval($user['cash_use'])-$user_radio;
                        $res_cash = M("user")->save($user_data);
                        if($res_cash)
                        {
                            $this->put_consume($user_radio,5,$OrderInfo['userid'],1);//写入记录表
                        }
                        //电子现金end
                    }
                }
                break;
            default:
                break;
        }

        ##保存通知日志##
        $pos['bizseq'] = !empty($posinfo['bizseq']) ? $posinfo['bizseq'] :'null';
        $pos['payseq'] = !empty($posinfo['payseq']) ? $posinfo['payseq'] :'null';
        $pos['trxid'] = !empty($posinfo['trxid']) ? $posinfo['trxid'] :'null';
        $pos['rspcode'] = !empty($posinfo['rspcode']) ? $posinfo['rspcode'] :'null';
        $pos['rspmsg'] = !empty($posinfo['rspmsg']) ? $posinfo['rspmsg'] :'null';
        $pos['add_time'] = time();
        $pos_log = M('Pos_log')->add($pos);
    	
        // 计算加密MAC
        $mac = $this->macBde($posinfo,$md5Key);
        $posinfo['mac'] = $mac;
        $result = json_encode($posinfo);
        echo $result;
    }

    public function vnbapiwebpos($data = array(),$order = array())
    {
        #发送订单报文,必须支付已成功#
        if ($data['rspcode'] == '0000')
        {
            $vnbapiweb['MessageID'] = date('YmdHis').'0001'; //报文编号
            $vnbapiweb['SendTime'] = date('Y-m-d H:i:s'); //时间戳

            $eshop_ent_code = M('Payment')->field('customs_config')->where('pay_code=\'Allinpay\'')->find();
            $payment_code = unserialize($eshop_ent_code['customs_config']);

            $vnbapiweb['customICP'] = $payment_code['eshop_ent_code']; //电商对海关接入企业备案号

            $vnbapiweb['orderNo'] = $data['bizseq']; //商户订单号

            $vnbapiweb['payTransactionNo'] = $data['trxid']; //通联支付流水号

            $vnbapiweb['payChnlID'] = '03'; //支付渠道

            $payDatetime = strtotime($data['timestamp']);

            $vnbapiweb['payTime'] = date('Y-m-d H:i:s',$payDatetime); //支付时间

            //计算支付货款
            $payGoodsAmount = $order['allinipay_amount'] - ($order['shipping_fee'] + $order['direct_total']);

            $vnbapiweb['payGoodsAmount'] = $payGoodsAmount; //支付货款
            $vnbapiweb['payTaxAmount'] = $order['direct_total']; //支付税款
            $vnbapiweb['freight'] = $order['shipping_fee']; //支付运费
            $vnbapiweb['payerName'] = $order['identity_name']; //电商订单注册人姓名
            $vnbapiweb['payerDocumentType'] = '01'; //注册人证件类型
            $vnbapiweb['payerDocumentNumber'] = $order['identity']; //注册人证件号码

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
                $customs_log['userId'] = $order['userid'];
                $customs_log['retCode'] = $xml_parse;
                $customs_log['retInfo'] = '通讯报文接口返回信息错误！';
                M('Customs_log')->add($customs_log);
                return $xml_parse;
            }

            $signReturn = $this->parseXML($signReturn);
            $signReturn = $this->xmlToArr($signReturn);

            $MessageHeadarr = $signReturn[VnbMessage][MessageHead];
            $MessageBodyarr = $signReturn[VnbMessage][MessageBodyList][MessageBody];

            //保存日志
            $customs_log['orderId'] = $order['id'];
            $customs_log['userId'] = $order['userid'];

            if (!empty($MessageHeadarr))
            {
                $customs_log['commcode'] = $MessageHeadarr[CommCode];

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

                $customs_log['bizstatus'] = $MessageHeadarr[BizStatus];
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
                $customs_log['sendtime'] = $MessageHeadarr[SendTime];
            }

            if (!empty($MessageBodyarr))
            {
                $customs_log['retCode'] = $MessageBodyarr[retCode];
                $customs_log['retInfo'] = $MessageBodyarr[retInfo];
            }
            $Customs_log_info = M('Customs_log')->field('id,orderId')->where('orderId='.$order['id'])->find();
            if (!empty($Customs_log_info))
            {
                $customs_log['id'] = $Customs_log_info['id'];
                $customs_log['bizinfo'] .= !empty($customs_log['bizinfo']) ? '②' : '';
                $customs_log['retInfo'] .= !empty($customs_log['retInfo']) ? '②' : '';
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
            $customs_log['userId'] = $order['userid'];
            $customs_log['retCode'] = 'ER4500';
            $customs_log['retInfo'] = '订单已提交，未支付成功！';
            M('Customs_log')->add($customs_log);
            return false;
        }
    }

    ##组装报文##
    public function genXml($order) {    
        $senderID = 'SYBS';
        $signkey = '39D0C748369D4F579B00334F5A4FCF5E';
        $orgSign = '<VnbMessage>';
        $orgSign .= '<MessageHead>';
        $orgSign .= '<MessageCode>VNB3PARTY_PAYVOUCHER</MessageCode>';
        $orgSign .= '<MessageID>'.$order['MessageID'].'</MessageID>';
        $orgSign .= '<SenderID>'.$senderID.'</SenderID>';
        $orgSign .= '<SendTime>'.$order['SendTime'].'</SendTime>';
        $orgSign .= '<Sign></Sign>';
        $orgSign .= '</MessageHead>';
        $orgSign .= '<MessageBodyList>';
        $orgSign .= '<MessageBody>';
        $orgSign .= '<customICP>'.$order['customICP'].'</customICP>';
        $orgSign .= '<orderNo>'.$order['orderNo'].'</orderNo>';
        $orgSign .= '<payTransactionNo>'.$order['payTransactionNo'].'</payTransactionNo>';
        $orgSign .= '<payChnlID>'.$order['payChnlID'].'</payChnlID>';
        $orgSign .= '<payTime>'.$order['payTime'].'</payTime>';
        $orgSign .= '<payGoodsAmount>'.$order['payGoodsAmount'].'</payGoodsAmount>';
        $orgSign .= '<payTaxAmount>'.$order['payTaxAmount'].'</payTaxAmount>'; //税款
        $orgSign .= '<freight>'.$order['freight'].'</freight>';
        $orgSign .= '<payCurrency>142</payCurrency>';
        $orgSign .= '<payerName>'.$order['payerName'].'</payerName>';
        $orgSign .= '<payerDocumentType>'.$order['payerDocumentType'].'</payerDocumentType>';
        $orgSign .= '<payerDocumentNumber>'.$order['payerDocumentNumber'].'</payerDocumentNumber>';
        $orgSign .= '</MessageBody>';
        $orgSign .= '</MessageBodyList>';
        $orgSign .= '</VnbMessage>';
        
        
        $md5_Sign = strtoupper(md5($orgSign.$signkey));
        $orgSign = preg_replace("/<Sign>(.*)<\/Sign>/", '<Sign>'.$md5_Sign.'</Sign>', $orgSign);
        $orgSign = '<?xml version="1.0" encoding="UTF-8"?>'.$orgSign;
        return $orgSign;
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

    /*
	 * MAC加密 
	 *算法：MD5（交易类型码 |业务流水|交易时间|交易金额|成功标志|约定的密码）
	 */
    public function macBde($macdata,$key,$flag=false)
    {
        if ($flag === true)
        {
            $rspcode = $macdata['payresult'];
        }
        else
        {
            $rspcode = $macdata['rspcode'];
        }
    	$macStr = $macdata['trxcod'].'|'.$macdata['bizseq'].'|'.$macdata['timestamp'].'|'.$macdata['amount'].'|'.$rspcode.'|'.$key;
        if ($flag === true)
        {
            $amacStr = $macStr."\r\n";
            $_order_ = fopen("macStr.txt","a");
            fwrite($_order_, $amacStr);
            fclose($_order_);
        }
        
    	$mac = strtoupper(MD5($macStr));
        if ($flag === true)
        {
            $amac = $mac."\r\n";
            $aa = fopen("amacStr.txt","a");
            fwrite($aa, $amac);
            fclose($aa);
        }
       
    	return $mac;
    }

	 /*
	 * 解析xml
	 * 独立一个方法出来，如果以后发现这解析太简单，有出错，直接在此修改
	 * xml_str => xml_arr
	 */
    private function parseXML($xmlStr){
        $xmlStr = simplexml_load_string($xmlStr);
        return $xmlStr;
    } 

    /*
	 * 解析xml对象，##多维
	 * xml_str => xml_arr
	 */
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
}