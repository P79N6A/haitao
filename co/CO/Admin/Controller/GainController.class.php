<?php
namespace Admin\Controller;
use Think\Controller;
class GainController extends CommController {

	public function index(){
		$where['catid']=$_REQUEST['catid']?intval($_REQUEST['catid']):5;
		$count = M('product')->where($where)->count();
		$p = new \Think\Page2($count,20); 		
		$prolist = M('product')->where($where)->order('listorder desc,id desc')->limit($p->firstRow.','.$p->listRows)->select();
		$this->assign('pager',$p->show());
		$this->assign('prolist',$prolist);
		$this->display();
	}

}
