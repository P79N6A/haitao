<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends CommController {
	public function index(){
		if ($_GET['code'])
		{
			print_r($_REQUEST);exit;
		}
		else
		{
			$info  = M('user')->field(" qq_user.*,qq_role.name as groupname ")->join(" `qq_role` on qq_role.id=qq_user.groupid ")->where(" qq_user.id=".session('uid'))->find();
			$list[0]=$info;
			$list=$this->get_info($list);//获取上级、管理年费、押金
			$list=$this->get_fee($list);//获取自消费、客户消费以及3种返利和下级平台管理分润
			$list=$this->next_num($list);
			$list=$this->month_fee($list);//每月利润
			$info=array();
			$info=$list[0];
			/*
			$groupname=M('role')->field('name')->where('id='.$info['groupid'])->find();
			$info['groupname']=$groupname['name'];*/
			// print_r($info);exit;
			$this->assign('user',$info);
			if(IS_POST){
				$id = (int)$_POST['id'];
				$_POST['outside_order'] = $_POST['outside_order'] ? 1 : 0;
				$outside_order =(int)$_POST['outside_order'];
				$return = $this->createUpdate('user',$id);
				if($return){
					/*$this->success('操作成功！');*/
					echo '<script>alert("操作成功");
	          			 window.location.href="/co/index.php/Index/index.shtml"; 
	    			</script>';
					exit;
				}	
			}
			$this->display();
		}
	}


	public function change_password(){
		if(isset($_POST['sub'])){
			$id['id']=session('uid');
			if(!empty($_POST['pass'])){
				$pass=trim($_POST['pass']);
				$data["password"]=$this->sysmd5($pass);
			}
			if(!empty($_POST['shop_name'])){
				$data["shop_name"]=trim($_POST['shop_name']);
			}
			$info = M('user')->where($id)->save($data);
			echo "<script>alert('修改成功！');location.href='/co/index.php';</script>";exit();
		}

		$info  = M('user')->field("shop_name")->where("id=".session('uid'))->find();
		$this->assign("info",$info);
		$this->display();
	} 
	public function code(){
		$uid=session('uid');
		$modle=M("qrcode_article");
		if(!empty($_POST['submit'])){
			$data['page_title']=htmlspecialchars($_POST['page_title']);
			$data['title']=htmlspecialchars($_POST['title']);
			$data['content']=htmlspecialchars($_POST['content']);
			$data['video']=urlencode($_POST['video']);
			$res=$modle->field("id")->where("userid=".$uid)->find();
			if($res){
			$data['id']=$res['id'];
			$data['updatetime']=mktime();
			$modle->save($data);
			$this->success("修改成功");exit();
			}
			$data['userid']=$uid;
			$data['createtime']=mktime();
			$modle->add($data);
			$this->success("提交成功");exit();

		}
		$info=$modle->where("userid=".$uid)->find();
		$this->assign("info",$info);
		$this->display();
	}
}
