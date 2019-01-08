<?php
namespace Admin\Controller;
use Think\Controller;
class AccessController extends CommController {
	
	public function index(){
		$this->assign('group',$this->getGroup());
		$group_id = (int)$_GET['group_id'];
		if($group_id){
			$uid = M('user_group_association')->field('uid')->where(array('group_id'=>$group_id,'status'=>1))->select();
			$uid = multidimensional_2_unidimensional($uid);
			$this->where['id']=array('in',$uid);
			$this->assign('activeGroup',$group_id);
		}else{
			$this->assign('activeGroup',0);
		}
		$count = M('user')->field('id')->where($this->where)->count();
		$p = new \Think\Page($count,C('PAGE_COUNT')); 
		$list = M('user')->field('id,nickname,mobile,status,createtime')->where($this->where)->order('nickname asc')
			->limit($p->firstRow.','.$p->listRows)
			->select();
		$this->assign('list',$list);
		$this->assign('page',$p->show());
		$this->display();
	}
	/**
	  * 添加前台用户
	  * 修改用户密码作为一项重要权限，分离了密码修改功能到 userPassword
	  */
	public function userAdd(){
		if(IS_POST){
			$id = (int)$_POST['id'];
			$others = array('createtime'=>date('Y-m-d h:i:s',time()),'pwd'=>md5(123456));
			$id = $this->createUpdate('user',$id,$others);//已经判断过
			if($id==1){//修改，更新
				$uid = (int)$_POST['id'];
			}else{
				$uid = $id;
			}
			$this->dataButt($_POST['gids'],$uid);
		}
		$id = (int)$_GET['id'];
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	//	//这里不支持一个用户属于多个组									//
	//	//前端使用 checkBox,后端不使用join而是分别查询，可以解决此问题					//
	//	$info = M('user')->alias('u')->field('u.`id`,nickname,email,status,createtime,group_id')	//
	//		->join('__USER_GROUP_ASSOCIATION__ g on u.`id`=g.`uid`','left')				//
	//		->where(array('status'=>array('in','0,1'),'id'=>$id))					//
	//		->find();										//
	//////////////////////////////////////////////////////////////////////////////////////////////////////////		
		$this->where['id'] = $id;
		$info = M('user')->field('id,nickname,email,status,createtime')->where($this->where)->find();
		$userGroup = M('user_group_association')->field('group_id')->where(array('uid'=>$id,'status'=>1))->select();
		$userGroup = multidimensional_2_unidimensional($userGroup,'group_id');
		$info['group'] = $userGroup;
		$this->initWhere();
		$this->assign('group',$this->getGroup());
		$this->assign('info',$info);
		$this->display();
	}
	public function userPassword(){
		if(IS_POST){
			$id = (int)$_POST['id'];
			$_POST['pwd'] = md5($_POST['pwd']);
			$id = $this->createUpdate('user',$id);
			$this->success('操作成功！');
			exit;

		}else{
			$id = (int)$_GET['id'];
			if($id===0){
				$this->error('请先选择用户！');
			}
			$this->where['id'] = $id;
			$info = M('user')->field('id,nickname,email')->where($this->where)->find();
			$this->assign('info',$info);
		}
		$this->display();
		
	}

	/**
	  * 软删除用户
	  */
	public function userDel(){
		$id = (int)$_GET['id'];
		$this->delete('user',$id);
		header('Location:'.U('Access/index'));
	}
	public function group(){
		if(IS_POST){
			$id = $_POST['id'];
			$this->createUpdate('user_group',$id);
		}
		$group = M('user_group')->field('id,title,status')->where($this->where)->order('listorder desc')->select();
		$this->assign('group',$group);
		$id = (int)$_GET['id'];

		$this->where['id'] = $id;
		$info = M('user_group')->field('id,title,status,listorder')->where($this->where)->find();
		$this->assign('info',$info);

		$uid = M('user')->field('id')->select();
		$uid = multidimensional_2_unidimensional($uid,'id');
		$flotsam = M('user_group_association')->field('uid')->where(array('uid'=>array('not in',$uid)))->count();
		$this->assign('flotsam',$flotsam);
		$this->display();
	}
	
	/**
	  * 清空流离的 用户-组 中间表
	  * 硬删除用户后，优化
	  */
	public function groupClearFlotsam(){
		$uid = M('user')->field('id')->select();
		$uid = multidimensional_2_unidimensional($uid,'id');
		$flotsam = M('user_group_association')->field('uid')->where(array('uid'=>array('not in',$uid)))->count();
		M('user_group_association')->where(array('uid'=>array('not in',$uid)))->delete();
		$count = M('user_group_association')->field('uid')->where(array('uid'=>array('not in',$uid)))->count();
		if($count<$flotsam){
			$this->success('恭喜您，清理了'.($flotsam-$count).'项垃圾！');
			exit;
		}
		header('Location:'.U('Access/group'));
	}
	public function groupDel(){
		$id = (int)$_GET['id'];
		$this->delete('user_group',$id);
		header('Location:'.U('Access/group'));
	}

	public function nodeAdd(){
		if(IS_POST){
			$id = (int)$_POST['id'];
			$id = $this->createUpdate('node',$id);
			if($id){
				$this->success('操作成功！');
				exit;
			}
		}
		$id = (int)$_GET['id'];
		$info = M('node')->field('id,pid,name,title,listorder,status')->where(array('status'=>array('in','0,1'),'id'=>$id))->find();
		$nodes = $this->getNodeList();
		$this->assign('nodeList',frequent_tree2list($nodes));
		$this->assign('info',$info);
		$this->display();
	}

	/**
	  * 节点列表
	  * 使用了通用缩进函数
	  */
	public function nodeList(){
		$nodes = $this->getNodeList();
		$nodes = frequent_tree2list($nodes,'',$icon=array('<span style="display:inline-block;width:20px;"></span>│', '<span style="display:inline-block;width:20px;"></span>├ ', '<span style="display:inline-block;width:20px;"></span>└ '),$prefix='');
		$this->assign('list',$nodes);
		$this->display();
	}
	public function nodeDel(){
		$id = (int)$_GET['id'];
		$this->delete('node',$id);
		header('Location:'.U('Access/nodeList'));
	}
	
}
