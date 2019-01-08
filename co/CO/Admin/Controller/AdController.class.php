<?php
namespace Admin\Controller;
use Think\Controller;
class AdController extends CommController {

	/*++++++手机端+++++++*/
	public function index(){
		$uid=$_SESSION['uid']? $_SESSION['uid']:0;
		if($_POST["sub"]){
			$data['userid']=$uid;
			$data["pic"]=$_POST['pic'];
			$pic=M("shop_pic")->field("id")->where("userid=".$data['userid'])->find();
			if($pic){
				$data["id"]=$pic['id'];
				$res=M("shop_pic")->save($data);
			}else{
				$res=M("shop_pic")->add($data);
			}
			if($res)$this->success("保存成功");else $this->error("保存失败");exit();
		}
		$private_pic=M("shop_pic")->where("userid={$uid}")->find();
		$this->assign('private_pic',$private_pic['pic']);
		$column_type=M('shopcolumn_type')->select();
		$this->get_offic_column();//获取官方栏目
		$this->get_column($column_type);//获取微店栏目
		$this->assign('column_type',$column_type);
		$this->display();
	}
	
	
	/*++++++PC端+++++++*/
	public function index_m(){
		$uid=$_SESSION['uid']? $_SESSION['uid']:0;
		
		if($_POST["sub"]){
			$data['userid']=$uid;
			$data["pic"]=$_POST['pic'];
			$pic=M("shop_pic")->field("id")->where("userid=".$data['userid'])->find();
			if($pic){
				$data["id"]=$pic['id'];
				$res=M("shop_pic")->save($data);
			}else{
				$res=M("shop_pic")->add($data);
			}
			if($res)$this->success("保存成功");else $this->error("保存失败");exit();
		}
		$private_pic=M("shop_pic")->where("userid={$uid}")->find();
		$this->assign('private_pic',$private_pic['pic']);
		$column_type=M('shopcolumnpc_type')->where('status=1')->select();
		$this->get_offic_column('pcslide_data');//获取官方栏目
		$this->get_column($column_type,true);//获取微店栏目
		$this->assign('column_type',$column_type);
		$this->display();
	}


 	//获取微店栏目
 	public function get_column($column_type=array(),$pc = false){
 		$shop_id=$_SESSION['uid']? $_SESSION['uid']:0;
		$table1 = 'slide_data';
		$table2 = 'qq_shopcolumn';
		$table_1 = 'qq_slide_data';
		if ($pc)
		{
			$table1 = 'pcslide_data';
			$table2 = 'qq_shopcolumnpc';
			$table_1 = 'qq_pcslide_data';
		}
 		$column=M($table1)->field(' '.$table2.'.*,'.$table_1.'.pic,'.$table_1.'.link ')->join(' '.$table2.' on '.$table2.'.slid_data_id='.$table_1.'.id ')->where(" ".$table2.".uid=".$shop_id)->select();
		/*echo M($table1)->getLastSql();exit;
		print_r($column);exit;*/
 		foreach ($column_type as $key => $value) {
 			$column_type[$key]['pic']="";
 			$column_type[$key]['url']="";
 			foreach ($column as $k => $v) {
 				if($value['id']==$v['columntype_id']){
 					$column_type[$key]['pic']=$v['pic']?$v['pic']:"";
 					$column_type[$key]['url']=$v['link']?$v['link']:"";
 				}
 			}

 		}
		$this->assign("column",$column_type);
		return true;
 	}
 	//获取官方栏目
 	public function get_offic_column($table = 'slide_data'){
		$offic_column=M($table)->where('fid > 4 and fid <11')->order('fid asc')->select();
		$this->assign("offic_column",$offic_column);
		return true;
 	}
	public function column_edit(){
			$columntype_id=$_REQUEST['columntype_id']? $_REQUEST['columntype_id']:0;
			$uid=$_SESSION['uid']? $_SESSION['uid']:0;

			/*查找栏目标题*/
			$slide_data=M('slide_data')->field(' `qq_slide_data`.title ')->join(' `qq_shopcolumn` on `qq_shopcolumn`.slid_data_id=`qq_slide_data`.id ')->where(' `qq_shopcolumn`.columntype_id='.$columntype_id.' and `qq_shopcolumn`.uid='.$uid)->find();
			$this->assign("slide_data",$slide_data);
			
			/*找出已选商品*/
			$sql="SELECT a.*,b.id as col_id from `qq_product` as a left join `qq_shopcolumn_data` as b on a.id=b.goods_id left join `qq_shopcolumn` as c on c.slid_data_id=b.slide_data_id  where c.columntype_id=".$columntype_id." and c.uid=".$uid;
			$query=mysql_query($sql);
			$has_info=array();
			while ($row = mysql_fetch_assoc($query)) {
				$has_info[]=$row;
			}
			$this->assign('has_info',$has_info);

			$this->display();
		}
	

	


}
