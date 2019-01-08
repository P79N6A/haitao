<?php
/**
 * 
 */
if(!defined("QQCMS")) exit("Access Denied"); 
class QrcodeAction extends Action
{
	//二维码
	public function code(){
		$id=$_REQUEST['id']?intval($_REQUEST['id']):0;
		$shop=M("user")->field("id")->where("id=".$id." and groupid between 6 and 13")->find();
		if(!$shop){
			$this->assign("title","抱歉");
			$this->assign("message","没有该微店");
			$this->display("Index:error");exit();			
		}
		$code=M("qrcode")->where("userid=".$shop['id'])->find();
		$_SESSION["parent_shopid"]=$shop['id'];
		if(empty($code['ticket'])){
		/*当用户不存在二维码时生成二维码*/
		$gh = M('wechat')->field('id,gh_id,appId,appSecret')->where(array('uid'=>1,'status'=>1))->find();
		if(!isset($gh['appId']) || !isset($gh['appSecret'])){
			$this->error('参数出错了，请联系客服');
		}
		$this->gh_local_id = $gh['id'];
		$this->gh_id = $gh['gh_id'];
		$this->appId = $gh['appId'];
		$this->appSecret = $gh['appSecret'];
		$this->assign('gh',$gh);

		import ( '@.ORG.MP' );
		$this->mp = new MP($this->appId,$this->appSecret);
		$scene_id=$shop['id'];
		$data['action_name']="QR_LIMIT_SCENE";
		$data['action_info']['scene']['scene_id']=$scene_id;
		$json_data=json_encode($data);
		$res=$this->mp->create_code($scene_id);//返回生成参数
		$img=$this->get_code_img($res['ticket']);
		$data['userid']=$shop['id'];
		$data['ticket']=$res['ticket'];
		$data['url']=$img;
		$data['createtime']=mktime();
		M("qrcode")->add($data);//插入数据库
		$code=array();
		$code=$data;
		$code['id']=$shop['id'];
		/*获取二维码*/
		}
		if(empty($code['url'])){
			$code['url']=$this->get_code_img($code['ticket']);

			M("qrcode")->save($code);
		}
		$this->assign("code",$code);
		$this->get_acticle();//总店推广说明
		$this->get_shopacticle($id);//店铺推广说明
		$this->display();
	}
	public function get_code_img($ticket){
		$url='https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.UrlEncode($ticket);//根据生成参数获取图片
		$return=$this->download_qrcode($url);//获取到图片
		$path="shop_qrcode/";
    if (!file_exists($path)) mkdir($path,0777);
		$filename="qr_".time().$shop['id'].".jpg";
		$fn=fopen($path.$filename,"w");
		if($fn!=false){
			fwrite($fn, $return['body']);//下载图片到本服务器
		}
		fclose($fn);
		return $path.$filename;
	}
public function download_qrcode($url){
		$curlhandle = curl_init();
		curl_setopt($curlhandle, CURLOPT_URL, $url);
		curl_setopt($curlhandle, CURLOPT_HEADER,0);
		curl_setopt($curlhandle, CURLOPT_NOBODY,0);
		curl_setopt($curlhandle, CURLOPT_SSL_VERIFYPEER, 0); //对认证证书来源的检查
		curl_setopt($curlhandle, CURLOPT_SSL_VERIFYHOST, 0); //从证书中检查SSL加密算法是否存在
		curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, 1); //获取的信息以文件流的形式返回
		$result = curl_exec($curlhandle);
		$info = curl_getinfo($curlhandle);
		curl_close($curlhandle);

		return array_merge(array('body'=>$result),array("header"=>$info));
}
public function get_shopacticle($id=0){
	$shop_info=M("qrcode_article")->where("userid=".$id)->find();
	$this->assign("shop_info",$shop_info);
}

public function get_acticle(){
	$info=M("page")->where("id=28")->find();
	$this->assign("info",$info);
}
}
?>