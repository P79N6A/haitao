/**
 * specs_extend.js
 *
 * 处理产品属性规格选择,添加,删除
 * 若有更好的方法可以直接修改这里
 */
$(function(){
	$("#showfieldset").click(function(){
		art.dialog({
			width:"100%",
			lock:true,
			opacity:"0.3",
			background:"#FFF",
			content: document.getElementById('fieldset-specsname'),
			id: 'fieldset-specsname',
			ok: function(){
				window.location.reload();
			},
			cancel: true
		});
	});

	$("#saveSpecsname").click(function(){
		$specsname = $("input[name='specsname']").val();
		if (!$specsname) return false;//判断如果没有输入 不保存
		var strval = '';
		var first_container = 0;
		$(".mb-container").find("ul li").each(function(v,e){
			var propertyvalue = $(e).attr("data-tag");
			//组装属性值
			if (propertyvalue)
			strval += propertyvalue+',';
			$(e).remove();
			first_container = 1;
		});
		saveextend($specsname,strval,saveextendUrl);
		if (first_container==0 || _container_==0) return false;
		$specsname = $("input[name='specsname']").val(""); //保存添加后删除原内容
	});
});

//计算数组条数
function count(o){
    var t = typeof o;
    if(t == 'string'){
       	return o.length;
    }else if(t == 'object'){
        var n = 0;
        for(var i in o){
            n++;
        }
        return n;
    }
    return false;
}


function extendShow(obj)
{
	sp_site = arr_id;
	var extend_id = $(obj).attr("data-extend_id");
	var specs_id = $(obj).attr("data-specs_id");
	var textValue = $(obj).find('b').html();
	// var data_lock = $(obj).find('b').attr("data-lock");
	var data_arr_id = $("[data-arr_id]");

	if (arr_id==0)
	{
		append_spval(data_arr_id,extend_id,specs_id,arr_id,textValue,arr_id,0);
		arr_id++;
		$(obj).css("background","#6DA0BA");
		$(obj).find('b').attr("data-lock",1);
	}
	else
	{
		var objGroup = {}; //总对象组
		var specsGroup = new Array(); //属性名称组
		$(data_arr_id).each(function(n,el){
			var extendGroup = new Array(); //属性值组
			var obj2Group = new Array(); //当前节点属性组
			/*获取某组下的属性规格节点*/
			var old_ex_sp = $(data_arr_id).find("td#td-ex_id"+n+">a"); 
			$(old_ex_sp).each(function(no,elo){
				/*获取各组属性规格*/
				var old_ex_id = $(elo).attr("data-ex_id");
				var old_sp_id= $(elo).attr("data-sp_id");
				/*判断属性名称ID是否已存在*/
				var m = specsGroup.indexOf(old_sp_id);
				/*end*/
				extendGroup.push(old_ex_id); //附加属性值ID，正常情况下不会重复
				if (m==-1)
				specsGroup.push(old_sp_id); //附加属性名称ID，若属性名称ID已存在则不添加
			});
			/*每循环一个节点保存为一组对象*/
			obj2Group['specs_id'] = specsGroup; 
			obj2Group['extend_id'] = extendGroup;
			/*end*/
			objGroup[n] = obj2Group; //保存总对象组
		});
		// console.log(objGroup);
		/*检查已选属性名称ID是否存在，Y：在已添加属性规格组上继续添加属性值，N：新增属性规格组*/
		var specsCheck = specsGroup.indexOf(specs_id);
		if (specsCheck!=-1)
		{
			var lockupdatearr = new Array();
			var lockupdate = 0;
			for (var x in objGroup){
				//定义两个临时数组
				var specsarrx = new Array();
				var extendarrxarr = new Array();
				var specsarrx = objGroup[x].specs_id //获取当前组下的属性名称ID
				var extendarrx = objGroup[x].extend_id;
				var specsarrCheckx = specsarrx.indexOf(specs_id);
				for (var z in extendarrx)
				{
					extendarrxarr.push(extendarrx[z]);
				}
				extendarrxarr.splice(specsarrCheckx,1,extend_id);
				if (extendarrxarr.toString() == extendarrx.toString())
					lockupdate = 1; //已经存在该组规格
				else
					lockupdate = 0; //改组规格不存在
				lockupdatearr.push(lockupdate);
			}
			console.log(lockupdatearr);
			var specsarr;
			for (var k in objGroup)
			{
				specsarr = objGroup[k].specs_id //获取当前组下的属性名称ID
				var extendarr = objGroup[k].extend_id;
				if (count(specsarr)==1)
				{
					if (extendarr[0] == extend_id)
						lockupdate = 1; //已经存在该组规格
					else
						lockupdate = 0; //改组规格不存在
					continue; //若当前属性名称ID只有一个，继续下一步
				}
				else
				{
					//获取相同属性名称ID所在位置
					var specsarrCheck = specsarr.indexOf(specs_id); 
					/*console.log(specsarr);
					console.log(extendarr);*/

					//更换该位置下的属性值ID
					if (lockupdatearr[k]!=1)
					{
						extendarr.splice(specsarrCheck,1,extend_id);
						//去除重复属性组
						var repeatNum = 0;
						for (var z in objGroup)
						{
							if (extendarr.toString() == objGroup[z].extend_id.toString())
								repeatNum++; //自动增加重复条数
						}
						if (repeatNum>1) continue; //若大于2条，继续下一步
						append_spval(data_arr_id,extendarr,specsarr,arr_id,'',arr_id,2);
						arr_id++;
						$(obj).css("background","#6DA0BA");
						$(obj).find('b').attr("data-lock",1);
					}
				}
			}

			//若当前属性名称ID只有一个，单纯添加新一组属性
			if (count(specsarr)==1 && lockupdate!=1){
				append_spval(data_arr_id,extend_id,specs_id,arr_id,textValue,arr_id,0);
				arr_id++;
				$(obj).css("background","#6DA0BA");
				$(obj).find('b').attr("data-lock",1);
			}
			
		}
		else
		{
			for (var j in objGroup)
			{
				var specsarr = objGroup[j].specs_id;
				var extendarr = objGroup[j].extend_id;
				append_spval(data_arr_id,extend_id,specs_id,arr_id,textValue,j,1);
			}
			$(obj).css("background","#6DA0BA");
			$(obj).find('b').attr("data-lock",1);
		}
		/*end*/
	}		
	/*}else{
		$("[data-ex_id='"+extend_id+"']").parent().parent().remove();
		$(obj).find('b').attr("data-lock",0);
	}*/

}

function append_spval(data_arr_id,extend_id,specs_id,arr_id,textValue,sp_site,type_id)
{
	switch (type_id){
		case 0:
		$texttr = $('<tr></tr>',{"style":"opacity:0.1;","data-arr_id":"specs"});
		$texttd = $('<td></td>',{"id":"td-ex_id"+sp_site+""});
		if (extend_id!='')
		{
			$texta = $('<a></a>',{"data-ex_id":extend_id,"data-sp_id":specs_id,href:"javascript:"});
			$($texta).append('<input type="hidden" placeholder="attribute_group" class="input-text" name="attribute_group'+sp_site+'[]" value="'+extend_id+'">');
			$textb = $('<b></b>');
			$($textb).html(textValue);
			$($texta).append($textb);
			$($texttd).append($texta);
		}
		$($texttr).append($texttd);
		$($texttr).append('<td><input type="checkbox" name="lock_extend[]" value="'+sp_site+'"></td>');
		$($texttr).append('<td><input type="text" data-input="price" class="input-text" name="price'+sp_site+'" value=""></td>');
		$($texttr).append('<td><input type="text" data-input="member_price" class="input-text" name="member_price'+sp_site+'" value=""></td>');
		$($texttr).append('<td><input type="text" data-input="stock" class="input-text" name="stock'+sp_site+'" value=""></td>');
		$($texttr).append('<td><a href="javascript:" style="background: #AB0E0E;" onclick="softDelOnePro(this);"><b>移除</b></a></td>');

		$("#table-specs").append($texttr);
		$($texttr).animate({"opacity":1});
		break;
		case 1:
		$texta = $('<a></a>',{"data-ex_id":extend_id,"data-sp_id":specs_id,href:"javascript:"});
		$($texta).append('<input type="hidden" placeholder="attribute_group" class="input-text" name="attribute_group'+sp_site+'[]" value="'+extend_id+'">');
		$textb = $('<b></b>');
		$($textb).html(textValue);
		$($texta).append($textb);
		$(data_arr_id).find("td#td-ex_id"+sp_site+"").append($texta);
		break;
		case 2:
		$texttr = $('<tr></tr>',{"style":"opacity:0.1;","data-arr_id":"specs"});
		$texttd = $('<td></td>',{"id":"td-ex_id"+sp_site+""});
		var tp = typeof specs_id;
		if (tp=='object')
		{
			for (var h in specs_id)
			{
				var extendValue = $("[data-extend_id='"+extend_id[h]+"']").find('b').html();
				$texta = $('<a></a>',{"data-ex_id":extend_id[h],"data-sp_id":specs_id[h],href:"javascript:"});
				$($texta).append('<input type="hidden" placeholder="attribute_group" class="input-text" name="attribute_group'+sp_site+'[]" value="'+extend_id[h]+'">');
				$textb = $('<b></b>');
				$($textb).html(extendValue);
				$($texta).append($textb);
				$($texttd).append($texta);
			}
		}

		$($texttr).append($texttd);
		$($texttr).append('<td><input type="checkbox" name="lock_extend[]" value="'+sp_site+'"></td>');
		$($texttr).append('<td><input type="text" data-input="price" class="input-text" name="price'+sp_site+'" value=""></td>');
		$($texttr).append('<td><input type="text" data-input="member_price" class="input-text" name="member_price'+sp_site+'" value=""></td>');
		$($texttr).append('<td><input type="text" data-input="stock" class="input-text" name="stock'+sp_site+'" value=""></td>');
		$($texttr).append('<td><a href="javascript:" style="background: #AB0E0E;" onclick="softDelOnePro(this);"><b>移除</b></a></td>');
		$("#table-specs").append($texttr);
		$($texttr).animate({"opacity":1});
		break;
		default:
		break;
	}
	
}

// 新增一组属性
function saveextend(specsname,strval,url)
{
	if (specsname == undefined || strval == undefined) return false;
	strval = strval.substr(0,strval.length-1);
	$.ajax({
		type: "POST",
		url: url,
		data : {"specsname": specsname, "strval": strval},
		async: false,
		timeout: 5000,
		dataType: "JSON",
		success: function(json){
			if (json.status == 0)
			{
				_container_ = 0;
				alert(json.info);
				return false;
			}
			_container_ = 1;
			var list = json.data;

			$Specsnameul = $('<ul></ul>',{"data-specs_id":list.specs_id,"style":'height:1px;opacity: 0.1;'});
			$SpecsnameLi = $('<li></li>',{"data-specs_id":list.specs_id,class:'specs_id'});
			$SpecsnameA = $('<a></a>',{"data-specs_id":list.specs_id,rel:'2',href:'javascript:'});
			$SpecsnameB = $('<b></b>').html(list.specsname);
			$($SpecsnameA).append($SpecsnameB);
			$($SpecsnameLi).append($SpecsnameA);
			$($Specsnameul).append($SpecsnameLi);

			$_SpecsnameLi = $('<li></li>',{class:'extend_id'});
			var extend_data = list.extend;
			for (var i = 0; i < count(extend_data); i++) {
				$_SpecsnameA = $('<a></a>',{"data-specs_id":list.specs_id,"data-extend_id":extend_data[i].extend_id,rel:'2',href:'javascript:'});
				$_SpecsnameB = $('<b></b>').html(extend_data[i].propertyvalue);
				$_Specsnamespan = $('<span></span>',{class:"specs-tag-remove",onclick:"tagRemove(this,'"+tagRemoveUrl+"');"});
				$($_SpecsnameB).append($_Specsnamespan);
				$($_SpecsnameA).append($_SpecsnameB);
				$($_SpecsnameLi).append($_SpecsnameA);
			};

			$($Specsnameul).append($_SpecsnameLi);
			$_Specsname_Li = $('<li></li>');
			$_Specsname_A = $('<a></a>',{href:"javascript:","data-specs_id":list.specs_id,onclick:"allTagRemove(this, '"+tagRemoveUrl+"');"});
			$_Specsname_B = $('<b>删除</b>');
			$($_Specsname_A).append($_Specsname_B);
			$($_Specsname_Li).append($_Specsname_A);

			$($Specsnameul).append($_Specsname_Li);
			$($Specsnameul).animate({height:"27px",opacity: 1})
			$("#showsel").append($Specsnameul).animate();

		},
		error: function(){
			alert("系统出错")
		}
	});
}


//删除已选属性组
function softDelOnePro(obj){
	var objspecs = $(obj).parent().parent();
	objspecs.find('td>a').each(function(fo,fof){
		var ex_id = $(fof).attr("data-ex_id");
		if (ex_id!=undefined){
			var SpecsNum = 0;
			$("[data-ex_id='"+ex_id+"']").each(function(oo,soo){
				SpecsNum = oo;
			});
			if (SpecsNum==0)
				$("[data-extend_id='"+ex_id+"']").css("background","#9D9FA0");
		}
	});

	objspecs.remove();

	$("[data-arr_id]").each(function(so,sos){
		$(sos).find('td').each(function(e,ev){
			var eid = $(ev).attr("id");
			if (eid!=undefined) 
			{
				$(ev).attr("id","td-ex_id"+so);
				arr_id = so;
			}
			
			$(ev).find("input[placeholder='attribute_group']").attr("name","attribute_group"+so+"[]");
			$(ev).find("input[data-input='price']").attr("name","price"+so+"");
			$(ev).find("input[data-input='member_price']").attr("name","member_price"+so+"");
			$(ev).find("input[data-input='stock']").attr("name","stock"+so+"");
			$(ev).find("input[type='checkbox']").val(so);
		});
		$(sos).find("input[placeholder='save_extend']").attr("name","save_extend"+so+"");
		$(sos).find("input[placeholder='property_id']").attr("name","property_id"+so+"");
	});
	arr_id++;
}

function delOnePro(obj,url)
{
	var property_id = $(obj).attr("data-property_id");
	var objspecs = $(obj).parent().parent();

	objspecs.find('td>a').each(function(fo,fof){
		var ex_id = $(fof).attr("data-ex_id");
		if (ex_id!=undefined){
			var SpecsNum = 0;
			$("[data-ex_id='"+ex_id+"']").each(function(oo,soo){
				SpecsNum = oo;
			});
			if (SpecsNum==0)
				$("[data-extend_id='"+ex_id+"']").css("background","#9D9FA0");
		}
	});

	$.ajax({
		type: "POST",
		url: url,
		data : {'property_id':property_id},
		async: false,
		timeout: 5000,
		dataType: "JSON",
		success: function(json){
			if (json.status==0)
			{
				alert(json.info);
				return false;
			}
			objspecs.remove();
		},
		error: function(){
			alert("系统出错")
		}
	});

	$("[data-arr_id]").each(function(so,sos){
		$(sos).find('td').each(function(e,ev){
			var eid = $(ev).attr("id");
			if (eid!=undefined){
				$(ev).attr("id","td-ex_id"+so);
				arr_id = so;
			}
			$(ev).find("input[placeholder='attribute_group']").attr("name","attribute_group"+so+"[]");
			$(ev).find("input[data-input='price']").attr("name","price"+so+"");
			$(ev).find("input[data-input='member_price']").attr("name","member_price"+so+"");
			$(ev).find("input[data-input='stock']").attr("name","stock"+so+"");
			$(ev).find("input[type='checkbox']").val(so);
		});
		$(sos).find("input[placeholder='save_extend']").attr("name","save_extend"+so+"");
		$(sos).find("input[placeholder='property_id']").attr("name","property_id"+so+"");
	});
	arr_id++;
}

// 删除单条属性值
function tagRemove(obj,url)
{
	$extend_id = $(obj).parent().parent().attr("data-extend_id");
	$specs_id = $(obj).parent().parent().attr("data-specs_id");
	$.ajax({
		type: "POST",
		url: url,
		data : {'extend_id':$extend_id,'specs_id':$specs_id},
		async: false,
		timeout: 5000,
		dataType: "JSON",
		success: function(json){
			if (json.data==1) $(obj).parent().parent().parent().parent().remove();
			$(obj).parent().parent().remove();
		},
		error: function(){
			alert("系统出错")
		}
	});
}

// 删除整条属性
function allTagRemove(obj,url)
{
	$specs_id = $(obj).attr("data-specs_id");
	$.post(url, {'extend_id':0,'specs_id':$specs_id},function(json){
		if (json.status == 1)
		$(obj).parent().parent().remove();
	}, "json");
}

function delOneSpecs(obj,url)
{
	if(confirm("确定要删除吗？，删除后相关产品属性将永久失效！"))
		var specs_id = obj.id;
	else
		return false;
	
	$.ajax({
		type: "POST",
		url: url,
		data : {'specs_id':specs_id},
		async: false,
		timeout: 5000,
		dataType: "JSON",
		success: function(json){
			if (json.status==0)
			{
				alert(json.info);
				return false;
			}
			$(obj).parent().parent().remove();
		},
		error: function(){
			alert("系统出错")
		}
	});
}

//选中事件
function choiceall(obj){
	var is_checked = $(obj).is(":checked");
	if (is_checked===true)
		$("input[name='lock_extend[]']:checkbox").attr("checked", true);
	else
		$("input[name='lock_extend[]']:checkbox").attr("checked", false);
}