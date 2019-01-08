<?php
namespace Admin\Controller;
use Think\Controller;
class AjaxController extends CommController {
	public function uploadOne(){
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize   =     512000 ;// 设置附件上传大小:500K
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->rootPath  =	'/Uploads';
			// 设置附件上传目录:
			//$this->where['uid'] = session('uid');
			//分文件夹 存储
			//$gh_id = M('gh')->field('gh_id')->where($this->where)->find();一直到Qqcms项目 的co 
		//$upload->savePath  =     '/'.$gh_id['gh_id'].'/'; 
		$upload->savePath  =     '/'.session('uid').'/'; 
		$upload->autoSub =	false;
		$info   =   $upload->uploadOne($_FILES['image']);// 上传单个文件 
		if(!$info) {
			$return = array('status'=>0,'info'=>$upload->getError());
		}else{
			$return = array('status'=>1,
					'info'=>C('SITE_URL').$upload->rootPath.$upload->savePath.$info['savename']);
			//项目仅使用本地上传
			//echo $info['savepath'].$info['savename'];
		}
		$return = json_encode($return);
		$return = frequent_unicode_decode_json($return);
		
		//$retrun = preg_replace("/\/",'/',$return);
		echo "<script>parent.imageUploader.uploadCallback(".$return.",false)</script>"; 
	}

	public function catch_colu($pc=false){
		if ($_POST['pc'])
		{
			$pc = $_POST['pc'];
		}
		$columntype_id=$_REQUEST['columntype_id']? $_REQUEST['columntype_id']:0;
		$uid=$_SESSION['uid'];
		$res_str="";
		$tab1 = 'qq_slide_data';
		$tab2 = 'qq_slide';
		$tab3 = 'qq_shopcolumn_type';
		$tab4 = 'qq_shopcolumn';
		if ($pc)
		{
			$tab1 = 'qq_pcslide_data';
			$tab2 = 'qq_pcslide';
			$tab3 = 'qq_shopcolumnpc_type';
			$tab4 = 'qq_shopcolumnpc';
		}
		if($columntype_id){
			/*找出该栏目下所有适合的图片*/
			$sql="SELECT a.* from ".$tab1." as a left join ".$tab2." as b on a.fid =b.id left join ".$tab3." as c on c.slide_id=b.id where c.id=".$columntype_id." and a.status=1";
			$query=mysql_query($sql);
			$img_res=array();
			while ($row = mysql_fetch_assoc($query)) {
				$img_res[]=$row;
			}
			/*找出该栏目已选图片*/
			if($columntype_id){
				$sql="SELECT b.* from ".$tab4." as a left join ".$tab1." as b on a.slid_data_id=b.id where a.columntype_id=".$columntype_id." and a.uid=".$uid;
				$query=mysql_query($sql);
				$this_img=mysql_fetch_assoc($query);
			}
			$res_str.="<ul style='padding:10px 0px 10px 0px;'><li>已选广告：<img src='".$this_img['pic']."'/></li></ul>";
			$res_str.="<div style='width:97%;border-top:1px dashed #C2BFBF;padding:3px 0px 3px 0px;margin:auto'></div><ul id='select_ul' style='overflow:hidden;margin-auto;'>";
			foreach ($img_res as $k => $v) {
				$res_str.="<li onclick='select_info(this,".$v['id'].",\"".$v['link']."\")'  style='text-align:center;width:13%;float:left;margin:5px 1% 5px 1%;padding:3px 0px 3px 0px'><img style='width:100%;height:100px' src='".$v['pic']."'/><p style='padding:3px 0px 3px 0px;'>".$v['title']."</p></li>";
			}
			$res_str.="</ul>";
		}
			echo $res_str;exit();
	}

	function column_edit($pc=false){
		if ($_POST['pc'])
		{
			$pc=$_POST['pc'];	
		}
		$tab= 'shopcolumn';
		if ($pc)
		{
			$tab= 'shopcolumnpc';
		}
		$columntype_id=$_REQUEST['columntype_id']? intval($_REQUEST['columntype_id']):0;
		$slid_data_id=$_REQUEST['slid_data_id']? intval($_REQUEST['slid_data_id']):0;
		$uid=$_SESSION['uid'];

		$info=M($tab);
		$res=$info->where("columntype_id=".$columntype_id." and uid=".$uid)->find();
		$data['slid_data_id']=$slid_data_id;
		if($res){
			$qe=$info->where('id='.$res['id'])->save($data);

		}else{
			$data['columntype_id']=$columntype_id;
			$data['uid']=$uid;
			$qe=$info->add($data);
		}
		$qe=$qe? 1:0;
		echo $qe;exit();
	}
	
}
