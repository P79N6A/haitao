<?php
/**
 * 
 * Common.php (项目公共函数库)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */

function fieldoption($fields,$value=null,$space=''){
	$options = explode("\n",$fields['setup']['options']);
	foreach($options as $r) {
		$v = explode("|",$r);
		$k = trim($v[1]);
		$optionsarr[$k] = $v[0];
	}
	if(isset($value)){
		if(strpos($value,',')){
			$value =explode(",",$value);
			$data=array();
			foreach((array)$value as $val){
			$data[]= $optionsarr[$val];
			}
			if($space!=''){
			return implode(stripcslashes($space),$data);
			}else{
			return $data;
			}			
		}else{
			return $optionsarr[$value];
		}
	}else{
		return $optionsarr;
	}
}

function get_arrparentid($pid, $array=array(),$arrparentid='') {
		if(!is_array($array) || !isset($array[$pid])) return $pid;
		$parentid = $array[$pid]['parentid'];
		$arrparentid = $arrparentid ? $parentid.','.$arrparentid : $parentid;
		if($parentid) {
			$arrparentid = get_arrparentid($parentid,$array, $arrparentid);
		}else{
			$data = array();
			$data['bid'] = $pid;
			$data['arrparentid'] = $arrparentid;
		}

		return $arrparentid;
}

function getform($form,$info,$value=''){
	return  $form->$info['type']($info,$value);
}

function getvalidate($info){
        $validate_data=array();
        if($info['minlength']) $validate_data['minlength'] = ' minlength:'.$info['minlength'];
		if($info['maxlength']) $validate_data['maxlength'] = ' maxlength:'.$info['maxlength'];
		if($info['required']) $validate_data['required'] = ' required:true';
		if($info['pattern']) $validate_data['pattern'] = ' '.$info['pattern'].':true';
        if($info['errormsg']) $errormsg = ' title="'.$info['errormsg'].'"';
        $validate= implode(',',$validate_data);
        $validate= $validate ? 'validate="'.$validate.'" ' : '';
        $parseStr = $validate.$errormsg;
        return $parseStr;
}

function sendmail($tomail,$subject,$body,$config=''){

		if(!$config)$config = F('Config');

		import("@.ORG.PHPMailer");
		$mail = new PHPMailer();

		if($config['mail_type']==1){
			$mail->IsSMTP();
		}elseif($config['mail_type']==2){
			$mail->IsMail();
		}else{
			if($config['sendmailpath']){
				$mail->Sendmail =$config['mail_sendmail'];
			}else{
				$mail->Sendmail =ini_get('sendmail_path');
			}
			$mail->IsSendmail();
		}
		if($config['mail_auth']){
			$mail->SMTPAuth = true; // 开启SMTP认证
		}else{
			$mail->SMTPAuth = false; // 开启SMTP认证
		}

		$mail->PluginDir=LIB_PATH."ORG/";
		$mail->CharSet='utf-8';
		$mail->SMTPDebug  = false;        // 改为2可以开启调试
		$mail->Host = $config['mail_server'];      // GMAIL的SMTP
		//$mail->SMTPSecure = "ssl"; // 设置连接服务器前缀
		//$mail->Encoding = "base64";
		$mail->Port = $config['mail_port'];    // GMAIL的SMTP端口号
		$mail->Username = $config['mail_user']; // GMAIL用户名,必须以@gmail结尾
		$mail->Password = $config['mail_password']; // GMAIL密码
		//$mail->From ="qqcms@163.com";
		//$mail->FromName = "PHP网站管理系统";
		$mail->SetFrom($config['mail_from'], $config['site_name']);     //发送者邮箱
		$mail->AddAddress($tomail); //可同时发多个
		//$mail->AddReplyTo('79441928@qq.com', 'qqcms'); //回复到这个邮箱
		//$mail->WordWrap = 50; // 设定 word wrap
		//$mail->AddAttachment("/var/tmp/file.tar.gz"); // 附件1
		//$mail->AddAttachment("/tmp/image.jpg", "new.jpg"); // 附件2
		$mail->IsHTML(true); // 以HTML发送
		$mail->Subject = $subject;
		$mail->Body = $body;
		//$mail->AltBody = "This is the body when user views in plain text format";		//纯文字时的Body
		if(!$mail->Send())
		{
			return false;
		}else{
			return true;
		}
}

function delattach($map=''){
		$model = M('Attachment');
		$att= $model->field('aid,filepath')->where($map)->select();
		$aids=array();
		foreach((array)$att as $key=> $r){
			$aids[]=$r['aid'];
			@unlink(__ROOT__.$r['filepath']);
		}
		$r =$model->delete(implode(',',$aids));
		return  false!==$r ? true : false;
}

function template_file($module='',$path='',$ext='html'){
	$sysConfig = F('sys.config');
	$path= $path ? $path : TMPL_PATH.'Home/'.$sysConfig['DEFAULT_THEME'].'/';
	$tempfiles = dir_list($path,$ext);
	foreach ($tempfiles as $key=>$file){
		$dirname = basename($file);
		if($module){
			if(strstr($dirname,$module.'_')) {
				$arr[$key]['name'] =  substr($dirname,0,strrpos($dirname, '.'));
				$arr[$key]['value'] =  substr($arr[$key]['name'],strpos($arr[$key]['name'], '_')+1);
				$arr[$key]['filename'] = $dirname;
				$arr[$key]['filepath'] = $file;
			}
		}else{
			$arr[$key]['name'] = substr($dirname,0,strrpos($dirname, '.'));
			$arr[$key]['value'] =  substr($arr[$key]['name'],strpos($arr[$key]['name'], '_')+1);
			$arr[$key]['filename'] = $dirname;
			$arr[$key]['filepath'] = $file;
		}
	}
	return  $arr;
}

function fileext($filename) {
	return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
}

function dir_path($path) {
	$path = str_replace('\\', '/', $path);
	if(substr($path, -1) != '/') $path = $path.'/';
	return $path;
}

function dir_create($path, $mode = 0777) {
	if(is_dir($path)) return TRUE;
	$ftp_enable = 0;
	$path = dir_path($path);
	$temp = explode('/', $path);
	$cur_dir = '';
	$max = count($temp) - 1;
	for($i=0; $i<$max; $i++) {
		$cur_dir .= $temp[$i].'/';
		if (@is_dir($cur_dir)) continue;
		@mkdir($cur_dir, 0777,true);
		@chmod($cur_dir, 0777);
	}
	return is_dir($path);
}

function dir_copy($fromdir, $todir) {
	$fromdir = dir_path($fromdir);
	$todir = dir_path($todir);
	if (!is_dir($fromdir)) return FALSE;
	if (!is_dir($todir)) dir_create($todir);
	$list = glob($fromdir.'*');
	if (!empty($list)) {
		foreach($list as $v) {
			$path = $todir.basename($v);
			if(is_dir($v)) {
				dir_copy($v, $path);
			} else {
				copy($v, $path);
				@chmod($path, 0777);
			}
		}
	}
    return TRUE;
}

function dir_list($path, $exts = '', $list= array()) {
	$path = dir_path($path);
	$files = glob($path.'*');
	foreach($files as $v) {
		$fileext = fileext($v);
		if (!$exts || preg_match("/\.($exts)/i", $v)) {
			$list[] = $v;
			if (is_dir($v)) {
				$list = dir_list($v, $exts, $list);
			}
		}
	}
	return $list;
}

function dir_tree($dir, $parentid = 0, $dirs = array()) {
	if ($parentid == 0) $id = 0;
	$list = glob($dir.'*');
	foreach($list as $v) {
		if (is_dir($v)) {
            $id++;
			$dirs[$id] = array('id'=>$id,'parentid'=>$parentid, 'name'=>basename($v), 'dir'=>$v.'/');
			$dirs = dir_tree($v.'/', $id, $dirs);
		}
	}
	return $dirs;
}

function dir_delete($dir) {
	$dir = dir_path($dir);
	if (!is_dir($dir)) return FALSE;
	$list = glob($dir.'*');
	foreach((array)$list as $v) {
		is_dir($v) ? dir_delete($v) : @unlink($v);
	}
    return @rmdir($dir);
}


function toDate($time, $format = 'Y-m-d H:i:s') {
	if (empty ( $time )) {
		return '';
	}
	$format = str_replace ( '#', ':', $format );
	return date ($format, $time );
}
function savecache($name = '',$id='') {
	$Model = M ( $name );
	if($name=='Lang'){
		$list = $Model->order('listorder')->select ();
		$pkid = $Model->getPk ();
		$data = array ();
		foreach ( $list as $key => $val ) {
			$data [$val ['mark']] = $val;
		}
		F($name,$data);

	}elseif($name=='Module'){
		$list = $Model->order('listorder')->select ();
		$pkid = $Model->getPk ();
		$data = array ();
		foreach ( $list as $key => $val ){
			$data [$val [$pkid]] = $val;
			$smalldata[$val['name']] =  $val [$pkid];
		}
		F($name,$data);
		F('Mod',$smalldata);
		//savecache

	}elseif($name=='Config'){
		
		$list = $Model->select ();
		$data=$sysdata=$temp=$memberconfig=array();
		foreach($list as $key=>$r) {
			if($r['groupid']==6){
				$sysdata[$r['varname']]=$r['value'];
			}elseif($r['groupid']==3){
				if(APP_LANG)
					$memberconfig_temp[$r['lang']][$r['varname']]=$r['value'];
				else
					$memberconfig[$r['varname']]=$r['value'];
			}else{
				if(APP_LANG)
					if($r['lang']){$temp[$r['lang']][$r['varname']]=$r['value'];}else{$data[$r['varname']]=$r['value'];}
				else
					$data[$r['varname']]=$r['value'];
			}
		}
		if(APP_LANG){
			$lang=F('Lang');
			foreach((array)$lang as $key=>$r){
				$data1=array();
				$data1 = array_merge($temp[$r['id']],$data);
				F('Config_'.$key,$data1);
				F('member.config_'.$key,$memberconfig_temp[$r['id']]);
				if(empty($data1['HOME_ISHTML'])){
					@unlink('./index.html');
					@unlink('./'.$key.'/index.html');
				}
			}
		}else{
			F('Config',$data);
			F('member.config',$memberconfig);
			if(empty($data['HOME_ISHTML']))@unlink('./index.html');
		}
		
		F('sys.config',$sysdata);

	}elseif($name=='Category'){

		$data=$smalldata=$temp=array();

		if(APP_LANG){
			$lang=F('Lang');
			foreach((array)$lang as $key=>$r){
				$langid =$r['id'];
				if($langid){
					$lang = $key;
					$list = $Model->where('lang='.$langid)->order('listorder')->select ();
					$pkid = $Model->getPk ();
					$data = array ();
					foreach ( $list as $key => $val ) {
						$data [$val [$pkid]] = $val;
						$smalldata[$val['catdir']] =  $val [$pkid];
					}
					F('Category_'.$lang,$data);
					F('Cat_'.$lang,$smalldata);
				}
			}
		}else{
			$list = $Model->order('listorder')->select ();
			$pkid = $Model->getPk ();
			$data = array ();
			foreach ( $list as $key => $val ) {
				$data [$val [$pkid]] = $val;
				$smalldata[$val['catdir']] =  $val [$pkid];
			}
			F($name,$data);
			F('Cat',$smalldata);
		}
	
	}elseif($name=='Field'){
		if($id){		
			$list = $Model->order('listorder')->where('moduleid='.$id)->select ();
			$pkid = 'field';
			$data = array ();
			foreach ( $list as $key => $val ) {
				$data [$val [$pkid]] = $val;
			}
			$name=$id.'_'.$name;
			F($name,$data);
		}else{
			$module = F('Module');
			foreach ( $module as $key => $val ) {
				savecache($name,$key);
			}
		}
	}elseif($name=='Dbsource'){
		$list = $Model->select ();
		$data = array ();
		foreach ( $list as $key => $val ) {
			$data [$val ['name']] = $val;
		}
		F($name,$data);		
	}else{		
		$list = $Model->order('listorder')->select ();
		$pkid = $Model->getPk ();
		$data = array ();
		foreach ( $list as $key => $val ) {
			$data [$val [$pkid]] = $val;
		}
		F($name,$data);
		if($name=='Urlrule'){
			$config = F('sys.config');
			if($config['URL_URLRULE'])routes_cache($config['URL_URLRULE']); 
		}
	}
	
	return true;
}


function checkfield($fields,$post){
		foreach ( $post as $key => $val ) {
				$setup=$fields[$key]['setup'];

				if(!empty($fields[$key]['required']) && empty($post[$key])) return '';

				//$setup=string2array($fields[$key]['setup']);
				if($setup['multiple'] || $setup['inputtype']=='checkbox' || $fields[$key]['type']=='checkbox'){
					$post[$key] = implode(',',$post[$key]);		
				}elseif($fields[$key]['type']=='datetime'){
					$post[$key] =strtotime($post[$key]);
				}elseif($fields[$key]['type']=='textarea'){
					$post[$key]=addslashes($post[$key]);
				}elseif($fields[$key]['type']=='images' || $fields[$key]['type']=='files'){
					$name = $key.'_name';
					$arrdata =array();
					foreach($post[$key] as $k=>$res){
						if(!empty($post[$key][$k])) $arrdata[]= $post[$key][$k].'|'.$post[$name][$k];
					}
					$post[$key]=implode(':::',$arrdata);
				}elseif($fields[$key]['type']=='editor'){					
					//自动提取摘要
					if(isset($post['add_description']) && $post['description'] == '' && isset($post['content'])) {
						$content = stripslashes($post['content']);
						$description_length = intval($post['description_length']);
						$post['description'] = str_cut(str_replace(array("\r\n","\t",'[page]','[/page]','&ldquo;','&rdquo;'), '', strip_tags($content)),$description_length);
						$post['description'] = addslashes($post['description']);
					}
					//自动提取缩略图
					if(isset($post['auto_thumb']) && $post['thumb'] == '' && isset($post['content'])) {
						$content = $content ? $content : stripslashes($post['content']);
						$auto_thumb_no = intval($post['auto_thumb_no']) * 3;
						if(preg_match_all("/(src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|bmp|png))\\2/i", $content, $matches)) {
							$post['thumb'] = $matches[$auto_thumb_no][0];
						}
					}
				} 
		}
		return $post;
}

function string2array($info) {
        if($info == '') return array();
        $info=stripcslashes($info);
        eval("\$r = $info;");
        return $r;
}


function array2string($info) {
	if($info == '') return '';
	if(!is_array($info)) $string = stripslashes($info);
	foreach($info as $key => $val) $string[$key] = stripslashes($val);
	return addslashes(var_export($string, TRUE));
}

/**
	 +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码
 * 默认长度6位 字母和数字混合 支持中文
	 +----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
	 +----------------------------------------------------------
 * @return string
	 +----------------------------------------------------------
 */
function rand_string($len = 6, $type = '', $addChars = '') {
	$str = '';
	switch ($type) {
		case 0 :
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
			break;
		case 1 :
			$chars = str_repeat ( '0123456789', 3 );
			break;
		case 2 :
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
			break;
		case 3 :
			$chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
			break;
		default :
			// 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
			$chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
			break;
	}
	if ($len > 10) { //位数过长重复字符串一定次数
		$chars = $type == 1 ? str_repeat ( $chars, $len ) : str_repeat ( $chars, 5 );
	}
	if ($type != 4) {
		$chars = str_shuffle ( $chars );
		$str = substr ( $chars, 0, $len );
	} else {
		// 中文随机字
		for($i = 0; $i < $len; $i ++) {
			$str .= msubstr ( $chars, floor ( mt_rand ( 0, mb_strlen ( $chars, 'utf-8' ) - 1 ) ), 1 );
		}
	}
	return $str;
}

function sysmd5($str,$key='',$type='sha1'){
	$key =  $key ?  $key : C('ADMIN_ACCESS');
	return hash ( $type, $str.$key );
}

function pwdHash($password, $type = 'md5') {
	return hash ( $type, $password );
}

/**
* @param string $string 原文或者密文
* @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
* @param string $key 密钥
* @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
* @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
*
* @example
*
*  $a = authcode('abc', 'ENCODE', 'key');
*  $b = authcode($a, 'DECODE', 'key');  // $b(abc)
*
*  $a = authcode('abc', 'ENCODE', 'key', 3600);
*  $b = authcode('abc', 'DECODE', 'key'); // 在一个小时内，$b(abc)，否则 $b 为空
*/
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) 
{
	$ckey_length = 4;   
	// 随机密钥长度 取值 0-32;
	// 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
	// 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
	// 当此值为 0 时，则不产生随机密钥


	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	$rndkey = array();
	for($i = 0; $i <= 255; $i++) 
	{
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	for($j = $i = 0; $i < 256; $i++) 
	{
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i++) 
	{
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') 
	{
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) 
		{
			return substr($result, 26);
		} 
		else 
		{
			return '';
		}
	} 
	else 
	{
		return $keyc.str_replace('=', '', base64_encode($result));
	}
}



//字符串截取
function str_cut($sourcestr,$cutlength,$suffix='...')
{
	$str_length = strlen($sourcestr);
	if($str_length <= $cutlength) {
		return $sourcestr;
	}
	$returnstr='';	
	$n = $i = $noc = 0;
	while($n < $str_length) {
			$t = ord($sourcestr[$n]);
			if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
				$i = 1; $n++; $noc++;
			} elseif(194 <= $t && $t <= 223) {
				$i = 2; $n += 2; $noc += 2;
			} elseif(224 <= $t && $t <= 239) {
				$i = 3; $n += 3; $noc += 2;
			} elseif(240 <= $t && $t <= 247) {
				$i = 4; $n += 4; $noc += 2;
			} elseif(248 <= $t && $t <= 251) {
				$i = 5; $n += 5; $noc += 2;
			} elseif($t == 252 || $t == 253) {
				$i = 6; $n += 6; $noc += 2;
			} else {
				$n++;
			}
			if($noc >= $cutlength) {
				break;
			}
	}
	if($noc > $cutlength) {
			$n -= $i;
	}
	$returnstr = substr($sourcestr, 0, $n);
 

	if ( substr($sourcestr, $n, 6)){
          $returnstr = $returnstr . $suffix;//超过长度时在尾处加上省略号
      }
	return $returnstr;
}

function IP($ip='',$file='UTFWry.dat') {
	import("@.ORG.IpLocation");
	$iplocation = new IpLocation($file);
	$location = $iplocation->getlocation($ip);
	return $location;
}

function byte_format($input, $dec=0)
{
  $prefix_arr = array("B", "K", "M", "G", "T");
  $value = round($input, $dec);
  $i=0;
  while ($value>1024)
  {
     $value /= 1024;
     $i++;
  }
  $return_str = round($value, $dec).$prefix_arr[$i];
  return $return_str;
}

/**
 +----------------------------------------------------------
 * 获取登录验证码 默认为4位数字
 +----------------------------------------------------------
 * @param string $fmode 文件名
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function build_verify ($length=4,$mode=1) {
    return rand_string($length,$mode);
}


function make_urlrule($url,$lang,$action,$MOREREQUEST=''){
		preg_match_all ("/{([\w\$]+)}/",$url, $matches);
		//$REQUEST= implode(',',$matches[0]);
	
		if(strstr($url,'{$parentdir') && C('URL_PATHINFO_DEPR')=='/'){
			if(APP_LANG){
				foreach((array)$lang as $r){
					$Category = F('Category_'.$r);
					foreach((array)$Category as $key =>$r){
						if($r['parentid']==0)$pcatdir[]=$r['catdir'];				
					}
				}
			}else{
				$Category = F('Category');
				foreach((array)$Category as $key =>$r){
					if($r['parentid']==0)$pcatdir[]=$r['catdir'];				
				}
			}
			unset($Category);		
			$parent_rule = '('.implode('|',$pcatdir).')\/';
			//if(preg_match("/^[\w]+$/",$str)){ }
		}

		$REQUEST=str_replace(array('{$parentdir}','{$module}','{$moduleid}','{$catdir}','{$year}','{$month}','{$day}','{$catid}','{$id}','{$page}'),array('','module','moduleid','catdir','year','month','day','catid','id',C('VAR_PAGE')),$matches[0]);
		$rule=str_replace(array('{$parentdir}','{$module}','{$moduleid}','{$catdir}','{$year}','{$month}','{$day}','{$catid}','{$id}','{$page}','/',C('URL_HTML_SUFFIX')),array('','([A-Z]{1}[a-z]+)','(\d+)','([\w^_]+)','(\d+)','(\d+)','(\d+)','(\d+)','(\d+)','(\d+)','\/',''),$url);
		
		$i=0;$j=1;$k=2;$n=3;$m=4;
		foreach($REQUEST as $key =>$r){
			if($r){
			$i=$i+1;
			$request .=$r.'=:'.$i.'&';
			$j=$j+1;
			$request_lang .=$r.'=:'.$j.'&';
			$k=$k+1;
			$request_lang_2 .=$r.'=:'.$k.'&'; //二级
			$n=$n+1;
			$request_lang_3 .=$r.'=:'.$n.'&'; //三级
			}
		}

		if(APP_LANG){
			$langrule = '('.implode('|',$lang).')\/';
			
			if($parent_rule){
				$data[] = '\'/^'.$langrule.$parent_rule.'([\w^_]+)\/'.$rule.'$/\' => \'Urlrule/'.$action.'?l=:1&parentdir=:2&'.$request_lang_3.$MOREREQUEST.$langrequest.'\'';
				$data[] = '\'/^'.$langrule.$parent_rule.$rule.'$/\' => \'Urlrule/'.$action.'?l=:1&parentdir=:2&'.$request_lang_2.$MOREREQUEST.$langrequest.'\'';
				$data[] = '\'/^'.$parent_rule.'([\w^_]+)\/'.$rule.'$/\' => \'Urlrule/'.$action.'?parentdir=:1&'.$request_lang_2.$MOREREQUEST.$langrequest.'\'';	
				$data[] = '\'/^'.$parent_rule.$rule.'$/\' => \'Urlrule/'.$action.'?parentdir=:1&'.$request_lang.$MOREREQUEST.$langrequest.'\'';
				
				if(strstr($url,'{$page')){
					$data[] = '\'/^'.$langrule.$parent_rule.'(\d+)$/\' => \'Urlrule/'.$action.'?l=:1&catdir=:2&p=:3\'';	 
					$data[] = '\'/^'.$parent_rule.'(\d+)$/\' => \'Urlrule/'.$action.'?catdir=:1&p=:2\'';
				}else{
					$data[] = '\'/^'.$langrule.$parent_rule.'$/\' => \'Urlrule/'.$action.'?l=:1&catdir=:2\'';	 
					$data[] = '\'/^'.$parent_rule.'$/\' => \'Urlrule/'.$action.'?catdir=:1\'';
				}
			}else{
				$data[] = '\'/^'.$langrule.$rule.'$/\' => \'Urlrule/'.$action.'?l=:1&'.$request_lang.$MOREREQUEST.$langrequest.'\'';	 
				$data[]='\'/^'.$rule.'$/\' => \'Urlrule/'.$action.'?'.$request.$MOREREQUEST.'\'';
			}
			$data = str_replace('\/$','$',$data);
			$data= implode(",\n",$data);
		}else{
			if($parent_rule){
				$data[] = '\'/^'.$parent_rule.'([\w^_]+)\/'.$rule.'$/\' => \'Urlrule/'.$action.'?parentdir=:1&'.$request_lang_2.$MOREREQUEST.$langrequest.'\'';	
				$data[] = '\'/^'.$parent_rule.$rule.'$/\' => \'Urlrule/'.$action.'?parentdir=:1&'.$request_lang.$MOREREQUEST.$langrequest.'\'';			
				if(strstr($url,'{$page')){				
					$data[] = '\'/^'.$parent_rule.'(\d+)$/\' => \'Urlrule/'.$action.'?catdir=:1&p=:2\'';
				}else{ 
					$data[] = '\'/^'.$parent_rule.'$/\' => \'Urlrule/'.$action.'?catdir=:1\'';
				}
			}else{
				$urlrule='\'/^'.$rule.'$/\' => \'Urlrule/'.$action.'?'.$request.$MOREREQUEST.'\'';
				$data = str_replace('\/$','$',$urlrule);
			}
		}
		return $data;
}

function routes_cache($URL_URLRULE=''){

			$urlstr .=  '\':l'.C('URL_PATHINFO_DEPR').'Tags'.C('URL_PATHINFO_DEPR').':module'.C('URL_PATHINFO_DEPR').':tag'.C('URL_PATHINFO_DEPR').':p\' => \'Home/Tags/index\','."\n";
			$urlstr .=  '\':l'.C('URL_PATHINFO_DEPR').'Tags'.C('URL_PATHINFO_DEPR').':tag'.C('URL_PATHINFO_DEPR').':p\' => \'Home/Tags/index\','."\n";
			$urlstr .=  '\':l'.C('URL_PATHINFO_DEPR').'Tags'.C('URL_PATHINFO_DEPR').':module'.C('URL_PATHINFO_DEPR').':tag\' => \'Home/Tags/index\','."\n";
			$urlstr .=  '\':l'.C('URL_PATHINFO_DEPR').'Tags'.C('URL_PATHINFO_DEPR').':p\d\' => \'Home/Tags/index\','."\n";
			$urlstr .=  '\':l'.C('URL_PATHINFO_DEPR').'Tags'.C('URL_PATHINFO_DEPR').':tag\' => \'Home/Tags/index\','."\n";
			$urlstr .=  '\':l'.C('URL_PATHINFO_DEPR').'Tags\' => \'Home/Tags/index\','."\n";

			$urlstr .=  '\'Tags'.C('URL_PATHINFO_DEPR').':module'.C('URL_PATHINFO_DEPR').':tag'.C('URL_PATHINFO_DEPR').':p\' => \'Home/Tags/index\','."\n";
			$urlstr .=  '\'Tags'.C('URL_PATHINFO_DEPR').':tag'.C('URL_PATHINFO_DEPR').':p\' => \'Home/Tags/index\','."\n";
			$urlstr .=  '\'Tags'.C('URL_PATHINFO_DEPR').':module'.C('URL_PATHINFO_DEPR').':tag\' => \'Home/Tags/index\','."\n";
			$urlstr .=  '\'Tags'.C('URL_PATHINFO_DEPR').':p\d\' => \'Home/Tags/index\','."\n";
			$urlstr .=  '\'Tags'.C('URL_PATHINFO_DEPR').':tag\' => \'Home/Tags/index\','."\n";
			$urlstr .=  '\'Tags\' => \'Home/Tags/index\','."\n";
			/*
			$urlstr .=  '\'^Tags$\' => \'Home/Tags/index\','."\n";
			$urlstr .=  '\'/^Tags\/(\d+).html$/\' => \'Home/Tags/index?p=:1\','."\n";
			*/
			if(APP_LANG){
				$Lang=F('Lang');
				foreach((array)$Lang as $key =>$r){$langarr[]=$key;}
				$urlstr .=  '\'/^('.implode('|',$langarr).')$/\' => \'Index/index?l=:1\','."\n";
			}
			
		

			$URL_URLRULE = $URL_URLRULE ? $URL_URLRULE : C('URL_URLRULE'); 
			$urlrule = is_array($URL_URLRULE) ?  $URL_URLRULE : explode(':::',$URL_URLRULE);
			$list=explode('|',$urlrule[1]);
			$show=explode('|',$urlrule[0]);
			$listurls[]= make_urlrule($show[1],$langarr,'show');
			$listurls[]= make_urlrule($show[0],$langarr,'show');
			$listurls[]= make_urlrule($list[1],$langarr,'index');
			$listurls[]= make_urlrule($list[0],$langarr,'index');		
			
			$url = implode(",\n",$listurls);
			file_put_contents(DATA_PATH.'Routes.php', "<?php\nreturn array(\n" . $urlstr.$url . "\n);\n?>");
			if(is_file(RUNTIME_PATH.'~runtime.php'))@unlink(RUNTIME_PATH.'~runtime.php');
			if(is_file(RUNTIME_PATH.'~allinone.php'))@unlink(RUNTIME_PATH.'~allinone.php');
}

function HOMEURL($lang){
	if(C('URL_M')==1)$index='/index.php/';
	$lang= C('URL_LANG')!=$lang ? $lang : '';
	if(C('URL_M') > 0){
		$url =$lang ? __ROOT__.$index.$lang.'/' :  __ROOT__.'/';
	}else{
		$url =$lang ?  __ROOT__.'/index.php?l='.$lang :  __ROOT__.'/';
	}
	return $url;
}
function URL($url='',$params=array()) { 
	
	if(APP_LANG)$lang = getlang();

	if(!empty($url)){
		list($path, $query) = explode('?',$url);
		list($group, $a) = explode('/',$path);
		list($g, $m) = explode('-',$group);
		$params= http_build_query($params);
		$params = !empty($params) ? '&' . $params : '';
		$query =  !empty($query) ? '&'.$query : '';
		//parse_str($_SERVER['QUERY_STRING'],$urlarr);	
		if($lang) $langurl = '&l='.$lang;
		if (strcasecmp($g,'Home')== 0){
			$url = __ROOT__.'/index.php?m='.$m.'&a='.$a.$query.$params.$langurl;
		}else{
			$url = __ROOT__.'/index.php?g='.$g.'&m='.$m.'&a='.$a.$query.$params.$langurl;
		}
	}else{
		if(C('URL_M')==1)$index='/index.php/';
		if(C('URL_M') > 0){
			$url = $lang ? __ROOT__.$index.$lang.'/' :  __ROOT__.'/';
		}else{
			$url = $lang ? __ROOT__.'/index.php?l='.$lang :  __ROOT__.'/';
		}
	}
	return $url;
}



function TAGURL($data,$p=''){
	$index= C('URL_M')==1 ? __ROOT__.'index.php/' : __ROOT__.'/';
	if(APP_LANG)$lang=getlang();
	if(C('URL_M')==0){
			if($data['moduleid'] > 0 && $data['moduleid']!=2) $params['moduleid']=$data['moduleid'] ;
			if($data['slug']) $params['tag']=$data['slug'] ;
			if($lang)$params['l']=$lang;			
			$url=URL('Home-Tags/index',$params);
			if($p)$url=$url.'&p={$page}';
	}else{
			$tag = $data['slug'] ? '/'.$data['slug'] : '';
			$module = ($data['moduleid'] > 0 && $data['moduleid']!=2) ?  '/'.$data['module'] : ''; 
			$langurl = $lang ? $lang.'/' : '' ;
			$url=$index.$langurl.'Tags'.$module.$tag.'/';
			if($p)$url=$url.'{$page}'.C('URL_HTML_SUFFIX');
	}
	return $url;
}
	 
function getlang($have=''){
	if($have){
		if(strcasecmp(GROUP_NAME,'Admin')== 0)
			$lang =  LANG_NAME;
		else
			$lang =  $_REQUEST['l'] ? $_REQUEST['l'] : C('URL_LANG');
	}else{
		if(strcasecmp(GROUP_NAME,'Admin')== 0)
			$lang =  C('URL_LANG')!= LANG_NAME ? LANG_NAME : '';
		else
			$lang = $_REQUEST['l'] && C('URL_LANG')!=$_REQUEST['l'] ? $_REQUEST['l'] : '';
	}
	return $lang;
}

function geturl($cat,$data='',$Urlrule=''){
		//$Urlrule =F('Urlrule');
		$id=$data['id']; 
		$URL_MODEL =C('URL_M');
		if(APP_LANG)$lang = getlang();

		$parentdir = $cat['parentdir'];
		$catdir = $cat['catdir'];
		$year = date('Y',$data['createtime']);
		$month = date('m',$data['createtime']);
		$day = date('d',$data['createtime']);
		$module = $cat['module'];
		$moduleid =$cat['moduleid'];
		$catid=$cat['id'];
		
		if($cat['ishtml']){
			if($cat['urlruleid'] && $Urlrule){
				$showurlrule = $Urlrule[$cat['urlruleid']]['showurlrule'];
				$listurlrule = $Urlrule[$cat['urlruleid']]['listurlrule'];
			}else{
				echo 'This cat has not urlruleid or no Urlrule.';exit;
			}
		}else{
			if($URL_MODEL==0){
				$langurl = $lang ?  '&l='.LANG_NAME : '';
				if($id){
					$url[] = U("Home/$cat[module]/show?id=$id".$langurl);
					$url[] = U("Home/$cat[module]/show?id=".$id.$langurl.'&'.C('VAR_PAGE').'={$page}');	
				}else{
					$url[] = U("Home/$cat[module]/index?id=$cat[id]".$langurl);
					$url[] = U("Home/$cat[module]/index?id=$cat[id]$langurl&".C('VAR_PAGE').'={$page}');
				}
				$urls = str_replace('g=Admin&','',$url);
				$urls = str_replace('g=Home&','',$url);
			}else{
				$urlrule = explode(':::',C('URL_URLRULE'));
				$showurlrule = $urlrule[0];
				$listurlrule = $urlrule[1];
			}
		}
		if(empty($urls)){
			$index =  $URL_MODEL==1 ? __ROOT__.'/index.php/' : __ROOT__.'/';
			$langurl = $lang ? $lang.'/' : '';
			if($id){
				$urls = str_replace(array('{$parentdir}','{$module}','{$moduleid}','{$catdir}','{$year}','{$month}','{$day}','{$catid}','{$id}'),array($parentdir,$module,$moduleid,$catdir,$year,$month,$day,$catid,$id),$showurlrule);
			}else{
				$urls = str_replace(array('{$parentdir}','{$module}','{$moduleid}','{$catdir}','{$year}','{$month}','{$day}','{$catid}','{$id}'),array($parentdir,$module,$moduleid,$catdir,$year,$month,$day,$catid,$id),$listurlrule);
			}
			$urls = explode('|',$urls);
			$urls[0]=$index.$langurl.$urls[0];
			$urls[1]=$index.$langurl.$urls[1];		
		}
		return $urls;
}


function content_pages($num, $p,$pageurls) {

	$multipage = '';
	$page = 11;
	$offset = 4;
	$pages = $num;
	$from = $p - $offset;
	$to = $p + $offset;
	$more = 0;
	if($page >= $pages) {
		$from = 2;
		$to = $pages-1;
	} else {
		if($from <= 1) {
			$to = $page-1;
			$from = 2;
		} elseif($to >= $pages) {
			$from = $pages-($page-2);
			$to = $pages-1;
		}
		$more = 1;
	}
	if($p>0) {
		$perpage = $p == 1 ? 1 : $p-1;
		if($perpage==1){
			$multipage .= '<a class="a1" href="'.$pageurls[$perpage][0].'">'.L('previous').'</a>';
		}else{
			$multipage .= '<a class="a1" href="'.$pageurls[$perpage][1].'">'.L('previous').'</a>';
		}
		if($p==1) {
			$multipage .= ' <span>1</span>';
		} elseif($p>6 && $more) {
			$multipage .= ' <a href="'.$pageurls[1][0].'">1</a>..';
		} else {
			$multipage .= ' <a href="'.$pageurls[1][0].'">1</a>';
		}
	}
	for($i = $from; $i <= $to; $i++) {
		if($i != $p) {
			$multipage .= ' <a href="'.$pageurls[$i][1].'">'.$i.'</a>';
		} else {
			$multipage .= ' <span>'.$i.'</span>';
		}
	}
	if($p<$pages) {
		if($p<$pages-5 && $more) {
			$multipage .= ' ..<a href="'.$pageurls[$pages][1].'">'.$pages.'</a> <a class="a1" href="'.$pageurls[$p+1][1].'">'.L('next').'</a>';
		} else {
			$multipage .= ' <a href="'.$pageurls[$pages][1].'">'.$pages.'</a> <a class="a1" href="'.$pageurls[$p+1][1].'">'.L('next').'</a>';
		}
	} elseif($p==$pages) {
		$multipage .= ' <span>'.$pages.'</span> <a class="a1" href="'.$pageurls[$p][1].'">'.L('next').'</a>';
	}
	return $multipage;
}

function thumb($f, $tw=300, $th=300 ,$autocat=0, $nopic = 'nopic.jpg',$t=''){
	if(strstr($f,'://')) return $f;
	if(empty($f)) return __ROOT__.'/Public/Images/'.$nopic;
	$f= '.'.str_replace(__ROOT__,'',$f);
	
	$temp = array(1=>'gif', 2=>'jpeg', 3=>'png');
	list($fw, $fh, $tmp) = getimagesize($f);
	  /* 检查原始文件是否存在及获得原始文件的信息 */
        $org_info = @getimagesize($f);
        /* 原始图片以及缩略图的尺寸比例 */
        $scale_org      = $org_info[0] / $org_info[1];
     if ($org_info[0] / $tw > $org_info[1] / $th)
        {
            $lessen_width  = $tw;
            $lessen_height  = $tw / $scale_org;
        }
        else
        {
            /* 原始图片比较高，则以高度为准 */
            $lessen_width  = $th * $scale_org;
            $lessen_height = $th;
        }

        $dst_x = ($tw  - $lessen_width)  / 2;
        $dst_y = ($th - $lessen_height) / 2;
        
	if(empty($t)){
		
			$pathinfo = pathinfo($f);
			$t = $pathinfo['dirname'].'/thumb_'.$tw.'_'.$th.'_'.$pathinfo['basename'];
			if(is_file($t)){
				return  __ROOT__.substr($t,1);
			}	
	}
	
	if(!$temp[$tmp]){	return false; }



	if($fw/$tw > $fh/$th){
		$fw = $tw * ($fh/$th);
		}else{
		$fh = $th * ($fw/$tw);
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
        $clr = imagecolorallocate($timg, 255, 255, 255); //背景颜色
        imagefilledrectangle($timg, 0, 0, $tw, $th, $clr);

	if(function_exists('imagecopyresampled'))
		imagecopyresampled($timg, $fimg, $dst_x,$dst_y, 0,0, $lessen_width,$lessen_height, $org_info[0],$org_info[1]);
	else
		imagecopyresized($timg, $fimg, $dst_x,$dst_y, 0,0, $lessen_width,$lessen_height, $org_info[0],$org_info[1]);
	if($tmp=='gif' || $tmp=='png') {
		$background_color  =  imagecolorallocate($timg,  0, 0, 0);  //  指派一个绿色
		imagecolortransparent($timg, $background_color);  //  设置为透明色，若注释掉该行则输出绿色的图
	}
	$outfunc($timg, $t,99);
	imagedestroy($timg);
	imagedestroy($fimg);
	return  __ROOT__.substr($t,1);
}



/*!
 * ubb2html support for php
 * @requires xhEditor
 * 
 * @author Yanis.Wang<yanis.wang@gmail.com>
 * @site http://xheditor.com/
 * @licence LGPL(http://www.opensource.org/licenses/lgpl-license.php)
 * 
 * @Version: 0.9.10 (build 110801)
 */
function ubb2html($sUBB)
{	
	$sHtml=$sUBB;
	
	global $emotPath,$cnum,$arrcode,$bUbb2htmlFunctionInit;$cnum=0;$arrcode=array();
	$emotPath='../xheditor_emot/';//表情根路径
	
	if(!$bUbb2htmlFunctionInit){
	function saveCodeArea($match)
	{
		global $cnum,$arrcode;
		$cnum++;$arrcode[$cnum]=$match[0];
		return "[\tubbcodeplace_".$cnum."\t]";
	}}
	$sHtml=preg_replace_callback('/\[code\s*(?:=\s*((?:(?!")[\s\S])+?)(?:"[\s\S]*?)?)?\]([\s\S]*?)\[\/code\]/i','saveCodeArea',$sHtml);
	
	//$sHtml=preg_replace("/&/",'&amp;',$sHtml);
	$sHtml=preg_replace("/</",'&lt;',$sHtml);
	$sHtml=preg_replace("/>/",'&gt;',$sHtml);
	$sHtml=preg_replace("/\r?\n/",'<br />',$sHtml);
	
	$sHtml=preg_replace("/\[(\/?)(b|u|i|s|sup|sub)\]/i",'<$1$2>',$sHtml);
	$sHtml=preg_replace('/\[color\s*=\s*([^\]"]+?)(?:"[^\]]*?)?\s*\]/i','<span style="color:$1;">',$sHtml);
	if(!$bUbb2htmlFunctionInit){
	function getSizeName($match)
	{
		$arrSize=array('10px','13px','16px','18px','24px','32px','48px');
		if(preg_match("/^\d+$/",$match[1]))$match[1]=$arrSize[$match[1]-1];
		return '<span style="font-size:'.$match[1].';">';
	}}
	$sHtml=preg_replace_callback('/\[size\s*=\s*([^\]"]+?)(?:"[^\]]*?)?\s*\]/i','getSizeName',$sHtml);
	$sHtml=preg_replace('/\[font\s*=\s*([^\]"]+?)(?:"[^\]]*?)?\s*\]/i','<span style="font-family:$1;">',$sHtml);
	$sHtml=preg_replace('/\[back\s*=\s*([^\]"]+?)(?:"[^\]]*?)?\s*\]/i','<span style="background-color:$1;">',$sHtml);
	$sHtml=preg_replace("/\[\/(color|size|font|back)\]/i",'</span>',$sHtml);
	
	for($i=0;$i<3;$i++)$sHtml=preg_replace('/\[align\s*=\s*([^\]"]+?)(?:"[^\]]*?)?\s*\](((?!\[align(?:\s+[^\]]+)?\])[\s\S])*?)\[\/align\]/','<p align="$1">$2</p>',$sHtml);
	$sHtml=preg_replace('/\[img\]\s*(((?!")[\s\S])+?)(?:"[\s\S]*?)?\s*\[\/img\]/i','<img src="$1" alt="" />',$sHtml);
	if(!$bUbb2htmlFunctionInit){
	function getImg($match)
	{
		$alt=$match[1];$p1=$match[2];$p2=$match[3];$p3=$match[4];$src=$match[5];
		$a=$p3?$p3:(!is_numeric($p1)?$p1:'');
		return '<img src="'.$src.'" alt="'.$alt.'"'.(is_numeric($p1)?' width="'.$p1.'"':'').(is_numeric($p2)?' height="'.$p2.'"':'').($a?' align="'.$a.'"':'').' />';
	}}
	$sHtml=preg_replace_callback('/\[img\s*=([^,\]]*)(?:\s*,\s*(\d*%?)\s*,\s*(\d*%?)\s*)?(?:,?\s*(\w+))?\s*\]\s*(((?!")[\s\S])+?)(?:"[\s\S]*)?\s*\[\/img\]/i','getImg',$sHtml);
	if(!$bUbb2htmlFunctionInit){
	function getEmot($match)
	{
		global $emotPath;
		$arr=split(',',$match[1]);
		if(!isset($arr[1])){$arr[1]=$arr[0];$arr[0]='default';}
		$path=$emotPath.$arr[0].'/'.$arr[1].'.gif';
		return '<img src="'.$path.'" alt="'.$arr[1].'" />';
	}}
	$sHtml=preg_replace_callback('/\[emot\s*=\s*([^\]"]+?)(?:"[^\]]*?)?\s*\/\]/i','getEmot',$sHtml);
	$sHtml=preg_replace('/\[url\]\s*(((?!")[\s\S])*?)(?:"[\s\S]*?)?\s*\[\/url\]/i','<a href="$1">$1</a>',$sHtml);
	$sHtml=preg_replace('/\[url\s*=\s*([^\]"]+?)(?:"[^\]]*?)?\s*\]\s*([\s\S]*?)\s*\[\/url\]/i','<a href="$1">$2</a>',$sHtml);
	$sHtml=preg_replace('/\[email\]\s*(((?!")[\s\S])+?)(?:"[\s\S]*?)?\s*\[\/email\]/i','<a href="mailto:$1">$1</a>',$sHtml);
	$sHtml=preg_replace('/\[email\s*=\s*([^\]"]+?)(?:"[^\]]*?)?\s*\]\s*([\s\S]+?)\s*\[\/email\]/i','<a href="mailto:$1">$2</a>',$sHtml);
	$sHtml=preg_replace("/\[quote\]([\s\S]*?)\[\/quote\]/i",'<blockquote>$1</blockquote>',$sHtml);
	if(!$bUbb2htmlFunctionInit){
	function getFlash($match)
	{
		$w=$match[1];$h=$match[2];$url=$match[3];
		if(!$w)$w=480;if(!$h)$h=400;
		return '<embed type="application/x-shockwave-flash" src="'.$url.'" wmode="opaque" quality="high" bgcolor="#ffffff" menu="false" play="true" loop="true" width="'.$w.'" height="'.$h.'" />';
	}}
	$sHtml=preg_replace_callback('/\[flash\s*(?:=\s*(\d+)\s*,\s*(\d+)\s*)?\]\s*(((?!")[\s\S])+?)(?:"[\s\S]*?)?\s*\[\/flash\]/i','getFlash',$sHtml);
	if(!$bUbb2htmlFunctionInit){
	function getMedia($match)
	{
		$w=$match[1];$h=$match[2];$play=$match[3];$url=$match[4];
		if(!$w)$w=480;if(!$h)$h=400;
		return '<embed type="application/x-mplayer2" src="'.$url.'" enablecontextmenu="false" autostart="'.($play=='1'?'true':'false').'" width="'.$w.'" height="'.$h.'" />';
	}}
	$sHtml=preg_replace_callback('/\[media\s*(?:=\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*(\d+)\s*)?)?\]\s*(((?!")[\s\S])+?)(?:"[\s\S]*?)?\s*\[\/media\]/i','getMedia',$sHtml);
	if(!$bUbb2htmlFunctionInit){
	function getTable($match)
	{
		return '<table'.(isset($match[1])?' width="'.$match[1].'"':'').(isset($match[2])?' bgcolor="'.$match[2].'"':'').'>';
	}}
	$sHtml=preg_replace_callback('/\[table\s*(?:=(\d{1,4}%?)\s*(?:,\s*([^\]"]+)(?:"[^\]]*?)?)?)?\s*\]/i','getTable',$sHtml);
	if(!$bUbb2htmlFunctionInit){
	function getTR($match){return '<tr'.(isset($match[1])?' bgcolor="'.$match[1].'"':'').'>';}}
	$sHtml=preg_replace_callback('/\[tr\s*(?:=(\s*[^\]"]+))?(?:"[^\]]*?)?\s*\]/i','getTR',$sHtml);
	if(!$bUbb2htmlFunctionInit){
	function getTD($match){
		$col=isset($match[1])?$match[1]:0;$row=isset($match[2])?$match[2]:0;$w=isset($match[3])?$match[3]:null;
		return '<td'.($col>1?' colspan="'.$col.'"':'').($row>1?' rowspan="'.$row.'"':'').($w?' width="'.$w.'"':'').'>';
	}}
	$sHtml=preg_replace_callback("/\[td\s*(?:=\s*(\d{1,2})\s*,\s*(\d{1,2})\s*(?:,\s*(\d{1,4}%?))?)?\s*\]/i",'getTD',$sHtml);
	$sHtml=preg_replace("/\[\/(table|tr|td)\]/i",'</$1>',$sHtml);
	$sHtml=preg_replace("/\[\*\]((?:(?!\[\*\]|\[\/list\]|\[list\s*(?:=[^\]]+)?\])[\s\S])+)/i",'<li>$1</li>',$sHtml);
	if(!$bUbb2htmlFunctionInit){
	function getUL($match)
	{
		$str='<ul';
		if(isset($match[1]))$str.=' type="'.$match[1].'"';
		return $str.'>';
	}}
	$sHtml=preg_replace_callback('/\[list\s*(?:=\s*([^\]"]+))?(?:"[^\]]*?)?\s*\]/i','getUL',$sHtml);
	$sHtml=preg_replace("/\[\/list\]/i",'</ul>',$sHtml);
	$sHtml=preg_replace("/\[hr\/\]/i",'<hr />',$sHtml);

	for($i=1;$i<=$cnum;$i++)$sHtml=str_replace("[\tubbcodeplace_".$i."\t]", $arrcode[$i],$sHtml);

	if(!$bUbb2htmlFunctionInit){
	function fixText($match)
	{
		$text=$match[2];
		$text=preg_replace("/\t/",'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',$text);
		$text=preg_replace("/ /",'&nbsp;',$text);
		return $match[1].$text;
	}}
	$sHtml=preg_replace_callback('/(^|<\/?\w+(?:\s+[^>]*?)?>)([^<$]+)/i','fixText',$sHtml);
	
	$bUbb2htmlFunctionInit=true;
	
	return $sHtml;
}


function Pinyin($_String) {
$_DataKey = "a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha".
   "|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|".
   "cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er".
   "|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui".
   "|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang".
   "|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang".
   "|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue".
   "|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne".
   "|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen".
   "|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang".
   "|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|".
   "she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|".
   "tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu".
   "|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you".
   "|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|".
   "zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo";
$_DataValue = "-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990".
   "|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725".
   "|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263".
   "|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003".
   "|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697".
   "|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211".
   "|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922".
   "|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468".
   "|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664".
   "|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407".
   "|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959".
   "|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652".
   "|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369".
   "|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128".
   "|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914".
   "|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645".
   "|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149".
   "|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087".
   "|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658".
   "|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340".
   "|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888".
   "|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585".
   "|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847".
   "|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055".
   "|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780".
   "|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274".
   "|-10270|-10262|-10260|-10256|-10254";
$_TDataKey   = explode('|', $_DataKey);
$_TDataValue = explode('|', $_DataValue);
$_Data =  array_combine($_TDataKey, $_TDataValue);
arsort($_Data);
reset($_Data);
$_String= auto_charset($_String,'utf-8','gbk');
$_Res = '';
for($i=0; $i<strlen($_String); $i++) {
      $_P = ord(substr($_String, $i, 1));
      if($_P>160) { $_Q = ord(substr($_String, ++$i, 1)); $_P = $_P*256 + $_Q - 65536; }
      $_Res .= _Pinyin($_P, $_Data);
}
return preg_replace("/[^a-z0-9]*/", '', $_Res);
}

// 自动转换字符集 支持数组转换
function auto_charset($fContents, $from='gbk', $to='utf-8') {
    $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
    $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
    if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
        //如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    if (is_string($fContents)) {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($fContents, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            return $fContents;
        }
    } elseif (is_array($fContents)) {
        foreach ($fContents as $key => $val) {
            $_key = auto_charset($key, $from, $to);
            $fContents[$_key] = auto_charset($val, $from, $to);
            if ($key != $_key)
                unset($fContents[$key]);
        }
        return $fContents;
    }
    else {
        return $fContents;
    }
}

function _Pinyin($_Num, $_Data) {
   if    ($_Num>0      && $_Num<160   ) return chr($_Num);
   elseif($_Num<-20319 || $_Num>-10247) return '';
   else {
        foreach($_Data as $k=>$v){ if($v<=$_Num) break; }
        return $k;
   }
}

function return_url($code){		
		$config = APP_LANG ?  F('Config_'.LANG_NAME) :  F('Config');
		return $config['site_url'].'/index.php?g=User&m=Pay&a=respond&code='.$code;
}

function pickupReceiveUrl($code){		
		$config = APP_LANG ?  F('Config_'.LANG_NAME) :  F('Config');
		return $config['site_url'].'/index.php?g=User&m=Pay&a='.$code;
}

function order_pay_status($sn,$value){
	$cart['status'] =1;
	if($value==2) $cart['pay_time'] =time();
	/*通过验证*/
	$order=M('Order')->where(array('sn'=>$sn))->find();
	if (!$order) $order=M('Wechat_order')->where(array('sn'=>$sn))->find();
	switch ($order['type']) {
		case '0':
			/*查出订单，并更改订单状态*/
			$sp[] = $order;
			$sp[] = '商品';
			if(!empty($order))
			{
				//$cart['pay_status'] =$value;
				//$r = M('Order')->where("sn='{$sn}'")->save($cart);
				//如果支付状态是未支付时就执行
				put_consume($order['order_amount'],1,$order['userid'],3);//写入记录表

				$con['id']=$order['id'];
				$con['status']=1;//订单状态变为确认
				$con['pay_id']=3;//订单支付类型为支付宝支付
				$con['pay_name']='支付宝支付';//订单支付类型为支付宝支付
				$con['pay_status']=2;//支付状态变为已支付
				$con['cod_amount']=0.00;//货到付款金额清零
				$con['pay_time']=mktime();//支付时间
				$r=M('order')->save($con);
				//执行电子现金返还
				$order_data=M("order_data")->field("number,ratio,product_id")->where("order_id=".$order['id'])->select();
				$user_radio=0;
				foreach ($order_data as $key => $value) {
					$user_radio+=intval($value['number'])*floatval($value['ratio']);
					if ($order['userid']!=5209 && $order['userid']!=1217)
					{
						M("product")->where("id=".$value['product_id'])->setDec('stock',$value['number']);//扣库存
					}
				}
				
				$user=M("user")->field("cash_use")->where("id=".$order['userid'])->find();
				$user_data['id']=$order['userid'];
				$user_data['cash_use']=floatval($user['cash_use'])+$user_radio;
				$res_cash=M("user")->save($user_data);

				if($res_cash){
					put_consume($user_radio,5,$order['userid'],1);//写入记录表
				}
				//电子现金end
				
				/*物流发货*/
				if($order['is_private']!=1 && $order['userid']!=5209)
				{
					$shipping_res=post_shipping($order['sn']);
					if(!$shipping_res['success'])
					{
						put_shipping_error($order['id'],"102",$shipping_res['message']);
					}
					else
					{
						put_shipping_error($order['id'],"101",$shipping_res['message']);
					}
					$con['shipping_notify']=$shipping_res['success']? 1:2;//已通知发货标记
					//$con['shipping_notify']=1;//测试
				}

				$string = '订单号:'.$sn.', 支付方式：支付支付宝支付, 发货标记：'.$con['shipping_notify'].', 支付状态：'.$con['pay_status'].', 订单状态保存结果：'.$r."\r\n";
				$_order_ = fopen("order_msg.txt","a");
				fwrite($_order_, $string);
				fclose($_order_);				

			}
			/*订单操作 end*/
			break;
		case '1'://充值
			$r = M('Wechat_order')->where("sn='{$sn}'")->save($cart);
			$data_user['cash_use']=floatval($user['cash_use'])+floatval($order['amount']);
			$consume['source']=4;
			$consume['cash']=floatval($order['amount']);
			/*检查充值是否足够升级*/
			$gold=M('role')->field('gold_money,gold_fee')->where("id=4")->find();
			$gold_money=$gold ? floatval($gold['gold_money']):0;//一次性充值，变为电子现金
			if($consume['cash']>=$gold_money){
			menber_level($order['userid'],$consume['cash'],$consume['create_time']);
			}
			break;
		case '4'://押金
			$r = M('Wechat_order')->where("sn='{$sn}'")->save($cart);
			$data_user['receipt']=floatval($user['receipt'])+floatval($order['amount']);
			$data_user['test_status']=1;
			$data_user['status']=1;
			$consume['source']=3;
			$consume['cash']=floatval($order['amount']);
			break;
		case '2'://平台管理费
			$r = M('Wechat_order')->where("sn='{$sn}'")->save($cart);
			$consume['source']=6;
			$consume['pay_for_time']=$order['pay_for_time'];
			$consume['cash']=floatval($order['amount']);
			break;
		case '3'://年费
			$r = M('Wechat_order')->where("sn='{$sn}'")->save($cart);
			$consume['source']=2;
			$consume['pay_for_time']=$order['pay_for_time'];
			$consume['cash']=floatval($order['amount']);
			menber_level($order['userid'],$consume['cash'],$consume['create_time']);
			break;
		default:
			# code...
			break;
	}

	if(!empty($data_user)){
		$data_user['id']=$order['userid'];
		M("user")->save($data_user);
	}

	if(!empty($consume)){
		$consume['order_id']=$order['id'];
		$consume['user_id']=$order['userid'];
		$consume['pay_type']=1;
		M("consume")->add($consume);
	}
	if ($r===false)
	{
		$msg_info = "订单更新出错！";
		return $msg_info;
	}
	else
	{
		return true;
	}
}

function post_shipping($sn=0){
		$method="PostOrder";//方法
		$Key="B49e7d57ca6643102dbec749ae8c1b6e";//加密串
		$ordernum=$sn;//订单号
		$type=0;//0为新增订单
		$curl_url="http://wms.hans-trans.com/wms/interface/QueryService.aspx?method=".$method."&key=".$Key."&ordernum=".$ordernum."&type=".$type;
		$curl_result = file_get_contents($curl_url);
		$res=json_decode($curl_result,TRUE);
		return $res;
}

/*物流信息反馈*/
function put_shipping_error($id=0,$type=0,$msg=""){
	$data['order_id']=$id;
	$data['message']=$msg;
	$data['createtime']=mktime();
	$data['type']=$type;
	M("shipping_msg")->add($data);
	return true;
}


//自动升级
function menber_level($userid,$gold_fee,$time){
	$us=array();
	$us=M('user')->field('id,groupid,lastrecharge_time')->where('id='.$userid)->find();
	if($us['groupid']==3){
		/*升级*/
		$da['id']=$userid;
		$da['groupid']=4;//金会员为4
		$da['lastrecharge_time']=$time;
		M('user')->save($da);
	}
	return true;
}

function put_consume($cash=0,$source=0,$user_id=0,$type=0){
		$data['user_id']=$user_id;
		$data['source']=$source;
		$data['pay_type']=$type;
		$data['cash']=floatval($cash);
		$data['create_time']=mktime();
		M("consume")->add($data);
		return true;
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
function frequent_infinite_category($arr, $pid = 0 ) {
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
 * 4. 无限分类
 * @param  array   $arr  一维数组
 * @param  integer $pid  父级id
  +---------------------------------------------------------------+
  | @return array  $tree
  +---------------------------------------------------------------+
*/
function frequent_infinite_express($arr, $pid = 0 ) {
	$fn = __FUNCTION__;
	$tree = array();
	foreach ($arr as $k => $v) {
		if ($v['school'] == $pid)		
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
function frequent_tree2list($arr,$fields=array(),$icon=array(),	$prefix='', $reset=true){
	$fn = __FUNCTION__;
	$fields = $fields ? $fields : array('title'=>'title','son'=>'son');
	$icon = $icon ? $icon :array('&nbsp;&nbsp;│', '&nbsp;&nbsp;├ ', '&nbsp;&nbsp;└ ');
	$prefix = ($first) ? $prefix:$prefix.$icon[0];

	static $first=true;
	static $arr_new;
	if($reset){
		$arr_new = array();
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
function multidimensional_2_unidimensional($array,$items=false, $reset=true) {
	$fn = __FUNCTION__;
	static $return = array();
	if($reset){
		$return = array();
	}
	foreach ($array as $key=>$value){
		if (is_array($value)){
			$fn($value,$items, false); 	
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
 * 17.
 |	反解unicode中文
 |	注意，在某些系统上 iconv 函数可能无法以你预期的那样工作
 |	hexdec(bin2hex( $bin_unichar ));//$c 是unicode字符编码的int类型数值，如
 |	果是用二进制读取的数据，需要多一步转化
 +---------------------------------------------------------------+
 */

function frequent_unicode_decode_json($str){
	if (!function_exists('conv')){
		function conv($arr){
		//////////////////////////////////////////////////////////////////////////////////
		//	$code_1 = base_convert(substr($arr[0],2,2),16,10);//16进制转10进制	//
		//	$code_2 = base_convert(substr($arr[0], 4), 16, 10);			//
		//	$c = chr($code_1).chr($code_2);						//
		//	$c = iconv('UCS-2','UTF-8',$c);						//
		//////////////////////////////////////////////////////////////////////////////////
			$c= hexdec($arr[1]);
			if ($c < 0x80){
				$utf8char = chr($c);
			}else if ($c < 0x800){
				$utf8char = chr(0xC0 | $c >> 0x06).chr(0x80 | $c & 0x3F);

			}else if ($c < 0x10000){
				$utf8char = chr(0xE0 | $c >> 0x0C).chr(0x80 | $c >> 0x06 & 0x3F).chr(0x80 | $c & 0x3F);

			}else{//因为UCS-2只有两字节，所以后面的情况是不可能出现的，这里只是说明unicode HTML实体编码的用法。
				$utf8char = "&#{$c};";
			}
			return $utf8char;
		}
	}
	$str = preg_replace_callback("/\\\u([\w]{4})/",conv,$str);
	return $str;
}
?>