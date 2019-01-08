<?php
namespace Admin\Controller;
use Think\Controller;
class CouponController extends CommController {
	
	public function index(){
		if(IS_POST){
			$id = (int)$_POST['id'];
			if($id === 0){
				$auto_increment = M()->query('SHOW TABLE STATUS where name="qq_co_coupon"');
				$auto_increment = $auto_increment[0]['Auto_increment'];
					//预留扩展多商家
				$_POST['gh_id'] = $this->current_user['id'];
				$_POST['href'] = '/index.php?a=show&m=Product&id='.($auto_increment+1);
				//$_SESSION['site_url'].U('Home/Coupon/show',array('id'=>$auto_increment+1));
			}
			$id = $this->createUpdate('co_coupon',$id);//已经判断过
			if($id){
				$this->success('操作成功！');
				exit;
			}
		}
		
		$count = M('co_coupon')->field('id')->where($this->where)->count();
		$p = new \Think\Page2($count,C('PAGE_COUNT')); 
		$tj['gh_id'] = session('uid');
		$list = M('co_coupon')->field('id,title,image,start_time,end_time,amount,status,listorder')->where($tj)->order('listorder desc')
			->limit($p->firstRow.','.$p->listRows)
			->select();
		
		$this->assign('list',$list);

		//print_r($list);
		$this->assign('page',$p->show());

		$tj2['id'] = $_GET['id'];
		$info = M('co_coupon')->field()->where($tj2)->order('listorder desc')->select();
		$this->assign('info',$info);
		//print_r($info);


		//$drawed = M('')
		$this->display();
	}

	public function del(){
		 $tj['id'] = $_GET['id'];
		 $return = M('co_coupon')->where($tj)->delete();
		  if($return){
				/*$this->success('操作成功！');*/
				echo '
				<script>
          			 window.location.href="/co/index.php/Coupon/index.shtml"; 
    			</script>';
				exit;
			}
	}


	public function help(){
		$this->display();
	}
}
