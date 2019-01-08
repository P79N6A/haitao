// JavaScript Document
  var checkAmount= $("span.check").length;
  function checkForm(){
	  var form = $("form")[0];
	  var msg="";
	  for (i=0;i<checkAmount;i++){
		  var title = $("span.check").eq(i).closest("tr").find("span.title").html();
		  var value = $("span.check").eq(i).closest("tr").find("input").val();
		      value = value.replace(/[ ]/g,""); 
		  if (value == ""){
			  msg += "请输入"+title+"        \n";
		  }
	  }
	  // 另外添加
	  if ($("input[name='endTime']").val() == ""){
		  msg += "请输入展会结束时间"+"        \n";
	  }
	  if (msg != ""){
		alert(msg);
		return false;
	  }else{
		commonAjaxSubmit();
	  }
  }