<?php
/**
 * 
 * OrderAction.class.php (前台购物订单)
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
	protected   $dao , $sessionid;
	function _initialize()
    {
		parent::_initialize();
		$this->dao=M('Cart');
		$this->sessionid = $_COOKIE['YP_onlineid'];
		//$this->_userid = $_COOKIE['YP_userid'];
        $this->shop_id=$this->_shopid?$this->_shopid:1;
    }


    public function index()
    {	
		$buy = intval($_REQUEST['buy']);
		$cart = $this->dao->where("userid='{$this->_userid}'")->select();
		$this->assign('cart',$cart);
        $this->display();
    }

	public function checkout(){
		if(empty($this->_userid)){
			header("location:".U('Home/Index/index'));exit();
		}
		if($this->Config['isuserbuy'] && empty($this->_userid))$this->error ( L('do_empty'));

		/*$data= M()->query("SELECT DISTINCT o.userid FROM ".C('DB_PREFIX')."cart as c , ".C('DB_PREFIX')."online as o where c.userid=o.userid ");*/
		/*if($data){
			foreach($data as $key=>$id)$ids[]=$id['userid'];
			M('Cart')->where(" userid NOT in('".implode("','",$ids)."') ")->delete();
		}*/
		$join = 'qq_product_oversea as p ON c.product_id = p.id';
		$field = 'c.*,p.post_price,p.post_rate';
		$where['c.userid'] = $this->_userid;
		$cart = $this->dao->alias('c')->field($field)->join($join)->where($where)->select();
		if (empty($cart))
		{
			$this->display('cartnull');exit;
		}
		
		$amount=0;
		$rebate_fee=0;
		foreach($cart as $key=>$r){
			$amount = $amount+$r['price'];
			/*此处计算返还电子现金 by dension*/
			$rebate_fee+=intval($r['number'])*floatval($r['ratio']);
			/**/
		}
		$this->assign('rebate_fee',$rebate_fee);
		$this->assign('cart',$cart);
		$this->assign('buy',1);

		if($this->_userid)
			$user_address = M('User_address')->where("userid='{$this->_userid}'")->select();
		else
			if($_COOKIE['YP_guest_address'])$default_address = unserialize($_COOKIE['YP_guest_address']);

		$Area = M('Area')->getField('id,name');
		$shipping = M('Shipping')->where("status=1")->select();
		$payment = M('Payment')->field('id,pay_code,pay_name,pay_fee,pay_fee_type,pay_desc,is_cod,is_online')->where("status=1")->select();

		foreach($user_address as $key=>$r){
			if($r['isdefault']){$default_address=$r;
				/*新增 消除在user_address中的默认地址*/
				unset($user_address[$key]);
			}
		}
		$user_address = array_values($user_address); //去除键值
		/*查找剩余电子现金以及会员当前状态*/
		$cash_user=M("user")->field("cash_use,identity,realname")->where("id=".$this->_userid)->find();
		$this->assign("cash_use",$cash_user["cash_use"]);
		if (!empty($cash_user['realname']) && $cash_user['identity'] != 0)
		{
			$this->assign('identity',$cash_user['identity']);
			$this->assign('realname',$cash_user['realname']);
		}
		/**/
		$this->assign("user",$this->_userid);//加上userid方便判断是游客还是会员
		$this->assign('default_address',$default_address);
		$this->assign('payment',$payment);
		$this->assign('user_address',$user_address);
		$this->assign('Area',$Area);
		$this->assign('shipping',$shipping);

		if($_REQUEST['do']){
			$this->assign('buy',2);
		}

	    $this->display();
	}

	public function _before_insert(){
		$_POST['ip'] = get_client_ip();
	}

	public function ajax(){ 
		$id = intval($_REQUEST['id']);
		$num = intval($_REQUEST['num']);		
		$module =  $this->module[$_REQUEST['moduleid']]['name'];
		if (!empty($_POST['attribute_key']))
		$attribute_key = $_POST['attribute_key']; //产品属性键值
	
		if (!$this->_userid)
		{
			$res['data']= 0 ;
			$res['status']= 202 ;
			$res['info']="请先登录！";
			$res['link']=U('User/Login/index',array('goodid'=>$id));
			echo json_encode($res); exit;
		}

		/*if (!$this->_realname)
		{
			$res['data']= 0 ;
			$res['status']= 666;
			$res['info']="请先注册！";
			$res['link']=U('User/Register/index',array('goodid'=>$id));
			echo json_encode($res); exit;
		}*/

		$do = $_REQUEST['do']; 
			
		if($do=='add'){
			$session_info = M('Online')->where(array("userid"=>$this->_userid))->find();
			$r = M($module)->find($id);
			$data=array();

			if (!empty($r))
			{
				//计算运费
				$shipping_fee = $r['fee_price']+$r['oversea_freight']+$r['country_freight']+$r['pack_freight'];
				$data['shipping_fee'] = number_format($shipping_fee,2);	//邮费

				$product_property = M('Product_property')->where('product_oversea_id='.$id)->select();
				//获取产品属性所对应的产品库存价格等信息
				if (!empty($product_property)){
					$propertyInfo = array();
					foreach ($product_property as $ki => $vi) {
						$extend_id_arr = unserialize($vi['attribute_group']);
						$extend_id_key = implode('_', $extend_id_arr);
						if ($extend_id_key == $attribute_key)
						{
							$data['goods_attr_id']=$product_property['property_id'];
							$r['price'] = $vi['price'];
							$r['member_price'] = $vi['member_price'];
							$r['stock'] = $vi['stock'];
							break;
						}
					}
				}
				
			}

			/*根据拍卖跟直购只有金会员有权限*/
				if($r['catid']==6||$r['catid']==7){
					$u=M('user')->field("groupid")->where('id='.$this->_userid)->find();
					if($u['groupid']<4||$u['groupid']>13){
						$res['data']= 0 ;
						$res['status']= 101 ;
						$res['info']="此商品只有金会员才能购买，赶紧注册成为金会员呗";
						echo json_encode($res); exit;
					}
				}
			/**/
			$cartWhere['product_id'] = $id;
			$cartWhere['userid'] = $this->_userid;

			$cart = $this->dao->where($cartWhere)->find();
			if($cart){
				$cart['number']=$cart['number']+$num;
			/*判断是否为0*/
				if(intval($cart['number'])<1){
						$res['data']= 0 ;
						$res['info']="购买数量不低于1";
						$res['maxcount']=1;
						echo json_encode($res); exit;
				}
			/**/
				//判断商品是否限购，购买数量是否大于限购数量
			/*	if($r['single_buy'] && intval($cart['number'])>intval($r['single_buy'])){
						$res['data']= 0 ;
						$res['info']="购买数量已超过限购数量";
						echo json_encode($res); exit;
				}*/
			/*比对商品库存 by dension start*/
				if(intval($cart['number'])>intval($r['stock'])){
						$res['data']= 0 ;
						$res['info']="库存不足";
						$res['maxcount']=$r['stock'];
						echo json_encode($res); exit;
				}
			/*end*/
				$cart['price'] = $cart['product_price']*$cart['number'];
				//计算商品行邮税
				if ($r['post_rate']>0)
				$direct_shipping = ($cart['price']*$r['post_rate'])/100;
				else
				$direct_shipping = $r['post_price']*$num;

				$cart['direct_shipping'] = $direct_shipping;
				$rs = $this->dao->save($cart);
			}else{
				/*判断商品是否已经下架*/
				if(!$r['status']){
					$res['data']= 0 ;
					$res['info']="此商品已经下架";
					echo json_encode($res); exit;
				}
				/**/
				/*判断是否为0*/
				if($num<1){
						$res['data']= 0 ;
						$res['info']="购买数量不低于1";
						$res['kucun']=1;
						$res['maxcount']=1;
						echo json_encode($res); exit;
				}/*elseif($r['single_buy'] && $num>intval($r['single_buy'])){
				//判断商品是否限购，购买数量是否大于限购数量
						$res['data']= 0 ;
						$res['info']="购买数量已超过限购数量";
						echo json_encode($res); exit;
				}*/
				/*比对商品库存 by dension start*/
				if($num>intval($r['stock'])){
						$res['data']= 0 ;
						$res['info']="库存不足";
						$res['maxcount']=$r['stock'];
						echo json_encode($res); exit;
				}
			/*end*/
				
				$group_id = M('user')->field("groupid")->where("id=".$this->_userid)->find();
				//获取会员组别
				$data['userid'] = $this->_userid;
				$data['sessionid'] = $this->_userid;
				$data['product_id'] = $r['id'];
				$data['product_thumb'] = $r['thumb'];
				$data['product_url'] = $r['url'];
				$data['product_name'] = $r['title'];
				$data['menber_rebate'] = $r['menber_rebate'];
				$data['parent_rebate'] = $r['parent_rebate'];
				/*商品价格根据商品类型跟会员类别判定 by dension*/

				switch ($r['catid']) {
					case "5":
						{
							switch ($group_id['groupid']) {
								case "3":
									$data['product_price'] = $r['member_price'];
									$data['ratio'] =0;
									break;
								case "4|6|8|9|10|11|12|13":
									$data['product_price'] = $r['gold_price'];
									$data['ratio'] =0;
									break;
								default:
									$data['product_price'] = $r['member_price'];
									break;
							}
						}break;
					case "4":
						{
							switch ($group_id['groupid']) {
								case "4":
									$data['product_price'] = $r['gold_price'];
									$data['ratio'] =0;
									break;
								default:
									$data['product_price'] = $r['price'];
									break;
							}
						}break;
					case "25":
						$data['product_price'] =$r['member_price'];
						$data['is_private'] =1;
						break;
					default:
						$data['product_price'] =$r['member_price'];
						break;
				}
				/*end*/
				$data['moduleid']=intval($_REQUEST['moduleid']);	
				$data['number'] = $num;
				$data['price'] = $data['product_price']*$data['number'];
				//计算商品行邮税
				if ($r['post_rate']>0)
				$direct_shipping = ($data['price']*$r['post_rate'])/100;
				else
				$direct_shipping = $r['post_price']*$num;

				$data['direct_shipping'] = $direct_shipping;
				if ($data['price'] == 0)
				{
					$res['data']= 0 ;
					$res['info']="此商品为非卖品";
					echo json_encode($res); exit;
				}
				$rs = $this->dao->add($data);
			}
			$res['data']= $rs ? 1 : 0 ;
			$res['info']= $rs ? '成功加入购物车！' : '参数错误！' ;
			$res['url']= U('Home/Order/checkout');
			echo json_encode($res); exit;		
		}elseif($do=='update'){
			$data = $this->dao->find($id);
			/*判断是否为0*/
			if($num<1){
				$res['data']= 0 ;
				$res['info']="购买数量不低于1";
				$res['kucun']=1;
				$res['maxcount']=1;
				echo json_encode($res); exit;
			}
			/**/

			/*比对商品库存 by dension start*/
			$product = M($module)->field("price,stock,status,post_rate,post_price")->where("id=".$data['product_id'])->find();
			if($num>intval($product['stock'])){
				$res['data']= 0 ;
				$res['info']="库存不足";
				$res['kucun']=intval($product['stock']);
				$res['maxcount']=$product['stock'];
				echo json_encode($res); exit;
			}
			/*end*/
			
			/*判断商品是否已经下架*/
			if(!$product['status']){
				$res['data']= 0 ;
				$res['info']="此商品已经下架";
				$res['kucun']=intval($product['single_buy']);
				echo json_encode($res); exit;
			}
			/**/

			$data['number'] = $num;
			$data['price'] = $data['product_price']*$data['number'];
			//计算商品行邮税
			if ($product['post_rate']>0)
			$data['direct_shipping'] = ($data['price']*$product['post_rate'])/100;
			else
			$data['direct_shipping'] = $product['post_price']*$num;
			if ($data['price'] == 0)
			{
				$res['data'] = 0 ;
				$res['info'] = "此商品为非卖品";
				$res['price'] = intval($data['price']);
				echo json_encode($res); exit;
			}
			$rs = $this->dao->save($data);

			$res['data']= $rs ? 1 : 0 ;
			echo json_encode($res); exit;
		}elseif($do=='del'){
			$rs = $this->dao->delete($id);
			$res['data']= $rs ? 1 : 0 ;
			if(!$res['data']){
			$res['info']="删除失败";
			}
			echo json_encode($res); exit;
		} 
 
	}

	public function doQuery()
	{
		if (!$this->_userid) $this->ajaxReturn(NULL,"请先登录！",0);
		$userInfo = M('User')->field('realname,wechat_name,email,mobile')->find($this->_userid);
		if (!intval($_POST['amount'])) $this->ajaxReturn(NULL,"请填写正确的金额！",0);
		/*支付方式*/
		$pay_where['pay_code'] = 'Allinpay'; // 通联支付
		$pay_where['status'] = 1; 
		$Payment = M('Payment')->where($pay_where)->find();
		$model = M("wechat_order");
		$receipt_where['status'] = 0;
		$receipt_where['type'] = $_POST['type'];
		$receipt_where['userid'] = $this->_userid;
		$receipt_order = $model->where($receipt_where)->find();
		if(!empty($receipt_order)){
			$receipt_order['createtime']=mktime();
			$receipt_order['amount'] = $_POST['amount'];
			$model->save($receipt_order);
			if ($_POST['type_pay']==3)
			{
				$alipay_button = $this->getalipay("Alipay",$receipt_order);
				$this->ajaxReturn($alipay_button,true,1);
			}
			elseif($_POST['type_pay']==1)
			{
				$pay_button = $this->get_qrcode($receipt_order['id'],$receipt_order['type']);
				$this->ajaxReturn($pay_button,true,1);
			}
			elseif($_POST['type_pay']==12)
			{
				$receipt_order['realname'] = $userInfo['realname'];
				$receipt_order['wechat_name'] = $userInfo['wechat_name'];
				$receipt_order['email'] = $userInfo['email'];
				$receipt_order['goods_number'] = 1;
				$receipt_order['mobile'] = $userInfo['mobile'];
				$receipt_order['allinipay_amount'] = $receipt_order['amount'];
				$receipt_order['add_time'] = $receipt_order['createtime'];
				$receipt_order['end_time'] = strtotime("+1 week");
				$payInfo = $this->getAllinpay($Payment['pay_code'],$receipt_order,$Payment);//获取到支付信息

				if ($payInfo['gateway_url'])
				{
					$pay_acturl = $payInfo['gateway_url'];
					unset($payInfo['gateway_url']);
				}
				if ($payInfo['gateway_method'])
				{
					$gateway_method = $payInfo['gateway_method'];
					unset($payInfo['gateway_method']);
				}
				unset($payInfo['pay_config']);
				$html = '';
				$html .= '<form name="form2" action="'.$pay_acturl.'" method="'.$gateway_method.'">';
				foreach ($payInfo as $key => $val) {
					$html .= '<input type="hidden" name="'.$key.'" id="'.$key.'" value="'.$val.'" />';
				}

				$html .= '<button type="submit" class="btn btn-primary col-xs-3 beijing2">进入支付</button>';
				$html .= '</form>';

				$this->ajaxReturn($html,true,1);
			}
		}else{
			//生成订单
			$order['userid'] = $this->_userid;
			$order['amount'] = sprintf("%.2f",intval($_POST['amount']));
			$order['status'] = 0;
			$order['type'] = $_POST['type'];
			$order['createtime'] = mktime();
			$orderid=$model->add($order);
			if($orderid){
				$order['sn'] = date("Ymd"). sprintf('%06d',$orderid);
				$model->save(array('id'=>$orderid,'sn'=>$order['sn'])); 
				if ($_POST['type_pay']==3)
				{
					$alipay_button = $this->getalipay("Alipay",$order);
					$this->ajaxReturn($alipay_button,true,1);
				}
				elseif($_POST['type_pay']==1)
				{
					$pay_button = $this->get_qrcode($orderid,$_POST['type']);
					$this->ajaxReturn($pay_button,true,1);
				}
				elseif($_POST['type_pay']==12)
				{
					$order['realname'] = $userInfo['realname'];
					$order['wechat_name'] = $userInfo['wechat_name'];
					$order['email'] = $userInfo['email'];
					$order['goods_number'] = 1;
					$order['mobile'] = $userInfo['mobile'];
					$order['allinipay_amount'] = $order['amount'];
					$order['add_time'] = $order['createtime'];
					$order['end_time'] = strtotime("+1 week");
					$payInfo = $this->getAllinpay($Payment['pay_code'],$order,$Payment);//获取到支付信息
					if ($payInfo['gateway_url'])
					{
						$pay_acturl = $payInfo['gateway_url'];
						unset($payInfo['gateway_url']);
					}
					if ($payInfo['gateway_method'])
					{
						$gateway_method = $payInfo['gateway_method'];
						unset($payInfo['gateway_method']);
					}
					unset($payInfo['pay_config']);
					$html = '';
					$html .= '<form name="form2" action="'.$pay_acturl.'" method="'.$gateway_method.'">';
					foreach ($payInfo as $key => $val) {
						$html .= '<input type="hidden" name="'.$key.'" id="'.$key.'" value="'.$val.'" />';
					}

					$html .= '<button type="submit" class="btn btn-primary col-xs-3 beijing2">进入支付</button>';
					$html .= '</form>';
					$this->ajaxReturn($html,true,1);
				}
			}else{
				$this->ajaxReturn(NULL,$model->getLastSql(),0);
			}
		}
	}

	public function deleteAll()
	{
		$rs = $this->dao->delete($_POST['datas']);
		if ($rs === false)
		{
			$st = 0;
			$info = "系统出错！";
		}
		else if ($rs == 0)
		{
			$st = 0;
			$info = "数据不存在！";
		}
		else{
			$st = 1;
			$info = "删除成功！";
		}
		$this->ajaxReturn($rs,$info,$st);
	}

	public function clear(){
		$this->dao->where("userid = '$this->_userid'")->delete();
		$this->error ( L('do_ok'));
	}

	public function done(){
		if($this->Config['isuserbuy'] && empty($this->_userid))$this->error ( L('NOLOGIN'));
		$model = M('Order');
		$userid = intval($this->_userid);
		if($userid){
			$user = M('User')->find($userid);
			if (!$user)
			{ 
				$this->assign('jumpUrl',URL('User-Login/index'));
				$this->error ( L('NOLOGIN'));
			}
			elseif (empty($user['wechat_name']) && $user['wechat_name'] == '{')
			{
				$reurl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
				$gh = M('wechat')->field('id,gh_id,appId,appSecret')->find();
				import ( '@.ORG.MP' );
				$this->mp = new MP($gh['appId'],$gh['appSecret']);
				$wechat_info = $this->mp->mpAuth($reurl,"snsapi_userinfo");
				if ($wechat_info)
				M('User')->where(array("id"=>$userid))->data(array("wechat_name"=>$wechat_info['nickname']))->save();

			}
		}

		/*获取当前已下订单*/
		/*$date_str = date('Y-m-d H:i:s',time());
		$date_start = preg_replace("/\d\d:\d\d:\d\d/",'00:00:00',$date_str);
		$date_end = preg_replace("/\d\d:\d\d:\d\d/",'23:59:59',$date_str);
		$date_start = strtotime($date_start);
		$date_end = strtotime($date_end);
		$orderwhere['add_time'] = array(array('gt',$date_start),array('lt',$date_end));
		$orderwhere['userid'] = $this->_userid;
		$orderwhere['status'] = array('not in','3');
		$nowOrder = $model->field('sn')->where($orderwhere)->select();
		switch ($this->_userid) {
			case 1196:case 1167:case 1224:case 1186:
				break;
			default:
				if (!empty($nowOrder))
				{
					$this->error('每天只能下单一次！');exit;
				}
				break;
		}*/

		/* 检查购物车中是否有商品 */
		$cart_count = $this->dao->where("userid = '$this->_userid'")->count();
	
		if ($cart_count == 0) $this->error ( L('ORDER_NO_PRODUCT'));


		/* 检查收货人信息是否完整 by dension*/
		if($this->_userid){
			if($_POST['addressid']){
				$address = M('User_address')->where("id=".intval($_POST['addressid']))->find();
			}else{
				$address = unserialize($_COOKIE['YP_guest_address']);
			}
			if(!$address['province'] || !$address['address'] || !$address['consignee'] || !$address['mobile']){
				$this->assign('jumpUrl',URL('Home-Order/checkout'));
				$this->error ( L('SHIPPING_ADDRESS_NO_FULL'));
			}
		}else{
			if(!$_POST['province'] || !$_POST['city'] || !$_POST['area'] || !$_POST['address'] || !$_POST['consignee'] || !$_POST['mobile']){
				$this->assign('jumpUrl',URL('Home-Order/checkout'));
				$this->error ( L('SHIPPING_ADDRESS_NO_FULL'));
			}
		}
		/*end*/
		$order=array();
		/*商品金额*/
		$cart = $this->dao->field(" `qq_cart`.* ")->join(" `qq_product_oversea` on `qq_product_oversea`.id=`qq_cart`.product_id ")->where(" `qq_cart`.userid='{$this->_userid}' and `qq_product_oversea`.catid!=25")->select();//只取商城商品

		/*配送方式-默认选择第一种配送方式 by dension*/
		$shippingid= 1;
		$Shipping = M('Shipping')->find($shippingid);
		if(!empty($cart)){
			$amount=0;
			$goods_number=0;
			$rebate_fee=0;
			$shipping_fee=0;
			foreach($cart as $key=>$r){

				$amount = $amount+$r['price'];
				//计算商品数量再计算邮费 by dension
				$goods_number=$goods_number+intval($r['number']);
				$shipping_fee += $r[shipping_fee]*$goods_number;
				$order['shipping_fee'] = number_format($shipping_fee,2);	//邮费
				/*此处计算返还电子现金 by dension*/
				$rebate_fee+=intval($r['number'])*floatval($r['ratio']);
				/*计算直购产品行邮税*/
				$direct_fee += floatval($r['direct_shipping']);
			}

			/*判断当前订单行邮税  > 50 ？*/
			if ($direct_fee<=50) $direct_fee = 0;

			if ($goods_number>1 && $amount>1000)
			{
				$this->error ("海关规定购买多件商品的总价不能超过￥1000元，请您分次购买。");exit;
			}
	
			/*保价*/
			if(intval($_POST['isinsure'])){ 
				$insure_fee = $amount*$Shipping['insure_fee']/100;
				$insure_fee =  number_format($insure_fee,2);
				if($insure_fee<=$Shipping['insure_low_price']) $insure_fee=$Shipping['insure_low_price']; 
				$order['insure_fee'] = $insure_fee;
			}

			/*支付方式*/
			$pay_code=$_POST['pay_code']; //默认选中通联支付
			if ($_POST['cash_pay']==1)
				$pay_code = 'Cash_pay'; // 电子现金支付

			$Payment = M('Payment')->where(array('pay_code'=>$pay_code,'status'=>1))->find();

			/*发票*/
			//$order['invoice_title']= htmlspecialchars($_POST['invoice_title']);
			$order['invoice'] = intval($_POST['invoice']);
			/*if($order['invoice']){			
				$order['invoice_title']= htmlspecialchars($_POST['invoice_title']);
				$order['invoice_fee'] = $amount*$_POST['invoice_fee']/100;
				$order['invoice_fee'] =  number_format($order['invoice_fee'],2);
			}*/

			if (!empty($user['identity_name']))
			$order['identity_name'] = $user['realname']; //身份证姓名
			else
			$order['identity_name'] = $_POST['identity_name'];

			if (!empty($user['identity']))
			$order['identity'] = $user['identity']; //身份证
			else
			$order['identity'] = $_POST['identity'];
			
			$order['amount'] = $amount;

			$order['rebate_fee']=$rebate_fee;//返还金额
			$order['direct_total']=$direct_fee;//直购商品行邮税
			$order['order_amount'] = $order['amount']+$order['insure_fee']+$order['shipping_fee']+$order['direct_total'];
		}

		/*发票*/
		/*if($Payment['pay_fee']){
			$order['pay_fee'] = $Payment['pay_fee_type'] ?  $Payment['pay_fee'] : $order['order_amount']*$Payment['pay_fee']/100;
			$order['pay_fee'] = number_format($order['pay_fee'],2);
		}
		$order['order_amount'] = $order['order_amount']+$order['pay_fee'];*/
		$order['userid'] = intval($userid);
		$order['status'] = 0;
		$order['pay_status']= 0;
		$order['shipping_status']= 0;

		$order['consignee'] = $address['consignee'] ? $address['consignee'] : $_POST['consignee'];
		$order['country'] =  $address['country'] ? intval($address['country']):intval($_POST['country']);
		$order['province']  = $address['province']? intval($address['province']):intval($_POST['province']);
		$order['city'] = $address['city']? intval($address['city']):intval($_POST['city']);
		$order['area'] = $address['area']? intval($address['area']):intval($_POST['area']);
		$order['address'] =  $address['address'] ? $address['address'] : $_POST['address'];
		//$order['zipcode'] =  $address['zipcode'] ? $address['zipcode'] : $_POST['zipcode'];
		$order['tel'] =  $address['tel'] ? $address['tel'] : $_POST['tel'];
		$order['mobile'] =  $address['mobile'] ? $address['mobile'] :  $_POST['mobile'];
		//$order['email'] =  $address['email'] ? $address['email'] :  $_POST['email'];

		$order['shipping_id'] =  intval($Shipping['id']);
		//$order['shipping_name'] =  $Shipping['name'] ?  $Shipping['name'] : '';
		$order['shipping_name'] = '';
 
		$order['pay_id'] =  intval($Payment['id']);
		$order['pay_name'] =  $Payment['pay_name'] ? $Payment['pay_name'] : '';
		$order['pay_code'] =  $Payment['pay_code'] ? $Payment['pay_code'] : '';
		$order['postmessage'] =  htmlspecialchars($_POST['postmessage']);

		##获取区域管理信息##
		$area_where['agentarea'] = $order['province'].','.$order['city'].','.$order['area'];
		// $area_where['userId'] = $this->_userid;
		$agentarea = M('Agentarea')->where($area_where)->find();
	
		/*添加商家id by dension*/
		$order['shop_id'] = $this->shop_id;
		$shop_parent=M("user")->field("parent_id")->where("id=".$this->shop_id)->find();
		$order['parent_shopid']=$shop_parent['parent_id']? $shop_parent['parent_id']:0;
		$order['add_time'] =  time();
		$order['end_time'] = strtotime("+30 minutes");
		foreach($order as $key=>$r){if($r==null)$order[$key]='';}

		/*购买会员信息*/
		$order['realname'] = $user['realname'];
		$order['username'] = $user['username'];
		$order['wechat_name'] = $user['wechat_name'];
		$order['email'] = $user['email'];
		$order['mobile'] = $user['mobile'];

		/*通联支付跟货到付款，电子现金金额 by dension*/
		$temp_amount=floatval($order['order_amount']);
		$user_cash=M("user")->field("cash_use")->where("id=".$this->_userid)->find();
		if($_POST['cash_pay']==1 && floatval($_POST['cash_pay_amount'])<=0){
				//如果提交电子现金金额低于0，则提交失败
				$this->error("抱歉,若选择电子现金支付请输入支付金额");exit();
		}
		if($_POST['cash_pay']==1 && floatval($_POST['cash_pay_amount'])>floatval($user_cash['cash_use'])){
				//如果提交电子现金金额低于实际剩余金额，则提交失败
				$this->error("抱歉，您现有的电子现金为￥".$user_cash['cash_use'].",不足￥".$_POST['cash_pay_amount']."。");exit();
		}
		if($_POST['cash_pay']==1 && $temp_amount>0){
			if(floatval($_POST['cash_pay_amount'])>$temp_amount)
			{
				//如果出现商城与私人酒窖都用电子现金
				$order['cash_coupon']=$temp_amount;
				$_POST['cash_pay_amount']=floatval($_POST['cash_pay_amount'])-$temp_amount;//减去商城总额就是私人酒窖可以用的电子现金
			}else{
				$order['cash_coupon']=floatval($_POST['cash_pay_amount']);
				$_POST['cash_pay_amount']=0;//这样私人酒窖时可以判断是否还有现金可以支付
			}
			$temp_amount=$temp_amount-$order['cash_coupon'];
		}

		$order['cod_amount']=0;
		$order['allinipay_amount']=$temp_amount;
		$order['wechat_amount']=$temp_amount;
		$order['type'] = 0;
		/**/
		if($cart){
			$orderid= $model->add($order);
		}
		if($orderid){
			$order['id']=$orderid;
			$order['sn'] = date("Ymd"). sprintf('%06d',$orderid); 
			$model->save(array('id'=>$orderid,'sn'=>$order['sn']));
			foreach($cart as $key=>$r){
				$cart[$key]['order_id'] = $orderid;
				$cart[$key]['userid'] = $userid;
				unset($cart[$key]['id']);
				$area_com = floatval(($agentarea['commission']*$r['product_price']*$r['number'])/100);
				$cart[$key]['area_com'] = $area_com;
				$cart[$key]['agentarea'] = $area_where['agentarea'];
				M('Order_data')->add($cart[$key]);
				if(!empty($r['goods_attr_id']))
					M("ProductProperty")->where("property_id=".$r['goods_attr_id'])->setDec('stock',$r['number']);//扣库存
				else
					M("ProductOversea")->where("id=".$r['product_id'])->setDec('stock',$r['number']);//扣库存
			}
		}
		
		/*添加完商城订单后开始生成私人酒窖订单 by dension*/
		$cart_private = $this->dao->field(" `qq_cart`.* ")->join(" `qq_product_oversea` on `qq_product_oversea`.id=`qq_cart`.product_id ")->where(" `qq_cart`.userid='{$this->_userid}' and `qq_product_oversea`.catid=25")->select();//取私人酒窖商品
		if($cart_private){
			$order_private=$order;
			unset($order_private['sn']);//消除订单号
			unset($order_private['id']);//消除id
			$amount_private=0;
			$privete_number=0;
			foreach($cart_private as $key=>$r){
				$amount_private = $amount_private+$r['price'];
				//计算商品数量再计算邮费 by dension
				$privete_number=$privete_number+intval($r['number']);
			}
			
			$order_private['pay_id'] =  intval($Payment['id']);
			$order_private['pay_name'] =  $Payment['pay_name'] ? $private_Payment['pay_name'] : '';
			$order_private['pay_code'] =  $Payment['pay_code'] ? $private_Payment['pay_code'] : '';
			$order_private['amount'] = $amount_private;
			$order_private['rebate_fee'] =0;//返还
			$order['direct_total']=0;//行邮税
			$order_private['is_private'] =1;//私人酒窖标记
			$order_private['shipping_fee']=0;//运费变为0
			//买单瓶需加邮费
			$shipping_fee = $r['shipping_fee']*$privete_number;
			$order_private['shipping_fee'] = number_format($shipping_fee,2);	
			$order_private['order_amount'] = $order_private['amount']+$order_private['shipping_fee'];
			foreach($order_private as $key=>$r){
				if($r==null)$order_private[$key]='';
			}
			/*微支付跟货到付款金额 by dension*/
			$temp_amount=floatval($order_private['order_amount']);
			if($_POST['cash_pay']==1  && $_POST['cash_pay_amount']>0){
				if(floatval($_POST['cash_pay_amount'])>$temp_amount){
				//如果出现这种情况就直接用订单总额为电子现金支付
					$order_private['cash_coupon']=$temp_amount;
					$_POST['cash_pay_amount']=floatval($_POST['cash_pay_amount'])-$temp_amount;//减去商城总额就是私人酒窖可以用的电子现金
				}else{
					$order_private['cash_coupon']=floatval($_POST['cash_pay_amount']);
				}
				$temp_amount=$temp_amount-$order_private['cash_coupon'];
			}

			/**/
			$order_private['cod_amount']=0;
			$order_private['allinipay_amount']=$temp_amount;
			$order_private['type'] = 0;
			$order_privateid= $model->add($order_private);
		}

		/*增加私人酒窖商品详情*/
		if($order_privateid){
			$order_private['id']=$order_privateid;
			$order_private['sn'] = date("Ymd"). sprintf('%06d',$order_privateid); 
			$model->save(array('id'=>$order_privateid,'sn'=>$order_private['sn']));
			foreach($cart_private as $key=>$r){
				$cart_private[$key]['order_id']=$order_privateid;
				$cart_private[$key]['userid']=$userid;
				unset($cart_private[$key]['id']);
				M('Order_data')->add($cart_private[$key]);
				// M("product")->where("id=".$cart_private[$key]['product_id'])->setDec('stock',$cart_private[$key]['number']);//扣库存
			}
		}
		/*end*/	
		if($orderid){
			$this->dao->where("userid = '$this->_userid'  and is_private=0")->delete();
			//$order['order_amount']=$order['order_amount']+$order_private['order_amount'];
			if($order['pay_id']){
				if($_POST['cash_pay']==1){
					//电子现金支付	
					if( $order['order_amount']>0 && $order['cash_coupon'] <= $user_cash['cash_use']){
						//减用户余额
						$cash_con['id']=$this->_userid;
						$cash_con['cash_use']=floatval($user_cash['cash_use'])-floatval($order['cash_coupon']);
						if($cash_con["cash_use"]>=0){
							$r=M("user")->save($cash_con);
						}else{
							$this->error ( L('do_error'));
						}
						if($r){
							$this->put_consume($order['cash_coupon'],1,$this->_userid,2);//写入记录表
							$orderup['id'] = $orderid;
							// $orderup['status'] = 1;
							$orderup['cash_pay_status'] =1;
							// $orderup['pay_time']  =$order['pay_time']=time();
							if($order['cash_coupon'] > 0){
								// $orderup['pay_status'] =2;
								// $orderup['shipping_notify'] =$shipping_res['success']? 1:2;
								$model->save($orderup);
								//支付成功返还金额
								if(!empty($order['rebate_fee'])){
									$this->change_user_cash(floatval($order['rebate_fee']));
								}

								//发送新订单给物流
								/*$shipping_res=$this->post_shipping($order['sn']);
								if(!$shipping_res['success'])$this->put_shipping_error($orderid,"102",$shipping_res['message']);
						else{ $this->put_shipping_error($orderid,"101",$res['message']);}*/
							}
							else{$model->save($orderup);}
						}else{
							$this->error ( L('do_error'));
						}
						if($order['allinipay_amount']>0){
							/*支付方式*/
							$pay_code = 'Allinpay'; // 通联支付
							$Payment = M('Payment')->where(array('pay_code'=>$pay_code,'status'=>1))->find();
							/*通联支付*/
							$order['goods_number'] = $goods_number;
							$order['allinpayInfo']=$this->getAllinpay('Allinpay',$order,$Payment);//获取到支付信息
						}
						if($order['wechat_amount']>0){
							$this->get_qrcode($order);
						}
					}else{	
						/*支付方式*/
						$pay_code = 'Allinpay'; // 通联支付
						$Payment = M('Payment')->where(array('pay_code'=>$pay_code,'status'=>1))->find();				
						/*通联支付*/
						$order['goods_number'] = $goods_number;
						$order['allinpayInfo']=$this->getAllinpay('Allinpay',$order,$Payment);//获取到支付信息
						if($order['wechat_amount']>0){
							$this->get_qrcode($order);
						}
					}
					//电子现金支付end
				}else if($order['pay_code']=='Allinpay'){
					/*通联支付*/
					$order['goods_number'] = $goods_number;
					$order['allinpayInfo']=$this->getAllinpay('Allinpay',$order,$Payment);//获取到支付信息
					if($order['wechat_amount']>0){
						$this->get_qrcode($order);
					}
				}
			}
		}
		//私人酒窖支付
		if($order_private['pay_id'] && $order_privateid){ 
		$this->dao->where("userid = '$this->_userid' and is_private=1")->delete();

			if($order_private['pay_code']=='Cash_pay'){
			//电子现金支付	
				$user_cash=array();
				$user_cash=M("user")->field("cash_use")->where("id=".$this->_userid)->find();//重新查询
				if( $order_private['order_amount']>0 && $order_private['cash_coupon'] <= $user_cash['cash_use']){
					//减用户余额
					$cash_con['id']=$this->_userid;
					$cash_con['cash_use']=floatval($user_cash['cash_use'])-floatval($order_private['cash_coupon']);
					if($cash_con["cash_use"]>=0){
						$r=M("user")->save($cash_con);
					}else{
						$this->error ( L('do_error'));
					}
					if($r){
						$this->put_consume($order_private['cash_coupon'],1,$this->_userid,2);//写入记录表
						$orderup=array();
						$orderup['id'] = $order_privateid;
						$orderup['status'] = 1;
						$orderup['cash_pay_status'] =1;
						$orderup['pay_time'] =$order_private['pay_time']=time();
						if(empty($order_private['wechat_amount'])){
							//发送新订单给物流--私人酒窖不发送
							/*$shipping_res=$this->post_shipping($order_private['sn']);
						if(!$shipping_res['success'])$this->put_shipping_error($order_privateid,"102",$shipping_res['message']);
					else{ $this->put_shipping_error($order['id'],"101",$res['message']);}*/
							$orderup['pay_status'] =2;
							//$orderup['shipping_notify'] =$shipping_res? 1:2;
							$model->save($orderup);
						}else $model->save($orderup);
					}else{
						$this->error ( L('do_error'));
					}
					if($order_private['allinipay_amount']>0){
						/*支付方式*/
						$pay_code = 'Allinpay'; // 通联支付
						$Payment = M('Payment')->where(array('pay_code'=>$pay_code,'status'=>1))->find();
						/*通联支付*/
						$order_private[goods_number] = $privete_number;
						$order_private['allinpayInfo']=$this->getAllinpay('Allinpay',$order_private,$Payment);//获取到支付信息
					}
				}else{	
					/*支付方式*/
					$pay_code = 'Allinpay'; // 通联支付
					$Payment = M('Payment')->where(array('pay_code'=>$pay_code,'status'=>1))->find();				
					/*通联支付*/
					$order_private[goods_number] = $privete_number;
					$order_private['allinpayInfo']=$this->getAllinpay('Allinpay',$order_private,$Payment);//获取到支付信息
				}
				//电子现金支付end
			}else if($order_private['pay_code']=='Allinpay'){
				/*通联支付*/
				$order_private[goods_number] = $privete_number;
				$order_private['allinpayInfo']=$this->getAllinpay('Allinpay',$order_private,$Payment);//获取到支付信息
			}
		}
		//私人酒窖支付end

		
		if ($order['allinpayInfo']['gateway_url'])
		{
			$pay_acturl = $order['allinpayInfo']['gateway_url'];
			unset($order['allinpayInfo']['gateway_url_m']);
		}
		if ($order['allinpayInfo']['gateway_method'])
		{
			$gateway_method = $order['allinpayInfo']['gateway_method'];
			unset($order['allinpayInfo']['gateway_method']);
		}

		if ($order_private['allinpayInfo']['gateway_url'])
		{
			$pay_acturl = $order_private['allinpayInfo']['gateway_url'];
			unset($order_private['allinpayInfo']['gateway_url']);
		}
		if ($order_private['allinpayInfo']['gateway_method'])
		{
			$gateway_method = $order_private['allinpayInfo']['gateway_method'];
			unset($order_private['allinpayInfo']['gateway_method']);
		}

		unset($order['allinpayInfo']['pay_config']);
		unset($order_private['allinpayInfo']['pay_config']);
		$this->assign('order',$order);
		$this->assign('gateway_method',$gateway_method);
		$this->assign('pay_acturl',$pay_acturl);
		$this->assign('order_private',$order_private);
		$this->assign('cart',$cart);
		$this->assign('cart_private',$cart_private);
		$Area = M('Area')->getField('id,name');
		$this->assign('Area',$Area);
		$this->display();
	}

	public function change_user_cash($cash=0){
		$user_cash=M("user")->field("cash_use")->where("id=".$this->_userid)->find();
		$cash_con['id']=$this->_userid;
		$cash_con['cash_use']=floatval($user_cash['cash_use'])+floatval($cash);
		$r=M("user")->save($cash_con);
		if($r){//记录金钱来去
			$this->put_consume($cash,5,$this->_userid,2);
		}
		return true;
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
	public function post_shipping($sn=0){
			$method="PostOrder";//方法
			$Key="B49e7d57ca6643102dbec749ae8c1b6e";//加密串
			$ordernum=$sn;//订单号
			$type=0;//0为新增订单
			$curl_url="http://wms.hans-trans.com/wms/interface/QueryService.aspx?method=".$method."&key=".$Key."&ordernum=".$ordernum."&type=".$type;
			$curl_result = file_get_contents($curl_url);
			$res=json_decode($curl_result,TRUE);
			return $res;
	}
	function change_price(){
		if($_POST['productid']){
			$proid=intval($_POST['productid']);
			$uid=$this->_userid;
			$u=M('user')->field("groupid")->where('id='.$this->_userid)->find();
			$pro=M('product')->field("id,min_price,start_price")->where("id=".$proid." and status=1")->find();
			if($u['groupid']!=4){
				$data['status']=0;
				$data['info']="只有金会员才可以参与哦，赶紧升级成为金会员吧";
				echo json_encode($data);exit();
			}
			if(!$pro){
				$data['status']=0;
				$data['info']="商品已经下架，请密切留意我们下期活动";
				echo json_encode($data);exit();
			}
			if(!$uid){
				$data['status']=0;
				$data['info']="请先登录再参与我们活动";
				echo json_encode($data);exit();
			}
			$new_price=M('auction_price')->field('price')->where("productid=".$proid)->order("price desc")->find();
			$user_price=M("auction_price")->field("price")->where("productid=".$proid." and userid=".$uid)->order("price desc")->find();
			$data['status']=1;
			$data['can_price']=$new_price['price']?floatval($new_price['price'])+floatval($pro['min_price']):floatval($pro['start_price'])+floatval($pro['min_price']);
			$data['can_price']=$data['can_price'];
			$data['min_price']=$pro['min_price'];
			$data['new_price']=$new_price['price'];
			$data['user_price']=$new_price['price'];
			echo json_encode($data);exit();
		}else{
		$data['status']=0;
		$data['info']="喔噢~找不到商品";
		echo json_encode($data);exit();
		}
	}
	public function update_price(){
		if($_POST['productid'] && $_POST['price']){
		$proid=intval($_POST['productid']);
		$price=floatval($_POST['price']);
		$content=htmlspecialchars($_POST['content']);
		$uid=$this->_userid;
		$pro=M('product')->field("id,min_price,start_price")->where("id=".$proid." and status=1")->find();
			$u=M('user')->field("groupid")->where('id='.$this->_userid)->find();
			if($u['groupid']!=4){
				$data['status']=0;
				$data['info']="只有金会员才可以参与哦，赶紧升级成为金会员吧";
				echo json_encode($data);exit();
			}
			if(!$pro){
				$data['status']=0;
				$data['info']="商品已经下架，请密切留意我们下期活动";
				echo json_encode($data);exit();
			}
			if(!$uid){
				$data['status']=0;
				$data['info']="请先登录再参与我们活动";
				echo json_encode($data);exit();
			}
		$new_price=M('auction_price')->field('price,num')->where("productid=".$proid)->order("price desc")->find();
		if(floatval($new_price['price'])>$price){
			$data['status']=0;
			$data['info']="已经有人出更高价格了，快点超过TA，加油";
			echo json_encode($data);exit();
		}
		$condition['price']=$price;
		$condition['num']=$new_price['num'] ? intval($new_price['num'])+1:1;
		$condition['userid']=$uid;
		$condition['productid']=$proid;
		$condition['content']=$content;
		$condition['createtime']=time();
		$res=M('auction_price')->add($condition);
			if($res){
			$data=$condition;
			$data['status']=1;
			echo json_encode($data);exit();
			}else{
			$data['status']=0;
			$data['info']="出价失败了，请刷新重来";
			echo json_encode($data);exit();
			}
		}else{
		$data['status']=0;
		$data['info']="喔噢~找不到商品";
		echo json_encode($data);exit();
		}
	}
	public function get_order(){
		if($_REQUEST['method']=='QueryOrder' && $_REQUEST['Key']=='8nIeiNcOnARIPxnfg2bbJFPk5EA'){
			$order_sn=$_REQUEST['ordernum']?$_REQUEST['ordernum']:0;
			$order=M('order')->field('id,sn,shop_id,consignee,province,city,area,address,mobile,order_amount,wechat_amount,invoice_title')->where(array("sn"=>$order_sn))->find();//获取订单信息
			if($order){
				$res=array();
				$Area = M('Area')->getField('id,name');
				$order['province']=$Area[$order['province']];
				$order['city']=$Area[$order['city']];
				$order['area']=$Area[$order['area']];
				//获取订单商品信息
				$order_data=M('order_data')->field(" `qq_order_data`.product_name as name,`qq_order_data`.number as count,`qq_order_data`.product_price as itemprice,`qq_product`.good_sn as barcode ")->join(' `qq_product` on `qq_product`.id=`qq_order_data`.product_id ')->where(' `qq_order_data`.order_id='.$order['id'])->select();
				//获取商家二维码
				if(!$order['shop_id'])$order['shop_id']=1;
				$img_code=$this->get_shop_code($order['shop_id']);
				if(empty($img_code)){
					$this->put_shipping_error($order['id'],"103","物流方获取信息，但微信生成二维码图片失败");
					$res['success']=false;
					$res['message']="生成二维码图片失败";
					echo json_encode($res);exit();
				}
				//格式化价格单位
				foreach ($order_data as $key => $value) {
					$order_data[$key]['itemprice']=$value['itemprice']*100;
				}
				//开始将数据赋给返回数据数组
				$res['success']=true;
				$res['customer']=$order['consignee'] ? $order['consignee']:'';
				$res['contact']=$order['mobile'] ? $order['mobile']:'';
				$res['address']['province']=$order['province'] ? $order['province']:'';
				$res['address']['city']=$order['city'] ? $order['city']:'';
				$res['address']['area']=$order['area'] ? $order['area']:'';
				$res['address']['detail']=$order['address'] ? $order['address']:'';
				$res['invoice']=$order['invoice_title'] ? $order['invoice_title']:'';
				$res['payment']=$order['wechat_amount'] ? floatval($order['wechat_amount'])*100:0;//这是货到付款金额,以分计
				$res['code']=$img_code;//商家二维码
				$res['delivery']='';//送货时段，暂时未有
				$res['remark']='';//备注，暂时未有
				$res['product']=$order_data;//订单商品
				echo json_encode($res);exit();
			}else{
			$this->put_shipping_error($order['id'],"103","物流方获取信息，但订单号不对");
			$res['success']=false;
			$res['message']="订单号不对";
			echo json_encode($res);exit();
			}
		}else{
			$this->put_shipping_error($order['id'],"103","物流方获取信息，但格式不对");
			$res['success']=false;
			$res['message']="格式不对";
			echo json_encode($res);exit();
		}
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
		$str=json_encode($data);
		$str=urlencode($str);//将数据并成字符串
		$result=$this->lock($str,"dension");//加密数据字符串
		return $result;
	}
	/*获取商家二维码*/
	public function get_shop_code($shop_id=1){

		$shop=M("user")->field("id")->where("id=".$shop_id." and groupid between 6 and 13")->find();
		if(!$shop){
				$shop['id']=1;//如果没有默认为总公司	
		}
		$code=M("qrcode")->where("userid=".$shop['id'])->find();
		if(empty($code['ticket'])){
		/*当用户不存在二维码时生成二维码*/
		$gh = M('wechat')->field('id,gh_id,appId,appSecret')->where(array('uid'=>1,'status'=>1))->find();
/*		if(!isset($gh['appId']) || !isset($gh['appSecret'])){
			$res['success']=false;
			$res['message']="获取二维码失败，因为找不到公众号信息";
			echo json_encode($res);exit();
		}*/
		$this->gh_local_id = $gh['id'];
		$this->gh_id = $gh['gh_id'];
		$this->appId = $gh['appId'];
		$this->appSecret = $gh['appSecret'];
		$this->assign('gh',$gh);

		import ( '@.ORG.MP' );
		$this->mp = new MP($this->appId,$this->appSecret);
		$scene_id=$shop['id'];
		$data['action_name']="QR_LIMIT_SCENE";
		$data['action_info']['scene']['scene_id']=$scene_id;
		$json_data=json_encode($data);
		$res=$this->mp->create_code($scene_id);//返回生成参数
		$img=$this->get_code_img($res['ticket']);
/*		if(empty($img)){
			$res['success']=false;
			$res['message']="微信生成二维码图片失败";
			echo json_encode($res);exit();
		}*/
		$data['userid']=$shop['id'];
		$data['ticket']=$res['ticket'];
		$data['url']=$img;
		$data['createtime']=mktime();
		M("qrcode")->add($data);//插入数据库
		$code=array();
		$code=$data;
		$code['id']=$shop['id'];
		/*获取二维码*/
		}
		if(empty($code['url'])){
			$code['url']=$this->get_code_img($code['ticket']);
			M("qrcode")->save($code);
		}
		return "http://".$_SERVER['HTTP_HOST']."/".$code['url'];
	}	
	public function get_code_img($ticket){
		$url='https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.UrlEncode($ticket);//根据生成参数获取图片
		$return=$this->download_qrcode($url);//获取到图片
		$path="shop_qrcode/";
    if (!file_exists($path)) mkdir($path,0777);
		$filename="qr_".time().$shop['id'].".jpg";
		$fn=fopen($path.$filename,"w");
		if($fn!=false){
			fwrite($fn, $return['body']);//下载图片到本服务器
		}
		fclose($fn);
		return $path.$filename;
	}
public function download_qrcode($url){
		$curlhandle = curl_init();
		curl_setopt($curlhandle, CURLOPT_URL, $url);
		curl_setopt($curlhandle, CURLOPT_HEADER,0);
		curl_setopt($curlhandle, CURLOPT_NOBODY,0);
		curl_setopt($curlhandle, CURLOPT_SSL_VERIFYPEER, 0); //对认证证书来源的检查
		curl_setopt($curlhandle, CURLOPT_SSL_VERIFYHOST, 0); //从证书中检查SSL加密算法是否存在
		curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, 1); //获取的信息以文件流的形式返回
		$result = curl_exec($curlhandle);
		$info = curl_getinfo($curlhandle);
		curl_close($curlhandle);
		return array_merge(array('body'=>$result),array("header"=>$info));
}
	/**/
	/*end*/
	/*微支付 add by dension*/
	public function go_pay(){
		$id=$_REQUEST['id']?intval($_REQUEST['id']):0;
		$order=M('order')->field("sn,order_amount,consignee,wechat_amount")->where('id='.$id." and pay_status!=2 and userid=".$this->_userid)->find();
		if(!$order){
			$this->error('您没有该订单需要支付');exit();
		}
		$user_ip=$_SERVER['REMOTE_ADDR'];
		/*开始组装表单*/
		$data['order_name']=(string)$order['consignee']."的订单。";
		$data['order_sn']=(string)$order['sn'];
		$data['order_amount']=$order['wechat_amount'];//支付金额为微支付总额
		$data['user_ip']=(string)$user_ip;
		$url="http://".$_SERVER['HTTP_HOST']."/pay/jsapicall.php";
		$res=$this->cURLPost($url,$data);
		echo $res;
	}
	function cURLPost($url,$parameter,$header=array()){
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
		curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $parameter);//发送数据
		curl_setopt($curlhandle, CURLOPT_COOKIE, ''); //读取储存的Cookie信息
		curl_setopt($curlhandle, CURLOPT_TIMEOUT, 30); //设置超时限制防止死循环
		curl_setopt($curlhandle, CURLOPT_HEADER, 0); //显示返回的Header区域内容
		curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, 1); //获取的信息以文件流的形式返回
		$result = curl_exec($curlhandle);
		curl_close($curlhandle);
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