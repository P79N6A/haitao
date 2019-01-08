<?php
namespace Admin\Controller;
use Think\Controller;
class WeixinController extends CommController {
	public $gh_local_id;
	public $gh_id;
	public $appId;
	public $appSecret;
	function __construct(){
		parent::__construct();
		$gh = M('gh')->field('id,gh_id,appId,appSecret')->where(array('uid'=>1))->find();
	//	$this->appId = $this->theParam['gh']['AppId'];
	//	$this->appSecret = $this->theParam['gh']['AppSecret'];
		$this->gh_local_id = $gh['id'];
		$this->gh_id = $gh['gh_id'];
		$this->appId = $gh['appId'];
		$this->appSecret = $gh['appSecret'];
		$this->assign('gh',$gh);
	}
	public function index(){
		if(IS_POST){
			//$_POST['gh_id'] = $_POST['gh_id'] ? trim($_POST['gh_id']) : unset($_POST['gh_id']);
			//$_POST['appId'] = $_POST['appId'] ? trim($_POST['appId']) : unset($_POST['appId']);
			//$_POST['appSecret'] = $_POST['appSecret'] ? trim($_POST['appSecret']) : unset($_POST['appSecret']);
			$this->createUpdate('gh',1);
		}
		$this->display();	
	}
	public function microMenu(){
		if(IS_POST){
			$id = (int)$_POST['id'];
			$_POST['gh_id']=$this->gh_local_id;
			$id = $this->createUpdate('gh_menu',$id);
			if($id){
				$this->success('操作成功！');
				exit;
			}
		}
		$id = (int) $_GET['id'];
		$this->where['id']=$id;
		$info = M('gh_menu')->field('id,pid,name,type,key,url,listorder,status')->where($this->where)->find();
		$this->assign('info',$info);
		$microMenu = M('gh_menu')->field('id,pid,name,type,key,url,listorder,status')
			->where(array('gh_id'=>$this->gh_local_id))->order('listorder desc')->select();
		$microMenu = frequent_infinite_category($microMenu);
		$microMenu = frequent_tree2list($microMenu,array('title'=>'name','son'=>'son'));
		$this->assign('list',$microMenu);
		$this->display();
	}
	public function fans(){
		echo 'xxx';
	}
	/*
	 * 这个是写配置文件的方式,(已弃用)
	 */
//	public function index(){
//		$this->assign('gh',$this->theParam['gh']);
//		if(IS_POST){
//			foreach ($_POST as $k=>$v){
//				$this->theParam['gh'][$k] = $v ? trim($v):$this->theParam['gh'][$k];
//			}
//			$str = "<?php".PHP_EOL."return ".var_export($this->theParam,true).';';
//			file_put_contents(PARAM_PATH,$str,true);
//			$this->success('操作成功');
//			exit;
//		}
//		$this->display();
//	}
}
