<?php
/**
 * 
 * 栏目图片(栏目图片)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
class CatimgpcAction extends AdminbaseAction {

	protected  $Tplpath,$Flashpath,$Xmlpath;
    function _initialize()
    {	
		parent::_initialize();
		$this->Tplpath = TMPL_PATH.'/Home/'.$this->sysConfig['DEFAULT_THEME'].'/';
		//$this->Flashpath = TMPL_PATH.$this->sysConfig['DEFAULT_THEME'].'/Public/flash/';
		$this->Xmlpath = TMPL_PATH.'/Home/'.$this->sysConfig['DEFAULT_THEME'].'/Public/xml/';
		 
    }
 

	function _before_add(){ 
		$Tpl = template_file('Catimgpc');
		$this->assign ( 'Tpl', $Tpl ); 

	 
	}

	function _before_edit(){
		
		$Tpl = template_file('Catimgpc');
		$this->assign ( 'Tpl', $Tpl );
		//$Flash= template_file('',$this->Flashpath,'swf');
		//$this->assign ( 'Flash', $Flash);
	}
	function edittpl(){
		$file = $this->Tplpath.'Pcslide_'.$_REQUEST['tpl'].'.html';
		if($_POST['content']){
			file_put_contents($file,htmlspecialchars_decode(stripslashes($_POST['content'])));			
			$this->success (L('do_ok'));
		}else{		
			$content = htmlspecialchars(file_get_contents($file));
			echo ' <form method="post" id="myform"  action="'.U('Pcslide/edittpl').'">Pcslide_'.$_GET['tpl'].'.html<input type="hidden" name="tpl" value="'.$_GET['tpl'].'"/><textarea  name="content" id="content" style="width:100%;height:500px;"  >'.$content.'</textarea>  <input type="hidden" name="isajax" value="1" />
			 <input name="dosubmit" type="submit" value="1" style="display:none;"class="hidden" id="dosubmit"> </form>';
		}
	}

	function picmanage(){
		$fid=intval($_REQUEST['fid']);
		if(!$fid) $this->error(L('do_empty'));
		$map = array();
		if(APP_LANG)$map['lang']=array('eq',LANG_ID);

		$slide = D('Pcslide')->find($fid);
		
		$map['fid']=array('eq',$fid);
		$list = D('Pcslide_data')->where($map)->order(" listorder ASC ,id DESC ")->select();
		$this->assign ( 'list', $list );
		$this->assign ( 'fid', $fid );
		$this->assign ( 'slide', $slide ); 
		$this->display();

	}
	function ad_index(){
		 $qq=D('Pcslide_data');
		$list = D('Pcslide_data')->field(" `qq_pcslide_data`.*,`qq_pcslide`.name ")->join(" `qq_pcslide` on `qq_pcslide`.id=`qq_pcslide_data`.fid ")->where(' `qq_pcslide_data`.fid in(1,3,4)')->order(" `qq_pcslide_data`.listorder ASC ,`qq_pcslide_data`.id DESC ")->select();
		$this->assign ( 'list', $list );
		$this->display();
	}
	function index(){
		$qq=D('Pcslide_data');
		$list = D('Pcslide_data')->field(" `qq_pcslide_data`.*,`qq_pcslide`.name ")->join(" `qq_pcslide` on `qq_pcslide`.id=`qq_pcslide_data`.fid ")->where(' `qq_pcslide_data`.fid=2 ')->order(" `qq_pcslide_data`.listorder ASC ,`qq_pcslide_data`.id DESC ")->select();
		$this->assign ( 'list', $list );
		$this->display();
	}
	function addpic(){ 
		$fid=intval($_REQUEST['fid']);
		if(!$fid) $this->error(L('do_empty'));
		$map = array();
		if(APP_LANG)$map['lang']=array('eq',LANG_ID);

		$slide = D('Pcslide')->find($id);
		$map['fid']=array('eq',$id);
		$list = D('Pcslide_data')->where($map)->order(" listorder ASC ,id DESC ")->select();


		$qqcms_auth_key = sysmd5(C('ADMIN_ACCESS').$_SERVER['HTTP_USER_AGENT']);
		$qqcms_auth = authcode('1-1-0-10-jpeg,jpg,png,gif-5-230', 'ENCODE',$qqcms_auth_key);
		$this->assign('qqcms_auth',$qqcms_auth);

		$vo['status'] = 1;
		$this->assign ( 'vo', $vo);
		$this->assign ( 'list', $list );
		$this->assign ( 'fid', $fid );
		$this->assign ( 'slide', $slide ); 
		
		$this->display ('Catimgpc:editpic');

	}

	function editpic(){
		$id=intval($_REQUEST['id']);
		$fid=intval($_REQUEST['fid']);
		if(!$id) $this->error(L('do_empty'));
		$slide = D('Pcslide')->find($fid);

		//isadmin,more,isthumb,file_limit,file_types,file_size,moduleid,
		$qqcms_auth_key = sysmd5(C('ADMIN_ACCESS').$_SERVER['HTTP_USER_AGENT']);
		$qqcms_auth = authcode('1-1-0-10-jpeg,jpg,png,gif-5-230', 'ENCODE',$qqcms_auth_key);
		$this->assign('qqcms_auth',$qqcms_auth);

		$vo = D('Pcslide_data')->find($id);
		$this->assign ( 'fid', $fid );
		$this->assign ( 'vo', $vo ); 
		$this->assign ( 'slide', $slide ); 
		$this->display ();

	}

	function insertpic(){
	
		if(APP_LANG)$_POST['lang']=LANG_ID;
		//if($_POST['setup']) $_POST['setup']=array2string($_POST['setup']);
		$name = 'Pcslide_data';

		$model = D ($name);
		if (false === $model->create ()) {
			$this->error ( $model->getError () );
		}
		$_POST['id'] = $id= $model->add();
		if($_POST['fid']==2){
			//$site_url=M('config')->field('value')->where("id=2")->find();
			$this_data['this_url']="/index.php?m=Product&a=shop_goods&ad_column=".$_POST['id'];
			$r=M($name)->where('id='.$_POST['id'])->save($this_data);
		}
		if ($id !==false) {

			if($_POST['aid']){
				$Attachment =M('Attachment');		
				$aids =  implode(',',$_POST['aid']);
				$data['id']= $_POST['id'];
				$data['catid']= $_POST['fid'];
				$data['status']= '1';
				$Attachment->where("aid in (".$aids.")")->save($data);
			}
			
			//$this->assign ( 'jumpUrl', U('Slide/picmanage?fid='.$_POST['fid']) );
			$this->success (L('add_ok'));
		} else {
			$this->error (L('add_error').': '.$model->getDbError());
		}
	}

	function updatepic(){
	 
		$name = 'Pcslide_data';
		$model = D ( $name );
		if ($_POST['id'])
		$_POST['this_url'] = "/index.php?m=Product&a=shop_goods&ad_column=".$_POST['id'];
		if (false === $model->create ()) {
			$this->error ( $model->getError () );
		}

		if (false !== $model->save ()) {
			
			if($_POST['aid']){
				$Attachment =M('Attachment');		
				$aids =  implode(',',$_POST['aid']);
				$data['id']= $_POST['id'];
				$data['catid']= $_POST['fid'];
				$data['status']= '1';
				$Attachment->where("aid in (".$aids.")")->save($data);
			}

			$this->success (L('edit_ok'));
		} else {
			$this->success (L('edit_error').': '.$model->getDbError());
		}
	}

	function param()
	{
		$files = glob(LANG_NAMEpath.'*');
		$lang_files=array();
		foreach($files as $key => $file) {
			//$filename = basename($file);
			$filename = pathinfo($file);
	 		$lang_files[$key]['filename'] = $filename['filename'];
			$lang_files[$key]['filepath'] = $file;
			$temp = explode('_',$filename);
			$lang_files[$key]['name'] = count($temp)>1 ? $temp[0].L('LANG_module') : L('LANG_common') ;
		}
		$this->assign ( 'id', $id );
		$this->assign ( 'lang', LANG_NAME );
		$this->assign ( 'files', $lang_files );
		$this->display();
		
	}
 

	function listorder(){
		$name ='Pcslide_data';
		$model = M ( $name );
		$pk = $model->getPk ();
		$ids = $_POST['listorders'];
		foreach($ids as $key=>$r) {
			$data['listorder']=$r;
			$model->where($pk .'='.$key)->save($data);
		} 
		$this->success (L('do_ok'));

	}


	function delete(){
		$name = MODULE_NAME;
		$model = M ( $name );
		$pk = $model->getPk ();
		$id = $_REQUEST [$pk];
		if (isset ( $id )) {
			if(false!==$model->delete($id)){
				$name ='Pcslide_data';
				$model = M ( $name );
				$model->where("fid=".$id)->delete();
				delattach(array('moduleid'=>'230','catid'=>$id));
				$this->success(L('delete_ok'));
			}else{
				$this->error(L('delete_error').': '.$model->getDbError());
			}
		}else{
			$this->error (L('do_empty'));
		}
	}

	function deletepic(){
		$name ='Pcslide_data';
		$model = M ( $name );
		$pk = $model->getPk ();
		$id = $_REQUEST [$pk];
		if (isset ( $id )) {
			if(false!==$model->delete($id)){
				delattach(array('moduleid'=>'230','id'=>$id));
				$this->success(L('delete_ok'));
			}else{
				$this->error(L('delete_error').': '.$model->getDbError());
			}
		}else{
			$this->error (L('do_empty'));
		}
	}
	
	function edit_goods(){

			$slide_data_id=$_REQUEST['id']? $_REQUEST['id']:0;

			/*查找栏目标题*/
			$slide_data=M('Pcslide_data')->where('id='.$slide_data_id)->find();
			$this->assign("Pcslide_data",$slide_data);
			/*找出已选商品*/
			$join = 'qq_shopcolumnpc_data as b ON a.id = b.goods_id';
			$where['b.slide_data_id'] = $slide_data_id;
			$has_info = M('Product_oversea as a')->field('a.*,b.id as col_id,b.listorder as sort')->join($join)->where($where)->order("b.listorder ASC,b.id DESC")->select();
			$this->assign('has_info',$has_info);

			/*找出该栏目下所有适合的商品*/
			$info_res = M('Product_oversea')->where('catid=36 and status=1')->select();

			$final_res=array();
			foreach ($info_res as $k => $v) {
				foreach ($has_info as $h_k => $h_v) {
					if($v['id']==$h_v['id']){
						$info_res[$k]=array();//清空已选择商品
					}
				}
			}

			$this->assign('info',$info_res);
			$this->display();
	}

	public function goods_add(){
		$goods_id=$_REQUEST['goods_id']? $_REQUEST['goods_id']:0;
		$slide_data_id=$_REQUEST['slide_data_id']? $_REQUEST['slide_data_id']:0;
		if($goods_id && $slide_data_id ){
			$data['slide_data_id']=$slide_data_id;
			$data['goods_id']=$goods_id;
			$res=M('shopcolumnpc_data')->add($data);

			}
		if($res){
				$this->success("加入成功");
		}else{
				$this->error("加入失败");
			}
	}		
	public function goods_del(){
			$column_id=$_REQUEST['col_id']?$_REQUEST['col_id']:0;
			if($column_id){
				$res=M("shopcolumnpc_data")->where("id=".$column_id)->delete();
			}
			if($res){
				$this->success("移除成功");
			}else{
				$this->error("移除失败");
			}
		}
/*流动栏目*/
	public function small_ad(){
		$uid=1;//默认官方的流动栏目都是第一个admin的
		$column_type=M('shopcolumnpc_type')->where("status=1")->select();
		//echo "流动栏目:";print_r($column_type)."<br>";
		$this->get_offic_column();//获取官方栏目
		$this->get_column($column_type);//获取微店栏目
		//exit;
		$this->assign('column_type',$column_type);
		$this->display();
	}

 	//获取微店栏目
 	public function get_column($column_type=array()){
		$shop_id=1;//默认官方的流动栏目都是第一个admin的
 		$column=M('Pcslide_data')->field(' `qq_shopcolumnpc`.*,`qq_pcslide_data`.pic,`qq_pcslide_data`.link ')->join(' `qq_shopcolumnpc` on `qq_shopcolumnpc`.slid_data_id=`qq_pcslide_data`.id ')->where(" `qq_shopcolumn`.uid=".$shop_id)->select();
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
		//echo "微店栏目:";print_r($column_type)."<br>";
		$this->assign("column",$column_type);
		return true;
 	}
 	//获取官方栏目
 	public function get_offic_column(){
		$offic_column=M('Pcslide_data')->where('fid > 4 and fid <11')->order('fid asc')->select();
		//echo "官方栏目:";print_r($offic_column)."<br>";
		$this->assign("offic_column",$offic_column);
		return true;
 	}
	public function column_edit(){
			$columntype_id=$_REQUEST['columntype_id']? $_REQUEST['columntype_id']:0;
			$uid=1;

			/*查找栏目标题*/
			$slide_data=M('Pcslide_data')->field(' `qq_pcslide_data`.title ')->join(' `qq_shopcolumnpc` on `qq_shopcolumnpc`.slid_data_id=`qq_pcslide_data`.id ')->where(' `qq_shopcolumnpc`.columntype_id='.$columntype_id.' and `qq_shopcolumnpc`.uid='.$uid)->find();
			$this->assign("slide_data",$slide_data);
			
			/*找出已选商品*/
			$sql="SELECT a.*,b.id as col_id from `qq_product` as a left join `qq_shopcolumnpc_data` as b on a.id=b.goods_id left join `qq_shopcolumnpc` as c on c.slid_data_id=b.slide_data_id  where c.columntype_id=".$columntype_id." and c.uid=".$uid;
			$query=mysql_query($sql);
			$has_info=array();
			while ($row = mysql_fetch_assoc($query)) {
				$has_info[]=$row;
			}
			$this->assign('has_info',$has_info);

			$this->display();
		}
	
/*end*/
}
?>