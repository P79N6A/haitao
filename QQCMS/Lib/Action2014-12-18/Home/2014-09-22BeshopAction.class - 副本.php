<?php
/**
 * 
 * 申请成为经营者表单
 *
 */
class BeshopAction extends Action {
	protected $_shop,$_user;
	 function _initialize(){
			$this->_shop=M("beshop")->field('id')->where('userid='.intval($_GET['id']))->find();
			$this->_user=M('user')->field("id,province,city,area,realname,wechat_name,mobile,address,parent_id")->where('groupid between 3 and 4 and id='.intval($_GET['id']))->find();
			if($this->_shop){
				$this->error("您已经申请过成为经营者，若审核依然未通过请及时联系我们");exit();
			}
			if(!$this->_user){
				$this->error("您还没成为会员或已经成为经营者");exit();
			}
	 }

	public function index(){
		if($_POST['submit']=='submit'){
			if(!$_POST['shop_name']||!$_POST['wechat_name']||!$_POST['password']||!$_POST['real_name']||!$_POST['id_number']||!$_POST['mobile']||!$_POST['address']||!$_POST['account_number']||!$_POST['account_name']||!$_POST['bank_name']){
				$this->error("请填写完整表单信息再提交");exit();
			}
			/*验证身份*/
			$user=M('user')->field('password')->where('id='.intval($_POST['id']))->find();
			if($user['password'] != sysmd5($_POST['password'])){
				$this->error("密码验证错误");exit();
			}
			$this->_shop=M("beshop")->field('id')->where('userid='.intval($_GET['id']))->find();
			if($this->_shop){
				$this->error("您已经申请过成为经营者，若审核依然未通过请及时联系我们");exit();
			}
			/*end*/
			$_POST = $this->stripslashes_array($_POST);//数据过滤
			foreach ($_POST as $key => $value) {
				$_POST[$key]=$this->lib_replace_end_tag($value);
			}
			$img_info=$this->upload();
			$_POST['id_img']=$img_info[0]['savepath'].$img_info[0]['savename'];//身份证复印件地址
			$_POST['userid']=intval($_POST['id']);
			$_POST['createtime']=time();
			unset($_POST['id']);
			$res=M('beshop')->add($_POST);
			if($res)
				$this->error("谢谢，您的资料已提交，请关闭此页面并等待管理员审核。");
			else
			$this->error("提交失败");
		}
		if($_GET['id']){
			$parent=M('user')->field("realname")->where('id='.$this->_user['parent_id'])->find();
			$this->assign('user',$this->_user);
			$this->assign("parent",$parent);
			$this->display();
		}else{
			$this->error("请输入正确的URL链接地址");exit();
		}
	}	
	public function terms(){
		$this->display();
	}
function stripslashes_array(&$array) {
	 while(list($key,$var) = each($array)) {
 		 if ($key != 'argc' && $key != 'argv' && (strtoupper($key) != $key || ''.intval($key) == "$key")) {
  		 if (is_string($var)) {
 	   $array[$key] = stripslashes($var);
  		 }
  	 if (is_array($var))  {
  	  $array[$key] = stripslashes_array($var);
  	 }
 	 }
 }
 return $array; 
}
function lib_replace_end_tag($str)
{
 if (empty($str)) return false;
 $str = htmlspecialchars($str);
 $str = str_replace( '/', "", $str);
 $str = str_replace("\\", "", $str);
 $str = str_replace(">", "", $str);
 $str = str_replace("<", "", $str);
 $str = str_replace("<SCRIPT>", "", $str);
 $str = str_replace("</SCRIPT>", "", $str);
 $str = str_replace("<script>", "", $str);
 $str = str_replace("</script>", "", $str);
 $str=str_replace("select","select",$str);
 $str=str_replace("join","join",$str);
 $str=str_replace("union","union",$str);
 $str=str_replace("where","where",$str);
 $str=str_replace("insert","insert",$str);
 $str=str_replace("delete","delete",$str);
 $str=str_replace("update","update",$str);
 $str=str_replace("like","like",$str);
 $str=str_replace("drop","drop",$str);
 $str=str_replace("create","create",$str);
 $str=str_replace("modify","modify",$str);
 $str=str_replace("rename","rename",$str);
 $str=str_replace("alter","alter",$str);
 $str=str_replace("cas","cast",$str);
 $str=str_replace("&","&",$str);
 $str=str_replace(">",">",$str);
 $str=str_replace("<","<",$str);
 $str=str_replace(" ",chr(32),$str);
 $str=str_replace(" ",chr(9),$str);
 $str=str_replace("    ",chr(9),$str);
 $str=str_replace("&",chr(34),$str);
 $str=str_replace("'",chr(39),$str);
 $str=str_replace("<br />",chr(13),$str);
 $str=str_replace("''","'",$str);
 $str=str_replace("css","'",$str);
 $str=str_replace("CSS","'",$str); 
 return $str;  
}
	public function upload(){
			import ( '@.ORG.UploadFile' );
			$upload=new UploadFile();
   			 $upload->maxSize  = 3145728 ;// 设置附件上传大小
    		$upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
   			 $upload->savePath =  'Public/Beshop_uploads/';// 设置附件上传目录
   			 $upload->saveRule = time().mt_rand(0,99); 
  		  if(!$upload->upload()) {// 上传错误提示错误信息
      	  $this->error($upload->getErrorMsg());
        exit();
   		 }else{// 上传成功
   		 	//取得成功上传的文件信息
			return $upload->getUploadFileInfo();
    	}
	}
}
?>