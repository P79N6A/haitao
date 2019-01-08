<?php
namespace Admin\Controller;
use Think\Controller;
class UserController extends CommController {

	public function index(){
		$id = session('uid');
		$user=M('user');
		$count=$user->where("parent_id=".$id." and groupid >2 and groupid <5")->count();
		$page = new \Think\Page2($count,15); 
		$show=$page->show();
		$this->assign("page",$show);
		$list=$user->field(" qq_user.*,qq_role.name as groupname ")->join(" `qq_role` on qq_user.groupid=qq_role.id ")->where("qq_user.parent_id=".$id." and qq_user.groupid >2 and qq_user.groupid <5")->limit($page->firstRow.','.$page->listRows)->order('qq_user.id desc')->select();

		$list=$this->get_info($list);//获取上级、管理年费、押金
		$list=$this->get_menber_fee($list);//获取消费金额
		$list=$this->take_menber_status($list);//检查会员年费缴纳状态
		$this->assign('ulist',$list);
		$this->display();
	}

	public function shop(){
		$id = session('uid');
		$user=M('user');
		$count=$user->where("parent_id=".$id." and groupid>6 and groupid <13")->count();
		$page = new \Think\Page2($count,15); 
		$show=$page->show();
		$this->assign("page",$show);
		$list=$user->field(" qq_user.*,qq_role.name as groupname ")->join(" `qq_role` on qq_user.groupid=qq_role.id ")->where(" qq_user.parent_id=".$id." and qq_user.groupid>6 and qq_user.groupid <13")->limit($page->firstRow.','.$page->listRows)->order('qq_user.id desc')->select();

		$list=$this->get_info($list);//获取上级、管理年费、押金
		$list=$this->get_fee($list);//获取自消费、客户消费以及3种返利和下级平台管理分润

		$list=$this->take_status($list);//检查试运营与年费缴纳状态
		$this->assign('ulist',$list);
		$this->display();
	}

	public function edit(){
		if($_GET['id']){
			$id=!empty($_GET['id'])? $_GET['id']:"";

		$user=M('user');
		$user_info=$user->where("id=".$id)->find();
		$this->assign('user_list',$user_info);
		/*查找地址*/
		$province=M('area')->field("id,name")->where('id='.$user_info['province'])->find();
		$city=M('area')->field("id,name")->where('id='.$user_info['city'])->find();
		$area=M('area')->field("id,name")->where('id='.$user_info['area'])->find();
		$adress=array_merge_recursive($province,$city,$area);
		/*当是微店时*/
		if($user_info['groupid']>5 and $user_info['groupid']<14 ){
		$shop_list=$this->get_next_shop($id);//获取下级微店列表
		$menber_list=$this->get_next_menber($id);//获取下级会员
			/*获取销售额*/
		$list[0]=$user_info;
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
		$usergroup=M('Role')->select();
		$this->assign('rlist',$usergroup);	

}
////////////////////////////
$this->display();
	}


}
