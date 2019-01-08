<?php
/**
 * 
 * IndexAction.class.php (前台首页)
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

    public function _initialize() {
    	parent::_initialize();			
    	
    }
    public function index()
    {	$this->check_shop();//检查是否有商家id
		##获取推荐专题##
		$count = M('slide_data')->where('fid=2')->count();
		$listRows =  6;
		import ( "@.ORG.Page" );
		$page = new Page ( $count, $listRows );
		$pages = $page->show();
		$topics = M('slide_data')->where('fid=2')->order('listorder asc,id desc')->limit($page->firstRow . ',' . $page->listRows)->select();

		$this->assign("topics",$topics);
		$this->assign("count",$count);
		##END##
        $this->display();
    }

    public function getitem()
 	{
		define("DEFAULT_PAGE", 1);
		define("DEFAULT_PAGESIZE", 6);
		$nowpage = DEFAULT_PAGE;
		$pagesize = DEFAULT_PAGESIZE;

		if (empty($_REQUEST['page']) || !isset($_REQUEST['page']) || $_REQUEST['page']=='' || $_REQUEST['page']==null) {
			$nowpage = DEFAULT_PAGE;
		} else {
			$nowpage = $_REQUEST['page'];
		}

		##获取推荐专题##
		$limit = (($nowpage-1)*$pagesize).','.$pagesize;
		$count = M('slide_data')->where('fid=2')->count();
		// import ( "@.ORG.Page" );
		// $listRows =  $pagesize;
		// $page = new Page ( $count, $listRows );
		
		// $pages = $page->show();
		$topics = M('slide_data')->where('fid=2')->order('id desc')->limit($limit)->select();
		/*foreach ($topics as $key => $value) {
			$topics[$key]['thumb_pic'] = thumb($value['pic'],365,175,1);
		}*/
		$special['list'] = $topics;
		if (empty($topics))
		{
			$special['list'] = array();
		}
		sleep(1.5);
		$special = json_encode($special);
		echo $special;
		##END##
 	}

 	//获取微店栏目
 	public function get_column(){
 		$shop_id=$this->_shopid?$this->_shopid:1;
		$column_type=M('shopcolumn_type')->select();
 		$column=M('slide_data')->field(' `qq_shopcolumn`.*,`qq_slide_data`.pic,`qq_slide_data`.link ')->join(' `qq_shopcolumn` on `qq_shopcolumn`.slid_data_id=`qq_slide_data`.id ')->where(" `qq_shopcolumn`.uid=".$shop_id)->select();
 		foreach ($column_type as $key => $value) {
 			$column_type[$key]['pic']="";
 			$column_type[$key]['url']="";
 			foreach ($column as $k => $v) {
 				if($value['id']==$v['columntype_id']){
 					$column_type[$key]['pic']=$v['pic']?$v['pic']:"";
 					$column_type[$key]['url']=$v['link']?$v['link']:"";
 				}
 			}

 		}
		//$this->assign("column",$column_type);
		return $column_type;
 	}
 	//获取官方默认7个栏目
 	public function get_default_column(){
 		$shop_id=1;
		$column_type=M('shopcolumn_type')->select();
 		$column=M('slide_data')->field(' `qq_shopcolumn`.*,`qq_slide_data`.pic,`qq_slide_data`.link ')->join(' `qq_shopcolumn` on `qq_shopcolumn`.slid_data_id=`qq_slide_data`.id ')->where(" `qq_shopcolumn`.uid=".$shop_id)->select();
 		foreach ($column_type as $key => $value) {
 			$column_type[$key]['pic']="";
 			$column_type[$key]['url']="";
 			foreach ($column as $k => $v) {
 				if($value['id']==$v['columntype_id']){
 					$column_type[$key]['pic']=$v['pic']?$v['pic']:"";
 					$column_type[$key]['url']=$v['link']?$v['link']:"";
 				}
 			}

 		}
		//$this->assign("default_column",$column_type);
		return $column_type;
 	}
 	//获取官方栏目
 	public function get_offic_column(){
		$offic_column=M('slide_data')->where('fid > 4 and fid <11')->order('fid asc')->select();
		$this->assign("offic_column",$offic_column);
		return true;
 	}
	
	//获取首页商品广告位
	public function getproductad($fid)
	{
		$productad=M('slide_data')->field('id,title,pic,small,pic,link')->where("fid=".$fid)->select();
		return $productad;
	}
	
 	/*统计秒拍结束时间*/
 	public function get_second_time(){
 		$time=M('product')->field("second_end")->where("catid=9 and status=1")->order('second_end desc')->find();//获取秒怕商品中结束时间最后的商品时间
 		if(intval($time['second_end'])>time()){
 			$end_time=$time['second_end']-time();
 		}
 		$this->assign("end_time",$end_time);
 		return true;
 	}

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
}
?>