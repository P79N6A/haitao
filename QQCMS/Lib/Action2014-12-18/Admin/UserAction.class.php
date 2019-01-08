<?php
/**
 * 
 * User(会员管理文件)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
class UserAction extends AdminbaseAction {

    public $dao,$usergroup;
	function _initialize()
	{
		parent::_initialize();
		$this->dao = D('User');
		$this->usergroup=F('Role');
		$this->assign('usergroup',$this->usergroup);
		//var_dump(strtotime("2013-09-06"));exit();
		//var_dump(date('Y-m-d',1377619200));exit();
	}

	function _after_index(){
		$list=M('user')->field("id,parent_id,receipt,groupid,beshop_time,lastrecharge_time")->where('groupid between 2 and 5')->select();
		$list=$this->take_menber_status($list);//检查会员年费缴纳状态
		$shop=M('user')->field("id,parent_id,receipt,groupid,beshop_time,lastrecharge_time")->where('groupid between 5 and 14')->select();
		$list=$this->take_status($shop);//检查试运营与年费缴纳状态
	}
	function index(){
		import ( '@.ORG.Page' );
	
		$keyword=$_GET['keyword'];
		$searchtype=$_GET['searchtype'];
		$groupid =intval($_GET['groupid']);
		$this->assign($_GET);
		
		$user_group=M('role')->field('id,name')->where('id >2 and id<14')->select();
		$this->assign('role',$user_group);
		if(!empty($keyword) && !empty($searchtype)){
			$where[$searchtype]=array('like','%'.$keyword.'%');
		}
		if(!empty($_REQUEST['province'])){
			$where['province']=$_REQUEST['province'];
		}
		if(!empty($_REQUEST['city'])){
			$where['city']=$_REQUEST['city'];
		}
		if(!empty($_REQUEST['area'])){
			$where['area']=$_REQUEST['area'];
		}
		if($groupid)$where['groupid']=$groupid;
		$user=$this->dao;
		$count=$user->where($where)->count();
		$page=new Page($count,15);
		$show=$page->show();
		$this->assign("page",$show);
		$list=$user->order('id desc')->where($where)->limit($page->firstRow.','.$page->listRows)->select();
		$list=$this->get_info($list);//获取上级、管理年费、此处注意，此函数有获取历史押金记录，但押金改为用user表里的receipt字段记录
		if($groupid>5 && $groupid<14){

			$list=$this->get_fee($list);//获取自消费、客户消费以及3种返利和下级平台管理分润
				$list=$this->take_status($list);//检查试运营与年费缴纳状态
		}else{

			$list=$this->get_menber_fee($list);//获取消费金额
			$list=$this->take_menber_status($list);//检查会员年费缴纳状态

		}	
		$this->assign('ulist',$list);
		$this->display();
	}

	function insert(){
		$user=$this->dao;
		$_POST['password'] = sysmd5($_POST['pwd']);
		$_POST['createtime']=mktime();
		/*如果是新增微店则增加成立微店时间*/
		if($_POST['groupid']>5 && $_POST['groupid']<14){
			$_POST['beshop_time']=mktime();
		}
		if($data=$user->create()){
			if(false!==$user->add()){
				$uid=$user->getLastInsID();
				$ru['role_id']=$_POST['groupid'];
				$ru['user_id']=$uid;
				$roleuser=M('RoleUser');
				$roleuser->add($ru);			
				$this->success(L('add_ok'));
			}else{
				$this->error(L('add_error'));
			}
		}else{
			$this->error($user->getError());
		}
	}

	function update(){
		$user=$this->dao;
		$_POST['password'] = $_POST['pwd'] ? sysmd5($_POST['pwd']) : $_POST['opwd'];
		$_POST['updatetime']=mktime();
					/*会员级别检测*/
					$list[0]['id']=$_POST['id'];
					$list[0]['groupid']=$_POST['groupid'];
					$this->menber_level($list);
					/**/
		if($data=$user->create()){
			if(!empty($data['id'])){
				if(false!==$user->save()){
					$ru['user_id']=$_POST['id'];
					$ru['role_id']=$_POST['groupid'];
					$roleuser=M('RoleUser');
					$roleuser->where('user_id='.$_POST['id'])->delete();
					$roleuser->where('user_id='.$_POST['id'])->add($ru);
					$this->success(L('edit_ok'));
				}else{
					$this->error(L('edit_error').$user->getDbError());
				}
			}else{
				$this->error(L('do_error'));
			}
		}else{
			$this->error($user->getError());
		}
	}
 
	function add_menber(){

		$this->assign('rlist',$this->usergroup);
		$name = MODULE_NAME;
		$this->display ('edit_menber');
	}
	function _before_add(){
		$this->assign('rlist',$this->usergroup);	
	}

	function _before_edit(){
		$id=$_REQUEST['id']?$_REQUEST['id']:0;
		$user=M('user')->where("id=".$id)->find();//获取本用户信息
		$parent=M('user')->field("realname")->where("id=".$user['parent_id'])->find();//获取上级名字
		$province=M('area')->field("id,name")->where('id='.$user['province'])->find();
		$city=M('area')->field("id,name")->where('id='.$user['city'])->find();
		$area=M('area')->field("id,name")->where('id='.$user['area'])->find();
		$adress=array_merge_recursive($province,$city,$area);
		$shop_list=$this->get_next_shop($id);//获取下级微店列表
		$menber_list=$this->get_next_menber($id);//获取下级会员
		/*当是微店时*/
		if($user['groupid']>5 and $user['groupid']<14 ){
			/*获取销售额*/
		$list[0]=$user;
		$sell_list=$this->get_fee($list);//获取自消费、客户消费以及3种返利和下级平台管理分润
		$sell_list=$sell_list[0];
		$order_list=$this->get_order($id);/*获取微店订单销售详情*/
		$code=$this->get_code($id);//获取二维码
		$this->assign("qrcode",$code);
		$this->assign("sell_list",$sell_list);
		$this->assign("order_list",$order_list);
		}else{
		$order_list=$this->get_menber_order($id);//获取会员消费订单详情
		$this->assign("menber_order",$order_list);
		}
		$this->assign('shop_list',$shop_list);
		$this->assign('menber_list',$menber_list);
		$this->assign('adress',$adress);
		$this->assign('parent',$parent);
		$this->assign('rlist',$this->usergroup);	
	}


	function delete(){
		$id=$_GET['id'];
		$user=$this->dao;
		$this_user=$user->field("id,groupid,parent_id")->where("id=".$id)->find();
		if(false!==$user->delete($id)){
			/*删除成功后将直属下级挂到原上级处*/
			$data['parent_id']=$this_user['parent_id'];
			M("user")->where("parent_id=".$id)->save($data);
			/**/
			$roleuser=M('RoleUser');
			$roleuser->where('user_id ='.$id)->delete();
			delattach(array('moduleid'=>0,'catid'=>0,'id'=>0,'userid'=>$id));
			$this->success(L('delete_ok'));
		}else{
			$this->error(L('delete_error').$user->getDbError());
		}
	}

	function deleteall(){		
		$ids=$_POST['ids'];
		if(!empty($ids) && is_array($ids)){
			$user=$this->dao;
			$id=implode(',',$ids);
			if(false!==$user->delete($id)){
				$roleuser=M('RoleUser');
				$roleuser->where('user_id in('.$id.')')->delete();
				delattach("moduleid=0 and catid=0 and id=0 and userid in($id)");
				$this->success(L('delete_ok'));
			}else{
				$this->error(L('delete_error'));
			}
		}else{
			$this->error(L('do_empty'));
		}
	}

	public function all_shop(){
		import ( '@.ORG.Page' );
		$keyword=$_GET['keyword'];
		$searchtype=$_GET['searchtype'];
		if(!empty($keyword) && !empty($searchtype)){
			$where[$searchtype]=array('like','%'.$keyword.'%');
		}
		if(!empty($_REQUEST['province'])){
			$where['province']=$_REQUEST['province'];
		}
		if(!empty($_REQUEST['city'])){
			$where['city']=$_REQUEST['city'];
		}
		if(!empty($_REQUEST['area'])){
			$where['area']=$_REQUEST['area'];
		}
		$where['groupid']=$_REQUEST['groupid']? intval($_REQUEST['groupid']):array('between','6,13');
		$user=M('user');
		$count=$user->where($where)->count();
		$page=new Page($count,15);
		$show=$page->show();
		$this->assign("page",$show);
		
		$list=$user->order('id desc')->where($where)->limit($page->firstRow.','.$page->listRows)->select();

		$list=$this->get_info($list);//获取上级、管理年费、此处注意，此函数有获取历史押金记录但已没用，押金改为用user表里的receipt字段记录
		$list=$this->get_fee($list);//获取自消费、客户消费以及3种返利和下级平台管理分润
		$list=$this->take_status($list);//检查试运营与年费缴纳状态
		$this->assign('ulist',$list);
		$user_group=M('role')->field('id,name')->where('id >6 and id<14')->select();
		$this->assign('role',$user_group);
		$this->display();
	}

/*获取会员消费金额*/
	public function get_menber_fee($list=array()){
		foreach ($list as $key => $value) {
		$temp=array();
		$list[$key]['self_fee']=0;
		$list[$key]['online_cash']=0;
		//查询订单
		$temp=M("order")->field("pay_code,pay_status,cash_pay_status,order_amount,wechat_amount,cash_coupon")->where("userid=".$value['id']." and status in(1,2)")->select();
		foreach ($temp as $k => $v) {
			if($v['pay_status']==2&&$v['pay_code']=="Wechat_pay"){
				//微信支付订单
				$list[$key]['self_fee']+=floatval($v['order_amount']);
			}elseif($v['pay_status']==2&&$v['cash_pay_status']==1&&$v['pay_code']=="Cash_pay"&&$v['wechat_amount']>0){
				//电子现金支付一部分
				$list[$key]['self_fee']+=floatval($v['order_amount']);
				$list[$key]['online_cash']+=floatval($v['cash_coupon']);
			}elseif($v['cash_pay_status']==1&&$v['pay_code']=="Cash_pay"&&$v['order_amount']==$v['cash_coupon']){
				//电子现金支付全部金额
				$list[$key]['self_fee']+=floatval($v['order_amount']);
				$list[$key]['online_cash']+=floatval($v['cash_coupon']);
			}
		}
		/*查询年费、充值*/
		$temp=array();
		$temp=M("wechat_order")->field("amount")->where("userid=".$value['id']." status=1 and type in(1,3) and pay_time  between ".$this->star_time." and ".$this->end_time)->select();
		foreach ($temp as $kk => $vo) {
			$list[$key]['self_fee']+=floatval($v['amount']);
		}
		}
		return $list;
	}

/*获取上级、管理年费、押金*/
	public function get_info($list=array()){
	
		foreach ($list as $key => $value) {
			$temp=M('user')->field("realname")->where("id=".$value['parent_id'])->find();
			$list[$key]['parent_name']=empty($temp['realname']) ? "有酒派":$temp['realname'];//获取上级名称
			$consume=M("consume")->field("source,cash")->where("user_id=".$value['id'])->select();
			foreach ($consume as $k => $v) {
				if(intval($v['source'])==6){
					$list[$key]['manage']=$v['cash'];//获取管理年费，source为6
				}elseif(intval($v['source'])==3){
					$list[$key]['deposit']=$v['cash'];//押金，source为3
				}
			}
			$list[$key]['manage']=empty($list[$key]['manage'])? 0:floatval($list[$key]['manage']);
			$list[$key]['deposit']=empty($list[$key]['deposit'])? 0:floatval($list[$key]['deposit']);
		}

		return $list;
	}

/*获取自消费、客户消费、销售返利、下级返利、总返利、下级平台管理分润*/
	public function get_fee($list=array()){
		//var_dump($list);
		foreach ($list as $key => $value) {
			$result=array();//每次获取先清零
			$order=M('order')->field('sum(amount) as fee')->where("userid=".$value['id']." and status=2")->select();
			$result['self_fee']=$order[0]['fee'];//自消费
			$order=M('order')->field(' sum(amount) as fee ')->where(" shop_id=".$value['id']." and status=2")->select();
			$result['next_fee']=$order[0]['fee'];//客户消费
/*
			echo $value['id'];
			echo M('order')->getlastsql();*/
			$result['sell_back']=$this->sell_back($value['id']);//获取销售返利
			$result['next_sell_back']=$this->next_back($value['id']);//获取下级返利
			if($value['groupid']==6){$result['next_splitt']=$this->next_splitt($value['id']);}//获取下级平台管理分润

			$list[$key]['self_fee']=empty($result['self_fee'])? 0:floatval($result['self_fee']);
			$list[$key]['next_fee']=empty($result['next_fee'])? 0:floatval($result['next_fee']);
			$list[$key]['sell_back']=empty($result['sell_back'])? 0:floatval($result['sell_back']);
			$list[$key]['next_sell_back']=empty($result['next_sell_back'])? 0:floatval($result['next_sell_back']);
			$list[$key]['next_splitt']=empty($result['next_splitt'])? 0:floatval($result['next_splitt']);
			$list[$key]['total_back']=$list[$key]['sell_back']+$list[$key]['next_sell_back'];//总返利
			/*下面获取自消费比例*/
			if($list[$key]['next_fee']==0 && $list[$key]['self_fee']==0){
				$list[$key]['scale']=0;
			}elseif($list[$key]['next_fee']==0 && $list[$key]['self_fee']!=0){
				$list[$key]['scale']="100%";
			}else{
				$result['scale']=round($list[$key]['self_fee']/($list[$key]['self_fee']+$list[$key]['next_fee'])*100,2)."%";
				$list[$key]['scale']=$result['scale']=="0%"? 0:$result['scale'];
			}
		}
	return $list;
	}

/*****************销售返利*******************/
	public function sell_back($user_id){

	$order=M('order')->field('qq_order_data.product_price,qq_order_data.number,qq_order_data.menber_rebate')->join("`qq_order_data` on `qq_order_data`.order_id=`qq_order`.id")->where(" `qq_order`.shop_id=".$user_id." and `qq_order`.status=2")->select();
	//获取订单中商品的总价和返利比率（不取当前商品表中的数据是因为这样计算出来的金额有可能跟消费的金额有误差）
	$sell_back=0;
	foreach ($order as $k => $v) {
		$sell_back+=intval($v['number'])*floatval($v['menber_rebate']);
	}
	return $sell_back;
}

/*****************下级返利*******************/

public function next_back($user_id){
		$sell_back=0;
			$order=M('order')->field(' `qq_order_data`.product_price,`qq_order_data`.number,`qq_order_data`.parent_rebate ')->join("`qq_order_data` on `qq_order_data`.order_id=`qq_order`.id")->where(" `qq_order`.parent_shopid=".$user_id." and `qq_order`.status=2")->select();
			foreach ($order as $k => $v) {
		$sell_back+=intval($v['number'])*floatval($v['parent_rebate']);
			}
	return $sell_back;
}

/*****************下级平台管理分润*******************/

public function next_splitt($user_id){
		$splitt=0;
		foreach ($user as $key => $value) {
			$wechat_order=M('wechat_order')->field('parent_amount')->where(" parent_shopid=".$value['id']." and type=2 and status =1")->select();

			foreach ($wechat_order as $k => $v) {
				$splitt+=floatval($v['parent_amount']);
			}

			//$splitt+=$this->next_splitt($value['id']);//下级返利只拿直属下级的客户消费，所以此处暂时屏蔽

		}

	return $splitt;
}
/**********************结束***************/

/*****************下级微店列表*******************/

public function get_next_shop($user_id){
	$user=M('user');
	$count=$user->where("parent_id=".$user_id." and groupid > 6 and groupid< 14")->count();
	$list=$user->field(' `qq_user`.id,`qq_user`.groupid,`qq_user`.createtime,`qq_user`.realname,`qq_user`.shop_name,`qq_user`.mobile,`qq_user`.address,`qq_role`.name  as role_name ')->join(" `qq_role` on `qq_role`.id=`qq_user`.groupid ")->where(" `qq_user`.parent_id=".$user_id." and `qq_user`.groupid > 6 and `qq_user`.groupid< 14")->order(' `qq_user`.id desc ')->select();
	$this->assign('shop_count',$count);
	return $list;
}
/**********************结束***************/

/*****************下级会员列表*******************/
public function get_next_menber($user_id){
	$user=M('user');
	$count=$user->where("parent_id=".$user_id." and groupid < 6")->count();
	$list=$user->field(' `qq_user`.id,`qq_user`.groupid,`qq_user`.createtime,`qq_user`.wechat_name as realname,`qq_user`.mobile,`qq_user`.address,`qq_role`.name as role_name ')->join(" `qq_role` on `qq_role`.id=`qq_user`.groupid ")->where(" `qq_user`.parent_id=".$user_id." and `qq_user`.groupid < 6")->order(' `qq_user`.id desc ')->select();
	$this->assign('menber_count',$count);
	return $list;
}


/**********************结束***************/

/*****************销售订单列表*******************/
	
public function get_order($user_id){
	$order=M('order');
	$list=$order->field("`qq_user`.wechat_name,`qq_user`.realname,`qq_order`.* ")->join(" `qq_user` on `qq_user`.id=`qq_order`.userid ")->where(" `qq_user`.id in (SELECT id from `qq_user` where parent_id={$user_id}) and `qq_order`.status=2 ")->select();
	return $list;
}


/**********************结束***************/
/*****************会员订单列表*******************/
	
public function get_menber_order($user_id){
	$order=M('order');
	$total_price=$order->field(' sum(amount) as total_price')->where(" userid='$user_id' and status=2 ")->find();
	$num=$order->field(" sum(`qq_order_data`.number) as num")->join(" `qq_order_data` on `qq_order_data`.order_id=`qq_order`.id ")->where(' `qq_order`.userid='.$user_id.' and `qq_order`.status=2 ')->find();
	$list=$order->where(" userid={$user_id} and status=2 ")->select();
	$this->assign("menber_num",$num);
	$this->assign("menber_total",$total_price);
	return $list;
}


/**********************结束***************/

/*微店状态标记，试运营、缴纳年费等*/
public function take_status($list){
	/*获取会员组分级区间*/
	$role=M('role')->field('id,role_level')->where('id between 8 and 13')->order("role_level desc")->select();
		foreach ($list as $key => $value) {
			/*标记微店状态*/
			$tem_time=0;
			$time_tip=0;
			$tem_time=intval($value['beshop_time'])+2592000;//试运营时间，即开店时间+30天
			$time_tip=intval($value['beshop_time'])+1987200;//距离试运营结束一周前时间
			$list[$key]['status_flat']=0;
			if($value['status']==1){
				if($tem_time<time() && $value['test_status']==0){
					$list[$key]['status_flat']=1;//试运营结束时间到，更改状态并显示
					$r['status']=0;
					$r['id']=$value['id'];
					M('user')->save($r);
				}elseif($time_tip<time() && $value['test_status']==0){
					$list[$key]['status_flat']=2;//试运营结束时间准备到了
				}
			}
			/*标记结束*/
			/*当运营一年后检查年费缴纳情况*/
		$time_list=array();
		$time_list['time_year']=date("Y",intval($value['beshop_time']));//取得开始运营年份
		$time_list['time_month']=date("m",intval($value['beshop_time']));//取得每年的结数月份
		$time_list['time_day']=date("d",intval($value['beshop_time']));//取得每年的结数日
		$time_list['time_pay']=strtotime(date("Y-".$time_list['time_month']."-".$time_list['time_day'],time()))+86400;//取得今年结数时间,默认加多一天到第二天凌晨
		$time_list['now_year']=date("Y",time());//取得今年年份
		$time_list['now_day']=strtotime(date("Y-m-d",time()));//取得今日
		if($time_list['now_year']>$time_list['time_year']){//如果今年年份大于开始运营年份，则开始收费
				$time_where["user_id"]=$value['id'];
				$time_where['source']=6;//状态6为缴费
				$time_where['pay_for_time']=$time_list['now_year']+1;//默认缴纳下一年的费用
				$consume=M("consume")->field("id")->where($time_where)->find();
			if($time_list['now_day']>$time_list['time_pay'] && !$consume){
			//如果今日时间已超过今年月结时间，则检查是否缴纳下一年费用
				//如果没有交费则停用账号
					$r['status']=0;
					$r['id']=$value['id'];
					M('user')->save($r);
			}elseif($time_list['now_day']>$time_list['time_pay']-604800 && !$consume){
					//在要交年费一周前开始提示
					$list[$key]['status_flat']=3;//提示要缴费
					/*每年缴费前一周开始分级*/
					if($value['groupid']!=6){//如果是A+店则跳出
					$all_fee=floatval($value['self_fee'])+floatval($value['next_fee']);//自消费+客户消费=总销售额
					foreach ($role as $k => $v) {
						$tem_fee=floatval($v['role_level'])*10000;//计算出区间条件实际金额
						if($all_fee>=$tem_fee){
							//当总销售金额>区间条件就是这等级，区间由大到小排序
							$r['groupid']=$v['id'];
							$r['id']=$value['id'];
							M('user')->save($r);
							break;
						}
					}
				}
					/**/
			}else{
				//上述情况都没有则保持运营，或唤醒运营
				$r['status']=1;
				$r['id']=$value['id'];
				$a=M('user')->save($r);
			}
		}
		}
	return $list;
}
/**************结束*********************/

/*会员年费缴纳状态*/
public function take_menber_status($list){
	$gold=M('role')->field('gold_money,gold_fee')->where("id=4")->find();
	$gold_money=$gold ? floatval($gold['gold_money']):0;//一次性充值，变为电子现金
	$gold_fee=$gold ? floatval($gold['gold_fee']):0;//一次性缴纳，不能变为电子现金
	foreach ($list as $k => $v) {
		$list[$k]['recharge_flat']=0;//即将结束标记,0则没事
		if($v['groupid']==3){
		/*会员检查充值记录是否足够*/
		$lasttime=$v['lastrecharge_time']?$v['lastrecharge_time']:0;
		$money=M("consume")->where("user_id=".$v['id']." and source in(2,4) and create_time>".$lasttime)->order("id desc")->find();
		if($money){
			if($money['source']==2&& floatval($money['cash'])>=$gold_money){
			$data['id']=$v['id'];
			$data['groupid']=4;//升为金会员
			$data['lastrecharge_time']=intval($money['create_time']);//记录最后一次满足条件的充值时间
			M('user')->save($data);
			}elseif($money['source']==4&&floatval($money['cash'])>=$gold_fee){
			$data['id']=$v['id'];
			$data['groupid']=4;//升为金会员
			$data['lastrecharge_time']=intval($money['create_time']);//记录最后一次满足条件的充值时间
			M('user')->save($data);
			}
		}

		}elseif($v['groupid']==4){
		/*金会员*/
		$time_list=array();
		$time_list['last_time']=intval($v['lastrecharge_time'])+31536000;//取到会员结束时间,开始时间+一年
		$time_list['now_time']=time();//取得当前时间
		$time_list['week_ago']=$time_list['last_time']-604800;//结束一周前
		if($time_list['now_time']>$time_list['last_time']){
			//超过时间，自动降级为普通会员
			$data['id']=$v['id'];
			$data['groupid']=3;
			M('user')->save($data);
		}elseif($time_list['now_time']>$time_list['week_ago']){
			//即将结束，进行提示
			$list[$k]['recharge_flat']=1;
		}

		}
	}
	return $list;
}

/**********************/

/*会员手动升级、降级*/
	public function menber_level($list){
	$gold=M('role')->field('gold_money,gold_fee')->where("id=4")->find();
	$gold_fee=$gold ? floatval($gold['gold_fee']):0;//一次性缴纳，不能变为电子现金
		/*更新检查级别*/
		foreach ($list as $key => $value) {
			$us=array();
			$level_data=array();
		$us=M('user')->field('id,groupid,lastrecharge_time')->where('id='.$value['id'])->find();
		if($us['groupid']!=$value['groupid'] && in_array($us['groupid'],array(3,4))){
			//如果是会员组别且组别不一样，则手动升级或降级了
			if($value['groupid']==4){
				/*升级*/
				$level_data['user_id']=$value['id'];
				$level_data['cash']=$gold_fee;
				$level_data['source']=2;
				$level_data['create_time']=time();
				$level_data['level_flat']=1;
				M('consume')->add($level_data);
				$da['id']=$value['id'];
				$da['lastrecharge_time']=$level_data['create_time'];
				M('user')->save($da);

			}elseif($value['groupid']==3){
				/*降级*/
				$level_data['user_id']=$us['id'];
				$level_data['create_time']=$us['lastrecharge_time'];
				$r=M('consume')->field('id')->where($level_data)->find();
				if($r){
				$level_data['id']=$r['id'];
				$level_data['level_flat']=2;
				M('consume')->save($level_data);
				}

			}
		}
		}
		/*检查级别 end*/
		return true;
	}

/********end*********/
/*批量升级、降级*/
	public function menber_level_all(){
		$ids=$_POST['ids'];
		$groupid=$_POST['groupid'];
		$do=$_POST['dosubmit'];
		$list=array();
		if(!empty($ids) && is_array($ids) && in_array($groupid,array(3,4))){
			foreach ($ids as $key => $value) {
				$list[$key]['id']= $value;
				$list[$key]['groupid']= $groupid;
			}
			$this->menber_level($list);
			foreach ($list as $key => $value) {
				$data=array();
				$data=$value;
				M('user')->save($data);
			}
			$this->success($do."成功");exit();
		}else{
			$this->error('请先选择好会员');
		}
	}
/**/
/*微店缴纳押金*/
public function pay_receipt(){
	$ids=$_POST['ids'];
	$amount=M("config")->field("value")->where(array("varname"=>"shop_receipt_fee"))->find();
	$US['id']=array('in',$ids);
	$user=M("user")->field("id,receipt,test_status")->where($US)->select();
	$consume['source']=3;
	$consume['pay_type']=3;
	$consume['create_time']=mktime();
	$consume['cash']=floatval($amount['value']);
	foreach ($user as $key => $value) {
		if($value['test_status']==0){
			$consume['user_id']=$value['id'];
			M("consume")->add($consume);
			//$data_user['receipt']=floatval($user['receipt'])+floatval($amount['value']);
			$data_user['test_status']=1;
			$data_user['id']=$value['id'];
			$data_user['status']=1;
			M("user")->save($data_user);
		}
	}
	$this->success("后台支付成功");
}
/*end*///二维码
	public function get_code($id){
		$code=M("qrcode")->where("userid=".$id)->find();
		if(empty($code['ticket'])){
		/*当用户不存在二维码时生成二维码*/
		$gh = M('wechat')->field('id,gh_id,appId,appSecret')->where(array('uid'=>1,'status'=>1))->find();
		if(!isset($gh['appId']) || !isset($gh['appSecret'])){
			$this->error('二维码参数出错了，请联系客服');
		}
		$this->gh_local_id = $gh['id'];
		$this->gh_id = $gh['gh_id'];
		$this->appId = $gh['appId'];
		$this->appSecret = $gh['appSecret'];
		$this->assign('gh',$gh);

		import ( '@.ORG.MP' );
		$this->mp = new MP($this->appId,$this->appSecret);
		$scene_id=$id;
		$data['action_name']="QR_LIMIT_SCENE";
		$data['action_info']['scene']['scene_id']=$scene_id;
		$json_data=json_encode($data);
		$res=$this->mp->create_code($scene_id);//返回生成参数
		$img=$this->get_code_img($res['ticket']);
		$data['userid']=$id;
		$data['ticket']=$res['ticket'];
		$data['url']=$img;
		$data['createtime']=mktime();
		M("qrcode")->add($data);//插入数据库
		$code=array();
		$code=$data;
		/*获取二维码*/
		}
		if(empty($code['url'])){
			$code['url']=$this->get_code_img($code['ticket']);
			M("qrcode")->save($code);
		}
		return $code;
	}
	protected function get_code_img($ticket){
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
protected function download_qrcode($url){
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
}
?>