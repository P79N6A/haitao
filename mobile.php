<?php
$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
$isMobile=false;
$uachar = "/(nokia|sony|ericsson|mot|samsung|sgh|lg|philips|panasonic|alcatel|lenovo|cldc|midp|mobile)/i";

 if(($ua == '' || preg_match($uachar, $ua))&& !strpos(strtolower($_SERVER['REQUEST_URI']),'wap'))
  {
 //$Loaction = 'mobile/';
$isMobile=true;
  if (!empty($Loaction))
  {
  ecs_header("Location:$Loaction\n");
  exit;
  }

  }

//再次判断是否为手机客户端
if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'],'vnd.wap.wml')!==FALSE) && (strpos($_SERVER['HTTP_ACCEPT'],'text/html')===FALSE || (strpos($_SERVER['HTTP_ACCEPT'],'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'],'text/html')) )) {
	//手机访问 
	//ecs_header("Location:mobile/");
	//exit;
	$isMobile=true;
}

//再判断是否为手机客户端  
if ((preg_match("/(iphone|ipod|android)/i", strtolower(myUserAgent()))) AND strstr(strtolower(myUserAgent()), 'webkit')){
	//手机访问 
	//ecs_header("Location:mobile/");
	//exit;
	$isMobile=true;
}
//再判断是否为手机客户端
if(trim(myUserAgent()) == '' OR preg_match("/(nokia|sony|ericsson|mot|htc|samsung|sgh|lg|philips|lenovo|ucweb|opera mobi|windows mobile|blackberry)/i", strtolower(myUserAgent()))){
	//手机访问 
	//ecs_header("Location:mobile/");
	//exit;
	$isMobile=true;
}

function myUserAgent(){   
    $user_agent = ( !isset($_SERVER['HTTP_USER_AGENT'])) ? FALSE : $_SERVER['HTTP_USER_AGENT'];   
    return $user_agent;   
}