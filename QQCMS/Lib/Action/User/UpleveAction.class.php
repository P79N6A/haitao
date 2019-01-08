<?php 
/*升级金会员和会员充值通道*/
// header('Content-Type:text/html; charset=utf-8'); 
if(!defined("QQCMS")) exit("Access Denied");
class UpleveAction extends BaseAction{
	protected $keycode='wx024d0a4cfb7d58eb';
	public function index(){
		if(empty($this->_groupid)){
			$res['status']=0;
			$res['msg']="请先登录";
			echo json_encode($res);
			exit();
		}
		$username=M("user")->field("username")->where("id=".$this->_userid)->find();
		if(empty($username['username'])){
			$res['status']=0;
			$res['msg']="亲,请先注册再升级。";
			echo json_encode($res);exit();
		}
		if($this->_groupid==4){
			$res['status']=0;
			$res['msg']="您已经是金会员了";
			echo json_encode($res);exit();
		}
		if($this->_groupid>5){
			$res['status']=0;
			$res['msg']="您是经营者,不需要升级";
			echo json_encode($res);exit();
		}
		$type=intval($_POST['type'])?intval($_POST['type']):1;
		if($type==1){
			$role=M("role")->field("gold_fee")->where("id=4")->find();
			$fee=floatval($role['gold_fee']);
			if(!$fee){
			$res['status']=0;
			$res['msg']="抱歉,未设置升级金额";
			echo json_encode($res);exit();
			}
			$the_type=3;
		}else{
			$role=M("role")->field("gold_money")->where("id=4")->find();
			$fee=floatval($role['gold_money']);
			if(!$fee){
			$res['status']=0;
			$res['msg']="抱歉,未设置升级金额";
			echo json_encode($res);exit();
			}
			$the_type=1;
		}
		$url=$this->get_leveurl($this->_userid,$fee,$the_type);
		$url="http://".$_SERVER['HTTP_HOST'].'/pay/uplevel.php?code='.$url."&showwxpaytitle=1";
		$res['status']=1;
		$res['url']=$url;
		$res['fee']=$fee;
		echo json_encode($res);exit();
	}
	public function recharge(){
		$user = M("user")->field("wechat_openid")->where("id=".$this->_userid)->find();
		$fee=floatval($_POST['fee']);
		if(!$fee){
			$res['status']=0;
			$res['msg']="抱歉,请输入正确金额";
			echo json_encode($res);exit();
		}
		$payInfo = $this->get_rechargeurl($this->_userid,$fee);
		// $url="http://".$_SERVER['HTTP_HOST'].'/pay/uplevel.php?code='.$url."&showwxpaytitle=1";
		$html = '';
		if (!empty($payInfo['wxdata']))
		{
			$wxdata=$payInfo['wxdata'];
			$wxdata['openId'] = $user['wechat_openid'];
			$auth_key = md5($this->keycode);
			$wxdatacode = authcode($wxdata['userid'].'├─'.$wxdata['openId'].'├─'.$wxdata['re_sn'].'├─'.$wxdata['amount'].'├─'.$wxdata['type'], 'ENCODE', $auth_key);
			$pay_url = "http://".$_SERVER['HTTP_HOST'].'/wxpay/unifiedorder.php?pay_data='.$wxdatacode."&showwxpaytitle=1";
			$html .= '<form name="formwx" action="/wxpay/unifiedorder.php" method="POST">';
						$html .= '<input type="hidden" name="pay_data" value="'.$wxdatacode.'" />';
			$html .= '<button type="submit" class="btn btn-success btn-block">微信支付</button>';
			$html .= '</form>';
		}

		if ($payInfo['tldata']){
			if ($payInfo['tldata']['gateway_url_m'])
			{
				$pay_acturl_m = $payInfo['tldata']['gateway_url_m'];
				unset($payInfo['tldata']['gateway_url_m']);
			}
			if ($payInfo['tldata']['gateway_method'])
			{
				$gateway_method = $payInfo['tldata']['gateway_method'];
				unset($payInfo['tldata']['gateway_method']);
			}
			unset($payInfo['tldata']['pay_config']);
			$html .= '<form name="form2" action="'.$pay_acturl_m.'" method="'.$gateway_method.'">';
			foreach ($payInfo['tldata'] as $key => $val) {
				$html .= '<input type="hidden" name="'.$key.'" id="'.$key.'" value="'.$val.'" />';
			}
			$html .= '<button type="submit" class="btn btn-primary btn btn-block">手机银行支付</button>';
			$html .= '</form>';
		}
		$res['data'] = $html;
		$res['fee'] = $fee;
		$res['status'] = 1;
		echo json_encode($res);exit();
	}	
protected function get_rechargeurl($userid=0,$fee=0){
		$model = M("wechat_order");
		$receipt_order = $model->where("status=0 and type=1 and userid=".$userid)->find();
		##获取通联支付信息##
		/*支付方式*/
		$pay_where['pay_code'] = 'Allinpay'; // 通联支付
		$pay_where['status'] = 1; 
		$Payment = M('Payment')->where($pay_where)->find();

		##获取用户信息##
		if ($userid)
			$userInfo = M('User')->field('realname,wechat_name,email,mobile')->find($userid);
		else
		{
			$res['status'] = 0;
			$res['msg'] = "请先登录";
			echo json_encode($res);exit();
		}
		if(!empty($receipt_order)){
			$receipt_order['amount'] = floatval($fee);
			$receipt_order['createtime']=mktime();
			$receipt_order['re_sn'] = date("YmdHis"). sprintf('%06d',$receipt_order['id']);
			$model->save($receipt_order);
			// $url=$this->pay_data($receipt_order,"会员充值");
			$receipt_order['realname'] = $userInfo['realname'];
			$receipt_order['wechat_name'] = $userInfo['wechat_name'];
			$receipt_order['email'] = $userInfo['email'];
			$receipt_order['goods_number'] = 1;
			$receipt_order['mobile'] = $userInfo['mobile'];
			$receipt_order['allinipay_amount'] = $receipt_order['amount'];
			$receipt_order['add_time'] = $receipt_order['createtime'];
			$receipt_order['end_time'] = strtotime("+1 week");
			$pay_config['tldata'] = $this->getAllinpay($Payment['pay_code'],$receipt_order,$Payment);//获取到支付信息
			$pay_config['wxdata']=$receipt_order;
		}else{//生成订单
			$order['userid']=$userid;
			$order['amount']=floatval($fee);
			$order['status']=0;
			$order['type']=1;
			$order['createtime']=mktime();
			$orderid=$model->add($order);
			if($orderid){
				$order['sn'] = date("Ymd"). sprintf('%06d',$orderid);
				$order['re_sn'] = date("YmdHis"). sprintf('%06d',$orderid);
				$model->save(array('id'=>$orderid,'sn'=>$order['sn'],'re_sn'=>$order['re_sn']));
				$pay_config['tldata'] = $this->getAllinpay($Payment['pay_code'],$receipt_order,$Payment);//获取到支付信息
				$pay_config['wxdata']=$order;
			}else{
				$this->error("网络错误");
			}
		}
		return $pay_config;
	}
	protected function get_leveurl($userid=0,$fee=0,$type=0){
		$model=M("wechat_order");
		$receipt_order=$model->where("status=0 and type=".$type." and userid=".$userid)->find();
		if(!empty($receipt_order)){
			$receipt_order['amount']=$fee;
			$receipt_order['createtime']=mktime();
			$model->save($receipt_order);
			$url=$this->pay_data($receipt_order,"会员升级");
		}else{//生成订单
			$order['userid']=$userid;
			$order['amount']=floatval($fee);//押金
			$order['status']=0;
			$order['type']=$type;
			$order['createtime']=mktime();
			$orderid=$model->add($order);
			if($orderid){
				$order['sn'] = date("Ymd"). sprintf('%06d',$orderid);
				$model->save(array('id'=>$orderid,'sn'=>$order['sn'])); 
				$url=$this->pay_data($order,"会员升级");
			}else{
				$this->error("网络错误");
			}
		}
		return $url;
	}
	/*组装支付按钮数据*/
	public function pay_data($arr=array(),$str=""){
		$user_ip=$_SERVER['REMOTE_ADDR'];
		/*开始组装表单*/
		$data['order_id']=(string)$arr['id'];
		$data['order_name']=$str;
		$data['order_sn']=(string)$arr['sn'];
		$data['order_amount']=$arr['amount'];//支付金额为微支付总额
		$data['user_ip']=(string)$user_ip;
		$str=json_encode($data);
		$str=urlencode($str);//将数据并成字符串
		$result=$this->lock($str,"dension");//加密数据字符串
		return $result;
	}
	/*获取金额*/
	public function get_role_fee(){
			$role=M("role")->field("gold_fee,gold_money")->where("id=4")->find();
			$data['gold_fee']=$role['gold_fee'];
			$data['gold_money']=$role['gold_money'];
			echo json_encode($data);exit();
	}
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
//解密函数
function unlock($txt,$key='zhuoyuexiazai'){
	$txt = base64_decode(urldecode($txt));
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+";
    $ch = $txt[0];
    $nh = strpos($chars,$ch);
    $mdKey = md5($key.$ch);
    $mdKey = substr($mdKey,$nh%8, $nh%8+7);
    $txt = substr($txt,1);
    $tmp = '';
    $i=0;$j=0; $k = 0;
    for ($i=0; $i<strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = strpos($chars,$txt[$i])-$nh - ord($mdKey[$k++]);
        while ($j<0) $j+=64;
        $tmp .= $chars[$j];
    }
    return trim(base64_decode($tmp),$key);
}
/*end*/
}
?>