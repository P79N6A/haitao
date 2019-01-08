<?php
/*
  +----------------------------------------------------------------+
  * 微信公众平台--消息响应接口 
  * Wechat($gh_id)
  *
  * 该对象和MP对象其实是一体的，主要是分离出消息响应的部分
  +----------------------------------------------------------------+
 * @初始化所需参数
 *  parameters string $appID		公众号appID
  +----------------------------------------------------------------+
  |（包括发送信息、点击自定义菜单、订阅事件、扫描二维码事件、支付成功事件、用户维权）
  | 这些都可以触发 48小时客服功能
  | 未验证 及时响应后，是否还可以使用客服功能
  +----------------------------------------------------------------+
 */
class Wechat extends Think {
	private $gh_id;
	private $mp;
	function __construct($gh_id){
		//检验本地是否有这个gh_id(token)
		// $gh_id = "gh_9ca1790ccc2a";
		$gh = M('wechat')->field('gh_id,appId,appSecret')->where(array('gh_id'=>$gh_id))->find();
		$gh ? $this->gh_id = $gh['gh_id']:exit('查无公众号');
		F('wechat',$gh);
		//实例化一个 内部对象
		import ( '@.ORG.MP' );
		$this->mp = new MP($gh['appId'],$gh['appSecret']);
	}
	final public function checkSignature(){
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];
        $echoStr = $_GET["echostr"];
		$token = $this->gh_id;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		if( $tmpStr == $signature ){
			//这个是 响应微信绑定token，服务器地址 的请求
			//如果不是绑定，什么也不做，进入下一步
			if($echoStr){
				echo $echoStr;
				exit;
			}
		}else{
			return false;
		}
	}

	/*
	 * 响应关注
	 * 
	 * 1.增加用户到公众号的会员，
	 * 2.根据 公众号 设置，响应信息给微信服务器
	 */
	final public function subscribe($data,$even_data){
		//关注逻辑请写在这里
		//增加用户到公众号的会员;对应follow表
		$user_info = $this->mp->getUserInfo($data['FromUserName']);

		$writeData = array();

		$writeData['gh_id'] = $data['ToUserName'];

		$writeData['openid'] = $data['FromUserName'];

		$writeData['nickname'] = $user_info['nickname'];

		$writeData['gender'] = $user_info['sex'];

		$writeData['avatar'] = $user_info['headimgurl'];

		//没有国家和省份，需要时请加上，记得修改数据库
		//$_POST['province']);$_POST['country'];
		$writeData['city'] = $user_info['city'];

		$writeData['subscribe_time'] = date('Y-m-d H:i:s',$user_info['subscribe_time']);

		//这个保证记录是有更新的，否则，因为使用createUpdate方法，可能提醒出错
		$writeData['createtime'] = date('Y-m-d H:i:s',time());
		
		$writeData['status'] = 1;

		//写入用户表，默认注册
		$us_where['wechat_openid'] = $data['FromUserName'];
		$user = M("user")->field("id,parent_id")->where($us_where)->find();
		$key = $even_data['EventKey'];//前缀qrscene_
		$parentid = explode("_", $key);
		if(!$parentid) $parentid[1] = "0"; 
		$check_group = M("user")->field("groupid")->where("id=".$parentid[1])->find();
		if(empty($check_group) || intval($check_group['groupid']) < 6 || intval($check_group['groupid']) > 13)
		{
			$parentid[1] = "0"; //如果不是微店上级就变为总的
		}

		// if($parentid[1] == 1) $parentid[1] = "0";//如果是1则上级是大后台
		//file_put_contents("test.txt", $parentid[1]);
		if(!empty($user)){
			$con['wechat_name']=$user_info['nickname'];
			$con['wechat_pic']=$user_info['headimgurl'];
			$con['sex']=$user_info['sex'];
			M("user")->data($con)->where("id=".$user['id'])->save();
			//file_put_contents("test2.txt", $con);
		}else{
			$con['wechat_pic'] = $user_info['headimgurl'];
			$con['wechat_openid'] = $data['FromUserName'];
			$con['wechat_name'] = $user_info['nickname'];
			$con['createtime'] = mktime();
			$con['sex'] = $user_info['sex'];
			$con['status'] = 1;
			$con['groupid'] = 3;
			$con['cash_use'] = 100;//送20元
			if($parentid[1]!=null)$con['parent_id']=$parentid[1];
			M("user")->add($con);
			//file_put_contents("test3.txt", $con);
		}
		//这里使用的createUpdate是自己类里面的方法
		//出错或者成功都不提醒，这里很适合非阻断式
		$this->createUpdate('wechat_follow',array('openid',$data['FromUserName']),$writeData);
		/********************写入数据库之后，回复消息给关注着********************/
		//最好能先回复消息给关注者 写入数据库的操作 在后台运行

		$where = array('varname'=>"wechat_scribe",'groupid'=>2,'lang'=>1);

		$content = M('config')->field('value')->where($where)->find();
		$repaly_content['type']="text";
		$repaly_content['description']=$content['value'];
		return $repaly_content;
		
	}
/*
	 * 响应菜单点击
	 * 
	 */
	final public function get_click($data,$xml_data){
		switch ($data['EventKey']) {
			case 'text':
		$where = array('varname'=>"wechat_scribe",'groupid'=>2,'lang'=>1);

		$content = M('config')->field('value')->where($where)->find();
		$repaly_content['type']="text";
		$repaly_content['description']=$content['value'];
				break;
			case 'new_1':
			/*new2即catid为27的至诚推介文章*/
				$repaly_content=$this->get_list(27);
				break;
			case 'new_2':
			/*new2即catid为2的资讯文章*/
				$repaly_content=$this->get_list(2);
				break;
			
			case 'new_3':
			/*new2即catid为3的纪事文章*/
				$repaly_content=$this->get_list(3);
				break;
			case 'new_4':
			/*new2即catid为10的活动文章*/
				$repaly_content=$this->get_list(10);
				break;
			default:
				# code...
				break;
		}

		return $repaly_content;
		
	}
/*获取菜单文章列表*/
	function get_list($catid){
	$repaly_content=array();
	$repaly_content['type']="news";
	$count=M("article")->where("catid=".$catid." and status=1")->count();
	if($count>=10)$repaly_content['ArticleCount']=10;
	else $repaly_content['ArticleCount']=$count ? $count:1;
	//获取所属文章
	$aa=M("article");
	$list=$aa->field("title,description,thumb,url")->where("catid=".$catid." and status=1")->order("listorder asc,id desc")->limit("0,".$repaly_content['ArticleCount'])->select();
	foreach ($list as $key => $value) {
	$repaly_content['Articles'][$key]['Title']=$value['title'];
	$repaly_content['Articles'][$key]['Description']=mb_substr($value['description'],0,10,'utf-8');
	$repaly_content['Articles'][$key]['PicUrl']="http://".$_SERVER['HTTP_HOST']."/".$value['thumb'];
	$repaly_content['Articles'][$key]['Url']="http://".$_SERVER['HTTP_HOST']."/".$value['url'];
	}
	return $repaly_content;
	}
/**/
	/**
	  * api的 更新/插入（出错或成功都不提醒）
	  * @param	$db	要操作的数据表
	  *		$unique	0=>$k
	  *			1=>$v
	  * +-----------------------------------------+
	  * 和 CommController 不同
	  *	这里 需要唯一键openid
	  * +-----------------------------------------+
	  */
	final private function createUpdate($db,$unique=array(),$data){
		$db = M($db);
		
		if(isset($unique[0]) && isset($unique[1])){//存在unique,检验是否有数据
			
			$where = array($unique[0]=>$unique[1]);

			$record = $db->field($unique[0])->where($where)->find();

			if($record){
				$db->data($data)->where($where)->save();
			}else{
				$db->add($data);
			}
		}else{
			$db->add($data);
		}
		return true;
	}

}
