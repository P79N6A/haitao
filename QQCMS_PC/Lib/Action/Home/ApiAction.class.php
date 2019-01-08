<?php
/**
 * 微信公众平台接口
 */
if(!defined("QQCMS")) exit("Access Denied"); 
class ApiAction extends Action
{

	protected $gh;//测试号 gh_33e69379e55a
	protected $wechat;
	public $xml_data = array();
	public $reply_template;
	function __construct(){
		parent::__construct();
		header('Content-Type:text/html; charset=utf-8');
		$this->gh = trim($_GET['token']);
		if (empty($this->gh)) $this->gh = 'gh_c223bbcb1e99';//$this->gh = 'outletTest';
		import ( '@.ORG.Wechat' );
		$this->wechat = new Wechat($this->gh);
		$this->wechat->checkSignature();
	}
	public function index(){
		$this->replay_template = include_once(APP_PATH.'/Common/replay_template.php');

		$xml = file_get_contents("php://input"); 

		$this->xml_data = $this->parseXML($xml);
		//调试的时候不要赋值，直接  $this->map($this->xml_data);
		//$return = $this->map($this->xml_data);
		$return = $this->map($this->xml_data);
		
		//按固定格式排列数组，供assembleXML使用
		$reply_arr['type'] = $return['type'];
		$reply_arr['to'] = $this->xml_data['FromUserName'];
		$reply_arr['from'] = $this->xml_data['ToUserName'];
		$reply_arr['when'] = time();
		$reply_arr['content'] = $return['description']?$return['description']:'';
		$reply_arr['Articles'] = $return['Articles']?$return['Articles']:array();
		$reply_arr['ArticleCount'] = $return['ArticleCount']?$return['ArticleCount']:0;
		$reply = $this->assembleXML($reply_arr);
		
		echo $reply;die();
		//dump($reply);die();
		//echo  json_encode($return);
	}

	/*
	 * 这里的map是映射的意思，不是地图
	 * 未来这个应该是最庞大的部分
	 * 当然 它也可以 放置到HOME模块，
	 */
	private function map($data){
		//如果不使用switch,也可以用变量做为函数名，这样可读性会差一些
		// F('datasubscribe',var_export($data,true));
		switch ($data['MsgType'])
		{
		case 'event':
			//嵌套 switch 
			switch ($data['Event'])
			{
			case 'subscribe':
				//分发给 Wechat 对象处理，
				$return = $this->wechat->subscribe($this->xml_data,$data);
				return $return;
				break;
			case 'SCAN':
				//分发给 Wechat 对象处理，
				$return = $this->wechat->scan($this->xml_data,$data);
				return $return;
				break;
			case 'CLICK':
				$return=$this->wechat->get_click($data,$this->xml_data);
				return $return;
				break;
			default:
				//默认什么也不做
				;
			}
			break;
			//接入多客服
		case 'text':
		case 'image':
		case 'voice':
			$result['type'] = 'CustomerMsg';
			return $result;
			break;
		default:
			;

		}
	}

	/*
	 * 解析xml
	 * 独立一个方法出来，如果以后发现这解析太简单，有出错，直接在此修改
	 * xml_str => xml_arr
	 */
	private function parseXML($xmlStr){
		//$xmlStr = preg_replace('/<!\[CDATA\[(.*?)\]\]>/',"$1",$xmlStr);
		//return (array)simplexml_load_string($xmlStr);	//得到对象,强转array

		$xmlStr = (array)simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA);
		return $xmlStr;
	}

	/*
	 * 组装xml
	 * 本质是要 发送被动响应信息，和 客服消息
	 * 形式包括：文本消息，图片消息，语音消息，视频消息，音乐消息，图文消息；最常用的预计是图文消息
	 * arr[0] => MsgType
	 * arr[1] => ToUserName
	 * arr[2] => FromUserName
	 * arr[3] => CreateTime
	 * arr[4] => Content
	 *
	 * arr	=> xml_str
	 */
	private function assembleXML($arr){
		$xmlStr = $this->replay_template[$arr['type']];
		
		//这个需要严格按照xmlStr的格式和顺序，以下为文本信息
		switch ($arr['type']) {
			case 'text':
			return sprintf($xmlStr, $arr['to'], $arr['from'], $arr['when'], html_entity_decode($arr['content']));
				break;
			case 'news':
			//组装图文信息
			 $str="";
			 $str.=sprintf($xmlStr['header'], $arr['to'], $arr['from'], $arr['when'], $arr['ArticleCount']);
			 //$str.=$xmlStr['header'];//拼接头
			 foreach ($arr['Articles'] as $key => $value) {
			 	$str.=sprintf($xmlStr['body'], $value['Title'], $value['Description'], $value['PicUrl'], $value['Url']);
				 //$str.=$temp;//拼接中部数据
			 }
			 $str.=$xmlStr['bottom'];//拼接尾部
			 return $str;
				break;
			case 'CustomerMsg':
			return sprintf($xmlStr, $arr['to'], $arr['from'], $arr['when']);
				break;
			default:
				# code...
				break;
		}
	}
}
?>