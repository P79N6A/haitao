<?php
/**
 * 
 * User/IndexAction.class.php (前台会员中心模块)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if(!defined("QQCMS")) exit("Access Denied");
class IndexAction extends BaseAction
{

	function _initialize()
    {	
		parent::_initialize();
		if(!$this->_userid){
			/*$this->assign('jumpUrl',U('User/Login/index'));
			$this->error(L('nologin'));*/
			//header("location:".U('User/Login/index'));
			header("location:".U('Home/Index/index'));
		}
		/*查找上级*/
		$shop=M('user')->field("id,shop_name")->where('id='.$this->_shopid." and groupid between 6 and 13")->find();
 		$this->assign("shop_info",$shop);
		$this->dao = M('User');
		$this->assign('bcid',0);
		$user = $this->dao->find($this->_userid);
		$this->assign('vo',$user);
		unset($_POST['status']);
		unset($_POST['groupid']);
		unset($_POST['amount']);
		unset($_POST['point']);
    }

    public function index()
    {	
    	$count=$this->news_count();
    	if(!empty($count))$this->assign("news_count",$count);
    	$user=M("user")->field("username,groupid")->where("id=".$this->_userid)->find();
    	$this->assign("the_u",$user);
        $this->display();
    }
	
	public function profile()
    {	 
		if($_POST['dosubmit']){
			$_POST['id']=$this->_userid;
			if(!$this->dao->create($_POST)) {
				$this->error($this->dao->getError());
			}
			$this->dao->update_time = time();
			$this->dao->last_ip = get_client_ip();
			$result	=	$this->dao->save();
			if(false !== $result) {
				$this->success(L('do_success'));
			}else{
				$this->error(L('do_error'));
			}
			exit;
		}
        $this->display();
    }

	public function avatar()
    {	
		
		if($_POST['dosubmit']){
		
			$_POST['id']=$this->_userid;
			if(!$this->dao->create($_POST)) {
				$this->error($this->dao->getError());
			}
			$this->dao->update_time = time();
			$this->dao->last_ip = get_client_ip();
			$result	=	$this->dao->save();
			if(false !== $result) {
				if($_POST['aid']){
				$Attachment =M('Attachment');		
				$aids =  implode(',',$_POST['aid']);
				$data['userid']= $this->_userid;
				$data['catid']= 0;
				$data['status']= '1';
				$Attachment->where("aid in (".$aids.")")->save($data);
				}

				$this->success(L('do_success'));
			}else{
				$this->error(L('do_error'));
			}
			exit;
		}

		$qqcms_auth_key = sysmd5(C('ADMIN_ACCESS').$_SERVER['HTTP_USER_AGENT']);
		$qqcms_auth = authcode('0-1-0-1-jpeg,jpg,png,gif-3-0', 'ENCODE',$qqcms_auth_key);
		$this->assign('qqcms_auth',$qqcms_auth);
        $this->display();
    }

	public function password()
    {	 
		
		if($_POST['dosubmit']){

			if(md5($_POST['verify']) != $_SESSION['verify']) {
				$this->error(L('error_verify'));
			}
			if($_POST['password'] != $_POST['repassword']){
				$this->error(L('password_repassword'));
			}
			$map	=	array();
			$map['password']= sysmd5($_POST['oldpassword']);
			if(isset($this->_userid)) {
				$map['id']		=	$this->_userid;
			}elseif(isset($this->_username)) {
				$map['username']	 =	 $this->_username;
			}
			//检查用户
			if(!$this->dao->where($map)->field('id')->find()) {
				$this->error(L('error_oldpassword'));
			}else {
				$this->dao->email = $_POST['email'];
				$this->dao->id = $this->_userid;
				$this->dao->update_time = time();
				$this->dao->password	=	sysmd5($_POST['password']);
				$r = $this->dao->save();
				$this->assign('jumpUrl',U('User/Index/password'));
				if($r){
					$this->success(L('do_success'));
				}else{
					$this->error(L('do_error'));
				}
			 }
			 exit;
		}
		$this->display();
    }
	public function tuichu(){
		$this->assign("big_title","即将推出");
		$this->assign("small_title","敬请期待！");
		$this->display();
	}

	/*收货地址管理*/
	public function address(){
		$default_address=M('user_address')->where("userid=".$this->_userid." and isdefault=1")->select();
		$address=M('user_address')->where("userid=".$this->_userid." and isdefault=0")->select();
		$area=M('area')->getfield("id,name");
		$this->assign('area',$area);
		$this->assign('default_address',$default_address);
		$this->assign('address',$address);
		$this->display("Index:address");
	}
	public function edit_address(){
		if($_REQUEST['id']){
			$id=intval($_REQUEST['id']);
			$address=M('user_address')->where("id=".$id)->find();
			$this->assign('address',$address);
		}
		$this->display("Index:edit_address");
	}

	public function delete_address(){
		if($_REQUEST['id']){
			$id=intval($_REQUEST['id']);
			M('user_address')->where("id=".$id)->delete();
			$this->address();
		}else{
			$this->address();
		}
	}
	/*站内信-我的消息*/
	public function news_count(){
		$uid=$this->_userid;
		$count=0;
		/*首先搜索整站发信*/
		$news_all=array();
			$news_all=M('message_text')->field('id')->where("is_all=0")->select();
			foreach ($news_all as $key => $value) {
				$temp=M('message_user')->field('flat')->where('message_id='.$value['id'].' and userid='.$uid)->find();
				if(!$temp){
					//如果不存在用户表，就创建
					$con['userid']=$uid;
					$con['message_id']=$value['id'];
					$con['flat']=0;
					$con['createtime']=time();
					$res=M('message_user')->add($con);
					if($res){
						$temp['flat']=$con['flat'];

					}
				}
				if($temp['flat']==0)$count++;
			}
		/*搜索整站的 end*/
		/*搜索个人的*/
		$news_single=M('message_text')->join(' `qq_message_user` on `qq_message_user`.message_id=`qq_message_text`.id')->where("`qq_message_text`.is_all=1 and `qq_message_user`.userid=".$uid)->count();
		/*end*/
		return $count+$news_single;
	}
	public function news_list(){
		$uid=$this->_userid;
		/*首先搜索整站发信*/
		$news_all=array();
			$news_all=M('message_text')->field('id,title,little_title,createtime')->where("is_all=0")->select();
			foreach ($news_all as $key => $value) {
				$temp=M('message_user')->field('flat')->where('message_id='.$value['id'].' and userid='.$uid)->find();
				if(!$temp){
					//如果不存在用户表，就创建
					$con['userid']=$uid;
					$con['message_id']=$value['id'];
					$con['flat']=0;
					$con['createtime']=time();
					$res=M('message_user')->add($con);
					if($res){
						$temp['flat']=$con['flat'];

					}
				}
				$news_all[$key]=array_merge_recursive($news_all[$key],$temp);
			}
		/*搜索整站的 end*/
		/*搜索个人的*/
		$news_single=array();
		$news_single=M('message_text')->field('`qq_message_text`.id,`qq_message_text`.title,`qq_message_text`.createtime,`qq_message_text`.little_title,`qq_message_user`.flat')->join(' `qq_message_user` on `qq_message_user`.message_id=`qq_message_text`.id')->where("`qq_message_text`.is_all=1 and `qq_message_user`.userid=".$uid)->select();
		/*end*/
		//$news_list=array_merge_recursive($news_all,$news_single);
		$this->assign("news_all",$news_all);
		$this->assign("news_single",$news_single);
		$this->display();
	}

	public function news(){
		$id=$_REQUEST['id']? intval($_REQUEST['id']):0;
		$news_id=M('message_text')->where('id='.$id)->find();
		if(!$news_id){$this->error("该信件不存在");exit();}
		$new_user=M('message_user')->field('id,flat')->where('userid='.$this->_userid." and message_id=".$news_id['id'])->find();
		if(!$new_user){
			$data['userid']=$this->_userid;
			$data['message_id']=$news_id['id'];
			$data['read_time']=time();
			$data['createtime']=time();
			$data['flat']=1;
			M('message_user')->add($data);
		}else{
			$data['id']=$new_user['id'];
			$data['flat']=1;
			$data['read_time']=time();
			M('message_user')->save($data);
		}
		$news_id['content']=htmlspecialchars($news_id['content']);
		$this->assign('info',$news_id);
		$this->display();
	}
	/*站内信 end*/
	//电子现金
	public function cash_use(){
        import ( "@.ORG.Page2" );
        $model=M('consume');
		$uid=$this->_userid;
		$count=$model->where("user_id=".$uid)->count();
        $listRows = 10; 
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
		$consume=$model->where("user_id=".$uid)->order('id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
		foreach ($consume as $key => $value) {
			//消费去向,1为购买产品，2为缴费，3为押金,4为充值,5为返现
			switch ($value['source']) {
				case '1':
				{ 
					switch ($value['pay_type']) {
						case '1':
						$consume[$key]['source']="现金消费";
							break;
						
						case '2':
						$consume[$key]['source']="电子现金消费";
							break;
						default:
							break;
					}
				$consume[$key]['cash']="&minus;".$value['cash'];
					break;
				}
				
				case '2':
				{ 
					switch ($value['level_flat']) {
						case '1':
						$consume[$key]['source']="系统免费升级";
							break;
						
						default:
						$consume[$key]['source']="缴纳年费";
							break;
					}
				$consume[$key]['cash']="&minus;".$value['cash'];
					break;
				}
				case '3':
				{ 
				$consume[$key]['cash']="&minus;".$value['cash'];
				switch ($value['pay_type']) {
						case '3':
						$consume[$key]['source']="系统赠送微店押金";
							break;
						default:
						$consume[$key]['source']="缴纳微店押金";
							break;
					}
					break;
				}
				case '4':
				{ 
				$consume[$key]['cash']="&#43;".$value['cash'];
				$consume[$key]['source']="充值";
					break;
				}
				case '5':
				{ 
				$consume[$key]['cash']="&#43;".$value['cash'];
				$consume[$key]['source']="消费返现";
					break;
				}
				default:
				case '6':
				{ 
				$consume[$key]['cash']="&minus;".$value['cash'];
				$consume[$key]['source']="缴纳平台管理费";
					break;
				}
				case '7':
				{ 
				$consume[$key]['cash']="&#43;".$value['cash'];
				$consume[$key]['source']="取消订单退还";
					break;
				}
				default:
				{ 
				$consume[$key]['source']="未知";
					break;
				}
			}
		}
        $this->assign('pages',$pages);
		$this->assign("fee_list",$consume);
		$this->display();
	}
	//二维码
	public function code(){
		//header("location:qr/shop_code.php?userid=".$this->_userid);
		$url="http://".$_SERVER['HTTP_HOST']."/qr/shop_code.php?userid=".$this->_userid;
		$str=file_get_contents($url);
		$src="http://".$_SERVER['HTTP_HOST']."/qr/".$str;
		$this->assign("src",$src);
		$this->display();
	}
	//申请成为经营者
	public function beshop(){
		$this->assign("big_title","请在电脑浏览器上输入此链接地址进入申请页面：");
		$this->assign("small_title",U('Home/Beshop/index','id='.$this->_userid,"","",true));
		$this->display("Index:tuichu");
	}
	//我的收藏
	public function my_collect(){
        import ( "@.ORG.Page2" );
        $model=M("pro_collect");
		$count=$model->join(" `qq_product` on `qq_product`.id=`qq_pro_collect`.productid ")->where(" `qq_pro_collect`.userid=".$this->_userid." and `qq_product`.status=1 ")->count();
        $listRows = 5; 
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
		$list=$model->field(" `qq_product`.catid,`qq_product`.title,`qq_product`.thumb,`qq_product`.price,`qq_product`.url,`qq_product`.en_name,`qq_product`.member_price,`qq_pro_collect`.createtime ")->join(" `qq_product` on `qq_product`.id=`qq_pro_collect`.productid ")->where(" `qq_pro_collect`.userid=".$this->_userid." and `qq_product`.status=1 ")->order(' `qq_pro_collect`.createtime desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign('pages',$pages);
        $this->assign('list',$list);
        $this->display();
	}
}
?>