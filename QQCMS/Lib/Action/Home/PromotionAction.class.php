<?php
/*推荐产品页*/
class PromotionAction extends BaseAction{
      	public function index(){
      		    $shop_id=$_REQUEST['shop_id']?intval($_REQUEST['shop_id']):0;
              $catid=$_REQUEST['catid']?intval($_REQUEST['catid']):0;
              import ( "@.ORG.Page2" );
              $count=M("Promotion")->count();
              $listRows = 10;
              $page = new Page ( $count, $listRows );
              $pages = $page->show();
              $model=M("Promotion");
              $where['catid'] = $catid;
              $where['status'] = 1;
              $list=$model->field("id,catid,title,thumb,url,eventser,logo")->where($where)->order("createtime desc")->limit($page->firstRow.",".$page->listRows)->select();
              /*foreach ($list as $key => $value) {
                $list[$key]=$this->cut_str($value);//截取字符串
              }*/
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
  	       $Promotion=M("Promotion")->where("id=".$id)->find();
           // print_r($info);exit;
           /*获取用户报名信息*/
           $where['uj.promotionid'] = $id;
           $where['uj.status'] = 1;
           $userjoincount = M('Userjoin as uj')->where($where)->count();
           $join = 'qq_user as u ON uj.userid = u.id';
           $field = 'uj.*,u.id as uid,u.username,u.realname,u.wechat_pic';
           $userjoin = M('Userjoin as uj')->field($field)->join($join)->where($where)->select();

           /*判断当前用户是否报名*/
           $locksignup = 0;
          if ($this->_userid)
          {
            $_userjoin = array();
            foreach ($userjoin as $key => $val) {
              $_userjoin[$val[userjoinid]] = $val;
              if ($val[userid]==$this->_userid)
              {
                $locksignup = 1;
              }
            }
          }
          unset($userjoin[$key]);
          // print_r($userjoin);exit;
          $this->assign("userjoincount",$userjoincount);
          $this->assign("userjoin",$_userjoin);
          $this->assign("Promotion",$Promotion);
          $this->assign("locksignup",$locksignup);
          $this->assign("user_id",$this->_userid);  
          $this->assign("shopid",$shop_id); 
          $this->display();
      	}

        /*参加报名活动*/
        public function doEvent(){
            $_POST[createtime] = time();
            $_POST[lang] = 1;
            $_POST[status] = 1;
            
            $_where[id] = $_POST[promotionid];
            $_where[status] = 1;
            $field = 'id,catid,eventser,sttime,edtime,minprice';
            $PromotionInfo = M('Promotion')->field($field)->where($_where)->find();

            /*判断当前活动是否自己发起*/
            if ($PromotionInfo[eventser] == $this->_userid)
              $this->ajaxReturn(NULL,"自己发起的活动不需要报名！",0);

            if (!$this->_username)
            {
              $logurl = U('User/Register/index');
              $this->ajaxReturn($logurl,"请先注册！",'101');
            }

            if (!$this->_userid)
            {
              $logurl = U('User/Login/index');
              $this->ajaxReturn($logurl,"请先登录！",'102');
            }

            //判断是否为金会员或商家
            $roleWhere[role_id] = array('in','6,7,8,9,10,11,12,13');
            $roleWhere[user_id] = $this->_userid;
            $role_user = M('Role_user')->field('role_id')->where($roleWhere)->find();
            //获取权限条件
            if (empty($role_user))
            {
              $time_slot = $PromotionInfo[edtime]-$PromotionInfo[sttime];
              if ($time_slot>0 && $PromotionInfo[minprice]>0)
              {
                // 获取time_slot时间段内该用户的消费金额
                $cWhere[user_id] = $this->_userid;
                $cWhere[create_time] = array(array('gt',$PromotionInfo[sttime]),array('lt',$PromotionInfo[edtime]));
                $consume = M('Consume')->where($cWhere)->sum('cash');
                if ($PromotionInfo[minprice] > $consume)
                {
                  $this->ajaxReturn($consume,"对不起，您不够条件参加此次活动，请继续加油！",0);
                }
              }
            }

            $where[promotionid] = $_POST[promotionid];
            $where[userid] = $this->_userid;
            $_POST[userid] = $this->_userid;
            $data = M('Userjoin')->field('userjoinid')->where($where)->find();
            if (!empty($data))
              $this->ajaxReturn(NULL,"您已经报名了！",0);
            $id = M('Userjoin')->add($_POST);
            if ($id)
            {
                $this->ajaxReturn($id,"恭喜您，您已报名成功！",1);
            }
            $this->ajaxReturn($sql,"报名失败！",0);
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
                   header("location:/index.php?m=Order&a=ischeckout");exit();
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