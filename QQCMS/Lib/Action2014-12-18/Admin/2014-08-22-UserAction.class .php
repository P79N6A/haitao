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
		var_dump($_POST['groupid']);exit();
		$user=$this->dao;
		$_POST['password'] = sysmd5($_POST['pwd']);
		$_POST['createtime']=mktime();
		/*如果是新增微店则增加成立微店时间*/
		if($_POST['groupid']>6 && $_POST['groupid']<14){
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
		if(false!==$user->delete($id)){
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

		$list=$this->get_info($list);//获取上级、管理年费、此处注意，此函数有获取历史押金记录，但押金改为用user表里的receipt字段记录
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
			$fee=M('order')->field('sum(amount) as fee')->where("userid=".$value['id']." and status=2")->select();
			$result['self_fee']=$fee[0]['fee'];//自消费
			$list[$key]['self_fee']=empty($result['self_fee'])? 0:floatval($result['self_fee']);
		}
		return $list;
	}

/*获取上级、管理年费、押金*/
	public function get_info($list=array()){
	
		foreach ($list as $key => $value) {
			$temp=M('user')->field("realname")->where("id=".$value['parent_id'])->find();
			$list[$key]['parent_name']=empty($temp['realname']) ? "有酒派":$temp['realname'];//获取上级名称
			$consume=M("consume")->where("user_id=".$value['id']." and source >1 and source<4")->select();
			foreach ($consume as $k => $v) {
				if(intval($v['source'])==2){
					$list[$key]['manage']=$v['cash'];//获取管理年费，source为2
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
			$order=M('order')->field(' sum(qq_order.amount) as fee ')->join(" `qq_user` on qq_order.userid=qq_user.id ")->where(" qq_user.parent_id=".$value['id']." and qq_user.groupid <6 and qq_order.status=2")->select();
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

	$order=M('order')->field('qq_order_data.total_price,qq_order_data.menber_rebate')->join(" `qq_user` on qq_order.userid=qq_user.id ")->join("`qq_order_data` on qq_order_data.userid=qq_user.id and qq_order_data.order_id=qq_order.id")->where(" qq_user.parent_id=".$user_id." and qq_user.groupid <6 and qq_order.status=2")->select();
	//获取订单中商品的总价和返利比率（不取当前商品表中的数据是因为这样计算出来的金额有可能跟消费的金额有误差）
	$sell_back=0;
	foreach ($order as $k => $v) {
		$sell_back+=floatval($v['total_price'])*floatval($v['menber_rebate']);
	}
	return $sell_back;
}

/*****************下级返利*******************/

public function next_back($user_id){
	$user=M('user')->field('id')->where("parent_id=".$user_id." and groupid > 5 and groupid< 14")->select();
	if($user){
		$sell_back=0;
		foreach ($user as $key => $value) {
			$order=M('order')->field('qq_order_data.total_price,qq_order_data.parent_rebate')->join(" `qq_user` on qq_order.userid=qq_user.id ")->join("`qq_order_data` on qq_order_data.userid=qq_user.id and qq_order_data.order_id=qq_order.id")->where(" qq_user.parent_id=".$value['id']." and qq_user.groupid <6 and qq_order.status=2")->select();
			
			foreach ($order as $k => $v) {
				$sell_back+=floatval($v['total_price'])*floatval($v['parent_rebate']);
			}
			//$sell_back+=$this->next_back($value['id']);下级返利只拿直属下级的客户消费，所以此处暂时屏蔽
		}

	}
	return $sell_back;
}

/*****************下级平台管理分润*******************/

public function next_splitt($user_id){
	$user=M('user')->field('id')->where("parent_id=".$user_id." and groupid > 6 and groupid< 14")->select();
	if($user){
		$splitt=0;
		foreach ($user as $key => $value) {
			$consume=M('consume')->field(' `qq_consume`.cash,`qq_role`.parent_splitt ')->join(" `qq_user` on `qq_user`.id=`qq_consume`.user_id")->join(" `qq_role` on `qq_role`.id=`qq_user`.groupid ")->where(" `qq_user`.id=".$value['id']." and `qq_consume`.source=2")->select();

			foreach ($consume as $k => $v) {
				$splitt+=floatval($v['cash'])*floatval($v['parent_splitt']);
			}

			//$splitt+=$this->next_splitt($value['id']);//下级返利只拿直属下级的客户消费，所以此处暂时屏蔽

		}

	}
	return $splitt;
}
/**********************结束***************/

/*****************下级微店列表*******************/

public function get_next_shop($user_id){
	$user=M('user');
	$count=$user->where("parent_id=".$user_id." and groupid > 6 and groupid< 14")->count();
	$list=$user->field(' `qq_user`.id,`qq_user`.groupid,`qq_user`.createtime,`qq_user`.realname,`qq_user`.mobile,`qq_user`.address,`qq_role`.name  as role_name ')->join(" `qq_role` on `qq_role`.id=`qq_user`.groupid ")->where(" `qq_user`.parent_id=".$user_id." and `qq_user`.groupid > 6 and `qq_user`.groupid< 14")->order(' `qq_user`.id desc ')->select();
	$this->assign('shop_count',$count);
	return $list;
}
/**********************结束***************/

/*****************下级会员列表*******************/
public function get_next_menber($user_id){
	$user=M('user');
	$count=$user->where("parent_id=".$user_id." and groupid < 6")->count();
	$list=$user->field(' `qq_user`.id,`qq_user`.groupid,`qq_user`.createtime,`qq_user`.realname,`qq_user`.mobile,`qq_user`.address,`qq_role`.name as role_name ')->join(" `qq_role` on `qq_role`.id=`qq_user`.groupid ")->where(" `qq_user`.parent_id=".$user_id." and `qq_user`.groupid < 6")->order(' `qq_user`.id desc ')->select();
	$this->assign('menber_count',$count);
	return $list;
}


/**********************结束***************/

/*****************销售订单列表*******************/
	
public function get_order($user_id){
	$order=M('order');
	$list=$order->field(" `qq_order`.*,`qq_user`.realname ")->join(" `qq_user` on `qq_user`.id=`qq_order`.userid ")->where(" `qq_user`.id in (SELECT id from `qq_user` where parent_id={$user_id}) and `qq_order`.status=2 ")->select();
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
		foreach ($list as $key => $value) {
			/*标记微店状态*/
			$tem_time=0;
			$time_tip=0;
			$tem_time=intval($value['beshop_time'])+2592000;//试运营时间，即开店时间+30天
			$time_tip=intval($value['beshop_time'])+1987200;//距离试运营结束一周前时间
			$list[$key]['status_flat']=0;
			if($value['status'] ){
				if($tem_time<time() && $value['receipt']==0){
					$list[$key]['status_flat']=1;//试运营结束时间到，更改状态并显示
					$r['status']=0;
					$r['id']=$value['id'];
					M('user')->save($r);
				}elseif($time_tip<time() && $value['receipt']==0){
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
				$time_where['source']=2;//状态2为缴费
				$time_where['pay_for_time']=$now_year+1;//默认缴纳下一年的费用
				$consume=M("consume")->field("id")->where($time_where)->find();

			if($time_list['now_day']>$time_list['time_pay'] && !$consume){
			//如果今日时间已超过今年月结时间，则检查是否缴纳下一年费用
				//如果没有交费则停用账号
					$r['status']=0;
					$r['id']=$check_user['id'];
					M('user')->save($r);
			}elseif($time_list['now_day']>$time_list['time_pay']-604800 && !$consume){//在要交年费一周前开始提示
					$list[$key]['status_flat']=3;//提示要缴费
			}
		}
		/*年费检查结束*/
		}
	return $list;
}
/**************结束*********************/

/*会员年费缴纳状态*/
public function take_menber_status($list){
	$gold=M('role')->field('gold_money')->where("id=4")->find();
	$gold_money=$gold ? floatval($gold['gold_money']):0;
	foreach ($list as $k => $v) {
		$list[$k]['recharge_flat']=0;//即将结束标记,0则没事
		if($v['groupid']==3){
		/*会员检查充值记录是否足够*/
		$lasttime=$v['lastrecharge_time']?$v['lastrecharge_time']:0;
		$fee=M("consume")->where("user_id=".$v['id']." and source=4 and create_time>".$lasttime)->order("id desc")->find();
		if($fee && floatval($fee['cash'])>=$gold_money){
			$data['id']=$v['id'];
			$data['groupid']=4;//升为金会员
			$data['lastrecharge_time']=intval($fee['create_time']);//记录最后一次满足条件的充值时间
			M('user')->save($data);
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

}
?>