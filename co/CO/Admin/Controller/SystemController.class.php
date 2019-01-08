<?php
namespace Admin\Controller;
use Think\Controller;
class SystemController extends CommController {
	public function index(){
		if(IS_POST){
			foreach($_POST as $k=>$v){
				M('config')->where(array('varName'=>$k))->data(array('value'=>$v))->save();
			}
		}
		$list = M('config')->field('varName,name,value')->where(array('status'=>1,'isConst'=>0))->select();
		$this->assign('list',$list);
		$this->display();
	}
	public function setconst(){
		if(IS_POST){
			foreach($_POST as $k=>$v){
				M('config')->where(array('varName'=>$k))->data(array('value'=>$v))->save();
			}
		}
		$list = M('config')->field('varName,name,value')->where(array('status'=>1,'isConst'=>1))->select();
		$this->assign('list',$list);
		foreach($list as $v){
			$distanceConst[$v['varName']]=$v['value'];
		}
		$localConst = include(APP_PATH.'/Common/Conf/const.php');
		if(array_diff_assoc($distanceConst,$localConst)){
			$this->assign('isConstChanged',true);
		}
		$this->display();
	}
	public function updateConst(){
		$list = M('config')->field('varName,name,value')->where(array('status'=>1,'isConst'=>1))->select();
		foreach($list as $v){
			$distanceConst[$v['varName']]=$v['value'];
		}
		$str = "<?php".PHP_EOL."return ".var_export($distanceConst,true).';';
			file_put_contents(APP_PATH.'/Common/Conf/const.php',$str,true);
		$this->success('操作成功',U('System/setconst'));
			exit;
	}
	public function settime(){
		if(IS_POST){
			foreach($_POST['times'] as $v){
				$v = preg_replace("/.*([0-2]\d:[0-6]\d).*/",'\1',$v);
				preg_match("/[0-2]\d:[0-6]\d/",$v,$match);
				if($match){
					$pass[] = $v;
				}
			}
			sort($pass);//删除原有的键名
			$str = "<?php".PHP_EOL."return ".var_export($pass,true).';';
			file_put_contents(APP_PATH.'/Common/Conf/settime.php',$str,true);
			$this->success('操作成功');
			exit;
		}
		$list = include_once(APP_PATH.'/Common/Conf/settime.php');	
		$this->assign('list',$list);
		$this->display();
	}	
	/**
	 * 添加学校 分区 宿舍楼信息，enum类型
	 */
	public function school(){
		if(IS_POST){
			$id = (int)$_POST['id'];
			$id = $this->createUpdate('school',$id);
			if($id){
				$this->success('操作成功！');
				exit;
			}				
		}	
		$id = (int)$_GET['id'];
		$list = M('school')->field('id,title,pid,status,type,listorder')->where($this->where)->order('listorder desc')->select();
		$list = frequent_infinite_category($list);//树形结构
		$list = frequent_tree2list($list);
		$this->assign('list',$list);
		$this->where['id'] = $id;
		$info = M('school')->field('id,type,pid,title,status,listorder')->where($this->where)->find();
		$this->assign('info',$info);
		$this->display();
	}
	public function schoolDel(){
		$id = (int)$_GET['id'];
		$this->delete('school',$id);
		header('Location:'.U('System/school'));
	}
	public function express(){
		if(IS_POST){
			$id = (int)$_POST['id'];
			$id = $this->createUpdate('express',$id);
			if($id){
				$this->success('操作成功！');
				exit;
			}				
		}
		$count = M('express')->field('id')->where($this->where)->count();
		$p = new \Think\Page($count,C('PAGE_COUNT')); 
		$list = M('express')->field('id,title,status,listorder')->where($this->where)->order('listorder desc')
			->limit($p->firstRow.','.$p->listRows)
			->select();
		$this->assign('list',$list);
		$this->assign('page',$p->show());

		$id = (int)$_GET['id'];
		$this->where['id'] = $id;
		$info = M('express')->field('id,title,status,listorder')->where($this->where)->order('listorder desc')->find();
		$this->assign('info',$info);
		$this->display();
	}
	public function expressDel(){
		$id = (int)$_GET['id'];
		$this->delete('express',$id);
		header('Location:'.U('System/express'));
	}

	/*
	 * 订单状态确定之后只读，否则严重干扰逻辑。
	 * 他是许多逻辑的基础
	 */
	public function orderStatus(){
		$orderStatus = include_once(APP_PATH.'/Common/Conf/orderStatus.php');	
		foreach ($orderStatus as $k=>$v){
			$list[] = array('id'=>$k,'value'=>$v);
		}
		$this->assign('list',$list);
		$this->display();
	}
}
