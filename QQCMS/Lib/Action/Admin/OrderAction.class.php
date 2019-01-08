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
    function _initialize()
    {	
		parent::_initialize();
		$this->dao=M('Order');	
    }

    public function index()
    {
	    $this->order_list(MODULE_NAME);
        $this->display();
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
			foreach($order_data as $key=>$r){
				$amount = $amount+$r['price'];
			} 
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
						$change_data["shipping_time"]=$timestr ? strtotime($timestr):mktime();
						M("order")->save($change_data);
						echo "<script>window.location.reload();</script>";exit();
					}elseif($res['data']['Status']==2){//收货
						$end_str=end($res['data']['steps']);
						$timestr=$end_str['acceptTime'];
						$change_data['id']=$order['id'];
						$change_data['accept_time']=$timestr? strtotime($timestr):0;
						$change_data["shipping_status"]=2;
						$change_data["status"]=2;//同时完成订单
						M("order")->save($change_data);
						echo "<script>window.location.reload();</script>";exit();
					}elseif($res['data']['Status']==1){//退货
						$change_data["shipping_status"]=3;
						M("order")->save($change_data);
						echo "<script>window.location.reload();</script>";exit();
					}
					$this->assign('this_shipping_status',$res['data']['Status']);
					$this->assign('shipping_steps',$res['data']['steps']);
				}else{
					$this->assign('shipping_error_msg',$res['message']);
				}
 			}
 			$msg=M("shipping_msg")->where("order_id=".$order["id"])->select();
			$this->assign('msg',$msg);
 			/*var_dump($msg);exit();*/
 			/**/
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
						//$order['wechat_amount']=$order['order_amount'];
						if(intval($order['status'])!=2){
							/*如果订单还没完成，则支付时确认订单*/
							$order['status']=1;
						}
						/**/
					}elseif($_POST['type'] == 'shipping_status' && $_POST['value']==2){
						$order['accept_time']=time();
					}elseif($_POST['type'] == 'status' && $_POST['value']==99){
						$resul=$this->put_wmx($order);
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
							if($res['success']==false)$this->put_shipping_error($order['id'],"102",$res['message']);else $this->put_shipping_error($order['id'],"101",$res['message']);
							$order_data['shipping_notify']=$res['success']? 1:2;
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