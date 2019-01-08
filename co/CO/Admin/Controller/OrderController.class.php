<?php
namespace Admin\Controller;
use Think\Controller;
class OrderController extends CommController {

/*私人酒窖订单*/
	public function common_order(){

		$where['shop_id'] = session('uid');
		$where['is_private'] =1;

		$count = M('order')->field('id')->where($where)->count();
		$p = new \Think\Page2($count,15); 
		
		$order_info =  M('order')->field(" `qq_user`.realname,`qq_user`.wechat_name,`qq_role`.name,`qq_order`.*")->join(" `qq_user` on `qq_user`.id=`qq_order`.userid ")->join(' `qq_role` on `qq_role`.id=`qq_user`.groupid ')->where("`qq_order`.shop_id=".session('uid')." and `qq_order`.is_private=1")->order('`qq_order`.id desc')->limit($p->firstRow.','.$p->listRows)->select();
		$this->assign('page',$p->show());
		$this->assign('order_info',$order_info);

		$this->display();
	}
	/*商城订单，只可以看详情*/
	public function index(){

		$where['shop_id'] = session('uid');
		$where['is_private'] =0;

		$count = M('order')->field('id')->where($where)->count();
		$p = new \Think\Page2($count,15); 
		
		$order_info =  M('order')->field(" `qq_user`.realname,`qq_user`.wechat_name,`qq_role`.name,`qq_order`.*")->join(" `qq_user` on `qq_user`.id=`qq_order`.userid ")->join(' `qq_role` on `qq_role`.id=`qq_user`.groupid ')->where("`qq_order`.shop_id=".session('uid')." and `qq_order`.is_private=0")->order('`qq_order`.id desc')->limit($p->firstRow.','.$p->listRows)->select();
		$this->assign('page',$p->show());
		$this->assign('order_info',$order_info);

		$this->display();
	}
	public function info(){
		/*订单信息*/
		$where1['id'] = $_GET['order_id'];
		$where1['shop_id'] = session('uid');
		$where1['is_private'] =1;
		$order_info =  M('order')->where($where1)->order('id desc')->find();
		if(!$order_info){$this->error("您没有该私人酒窖订单哦!");exit();}
		//print_r($order_info);die();
		$this->assign('order_info',$order_info);

		/*订单产品*/
		$where2['order_id'] = $order_info['id'];
		$order_list =  M('order_data')->where($where2)->order('id desc')->select();

		//print_r($order_list);die();
		$this->assign('order_list',$order_list);


		$Area = M('Area')->getField('id,name');
		$this->assign('Area',$Area);
		$this->display();
	}
	public function edit(){
		/*订单信息*/
		$where1['id'] = $_GET['order_id'];
		$where1['shop_id'] = session('uid');
		$where1['is_private'] =0;
		$order_info =  M('order')->where(" id=".$_GET['order_id']." and (shop_id='".$where1['shop_id']."' or parent_shopid='".$where1['shop_id']."') ")->order('id desc')->find();
		if(!$order_info){$this->error("您没有该商城订单哦!");exit();}
		//print_r($order_info);die();
		$this->assign('order_info',$order_info);

		/*订单产品*/
		$where2['order_id'] = $order_info['id'];
		$order_list =  M('order_data')->where($where2)->order('id desc')->select();

 			/*获取物流信息 by dension*/
 			if($order_info['pay_status']==2){
 				$ordernum=$order_info['sn'];
 				$method="QueryOrder";
 				$Key='B49e7d57ca6643102dbec749ae8c1b6e';
				$curl_url="http://wms.hans-trans.com/wms/interface/QueryService.aspx?method=".$method."&key=".$Key."&ordernum=".$ordernum;
				$curl_result = file_get_contents($curl_url);
				$res=array();
				$res=json_decode($curl_result,TRUE);
				if($res['success']){
					if($order_info['shipping_status']==0 && $res['data']['Status']==3){//发货
						//表示已经发货
						$timestr=$res['data']['steps'][1]['acceptTime'];
						$change_data["id"]=$order_info['id'];
						$change_data["shipping_status"]=1;
						$change_data["shipping_time"]=$timestr ? strtotime($timestr):mktime();
						M("order")->save($change_data);
						echo "<script>window.location.reload();</script>";exit();
					}elseif($res['data']['Status']==2){//收货
						$end_str=end($res['data']['steps']);
						$timestr=$end_str['acceptTime'];
						$change_data['id']=$order_info['id'];
						$change_data['accept_time']=$timestr? strtotime($timestr):0;
						$change_data["shipping_status"]=2;
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
 			$msg=M("shipping_msg")->where("order_id=".$order_info["id"])->select();
			$this->assign('msg',$msg);
 			/*var_dump($msg);exit();*/
		//print_r($order_list);die();
		$this->assign('order_list',$order_list);


		$Area = M('Area')->getField('id,name');
		$this->assign('Area',$Area);
		$this->display();
	}



	public function del(){
		$order_id = $_GET['order_id'];
		$shop_id= session('uid');
		$order=M('order')->field('id,pay_status,shipping_status')->where('id = '.$order_id." and shop_id=".$shop_id)->find();
		var_dump($order);exit();
		if($order){
		if($order['pay_status']||$order['shipping_status']){
			$this->error("订单已支付或已发货，不能删除");exit();
		}
		$data['id']=$order['id'];
		$r=M('order')->where($data)->delete();
		if($r)$p=M('order_data')->where("order_id=".$order_id)->delete();
		if($r&&$p)
			{header("Location: /co/index.php/Order/index.shtml");}
		 else {$this->error("删除失败");exit();}
		}else{
			$this->error("删除失败");exit();
		}
	}

	/*确认订单*/
	public function queren_order(){

		$where1['id'] = $_GET['order_id'];
		$where1['shop_id'] = session('uid');
		$where1['is_private'] =1;
		$order_info =  M('order')->where($where1)->order('id desc')->find();
		if(!$order_info){$this->error("您没有该私人酒窖订单哦!");exit();}
		$order_id = $_GET['order_id'];
		//echo $order_id;
		 //UPDATE `qq_order_data` SET `

		$sql = 'update qq_order set status = 1 where id = '.$order_id;
		$query = mysql_query($sql);

		header("Location: /co/index.php/Order/info.shtml?order_id=$order_id");

	}

	/*订单支付确认*/
	public function queren_pay(){
		//echo 123;exit;
		$where1['id'] = $_GET['order_id'];
		$where1['shop_id'] = session('uid');
		$where1['is_private'] =1;
		$order_info =  M('order')->where($where1)->order('id desc')->find();
		if(!$order_info){$this->error("您没有该私人酒窖订单哦!");exit();}
		$order_id = $_GET['order_id'];
		//echo $order_id;
		 //UPDATE `qq_order_data` SET `

		$sql = 'update qq_order set pay_status = 2,pay_time ='.time().' where id = '.$order_id;
		$query = mysql_query($sql);

		header("Location: /co/index.php/Order/info.shtml?order_id=$order_id");

	}

	/*发货确认*/
	public function queren_fahuo(){
		$where1['id'] = $_GET['order_id'];
		$where1['shop_id'] = session('uid');
		$where1['is_private'] =1;
		$order_info =  M('order')->where($where1)->order('id desc')->find();
		if(!$order_info){$this->error("您没有该私人酒窖订单哦!");exit();}
		$order_id = $_GET['order_id'];
		$sql = 'update qq_order set shipping_status = 1,shipping_time ='.time().' where id = '.$order_id;
		//echo $sql;exit;
		$query = mysql_query($sql);

		header("Location: /co/index.php/Order/info.shtml?order_id=$order_id");

	}

	/*订单确认完成*/
	public function queren_wancheng(){
		$where1['id'] = $_GET['order_id'];
		$where1['shop_id'] = session('uid');
		$where1['is_private'] =1;
		$order_info =  M('order')->where($where1)->order('id desc')->find();
		if(!$order_info){$this->error("您没有该私人酒窖订单哦!");exit();}
		$order_id = $_GET['order_id'];
		$sql = 'update qq_order set status = 2,confirm_time ='.time().' where id = '.$order_id;
		//echo $sql;exit;
		$query = mysql_query($sql);

		header("Location: /co/index.php/Order/info.shtml?order_id=$order_id");

	}

	/*订单作废*/
	public function zuofei_order(){
		$where1['id'] = $_GET['order_id'];
		$where1['shop_id'] = session('uid');
		$where1['is_private'] =1;
		$order_info =  M('order')->where($where1)->order('id desc')->find();
		if(!$order_info){$this->error("您没有该私人酒窖订单哦!");exit();}
		$order_id = $_GET['order_id'];
		$sql = 'update qq_order set status = 3 where id = '.$order_id;
		//echo $sql;exit;
		$query = mysql_query($sql);

		header("Location: /co/index.php/Order/info.shtml?order_id=$order_id");

	}

	/*填写订单号*/
	public function order_num(){
		$where1['id'] = $_POST['order_id'];
		$where1['shop_id'] = session('uid');
		$where1['is_private'] =1;
		$order_info =  M('order')->where($where1)->order('id desc')->find();
		if(!$order_info){$this->error("您没有该私人酒窖订单哦!");exit();}
		$order_sn = $_POST['order_sn'];
		$order_id = $_POST['order_id'];
		$sql = 'update qq_order set shipping_sn = "'.$order_sn.'" where id = '.$order_id;
		$query = mysql_query($sql);
		$this->success("操作成功");

	}
	/*填写配送公司*/
	public function order_shippingname(){
		$where1['id'] = $_POST['order_id'];
		$where1['shop_id'] = session('uid');
		$where1['is_private'] =1;
		$order_info =  M('order')->where($where1)->order('id desc')->find();
		if(!$order_info){$this->error("您没有该私人酒窖订单哦!");exit();}
		$shipping_name = $_POST['shipping_name'];
		$order_id = $_POST['order_id'];
		$sql = 'update qq_order set shipping_name = "'.$shipping_name.'" where id = '.$order_id;
		$query = mysql_query($sql);
		$this->success("操作成功");
	}

	/*修改订单邮费*/
	public function order_Shipping(){

		$where1['id'] = $_POST['order_id'];
		$where1['shop_id'] = session('uid');
		$where1['is_private'] =1;
		$order_info =  M('order')->where($where1)->order('id desc')->find();
		if(!$order_info){$this->error("您没有该私人酒窖订单哦!");exit();}
		$order_Shipping = $_POST['order_Shipping'];
		$order_id = $_POST['order_id'];
		//echo $order_Shipping;

		//改变邮费
		$sql1 = 'update qq_order set shipping_fee = "'.$order_Shipping.'" where id = '.$order_id;
		//echo $sql;exit;
		$query = mysql_query($sql1);

		//改变订单总价
		$sql2 = 'update qq_order set order_amount = shipping_fee+amount where id = '.$order_id;
		//echo $sql2;exit;
		$query = mysql_query($sql2);

		$this->success("操作成功");

	}
}
