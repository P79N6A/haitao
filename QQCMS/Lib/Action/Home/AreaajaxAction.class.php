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
class AreaajaxAction extends Action
{
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

	
}
?>