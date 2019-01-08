<?php
return array(
	'ADMIN_ACCESS' => '90cef8d938a6be5c98f0a017ec3d2333',//兼容QQCMS的登录验证
	//路径配置
	'WEB_CACHE_PATH'	=> APP_PATH.'Runtime/',
	/*'VENDOR'		=> APP_PATH.'Lib/vendor/',*/
	'VENDOR'		=>  '/CO/Lib/vendor/',

	'SITE_URL'		=> 'http://www.ubovip.com/co',
	'DEFAULT_CHARSET'       =>  'utf-8',
	'SHOW_PAGE_TRACE' 	=>false, 
	'URL_MODEL'		=> 1,	//需要apache开启重写功能：LoadModule rewrite_module modules/mod_rewrite.so
	'URL_HTML_SUFFIX'	=> 'shtml',		//伪静态
	'VAR_PAGE'		=>  'p',
	'PAGE_COUNT'		=> 20,
	//配置数据库
	'DB_TYPE'		=> 'mysql',
	'DB_HOST'		=> 'localhost',
	//'DB_NAME'		=> 'pth',
	'DB_NAME'		=> 'haitao',
	'DB_USER'		=> 'haitao',
	'DB_PWD'		=> 'haitao0326',
	'DB_PORT'		=> '3306',
	'DB_PREFIX'		=> 'qq_',
	//配置默认模块
	'DEFAULT_MODULE'	=> 'Admin',
	'MODULE_ALLOW_LIST'	=> array('Admin'),
	'MODULE_DENY_LIST'	=> array('Common','Lib'),
	//my public class
	'AUTOLOAD_NAMESPACE' => array(
		'Lib'     => APP_PATH.'Lib',
	),
	'AUTH_ON'		=> true,
	'LOAD_EXT_CONFIG'	=> 'const', 
);
