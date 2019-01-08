/**
 * Created by an.han on 13-12-17.
 */
window.onload = function () {
    if (!document.getElementsByClassName) {
        document.getElementsByClassName = function (cls) {
            var ret = [];
            var els = document.getElementsByTagName('*');
            for (var i = 0, len = els.length; i < len; i++) {

                if (els[i].className.indexOf(cls + ' ') >=0 || els[i].className.indexOf(' ' + cls + ' ') >=0 || els[i].className.indexOf(' ' + cls) >=0) {
                    ret.push(els[i]);
                }
            }
            return ret;
        }
    }

    var table = document.getElementById('cartTable'); // 购物车表格
    var selectInputs = document.getElementsByClassName('check'); // 所有勾选框
    var checkAllInputs = document.getElementsByClassName('check-all') // 全选框
    var tr = table.children[1].rows; //行
    var selectedTotal = document.getElementById('selectedTotal'); //已选商品数目容器
    var priceTotal = document.getElementById('priceTotal'); //总计
    var directTotal = document.getElementById('directTotal'); //关税总计
    var deleteAll = document.getElementById('deleteAll'); // 删除全部按钮
    var selectedViewList = document.getElementById('selectedViewList'); //浮层已选商品列表容器
    var selected = document.getElementById('selected'); //已选商品
    var foot = document.getElementById('foot');

    // 更新总数和总价格，已选浮层
    function getTotal() {
		var seleted = 0;
		var price = 0;
        var direct = 0;
		var HTMLstr = '';
		for (var i = 0, len = tr.length; i < len; i++) {
			if (tr[i].getElementsByTagName('input')[0].checked) {
				tr[i].className = 'on';
                var _span = tr[i].getElementsByTagName('span')[1]; //-号
                var p_count = tr[i].getElementsByTagName('input')[1].value;
                var _direct = $(tr[i].getElementsByTagName('input')[1]).attr("data-direct");
				seleted += 1;
                //如果数目只有一个，把-号去掉
                if (p_count == 1) {
                    _span.innerHTML = '';
                }else{
                    _span.innerHTML = '-';
                }

				price += parseFloat(tr[i].cells[4].innerHTML);
                direct += parseFloat(_direct);
				HTMLstr += '<div><img src="' + tr[i].getElementsByTagName('img')[0].src + '"><span class="del" index="' + i + '">取消选择</span></div>'
			}
			else {
				tr[i].className = '';
			}
		}
	
		selectedTotal.innerHTML = seleted;
        if (direct > 50)
            price += direct;
		priceTotal.innerHTML = price.toFixed(2);
        directTotal.innerHTML = direct.toFixed(2);
		selectedViewList.innerHTML = HTMLstr;
	
		if (seleted == 0) {
			foot.className = 'foot';
		}
	}

    // 计算单行价格
    function getSubtotal(tr) {
        var tr_direct = 0;
        var cells = tr.cells;
        var price = cells[2]; //单价
        var subtotal = cells[4]; //小计td
        var countInput = tr.getElementsByTagName('input')[1]; //数目input
        var span = tr.getElementsByTagName('span')[1]; //-号
        var moduleid = $(countInput).attr("data-moduleid");
        var productid = $(countInput).attr("data-id");
        var post_price = $(tr.getElementsByTagName('input')[1]).attr("data-post_price");
        var post_rate = $(tr.getElementsByTagName('input')[1]).attr("data-post_rate");
        if (post_rate > 0)
        {
            var tr_direct = (parseInt(countInput.value) * parseFloat(price.innerHTML) * post_rate)/100;
        }
        else
        {
            var tr_direct = parseInt(countInput.value) * parseFloat(post_price);
        }
        $(tr.getElementsByTagName('input')[1]).attr("data-direct",tr_direct.toFixed(2));
        // 更新后台购物车数据
        editorder(moduleid,productid,countInput,cells);
        //写入HTML
        subtotal.innerHTML = (parseInt(countInput.value) * parseFloat(price.innerHTML)).toFixed(2);
        //如果数目只有一个，把-号去掉
        if (countInput.value == 1) {
            span.innerHTML = '';
        }else{
            span.innerHTML = '-';
        }

        
    }

    //动态更新后台购物车数据
    function editorder(moduleid,productid,countInput,_cells){
        var _price = _cells[2]; //单价
        var _subtotal = _cells[4]; //小计td
        var count = countInput.value;
        $.ajax({
            type:"POST",
            url: "/index.php?m=Order&a=ajax&do=update",
            data: {'moduleid':moduleid,'id': productid,'num':count},
            timeout:"4000",
            dataType:"JSON",
            success: function(data){
                if(data.data==1){
                    return true;
                }else{
                    alert(data.info);
                    countInput.value = data.maxcount;
                    // $(countInput).attr("value",data.maxcount);
                    //写入HTML
                    _subtotal.innerHTML = (parseInt(data.maxcount) * parseFloat(_price.innerHTML)).toFixed(2);
                    getTotal();//更新总计
                }
            },
            error:function(){
                return false;
            }
        });
    }

    // 点击选择框
    for(var i = 0; i < selectInputs.length; i++ ){
        selectInputs[i].onclick = function () {
            if (this.className.indexOf('check-all') >= 0) { //如果是全选，则吧所有的选择框选中
                for (var j = 0; j < selectInputs.length; j++) {
                    selectInputs[j].checked = this.checked;
                }
            }
            if (!this.checked) { //只要有一个未勾选，则取消全选框的选中状态
                for (var i = 0; i < checkAllInputs.length; i++) {
                    checkAllInputs[i].checked = false;
                }
            }
            getTotal();//选完更新总计
        }
    }

    // 显示已选商品弹层
    selected.onclick = function () {
        if (selectedTotal.innerHTML != 0) {
            foot.className = (foot.className == 'foot' ? 'foot show' : 'foot');
        }
    }

    //已选商品弹层中的取消选择按钮
    selectedViewList.onclick = function (e) {
        var e = e || window.event;
        var el = e.srcElement;
        if (el.className=='del') {
            var input =  tr[el.getAttribute('index')].getElementsByTagName('input')[0]
            input.checked = false;
            input.onclick();
        }
    }

    //为每行元素添加事件
    for (var i = 0; i < tr.length; i++) {
        //将点击事件绑定到tr元素
        tr[i].onclick = function (e) {
            var e = e || window.event;
            var el = e.target || e.srcElement; //通过事件对象的target属性获取触发元素
            var cls = el.className; //触发元素的class
            var countInout = this.getElementsByTagName('input')[1]; // 数目input
            var value = parseInt(countInout.value); //数目
            //通过判断触发元素的class确定用户点击了哪个元素
            switch (cls) {
                case 'add': //点击了加号
                    countInout.value = value + 1;
                    getSubtotal(this);
                    break;
                case 'reduce': //点击了减号
                    if (value > 1) {
                        countInout.value = value - 1;
                        getSubtotal(this);
                    }
                    break;
                case 'delete': //点击了删除
                    var conf = confirm('确定删除此商品吗？');
                    var obj = $(this).attr("data-num");
                    var modid = $(this).attr("data-modid");
                    var id = $(this).attr("id");
                    var thisobj = this;
                    if (conf) {
                        var objs = document.getElementById(obj);
                        var datas={'moduleid':modid,'id': id,'num':objs.value};
                        $.ajax({
                            type:"POST",
                            url: "/index.php?m=Order&a=ajax&do=del",
                            data: datas,
                            timeout:"4000",
                            dataType:"JSON",
                            success: function(data){
                                if(data.data==1){
                                    thisobj.parentNode.removeChild(thisobj);
                                }
                            },
                            error:function(){
                                alert("出错");
                            }
                        });
                    }
                    break;
            }
            getTotal();
        }
        // 给数目输入框绑定keyup事件
        tr[i].getElementsByTagName('input')[1].onkeyup = function () {
            var val = parseInt(this.value);
            if (isNaN(val) || val <= 0) {
                val = 1;
            }
            if (this.value != val) {
                this.value = val;
            }

            getSubtotal(this.parentNode.parentNode); //更新小计
            getTotal(); //更新总数
        }
    }

    // 点击全部删除
    deleteAll.onclick = function () {
        if (selectedTotal.innerHTML != 0) {
            var con = confirm('确定删除所选商品吗？'); //弹出确认框
            if (con) {
                for (var i = 0; i < tr.length; i++) {
                    // 如果被选中，就删除相应的行
                    if (tr[i].getElementsByTagName('input')[0].checked) {
                        var obj = $(tr[i]).attr("data-num");
                        var modid = $(tr[i]).attr("data-modid");
                        var id = $(tr[i]).attr("id");
                        var thisobj = tr[i];
                        var objs = document.getElementById(obj);
                        var datas={'moduleid':modid,'id': id,'num':objs.value};
                        $.ajax({
                            type:"POST",
                            url: "/index.php?m=Order&a=ajax&do=del",
                            data: datas,
                            timeout:"4000",
                            dataType:"JSON",
                            success: function(data){
                            },
                            error:function(){
                                alert("出错");
                            }
                        });
                        tr[i].parentNode.removeChild(tr[i]); // 删除相应节点
                        i--; //回退下标位置 
                    }
                }
            }
        } else {
            alert('请选择商品！');
        }
        getTotal(); //更新总数
    }

    // 默认全选
    checkAllInputs[0].checked = true;
    checkAllInputs[0].onclick();
}
