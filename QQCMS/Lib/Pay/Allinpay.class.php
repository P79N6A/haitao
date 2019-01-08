<?php
/**
 * 
 * Alipay.php (支付宝支付模块)
 *
 * @package         QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright       Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version         QQCMS网站管理系统 v4.1.5 2012-01-09 qqcms.net $
 * @此注解信息不能修改或删除,请尊重我们的劳动成果,你的修改请注解在此注解下面。
 */
class Allinpay extends Think {
    public $config = array();
    public $reply_template;
    public $xml_data;
    public function __construct($config=array()) {
        header('Content-Type:text/html; charset=utf-8');
        $this->config = $config;
        $this->config['gateway_url_m'] = 'https://service.allinpay.com/mobilepayment/mobile/SaveMchtOrderServlet.action';
        
        $this->config['productNum'] = '1';
        $this->config['gateway_method'] = 'POST';
        $this->config['signType'] = '1'; //签名类型
        $this->config['language'] = '1'; //语言
        $this->config['inputCharset'] = 1; //默认填1；1代表UTF-8、2代表GBK、3代表GB2312；
        $this->config['tradeNature'] = 'GOODS';
        $this->config['customs_type'] = 'HG001'; // 海关类别（通联分配的海关类别代码HG001）
        $this->config['biz_type_code'] = 'I10';
        $this->config['ext1'] = 'mobile_pay';

    }

    public function setup(){
        $this->config['pickupUrl'] =  pickupReceiveUrl('payPickup'); //取货地址,客户的取货地址
        $this->config['receiveUrl'] =  pickupReceiveUrl('payReceive');//商户系统通知地址,通知商户网站支付结果的url地址
        $this->config['version'] = 'v1.0'; //版本号
        /*
        默认填0
        0和156代表人民币、840代表美元、344代表港币，跨境支付商户不建议使用0
        */
        $this->config['orderCurrency'] = '0'; //订单金额币种类型

        /*
        *
            0代表未指定支付方式，即显示该商户开通的所有支付方式
            1个人储蓄卡网银支付
            4企业网银支付
            11个人信用卡网银支付
            23外卡支付
            28认证支付
            非直连模式，设置为0；直连模式，值为非0的固定选择值
        *
        */
        $this->config['payType'] = '0'; //支付方式
        $this->config['merchantId'] = '100020091218001'; //商户号
        $this->config['key'] = '1234567890'; //key为MD5密钥，密钥是在通联支付网关商户服务网站上设置。

        $modules['pay_name']    = L('allinpay_pay_name');   
        $modules['pay_code']    = 'Allinpay';
        $modules['pay_desc']    = L('allinpay_pay_desc');
        $modules['is_cod']  = '0';
        $modules['is_online']  = '1';
        $modules['version'] = $this->config['version'];
        $modules['author']  = 'QQCMS_PC';
        $modules['website'] = 'http://weixin.ubovip.com';
        $modules['language'] = $this->config['language']; // 默认填1，固定选择值：1；1代表简体中文、2代表繁体中文、3代表英文
        $modules['inputCharset'] = $this->config['inputCharset'];
        $modules['signType'] = $this->config['signType'];
        $modules['config']  = array(
            array('name' => 'merchantId_m','type' => 'text', 'value' => $this->config['merchantId']),
            array('name' => 'merchantId_pc','type' => 'text', 'value' => $this->config['merchantId']),
            array('name' => 'pickupUrl','type' => 'text','value' => $this->config['pickupUrl']),
            array('name' => 'receiveUrl','type' => 'text','value' => $this->config['receiveUrl']),
            array('name' => 'version','type' => 'text','value' => $this->config['version']),
            array('name' => 'payType','type' => 'text','value' => $this->config['payType']),
            array('name' => 'orderCurrency','type' => 'text','value' => $this->config['orderCurrency']),
            array('name' => 'key','type' => 'text','value' => $this->config['key'])
        );

        $modules['_config']  = array(
            array('name' => 'customs_type','type' => 'text', 'value' => $this->config['customs_type']),
            array('name' => 'biz_type_code','type' => 'text','value' => $this->config['biz_type_code']),
            array('name' => 'eshop_ent_code','type' => 'text','value' => $this->config['eshop_ent_code']),
            array('name' => 'eshop_ent_name','type' => 'text','value' => $this->config['eshop_ent_name'])
        );

        return $modules;
    }

    public function get_signMsg(){
        $pay_config = unserialize($this->config['pay_config']);
        $parameter = array();
        foreach ($pay_config as $k => $v) {
            if ($k=='key') continue; //MD5密匙放在最后
            $parameter[$k] = $v;
        }

        if ($this->config[orderuserid] == 5482)
        {
            $parameter['merchantId_m'] = '100020091218001';
        }

        if (!empty($this->config['customs_config']))
        {
            $customs_config = unserialize($this->config['customs_config']);
            $replay_template = include_once(APP_PATH.'Common/replay_template.php');
            $customsXml = $replay_template['customsExt'];
            $customsXmlstr = sprintf($customsXml, $customs_config['customs_type'], $customs_config['biz_type_code'], $customs_config['eshop_ent_code'], $customs_config['eshop_ent_name'], $this->config['product_amount'], $this->config['tax_fee']);
            $customsXmlarr = $this->parseXML($customsXmlstr);
            $customsstr = '';
            foreach ($customsXmlarr as $k => $v) {
                $customsstr .= '<'.$k.'>'.$v.'</'.$k.'>';
            }
            $ext2value = strtoupper(md5($customsstr));
        }

        $paramPay = array();
        $paramPay = array(
            'inputCharset'         => $this->config['inputCharset'],
            'pickupUrl'            => $parameter['pickupUrl'],
            'receiveUrl'           => $parameter['receiveUrl'],
            'version'              => $parameter['version'],
            'language'             => $this->config['language'],
            'signType'             => $this->config['signType'],
            'merchantId'           => $parameter['merchantId_m'],
            'payerName'            => $this->config['payerName'],
            'payerEmail'           => $this->config['payerEmail'],
            'payerTelephone'       => $this->config['payerTelephone'],
            'orderNo'              => $this->config['orderNo'],
            'orderAmount'          => (int)$this->config['orderAmount'],
            'orderCurrency'        => $parameter['orderCurrency'],
            'orderDatetime'        => $this->config['orderDatetime'],
            'orderExpireDatetime'  => (int)$this->config['orderExpireDatetime'],
            'productName'          => trim($this->config['productName']),
            'productPrice'         => (int)$this->config['orderAmount'],
            'productNum'           => (int)$this->config['productNum'],
            'productDesc'          => $this->config['productDesc'],
            'payType'              => $parameter['payType'],
            'tradeNature'          => $this->config['tradeNature']
        );
        
        if ($this->config['paytype'] > 0)
        {
            $paramPay['receiveUrl'] = 'http://www.ubovip.com/index.php?g=User&m=Pay&a=rechargeReceive';
        }
        
        if (!empty($ext2value))
        {
            $paramPaycount = count($paramPay)-2;
            $paramPay = array_slice($paramPay,0,$paramPaycount);
            $paramPay['ext1'] = $this->config['ext1'];
            $paramPay['ext2'] = $ext2value;
            $paramPay['payType'] = $parameter['payType'];
            $paramPay['tradeNature'] = $this->config['tradeNature'];
        }

        $param = '';
        foreach ($paramPay AS $key => $val)
        {
            $param .= trim($key).'='.trim($val).'&';
        }
        
        $param = $param.'key='.trim($pay_config['key']);
        $signMsg = strtoupper(md5($param));
        $paramPay['signMsg'] = $signMsg;
        if (!empty($customsstr))
            $paramPay['customsExt'] = trim($customsstr);
        return $paramPay;
    }

    public function respond()
    {
        if (!empty($_POST))
        {
            foreach($_POST as $key => $data)
            {
                $_GET[$key] = $data;
            }
        }

        //file_put_contents('cccccc.txt', var_export($_GET,true));
        $seller_email = rawurldecode($_GET['seller_email']);
        //$order_sn = str_replace($_GET['subject'], '', $_GET['out_trade_no']);
        $order_sn = trim($_GET['out_trade_no']);

        /* 检查数字签名是否正确 */
        ksort($_GET);
        reset($_GET);

        $sign = '';
        foreach ($_GET AS $key=>$val)
        {
            if ($key != 'sign' && $key != 'sign_type' && $key != 'code' && $key != 'g' && $key != 'm' && $key != 'a')
            {
                $sign .= "$key=$val&";
            }
        }

        $sign = substr($sign, 0, -1) . $this->config['alipay_key'];
        //$sign = substr($sign, 0, -1) . ALIPAY_AUTH;
        if (md5($sign) != $_GET['sign'])
        {
            return false;
        }

        if ($_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS' || $_GET['trade_status'] =='WAIT_BUYER_CONFIRM_GOODS' ||  $_GET['trade_status'] =='WAIT_BUYER_PAY')
        {
            /* 改变订单状态 进行中*/
            /*order_pay_status($order_sn,'1');
            return true;*/
            $ro = order_pay_status($order_sn,'1');
            return $ro;
        }
        elseif ($_GET['trade_status'] == 'TRADE_FINISHED')
        {
            /* 改变订单状态 */

            $ro = order_pay_status($order_sn,'2');
            return $ro;
            /*order_pay_status($order_sn,'2');
            return true;*/
        }
        elseif ($_GET['trade_status'] == 'TRADE_SUCCESS')
        {
            /* 改变订单状态 即时交易成功*/      
            //判断是否为商品订单
            $ro = order_pay_status($order_sn,'2');
            return $ro;
        }
        else
        {
            return false;
        }
    }


    /*
     * 解析xml
     * 独立一个方法出来，如果以后发现这解析太简单，有出错，直接在此修改
     * xml_str => xml_arr
     */
    private function parseXML($xmlStr){
        //$xmlStr = preg_replace('/<!\[CDATA\[(.*?)\]\]>/',"$1",$xmlStr);
        //return (array)simplexml_load_string($xmlStr); //得到对象,强转array

        $xmlStr = (array)simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        return $xmlStr;
    }
}
?>