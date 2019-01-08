<?php 
/*
*RandomAction.class.php
*@闲逛模式 add by dension
*/
if(!defined("QQCMS")) exit("Access Denied"); 
class RandomAction extends BaseAction{
	 public function _initialize() {
        parent::_initialize();
        import ( "@.ORG.Page2" );
   }
//评论最多
	public function reviews(){
    $this->check_shop();//检查是否有商家id
        $page=1;
        $listrow=6;
		$list=M("guestbook")->alias("a")->field(" count(a.id) as talk_number,a.content,c.id,c.title,c.thumb,c.url,c.en_name,b.wechat_name,b.wechat_pic ")->join(" `qq_user` as b on a.userid=b.id ")->join(" `qq_product_oversea` as c on a.product_id=c.id ")->where(" a.type=1 and a.status=1 and c.catid=5 and c.status=1 ")->order("talk_number desc")->group("a.product_id")->limit("0,".$listrow)->select();
        $this->assign("page",$page);
        $this->assign("listrow",$listrow);
		$this->assign("list",$list);
		$this->display();
	}
//点击最多
	public function hits(){
        $this->check_shop();//检查是否有商家id
        $where['catid']=5;//默认商城商品catid为5
        $where['status']=1;
        $product=M('Product_oversea');
        $count=$product->where($where)->count();
        $listRows =  10;    
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
        $list=$product->field("id,catid,title,hits,thumb,url,en_name,price,member_price,gold_price")->where($where)->order(" hits desc ")->limit($page->firstRow . ',' . $page->listRows)->select(); 
        
        $this->assign("list",$list);
        $this->assign('pages',$pages);
		$this->display();
	}
//购买最多
	public function sells(){
        $this->check_shop();//检查是否有商家id
        $count=M("order_data")->alias("a")->join(" `qq_order` as b on a.order_id=b.id ")->join(" `qq_product_oversea` as c on a.product_id=c.id ")->where(" b.pay_status=2 and c.catid=5 and c.status=1 ")->group("a.product_id")->count();
        $listRows =  10;    
        $page = new Page ( $count, $listRows );
        $pages = $page->show();
		$list=M("order_data")->alias("a")->field(" sum(a.number) as pro_number,c.id,c.catid,c.title,c.hits,c.thumb,c.url,c.en_name,c.price,c.member_price,c.gold_price ")->join(" `qq_order` as b on a.order_id=b.id ")->join(" `qq_product_oversea` as c on a.product_id=c.id ")->where(" b.pay_status=2 and c.catid=5 and c.status=1 ")->order("pro_number desc")->group("a.product_id")->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign("list",$list);
        $this->assign('pages',$pages);
		$this->display("hits");
	}
//ajax评论
    public function get_reviews(){
        $page=$_POST['page']?intval($_POST['page']):1;
        $listrow=$_POST['listrow']?intval($_POST['listrow']):10;
        $firstrow=$page*$listrow;
        $list=M("guestbook")->alias("a")->field(" count(a.id) as talk_number,a.content,c.id,c.title,c.thumb,c.url,c.en_name,b.wechat_name,b.wechat_pic ")->join(" `qq_user` as b on a.userid=b.id ")->join(" `qq_product_oversea` as c on a.product_id=c.id ")->where(" a.type=1 and a.status=1 and c.catid=5 and c.status=1 ")->order("talk_number desc")->group("a.product_id")->limit($firstrow.",".$listrow)->select();
        if(!empty($list)){
            $res['status']=1;
            $res['data']=$list;
            echo json_encode($res);exit();
        }else{
            $res['status']=0;
            $res['msg']="已经到底啦~";
            echo json_encode($res);exit();
        }
    }
}
?>