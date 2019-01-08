<?php
/**
 *
 * index(入口文件)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <79441928@qq.com>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if (!is_file('./config.php')) header("location: ./Install");
header("Content-type: text/html; charset=utf-8");
error_reporting(E_ERROR | E_WARNING | E_PARSE);
define('QQCMS', 'QQCMS');
define('UPLOAD_PATH', './Uploads/');
define('VERSION', 'v2.1 Released');
define('UPDATETIME', '20120412');

//define('APP_PATH', './QQCMS/');
session_start();
require('mobile.php');
if($isMobile||($_SESSION['isMobile']==1)){
	define('APP_NAME', 'QQCMS');
	define('RUNTIME_PATH','./Cache/');
	define('APP_PATH', './QQCMS/');
	}
else{
	define('APP_NAME', 'QQCMS_PC');
	define('RUNTIME_PATH','./Cache_PC/');
	define('APP_PATH', './QQCMS_PC/');
	}
define('APP_LANG', true);
define('APP_DEBUG',false);
define('THINK_PATH','./Core/');
require(THINK_PATH.'/Core.php');
?>
