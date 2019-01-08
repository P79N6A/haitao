<?php
/*推荐产品页*/
class TosellAction extends Action{
	public function index(){
		$shop_id=$_REQUEST['shop_id']?intval($_REQUEST['shop_id']):0;
    $catid=$_REQUEST['catid']?intval($_REQUEST['catid']):0;
        import ( "@.ORG.Page2" );
        $count=M("tosell")->count();
        $listRows = 10;
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
        $model=M("tosell");
        $list=$model->alias(" as a")->field("a.id,a.title,b.name,a.logo")->join(" `qq_type` as b on a.type=b.typeid")->where(" a.catid={$catid} ")->order("a.createtime desc")->limit($page->firstRow.",".$page->listRows)->select();
        foreach ($list as $key => $value) {
          $list[$key]=$this->cut_str($value);//截取字符串
        }
        $this->assign("list",$list);
        $this->assign("pages",$pages);
        $this->assign("shopid",$shop_id);
        $this->display();
	}
  protected function cut_str($value){
    $length=mb_strlen($value['title']);
    $value['title']=mb_substr($value['title'],0,12,'utf-8');
    if($length>12)$value['title'].="……";
    return $value;
  }
	public function show(){
	       $id=$_REQUEST['id']?intval($_REQUEST['id']):0;
	       $shop_id=$_REQUEST['shop_id']?intval($_REQUEST['shop_id']):0;
	       $info=M("tosell")->where("id=".$id)->find();
               $this->assign("info",$info);
               $this->assign("shopid",$shop_id); 
               $this->display();
	}
        public function jumpurl(){
                $id=$_REQUEST['id']?intval($_REQUEST['id']):0;
                $shop_id=$_REQUEST['shop_id']?$_REQUEST['shop_id']:0;
                $wechat=$this->getwechat();//获取微信用户信息
                if(!empty($id)&&!empty($shop_id)&&!empty($wechat)){

                /*首先判断是否存在该用户*/
                //判断是否有此用户
                $user_model=M("user");
                $user=$user_model->field("id,groupid")->where("wechat_openid='".$wechat."'")->find();
                $type_model=M("tosell");
                $page_sell=$type_model->where("id=".$id)->find();//查出页面
                  if(empty($user)||$user==null){//如果不存在用户则注册
                //注册
                $con['wechat_openid']=$wechat;
                $con['createtime']=mktime();
                $con['status']=1;
                $con['groupid']=3;
                $con['parent_id']=$shop_id;
                $userid=M("user")->add($con);
                 //注册并绑定完成导向关注页面
          $qqcms_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
           $qqcms_auth = authcode($userid."-".$con['groupid']."-".$con['password'], 'ENCODE', $qqcms_auth_key);
       $wechat_auth = authcode($wechat, 'ENCODE', $qqcms_auth_key);
      
        $_SESSION['wechat_auth']=$wechat_auth;
          $_SESSION['auth']=$qqcms_auth;
      $cookietime =432000;
      cookie('auth',$qqcms_auth,$cookietime);
      cookie('username',$con['username'],$cookietime);
      cookie('groupid',$con['groupid'],$cookietime);
      cookie('userid',$userid,$cookietime);
      //cookie('email',$authInfo['email'],$cookietime);
                   header("location:".$page_sell['other_url']);exit();
                 }else{//已注册用户导向
                    //此为产品页，直接进行购买
                  if($page_sell['product_id']!=null&&$page_sell['product_id']!='0'){
                     $flat=$this->add_cart($user,$page_sell['product_id'],1);
                     if($flat){
                   header("location:/index.php?m=Order&a=checkout");exit();
                     }else{
                    echo "<script>location.reload();</script>";exit();
                     }
                  }
                  /*end*/
                   header("location:".$page_sell['button_url']);exit();
                  }
                }else{
                        exit();
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
    public function put_consume($cash=0,$source=0,$user_id=0,$type=0){
      $data['user_id']=$user_id;
      $data['source']=$source;
      $data['pay_type']=$type;
      $data['cash']=floatval($cash);
      $data['create_time']=mktime();
      M("consume")->add($data);
      return true;
  }
  protected function add_cart($user,$product_id,$num){
    if(empty($user['id'])||empty($user['groupid'])){
      return false;exit();
    }
    $sessionid =md5(session_id());
      $r=M("product")->find($product_id);
      /*根据拍卖跟直购只有金会员有权限*/
        if($r['catid']==6||$r['catid']==7){
          if($user['groupid']<4||$user['groupid']>13){
            $this->error("此商品只有金会员才能购买，赶紧升级呗");exit();
          }
        }
      /**/
      $cart =M('Cart')->where("product_id='{$product_id}' and sessionid='{$sessionid}'")->find();
      if($cart){
        $cart['number']=$cart['number']+$num;
      /*判断是否为0*/
        if(intval($cart['number'])<1){
            $this->error("购买数量不低于1");exit();
        }
      /**/
        //判断商品是否限购，购买数量是否大于限购数量
        if($r['single_buy'] && intval($cart['number'])>intval($r['single_buy'])){
            $this->error("购买数量已超过限购数量");exit();
        }
      /*比对商品库存 by dension start*/
        if(intval($cart['number'])>intval($r['stock'])){
            $this->error("库存不足");exit();
        }
      /*end*/
        $cart['price'] = $cart['product_price']*$cart['number'];
        $rs = M('Cart')->save($cart);
      }else{

      /*判断商品是否已经下架*/
          if(!$r['status']){
            $this->error("此商品已经下架");exit();
          }
        /**/
      /*判断是否为0*/
        if($num<1){
            $this->error("购买数量不低于1");exit();
        }elseif($r['single_buy'] && $num>intval($r['single_buy'])){
        //判断商品是否限购，购买数量是否大于限购数量
            $this->error("购买数量已超过限购数量");exit();
        }
      /*比对商品库存 by dension start*/
        if($num>intval($r['stock'])){
            $this->error("库存不足");exit();
        }
      /*end*/
        $data=array();
        //获取会员组别
        $data['userid']=$user['id'];
        $data['sessionid']=$sessionid;
        $data['product_id']=$r['id'];
        $data['product_thumb']=$r['thumb'];
        $data['product_url']=$r['url'];
        $data['product_name']=$r['title'];
        $data['menber_rebate']=$r['menber_rebate'];
        $data['parent_rebate']=$r['parent_rebate'];
        /*商品价格根据商品类型跟会员类别判定 by dension*/
        switch ($r['catid']) {
          case '6':
            $data['direct_shipping'] =$r['direct_shipping'];//此处不跳出继续执行下面
          case '5':
            {
              switch ($user['groupid']) {
                case 3:
                $data['product_price'] =$r['member_price'];
                $data['ratio'] =$r['menber_ratio'];
                  break;
                case 4:
                case 6:
                case 7:
                case 8:
                case 9:
                case 10:
                case 11:
                case 12:
                case 13:
                $data['product_price'] =$r['gold_price'];
                $data['ratio'] =$r['gold_ratio'];
                  break;
                default:
                $data['product_price'] =$r['price'];
                  break;
              }
            }break;
          case '4':
            {
              switch ($user['groupid']) {
                case 4:
                $data['product_price'] =$r['gold_price'];
                $data['ratio'] =$r['gold_ratio'];
                  break;
                default:
                $data['product_price'] =$r['price'];
                  break;
              }
            }break;
          case '25':
            $data['product_price'] =$r['member_price'];
            $data['is_private'] =1;
            break;
          default:
            $data['product_price'] =$r['price'];
            break;
        }
        /*end*/
        $data['moduleid']=3;  
        $data['number']=$num;
        $data['price']= $data['product_price']*$data['number'];
        $rs = M('Cart')->add($data);
      }
      return true;
  }
}
?>