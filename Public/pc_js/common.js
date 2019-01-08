/*展开导航栏*/
function shouwbox(obj) {
	var id = $(obj).attr("id");
	var index = $(".expandedNav").find(".shopCategory");
	var c = $(".box_"+id);
	var openeds = $(".expandedNav").find('.opened'+id);
	$(index).each(function(e,val){
		var ids = $(val).attr("id");
		var opened = $(val).find('.opened'+ids);
		if (ids == id)
		{
			if (!$(openeds).length)
			{
				$(c).slideDown('normal');
				$(c).addClass('opened'+ids);
			}
			else
			{
				$(c).slideUp('normal');
				$(c).removeClass('opened'+ids);
			}
		}
		else
		{
			$(val).slideUp('normal');
			$(val).removeClass('opened'+ids);
		}
	});
}
