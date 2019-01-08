<?php
/**
 * 物流信息接口
 */
if(!defined("QQCMS")) exit("Access Denied"); 
class ShippingAction extends Action
{
	public function get_msg(){
		if($_REQUEST['key']=="834e74326be51153a4a553261f455d12"){
			$order=M("order")->field("id")->where("sn=".$_REQUEST['ordernum'])->find();
			if(empty($order)){
			$res['status']=0;
			$res['message']="没有订单号为".$_REQUEST['ordernum']."的订单";
			echo json_encode($res);exit();
			}
			switch ($_REQUEST['type']){
				case '201':
					$con['order_id']=$order['id'];
					$con['shipping_status']=1;//发货标记
					$con['shipping_time']=mktime();
					M("order")->save($con);
					break;
				case '401':
					$con['order_id']=$order['id'];
					$con['shipping_status']=2;//发货标记
					$con['accept_time']=mktime();
					M("order")->save($con);
					break;
				default:
					break;
			}
			$msg_data['order_id']=$order['id'];
			$msg_data['type']=intval($_REQUEST['type']);
			$msg_data['message']=$_REQUEST['message'];
			$msg_data['createtime']=mktime();
			$res=M("shipping_msg")->save($msg_data);
			if($res){
			$res['status']=1;
			$res['message']="通知成功";
			echo json_encode($res);exit();
			}else{
			$res['status']=0;
			$res['message']="通知失败";
			echo json_encode($res);
			}
		}else{
			$res['status']=0;
			$res['message']="所传key值不对";
			echo json_encode($res);
		}
	}
}