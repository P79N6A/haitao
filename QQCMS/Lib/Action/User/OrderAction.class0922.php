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
        $order = M('order');
        $count = $order->where("userid=".$uid)->count();
        $listRows = 10; 
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
        $list = $order->where("userid=".$uid)->order('id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        foreach ($list as $key => $val) {
        	if ($val['end_time'] < time() && !$val['status'] && !$this->_checkOrder)
        	{
        		##获取订单商品##
        		/*$join = 'qq_product_oversea as po ON od.product_id = po.id';
        		$field = 'od.id,od.order_id,od.product_id,od.number,po.stock';*/
        		$where['order_id'] = $val['id'];
        		$order_data = M('OrderData')->field('id,order_id,product_id,number,goods_attr_id')->where($where)->select();
        		foreach ($order_data as $k => $v) {
        			if(!empty($v['goods_attr_id']))
        				M("ProductProperty")->where("property_id=".$v['goods_attr_id'])->setInc('stock',$v['number']);//返还库存
        			else
        				M("ProductOversea")->where("id=".$v['product_id'])->setInc('stock',$v['number']);//返还库存
        		}
        		$orderd['id'] = $val['id'];
        		$orderd['status'] = 6;
        		$or=$order->save($orderd);
        		if($or)
        		$list[$key]['status'] = 6;
        	}
        }
        $this->assign("list",$list);
        $this->assign('pages',$pages);
        $this->display();
    }

	public function show()
    {
		$sn = intval($_REQUEST['sn']);
		$id= intval($_REQUEST['id']);
		// $order = $id ? $this->dao->find($id) : $this->dao->getBySn($sn) ;
		$order = $this->dao->find($id);
		$user = M('User')->find($order['userid']);
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
			##获取商品属性##
			$property=M('ProductProperty')->field('attribute_group')->where("property_id=".intval($r['goods_attr_id']))->find();
			if(!empty($property)){
				$goods_attr_id=unserialize($property['attribute_group']);
				$attribute=array();
				if($goods_attr_id && is_array($goods_attr_id)){
					foreach ($goods_attr_id as $kg => $gi) {
						$extend=M('PropertyExtend')->alias('pe')->field('pe.*,sp.specsname')->join('qq_specs as sp ON pe.specs_id=sp.specs_id')->where('extend_id='.intval($gi))->find();
						$attribute[]=$extend;//组装当前商品所有属性规格
					}
					$order_data[$key]['attribute']=$attribute;
				}
			}
		}
		/*购买会员信息*/
		$order['realname'] = $user['realname'];
		$order['username'] = $user['username'];
		$order['wechat_name'] = $user['wechat_name'];
		$order['email'] = $user['email'];
		$order['mobile'] = $user['mobile'];
		$order['openId']=$user['wechat_openid'];
		
		$Shipping = M('Shipping')->find($shippingid);
		$Area = M('Area')->getField('id,name');
		$this->assign('Area',$Area);
		$this->assign('Payment',$Payment);
		$this->assign('Shipping',$Shipping);
		if($order['status']<2 && $order['pay_status']<2){
			if($order['allinipay_amount']>0){
				$order_data_num = M('Order_data')->where('order_id='.$id)->count();
				$$order['goods_number'] = $order_data_num;
				/*通联支付*/
				$Payment = M('Payment')->where(array('pay_code'=>'Allinpay', 'status'=>1))->find();
				$paybutton=$this->getAllinpay('Allinpay',$order,$Payment);//获取到支付信息
			}

			if ($paybutton['gateway_url_m'])
			{
				$pay_acturl_m = $paybutton['gateway_url_m'];
				unset($paybutton['gateway_url_m']);
			}

			if ($paybutton['gateway_method'])
			{
				$gateway_method = $paybutton['gateway_method'];
				unset($paybutton['gateway_method']);
			}
			unset($paybutton['pay_config']);
			$this->assign('gateway_method',$gateway_method);
			$this->assign('pay_acturl_m',$pay_acturl_m);
			$this->assign('paybutton',$paybutton);
			if($order['wechat_amount']>0){
				$order['pay_button']=1;//使用微信支付
			}
		}

		
		$order['order_qrcode'] = './order_qrcode/order'.$order['sn'].'.png';
		if (!file_exists($order['order_qrcode']))
		{
			import("@.ORG.QRcode");
			\QRcode::png($order['sn'],$order['order_qrcode'],'H',8,0);
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
			if($order['status']!=3 && $order['pay_status']!=2 && floatval($order['cash_coupon'])<floatval($order['order_amount'])){
				//如果是电子现金支付且仍需微信支付但尚未支付时，可以取消订单且退回电子现金
				$data['cash_use']=array('exp','cash_use+'.$order['cash_coupon']);
				$data['id']=$order['userid'];
				M("user")->save($data);
				$this->put_consume(floatval($order['cash_coupon']),7,$order['userid']);

				$where['order_id'] = $order['id'];
        		$order_data = M('OrderData')->field('id,order_id,product_id,number,goods_attr_id')->where($where)->select();
        		foreach ($order_data as $k => $v) {
        			if(!empty($v['goods_attr_id']))
        				M("ProductProperty")->where("property_id=".$v['goods_attr_id'])->setInc('stock',$v['number']);//返还库存
        			else
        				M("ProductOversea")->where("id=".$v['product_id'])->setInc('stock',$v['number']);//返还库存
        		}
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