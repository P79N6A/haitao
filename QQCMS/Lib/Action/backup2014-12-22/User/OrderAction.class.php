<?php
/**
 * 
 * OrderAction.class.php (订单管理)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if(!defined("QQCMS")) exit("Access Denied");
class OrderAction extends BaseAction
{

	function _initialize()
    {	
		parent::_initialize();

		$this->dao = M('Order');
		$this->assign('bcid',0);
		$user =  M('User')->find($this->_userid);
		$this->assign('vo',$user);
    }

    public function index()
    {
    	$uid=$this->_userid;
        import ( "@.ORG.Page2" );
        $order=M('order');
        $count=$order->where("userid=".$uid)->count();
        $listRows = 10; 
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
        $list=$order->where("userid=".$uid)->order('id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign("list",$list);
        $this->assign('pages',$pages);
        $this->display();
    }

	public function show()
    {
		$sn = intval($_REQUEST['sn']);
		$id= intval($_REQUEST['id']);
		$order = $id ? $this->dao->find($id) : $this->dao->getBySn($sn) ;
 			/*获取物流信息 by dension*/
 			if($order['pay_status']==2){
 				$ordernum=$order['sn'];
 				$method="QueryOrder";
 				$Key='B49e7d57ca6643102dbec749ae8c1b6e';
				$curl_url="http://wms.hans-trans.com/wms/interface/QueryService.aspx?method=".$method."&key=".$Key."&ordernum=".$ordernum;
				$curl_result = file_get_contents($curl_url);
				$res=array();
				$res=json_decode($curl_result,TRUE);
				if(!empty($res['data']['logisticCompany'])&&!empty($res['data']['logisticNum'])&&$order['shipping_id']!=2){
					$change_data['id']=$order['id'];
					$change_data['shipping_id']=2;
					$change_data['shipping_name']=$res['data']['logisticCompany'];
					$change_data['shipping_sn']=$res['data']['logisticNum'];
					M("order")->save($change_data);
					echo "<script>window.location.reload();</script>";exit();
				}
				if($res['success']){
					if($order['shipping_status']==0 && $res['data']['Status']==3){//发货
						//表示已经发货
						$timestr=$res['data']['steps'][1]['acceptTime'];
						$change_data["id"]=$order['id'];
						$change_data["shipping_status"]=1;
						$change_data["shipping_time"]=$timestr ? strtotime($timestr):0;
						M("order")->save($change_data);
						echo "<script>window.location.reload();</script>";exit();
					}elseif($order['shipping_status']!=2 &&$res['data']['Status']==2){//收货
						$end_str=end($res['data']['steps']);
						$timestr=$end_str['acceptTime'];
						$change_data['id']=$order['id'];
						$change_data['accept_time']=$timestr? strtotime($timestr):0;
						$change_data["shipping_status"]=2;
						M("order")->save($change_data);
						echo "<script>window.location.reload();</script>";exit();
					}elseif($order['shipping_status']!=3 &&$res['data']['Status']==1){//退货
						$change_data["shipping_status"]=3;
						M("order")->save($change_data);
						echo "<script>window.location.reload();</script>";exit();
					}
					$this->assign('this_shipping_status',$res['data']['Status']);
					$this->assign('shipping_steps',$res['data']['steps']);
				}
 			}
 			/**/
		if(!$order && $order['userid']!=$this->_userid) $this->success (L('do_empty'));

		$order_data = M('Order_data')->where("order_id='{$order[id]}'")->select();
		$amount=0;
		foreach($order_data as $key=>$r){
			$amount = $amount+$r['price'];
		
		}
	 	

		$Payment = M('Payment')->find($order['pay_id']);
		$Shipping = M('Shipping')->find($shippingid);
		$Area = M('Area')->getField('id,name');
		$this->assign('Area',$Area);
		$this->assign('Payment',$Payment);
		$this->assign('Shipping',$Shipping);
		if($order['pay_code'] && $order['status']<2 && $order['pay_status']<2){
			if($order['pay_code']=="Wechat_pay"){
				$paybutton=$this->pay_data($order);//获取到支付按钮
			}elseif($order['pay_code']=="Cash_pay" && $order['cash_pay_status']==1 &&$order['wechat_amount']>0){//表示已完成电子现金支付，但仍需要微信支付
				$paybutton=$this->pay_data($order);//获取到支付按钮
			}else{
			$aliapy_config = unserialize($Payment['pay_config']);
			$aliapy_config['order_sn']= $order['sn'];
			$aliapy_config['order_amount']= $order['order_amount'];
			$aliapy_config['body'] = $order['consignee'].' '.$order['postmessage'];
			import("@.Pay.".$order['pay_code']);
			$pay=new $order['pay_code']($aliapy_config);
			$paybutton = $pay->get_code();
			}
			$this->assign('paybutton',$paybutton);
		}

		$this->assign('order',$order);
		$this->assign('order_data',$order_data);
		$this->assign('amount',$amount); 
		$this->display();
    }


	/*组装支付按钮数据*/
	public function pay_data($arr){
		$user_ip=$_SERVER['REMOTE_ADDR'];
		/*开始组装表单*/
		$data['order_id']=(string)$arr['id'];
		$data['order_name']=(string)$arr['consignee']."的订单。";
		$data['order_sn']=(string)$arr['sn'];
		$data['order_amount']=$arr['wechat_amount'];//支付金额为微支付总额
		$data['user_ip']=(string)$user_ip;
		$str=json_encode($data);//将数据并成JSON字符串
		$result=$this->lock($str,"dension");//加密数据字符串
		return $result;
	}
	/*end*/
/*加密-解密*/
function lock($txt,$key='dension'){
	$txt = $txt.$key;
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+";
    $nh = rand(0,64);
    $ch = $chars[$nh];
    $mdKey = md5($key.$ch);
    $mdKey = substr($mdKey,$nh%8, $nh%8+7);
    $txt = base64_encode($txt);
    $tmp = '';
    $i=0;$j=0;$k = 0;
    for ($i=0; $i<strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = ($nh+strpos($chars,$txt[$i])+ord($mdKey[$k++]))%64;
        $tmp .= $chars[$j];
    }
    return urlencode(base64_encode($ch.$tmp));
}

	function ajax(){
		
		$model= M('Order');
		$order = $model->find($_POST['id']);
		if($order['userid']!=$this->_userid)die(json_encode(array('msg'=>L('do_empty'))));
		if($_GET['do']=='saveaddress'){
			$r = $model->save($_POST);
			die(json_encode(array('id'=>1)));
		}elseif($_GET['do'] =='order_status'){
			$_POST['status']=3;
			$_POST['confirm_time']=time();
			if($order['pay_status']!=2&&$order['pay_code']=='Cash_pay' && floatval($order['cash_coupon'])<floatval($order['order_amount'])){
				//如果是电子现金支付且仍需微信支付但尚未支付时，可以取消订单且退回电子现金
				$data['cash_use']=array('exp','cash_use+'.$order['cash_coupon']);
				$data['id']=$order['userid'];
				M("user")->save($data);
				$this->put_consume(floatval($order['cash_coupon']),7,$order['userid']);
			}
			$r = $model->save($_POST);
			die(json_encode(array('id'=>1)));
		}elseif($_GET['do'] =='pay_status'){
			$_POST['pay_status']=3;
			$r = $model->save($_POST);
			die(json_encode(array('id'=>1)));
		}elseif($_GET['do'] =='shipping_status'){
			$_POST['shipping_status']=$_POST['num'];
			unset($_POST['num']);
			$_POST['accept_time']= $_POST['shipping_status']==2 ? time() : '';
			$r = $model->save($_POST);
			die(json_encode(array('id'=>1)));
		}
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

	
}
?>