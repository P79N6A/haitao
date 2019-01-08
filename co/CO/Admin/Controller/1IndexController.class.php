<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends CommController {
	public function index(){
		$this->where['id'] = session('uid');
		$info  = M('user')->field('id,groupid,shop_name,store_image,shop_hours,shop_about,outside_order,leixing,sjfenlei,pb_typel')->where($this->where)->find();
		$this->assign('info',$info);
		//print_r($info);

		//商家类型
		$tj['keyid']=8;
		$tj['status']=1;
		$sj_leixing = M('type')->field('typeid,name')->where($tj)->select();
		$this->assign('sj_leixing',$sj_leixing);


		//商家分类
		$tj['keyid']=11;
		$tj['status']=1;
		$sj_fenlei = M('type')->field('typeid,name')->where($tj)->select();
		$this->assign('sj_fenlei',$sj_fenlei);




		if(IS_POST){
			//print_r($_POST['outside_order']);exit;
			$id = (int)$_POST['id'];
			$_POST['outside_order'] = $_POST['outside_order'] ? 1 : 0;
			$outside_order =(int)$_POST['outside_order'];

			$return = $this->createUpdate('user',$id);
			if($return){
				$this->success('操作成功！');
				/*echo '
				<script>alert("操作成功");
          			 window.location.href="/co/index.php/Index/index.shtml"; 
    			</script>';*/
				exit;
			
			}	

		}
		
		$this->display();
	}

	public function code(){
		
		$this->display();
	}
}
