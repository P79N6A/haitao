<?php
/**
 * 
 * ProductAction.class.php (商品)
 *
 * @package         QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright       Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version         QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if(!defined("QQCMS")) exit("Access Denied"); 
class ProductAction extends BaseAction
{
    public  $gh_id;
   public function _initialize() {
        parent::_initialize();
        import ( "@.ORG.Pages2" );
        $this->get_goods_type();//获取商品类别列表
   }

/*商品详情*/
    public function _before_show(){
        
    }

/*秒拍*/
    public function second_beats(){
    $this->check_shop();//检查是否有商家id
        $product=M('Product_oversea');
        $where['catid']=9;//默认直购商品catid为6
        /*修改状态，把超时的状态变为0*/
        $data1['status']=0;
        $now_time=time()+2;//由于容易有1、2秒的误差导致转换状态不成功，所以加多2秒
        $rr=$product->where(" id>0 and catid=".$where['catid'])->save($data1);
        /**/
        $where['status']=1;
        $today_small=strtotime(date("Y-m-d",time()));//今日凌晨
        $tomorrow_small=strtotime(date("Y-m-d",time()+86400));//明日凌晨
        $after_to_small=strtotime(date("Y-m-d",time()+172800));//后日凌晨
        $this->assign('today_small',$today_small);
        $this->assign('tomorrow_small',$tomorrow_small);
        $this->assign('after_to_small',$after_to_small);
        /*取今天商品列表*/
        $today_list=$product->field("id,title,thumb,status,price,url,stock,en_name")->where($where)->order('createtime')->select();
        /*取明天商品列表*/
       // $tomorrow_list=$product->field("id,title,thumb,status,price,url,stock,second_price,en_name,second_star,second_end")->where("second_star>".$tomorrow_small." and second_end<".$after_to_small." and catid=".$where['catid'])->select();
        $today_temp=array();
        //$tomorrow_temp=array();
        /*今日分组*/
         $today_temp=$this->second_team($today_list);
        /*明日分组*/
       // $tomorrow_temp=$this->second_team($tomorrow_list);
        $this->assign("today_list",$today_temp);
       // $this->assign("tomorrow_temp",$tomorrow_temp);
        $this->display();
    }

/*拍卖*/
    public function auction(){
        $this->check_shop();//检查是否有商家id
        $product=M('Product_oversea');
        $where['catid']=7;//默认直购商品catid为6
        /*修改状态，把超时的状态变为0*/
        $data1['status']=0;
        $now_time=time()+2;//由于容易有1、2秒的误差导致转换状态不成功，所以加多2秒
        $rr=$product->where(" id>0 and catid=".$where['catid'])->save($data1);
        /**/
        $where['status']=1;
        $today_small=strtotime(date("Y-m-d",time()));//今日凌晨
        $tomorrow_small=strtotime(date("Y-m-d",time()+86400));//明日凌晨
        $after_to_small=strtotime(date("Y-m-d",time()+172800));//后日凌晨
        $this->assign('today_small',$today_small);
        $this->assign('tomorrow_small',$tomorrow_small);
        $this->assign('after_to_small',$after_to_small);
        /*取今天商品列表*/
        $today_list=$product->field("id,title,thumb,status,price,url,stock,content,en_name")->where($where)->order('createtime')->select();

        /*取明天商品列表*/
        //$tomorrow_list=$product->field("id,title,thumb,status,price,url,stock,start_price,en_name,second_star,second_end")->where("second_star>".$tomorrow_small." and second_end<".$after_to_small." and catid=".$where['catid'])->select();
        $today_temp=array();
        //$tomorrow_temp=array();
        /*今日分组*/
         $today_temp=$this->second_team($today_list);
        /*明日分组*/
       // $tomorrow_temp=$this->second_team($tomorrow_list);
        $this->assign("today_list",$today_temp);
       // $this->assign("tomorrow_temp",$tomorrow_temp);
        $this->display();
    }

/*直购*/
    public function direct_sell(){
    $this->check_shop();//检查是否有商家id
        $direct=M('Product_oversea');
        $where['catid']=6;//默认直购商品catid为6
        $where['status']=1;
        $order="listorder desc,id desc";
        $list=$this->pro_list($where,$order);
        $order=$this->ord_list();//调用排序
        $list=$this->pro_list($where,$order);
        $this->assign("list",$list);

        $this->display();
    }

/*商家栏目商品*/
    public function shop_goods(){
        $this->check_shop();//检查是否有商家id
        $slide_data_id=$_REQUEST['ad_column'] ? $_REQUEST['ad_column']:0;
        // print_r($slid_data);exit;

        /*查出栏目id*/
        $slid_data=M('Pcslide_data')->where(' id='.intval($slide_data_id))->find();
        $this->assign('column',$slid_data);
        $ShopcolumnpcData=M('ShopcolumnpcData');
        $_where['sp.slide_data_id']=intval($slide_data_id);
        $_where['p.status']=1;
        $count=$ShopcolumnpcData->alias('sp')->field("p.id,p.catid,p.title,p.thumb,p.url,p.en_name,p.price,p.member_price,p.description")->join('qq_product_oversea as p ON sp.goods_id=p.id ')->where($_where)->count();

        $listRows =  8; 
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
        $list=$ShopcolumnpcData->alias('sp')->field("p.id,p.catid,p.title,p.thumb,p.url,p.en_name,p.price,p.member_price,p.description")->join('qq_product_oversea as p ON sp.goods_id=p.id ')->where($_where)->order('p.listorder asc,p.id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
         // print_r($list);exit;
        $this->assign("list",$list);
        $this->assign('pages',$pages);
        $this->display();
    }

/*私人酒窖*/
    public function self_goods(){
    $this->check_shop();//检查是否有商家id
        $where['catid']=25;//默认私人酒窖商品catid为25
        $where['status']=1;
        $where['userid']=$this->_shopid;
        $where['private_status']=1;
        $order="listorder desc,id desc";
        $list=$this->pro_list($where,$order);
        $this->assign("list",$list);
        $this->display();
    }

/*团购*/
    public function group_buy(){
    $this->check_shop();//检查是否有商家id
        $where['catid']=26;//默认团购商品catid为26
        $where['status']=1;
        $order="listorder desc,id desc";
        $list=$this->pro_list($where,$order);
        $this->assign("list",$list);
        $this->assign("page_title","团购");
        $this->display();
    }  

/*奢侈品*/
    public function luxury_goods(){
    $this->check_shop();//检查是否有商家id
        $where['catid']=36;//默认商城商品catid为5
        $where['status']=1;
        $where['posid']=4;//奢侈品默认posid为4
        $order="listorder desc,id desc";
        $list=$this->pro_list($where,$order);
        $order=$this->ord_list();//调用排序
        $list=$this->pro_list($where,$order);
        $this->assign("list",$list);
        $this->display();
    }   

/*类别商品*/
    public function type_goods(){
        $this->check_shop();//检查是否有商家id
        /*$where['catid']=36;//默认商城商品catid为5
        $where['status']=1;*/
        $typeid = $_REQUEST['typeid']? $_REQUEST['typeid']:0;
        $where = "catid = 36 AND status = 1 AND (typeid LIKE '$typeid,%' OR typeid LIKE '%,$typeid' OR typeid LIKE '%,$typeid,%' OR typeid = $typeid)";
        $next_list=$this->get_next_type($_REQUEST['typeid']);
        $this->assign("next_list",$next_list);
        $this->assign("typeid",$_REQUEST['typeid']);
        $order=$this->ord_list();//调用排序
        $list=$this->pro_list($where,$order);
        $this->assign("moduleid",$this->moduleid);
        $this->assign("list",$list);
        $this->display();
    }  

/*砍价商品*/
    public function bargaining(){
        $this->check_shop();//检查是否有商家id
        $where['catid']=36;//默认商城商品catid为5
        $where['status']=1;
        $where['bargaining']=1;

        $this->assign("userid",$this->_userid);
        $list=$this->pro_list($where,$order);
        //$list = F('gggggggggg');
        $this->assign("list",$list);
        $this->display();
    }  

/*砍价商品详细*/
    public function bargg_show(){
        $this->check_shop();//检查是否有商家id
        // 获取会员信息
        $userinfo=M('user')->field("groupid")->where('id='.$this->_userid)->find();
        $_where['bg.fromuser'] = $_REQUEST[beg_uid];
        $_where['bg.prid'] = $_REQUEST[prid];
        $field = "bg.*,p.title,p.thumb,p.url,p.address,p.price,p.member_price,p.gold_price,p.bargain_num,p.lowestbgg";
        $product = M("Bargain as bg")->field($field)->join('qq_product as p ON bg.prid=p.id')->where($_where)->find();
        // print_r($product);exit;
        $bargain_product = $product;
        if (empty($product))
        {
            $where['catid']=36;//默认商城商品catid为5
            $where['status']=1;
            $where['bargaining']=1;
            $where['id'] = $_REQUEST[prid];
            $field = "id,catid,title,thumb,url,en_name,price,member_price,gold_price,address,bargain_num,lowestbgg";

            //获取商品信息
            $product=M("Product_oversea")->field($field)->where($where)->find();
            if($userinfo['groupid']<4 || $userinfo['groupid']>13)
            $product[bargain_price] = $product[member_price];
            else $product[bargain_price] = $product[gold_price];
        }
        else
        {
            //获取评论列表
            $where['b.fromuser'] = $product['fromuser'];
            $where['b.prid'] = $product['prid'];
            $where['b.bargainid'] = $product['bargainid'];
            $field = "b.*,u.id as userid,u.username,u.wechat_pic";
            $bargain_discuss = M('Bargain_discuss as b')->field($field)->join(' qq_user as u ON b.touser = u.id')->where($where)->select();
            $discuss_count = M('Bargain_discuss as b')->where($where)->count();
            $this->assign("discuss_count",$discuss_count);
            $this->assign("bargain_discuss",$bargain_discuss);
            $where['b.touser'] = $this->_userid;
            $touser_discuss_count = M('Bargain_discuss as b')->where($where)->count();
        }
// file_put_contents('product.txt',var_export($product));
        //砍价后价格
        $af_bargain_price = $product[bargain_price]-$product[bargain_num];
        $lock_member_price = 0;
        if ($product[bargain_price]==$product[lowestbgg])
            $lock_member_price = 1;

         /*设置jssdk*/
        if ($this->_userid==$_GET[beg_uid])
        {
            $lock_product = 0;
            $bagWhere['userid'] = $this->_userid;
            $bagWhere['product_id'] = $_REQUEST[prid];
            $bagWhere['bargain'] = 1;
            $bagcart = M('cart')->field('id')->where($bagWhere)->find();
            if (!empty($bagcart))
            {
                $lock_product = 1;
            }
            else
            {
                $gh = M('wechat')->field('gh_id,appId,appSecret')->where(array('id'=>'2'))->find();
                $gh ? $this->gh_id = $gh['gh_id']:exit('查无公众号');
                //实例化一个 内部对象
                import ( '@.ORG.MP' );
                $_newMp = new MP($gh['appId'],$gh['appSecret']);

                //获取JS-SDK使用权限签名
                $JsapiTicket = $_newMp->getJsapiTicket();

                $time = time();

                $nonceStr= $this->randCode(16,0);
                $timestamp=$time;
                $_url='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                $signature = $this->getSignature($JsapiTicket[ticket],$time,$nonceStr,$_url);

                $this->assign("appId",$gh['appId']);
                $this->assign("nonceStr",$nonceStr);
                $this->assign("timestamp",$timestamp);
                $this->assign("signature",$signature);
                $this->assign("_url",$_url);
                $this->assign("site_url",$this->Config['site_url']);
            }
        }

        //判断当前砍价商品进度以及具体砍价信息
        if ($this->_userid == $_GET[beg_uid]){
            //到达目标需要人数
            $bargain_difference = ceil(($product[bargain_price]-$product[lowestbgg])/$product[bargain_num]);
            $this->assign("bargain_difference",$bargain_difference);
            // 当前用户为请求砍价用户，已请求状态
            $beg_uid_tag = 3;
            //当前用户为请求砍价用户,并且未请求状态
            if (empty($bargain_product))
            {
                $beg_uid_tag = 1;
            }
        }else{
            //当前用户为接收用户
            $beg_uid_tag = 2;
            //当前用户为接收用户,并且已砍价
            if ($touser_discuss_count>0)
                $beg_uid_tag = 4;
        }

        $this->assign("lock_product",$lock_product);
        $this->assign("lock_member_price",$lock_member_price);
        $this->assign("prid",$_REQUEST[prid]);
        $this->assign("_userid",$this->_userid);
        $this->assign("fromuser",$_GET[beg_uid]);
        $this->assign("beg_uid_tag",$beg_uid_tag);
        $this->assign("af_bargain_price",$af_bargain_price);
        $this->assign("product",$product);
        $this->display();
    }


    // 生成临时订单，生成砍价商品推送信息
    public function tobargg()
    {
        $prid = $_POST['prid'];
        $where['fromuser'] = $this->_userid;
        $where['prid'] = $prid;
        $_POST['fromuser'] = $this->_userid;
        $data = M('Bargain')->field('bargainid')->where($where)->find();
        if (!empty($data))
            $this->ajaxReturn(NULL,"您已经推送过此商品！",0);
        $id = M('Bargain')->add($_POST);
        //$sql = M('Bargain')->getLastsql();
        if ($id)
        {
            $this->ajaxReturn($id,"恭喜您，您已分享成功！",1);
        }
        $this->ajaxReturn($sql,"分享成功！",0);
    }

    //添加保存评论信息
    public function createBargainDiscuss()
    {
        $_POST['touser'] = $this->_userid;
        $where['fromuser'] = $_POST['fromuser'];
        if ($this->_userid == $_POST['fromuser'])
            $this->ajaxReturn(NULL,"不能对自己推荐的商品砍价！",0);
        $where['touser'] = $this->_userid;
        $where['prid'] = $_POST['prid'];
        $where['bargainid'] = $_POST['bargainid'];
        $data = M('Bargain_discuss')->field('id')->where($where)->find();
        if (!empty($data))
            $this->ajaxReturn(NULL,"您已经对此商品砍过价了，去帮办其他朋友吧！",0);
        $_where['fromuser'] = $_POST['fromuser'];
        $_where['prid'] = $_POST['prid'];
        $_where['bargainid'] = $_POST['bargainid'];
        $_where_['b.bargainid'] = $_POST['bargainid'];
        $bargainInfo = M('Bargain as b')->field('b.bargainid,b.bargain_price,p.bargain_num,p.lowestbgg')->join('qq_product as p ON b.prid = p.id')->where($_where_)->find();
        if (!empty($bargainInfo))
        {
            $price_num = $bargainInfo['bargain_price']-$bargainInfo['lowestbgg'];

            if ($price_num>$bargainInfo[bargain_num])
            {
                $minus = $bargainInfo[bargain_num];
                $id = M('Bargain')->where($_where)->setDec('bargain_price',$minus);
            }
            elseif ($price_num>0)
            {
                $minus = $price_num;
                $id = M('Bargain')->where($_where)->setDec('bargain_price',$minus);
            }
            else{
                $this->ajaxReturn(NULL,"目标价位低于商品砍后价格",0);
            }
        }
        
        if ($id)
        {
            M('Bargain_discuss')->add($_POST);
            $this->ajaxReturn($id,"您已成功帮我砍了".$minus."元，太感谢了！",1);
        }

        $this->ajaxReturn($id,"砍价失败，请刷新再试试！",0);
    }

    /*搜索商品-至搜索商城商品*/
    public function search_goods(){
    $this->check_shop();//检查是否有商家id
        $keyword=$_REQUEST['key_word']?$_REQUEST['key_word']:"";
        $where['catid']=36;//默认商城商品catid为5
        $where['status']=1;
        $where['title']=array("like","%".$keyword."%");
        $order="listorder desc,id desc";
        $list=$this->pro_list($where,$order);

        $this->assign("list",$list);
        $this->assign("page_title","搜索列表");
        $this->display("Product:group_buy");

    }
    
/**/
/*排序*/
    public function ord_list(){
        /*价格排序*/
        if($_REQUEST['order_price']==1){
            $order=" price desc ";
            $od['order_price']=2;
        }elseif($_REQUEST['order_price']==2){
            $order=" price asc ";
            $od['order_price']=1;
        }else{
            $od['order_price']=1;
        }
        /*销量排序*/
        if($_REQUEST['order_sell_num']==1){
            $order=" sell_num desc ";
            $od['order_sell_num']=2;
        }elseif($_REQUEST['order_sell_num']==2){
            $order=" sell_num asc ";
            $od['order_sell_num']=1;
        }else{
            $od['order_sell_num']=1;
        }
        /*人气排序*/
        if($_REQUEST['order_hits']==1){
            $order=" hits desc ";
            $od['order_hits']=2;
        }elseif($_REQUEST['order_hits']==2){
            $order=" hits asc ";
            $od['order_hits']=1;
        }else{
            $od['order_hits']=1;
        }
        /*时间排序*/
        if($_REQUEST['order_time']==1){
            $order=" createtime desc ";
            $od['order_time']=2;
        }elseif($_REQUEST['order_time']==2){
            $order=" createtime asc ";
            $od['order_time']=1;
        }else{
            $od['order_time']=1;
        }
        $this->assign("order_list",$od);
        return $order=$order? $order:"";

    }
    /*秒拍跟拍卖-分组*/
    public function second_team($today_list=array()){
        $today_temp=array();
        foreach ($today_list as $key => $value) {
            if(!$today_temp){
                $today_temp[date("m-d H:i",$value['second_star'])][]=$value;
                $today_temp[date("m-d H:i",$value['second_star'])]["end_time"]['time']=$value['second_end'];//取得小组结束时间
                $today_temp[date("m-d H:i",$value['second_star'])]["end_time"]['id']=0;
                $today_temp[date("m-d H:i",$value['second_star'])]["end_time"]['status']=0;
                /*距离结束时间*/
                $today_temp[date("m-d H:i",$value['second_star'])]["end_time"]['time_out']=intval($today_temp[date("m-d H:i",$value['second_star'])]["end_time"]['time'])-time();
                /***************/
                $today_temp[date("m-d H:i",$value['second_star'])]["star_time"]['time']=$value['second_star'];//取得小组开始时间
                $today_temp[date("m-d H:i",$value['second_star'])]["star_time"]['id']=0;
                $today_temp[date("m-d H:i",$value['second_star'])]["star_time"]['status']=0;
            }
            else{
            foreach ($today_temp as $k => $v) {
                if($k!=date("m-d H:i",$value['second_star']))$flat=1; else{
                    $flat=1;
                foreach ($today_temp[date("m-d H:i",$value['second_star'])] as $sy => $sv) {
                    if($sv['id']==$value['id']){
                        $flat=0;//如果在此组中已经存在则不加入
                    }
                }
                } 
                if($flat){
                $today_temp[date("m-d H:i",$value['second_star'])][]=$value;
                $end_time=$today_temp[date("m-d H:i",$value['second_star'])]["end_time"]['time'];
                if(intval($value['second_end'])>intval($end_time)){
                $today_temp[date("m-d H:i",$value['second_star'])]["end_time"]['time']=$value['second_end'];//取得结束时间
                  }
                  var_dump(date("Y-m-d H:i:s",$today_temp[date("m-d H:i",$value['second_star'])]["end_time"]['time']));
                $today_temp[date("m-d H:i",$value['second_star'])]["end_time"]['id']=0;
                $today_temp[date("m-d H:i",$value['second_star'])]["end_time"]['status']=0;
                /*距离结束时间*/
                $today_temp[date("m-d H:i",$value['second_star'])]["end_time"]['time_out']=intval($today_temp[date("m-d H:i",$value['second_star'])]["end_time"]['time'])-time();
                /***************/
                $today_temp[date("m-d H:i",$value['second_star'])]["star_time"]['time']=$value['second_star'];//取得小组开始时间
                $today_temp[date("m-d H:i",$value['second_star'])]["star_time"]['id']=0;
                $today_temp[date("m-d H:i",$value['second_star'])]["star_time"]['status']=0;
                }
            
            }
        }
        }
        return $today_temp;
    }
    /**/
/*商品分类列表*/
    public function type_goods_list(){

    $this->check_shop();//检查是否有商家id
        $list=$this->get_goods_type();
        $this->display();
    }
    public function get_next_type($id){
        $good_type=M('type')->where('parentid='.$id)->order('typeid desc')->select();
        return $good_type;
    }
/*    public function get_next_type($id){
        $good_type=M('type')->where('parentid='.$id)->order('typeid desc')->select();
        foreach ($good_type as $key => $value) {
            $tem=$this->get_next_type($value['typeid']);
            if($tem)$good_type=array_merge_recursive($good_type,$tem);
        }
        return $good_type;
    }*/
/************/
/*留言板*/
    public function guest_book(){
        if($_POST['content']){
         $data['userid']=$this->_userid;
         $data['product_id']=intval($_POST['productid']);
         $data['content']=htmlspecialchars($_POST['content']);
         $data['createtime']=time();
         $data['status']=1;
         $data['type']=2;//类型
         $r=M('guestbook')->add($data);
         if($r){
            $res['status']=1;
            $res['info']="提交成功";
            echo json_encode($res);exit();
         }else{
            $res['status']=0;
            $res['info']="提交失败";
            echo json_encode($res);exit();

         }
    }else{

            $res['status']=0;
            $res['info']="提交失败";
            echo json_encode($res);exit();
    }
    
    }
/**/
/*拍卖叫价信息*/
    public function auction_info(){
    $this->check_shop();//检查是否有商家id
        $id=$_REQUEST['id']? intval($_REQUEST['id']):0;
        if($id){
        $product=M('Product_oversea')->field('id,title')->where("id=".$id)->find();
        $new_price=M('auction_price')->field(" `qq_user`.realname,`qq_auction_price`.* ")->join(" `qq_user` on `qq_user`.id=`qq_auction_price`.userid ")->where(' `qq_auction_price`.productid='.$id)->order(' `qq_auction_price`.price desc')->find();
        $count=M("auction_price")->where("productid=".$id)->count();
        $listRows = 3;
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
        $list =M('auction_price')->field(" `qq_user`.realname,`qq_auction_price`.* ")->join(" `qq_user` on `qq_user`.id=`qq_auction_price`.userid ")->where(' `qq_auction_price`.productid='.$id)->order(' `qq_auction_price`.price desc')->limit($page->firstRow . ',' . $page->listRows)->select();
         $this->assign('pages',$pages);
         $this->assign('list',$list);
         $this->assign("pro_info",$product);
         $this->assign("new_price",$new_price);
        $this->display();
        }else{
            header("location:/index.php");
        }
    }
/**/
/*团购表单提交*/
    public function group_buy_update(){
        if($_POST['productid']){
            $con['productid']=intval($_POST['productid']);
            $con['name']=htmlspecialchars($_POST['name']);
            $con['company']=htmlspecialchars($_POST['company']);
            $con['content']=htmlspecialchars($_POST['content']);
            $con['mobile']=htmlspecialchars($_POST['mobile']);
            $con['num']=intval($_POST['num']);
            $con['createtime']=time();
            $con['userid']=$this->_userid ? $this->_userid:0;
            $r=M('group_buy')->add($con);
            if($r){
             $data['status']=1;
             $data['info']="提交成功,稍后将会有工作人员联系您。";
             echo json_encode($data);exit();
            }else{
             $data['status']=0;
              $data['info']="提交失败";
              echo json_encode($data);exit();
            }

        }else{
        $data['status']=0;
        $data['info']="出错，没有改商品";
        echo json_encode($data);exit();
        }
    }

/**/
/*商品评价*/
    public function appraise(){
        if(IS_AJAX){
            $data['product_id'] = $_POST['id']?intval($_POST['id']):0;
            $data['level'] = $_POST['level']?intval($_POST['level']):1;
            $data['content'] = $_POST['content']?htmlspecialchars($_POST['content']):"";
            if (empty($data['content']))
                $this->ajaxReturn($res,'请填写评论',0);
            $data['userid'] = $this->_userid;
            $data['createtime'] = mktime();
            $data['status'] = 1;
            $data['type'] = 1;//类型
            $res=M("guestbook")->add($data);
            if($res)
                $this->ajaxReturn($res,'感谢您的评价。',1);
            else
                $this->ajaxReturn(null,"对不起,评价失败了。",0);
        }
        $this->check_shop();//检查是否有商家id
        $where['product_id']=$_REQUEST['id']?intval($_REQUEST['id']):0;
        $where['level']=$_REQUEST['type']?intval($_REQUEST['type']):1;

        $count=M('guestbook')->where($where)->order('id desc')->count();
        $listRows = 10;
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
        $list =M('guestbook')->field(' `qq_user`.wechat_name as realname,`qq_guestbook`.* ')->join(' `qq_user` on `qq_user`.id=`qq_guestbook`.userid ')->where('product_id='.$where['product_id'].' and type=1 and level='.$where['level'])->order('id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
         $this->assign('pages',$pages);
         $this->assign('list',$list);
         $this->assign('type',$where['level']);
         $this->assign('product_id',$where['product_id']);
         //统计各评价条数,好评为1，中评为2，差评为3
         $count=array();
         $con['product_id']=$where['product_id'];
         $con['type']=1;
         $con['level']=1;
         $count['hight']=M('guestbook')->where($con)->order('id desc')->count();
         $con['level']=2;
         $count['center']=M('guestbook')->where($con)->order('id desc')->count();
         $con['level']=3;
         $count['low']=M('guestbook')->where($con)->order('id desc')->count();
         $this->assign("count",$count);
         //获取商品信息
         $product=M("Product_oversea")->field("id,title,top_pics,en_name,sell_content")->where("id=".$where['product_id'])->find();
         if(!empty($product["top_pics"])){
                    $p_data=explode(':::',$product["top_pics"]);
                    $product['top_pics']=array();
                    foreach($p_data as $k=>$res){
                        $p_data_arr=explode('|',$res);                  
                        $product['top_pics'][$k]['filepath'] = $p_data_arr[0];
                        $product['top_pics'][$k]['filename'] = $p_data_arr[1];
                    }
                    unset($p_data);
                    unset($p_data_arr);
                }
        $this->assign("product",$product);
         $this->display();
    }
/**/

/*公用商品列表方法*/
    public function pro_list($where=array(),$order){
        $product=M('Product_oversea');
        $count=$product->where($where)->count();
        $listRows =  !empty($cat['pagesize']) ? $cat['pagesize'] : C('PAGE_LISTROWS');
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
        $list=$product->where($where)->order($order)->limit($page->firstRow . ',' . $page->listRows)->select();
        
        $this->assign('pages',$pages);
        return $list;
    }
/*收藏商品*/
    public function pro_collect(){
        if (IS_AJAX)
        {
            if(!empty($this->_userid) && $_REQUEST['pro_id']){
                $data['userid'] = $this->_userid;
                $data['productid'] = floatval($_REQUEST['pro_id']);
                $res = M("pro_collect")->where($data)->find();
                if($res){
                    $this->ajaxReturn($_REQUEST['pro_id'],"您已收藏过该商品",0);
                }

                $data['createtime'] = mktime();
                $res = M("pro_collect")->add($data);
                if ($res === false)
                    $this->ajaxReturn($_REQUEST['pro_id'],"收藏失败",0);
                else
                    $this->ajaxReturn($res,"收藏成功",1);
            }else{ 
                $this->ajaxReturn($_REQUEST['pro_id'],"收藏失败,请先登录！",0);
            }
            $this->ajaxReturn($_REQUEST['pro_id'],"收藏失败,商品不存在",0);
        }
        else
        {
            if(!empty($this->_userid)&&$_REQUEST['pro_id']){
                $data['userid']=$this->_userid;
                $data['productid']=floatval($_REQUEST['pro_id']);
                $res=M("pro_collect")->where($data)->find();
                if($res){
                    $this->error("您已收藏过该商品");
                }
                $data['createtime']=mktime();
                $res = M("pro_collect")->add($data);
                $this->success("收藏成功");
            }else{  
                $this->error("收藏失败,请先登录！");
            }
        }
    }
/**/
}
?>