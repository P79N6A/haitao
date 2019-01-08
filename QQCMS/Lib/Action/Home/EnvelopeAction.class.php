<?php
/**
 * 物流信息接口
 */
if(!defined("QQCMS")) exit("Access Denied"); 
class EnvelopeAction extends BaseAction
{
	protected   $dao ,$sessionid;
	function _initialize()
    {
		parent::_initialize();
		$this->dao = M('Envelope');
        $this->shop_id=$this->_shopid?$this->_shopid:1;
    }

    public function index()
    {
        $order_id = $_GET['id'];
        $order = M('Order')->field('id,iswallet')->find($order_id);
        switch ($order['iswallet']) {
            case 0:
                $this->assign('jumpUrl',U('User/Order/index'));
                $this->assign('waitSecond',2);
                $this->error(L('请先完成订单'));exit;
                break;
            case 1:
                /*设置jssdk*/
                $gh = M('wechat')->field('gh_id,appId,appSecret')->where(array('id'=>'1'))->find();
                $gh ? $this->gh_id = $gh['gh_id']:exit('查无公众号');
                //实例化一个 内部对象
                import ( '@.ORG.MP' );
                $_newMp = new MP($gh['appId'],$gh['appSecret']);

                //获取JS-SDK使用权限签名
                $JsapiTicket = $_newMp->getJsapiTicket();
                $time = time();
                $nonceStr= $this->randCode(16,0);
                $timestamp = $time;
                $_url= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $lock_key = sysmd5($_SERVER['HTTP_HOST']);
                $iswallet = 1;
                $user_data = authcode($iswallet.'├─'.$this->_userid.'├─'.$order_id, 'ENCODE', $lock_key);
                $share_url= $this->site_url.U('Home/Envelope/openWallet',array('user_data'=>trim($user_data)));
                $signature = $this->getSignature($JsapiTicket['ticket'],$time,$nonceStr,$_url);
                $this->assign("gh_id",$gh['gh_id']);
                $this->assign("appId",$gh['appId']);
                $this->assign("nonceStr",$nonceStr);
                $this->assign("timestamp",$timestamp);
                $this->assign("signature",$signature);
                $this->assign("_url",$_url);
                $this->assign("userid",$this->_userid);
                $this->assign("share_url",$share_url);
                $this->assign("site_url",$this->Config['site_url']);
                $this->assign("order_id",$order_id);
                break;
            case 1:
                $this->assign('jumpUrl',U('User/Order/index'));
                $this->assign('waitSecond',2);
                $this->error(L('该订单的红包数量已经发完！'));exit;
                break;
            default:
                $this->assign('jumpUrl',U('User/Order/index'));
                $this->assign('waitSecond',2);
                $this->error(L('订单信息不存在'));exit;
                break;
        }
        $this->display();
    }

    public function grab()
    {
        $lock_key = sysmd5($_SERVER['HTTP_HOST']);
        $user_data = trim($_GET['user_data']);
        list($iswallet,$enveloper,$order_id) = explode('├─', authcode($user_data, 'DECODE', $lock_key));
        $userinfo = M('User')->field('wechat_name,wechat_pic')->find($enveloper);
        $tpwhere['order_id'] = $order_id;
        $tpwhere['enveloper'] = $enveloper;
        $tpwallet = M('Tpwallet')->where($tpwhere)->find();
        $this->assign("iswallet",$iswallet);
        $this->assign("enveloper",$enveloper);
        $this->assign("order_id",$order_id);
        $this->assign("userinfo",$userinfo);
        if (!empty($tpwallet))
            $this->display();
        else
            $this->display('end');
    }

    public function openWallet()
    {
        if (!$this->_userid)
        {
            $this->error('请先登录！');exit;
        }
        $lock_key = sysmd5($_SERVER['HTTP_HOST']);
        $user_data = trim($_GET['user_data']);
        list($iswallet,$enveloper,$order_id) = explode('├─', authcode($user_data, 'DECODE', $lock_key));
        ##获取已领红包用户##
        $allwhere['e.enveloper'] = $enveloper;
        $allwhere['e.order_id'] = $order_id;
        $join = 'qq_user as u ON e.receiver = u.id';
        $field = 'e.*,u.wechat_pic,u.wechat_name';
        $allenvelope = $this->dao->alias('e')->field($field)->join($join)->where($allwhere)->select();
        $this->assign("allenvelope",$allenvelope);

        ##派红包用户##
        $userinfo = M('User')->field('wechat_name,wechat_pic')->find($enveloper);
        $this->assign("userinfo",$userinfo);

        ##当前用户##
        $iuser = M('User')->field('id,cash_use,wechat_pic')->find($this->_userid);

        $where['order_id'] = $order_id;
        $where['enveloper'] = $enveloper;
        $tpwallet = M('Tpwallet')->where($where)->find();
        if (empty($tpwallet))
            $isget = 1;
        else
        {
            $opwhere['enveloper'] = $enveloper;
            $opwhere['receiver'] = $this->_userid;
            $opwhere['order_id'] = $order_id;
            $envelopeinfo = $this->dao->where($opwhere)->find();
            if (empty($envelopeinfo))
            {
                $isget = 2;
                $tp = unserialize($tpwallet['wallet']);
                $randkey = array_rand($tp);
                $onewallet = $tp[$randkey];
                $lope['enveloper'] = $enveloper;
                $lope['receiver'] = $this->_userid;
                $lope['order_id'] = $order_id;
                $lope['drawwallet'] = $onewallet;
                $enve = $this->dao->add($lope);
                if ($enve)
                {
                    ##改变用户电子现金##
                    $iuser['cash_use'] += $onewallet;
                    M('User')->save($iuser);
                    unset($tp[$randkey]);
                    shuffle($tp);
                    if (!empty($tp))
                    {
                        $tp = serialize($tp);
                        $tpdata['tpwallet_id'] = $tpwallet['tpwallet_id'];
                        $tpdata['wallet'] = $tp;
                        M('Tpwallet')->save($tpdata); 
                    }
                    else{
                        M('Tpwallet')->where('tpwallet_id='.$tpwallet['tpwallet_id'])->delete();
                    }
                }
            }
            else
            {
                $isget = 3;
                $onewallet = $envelopeinfo['drawwallet'];
            }

            $this->assign("onewallet",$onewallet);
        }

        $this->assign("iuser",$iuser);
        $this->assign("isget",$isget);
        $this->display();  
    }

    public function makeWallet()
    {
        $order_id = $_POST['order_id'];
        $enveloper = $_POST['enveloper'];
        $where['id'] = $order_id;
        $where['userid'] = $enveloper;
        $order = M('Order')->field('id,userid,amount')->where($where)->find();
        $tptotal = ($order['amount'] * 10)/100;
        $number = 10; // 红包数
        $total = round($tptotal); // 总金额
        $toWallet = $total; // 红包总金额
        $wallet = array(); // 红包列表
        // 算法
        for($i = $number; $i > 0; $i--){
            $x = $i == 1 ? $total : mt_rand(1, $total/$i);
            $total -= $x;
            $wallet[] = $x;
        }
        shuffle($wallet);
        if (!empty($wallet))
        {
            $_where['order_id'] = $order_id;
            $_where['enveloper'] = $enveloper;
            $tpwallet = M('Tpwallet')->field('tpwallet_id')->where($_where)->find();
            if (empty($tpwallet))
            {
                $wallet = serialize($wallet);
                $data['wallet'] = $wallet;
                $data['order_id'] = $order_id;
                $data['enveloper'] = $enveloper;
                $data['totalwallet'] = $toWallet;
                $tp = M('Tpwallet')->add($data);
                if ($tp === false)
                    $this->ajaxReturn(null,'fail',0);
                else{
                    $order_data['id'] = intval($order_id);
                    $order_data['iswallet'] = 2;
                    M('Order')->save($order_data);
                    $this->ajaxReturn($tp,'ok',1);
                }
            }
            else
            {
                $this->ajaxReturn(null,'fail',1);
            }
        }
        else
        {
            $this->ajaxReturn(null,'fail',0);
        }
    }
}