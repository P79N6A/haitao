<?php
namespace Admin\Controller;
use Think\Controller;
class ProductController extends CommController {

	public function index(){
		$this->where['userid'] = session('uid');
		$count = M('product')->field('id')->where($this->where)->count();
		$p = new \Think\Page2($count,20); 
		$prolist = M('product')->field('id,title,hits,status,createtime,member_price,listorder,thumb,address,en_name,liquid,vol,price,private_status')->where($this->where)->order('listorder desc,id desc')->limit($p->firstRow.','.$p->listRows)->select();
		$this->assign('pager',$p->show());
		$this->assign('prolist',$prolist);
		$this->display();
	}


	public function add(){
		
		if(IS_POST){

			$id = (int)$_POST['id'];
			$_POST['top_pics']=implode(":::",$_POST['top_pics']);
			$others['catid']=25;
			$others['status']=0;
			$return = $this->createUpdate('product',$id,$others);
			//print_r($return);exit;
			if($return){
				/*$this->success('操作成功！');*/
				echo '
				<script>
          			 alert("提交成功，请耐心等候公司审核");window.location.href="/co/index.php/Product/index.shtml"; 
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
		$info  = M('product')->field()->where($this->where)->find();
		$info["top_pics"]=explode(":::",$info["top_pics"]);
		$this->assign('info',$info);
		//print_r($info);
		if(IS_POST){
			$_POST['top_pics']=implode(":::",$_POST['top_pics']);
			$id = (int)$_POST['id'];
			$others['status']=0;
			$return = $this->createUpdate('product',$id,$others);
			if($return){
				/*$this->success('操作成功！');*/
				echo '
				<script>
          			 alert("修改成功，请耐心等候公司审核");window.location.href="/co/index.php/Product/edit.shtml?id='.$_GET['id'].'"; 
    			</script>';
				exit;
			}

		}
		
		$tj['userid'] = session('uid');
		$fenlei  = M('product_category')->field('id,title')->where($tj)->select();
		$this->assign('fenlei',$fenlei);
		//print_r($list);

		$this->display();
	}


		public function del(){
			//echo 123 ;
			 $tj['id'] = $_GET['id'];
			 $return=M('product')->where($tj)->delete();
			 if($return){
				/*$this->success('操作成功！');*/
				echo '
				<script>
          			 window.location.href="/co/index.php/Product/index.shtml"; 
    			</script>';
				exit;
			}
			
		}


	public function procat(){
		$this->where['userid'] = session('uid');
		$count = M('product')->field('id')->where($this->where)->count();
		$p = new \Think\Page($count,20); 
		$prolist = M('product_category')->field('id,title,createtime')->where($this->where)->order('id desc')
			->limit($p->firstRow.','.$p->listRows)
			->select();

		$this->assign('pager',$p->show());
		$this->assign('prolist',$prolist);
		$this->display();
	}

		public function add_cat(){

		if(IS_POST){
			$id = (int)$_POST['id'];
			$return = $this->createUpdate('product_category',$id);
			//print_r($return);exit;
			if($return){
				/*$this->success('操作成功！');*/
				echo '
				<script>
          			 window.location.href="/co/index.php/Product/procat.shtml"; 
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

	public function edit_cat(){

		$this->where['id'] = $_GET['id'];
		$info  = M('product_category')->field()->where($this->where)->find();
		$this->assign('info',$info);
		//print_r($info);
		if(IS_POST){
			$id = (int)$_POST['id'];
			$return = $this->createUpdate('product_category',$id);
			if($return){
				/*$this->success('操作成功！');*/
				echo '
				<script>
          			 window.location.href="/co/index.php/Product/edit_cat.shtml?id='.$_GET['id'].'"; 
    			</script>';
				exit;
			}	

		}
		
		$tj['userid'] = session('uid');
		$fenlei  = M('product_category')->field('id,title')->where($tj)->select();
		$this->assign('fenlei',$fenlei);
		//print_r($list);

		$this->display();
	}

	public function del_cat(){
			//echo 123 ;
			 $tj['id'] = $_GET['id'];
			 $return=M('product_category')->where($tj)->delete();
			 if($return){
				/*$this->success('操作成功！');*/
				echo '
				<script>
          			 window.location.href="/co/index.php/Product/procat.shtml"; 
    			</script>';
				exit;
			}
			
		}
	public function edit_status(){
		$uid=session('uid');
		$id=$_REQUEST['id']?intval($_REQUEST['id']):0;
		$status=$_REQUEST['type']?intval($_REQUEST['type']):0;
		$product=M("product")->field("id")->where("id={$id} and userid={$uid}")->find();
		if(empty($product)){
			$this->error("抱歉,您没有该商品");exit();
		}
		$con['id']=$product['id'];
		$con['private_status']=$status;
		M("product")->save($con);
		$this->success("操作成功！");exit();
	}


}
