<?php 
/*
*ActivityAction.class.php(抽奖表单)
*
*/
if(!defined("QQCMS")) exit("Access Denied"); 
class ActivityAction extends AdminbaseAction
{
	public function index(){
		import ( '@.ORG.Page' );
		if(isset($_GET['is_menber'])){
			if(intval($_GET['is_menber'])==1){
				$where['user_id']=array("gt",0);
				$get_menber=1;
			}else{
				$where['user_id']=0;
				$get_menber=99;
			}
		}
		if(isset($_GET['get_time'])){
			if(intval($_GET['get_time']==1)){
				$order=" createtime desc";
				$get_time=99;
			}else{
				$order=" createtime asc";
				$get_time=1;
			}
		}else{
			
				$order=" createtime desc";
				$get_time=99;
		}
		if(isset($_GET['typeid'])){
			$where['typeid']=intval($_GET['typeid']);
		}
		M("activity")->where($where)->count();
		$page=new Page($count,15);
		$show=$page->show();
		$this->assign("page",$show);
		$activity=M("activity")->where($where)->order($order)->limit($page->firstRow.','.$page->listRows)->select();
		foreach ($activity as $key => $value) {
			$temp=array();
			$temp=M("type")->field("name")->where("typeid=".$value['typeid'])->find();
			$activity[$key]['type_name']=$temp['name'];
		}
		$typelist=M("type")->field("typeid,name")->where("parentid=38")->select();
		//var_dump($typelist);exit();
		$this->assign("typelist",$typelist);
		$area=M("area")->getfield("id,name");
		$this->assign("list",$activity);
		$this->assign("area",$area);
		$this->assign("get_menber",$get_menber);
		$this->assign("get_time",$get_time);
		$this->display();
	}

}
?>