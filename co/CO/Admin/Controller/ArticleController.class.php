<?php
namespace Admin\Controller;
use Think\Controller;
class ArticleController extends CommController {

	public function index(){
		$this->where['userid'] = session('uid');
		$count = M('article')->field('id')->where($this->where)->count();
		$p = new \Think\Page2($count,20); 
		$prolist = M('article')->field('id,title,hits,createtime')->where($this->where)->order('id desc')
			->limit($p->firstRow.','.$p->listRows)
			->select();

		$this->assign('pager',$p->show());
		$this->assign('prolist',$prolist);
		$this->display();
	}


	public function add(){

		if(IS_POST){
			$id = (int)$_POST['id'];
			$return = $this->createUpdate('article',$id);
			//print_r($return);exit;
			if($return){
				/*$this->success('操作成功！');*/
				echo '
				<script>
					 window.location.href="/co/index.php/article/index.shtml"; 
    			</script>';
				exit;
			}	

		}


		$tj['userid'] = session('uid');
		$fenlei  = M('product_category')->field('id,title')->where($tj)->select();
		$this->assign('fenlei',$fenlei);


		$tj2['id'] = session('uid');
		$yonhuming  = M('user')->field('id,username')->where($tj2)->select();
		
		$u_name = $yonhuming['0']['username'];
		
		$this->assign('u_name',$u_name);
		//print_r($u_name);

		$this->display();
	}

	
	public function edit(){

		$this->where['id'] = $_GET['id'];
		$info  = M('article')->field()->where($this->where)->find();
		$this->assign('info',$info);
		//print_r($info);
		if(IS_POST){
			$id = (int)$_POST['id'];
			$return = $this->createUpdate('article',$id);
			if($return){
				/*$this->success('操作成功！');*/
				echo '
				<script>
          			 window.location.href="/co/index.php/article/index.shtml"; 
    			</script>';
				exit;
			}	

		}
		
	

		$this->display();
	}


		public function del(){
			//echo 123 ;
			 $tj['id'] = $_GET['id'];
			 $return=M('article')->where($tj)->delete();
			 if($return){
				/*$this->success('操作成功！');*/
				echo '
				<script>
          			 window.location.href="/co/index.php/Article/index.shtml"; 
    			</script>';
				exit;
			}
			
		}


	


}
