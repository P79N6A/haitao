<?php
namespace Admin\Controller;
use Think\Controller;
class CommController extends Controller {
	protected $current_user;
	public $where = array('status'=>array('in','0,1'));
	public $theParam;
	public $groups;
	public $wxappId;
	public $wxappSecret;
	public $oauth;
	public $code;
	protected $_istime_pay=0,$_pay_for_time=0;

	function __construct(){
		parent::__construct();
		header('Content-Type:text/html; charset=utf-8');
		$this->wxappId = 'wx298a48a600565b2f';
		$this->wxappSecret = 'c43d586fe5f6560dabbc70e057e88058';
		$vars = M('co_config')->field('varName,value')->select();
		foreach($vars as $var){
			$this->assign($var['varName'],$var['value']);

		}
		//$this->assign('site_url','http://192.168.1.100');
		if(C('AUTH_ON') && !isset($_SESSION['uid'])){
			if($_GET['to']){
				$this->supperLogin($_GET['to']);

			}
			$this->login();
			exit;
		}else{
			$this->current_user = M('user')->field('*,`realname` as nickname')->where(array('id'=>session('uid')))->find();
			$this->assign('admin',$this->current_user);

			//dump($this->current_user);die();
		}
		$this->menu();
		$this->auth = new \Lib\Auth;//自定义命名空间
		$this->assign('auth',$this->auth);//预留模板调用 权限验证
		$this->assign('status_01', array(array('value'=>1,'title'=>'启用'),array('value'=>0,'title'=>'禁用')));
		$this->assign('status_converter',array('<span class="red">已禁用</span>','正常'));
		$this->theParam = include_once(APP_PATH.'/Common/Conf/param.php');
		$this->assign('theParam',$this->theParam );
		$this->assign('vendor',C('VENDOR'));
		$this->assign('co_site_url',C('SITE_URL'));
		//考虑设置为常量
		
			/*检查登录权限*/
		$check_user=$this->current_user;
		if($check_user['groupid']<6||$check_user['groupid']>13){
			$this->error("非微店账号不能登录！");exit();
		}
			/*获取会员组分级区间*/
		$role=M('role')->field('id,role_level')->where('id between 8 and 13')->order("role_level desc")->select();
			/*检查微店运营状态*/
		$test_shop_time=intval($check_user['beshop_time'])+2592000;//加上30天时间得出试运营时间
		$time_tip=intval($check_user['beshop_time'])+1987200;//距离试运营结束一周前时间
		if($test_shop_time<time()&& $check_user['test_status']==0){
			//如果试运营超过1个月并且没缴纳保证金的，停止营业
			$r['status']=0;
			$r['id']=$check_user['id'];
			M('user')->save($r);
			$tip_list['type']=2;//提示试运营已经过期
			setcookie("time_status_".$check_user['id'],1,time()+60);
			$this->assign("tip_list",$tip_list);
			//$this->error("您的微店超过试营时间未缴纳保证金，请联系管理员缴纳保证金并恢复营业！");exit();
		}elseif($time_tip<time() && $check_user['test_status']==0 && !isset($_COOKIE["time_status_".$check_user['id']])){
			$tip_list['type']=1;//提示试运营即将到期
			setcookie("time_status_".$check_user['id'],1,time()+60);
			$this->assign("tip_list",$tip_list);
		}
		/*当运营一年后检查年费缴纳情况*/
		$time_year=date("Y",intval($check_user['beshop_time']));//取得开始运营年份
		$time_month=date("m",intval($check_user['beshop_time']));//取得每年的结数月份
		$time_day=date("d",intval($check_user['beshop_time']));//取得每年的结数日
		$time_pay=strtotime(date("Y-".$time_month."-".$time_day,time()))+86400;//取得今年结数时间,默认加多一天到第二天凌晨
		$now_year=date("Y",time());//取得今年年份
		$now_day=strtotime(date("Y-m-d",time()));//取得今日
		if($now_year>$time_year && $check_user['groupid']!=6){//如果今年年份大于开始运营年份，则开始收费
				$time_where["user_id"]=$check_user['id'];
				$time_where['source']=6;//状态6为缴费
				$time_where['pay_for_time']=$now_year+1;//默认缴纳下一年的费用
				$consume=M("consume")->field("id")->where($time_where)->find();
			if($now_day>$time_pay && !$consume){
			//如果今日时间已超过今年月结时间，则检查是否缴纳下一年费用
				//如果没有交费则停用账号
					$r['status']=0;
					$r['id']=$check_user['id'];
					M('user')->save($r);
					if(!isset($_COOKIE["time_status_".$check_user['id']])){
					$tip_list['type']=3;//提示已停用
					setcookie("time_status_".$check_user['id'],1,time()+60);
					$this->assign("tip_list",$tip_list);}
					/*标记需要缴纳平台管理费*/
					$this->_pay_for_time=$now_year+1;
					$this->_istime_pay=1;
					/**/
			}elseif($now_day>$time_pay-604800 && !$consume){//在要交年费一周前开始提示
				if(!isset($_COOKIE['time_status_'])){
					$tip_list['type']=4;//提示要缴费
					setcookie("time_status_".$check_user['id'],1,time()+60);
					$this->assign("tip_list",$tip_list);
				}
					/*每年缴费前一周开始分级*/
					if($check_user['groupid']!=6){
					$all_fee=floatval($value['self_fee'])+floatval($value['next_fee']);//自消费+客户消费=总销售额
					foreach ($role as $k => $v) {
						$tem_fee=floatval($v['role_level'])*10000;//计算出区间条件实际金额
						if($all_fee>=$tem_fee){
							//当总销售金额>区间条件就是这等级，区间由大到小排序
							$r['groupid']=$v['id'];
							$r['id']=$check_user['id'];
							M('user')->save($r);
							break;
						}
						}
					}
					/**/
					/*标记需要缴纳平台管理费*/
					$this->_pay_for_time=$now_year+1;
					$this->_istime_pay=1;
					/**/
			}else{
				//上述情况都没有则保持运营，或唤醒运营
				$r['status']=1;
				$r['id']=$check_user['id'];
				M('user')->save($r);
			}
		}
		/*年费检查结束*/
		//var_dump(strtotime(date("2013-08-17")));exit();
	}
	final public function supperLogin($to){
		//这里做验证 是否有权限登录，考虑用一个 session 或 cookie的标示
		
			$to = (int)$to;
		
	   //print_r($_SESSION);exit;

	 //print_r($_COOKIE);

		$_SESSION['uid']= $to;
		//$_SESSION['from_uid'] = $from;
		header("location:".$this->site_url.U('Index/index'));
				exit;



	}  
	/**
	  * 分离checkPwd的过程，预留给其他地方使用
	  */
	final public function login(){
		if($_GET['code'] && $_GET['state']){
			$this->code = $_GET['code'];
			$this->oauth = new \Lib\Oauth($this->wxappId,$this->wxappSecret,$this->code);
			$wechatuser = $this->oauth->getUserInfo();
			##检查用户是否有效存在##
			$where['unionid'] = $wechatuser['unionid'];
			$checkuser = M('User')->field('id,wechat_openid,wechat_name,status')->where($where)->find();
			$this->assign('jumpUrl',$this->site_url.U('Index/login'));
			if ($checkuser['id'] && $checkuser['status'])
			{
				$_SESSION['uid']=$checkuser['id'];
				header("location:".$this->site_url.U('Index/index'));
				exit;
			}else if (!$checkuser['status']){
				$this->error('您的账号已被禁用，请联系管理员！');
				exit;
			}else{
				$this->error('用户名或者密码错误！');
				exit;
			}
		}

		/*获取微信开放平台配置*/
		$oa_gh = M("wechat")->field("appId,appSecret")->find();
		if ($oa_gh)
		{
			$url = 'http://'.$_SERVER['HTTP_HOST'].'/redirect.php?act=coLogin';
			$redirect_uri = UrlEncode($url);
			$this->assign("redirect_uri",$redirect_uri);
			$this->assign("oa_appId",'wx298a48a600565b2f');
			$this->assign("oa_appSecret",'c43d586fe5f6560dabbc70e057e88058');
		}
		$this->display('Index_login');
	}
	protected function checkPwd($user,$pwd){
		$email = trim($user);
		//$pwd = md5($pwd);
		$pwd = $this->sysmd5($pwd);//兼容QQcms	
	/*	$check = M('user')->field('id,realname as nickname')->where(array('groupid'=>array('in',array(6,7,8,9,10,11,12,13)),'password'=>$pwd,'_query'=>'email='.$email.'&username='.$email.'&_logic=or','status'=>1))->find();*/
	$mm=M('user');
	$check =$mm->field('id,realname as nickname,password')->where("groupid in (6,7,8,9,10,11,12,13) and username='".$email."'")->find();
		if(isset($check['id']) && $check['password']==$pwd){
			return $check;
		}else{
			return false;
		}
	}

	/**
	  * 移植QQcms的sysmd5
	  */
	function sysmd5($str,$key='',$type='sha1'){
		$key =  $key ?  $key : C('ADMIN_ACCESS');
		return hash ( $type, $str.$key );
	}
	/**
	  * unset无法logout 奇怪
	  */
	final public function logout(){
		session_destroy();
		session('uid',null);
		header('Location: /co/index.php ');
	}

	/**
	  * 节点菜单(后台适用)
	  */
	final protected function menu(){
		C('MERCHANT',83);//这个可以扩展在数据库 qq_co_config 里
		$temp_pid = 'parentid';//fuck 为什么每个人用的pid 都不统一
		$menu = M('menu')->field('`model` as name,`name` as title,data')
			->where(array('parentid'=>C('MERCHANT'),'status'=>1))
			->order('listorder desc')
			->select();
		$count = count($menu)-1;
		foreach ($menu as $k=>$v){
			if(0==$k && $v['name']==CONTROLLER_NAME){
				$menu[$k]['theclass'] = 'class="fisrt_current"';
			}elseif(0==$k && $v['name']!=CONTROLLER_NAME){
				$menu[$k]['theclass'] = 'class="fisrt"';
			}elseif($count==$k && $v['name']==CONTROLLER_NAME){
				$menu[$k]['theclass'] = 'class="end_current"';
			}elseif($count==$k && $v['name']!=CONTROLLER_NAME){
				$menu[$k]['theclass'] = 'class="end"';
			}elseif($v['name']==CONTROLLER_NAME){
				$menu[$k]['theclass'] = 'class="current"';
			}else{
				$menu[$k]['theclass'] = '';
			}
		}
		$this->assign('menu',$menu);
		$pid = M('menu')->field('id')->where(array('model'=>CONTROLLER_NAME,$temp_pid=>C('MERCHANT'),'status'=>1))->find();
		$subMenu = M('menu')->field('`model` as name,`name` as title,id,data')->where(array($temp_pid=>$pid['id'],'status'=>1))->order('listorder desc')->select();
		$this->assign('subMenu',$subMenu);
	}
	/**
	  * 通用 更新/插入 数据
	  * @param	$db	要操作的数据表
	  * +-----------------------------------------+
	  * 请注意：
	  * 只适用于以ID为主键的数据库表
	  * +-----------------------------------------+
	  */
	final protected function createUpdate($db,$id=0,$others=array()){
		$id = (int) $id;
		$db = M($db);

		if($others){
			foreach ($others as $k=>$v){
				$_POST[$k]=$v;

			}
		}
//dump($_POST);
//dump($db->create());

		if ($db->create() === false) {
			$this->error($db->getError());exit;
		} else {
			
			if(0===$id)
				$id = $db->add();
			else
				$id = $db->where(array('id'=>$id))->save();
		}
		if(!$id){
			//$this->error('操作失败了，可能是没新数据！');exit;
		}else{
			return $id;
		}
	}
	/**
	  * 软删除
	  * @param	$db	要操作的数据表
	  */
	final protected function delete($db,$id){
		$id = (int)$id;
		$return = M($db)->where(array('id'=>$id))->data(array('status'=>-1))->save();
		if(!$return)
			$this->error('删除失败！');
	}
	/**
	  * 获取分组
	  * 后续应该会 扩展增加参数，
	  */
	protected function getGroup(){
		$groups = M('user_group')->field('id,title,status')->where($this->where)->select();
		return $groups;
	}
	/**
	  * 获取节点列表
	  * 后续应该会 扩展增加参数，
	  */
	protected function getNodeList(){
		$nodes = M('node')->field('id,pid,name,title,status,listorder')->where($this->where)->order('listorder desc')->select();
		$nodes = frequent_infinite_category($nodes,0);
		return $nodes;
	}
	/**
	  * user_group_association
	  * 数据对接，更新
	  * 优化了的分组更新，原理和微信的更新线上'线下分组一样
	  * +-----------------------------------------+
	  *  1.本地有，远端没有
	  *  2.本地有，线上也有的
	  *  3.本地没有，线上有的，需要插入(mysql批量插入)
	  * +-----------------------------------------+
  	  */
	final protected function dataButt($distance,$uid){
		$local = M('user_group_association')->field('group_id')->where(array('uid'=>$uid))->select();
		$local = multidimensional_2_unidimensional($local,'group_id');
		$toClose = array_diff($local,$distance);
		M('user_group_association')->where(array('uid'=>$uid,'group_id'=>array('in',$toClose)))->save(array('status'=>0));
		$intersect = array_intersect($distance,$local); 
		M('user_group_association')->where(array('uid'=>$uid,'group_id'=>array('in',$intersect)))->save(array('status'=>1));
		$toOpen = array_diff($distance,$intersect);
		if($toOpen){
			foreach ($toOpen as $v){
				$dataList[] = array('uid'=>$uid,'group_id'=>$v,'status'=>1);
			}
			M('user_group_association')->addAll($dataList);
		}
	}
	protected function initWhere(){
		$this->where = array('status'=>array('in','0,1'));
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
			$list[$key]['parent_name']=empty($temp['realname']) ? "上优舶":$temp['realname'];//获取上级名称
			$consume=M("consume")->where("user_id=".$value['id'])->select();
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
			$order=M('order')->field('sum(amount) as fee')->where("userid=".$value['id']." and status=1")->select();
			$result['self_fee']=$order[0]['fee'];//自消费
			$order=M('order')->field(' sum(amount) as fee ')->where(" shop_id=".$value['id']." and status=1")->select();
			$result['next_fee']=$order[0]['fee'];//客户消费

			##获取代理区域信息##
			$agarea = M('Agentarea')->where('userId='.$value['id'])->find();
			//代理区域
			$agentarea = explode(',', $agarea['agentarea']);
			$agwhere['o.province'] = $agentarea[0];
			$agwhere['o.city'] = $agentarea[1];
			if ($agentarea[2])
			$agwhere['o.area'] = $agentarea[2];
			$agwhere['o.status'] = 1;
			$_field = 'sum(o.amount) as all_amount';
			$join = 'qq_order_data as od ON o.id = od.order_id';
			$agorder = M('Order')->alias('o')->field($_field)->join($join)->where($agwhere)->select();
			/*$userid = session('uid');
			if ($userid == 1196)
			{
				print_r($agorder);exit;
			}*/
			$result['next_fee'] += $agorder[0]['all_amount'];//客户消费

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

	$order=M('order')->field('qq_order_data.product_price,qq_order_data.number,qq_order_data.menber_rebate')->join("`qq_order_data` on `qq_order_data`.order_id=`qq_order`.id")->where(" `qq_order`.shop_id=".$user_id." and `qq_order`.status=1")->select();
	$agentarea = M('Agentarea')->where('userId='.$user_id)->find();
	//获取订单中商品的总价和返利比率（不取当前商品表中的数据是因为这样计算出来的金额有可能跟消费的金额有误差）
	$sell_back=0;
	foreach ($order as $k => $v) {
		$sell_back+=intval($v['number'])*floatval($v['product_price'])*15/100;
	}
	$sell_back+=$agentarea['com_price'];
	return $sell_back;
}

/*****************下级返利*******************/

public function next_back($user_id){
		$sell_back=0;
			$order=M('order')->field(' `qq_order_data`.product_price,`qq_order_data`.number,`qq_order_data`.parent_rebate ')->join("`qq_order_data` on `qq_order_data`.order_id=`qq_order`.id")->where(" `qq_order`.parent_shopid=".$user_id." and `qq_order`.status=1")->select();
			foreach ($order as $k => $v) {
		$sell_back+=intval($v['number'])*floatval($v['product_price'])*15/100;
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
	$list=$order->field(" `qq_order`.*,`qq_user`.realname ")->join(" `qq_user` on `qq_user`.id=`qq_order`.userid ")->where(" `qq_order`.shop_id='{$user_id}' and `qq_order`.status=2 ")->select();
	return $list;
}


/**********************结束***************/
/*****************会员订单列表*******************/
	
public function get_menber_order($user_id){
	$shop_id=intval($_SESSION['uid']);
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
/**************统计下级个数*****************/
public function next_num($list=array()){
	if($list){
	foreach ($list as $k => $v) {
		$list[$k]['menber_count']=M('user')->where('groupid between 3 and 4 and parent_id='.$v['id'])->count();
		$list[$k]['shop_count']=M('user')->where('groupid between 7 and 13 and parent_id='.$v['id'])->count();
	}
}
	return $list;
}
/***************统计每月销售利润***************************/
protected function month_fee($list=array()){
	foreach ($list as $key => $value) {
		$time_list=array();
		$month_num=0;
		$month_list=array();
		$data=array();
		$time_list['time_year']=date("Y",intval($value['beshop_time']));//取得开始运营年份
		$time_list['time_month']=date("m",intval($value['beshop_time']));//取得每年的结数月份
		$time_list['now_year']=date("Y",time());//取得今年年份
		$time_list['now_month']=date("m",time());//取得当前月份
		foreach ($time_list as $k => $v) {
			$time_list[$k]=intval($v);
		}
		$month_num=12-$time_list['time_month']+1;//加多一个月从开始月1号开始算
		$month_num+=($time_list['now_year']-$time_list['time_year']-1)*12;
		$month_num+=$time_list['now_month']+1;//加多一个月，其实就是算到当前月月未
		
		for($i=1;$i<=$month_num;$i++){//开始组装月份列表
			if($time_list['time_month']>12){
				$time_list['time_year']++;
				$time_list['time_month']=1;
			}
			$month_list[]=$time_list['time_year']."-".$time_list['time_month'];
			$time_list['time_month']++;
		}
		for ($i=0; $i <$month_num-1 ; $i++) { //查询每月销售数据
			$star=strtotime($month_list[$i]);
			$end=strtotime($month_list[$i+1]);
			$data[$i]=$this->time_get_fee($value,$star,$end);
			$data[$i]["month"]=date("Y年m月",$star);
		}
		//倒序排列
		$lenght=$month_num-2;
		for ($i=0; $i < $month_num-1; $i++) { 
			$list[$key]["month_fee"][$i]=$data[$lenght];
			$lenght--;
		}
	}
	return $list;
}
/*获取自消费、客户消费、销售返利、下级返利、总返利*/
	public function time_get_fee($value=array(),$star_time=0,$end_time=0){
		//var_dump($list);
			$result=array();//每次获取先清零
			$where=array();
			$where['userid']=$value['id'];
			$where['status']=2;
			$where['pay_time']=array("between",array($star_time,$end_time));
			$order=M('order')->field('sum(amount) as fee')->where($where)->select();
			$result['self_fee']=$order[0]['fee'];//自消费

			unset($where['userid']);
			$where['shop_id']=$value['id'];
			$order=M('order')->field(' sum(amount) as fee ')->where($where)->select();
			$result['next_fee']=$order[0]['fee'];//客户消费

			$result['sell_back']=$this->time_sell_back($value['id'],$star_time,$end_time);//获取销售返利
			$result['next_sell_back']=$this->time_next_back($value['id'],$star_time,$end_time);//获取下级返利

			$list['self_fee']=empty($result['self_fee'])? 0:floatval($result['self_fee']);
			$list['next_fee']=empty($result['next_fee'])? 0:floatval($result['next_fee']);
			$list['sell_back']=empty($result['sell_back'])? 0:floatval($result['sell_back']);
			$list['next_sell_back']=empty($result['next_sell_back'])? 0:floatval($result['next_sell_back']);
			$list['total_back'] = $list['sell_back']+$list['next_sell_back'];//总返利
			/*下面获取自消费比例*/
			if($list['next_fee']==0 && $list['self_fee']==0){
				$list['scale']=0;
			}elseif($list['next_fee']==0 && $list['self_fee']!=0){
				$list['scale']="100%";
			}else{
				$result['scale']=round($list['self_fee']/($list['self_fee']+$list['next_fee'])*100,2)."%";
				$list['scale']=$result['scale']=="0%"? 0:$result['scale'];
			}
	return $list;
	}

/*****************销售返利*******************/
	public function time_sell_back($user_id,$star_time=0,$end_time=0){
		$model=M('order');
	$order=$model->field('qq_order_data.product_price,qq_order_data.number,qq_order_data.menber_rebate')->join("`qq_order_data` on `qq_order_data`.order_id=`qq_order`.id")->where(" `qq_order`.shop_id=".$user_id." and `qq_order`.status=2 and `qq_order`.pay_time between ".$star_time." and ".$end_time)->select();
	//获取订单中商品的总价和返利比率（不取当前商品表中的数据是因为这样计算出来的金额有可能跟消费的金额有误差）
	$sell_back=0;
	foreach ($order as $k => $v) {
		$sell_back+=intval($v['number'])*floatval($v['menber_rebate']);
	}
	return $sell_back;
}

/*****************下级返利*******************/

public function time_next_back($user_id,$star_time=0,$end_time=0){
		$sell_back=0;
			$order=M('order')->field(' `qq_order_data`.product_price,`qq_order_data`.number,`qq_order_data`.parent_rebate ')->join("`qq_order_data` on `qq_order_data`.order_id=`qq_order`.id")->where(" `qq_order`.parent_shopid=".$user_id." and `qq_order`.status=2 and `qq_order`.pay_time between ".$star_time." and ".$end_time)->select();
			foreach ($order as $k => $v) {
		$sell_back+=intval($v['number'])*floatval($v['parent_rebate']);
			}
	return $sell_back;
}
/**/
}
