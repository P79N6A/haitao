<?php
/**
 * 
 * ProductAction.class.php (商品)
 *
 * @package      	QQCMS
 * @author          Ivan QQ:79441928 <admin@qqcms.net>
 * @copyright     	Copyright (c) 2008-2011  (http://www.qqcms.net)
 * @license         http://www.qqcms.net/license.txt
 * @version        	QQCMS网站管理系统 v4.1.5 2011-03-01 qqcms.net $
 */
if(!defined("QQCMS")) exit("Access Denied"); 
class ProductAction extends BaseAction
{
   public function _initialize() {
        parent::_initialize();
        import ( "@.ORG.Page2" );
        $this->shop_id=$_SESSION['shop_id'];
        $this->get_goods_type();//获取商品类别列表

   }

/*商品详情*/
    public function _before_show(){
        
    }

/*秒拍*/
    public function second_beats(){

        $product=M('Product');
        $where['catid']=9;//默认直购商品catid为6
        /*修改状态，把超时的状态变为0*/
        $data1['status']=0;
        $now_time=time()+2;//由于容易有1、2秒的误差导致转换状态不成功，所以加多2秒
        $rr=$product->where(" id>0 and catid=".$where['catid']." and second_end<".$now_time)->save($data1);
        /**/
        //$where['status']=1;
        $today_small=strtotime(date("Y-m-d",time()));//今日凌晨
        $tomorrow_small=strtotime(date("Y-m-d",time()+86400));//明日凌晨
        $after_to_small=strtotime(date("Y-m-d",time()+172800));//后日凌晨
        /*取今天商品列表*/
        $today_list=$product->field("id,title,thumb,status,price,url,stock,second_price,en_name,second_star,second_end")->where("second_star>".$today_small." and second_end<".$tomorrow_small." and catid=".$where['catid'])->order('second_star')->select();
        /*取明天商品列表*/
        $tomorrow_list=$product->field("id,title,thumb,status,price,url,stock,second_price,en_name,second_star,second_end")->where("second_star>".$tomorrow_small." and second_end<".$after_to_small." and catid=".$where['catid'])->select();
        $today_temp=array();
        $tomorrow_temp=array();
        /*今日分组*/
         $today_temp=$this->second_team($today_list);
        /*明日分组*/
        $tomorrow_temp=$this->second_team($tomorrow_list);
        $this->assign("today_list",$today_temp);
        $this->assign("tomorrow_temp",$tomorrow_temp);
    	$this->display();
    }

/*拍卖*/
    public function auction(){
    	
        $product=M('Product');
        $where['catid']=7;//默认直购商品catid为6
        /*修改状态，把超时的状态变为0*/
        $data1['status']=0;
        $now_time=time()+2;//由于容易有1、2秒的误差导致转换状态不成功，所以加多2秒
        $rr=$product->where(" id>0 and catid=".$where['catid']." and second_end<".$now_time)->save($data1);
        /**/
        //$where['status']=1;
        $today_small=strtotime(date("Y-m-d",time()));//今日凌晨
        $tomorrow_small=strtotime(date("Y-m-d",time()+86400));//明日凌晨
        $after_to_small=strtotime(date("Y-m-d",time()+172800));//后日凌晨
        /*取今天商品列表*/
        $today_list=$product->field("id,title,thumb,status,price,url,stock,start_price,content,en_name,second_star,second_end")->where("second_star>".$today_small." and second_end<".$tomorrow_small." and catid=".$where['catid'])->order('second_star')->select();

        /*取明天商品列表*/
        $tomorrow_list=$product->field("id,title,thumb,status,price,url,stock,start_price,en_name,second_star,second_end")->where("second_star>".$tomorrow_small." and second_end<".$after_to_small." and catid=".$where['catid'])->select();
        $today_temp=array();
        $tomorrow_temp=array();
        /*今日分组*/
         $today_temp=$this->second_team($today_list);
        /*明日分组*/
        $tomorrow_temp=$this->second_team($tomorrow_list);
        $this->assign("today_list",$today_temp);
        $this->assign("tomorrow_temp",$tomorrow_temp);
    	$this->display();
    }

/*直购*/
    public function direct_sell(){
        $direct=M('Product');
        $where['catid']=6;//默认直购商品catid为6
        $where['status']=1;
        $order="listorder desc,id desc";
        $list=$this->pro_list($where,$order);
        $this->assign("list",$list);

        $this->display();
    }

/*商家栏目商品*/
    public function shop_goods(){

        $slide_data_id=$_REQUEST['ad_column'] ? $_REQUEST['ad_column']:0;

        /*查出栏目id*/
        $slid_data=M('slide_data')->where(' id='.intval($slide_data_id))->find();
        $this->assign('column',$slid_data);
        $product=M('product');
        $count=$product->field(" `qq_product`.id,`qq_product`.catid,`qq_product`.title,`qq_product`.thumb,`qq_product`.url,`qq_product`.en_name,`qq_product`.price,`qq_product`.member_price ")->join(' `qq_shopcolumn_data` on `qq_shopcolumn_data`.goods_id=`qq_product`.id ')->where(" `qq_shopcolumn_data`.slide_data_id=".$slid_data['id'])->count();
        $listRows =  !empty($cat['pagesize']) ? $cat['pagesize'] : C('PAGE_LISTROWS'); 
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
        $list=$product->field(" `qq_product`.id,`qq_product`.catid,`qq_product`.title,`qq_product`.thumb,`qq_product`.url,`qq_product`.en_name,`qq_product`.price,`qq_product`.member_price ")->join(' `qq_shopcolumn_data` on `qq_shopcolumn_data`.goods_id=`qq_product`.id ')->where(" `qq_shopcolumn_data`.slide_data_id=".$slid_data['id'])->order('listorder desc,id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign("list",$list);
        $this->assign('pages',$pages);
        $this->display();
    }

/*私人酒窖*/
    public function self_goods(){
        
        $where['catid']=25;//默认私人酒窖商品catid为25
        $where['status']=1;
        $where['userid']=$this->shop_id; 
        $order="listorder desc,id desc";
        $list=$this->pro_list($where,$order);
        $this->assign("list",$list);
        $this->display();
    }

/*团购*/
    public function group_buy(){
        $where['catid']=26;//默认团购商品catid为26
        $where['status']=1;
        $order="listorder desc,id desc";
        $list=$this->pro_list($where);
        $this->assign("list",$list);
        $this->display();
    }  

/*奢侈品*/
    public function luxury_goods(){
        $where['catid']=5;//默认商城商品catid为5
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
        $where['catid']=5;//默认商城商品catid为5
        $where['status']=1;
        $where['typeid']=$_REQUEST['typeid']? array('LIKE','%'.$_REQUEST['typeid'].'%'):0;
        $next_list=$this->get_next_type($_REQUEST['typeid']);
        $this->assign("next_list",$next_list);
        $this->assign("typeid",$_REQUEST['typeid']);
        $order=$this->ord_list();//调用排序
        $list=$this->pro_list($where,$order);
        $this->assign("list",$list);
        $this->display();
    }  

/*公用商品列表方法*/
    public function pro_list($where=array(),$order){
        $product=M('Product');
        $count=$product->field("id,catid,title,thumb,url,en_name,price,member_price,gold_price")->where($where)->count();
        $listRows =  !empty($cat['pagesize']) ? $cat['pagesize'] : C('PAGE_LISTROWS');    
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
        $list=$product->field("id,catid,title,thumb,url,en_name,price,member_price,gold_price")->where($where)->order($order)->limit($page->firstRow . ',' . $page->listRows)->select(); 
        
        $this->assign('pages',$pages);
        return $list;
    }

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
    /*秒拍-分组*/
    public function second_team($today_list=array()){
        $today_temp=array();
        foreach ($today_list as $key => $value) {
            if(!$today_temp){
                $today_temp[date("H:i",$value['second_star'])][]=$value;
                $time=$today_temp[date("H:i",$value['second_star'])]["end_time"]['time'];
                if(intval($value['second_end'])>$time){
                $today_temp[date("H:i",$value['second_star'])]["end_time"]['time']=$value['second_end'];//取得小组结束时间
                }
                $today_temp[date("H:i",$value['second_star'])]["end_time"]['id']=0;
                $today_temp[date("H:i",$value['second_star'])]["end_time"]['status']=0;
                /*距离结束时间*/
                $today_temp[date("H:i",$value['second_star'])]["end_time"]['time_out']=intval($today_temp[date("H:i",$value['second_star'])]["end_time"]['time'])-time();
                /***************/
                $today_temp[date("H:i",$value['second_star'])]["star_time"][]=$value['second_star'];//取得小组开始时间
                $today_temp[date("H:i",$value['second_star'])]["star_time"]['id']=0;
                $today_temp[date("H:i",$value['second_star'])]["star_time"]['status']=0;
            }
            else{
            foreach ($today_temp as $k => $v) {
                if($k!=date("H:i",$value['second_star'])){$flat=1;} 
                foreach ($today_temp[date("H:i",$value['second_star'])] as $sy => $sv) {
                    if($sv['id']==$value['id']){
                        $flat=0;//如果在此组中已经存在则不加入
                    }else{$flat=1;}
                }
                if($flat){
                $today_temp[date("H:i",$value['second_star'])][]=$value;
                $end_time=$today_temp[date("H:i",$value['second_star'])]["end_time"]['time'];
                if(intval($value['second_end'])>$time){
                $today_temp[date("H:i",$value['second_star'])]["end_time"][]=$value['second_end'];//取得结束时间
                  }
                $today_temp[date("H:i",$value['second_star'])]["end_time"]['id']=0;
                $today_temp[date("H:i",$value['second_star'])]["end_time"]['status']=0;
                /*距离结束时间*/
                $today_temp[date("H:i",$value['second_star'])]["end_time"]['time_out']=intval($today_temp[date("H:i",$value['second_star'])]["end_time"]['time'])-time();
                /***************/
                $start_time=$today_temp[date("H:i",$value['second_star'])]["star_time"]['time'];
                if(intval($value['second_star'])<intval($start_time)){
                $today_temp[date("H:i",$value['second_star'])]["star_time"]['time']=$value['second_star'];//取得小组开始时间
                }
                $today_temp[date("H:i",$value['second_star'])]["star_time"]['id']=0;
                $today_temp[date("H:i",$value['second_star'])]["star_time"]['status']=0;
                }
            
            }
        }
        }
        return $today_temp;
    }
    /**/
/*商品分类列表*/
    public function type_goods_list(){

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
        $id=$_REQUEST['id']? intval($_REQUEST['id']):0;
        if($id){
        $product=M('product')->field('id,title,start_price,min_price')->where("id=".$id)->find();
        $new_price=M('auction_price')->field(" `qq_user`.realname,`qq_auction_price`.* ")->join(" `qq_user` on `qq_user`.id=`qq_auction_price`.userid ")->where(' `qq_auction_price`.productid='.$id)->order(' `qq_auction_price`.price desc')->find();
        $count=M("auction_price")->where("productid=".$id)->count();
        import ( "@.ORG.Page2" );
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
}
?>