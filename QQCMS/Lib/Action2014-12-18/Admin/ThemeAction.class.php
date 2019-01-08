<?php
/**
 * 
 * Theme(风格选择)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
class ThemeAction extends AdminbaseAction
{
	 protected $filepath,$publicpath;
    function _initialize()
    {	
		parent::_initialize();
		$this->filepath = TMPL_PATH.'Home/'.$this->sysConfig['DEFAULT_THEME'].'/'; 
		$this->publicpath = TMPL_PATH.'Home/'.$this->sysConfig['DEFAULT_THEME'].'/Public/';
		$this->tplpath = TMPL_PATH.'Home/';
    }

    public function index()
    {
		$filed = glob($this->tplpath.'*');
		foreach ($filed as $key=>$v) {
			$arr[$key]['name'] =  basename($v);
			if(is_file($this->tplpath.$arr[$key]['name'].'/preview.jpg')){
				$arr[$key]['preview']=$this->tplpath.$arr[$key]['name'].'/preview.jpg';
			}else{
				$arr[$key]['preview']=__ROOT__.'/Public/Images/nopic.jpg';
			}
			if($this->sysConfig['DEFAULT_THEME']==$arr[$key]['name']) $arr[$key]['use']=1;
		}

		$this->assign ( 'themes',$arr );
        $this->display ();
    }
	public function chose()
	{
		$theme = $_GET['theme'];
		if($theme){
			$Model = M('Config');
			$r = $Model->where("varname='DEFAULT_THEME'")->setField('value',$theme);
			savecache('Config');
			$this->success(L('do_ok'));
		}else{
			$this->error(L('do_empty'));
		}
	}
	public function upload()
	{
		$this->display ();
	}
}
?>