<?php
/**
 * 
 * AreaAction.class.php (ajax 获取地址)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if(!defined("QQCMS")) exit("Access Denied"); 
class AjaxAction extends BaseAction
{
    public function index()
    {
	 exit;
    }
    public function area()
    {
		$module = M('Area');
		$id = intval($_REQUEST['id']);
		$level= intval($_REQUEST['level']);
		$provinceid= intval($_REQUEST['provinceid']);
		$cityid= intval($_REQUEST['cityid']);
		$areaid= intval($_REQUEST['areaid']);
		
 		
		$province_str='<option value="0">请选择省份...</option>';
		$city_str='<option value="0">请选择城市...</option>';
		$area_str='<option value="0">请选择区域...</option>';
		$str ='';

		$r = $module->where("parentid=".$id)->select();	 		
		foreach($r as $key=>$pro){
			$selected = ( $pro['id']==$provinceid) ? ' selected="selected" ' : '';
			$str .='<option value="'.$pro['id'].'"'.$selected.'>'.$pro['name'].'</option>';
		}
		if($level==0){
			$province_str .=$str;
		}elseif($level==1){
			$city_str .=$str;
		}elseif($level==2){
			$area_str .=$str;
		}
		$str='';
		if($provinceid){
			
			$rr = $module->where("parentid=".$provinceid)->select();	 		
			foreach($rr as $key=>$pro){
				$selected = ($pro['id']==$cityid) ? ' selected="selected" ' : '';
				$str .='<option value="'.$pro['id'].'"'.$selected.'>'.$pro['name'].'</option>';
			}
			$city_str .=$str;
		}
		$str='';
		if($cityid){
			$rrr = $module->where("parentid=".$cityid)->select();	 		
			foreach($rrr as $key=>$pro){
				$selected = ($pro['id']==$areaid) ? ' selected="selected" ' : '';
				$str .='<option value="'.$pro['id'].'"'.$selected.'>'.$pro['name'].'</option>';
			}
			$area_str .=$str;
		}
		
		$res=array();
		$res['data']= $rs ? 1 : 0 ;
		$res['province'] =$province_str;
		$res['city'] =$city_str;
		$res['area'] =$area_str;
		echo json_encode($res); exit;
	 exit;
    }

	public function address(){
		$do=$_REQUEST['do'];
		$model = M('User_address');
		$id = intval($_REQUEST['id']);
		$provinceid= intval($_REQUEST['province']);
		$cityid= intval($_REQUEST['city']);
		$areaid= intval($_REQUEST['area']);
		$_POST['address']=htmlspecialchars($_POST['address']);
		$userid = $_POST['userid'] = $this->_userid;
		if($do=='save'){
			$id= intval($_POST['id']);
			$_POST['isdefault']=1;
			if($userid){					
				if($id){
				$model->where("userid=".$userid)->save(array('isdefault'=>0));	
					$r = $model->save($_POST);
					if($model->getDbError())die(json_encode(array('id'=>0)));
					$_POST['edit'] =1;				
				}else{
					$where['userid']=array('eq',$this->_userid);
					$where['province'] = array('eq',$provinceid);
					$where['city'] = array('eq',$cityid);
					$where['area'] = array('eq',$areaid);
					$where['consignee'] = array('eq',$_POST['consignee']);
					$where['address'] = array('eq',$_POST['address']);
					$ir = $model->where($where)->find();	
					if($ir){
						echo json_encode(array('error'=>'收货信息已经存在！'));exit;
					}
				$model->where("userid=".$userid)->save(array('isdefault'=>0));	
					$id=$model->add($_POST);
				}
			}else{
					$_POST['id']=1;
					$data = serialize($_POST);
					cookie('guest_address',$data,315360000);
					$id=1;
					$_POST['edit'] =1;
			}
			if($id){
				$_POST['id'] =$id;
				$Area = M('Area')->getField('id,name');
				$_POST['province_name']=$Area[$provinceid];
				$_POST['city_name']=$Area[$cityid];
				$_POST['area_name']=$Area[$areaid];
				die(json_encode($_POST));
			}else{
				die(json_encode(array('id'=>0)));
			}
			 
		}elseif($do=='get'){
			if($userid){	
				$data=$model->find($id);
			}else{
				$data = unserialize($_COOKIE['YP_guest_address']);
			}
			if($data){
				die(json_encode($data));
			}else{
				die(json_encode(array('id'=>0)));
			}
			exit;
		}
	
	}

	public function shipping(){
		$do=$_REQUEST['do'];
		$model = M('Shipping');
		$id = intval($_REQUEST['id']); 
 
		if($do=='get'){
			$data=$model->find($id);
			if($data){
				echo json_encode($data);
			}else{
				echo json_encode(array('id'=>0));
			}
			exit;
		}
	
	}
	public function change_par(){
		$uid=$_REQUEST['id']?$_REQUEST['id']:0;
		$par=M('user')->field("id,realname")->where(" groupid >5 and groupid< 14 and id!=".$uid)->order("CONVERT( realname USING gbk ) COLLATE gbk_chinese_ci ASC")->select();
		$str="<ul id='select_ul' style='width:98%;margin-left:1%;'>";
		$str.="<li onclick='par_select(this)' style='padding:0px 10px 0px 10px;list-style-type:none;float:left;margin-right:5px;widtg:40px;line-height:40px;'><input type='hidden' value='0'/><span>有酒派</span></li>";
		foreach ($par as $k => $v) {
			$str.="<li onclick='par_select(this)' style='padding:0px 10px 0px 10px;list-style-type:none;float:left;margin-right:5px;widtg:40px;line-height:40px;'><input type='hidden' value='".$v['id']."'/><span>".$v['realname']."</span></li>";
		}
		$str.="</ul>";
		echo $str;
	}
	public function search_par(){
		$keyword=$_REQUEST['name']?$_REQUEST['name']:"";
		$uid=$_REQUEST['id']?$_REQUEST['id']:0;
		$par=M('user')->field("id,realname")->where(" groupid >5 and groupid< 14 and realname like '%".$keyword."%' and id!=".$uid)->order("CONVERT( realname USING gbk ) COLLATE gbk_chinese_ci ASC")->select();
		$str="<ul id='select_ul' style='width:98%;margin-left:1%;'>";
		$str.="<li onclick='par_select(this)' style='padding:0px 10px 0px 10px;list-style-type:none;float:left;margin-right:5px;widtg:40px;line-height:40px;'><input type='hidden' value='0'/><span>有酒派</span></li>";
		foreach ($par as $k => $v) {
			$str.="<li onclick='par_select(this)' style='padding:0px 10px 0px 10px;list-style-type:none;float:left;margin-right:5px;widtg:40px;line-height:40px;'><input type='hidden' value='".$v['id']."'/><span>".$v['realname']."</span></li>";
		}
		$str.="</ul>";
		echo $str;
	}
 /*站内信 搜索*/
	public function change_menber(){
		$user=M('user')->field("id,wechat_name")->where(" groupid >2 and groupid<5 ")->order("CONVERT( wechat_name USING gbk ) COLLATE gbk_chinese_ci ASC")->select();
		$shop=M('user')->field("id,realname")->where(" groupid >5 and groupid<14 ")->order("CONVERT( realname USING gbk ) COLLATE gbk_chinese_ci ASC")->select();
		$str="<ul id='select_ul' style='width:98%;margin-left:1%;overflow: hidden;'>";
		$str.="<p style='width:98%;margin-left:1%;'>微店</p>";
		foreach ($shop as $k => $v) {
			$str.="<li onclick='par_select(this)' style='padding:0px 10px 0px 10px;list-style-type:none;float:left;margin:0px 0px 5px 5px;widtg:40px;line-height:40px;'><input class='get_icon'  type='hidden' value='".$v['id']."'/><span>".$v['realname']."</span></li>";
		}
		$str.="</ul><ul id='select_ul' style='width:98%;margin-left:1%;overflow: hidden;'><p style='width:98%;margin-left:1%;'>会员</p>";
		foreach ($user as $k => $v) {
			$str.="<li onclick='par_select(this)' style='padding:0px 10px 0px 10px;list-style-type:none;float:left;margin:0px 0px 5px 5px;widtg:40px;line-height:40px;'><input class='get_icon'  type='hidden' value='".$v['id']."'/><span>".$v['wechat_name']."</span></li>";
		}
		$str.="</ul>";
		echo $str;
	}
	public function search_menber(){
		$keyword=$_REQUEST['name']?$_REQUEST['name']:"";
		$par=M('user')->field("id,wechat_name")->where("groupid >2 and groupid<14 and wechat_name like '%".$keyword."%'")->order("CONVERT( wechat_name USING gbk ) COLLATE gbk_chinese_ci ASC")->select();
		$str="<ul id='select_ul' style='width:98%;margin-left:1%;'>";
		foreach ($par as $k => $v) {
			$str.="<li onclick='par_select(this)' style='padding:0px 10px 0px 10px;list-style-type:none;float:left;margin:0px 0px 5px 5px;widtg:40px;line-height:40px;'><input class='get_icon' type='hidden' value='".$v['id']."'/><span>".$v['wechat_name']."</span></li>";
		}
		$str.="</ul>";
		echo $str;
	}
 /**/
 	/*后台流动栏目设置*/

	public function catch_colu(){
		$columntype_id=$_REQUEST['columntype_id']? $_REQUEST['columntype_id']:0;
		$uid=1;//默认为admin
		$res_str="";
		if($columntype_id){
			/*找出该栏目下所有适合的图片*/
			$sql="SELECT a.* from `qq_slide_data` as a left join `qq_slide` as b on a.fid =b.id left join `qq_shopcolumn_type` as c on c.slide_id=b.id where c.id=".$columntype_id." and a.status=1";
			$query=mysql_query($sql);
			$img_res=array();
			while ($row = mysql_fetch_assoc($query)) {
				$img_res[]=$row;
			}
			/*找出该栏目已选图片*/
			if($columntype_id){
				$sql="SELECT b.* from `qq_shopcolumn` as a left join `qq_slide_data` as b on a.slid_data_id=b.id where a.columntype_id=".$columntype_id." and a.uid=".$uid;
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

	function column_edit(){
		$columntype_id=$_REQUEST['columntype_id']? intval($_REQUEST['columntype_id']):0;
		$slid_data_id=$_REQUEST['slid_data_id']? intval($_REQUEST['slid_data_id']):0;
		$uid=1;//默认为admin

		$info=M('shopcolumn');
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

 	/**/
}
?>