<?php
/**
+-----------------------------------------------------------------------------------------
* 删除目录及目录下所有文件或删除指定文件
+-----------------------------------------------------------------------------------------
* @param str $path   待删除目录路径
* @param int $delDir 是否删除目录，1或true删除目录，0或false则只删除文件保留目录（包含子目录）
+-----------------------------------------------------------------------------------------
* @return bool 返回删除状态
+-----------------------------------------------------------------------------------------
 */
function delDirAndFile($path, $delDir = FALSE) {
	$handle = opendir($path);
	if ($handle) {
		while (false !== ( $item = readdir($handle) )) {
			if ($item != "." && $item != "..")
				is_dir("$path/$item") ? delDirAndFile("$path/$item", $delDir) : unlink("$path/$item");
		}
		closedir($handle);
		if ($delDir)
			return rmdir($path);
	}else {
		if (file_exists($path)) {
			return unlink($path);
		} else {
			return FALSE;
		}
	}
}


/**
+---------------------------------------------------------------+
* 获取客户端 ip
* frequent_getIP()
* 如果代理服务器供出原ip,可以获取到真实ip.
+---------------------------------------------------------------+
* @return string	(8.35.201.50)
+---------------------------------------------------------------+
 */
function frequent_getIP(){
	static $realip = NULL;
	if ($realip !== NULL)	return $realip;
	if (isset($_SERVER)){
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			foreach ($arr AS $ip){
				$ip = trim($ip);
				if ($ip != 'unknown'){
					$realip = $ip;
					break;
				}
			}
		}elseif (isset($_SERVER['HTTP_CLIENT_IP'])){
			$realip = $_SERVER['HTTP_CLIENT_IP'];
		}else{
			if (isset($_SERVER['REMOTE_ADDR'])){
				$realip = $_SERVER['REMOTE_ADDR'];
			}else{
				$realip = '0.0.0.0';
			}
		}
	}else{
		if (getenv('HTTP_X_FORWARDED_FOR')){
			$realip = getenv('HTTP_X_FORWARDED_FOR');
		}elseif (getenv('HTTP_CLIENT_IP')){
			$realip = getenv('HTTP_CLIENT_IP');
		}else{
			$realip = getenv('REMOTE_ADDR');
		}
	}
	preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
	$realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
	return $realip;
}
/**
+---------------------------------------------------------------+
* 略缩图
+---------------------------------------------------------------+
* @return string	
+---------------------------------------------------------------+
 */
function thumb($f, $tw=300, $th=300 ,$autocat=0, $nopic = 'nopic.jpg',$t=''){
	if(strstr($f,'://')) return $f;
	if(empty($f)) return __ROOT__.'/Public/Images/'.$nopic;
	$f= '.'.str_replace(__ROOT__,'',$f);

	$temp = array(1=>'gif', 2=>'jpeg', 3=>'png');
	list($fw, $fh, $tmp) = getimagesize($f);
	if(empty($t)){
		if($fw>$tw && $fh>$th){
			$pathinfo = pathinfo($f);
			$t = $pathinfo['dirname'].'/thumb_'.$tw.'_'.$th.'_'.$pathinfo['basename'];
			if(is_file($t)){
				return  __ROOT__.substr($t,1);
			}
		}else{
			return  __ROOT__.substr($f,1);
		}		
	}
	if(!$temp[$tmp]){	return false; }
	if($autocat){
		if($fw/$tw > $fh/$th){
			$fw = $tw * ($fh/$th);
		}else{
			$fh = $th * ($fw/$tw);
		}
	}else{
		$scale = min($tw/$fw, $th/$fh); // 计算缩放比例
		if($scale>=1) {
			// 超过原图大小不再缩略
			$tw   =  $fw;
			$th  =  $fh;
		}else{
			// 缩略图尺寸
			$tw  = (int)($fw*$scale);
			$th = (int)($fh*$scale);
		}
	}
	$tmp = $temp[$tmp];
	$infunc = "imagecreatefrom$tmp";
	$outfunc = "image$tmp";
	$fimg = $infunc($f);

	if($tmp != 'gif' && function_exists('imagecreatetruecolor')){
		$timg = imagecreatetruecolor($tw, $th);
	}else{
		$timg = imagecreate($tw, $th);
	}
	if(function_exists('imagecopyresampled'))
		imagecopyresampled($timg, $fimg, 0,0, 0,0, $tw,$th, $fw,$fh);
	else
		imagecopyresized($timg, $fimg, 0,0, 0,0, $tw,$th, $fw,$fh);
	if($tmp=='gif' || $tmp=='png') {
		$background_color  =  imagecolorallocate($timg,  0, 255, 0);  //  指派一个绿色
		imagecolortransparent($timg, $background_color);  //  设置为透明色，若注释掉该行则输出绿色的图
	}
	$outfunc($timg, $t);
	imagedestroy($timg);
	imagedestroy($fimg);
	return  __ROOT__.substr($t,1);
}
/**
  +---------------------------------------------------------------+
 *  判断是否工作日
 * is_workday(1384666888,3,array("2012-11-20"))
  +---------------------------------------------------------------+	
 *  parameters 
 * @param integer $theTime 时间戳
 * @param integer $mode  模式0 =不考虑周末,假日;模式1 =排除星期天;模式2 =排除星期六,星期天;模式3 =排除星期六,星期天,假日 
 * @param array   $holiday   自定义的节日数组
  +---------------------------------------------------------------+	
 * @return boolen 
  +---------------------------------------------------------------+
*/
function frequent_is_workday ($theTime,$mode=0,$holiday){
	$return = true;
	switch ($mode){
	case 1:
		if (0==idate(w,$theTime))$return=false;
		break;
	case 2:
		if (0==idate(w,$theTime) || 6==idate(w,$theTime))$return=false;
		break;
	case 3:
		$date = date("Y-m-d",$theTime); //获得日期,日期的格式需结合$holiday数组的要求
		if(0==idate(w,$theTime) || 6==idate(w,$theTime) || in_array($date,$holiday))$return=false;
		break;
	default:
	}
	return $return;
}
/**
  +---------------------------------------------------------------+
 *  计算结束时间
 * frequent_endTime(1384666888,3,0)
  +---------------------------------------------------------------+
 *  parameters 
 * @param integer $startTime 时间戳
 * @param integer $during持续天数
 * @param integer $mode  模式0 =不考虑周末,假日;模式1 =排除星期天;模式2 =排除星期六,星期天;模式3 =排除星期六,星期天,假日 
 * @param array   $holiday   自定义的节日数组
  +---------------------------------------------------------------+	
 * @return inerger 时间戳
  +---------------------------------------------------------------+
*/
function frequent_endTime($startTime,$during,$mode=0,$holidy){
	if (!function_exists('new_startTime')){
		function new_startTime($t,$a,$b){
			if(!frequent_is_workday($t,$a,$b)&&frequent_is_workday($t+86400,$a,$b)){//今天是假日+明天是工作日
				$arr = getdate($t+86400);
				//mktime(hour,minute,second,month,day,year)
				$t = mktime(9,0,0,$arr['mon'],$arr['mday'],$arr['year']);//设定从工作日的几点开始计算
			}elseif(!frequent_is_workday($t,$a,$b)&&!frequent_is_workday($t+86400,$a,$b)){//今天是假日+明天是假日
				$t += 86400;
				$t = new_startTime($t,$a,$b);
			}else{
			}
			return $t;
		}
	};
	$startTime = new_startTime($startTime,$mode,$holiday); //通过new_startTime传递$mode,$holiday 给 frequent_is_workday
	for($i=1;$i<=$during;$i++){
		$t = $startTime+86400*$i;
		if(!frequent_is_workday($t,$mode,$holiday)){
			$during++;
		}
	}
	$endTime = $startTime+$during*86400;
	return $endTime;
}
/**
  +---------------------------------------------------------------+
 * 4. 无限分类
 * @param  array   $arr  一维数组
 * @param  integer $pid  父级id
  +---------------------------------------------------------------+
  | @return array  $tree
  +---------------------------------------------------------------+
*/
function frequent_infinite_category($arr, $pid = 0) {
	$fn = __FUNCTION__;
	$tree = array();
	foreach ($arr as $k => $v) {
		if ($v['pid'] == $pid)		
			$tree[] = $v;
	}
	if (empty($tree))
		return null;
	foreach ($tree as $k => $v) {
		if($fn($arr, $v['id']))		
			$tree[$k]['son'] = $fn($arr, $v['id']);
	}
	return $tree;
}

/**
  +---------------------------------------------------------------+
 * 6. tree逆树过程
 * @param  array	$arr	数形数组
 * @param  array	$field	标明缩进的字段和子类字段
 * @param  array	$icon	缩进图标，可以使用图片，文字，html
 * @param  string	$prefix 
  +---------------------------------------------------------------+
  | @return array  $tree
  +---------------------------------------------------------------+
*/
function frequent_tree2list($arr,$fields=array(),$icon=array(),	$prefix='',$reset=true){
	$fields = $fields ? $fields : array('title'=>'title','son'=>'son');
	$icon = $icon ? $icon :array('&nbsp;&nbsp;│', '&nbsp;&nbsp;├ ', '&nbsp;&nbsp;└ ');
	$prefix = ($first) ? $prefix:$prefix.$icon[0];

	static $first=true;
	static $arr_new;
	if($reset == true){
		$arr_new=array();
	}
	foreach ($arr as $k=>$v){
		$count = count($arr);
		if($first){
			$first=false;
			$lastIcon = $icon[1];
		}elseif($k == ($count-1)){
			$lastIcon = $icon[2];
		}else{
			$lastIcon = $icon[1];
		}
		if(!$v[$fields['son']]){
			$v[$fields['title']] = $prefix.$lastIcon.$v[$fields['title']];
			$arr_new[] = $v;
		}else{
			$v[$fields['title']] = $prefix.$lastIcon.$v[$fields['title']];
			$temp = $v;
			unset($temp[$fields['son']]);
			$arr_new[] = $temp;
			$fn = __FUNCTION__;
			$fn($v[$fields['son']],$fields,$icon,$prefix,false);//frequent_tree2list($v[$fields['son']],$fields,$icon,$prefix);
		}
	}
	
	return $arr_new;

}
/**
  +---------------------------------------------------------------+
 * 状态转化
 * @param  int/str	$status	
 * @param  array	$arr	array(array('value'=>'','title'=>''))
 *			或者	array(0=>'已关闭'，1=>'开启中')
  +---------------------------------------------------------------+
  | @return array  $tree
  +---------------------------------------------------------------+
*/
function frequent_status_converter($status,$arr,$flag='text'){
	$status = isset($status) ? $status : 1;
	if($flag == 'text'){
		return $arr[$status];
	}else{
		
		foreach ($arr as $v){
			$selected = $v['value']==$status ? ' selected="selected" ':'';
			$str .= '<option value="'.$v['value'].'" '.$selected.'>'.$v['title'].'</option>';
		}
		return $str;
	}
}
/**
  +---------------------------------------------------------------+
 * 5. 多维数组转1维
  +---------------------------------------------------------------+
  | @param  array  $arr  多维数组
  | @return array  $arr
 +---------------------------------------------------------------+
*/ 
function multidimensional_2_unidimensional($array,$items=false,$reset=true) {
	$fn = __FUNCTION__;
	static $return = array();
	if($reset==true){
		$return = array();
	}
	foreach ($array as $key=>$value){
		if (is_array($value)){
			$fn($value,$items,false); 	
		}else{
			if(is_array($items)){
				if(in_array($key,$items)){	
					$return[] = $value;
				}
			}elseif($items){
				if($key == $items)	$return[]=$value;
			}else{
				$return[]=$value;
			}
		// $return[$key] = $value;保留Key，但是相同key的会被覆盖	
		}
	}
	return $return;
}

/**
  +---------------------------------------------------------------+
 * 25. 索引改键
 * frequent_index2key($arr,$unique)
  +---------------------------------------------------------------+	
 *  parameters array	$arr		数组
 *  parameters string 	$unique		能唯一标示的键
  +---------------------------------------------------------------+	
  | @return $arr		
  +---------------------------------------------------------------+	
 */
function frequent_index2key($arr,$unique){
	foreach ($arr as $v){
		$return[$v[$unique]]=$v;
	}
	return $return;
}
/**
  +---------------------------------------------------------------+
 * 17.
 |	反解unicode中文
 +---------------------------------------------------------------+
 */
function frequent_unicode_decode_json($str){
	if (!function_exists('conv')){
		function conv($arr){
			$code_1 = base_convert(substr($arr[0],2,2),16,10);
			$code_2 = base_convert(substr($arr[0], 4), 16, 10);
			$c = chr($code_1).chr($code_2);
			$c = iconv('UCS-2','UTF-8',$c);
			return $c;
		}
	}
	$str = preg_replace_callback("/\\\u([\w]{4})/",conv,$str);
	return $str;
}
