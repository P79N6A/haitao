<?php
/**
 * 
 * Order (后台订单管理)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
class OrderAction extends AdminbaseAction
{

	protected $dao;
    public function _initialize()
    {
		parent::_initialize();
		$this->dao=M('Order');	
    }

    public function index()
    {
	    $this->order_list(MODULE_NAME);
        $this->display();
    }

    public function CustomsOrder()
    {
        $where['o.pay_status'] = 2;
        $where['o.status'] = 1;
        $keyword = $_REQUEST['keyword'];
        $searchtype = $_REQUEST['searchtype'];
        $groupid = intval($_REQUEST['groupid']);
        $catid = intval($_REQUEST['catid']);
        $posid = intval($_REQUEST['posid']);
        $typeid = intval($_REQUEST['typeid']);
        if(isset($_REQUEST['status'])){
            $status = intval($_REQUEST['status']);
        }

        if(!empty($keyword) && !empty($searchtype)){
            $where[$searchtype] = array('like','%'.$keyword.'%');
        }

        if($groupid) $where['o.groupid'] = $groupid;
        if($catid) $where['o.catid'] = $catid;
        if($posid) $where['o.posid'] = $posid;
        if($typeid) $where['o.typeid'] = $typeid;
        if($status) $where['o.status'] = $status;

        if($where['o.status']==99) $where['o.status']=0;

        $field = 'c.*,o.sn,o.status,o.pay_status,o.userid,o.allinipay_amount,o.pay_time,o.add_time,o.shipping_status,o.consignee,o.order_amount,o.paymentOrderId,u.wechat_name';
        $order = 'c.id desc';
        $join = array('qq_order as o ON c.orderId = o.id','qq_user as u ON c.userId = u.id');
        $alias = 'c';
        $list = $this->customs_log_list('Customs_log',$where,$field,15,$order,$join,$alias);
        $this->assign('list',$list['list']);
        $this->assign('page',$list['page']);
        $this->display();
    }

    public function customs_log_list($modelname,$map='',$field,$listRows=15,$order='',$join,$alias) {
        $model = M($modelname);
        $id = $model->getPk ();
        $this->assign ( 'pkid', $id );

        if(APP_LANG)if($this->moduleid)$map['lang']=array('eq',LANG_ID);
        $tables = $model->getDbFields();

        foreach($_REQUEST['map'] as $key=>$res){
                if(($res==='0' || $res>0) || !empty($res))
                {                   
                    if($_REQUEST['maptype'][$key]){
                        $map[$key]=array($_REQUEST['maptype'][$key],$res);
                    }else{
                        $map[$key]=intval($res);
                    }
                    $_REQUEST[$key]=$res;
                }else{                  
                    unset($_REQUEST[$key]);
                }
        }

        $this->assign($_REQUEST);

        //取得满足条件的记录总数
        $count = $model->alias($alias)->join($join)->where($map)->count();
        if ($count >0) {
            import ( "@.ORG.Page" );
            //创建分页对象
            if (! empty ( $_REQUEST ['listRows'] )){
                $listRows = $_REQUEST ['listRows'];
            }

            $p = new Page ($count,$listRows);
            //分页查询数据
            $voList = $model->alias($alias)->field($field)->join($join)->where($map)->order($order)->limit($p->firstRow . ',' . $p->listRows)->select();
            // echo $model->getLastsql();exit;
            //分页跳转的时候保证查询条件
            foreach ( $map as $key => $val ) {
                if (! is_array ( $val )) {
                    $p->parameter .= "$key=".urlencode($val)."&";
                }
            }

            $map[C('VAR_PAGE')]='{$page}';
            $page->urlrule = U($modelname.'/CustomsOrder', $map);

            //分页显示
            $page = $p->show ();
            //模板赋值显示
            $comData['list'] = $voList;
            $comData['page'] = $page;
            return $comData;
        }
        return false;
    }

    public function vnbapiwebpos()
    {
        $orderNo = $_POST['sn'];
        // $_REQUEST = json_decode($_REQUEST,true);
        $order = M('order')->where('sn=\''.$orderNo.'\'')->find();
        $userInfo = M('User')->field('realname,identity')->find($order['userid']);
        $paystr = strlen($order['paymentOrderId']);
        if ($paystr == 8) 
            $order['payChnlID'] = '03';
        else
            $order['payChnlID'] = '01';

        #发送订单报文,必须支付已成功#
        $vnbapiweb['MessageID'] = date('YmdHis').'0001'; //报文编号
        $vnbapiweb['SendTime'] = date('Y-m-d H:i:s'); //时间戳

        $eshop_ent_code = M('Payment')->field('customs_config')->where('pay_code=\'Allinpay\'')->find();
        $payment_code = unserialize($eshop_ent_code['customs_config']);

        $vnbapiweb['customICP'] = $payment_code['customICP']; //电商对海关接入企业备案号

        $vnbapiweb['orderNo'] = $orderNo; //商户订单号

        $vnbapiweb['payTransactionNo'] = $order['paymentOrderId']; //通联支付流水号

        $vnbapiweb['payChnlID'] = $order['payChnlID']; //支付渠道

        $payDatetime = $order['pay_time'];

        $vnbapiweb['payTime'] = date('Y-m-d H:i:s',$payDatetime); //支付时间

        //计算支付货款
        $payGoodsAmount = $order['allinipay_amount']-($order['shipping_fee']+$order['direct_total']);

        $vnbapiweb['payGoodsAmount'] = $payGoodsAmount; //支付货款
        $vnbapiweb['payTaxAmount'] = $order['direct_total']; //支付税款
        $vnbapiweb['freight'] = $order['shipping_fee']; //支付运费
        
        if ($userInfo['realname'])
            $vnbapiweb['payerName'] = trim(str_replace('　', '  ', $userInfo['realname'])); //电商订单注册人姓名
        else
            $vnbapiweb['payerName'] = trim(str_replace('　', '  ', $order['identity_name'])); //电商订单注册人姓名 

        $vnbapiweb['payerDocumentType'] = '01'; //注册人证件类型

        if ($userInfo['identity'])
            $vnbapiweb['payerDocumentNumber'] = trim(strtoupper($order['identity'])); //注册人证件号码
        else
            $vnbapiweb['payerDocumentNumber'] = trim(strtoupper($order['identity'])); //注册人证件号码
        
        // F('vnbapiweb',$vnbapiweb);
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
        
        $MessageHeadarr = $signReturn[VnbMessage][MessageHead];
        $MessageBodyarr = $signReturn[VnbMessage][MessageBodyList][MessageBody];

        //保存日志
        $customs_log['orderId'] = $order['id'];
        $customs_log['userId'] = $order['userid'];
        $customs_log['orderSn'] = $order['sn'];

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
                    $customs_log['bizinfo'] .= '该订单已经上报';
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

        $Customs_log_info = M('Customs_log')->where('orderId='.$order['id'])->find();
        if (!empty($Customs_log_info))
        {
            $customs_log['id'] = $Customs_log_info['id'];
            $customs_log['bizinfo'] .= '②';
            $customs_log['retInfo'] .= '②';
            M('Customs_log')->save($customs_log);
        }
        else
        {
            M('Customs_log')->add($customs_log);
        }
        if ($customs_log['retInfo'] == '②' || empty($customs_log['retInfo']))
            $msg = $customs_log['bizinfo'];
        else
            $msg = $customs_log['retInfo'];

        if ($customs_log['commcode'] == '000000' && $customs_log['bizstatus'] == 'BZ0001')
        $this->ajaxReturn($customs_log['commcode'].'||'.$customs_log['bizstatus'],$msg,1);
        else
        $this->ajaxReturn($customs_log['commcode'].'||'.$customs_log['bizstatus'],$msg,0);  
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

     /*
     * 解析xml
     * 独立一个方法出来，如果以后发现这解析太简单，有出错，直接在此修改
     * xml_str => xml_arr
     */
    private function parseXML($xmlStr){
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

    protected function cURLGet($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//这个是重点
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result =  curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    ##导出BBC订单信息##
    public function BBCOrder()
    {
	    $model= M("Order");
        $star_time = $_POST['star'].' 00:00:00';
        $end_time = $_POST['end'].' 23:59:59';
        $star_time = strtotime($star_time);
		$end_time = strtotime($end_time);
		$where['add_time'] = array(array('gt',$star_time),array('lt',$end_time));
        $OrdersData= $model->where($where)->select();  //查询数据得到$OrdersData二维数组  
        $join = 'qq_product_oversea as pr ON od.product_id = pr.id';
        $field = 'od.order_id,od.product_id,od.product_name,od.product_price,od.number,pr.post_rate,pr.registration_num,pr.unit,pr.gross_weight,pr.suttle';
        foreach ($OrdersData as $key => $val) {
            $_where['od.order_id'] = $val['id'];
            $_where['od.userid'] = $val['userid'];
            $order_data = M('Order_data')->alias('od')->field($field)->join($join)->where($_where)->find();
            $OrdersData[$key]['product_name'] = $order_data['product_name'];
            $OrdersData[$key]['product_price'] = $order_data['product_price'];
            $OrdersData[$key]['number'] = $order_data['number'];
            $OrdersData[$key]['registration_num'] = $order_data['registration_num'];
            $OrdersData[$key]['unit'] = $order_data['unit'];
            $OrdersData[$key]['gross_weight'] = $order_data['gross_weight']*$order_data['number'];
            $OrdersData[$key]['suttle'] = $order_data['suttle']*$order_data['number'];
        }
        vendor("PHPExcel.PHPExcel");
	    $objPHPExcel = new PHPExcel();
        // Create new PHPExcel object  
        // Set properties  
        $objPHPExcel->getProperties()->setCreator("ctos")  
            ->setLastModifiedBy("ctos")  
            ->setTitle("Office 2007 XLSX Test Document")  
            ->setSubject("Office 2007 XLSX Test Document")  
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")  
            ->setKeywords("office 2007 openxml php")  
            ->setCategory("Test result file");  
  
        //set width  
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(18);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(18);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(50);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(25); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(13);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(35);
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(10); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('U')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('V')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('W')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('X')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Y')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Z')->setWidth(10);
  
        //设置行高度  
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(22);  
  
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);  
  
        //set font size bold  
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);  
        $objPHPExcel->getActiveSheet()->getStyle('A2:J2')->getFont()->setBold(true);  
  
        $objPHPExcel->getActiveSheet()->getStyle('A2:J2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('A2:J2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);  
  
        //设置水平居中  
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);  
        $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('H')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('I')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('M')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

       	//设置单元格颜色
       	$objPHPExcel->getActiveSheet()->getStyle( 'A1:B1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
       	$objPHPExcel->getActiveSheet()->getStyle( 'A1:B1')->getFill()->getStartColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
        $objPHPExcel->getActiveSheet()->getStyle( 'C1:Z1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);  
		$objPHPExcel->getActiveSheet()->getStyle( 'C1:Z1')->getFill()->getStartColor()->setARGB('FF808080');
		$objPHPExcel->getActiveSheet()->getStyle( 'A2:Z2')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);  
		$objPHPExcel->getActiveSheet()->getStyle( 'A2:Z2')->getFill()->getStartColor()->setARGB('FF808080');

		//加粗单元格边框
		$objPHPExcel->getActiveSheet()->getStyle('A1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('B1:E1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('F1:G1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('H1:L1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('M1:U1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

		$objPHPExcel->getActiveSheet()->getStyle('V1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('W1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('X1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('Y1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('Z1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

				$objPHPExcel->getActiveSheet()->getStyle('K2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('L2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('M2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('N2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('O2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('P2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('Q2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('R2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('S2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('T2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('U2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('V2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('W2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('X2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('Y2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('Z2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

		$objPHPExcel->getActiveSheet()->getStyle('A1:Z1')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE); // 白色
		$objPHPExcel->getActiveSheet()->getStyle('A2:Z2')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE); // 白色
  
        //合并cell  
        $objPHPExcel->getActiveSheet()->mergeCells('F1:G1'); 
        $objPHPExcel->getActiveSheet()->mergeCells('H1:L1');
        $objPHPExcel->getActiveSheet()->mergeCells('M1:U1');
        // $objPHPExcel->getActiveSheet()->mergeCells('B1:E1');
  
        // set table header content  
        $objPHPExcel->setActiveSheetIndex(0)
        	->setCellValue('A1', '电商企业')
            ->setCellValue('B1', 'UBO')
            ->setCellValue('F1', '订单人信息')  
            ->setCellValue('H1', '收货人信息')  
            ->setCellValue('M1', '商品信息')  
            ->setCellValue('V2', '订单时间')  
            ->setCellValue('W2', '导入时间')  
            ->setCellValue('X2', '订单申报')  
            ->setCellValue('Y2', '运单申报')  
            ->setCellValue('Z2', '个人申报')  
            ->setCellValue('A2', '序号')  
            ->setCellValue('B2', '订单号')  
            ->setCellValue('C2', '运单号')
            ->setCellValue('D2', '运费')
            ->setCellValue('E2', '关税')
            ->setCellValue('F2', '姓名')
            ->setCellValue('G2', '证件号')
            ->setCellValue('H2', '姓名')
            ->setCellValue('I2', '地址')
            ->setCellValue('J2', '收件地代码')
            ->setCellValue('K2', '电话')
            ->setCellValue('L2', '证件号')
            ->setCellValue('M2', '商品海关备案号')
            ->setCellValue('N2', '名称')
            ->setCellValue('O2', '数量')
            ->setCellValue('P2', '总价')
            ->setCellValue('Q2', '单价')
            ->setCellValue('R2', '单位')
            ->setCellValue('S2', '毛重')
            ->setCellValue('T2', '净重')
            ->setCellValue('U2', '备注');
  
        // Miscellaneous glyphs, UTF-8 
        $_area = M('Area')->field('id,name,address_code')->select();
        $area = array();
        foreach ($_area as $key => $value) {
        	$area[$value['id']] = $value;
        }
        for($i=0;$i<count($OrdersData);$i++){ 
        	$sn = $OrdersData[$i]['sn'];
        	$shipping_address = $area[$OrdersData[$i]['province']]['name'] . ' - ' . $area[$OrdersData[$i]['city']]['name'] . ' - ' . $area[$OrdersData[$i]['area']]['name'] . ' ' . $OrdersData[$i]['address'];
        	$objPHPExcel->getActiveSheet(0)->getStyle('B')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        	$objPHPExcel->getActiveSheet(0)->getStyle('C')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        	$objPHPExcel->getActiveSheet(0)->getStyle('G')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        	$objPHPExcel->getActiveSheet(0)->getStyle('L')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            $objPHPExcel->getActiveSheet(0)->getStyle('P')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $objPHPExcel->getActiveSheet(0)->getStyle('Q')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+3), $OrdersData[$i]['id']);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('B'.($i+3), ''.$sn.' ');  
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3),''.$OrdersData[$i]['shipping_sn'].' ');  
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3), $OrdersData[$i]['shipping_fee']); 
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3), $OrdersData[$i]['direct_total']);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3), $OrdersData[$i]['identity_name']);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3), ''.$OrdersData[$i]['identity'].' ');  
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3), $OrdersData[$i]['identity_name']);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+3), $shipping_address);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+3), $area[$OrdersData[$i]['area']]['address_code']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+3), $OrdersData[$i]['mobile']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('L'.($i+3), ''.$OrdersData[$i]['identity'].' ');
            $objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+3), $OrdersData[$i]['registration_num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+3), $OrdersData[$i]['product_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+3), $OrdersData[$i]['number']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+3), $OrdersData[$i]['allinipay_amount']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+3), $OrdersData[$i]['product_price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+3), $OrdersData[$i]['unit']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('S'.($i+3), $OrdersData[$i]['gross_weight']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('T'.($i+3), $OrdersData[$i]['suttle']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('U'.($i+3), '');
            $objPHPExcel->getActiveSheet(0)->setCellValue('V'.($i+3), toDate($OrdersData[$i]['add_time']));
            $objPHPExcel->getActiveSheet(0)->setCellValue('W'.($i+3), toDate(time()));
            $objPHPExcel->getActiveSheet()->getStyle('A'.($i+3).':J'.($i+3))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);  
            $objPHPExcel->getActiveSheet()->getStyle('A'.($i+3).':J'.($i+3))->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);  
            $objPHPExcel->getActiveSheet()->getRowDimension($i+3)->setRowHeight(16);  
        }  
  
  
        //  sheet命名  
        $objPHPExcel->getActiveSheet()->setTitle('跨境电商 BBC订单申报表格');  
  
  
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet  
        $objPHPExcel->setActiveSheetIndex(0);  
  
  
        // excel头参数  
        header('Content-Type: application/vnd.ms-excel');  
        header('Content-Disposition: attachment;filename="跨境电商 BBC订单申报表格'.date('His').'('.$_POST['star'].' - '.$_POST['end'].').xls"');  //日期为文件名后缀  
        header('Cache-Control: max-age=0');  
  
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  //excel5为xls格式，excel2007为xlsx格式  
        $objWriter->save('php://output'); 
    }

    ##导出BC订单信息##
    public function BCOrder()
    {
	    $model= M("Order");  
	    $star_time = $_POST['star'].' 00:00:00';
        $end_time = $_POST['end'].' 23:59:59';
        $star_time = strtotime($star_time);
        $end_time = strtotime($end_time);
		$where['add_time'] = array(array('gt',$star_time),array('lt',$end_time));
        $OrdersData= $model->where($where)->select();  //查询数据得到$OrdersData二维数组  
        $join = 'qq_product_oversea as pr ON od.product_id = pr.id';
        $field = 'od.order_id,od.product_id,od.product_name,od.product_price,od.number,pr.post_rate,pr.registration_num,pr.unit,pr.gross_weight,pr.suttle';
        foreach ($OrdersData as $key => $val) {
            $_where['od.order_id'] = $val['id'];
            $_where['od.userid'] = $val['userid'];
            $order_data = M('Order_data')->alias('od')->field($field)->join($join)->where($_where)->find();
            $OrdersData[$key]['product_name'] = $order_data['product_name'];
            $OrdersData[$key]['product_price'] = $order_data['product_price'];
            $OrdersData[$key]['number'] = $order_data['number'];
            $OrdersData[$key]['registration_num'] = $order_data['registration_num'];
            $OrdersData[$key]['unit'] = $order_data['unit'];
            $OrdersData[$key]['gross_weight'] = $order_data['gross_weight']*$order_data['number'];
            $OrdersData[$key]['suttle'] = $order_data['suttle']*$order_data['number'];
        }

        vendor("PHPExcel.PHPExcel");
	    $objPHPExcel = new PHPExcel();
        // Create new PHPExcel object  
        // Set properties  
        $objPHPExcel->getProperties()->setCreator("ctos")  
            ->setLastModifiedBy("ctos")  
            ->setTitle("Office 2007 XLSX Test Document")  
            ->setSubject("Office 2007 XLSX Test Document")  
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")  
            ->setKeywords("office 2007 openxml php")  
            ->setCategory("Test result file");  
  
        //set width  
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(18);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(18);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(18);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(10);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(25);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(50); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(13);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(13);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(35); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('U')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('V')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('W')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('X')->setWidth(20);
  
        //设置行高度  
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(16);  
  
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(16);  
  
        //set font size bold  
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);  
        $objPHPExcel->getActiveSheet()->getStyle('A2:X2')->getFont()->setBold(true);  
  
        $objPHPExcel->getActiveSheet()->getStyle('A2:X2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('A2:X2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);  
  
        //设置水平居中  
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);  
        $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('H')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('I')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('N')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

       	//设置单元格颜色
       	$objPHPExcel->getActiveSheet()->getStyle( 'A1:B1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
       	$objPHPExcel->getActiveSheet()->getStyle( 'A1:B1')->getFill()->getStartColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
        $objPHPExcel->getActiveSheet()->getStyle( 'C1:X1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);  
		$objPHPExcel->getActiveSheet()->getStyle( 'C1:X1')->getFill()->getStartColor()->setARGB('FF808080');
		$objPHPExcel->getActiveSheet()->getStyle( 'A2:X2')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);  
		$objPHPExcel->getActiveSheet()->getStyle( 'A2:X2')->getFill()->getStartColor()->setARGB('FF808080');

		//加粗单元格边框
		$objPHPExcel->getActiveSheet()->getStyle('A1:B1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('C1:F1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('G1:H1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('I1:M1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('N1:V1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

		$objPHPExcel->getActiveSheet()->getStyle('W1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
		$objPHPExcel->getActiveSheet()->getStyle('X1')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

		$objPHPExcel->getActiveSheet()->getStyle('A1:X1')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE); // 白色
		$objPHPExcel->getActiveSheet()->getStyle('A2:X2')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE); // 白色
  
        //合并cell  
        $objPHPExcel->getActiveSheet()->mergeCells('G1:H1'); 
        $objPHPExcel->getActiveSheet()->mergeCells('I1:M1');
        $objPHPExcel->getActiveSheet()->mergeCells('N1:V1');
        // $objPHPExcel->getActiveSheet()->mergeCells('B1:E1');
  
        // set table header content  
        $objPHPExcel->setActiveSheetIndex(0)
        	->setCellValue('A1', '电商企业')
            ->setCellValue('B1', 'UBO')
            ->setCellValue('G1', '订单人信息')  
            ->setCellValue('I1', '收货人信息')  
            ->setCellValue('N1', '商品信息')
            ->setCellValue('A2', '序号')  
            ->setCellValue('B2', '订单号')  
            ->setCellValue('C2', '运单号')
            ->setCellValue('D2', '主单号')
            ->setCellValue('E2', '运费')
            ->setCellValue('F2', '关税')
            ->setCellValue('G2', '姓名')
            ->setCellValue('H2', '证件号')
            ->setCellValue('I2', '姓名')
            ->setCellValue('J2', '地址')
            ->setCellValue('K2', '收件地址代码')
            ->setCellValue('L2', '电话')
            ->setCellValue('M2', '证件号')
            ->setCellValue('N2', '商品海关备案号')
            ->setCellValue('O2', '名称')
            ->setCellValue('P2', '数量')
            ->setCellValue('Q2', '单价')
            ->setCellValue('R2', '规格')
            ->setCellValue('S2', '产销国')
            ->setCellValue('T2', '单位')
            ->setCellValue('U2', '毛重')
            ->setCellValue('V2', '净重')
            ->setCellValue('W2', '订单备注')  
            ->setCellValue('X2', '订单时间');
  
        // Miscellaneous glyphs, UTF-8 
        $_area = M('Area')->field('id,name,address_code')->select();
        $area = array();
        foreach ($_area as $key => $value) {
        	$area[$value['id']] = $value;
        }
        for($i=0;$i<count($OrdersData);$i++){ 
        	$sn = $OrdersData[$i]['sn'];
        	$shipping_address = $area[$OrdersData[$i]['province']]['name'] . ' - ' . $area[$OrdersData[$i]['city']]['name'] . ' - ' . $area[$OrdersData[$i]['area']]['name'] . ' ' . $OrdersData[$i]['address'];
        	$objPHPExcel->getActiveSheet(0)->getStyle('B')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        	$objPHPExcel->getActiveSheet(0)->getStyle('C')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        	$objPHPExcel->getActiveSheet(0)->getStyle('D')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        	$objPHPExcel->getActiveSheet(0)->getStyle('H')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        	$objPHPExcel->getActiveSheet(0)->getStyle('M')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            $objPHPExcel->getActiveSheet(0)->getStyle('Q')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+3), $OrdersData[$i]['id']);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('B'.($i+3), ''.$sn.' ');  
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3), ''.$OrdersData[$i]['shipping_sn'].' ');  
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3), ''.$OrdersData[$i]['shipping_sn'].' '); 
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3), $OrdersData[$i]['shipping_fee']);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3), $OrdersData[$i]['direct_total']);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('G'.($i+3), $OrdersData[$i]['identity_name']);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('H'.($i+3), ''.$OrdersData[$i]['identity'].' ');  
            $objPHPExcel->getActiveSheet(0)->setCellValue('I'.($i+3), $OrdersData[$i]['identity_name']);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('J'.($i+3), $shipping_address);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K'.($i+3), $area[$OrdersData[$i]['area']]['address_code']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('L'.($i+3), $OrdersData[$i]['mobile']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('M'.($i+3), ''.$OrdersData[$i]['identity'].' ');
            $objPHPExcel->getActiveSheet(0)->setCellValue('N'.($i+3), $OrdersData[$i]['registration_num']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('O'.($i+3), $OrdersData[$i]['product_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('P'.($i+3), $OrdersData[$i]['number']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('Q'.($i+3), $OrdersData[$i]['product_price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('R'.($i+3), $OrdersData[$i]['unit']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('S'.($i+3), '中国');
            $objPHPExcel->getActiveSheet(0)->setCellValue('T'.($i+3), $OrdersData[$i]['unit']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('U'.($i+3), $OrdersData[$i]['gross_weight']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('V'.($i+3), $OrdersData[$i]['suttle']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('W'.($i+3), '');
            $objPHPExcel->getActiveSheet(0)->setCellValue('X'.($i+3), toDate($OrdersData[$i]['add_time']));
            $objPHPExcel->getActiveSheet()->getStyle('A'.($i+3).':J'.($i+3))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);  
            $objPHPExcel->getActiveSheet()->getStyle('A'.($i+3).':J'.($i+3))->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);  
            $objPHPExcel->getActiveSheet()->getRowDimension($i+3)->setRowHeight(16);  
        }  
  
  
        //  sheet命名  
        $objPHPExcel->getActiveSheet()->setTitle('跨境电商 BC订单申报表格');  
  
  
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet  
        $objPHPExcel->setActiveSheetIndex(0);  
  
  
        // excel头参数  
        header('Content-Type: application/vnd.ms-excel');  
        header('Content-Disposition: attachment;filename="跨境电商 BC订单申报表格'.date('His').'('.$_POST['star'].' - '.$_POST['end'].').xls"');  //日期为文件名后缀  
        header('Cache-Control: max-age=0');  
  
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  //excel5为xls格式，excel2007为xlsx格式  
        $objWriter->save('php://output'); 
    }

 	public function _after_index(){
 		/*$order_list=M("order")->field("id,sn,status,pay_status,shipping_status")->where("pay_status=2 and status!=2")->select();
 		$this->get_shipping_msg($order_list);*/
 		exit();
 	}
    public function order_list($modelname, $map = '', $sortBy = '', $asc = false ,$listRows = 15){
    		$model = M($modelname);
    		$id=$model->getPk ();
    		$this->assign ( 'pkid', $id );
    		
    		if (isset ( $_REQUEST ['order'] )) {
    			$order = $_REQUEST ['order'];
    		} else {
    			$order = ! empty ( $sortBy ) ? $sortBy : $id;
    		}
    		if (isset ( $_REQUEST ['sort'])) {
    			$_REQUEST ['sort']=='asc' ? $sort = 'asc' : $sort = 'desc';
    		} else {
    			$sort = $asc ? 'asc' : 'desc';
    		}


    		$_REQUEST ['sort'] = $sort;
    		$_REQUEST ['order'] = $order;

    		$keyword=$_REQUEST['keyword'];
    		$searchtype=$_REQUEST['searchtype'];
    		$groupid =intval($_REQUEST['groupid']);
    		$catid =intval($_REQUEST['catid']);
    		$posid =intval($_REQUEST['posid']);
    		$typeid =intval($_REQUEST['typeid']);
    		$is_private =intval($_REQUEST['is_private']);
    		if(isset($_REQUEST['status'])){
    			$status=intval($_REQUEST['status']);
    		}
    		if(APP_LANG)if($this->moduleid)$map['lang']=array('eq',LANG_ID);


    		if(!empty($keyword) && !empty($searchtype)){
    			$map[$searchtype]=array('like','%'.$keyword.'%');
    		}
    		
    		if($is_private){
    			if($is_private==99){
    			$map['is_private']=0;//私人酒窖的不取
    			}else $map['is_private']=$is_private;

    		}
    		if($groupid)$map['groupid']=$groupid;
    		if($catid)$map['catid']=$catid;
    		if($posid)$map['posid']=$posid;
    		if($typeid) $map['typeid']=$typeid;
    		if($status) $map['status']=$status;
    		if($map['status']==99)$map['status']=0;
    		$tables = $model->getDbFields();

    		foreach($_REQUEST['map'] as $key=>$res){
    				if(  ($res==='0' || $res>0) || !empty($res) ){					 
    					if($_REQUEST['maptype'][$key]){
    						$map[$key]=array($_REQUEST['maptype'][$key],$res);
    					}else{
    						$map[$key]=intval($res);
    					}
    					$_REQUEST[$key]=$res;
    				}else{					
    					unset($_REQUEST[$key]);
    				}
    		}

    		$this->assign($_REQUEST);

    		//取得满足条件的记录总数
    		$count = $model->where ( $map )->count ( $id );//echo $model->getLastsql();
    		if ($count > 0) {
    			import ( "@.ORG.Page" );
    			//创建分页对象
    			if (! empty ( $_REQUEST ['listRows'] )) {
    				$listRows = $_REQUEST ['listRows'];
    			}
    			$p = new Page ( $count, $listRows );
    			//分页查询数据
    			foreach ($map as $key => $value) {
    					$newkey='qq_order.'.$key;
    					$map[$newkey]= $value;
    					unset($map[$key]);
    			}
    			$voList = $model->field(" `qq_user`.realname,`qq_user`.wechat_name,`qq_role`.name,`qq_order`.*")->join(" `qq_user` on `qq_user`.id=`qq_order`.userid ")->join(' `qq_role` on `qq_role`.id=`qq_user`.groupid ')->where($map)->order( "`qq_order`.`" . $order . "` " . $sort)->limit($p->firstRow . ',' . $p->listRows)->select();
    			/*再获取商家信息 by dension*/
    			foreach ($voList as $key => $value) {

    			if($value['status']==3){
    				//已取消
    			}elseif($value['status']==2){
    				//已完成
    				$voList[$key]['put_shipping_time']=$value['pay_time'];
    			}elseif ($value['shipping_status']==1) {
    				//已发货
    				$voList[$key]['put_shipping_time']=$value['pay_time'];
    			}elseif($value['pay_status']==2){
    				//已支付
    				$voList[$key]['put_shipping_time']=$value['pay_time'];
    			}elseif($value['cash_pay_status']==1 && $value['pay_code']=="Cash_pay"&&$value['order_amount']==$value['cash_coupon']){
    				//电子现金支付
    				$voList[$key]['put_shipping_time']=$value['pay_time'];
    			}elseif($value['cash_pay_status']==1&&$value['pay_status']!=2 && $value['pay_code']=="Cash_pay"&&$value['order_amount']>$value['cash_coupon']){
    				//电子现金支付,但微信未支付
    			}else{
    				//未支付
    			}



    				$user=M('user')->field('realname')->where("id=".$value['shop_id'])->find();
    				$voList[$key]['shop_name']=$user['realname'];
    			}
    			/**/
    			//分页跳转的时候保证查询条件
    			foreach ( $map as $key => $val ) {
    				if (! is_array ( $val )) {
    					$p->parameter .= "$key=" . urlencode ( $val ) . "&";
    				}
    			}

    			$map[C('VAR_PAGE')]='{$page}';
    			$page->urlrule = U($modelname.'/index', $map);

    			//分页显示
    			$page = $p->show ();
    			//列表排序显示
    			$sortImg = $sort; //排序图标
    			$sortAlt = $sort == 'desc' ? '升序排列' : '倒序排列'; //排序提示
    			$sort = $sort == 'desc' ? 1 : 0; //排序方式
    			//模板赋值显示
    			//var_dump($voList);exit();
    			$this->assign ( 'list', $voList );
    			$this->assign ( 'page', $page );
    		}
    		return;
    }
    public function tuangou(){
    		import ("@.ORG.Page");
    		$modle=M('group_buy');
    		$listRows = 15;
    		$where['status']=$_REQUEST['status'];
    		$count=$modle->where($where)->count();
    		$page = new Page ( $count, $listRows);
    		$pages = $page->show();
    		$list = $modle->where($where)->order('id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
    		$this->assign('pages',$pages);
    		$this->assign('list',$list);
            $this->display();
    }
    public function tuangou_chuli(){
    	$data['id']=$_REQUEST['id']?$_REQUEST['id']:0;
    	$data['status']=$_REQUEST['status'];
    	$r=M("group_buy")->save($data);
    	if($r)$this->success('已处理');
    	else $this->error('处理失败');
    	exit();

    }
    public function tuangou_delete(){
    	$data['id']=$_REQUEST['id']?$_REQUEST['id']:0;
    	$r=M("group_buy")->where($data)->delete();
    	if($r)$this->success('删除成功');
    	else $this->error('删除失败');
    	exit();
    }
 	public function show(){
		$id= intval($_REQUEST['id']);
		$order = $id ? $this->dao->find($id) : $this->dao->getBySn($sn) ;
		if(!$order && $order['userid']!=$this->_userid) $this->success (L('do_empty'));
		$order_data = M('Order_data')->where("order_id='{$order[id]}'")->select();
		$amount=0;
        
        ##商品信息的属性规格##
        $property=array();
		foreach($order_data as $key=>$r){
			$amount = $amount+$r['price'];
            if (!empty($r['goods_attr_id']))
            {
                $property=M('ProductProperty')->field('attribute_group')->find(intval($r['goods_attr_id']));
                if(!empty($property['attribute_group']))
                {
                    $attr=unserialize($property['attribute_group']);
                    foreach ($attr as $ik => $it) {
                        $join='qq_specs as s ON pe.specs_id=s.specs_id';
                        $field='pe.*,s.specsname';
                        $where['pe.extend_id']=$it;
                        $extend=M('PropertyExtend')->alias('pe')->field($field)->join($join)->where($where)->find();
                        if(!empty($extend))
                        {
                            $order_data[$key]['extend'][$ik]='['.$extend['specsname'].'/'.$extend['propertyvalue'].']';
                        }
                    }
                }
            }
		} 

		/*获取物流信息 by dension*/
        $msg=M("shipping_msg")->where("order_id=".$order["id"])->find();
		if($order['pay_status'] && $order['pay_status']==2){
			$curl_url='http://api.i.zeny-express.com/api/a?u=19&c=ZENY-KJXM-001&d='.$order['shipping_sn'].'&kj=1';
			$curl_result = json_decode($this->cURLGet($curl_url),true);
			if($curl_result['data'][$order['shipping_sn']]&&$order['shipping_id']!=2){
				//表示已经发货
				$timestr=$curl_result['data'][$order['shipping_sn']][0]['AcceptTime'];
				$change_data["id"]=$order['id'];
				$change_data["shipping_status"]=1;
				$change_data["shipping_time"]=$timestr ? strtotime($timestr):mktime();
				M("order")->save($change_data);
                $order['shipping_status']=$change_data["shipping_status"];
                $order['shipping_time']=$change_data["shipping_time"];
                $msgs['order_id']=$order['id'];
                $msgs['message']=json_encode($curl_result['data'][$order['shipping_sn']]);
                $msgs['createtime']=$timestr ? strtotime($timestr):mktime();
                if($msg)
                M("shipping_msg")->data($msgs)->where("order_id=".$order["id"])->save();
                else
                M("shipping_msg")->add($msgs);
 			}else{
                $msgs['order_id']=$order['id'];
                $shippingmsg[$order['shipping_sn']]['AcceptTime']=date('Y-m-d H:i:s',time());
                $shippingmsg[$order['shipping_sn']]['Remark']='暂无物流信息';
                $shippingmsg[$order['shipping_sn']]['states']='';
                $msgs['message']=json_encode($shippingmsg);
                $msgs['createtime']=time();
                if($msg)
                M("shipping_msg")->data($msgs)->where("order_id=".$order["id"])->save();
                else
                M("shipping_msg")->add($msgs);
            }
        }else{
            $msgs['order_id']=$order['id'];
            $shippingmsg[$order['shipping_sn']]['AcceptTime']=date('Y-m-d H:i:s',time());
            $shippingmsg[$order['shipping_sn']]['Remark']='订单未发货';
            $shippingmsg[$order['shipping_sn']]['states']='';
            $msgs['message']=json_encode($shippingmsg);
            $msgs['createtime']=time();
            if($msg)
            M("shipping_msg")->data($msgs)->where("order_id=".$order["id"])->save();
            else
            M("shipping_msg")->add($msgs);
        }
        /*$msg['message']=$msgs['message'];*/
        if($msgs){
	        $msg['createtime']=$msgs['createtime'];
	        $msg['message']=json_decode($msg['message'],true);
			$this->assign('msg',$msg['message']);
        }
        /*foreach ($msg['message'] as $key => $value) {
            $msg['message'][$key]['AcceptTime']=date('Y-m-d H:i:s',$value['AcceptTime']);
        }*/
        /*if ($order['userid']==1196)
        print_r($msg);*/

		$Payment = M('Payment')->find($order['pay_id']);
		$Shipping = M('Shipping')->find($shippingid);
		$Area = M('Area')->getField('id,name');
		$this->assign('Area',$Area);
		$this->assign('Payment',$Payment);
		$this->assign('Shipping',$Shipping);

		$this->assign('order',$order);
		$this->assign('order_data',$order_data);
		$this->assign('amount',$amount); 
        $this->display();
    }
	public function edit()
    {
		$id= intval($_REQUEST['id']);
		$order = $id ? $this->dao->find($id) : '';
		$do = $_REQUEST['do']; 
		$this->assign('do',$do);
		$this->assign('id',$id);

		if($order['shipping_status'] && $do!='status'){
				$this->assign('dialog','1');
				$this->assign ( 'waitSecond', 2);
				$this->assign ( 'jumpUrl',1);
				$this->error (L('order_shippinged_no_edit'));
		}

		if($_REQUEST['dosubmit']){
			
			switch($do) {
				case 'data':
					$modle = M('Order_data');
					if($_GET['delete']){
						$data_id = intval($_GET['data_id']);
						$modle->delete($data_id);
					}else{
						foreach($_POST['data_id'] as $key=>$r){
							$data=array();
							$data['id'] = $r;
							$data['product_price'] = $_POST['product_price'][$key];
							$data['number'] =  $_POST['number'][$key];
							$data['price'] = $data['product_price']*$data['number'];
							$modle->save($data); 
						}
					}
					$_POST = order_count($order); 
				case 'money':
					$order['discount'] = $_POST['discount'];
					$_POST  = order_count($order);
				break;

				case 'payment':
					$order['pay_id'] = $_POST['pay_id'];
					$_POST  = order_count($order);
				break;

				case 'shipping':					
					$order['shipping_id'] = $_POST['shipping_id'];
					$order['insure'] =  $_POST['insure_'.$order['shipping_id']] ? 1 : 0;
					$_POST  = order_count($order);
				break;

				case 'status':					
					$order[$_POST['type']] = $_POST['value'];
					if($_POST['type'] == 'status' && $_POST['value']==2){
						$order['confirm_time'] =time();
					}elseif($_POST['type'] == 'shipping_status' && $_POST['value']==1){
						$order['shipping_time'] =time();
					}elseif($_POST['type'] == 'pay_status' && $_POST['value']==2){
						$order['pay_time'] =time();
						/*后台支付时将默认把微支付金额设为订单总额，表示支付完成 by dension*/
						$order['wechat_amount']=$order['order_amount'];
						if(intval($order['status'])!=2){
							/*如果订单还没完成，则支付时确认订单*/
							$order['status']=1;
						}
						/**/
					}elseif($_POST['type'] == 'shipping_status' && $_POST['value']==2){
						$order['accept_time']=time();
					}elseif($_POST['type'] == 'status' && $_POST['value']==99){
						$resul=$this->put_logistics($order);
						if($resul)
						die(json_encode(array('msg'=>"发送成功")));
							else
						die(json_encode(array('msg'=>"发送失败")));
					}

					if (false!==$this->dao->save($order)) {
						if($do=='status' && $_REQUEST['type'] == 'pay_status' && $_REQUEST['value']==2){
						
				/*支付成功后会员返回电子现金*/
						if($order['userid']){
								$users=M('user')->field('cash_use')->where('id='.$order['userid'])->find();
								$data['cash_use']=floatval($users['cash_use'])+floatval($order['rebate_fee']);
								$data['id']=$order['userid'];
								$cash_res=M('user')->save($data);
								if($cash_res){//记录金钱来去
								$data['user_id']=$this->_userid;
								$data['source']=5;
								$data['cash']=floatval($order['rebate_fee']);
								$data['create_time']=mktime();
								M("consume")->add($data);
								}
							 }
					/*返回电子现金 end*/
				/*支付成功后执行将订单号通过物流接口传给物流公司*/
							$method="PostOrder";//方法
							$Key="B49e7d57ca6643102dbec749ae8c1b6e";//加密串
							$ordernum=$order['sn'];//订单号
							$type=0;//0为新增订单
							$curl_url="http://wms.hans-trans.com/wms/interface/QueryService.aspx?method=".$method."&key=".$Key."&ordernum=".$ordernum."&type=".$type;
							$curl_result = file_get_contents($curl_url);
							$res=array();
							$res=json_decode($curl_result,TRUE);
							//file_put_contents("shipping.txt",$res);
							// if($res['success']==false)$this->put_shipping_error($order['id'],"102",$res['message']);else $this->put_shipping_error($order['id'],"101",$res['message']);
							// $order_data['shipping_notify']=$res['success']? 1:2;
							$order_data['id']=$order['id'];
							M("order")->save($order_data);
							}
						die(json_encode(array('msg'=>L('do_ok'))));
					}else{
						die(json_encode(array('msg'=>L('do_error'))));
					}
				break;				
			}
			if($do="data"){
					$r=$this->dao->save($_POST);
					if($r){
					$this->success (L('edit_ok'));}else{$this->error (L('do_error'));}
				}else{
			if (false === $this->dao->create())  $this->error ( $this->dao->getError () ); 
			if (false!==$this->dao->save()) {
				$this->assign('dialog','1');
				$jumpUrl = U(MODULE_NAME.'/show?id='.$_REQUEST['id']);
				$this->assign ( 'jumpUrl', $jumpUrl);
				$this->success (L('edit_ok'));
			}else{
				$this->error (L('do_error'));
			}
			}

			exit;
		}

		switch($do) {
				case 'address':
					$Area = M('Area')->getField('id,name');
					$this->assign('Area',$Area);
				break;

				case 'payment':
					$payment = M('Payment')->field('id,pay_code,pay_name,pay_fee,pay_fee_type,pay_desc,is_cod,is_online')->where("status=1")->select();
					$this->assign('payment',$payment);
				break;

				case 'data':
					$order_data = M('Order_data')->where("order_id='{$order[id]}'")->select();
					$this->assign('order_data',$order_data);
				break;
				case 'shipping':
					$shipping = M('Shipping')->where("status=1")->select();
					$this->assign('shipping',$shipping);
				break;
		}

		$this->assign('order',$order);
		$this->display();
    }
    /*获取物流信息*/
    protected function get_shipping_msg($list){
        foreach ($list as $key => $order) {
				$ordernum=$order['sn'];
				$method="QueryOrder";
				$Key='B49e7d57ca6643102dbec749ae8c1b6e';
			$curl_url="http://wms.hans-trans.com/wms/interface/QueryService.aspx?method=".$method."&key=".$Key."&ordernum=".$ordernum;
			$curl_result = file_get_contents($curl_url);
			$res=array();
			$change_data=array();
			$res=json_decode($curl_result,TRUE);
			if(!empty($res['data']['logisticCompany'])&&$order['shipping_id']!=2){
				$change_data['id']=$order['id'];
				$change_data['shipping_id']=2;
				$change_data['shipping_name']=$res['data']['logisticCompany'];
				$change_data['shipping_sn']=$res['data']['logisticNum'];
				M("order")->save($change_data);
			}
			if($res['success']){
				if($order['shipping_status']==0 && $res['data']['Status']==3){//发货
					//表示已经发货
					$timestr=$res['data']['steps'][1]['acceptTime'];
					$change_data["id"]=$order['id'];
					$change_data["shipping_status"]=1;
					$change_data["shipping_time"]=$timestr ? strtotime($timestr):mktime();
					M("order")->save($change_data);
				}elseif($res['data']['Status']==2){//收货
					$end_str=end($res['data']['steps']);
					$timestr=$end_str['acceptTime'];
					$change_data['id']=$order['id'];
					$change_data['accept_time']=$timestr? strtotime($timestr):0;
					$change_data["shipping_status"]=2;
					$change_data["status"]=2;
					M("order")->save($change_data);
				}elseif($res['data']['Status']==1){//退货
					$change_data["shipping_status"]=3;
					M("order")->save($change_data);
				}
			}
			//sleep(1);//每查完一条就等待1秒再继续
			}
 		return true;
	}
    /*获取物流信息end*/
    protected function put_wmx($order){
    	$method="PostOrder";//方法
    	$Key="B49e7d57ca6643102dbec749ae8c1b6e";//加密串
    	$ordernum=$order['sn'];//订单号
    	$type=0;//0为新增订单
    	$curl_url="http://wms.hans-trans.com/wms/interface/QueryService.aspx?method=".$method."&key=".$Key."&ordernum=".$ordernum."&type=".$type;
    	$curl_result = file_get_contents($curl_url);
    	$res=array();
    	$res=json_decode($curl_result,TRUE);
    		//file_put_contents("shipping.txt",$res);
    	$order_data['shipping_notify']=$res['success']? 1:2;
    	$order_data['id']=$order['id'];
    	M("order")->save($order_data);
    	if($res['success']==false)
    		{$this->put_shipping_error($order['id'],"102",$res['message']);
    		return false;
    		}else{
    			$this->put_shipping_error($order['id'],"101",$res['message']);
    			return true;
    		}

    }

    public function put_logistics($order)
    {
        /*获取物流信息 by dension*/
        $msg=M("shipping_msg")->where("order_id=".$order["id"])->find();
        if($order['pay_status'] && $order['pay_status']==2){
            $curl_url='http://api.i.zeny-express.com/api/a?u=19&c=ZENY-KJXM-001&d='.$order['shipping_sn'].'&kj=1';
            $curl_result = json_decode($this->cURLGet($curl_url),true);
            if($curl_result['data'][$order['shipping_sn']]&&$order['shipping_id']!=2){
                //表示已经发货
                $timestr=$curl_result['data'][$order['shipping_sn']][0]['AcceptTime'];
                $change_data["id"]=$order['id'];
                $change_data["shipping_status"]=1;
                $change_data["shipping_time"]=$timestr ? strtotime($timestr):mktime();
                M("order")->save($change_data);
                $order['shipping_status']=$change_data["shipping_status"];
                $order['shipping_time']=$change_data["shipping_time"];
                $msgs['order_id']=$order['id'];
                $msgs['message']=json_encode($curl_result['data'][$order['shipping_sn']]);
                $msgs['createtime']=$timestr ? strtotime($timestr):mktime();
                if($msg)
                M("shipping_msg")->data($msgs)->where("order_id=".$order["id"])->save();
                else
                M("shipping_msg")->add($msgs);
            }else{
                $msgs['order_id']=$order['id'];
                $shippingmsg[$order['shipping_sn']]['AcceptTime']=time();
                $shippingmsg[$order['shipping_sn']]['Remark']='暂无物流信息';
                $shippingmsg[$order['shipping_sn']]['states']='';
                $msgs['message']=json_encode($shippingmsg);
                $msgs['createtime']=time();
                if($msg)
                M("shipping_msg")->data($msgs)->where("order_id=".$order["id"])->save();
                else
                M("shipping_msg")->add($msgs);
            }
        }else{
            $msgs['order_id']=$order['id'];
            $shippingmsg[$order['shipping_sn']]['AcceptTime']=time();
            $shippingmsg[$order['shipping_sn']]['Remark']='订单未发货';
            $shippingmsg[$order['shipping_sn']]['states']='';
            $msgs['message']=json_encode($shippingmsg);
            $msgs['createtime']=time();
            if($msg)
            M("shipping_msg")->data($msgs)->where("order_id=".$order["id"])->save();
            else
            M("shipping_msg")->add($msgs);
        }
        return true;
    }
}
/*class end*/
function order_count($order){
	$order['amount'] = M('Order_data')->where("order_id='{$order[id]}'")->sum('price'); //商品总价
	$order['invoice_fee'] =  $order['invoice'] ? $order['amount']*0.05 : 0; //税金
	$order['invoice_fee'] =  number_format($order['invoice_fee'],2);

	if($order['shipping_id'])$Shipping = M('Shipping')->find($order['shipping_id']);
	if($order['pay_id'])$Payment  = M('Payment')->find($order['pay_id']);
	$order['pay_name'] = $Payment['pay_name'];
	$order['pay_code'] = $Payment['pay_code'];

	if($order['insure']){ //保价
		$insure_fee =$order['amount']*$Shipping['insure_fee']/100;
		$order['insure_fee'] = $insure_fee >=$Shipping['insure_low_price'] ? number_format($insure_fee,2) : $Shipping['insure_low_price'];
	}else{
		$order['insure_fee'] =0;
	}

   	$info=M('order_data')->where("order_id=".$order['id'])->select();
    $num=0;
    $rebate_fee=0;
    foreach ($info as $key => $value) {
    	$num+=$num+intval($value['number']);
			/*此处计算返还电子现金 by dension*/
			$rebate_fee+=intval($value['number'])*floatval($value['ratio']);
			/**/
    	}
    if($num>1){
    		$order['shipping_fee']=0;//如果商品数量2支以上就免运费
    	}elseif($num<2 && floatval($order['shipping_fee'])==0){
	$order['shipping_fee'] = $Shipping['first_price']; //运费
    	}
	$order['shipping_name']  = $Shipping['name']; //运费
		$order['rebate_fee']=$rebate_fee;//返还金额
	$order['order_amount'] = $order['amount']+$order['invoice_fee']+$order['insure_fee']+$order['shipping_fee']-$order['promotions']-$order['discount'];	
	$order['pay_fee'] =  $Payment['pay_fee_type'] ?  $Payment['pay_fee'] : $order['order_amount']*$Payment['pay_fee']/100; 
	$order['pay_fee'] =   number_format($order['pay_fee'],2);

	$order['order_amount'] = $order['order_amount']+$order['pay_fee'];
	return $order;  
}
?>