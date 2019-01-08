<?php 
/*
*ActivityAction.class.php(抽奖表单)
*
*/
if(!defined("QQCMS")) exit("Access Denied"); 
class ActivityAction extends Action
{
	public function index(){
		//判断是否是会员
		$wechat=$this->getwechat();
        $user_model=M("user");
        if($wechat==null||$wechat==""){
        	$wechat=0;
        }
        $typeid=isset($_GET['type']) ? intval($_GET['type']):0;
        $this->assign("type",$typeid);
        $user=$user_model->field("id,groupid,realname,mobile,address,province,city,area")->where("wechat_openid='".$wechat."'")->find();
        if(!empty($user)){
        	//获取收货地址
        	$address_model=M("user_address");
        	$address=$address_model->where("userid=".$user['id'])->order(" isdefault desc")->find();
        	if(empty($address)){
        		$address['consignee']=$user['realname'];
        		$address['province']=$user['province'];
        		$address['city']=$user['city'];
        		$address['area']=$user['area'];
        		$address['mobile']=$user['mobile'];
        		$address['address']=$user['address'];
        	}
        	$this->assign("u_address",$address);
        	$this->assign("user",$user);
        }
		$this->display();
	}
	public function post_msg(){
		foreach ($_POST as $key => $value) {
			$_POST[$key]=$this->post_check($value);//防注入
		}
        $typeid=$_POST['typeid'] ? intval($_POST['typeid']):0;
        //检查是否有这活动
        $type=M("type")->field("typeid")->where("typeid=".$typeid." and keyid=38")->find();//38为活动分类顶级id
        if(empty($type)){
            //没有此活动，不能报名
            $res['status']=0;
            $res['msg']="您要报名的活动不存在或者已过期";
            echo json_encode($res);exit();
        }
		$data['name']=$_POST['username'] ? $_POST['username']:"";
		$data['mobile']=$_POST['mobile'] ? $_POST['mobile']:0;
		$data['province']=$_POST['province'] ? $_POST['province']:0;
		$data['city']=$_POST['city'] ? $_POST['city']:0;
		$data['area']=$_POST['area'] ? $_POST['area']:0;
		$data['address']=$_POST['address'] ? $_POST['address']:"";
		$data['user_id']=$_POST['uu'] ? intval($_POST['uu']):0;
		$data['createtime']=mktime();
        $data['typeid']=$typeid;
		$activity=M("activity")->field("id")->where("mobile='".$data['mobile']."' and typeid=".$typeid)->find();
		if(!empty($activity)){//检查
			$res['status']=0;
			$res['msg']="您已经提交过信息";
			echo json_encode($res);exit();
		}
		//入库
		$result=M("activity")->add($data);
		if($result){
		$res['status']=1;
		echo json_encode($res);exit();
		}else{
			$res['status']=0;
			$res['msg']="提交失败";
			echo json_encode($res);exit();
		}
	}       

 protected function getwechat(){
         $gh = M('wechat')->field('gh_id,appId,appSecret')->where(array('id'=>'2'))->find();
         $gh ? $this->gh_id = $gh['gh_id']:exit('查无公众号');
                //实例化一个 内部对象
         import ( '@.ORG.MP' );
         $this->mp = new MP($gh['appId'],$gh['appSecret']);
         $res=$this->mp->mpAuth('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'],'snsapi_base');
                /**/
         return $res;
        } 
	protected function post_check($post)     
    {     
    if (!get_magic_quotes_gpc()) // 判断magic_quotes_gpc是否为打开，此方法会自动对表单数据进行addskashee() 
    {     
    $post = addslashes($post); // 进行magic_quotes_gpc没有打开的情况对提交数据的过滤     
    }     
    $post = str_replace("_", "\_", $post); // 把 '_'过滤掉     
    $post = str_replace("%", "\%", $post); // 把' % '过滤掉     
    $post = nl2br($post); // 回车转换     
    $post= htmlspecialchars($post); // html标记转换        
    return $post;     
    }  
}
?>