<?php
class WeixinAction extends AdminbaseAction {
	public $gh_local_id;
	public $gh_id;
	public $appId;
	public $appSecret;
	protected $mp;
	function __construct(){
		parent::__construct();
		$gh = M('wechat')->field('id,gh_id,appId,appSecret')->where(array('uid'=>1,'status'=>1))->find();
		if(!isset($gh['appId']) || !isset($gh['appSecret'])){
			$this->error('请先配置appId,appSecret');
		}
	//	$this->appId = $this->theParam['gh']['AppId'];
	//	$this->appSecret = $this->theParam['gh']['AppSecret'];
		$this->gh_local_id = $gh['id'];
		$this->gh_id = $gh['gh_id'];
		$this->appId = $gh['appId'];
		$this->appSecret = $gh['appSecret'];
		$this->assign('gh',$gh);

		import ( '@.ORG.MP' );
		$this->mp = new MP($this->appId,$this->appSecret);
	}
	public function index(){
		
		$this->display();	
	}

	/*
	 * 菜单设置
	 */
	public function microMenu(){
		if($_POST['submit']){
			if($_POST['id'])$id = (int)$_POST['id'];
			$_POST['gh_id']=$this->gh_local_id;
			$id = M('wechat_menu')->save($_POST);
			if($id){
				$this->success('操作成功！');
				exit;
			}else{
				M('wechat_menu')->add($_POST);
				$this->success('操作成功！');
				exit;
			}
		}
		$id = (int) $_GET['id'];
		$where['id']=$id;
		$aa=M('wechat_menu');
		$info = $aa->field('id,pid,name,type,key,url,listorder,status')->where($where)->find();
		$this->assign('info',$info);
		$microMenu = M('wechat_menu')->field('id,pid,name,type,key,url,listorder,status')
			->where(array('gh_id'=>$this->gh_local_id))->order('listorder desc')->select();
		$microMenu = frequent_infinite_category($microMenu);
		$microMenu = frequent_tree2list($microMenu,array('title'=>'name','son'=>'son'));
		$this->assign('list',$microMenu);
		$this->display();
	}

	/*
	 * 菜单删除
	 */
	public function microMenuDel(){
		$data['id'] = (int)$_GET['id'];
		M('wechat_menu')->where("id=".$data['id'])->delete();
		header('Location:'.U('Weixin/microMenu'));	
	}
	private function assembleWeixinMenu($arr, $pid=0, $limit=3) {
		$tree = array();
		$count = 0;
		foreach ($arr as $k => $v) {
			if ($v['pid'] == $pid && $topMenuCount<$limit){	
				$temp=array();
				if($pid==0)	$temp['id'] = $v['id'];
				$temp['type'] = $v['type'];
				$temp['name'] = $v['name'];
				if($v['type']=='click')		
					$temp['key'] = $v['key'];
				else				
					$temp['url'] = $v['url'];
				$tree[] = $temp;
				$count++;
			}
		}
		return $tree;
	}
	public function createMenu(){
		header('Content-Type:text/html; charset=utf-8');
		$where = array('gh_id'=>$this->gh_local_id,'status'=>1);
		$all_menu = M('wechat_menu')->field('`id`,`pid`,`name`,`type`,`key`,`url`')->where($where)->order('listorder desc')->select();
		$menu = $this->assembleWeixinMenu($all_menu);
		foreach ($menu as $k=>$v){
			$sub_button = $this->assembleWeixinMenu($all_menu,$v['id'],5);
			unset($menu[$k]['id']);
			if($sub_button){
				unset($menu[$k]['type']);
				unset($menu[$k]['url']);
				unset($menu[$k]['key']);
				$menu[$k]['sub_button']=$sub_button;
			}
		}
		$menu = array('button'=>$menu);
		$menu = json_encode($menu);
		$menu = frequent_unicode_decode_json($menu);
		$return = $this->mp->createMenu($menu);

		if($return['errcode']){
			$this->error(dump($return));
		}else{
			$this->success('创建成功');
		}
	}
	/*
	 * 下载菜单到本地，适用于从别的平台移植过来的客户
	 * 需要验证本地是否存在菜单
	 */
	public function downMenu(){
		$menu = $this->mp->menuList();
		
		dump($menu);
		die();
		
	}

	public function fans(){
		if(IS_POST){
			$key = trim($_POST['key']);
			$where = ' `nickname` like "%'.$key.'%" or `city` like "%'.$key.'%" or `subscribe_time` like "%'.$key.'%"  ';
			if($_POST['inResult']){
				$where .= ' and `id` in ('.$_SESSION['result_ids'].') ';
			}
		}else{
			unset($_SESSION['result_ids']);
			$where = array('gh_id'=>$this->gh_id,'status'=>1);
			$groupid = (int) $_GET['groupid'];
			$openids = M('follow_group_association')->field('openid')->where(array('groupid'=>$groupid,'gh_id'=>$this->gh_id))->select();
			$openids = multidimensional_2_unidimensional($openids,'openid');
			if($openids){
				$where['openid'] = array('in',$openids);
			}
		}
		$count = M('follow')->field('id')->where($where)->count();
			//实现在结果中查找
		$result_ids = M('follow')->field('id')->where($where)->select();
		foreach ($result_ids as $id){
			$idstr .= $id['id'].",";
		}
		$_SESSION['result_ids'] =substr($idstr,0,-1);

		$p=new \Think\Page($count,50);
		$list = M("follow")->field('openid,nickname,subscribe_time,avatar,city')->where($where)->limit($p->firstRow.','.$p->listRows)->select();
		$this->assign('list',$list);
		$groups = M('follow_group_association')->field('openid,groupid')->where(array('gh_id'=>$this->gh_id))->select();
		$groups = $groups ? frequent_index2key($groups,'openid') : array();
		foreach ($list as $k=>$v){
			$list[$k]['groupid']=$groups[$v['openid']]['groupid'];
		}
		$this->assign('list',$list);
		$group = M('follow_group')->field('realid,name,count')->where(array('gh_id'=>$this->gh_id,'status'=>1))->select();
		$this->assign('group',$group);
		$this->assign('page',$p->show());

		$this->display();
	}
	/*
	 * 更新关注者
	 */
	function updateFollow(){
		C('TOKEN_ON',false);
		$where = array('gh_id'=>$this->gh_id,'status'=>1);
		$local_follow = M('follow')->field('openid')->where($where)->select();
		$local_follow = multidimensional_2_unidimensional($local_follow);
		$online_follow = $this->mp->getUser();
		$new_follow = array_diff($online_follow,$local_follow);
		if($new_follow){
			foreach($new_follow as $follow){
				$follow = $this->mp->getUserInfo($follow);
				$_POST['openid'] = $follow['openid'];
				$_POST['gh_id']=$this->gh_id;
				$_POST['nickname'] = $follow['nickname'];
				$_POST['gender'] = $follow['sex'];
				$_POST['avatar'] = $follow['headimgurl'];
				$_POST['city'] = $follow['city'];
				$_POST['subscribe_time'] = date('Y-m-d H:i:s',$follow['subscribe_time']);
				$_POST['status'] = 1;
				$id = M('follow')->field('id,openid')->where(array('openid'=>$follow['openid']))->find();
				$this->createUpdate('follow',$id['id']);
			}
		}
		$unsubscribe = array_diff($local_follow,$online_follow);
		if($unsubscribe){
			foreach($unsubscribe as $openid){
				M('follow')->data(array('status'=>0))->where(array('openid'=>$openid,'gh_id'=>$this->gh_id))->save();
			}
		}
		header("Location:".U('Weixin/fans'));
	}	

	/*
	 * 更新分组到本地
	 */
	function updateGroup(){
		C('TOKEN_ON',false);
		$where = array('gh_id'=>$this->gh_id,'status'=>1);
		$local_group = M('follow_group')->field('realid as id,name,count')->where($where)->select();
		$local_group = $local_group ? frequent_index2key($local_group,'id') : array();
		$online_group = $this->mp->getGroup();
		$online_group = frequent_index2key($online_group,'id');//微信分组，不存在无分组的现象
		foreach ($online_group  as $key=>$value){
			// 考虑了信息细节的变化
			$contrast = $local_group[$value['id']] ? $local_group[$value['id']]:array();
			if(array_diff_assoc($value,$contrast)){//有变化才操作 增加或者修改
				$_POST['realid'] = $value['id'];
				$_POST['name'] = $value['name'];
				$_POST['count'] = $value['count'];
				$_POST['gh_id'] = $this->gh_id;
				$_POST['status'] = 1;
				$id = M('follow_group')->field('id')->where(array('gh_id'=>$this->gh_id,'realid'=>$value['id']))->find();
				$this->createUpdate('follow_group',$id['id']);
			}
		}
		header("Location:".U('Weixin/fans'));
	}

	/*
	 * 更新分组名称到微信
	 * 不支持新建，因为创建会生成 新id 和本地的realid冲突（考虑realid = -1）
	 * 替代的，在创建本地分组的时候，是先创建微信分组，获取realiｄ 再创建本地分组的
	 */
	function updateGroupTo(){
		$where = array('gh_id'=>$this->gh_id,'status'=>1);
		$local_group = M('follow_group')->field('realid as id,name,count')->where($where)->select();
		$local_group = $local_group ? frequent_index2key($local_group,'id') : array();
		$online_group = $this->mp->getGroup();
		$online_group = frequent_index2key($online_group,'id');//微信分组，不存在无分组的现象，所以不用三元运算
		$new_group = array_diff_assoc($online_group,$local_group);
		foreach($local_group as $key=>$value){
			//本地创建分组的时候才创建，不采用批量更新创建，因为ID realid不匹配
			if($value['name']!=$online_group[$key]['name']){
				$json_param = '{"group":{"id":'.$value['id'].',"name":"'.$value['name'].'"}}';
				$t = $this->mp->updateGroup($json_param);
			}
		}	
	}

	/*
	 * 更新 关注者-分组 对应关系
	 */
	function updateUserGroup(){
		//当前使用foreach M()操作数据库，无谓增加许多IO开销,多数据操作时的系统执行时间也是个问题。
		$upDown = $_GET['type'];
		$ug = $this->mp->getUserGroup();
		$local_ug = M('follow_group_association')->field('openid,groupid')->where(array('wxid'=>session('wxid')))->select();
		$local_ug = $this->openidGroup($local_ug);
		if($upDown == 'down'){
			$new_ug = array_diff_key($ug,$local_ug);
			foreach ($new_ug as $k=>$v){
				M('follow_group_association')->data(array('wxid'=>session('wxid'),'openid'=>$k,'groupid'=>$v))->add();
			}
			$modify_ug = array_diff_key($ug,$new_ug);
			$modify_ug = array_diff_assoc($modify_ug,$local_ug);
			foreach ($modify_ug as $k=>$v){
				M('follow_group_association')->where(array('openid'=>$k))->data(array('wxid'=>session('wxid'),'groupid'=>$v))->save();
			}
			$local_group_count = M('follow_group_association')->field('groupid,count(1)')->where(array('wxid'=>session('wxid')))->group('groupid')->select();
			dump($local_group_count);die();
		}else{
			//$start = frequent_microtime_float();
			//$groups = session('MP_groups');
			$groups = $this->mp->getGroup();
			$groups = multidimensional_2_unidimensional ($groups,'id');
			//$end = frequent_microtime_float();
			//$long = $end-$start;
			//dump($long);die();
			$local_ug = array_intersect($local_ug,$groups);//排除微信上没有的分组
			$can_ug = array_intersect_key($ug,$local_ug);//排除微信上没有的openid
			$local_ug = array_intersect_key($local_ug,$can_ug);
			$ug = array_diff_assoc($local_ug,$ug);
			if($ug){
				$return = $this->mp->moveUser($ug);
				
			}
			
		}
		header("Location:".U('Weixin/fans'));
	}

	/*
	 * 关注时回复设置
	 */
	public function subscribe(){
		if(IS_POST){
			$id = (int)$_POST['id'];
			$this->createUpdate('material',$id);
		}
		$where = array('gh_id'=>$this->gh_id,'is_subscribe'=>1,'status'=>1);
		$info = M('material')->field('id,description')->where($where)->find();
		$this->assign('info',$info);
		$this->display();
	}
}
