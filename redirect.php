<?php
	if ($_REQUEST['act']=="Login")
	{
		header("Location: http://".$_SERVER['HTTP_HOST']."/index.php?a=wechatlogin&m=Login&g=User&".$_SERVER['QUERY_STRING']);
	}

	if ($_REQUEST['act'] == 'coLogin')
	{
		header("Location: http://".$_SERVER['HTTP_HOST']."/co/index.php/Index/login.shtml?".$_SERVER['QUERY_STRING']);
	}
?>