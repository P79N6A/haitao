<script src="__PUBLIC__/pc_js/jquery.js" type="text/javascript"></script>
<script src="__PUBLIC__/pc_js/jcarousellite_1.0.1.js" type="text/javascript"></script>

<script src="__PUBLIC__/pc_js/jquery.easing.js" type="text/javascript"></script>
<img class="subTitle" src="__PUBLIC__/pc_img/nouveautes_soustitre.gif">


<script type="text/javascript">
////
// 水平滚动，带悬停效果 //
////
var MyMar = null;
var SleepTime = 3000;                             //滚动间隔时间

function next(){
  $(".scrollx .next").click();
}
MyMar = setInterval(next,SleepTime);

////
// 垂直滚动，不带悬停效果 //
////
$(document).ready(function(){
  $(".newsContainer").jCarouselLite({
    easing:"easein",                              //动画效果，需要”jquery.easing.js“
    mouseWheel:true,                              //鼠标滚轮动作，需要”jquery.mousewheel.js“
    vertical:true,                                  //垂直滚动
    auto:2000,                                    //滚动间隔时间
    visible:3,
    scroll:1,
    speed: 500
  });
});
</script>