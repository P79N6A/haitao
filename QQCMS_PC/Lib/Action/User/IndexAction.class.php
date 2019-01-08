<?php
/**
 * 
 * User/IndexAction.class.php (前台会员中心模块)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if(!defined("QQCMS")) exit("Access Denied");
class IndexAction extends BaseAction
{

	function _initialize()
    {	
		parent::_initialize();
		if(!$this->_userid){
			/*$this->assign('jumpUrl',U('User/Login/index'));
			$this->error(L('nologin'));*/
			//header("location:".U('User/Login/index'));
			header("location:".U('Home/Index/index'));
		}

		/*查找上级*/
		$shop=M('user')->field("id,shop_name")->where('id='.$this->_shopid." and groupid between 6 and 13")->find();
 		$this->assign("shop_info",$shop);
		$this->dao = M('User');
		$this->assign('bcid',0);
		$user = $this->dao->find($this->_userid);
		$this->assign('vo',$user);
		unset($_POST['status']);
		unset($_POST['groupid']);
		unset($_POST['amount']);
		unset($_POST['point']);
    }

    public function index()
    {	
    	$this->check_shop();//检查是否有商家id
    	$count = $this->news_count();
    	if(!empty($count))$this->assign("news_count",$count);
    	$user = M("user")->field("cash_use,username,groupid")->where("id=".$this->_userid)->find();
    	$this->assign("the_u",$user);

    	$uid = $this->_userid;
    	if (IS_AJAX)
    	{
	        import ( "@.ORG.Page2" );
	        $order = M('order');
	        $join = 'qq_order_data as od ON o.id = od.order_id';
	        $field = 'o.*,od.product_thumb';
	    	switch ($_REQUEST['_status']) {
	    		case 1:
	    			$where['o.status'] = 0;
	    			$where['o.pay_status'] = 0;
	    			break;
	    		case 2:
	    			$where['o.pay_status'] = 2;
	    			break;
	    		default:
	    			break;
	    	}
	    	
	    	$where['o.userid'] = $uid;		
	        $count = $order->alias('o')->join($join)->where($where)->count();
	        $listRows = 10; 
	        $page = new Page ( $count, $listRows );
	        $pages = $page->show();
	        $_list = $order->alias('o')->field($field)->join($join)->where($where)->order('o.id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
	        $list = array();
	        foreach ($_list as $kt => $vt) {
	        	$list[$vt['sn']] = $vt;
	        }
	        $html = '';
	        foreach ($list as $key => $value) {
	        	$str_status = '详情';
	        	$classname = 'pay';
	        	$str_state = '';
	        	if ($value['status'] == 2)
	        	{
	        		$str_state = '订单完成';
	        	}elseif ($value['pay_status'] == 2){
	        		$str_status = '查看物流';
	        		$classname = 'check';
	        		$str_state = '已付款';
	        	}
	        	switch ($value['status']) {
	        		case 0:
	        			$str_status = '去支付';
	        			$str_state = '未确认';
	        			break;
	        		case 1:
	        			$str_status = '查看物流';
	        			$str_state = '已确认';
	        			break;
	        		case 2:
	        			$str_state = '己完成';
	        			break;
	        		case 3:
	        			$str_state = '作废订单';
	        			break;
	        		case 4:
	        			$str_state = '己退货';
	        			break;
	        		case 5:
	        			$str_state = '取消订单';
	        		case 6:
	        			$str_state = '订单已过期';
	        			break;
	        		default:
	        			break;
	        	}
	        	$value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
	        	$html .= '<dl class="order-item">';
	        	$html .= '<span class="pic">';
	        	$html .= '<a href="'.U('User/Order/show',array('id'=>$value['id'])).'" target="_blank">';
	        	$html .= '<img src="'.$value['product_thumb'].'" alt=""></a></span>';
	        	$html .= '<span class="time">';
	        	$html .= '订单号：<a href="'.U('User/Order/show',array('id'=>$value['id'])).'">'.$value['sn'].'</a>';
	        	$html .= '<p>'.$value['add_time'].'</p></span>';
	        	$html .= '<span class="state">订单状态：'.$str_state;
	        	$html .= '<a href="javascript:" style="display:block">&yen;'.money_format($value['order_amount'],2).'</a></span>';
	        	$html .= '<a href="'.U('User/Order/show',array('id'=>$value['id'])).'"><button class="'.$classname.'">'.$str_status.'</button></a></span></dl>';
	        }
	        if (!empty($pages))
	        {
	        	$html .= '<dl class="text-center">';
	        	$html .= '<ul class="pagination" style="margin-bottom:20px">';
	        	$html .= $pages;
	        	$html .= '</ul></dl>';
	        }
	        if ($html)
	        $this->ajaxReturn($html,'ok',1);
	    	else
	    	{
	    		$html .= '<dl class="order-item">';
	    		$html .= '<h1>空空如也(•́︿•̀)</h1>';
	    		$this->ajaxReturn($html,'fail',0);
	    	}
	    	
    	}



        // $this->assign("list",$list);
        // $this->assign('pages',$pages);

        /*首先搜索整站发信*/
		$news_all = array();
		$news_all = M('message_text')->field('id,title,little_title,createtime')->where("is_all=0")->select();
		$this->assign("news_all",$news_all);

		/*搜索个人的消息*/
		$news_single=array();
		$news_single=M('message_text')->field('`qq_message_text`.id,`qq_message_text`.title,`qq_message_text`.createtime,`qq_message_text`.little_title,`qq_message_user`.flat')->join(' `qq_message_user` on `qq_message_user`.message_id=`qq_message_text`.id')->where("`qq_message_text`.is_all=1 and `qq_message_user`.userid=".$uid)->select();
		/*end*/
		$this->assign("news_single",$news_single);
        $this->display();
    }
	
	public function profile()
    {	 
		if($_POST['dosubmit']){
			$_POST['id']=$this->_userid;
			if(!$this->dao->create($_POST)) {
				$this->error($this->dao->getError());
			}
			$this->dao->update_time = time();
			$this->dao->last_ip = get_client_ip();
			$result	=	$this->dao->save();
			if(false !== $result) {
				$this->success(L('do_success'));
			}else{
				$this->error(L('do_error'));
			}
			exit;
		}
        $this->display();
    }

	public function avatar()
    {	
		
		if($_POST['dosubmit']){
		
			$_POST['id']=$this->_userid;
			if(!$this->dao->create($_POST)) {
				$this->error($this->dao->getError());
			}
			$this->dao->update_time = time();
			$this->dao->last_ip = get_client_ip();
			$result	=	$this->dao->save();
			if(false !== $result) {
				if($_POST['aid']){
				$Attachment =M('Attachment');		
				$aids =  implode(',',$_POST['aid']);
				$data['userid']= $this->_userid;
				$data['catid']= 0;
				$data['status']= '1';
				$Attachment->where("aid in (".$aids.")")->save($data);
				}

				$this->success(L('do_success'));
			}else{
				$this->error(L('do_error'));
			}
			exit;
		}

		$qqcms_auth_key = sysmd5(C('ADMIN_ACCESS').$_SERVER['HTTP_USER_AGENT']);
		$qqcms_auth = authcode('0-1-0-1-jpeg,jpg,png,gif-3-0', 'ENCODE',$qqcms_auth_key);
		$this->assign('qqcms_auth',$qqcms_auth);
        $this->display();
    }

	public function password()
    {	 
		
		if($_POST['dosubmit']){

			if(md5($_POST['verify']) != $_SESSION['verify']) {
				$this->error(L('error_verify'));
			}
			if($_POST['password'] != $_POST['repassword']){
				$this->error(L('password_repassword'));
			}
			$map	=	array();
			$map['password']= sysmd5($_POST['oldpassword']);
			if(isset($this->_userid)) {
				$map['id']		=	$this->_userid;
			}elseif(isset($this->_username)) {
				$map['username']	 =	 $this->_username;
			}
			//检查用户
			if(!$this->dao->where($map)->field('id')->find()) {
				$this->error(L('error_oldpassword'));
			}else {
				$this->dao->email = $_POST['email'];
				$this->dao->id = $this->_userid;
				$this->dao->update_time = time();
				$this->dao->password	=	sysmd5($_POST['password']);
				$r = $this->dao->save();
				$this->assign('jumpUrl',U('User/Index/password'));
				if($r){
					$this->success(L('do_success'));
				}else{
					$this->error(L('do_error'));
				}
			 }
			 exit;
		}
		$this->display();
    }
	public function tuichu(){
		$this->assign("big_title","即将推出");
		$this->assign("small_title","敬请期待！");
		$this->display();
	}

	/*收货地址管理*/
	public function address(){
    $this->check_shop();//检查是否有商家id
		$default_address=M('user_address')->where("userid=".$this->_userid." and isdefault=1")->select();
		$address=M('user_address')->where("userid=".$this->_userid." and isdefault=0")->select();
		$area=M('area')->getfield("id,name");
		$this->assign('area',$area);
		$this->assign('default_address',$default_address);
		$this->assign('address',$address);
		$this->display("Index:address");
	}

	/*收货地址管理*/
	public function addresss(){
    $this->check_shop();//检查是否有商家id
		$default_address=M('user_address')->where("userid=".$this->_userid." and isdefault=1")->select();
		$address=M('user_address')->where("userid=".$this->_userid." and isdefault=0")->select();
		$area=M('area')->getfield("id,name");
		$this->assign('area',$area);
		$this->assign('default_address',$default_address);
		$this->assign('address',$address);
		$this->display();
	}

	public function editDefault()
	{
		$id = $_POST['id'];
		$data['id'] = $id;
		$data['isdefault'] = 1;
		$_address_ = M('user_address')->where(array('userid'=>$this->_userid,'isdefault'=>1))->save(array('isdefault'=>0));
		if ($_address === false) $this->ajaxReturn(NULL,"修改出错！",0);
		$_address = M('user_address')->save($data);
		if ($_address === false)
		{
			$this->ajaxReturn(NULL,"设置出错！",0);
		}
		$this->ajaxReturn($_address,"设置成功！",1);
	}

	public function edit_address(){
    $this->check_shop();//检查是否有商家id
		if($_REQUEST['id']){
			$id=intval($_REQUEST['id']);
			$address=M('user_address')->where("id=".$id)->find();
			$this->assign('address',$address);
		}
		$this->display("Index:edit_address");
	}

	public function delete_address(){
		if($_REQUEST['id']){
			$id=intval($_REQUEST['id']);
			M('user_address')->where("id=".$id)->delete();
			$this->address();
		}else{
			$this->address();
		}
	}
	/*站内信-我的消息*/
	public function news_count(){
		$uid=$this->_userid;
		$count=0;
		/*首先搜索整站发信*/
		$news_all=array();
			$news_all=M('message_text')->field('id')->where("is_all=0")->select();
			foreach ($news_all as $key => $value) {
				$temp=M('message_user')->field('flat')->where('message_id='.$value['id'].' and userid='.$uid)->find();
				if(!$temp){
					//如果不存在用户表，就创建
					$con['userid']=$uid;
					$con['message_id']=$value['id'];
					$con['flat']=0;
					$con['createtime']=time();
					$res=M('message_user')->add($con);
					if($res){
						$temp['flat']=$con['flat'];

					}
				}
				if($temp['flat']==0)$count++;
			}
		/*搜索整站的 end*/
		/*搜索个人的*/
		$news_single=M('message_text')->join(' `qq_message_user` on `qq_message_user`.message_id=`qq_message_text`.id')->where("`qq_message_text`.is_all=1 and `qq_message_user`.userid=".$uid)->count();
		/*end*/
		return $count+$news_single;
	}
	public function news_list(){
		$uid=$this->_userid;
		/*首先搜索整站发信*/
		$news_all=array();
		$news_all=M('message_text')->field('id,title,little_title,createtime')->where("is_all=0")->select();
		foreach ($news_all as $key => $value) {
			$temp = M('message_user')->field('flat')->where('message_id='.$value['id'].' and userid='.$uid)->find();
			if(!$temp){
				//如果不存在用户表，就创建
				$con['userid'] = $uid;
				$con['message_id'] = $value['id'];
				$con['flat'] = 0;
				$con['createtime'] = time();
				$res = M('message_user')->add($con);
				if($res){
					$temp['flat'] = $con['flat'];

				}
			}
			$news_all[$key] = array_merge_recursive($news_all[$key],$temp);
		}
		/*搜索整站的 end*/

		/*搜索个人的*/
		$news_single=array();
		$news_single=M('message_text')->field('`qq_message_text`.id,`qq_message_text`.title,`qq_message_text`.createtime,`qq_message_text`.little_title,`qq_message_user`.flat')->join(' `qq_message_user` on `qq_message_user`.message_id=`qq_message_text`.id')->where("`qq_message_text`.is_all=1 and `qq_message_user`.userid=".$uid)->select();
		/*end*/
		//$news_list=array_merge_recursive($news_all,$news_single);
		$this->assign("news_all",$news_all);
		$this->assign("news_single",$news_single);
		$this->display();
	}

	public function news(){
		$id=$_REQUEST['id']? intval($_REQUEST['id']):0;
		$news_id=M('message_text')->where('id='.$id)->find();
		if(!$news_id){$this->error("该信件不存在");exit();}
		$new_user=M('message_user')->field('id,flat')->where('userid='.$this->_userid." and message_id=".$news_id['id'])->find();
		if(!$new_user){
			$data['userid']=$this->_userid;
			$data['message_id']=$news_id['id'];
			$data['read_time']=time();
			$data['createtime']=time();
			$data['flat']=1;
			M('message_user')->add($data);
		}else{
			$data['id']=$new_user['id'];
			$data['flat']=1;
			$data['read_time']=time();
			M('message_user')->save($data);
		}
		$news_id['content']=htmlspecialchars($news_id['content']);
		$this->assign('info',$news_id);
		$this->display();
	}
	/*站内信 end*/
	//电子现金
	public function cash_use(){
        import ( "@.ORG.Page2" );
        $model=M('consume');
		$uid=$this->_userid;
		$count=$model->where("user_id=".$uid)->count();
        $listRows = 10; 
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
		$consume=$model->where("user_id=".$uid)->order('id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
		foreach ($consume as $key => $value) {
			//消费去向,1为购买产品，2为缴费，3为押金,4为充值,5为返现
			switch ($value['source']) {
				case '1':
				{ 
					switch ($value['pay_type']) {
						case '1':
						$consume[$key]['source']="现金消费";
							break;
						
						case '2':
						$consume[$key]['source']="电子现金消费";
							break;
						default:
							break;
					}
				$consume[$key]['cash']="&minus;".$value['cash'];
					break;
				}
				
				case '2':
				{ 
					switch ($value['level_flat']) {
						case '1':
						$consume[$key]['source']="系统免费升级";
							break;
						
						default:
						$consume[$key]['source']="缴纳年费";
							break;
					}
				$consume[$key]['cash']="&minus;".$value['cash'];
					break;
				}
				case '3':
				{ 
				$consume[$key]['cash']="&minus;".$value['cash'];
				switch ($value['pay_type']) {
						case '3':
						$consume[$key]['source']="系统赠送微店押金";
							break;
						default:
						$consume[$key]['source']="缴纳微店押金";
							break;
					}
					break;
				}
				case '4':
				{ 
				$consume[$key]['cash']="&#43;".$value['cash'];
				$consume[$key]['source']="充值";
					break;
				}
				case '5':
				{ 
				$consume[$key]['cash']="&#43;".$value['cash'];
				$consume[$key]['source']="消费返现";
					break;
				}
				default:
				case '6':
				{ 
				$consume[$key]['cash']="&minus;".$value['cash'];
				$consume[$key]['source']="缴纳平台管理费";
					break;
				}
				case '7':
				{ 
				$consume[$key]['cash']="&#43;".$value['cash'];
				$consume[$key]['source']="取消订单退还";
					break;
				}
				default:
				{ 
				$consume[$key]['source']="未知";
					break;
				}
			}
		}
		// print_r($consume);exit;
        $this->assign('pages',$pages);
		$this->assign("fee_list",$consume);
		$this->display();
	}
	//二维码
	public function code(){
		//header("location:qr/shop_code.php?userid=".$this->_userid);
		$url="http://".$_SERVER['HTTP_HOST']."/qr/shop_code.php?userid=".$this->_userid;
		$str=file_get_contents($url);
		$src="http://".$_SERVER['HTTP_HOST']."/qr/".$str;
		$this->assign("src",$src);
		$this->display();
	}
	//申请成为经营者
	public function beshop(){
		$this->assign("big_title","请在电脑浏览器上输入此链接地址进入申请页面：");
		$this->assign("small_title",U('Home/Beshop/index','id='.$this->_userid,"","",true));
		$this->display("Index:tuichu");
	}
	//我的收藏
	public function my_collect(){
        import ( "@.ORG.Page2" );
        $model=M("pro_collect");
		$count=$model->join(" `qq_product_oversea` on `qq_product_oversea`.id=`qq_pro_collect`.productid ")->where(" `qq_pro_collect`.userid=".$this->_userid." and `qq_product_oversea`.status=1 ")->count();
        $listRows = 5; 
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
		$list = $model->field(" `qq_product_oversea`.catid,`qq_product_oversea`.title,`qq_product_oversea`.thumb,`qq_product_oversea`.price,`qq_product_oversea`.url,`qq_product_oversea`.en_name,`qq_product_oversea`.member_price,`qq_pro_collect`.createtime,`qq_pro_collect`.productid,`qq_pro_collect`.userid")->join(" `qq_product_oversea` on `qq_product_oversea`.id=`qq_pro_collect`.productid ")->where(" `qq_pro_collect`.userid=".$this->_userid." and `qq_product_oversea`.status=1 ")->order(' `qq_pro_collect`.createtime desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign('pages',$pages);
        // print_r($list);exit;
        $this->assign('list',$list);
        $this->display();
	}

	//删除我的收藏
	public function deletecollect()
	{
		$productid = $_POST['productid'];
		$id = M("pro_collect")->where(array("userid"=>$this->_userid,"productid"=>$productid))->delete();
		if ($id === false)
		{
			$this->ajaxReturn(NULL,"系统出错！",0);
		}
		else if(!$id)
		{
			$this->ajaxReturn(NULL,"删除失败！",0);
		}
		$this->ajaxReturn($id,"删除成功！",1);
	}
}
?>