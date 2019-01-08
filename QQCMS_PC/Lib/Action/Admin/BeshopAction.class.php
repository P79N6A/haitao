<?php

/**
 * 申请经营者列表
 */

class BeshopAction extends AdminbaseAction
{
	public function index(){
		import ( '@.ORG.Page' );
		$count=M('beshop')->count();
		$page=new Page($count,15);
		$show=$page->show();
		$list=M("beshop")->order('id desc')->limit($page->firstRow.",".$page->listRows)->select();
		$this->assign("page",$show);
		$this->assign("list",$list);
		$this->display();
	}

	public function edit(){
		$id=$_GET['id'];
		$info=M('beshop')->where('id='.$id)->find();
		if(!$info){
			$this->error("没有这条申请");exit();
		}
		$user=M('user')->field("parent_id,wechat_name")->where("id=".$info['userid'])->find();
		$parent=M('user')->field("realname")->where("id=".$user['parent_id'])->find();
		$info['wechat_name']=$user['wechat_name'];
		$info['parent']=$parent['realname'];
		$area=M('area')->getfield("id,name");
		$this->assign("area",$area);
		$this->assign("info",$info);
		$this->display();
	}
	public function checked(){
		$id=$_GET['id']?intval($_GET['id']):0;
		$res=M('beshop')->where("id=".$id)->find();
		if($res){
			$data['id']=$res['userid'];
			$data['beshop_time']=mktime();
			$data['shop_name']=$res['shop_name'];
			$data['realname']=$res['real_name'];
			$data['email']=$res['email'];
			$data['groupid']=7;//变为A0店
			$data['test_status']=1;
			$uu=M("user")->save($data);
			if($uu){
				$con['id']=$res['id'];
				$con['status']=1;
				$res=M('beshop')->save($con);
			}
			$this->success("操作成功");
		}
		else {$this->error("操作失败");}
	}
	public function unchecked(){
		$id=$_GET['id']?intval($_GET['id']):0;
		$res=M('beshop')->find($id);
		if($res){
			$data['id']=$res['userid'];
			$data['groupid']=3;//变为A0店
			$uu=M("user")->save($data);
			if($uu){
				$con['id']=$res['id'];
				$con['status']=0;
				$res=M('beshop')->save($con);
			}
			$this->success("操作成功");
		}
		else {$this->error("操作失败");}
	}
public function delete(){
		$id=$_GET['id'];
		$beshop=M('beshop');
		if(false!==$beshop->delete($id)){

			$this->success(L('delete_ok'));
		}else{
			$this->error(L('delete_error'));
		}
	}
}
?>