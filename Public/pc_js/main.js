$(function(){

	// Price List
	$('.priceList').hover(function(){ $(this).addClass('hover'); }, function(){ $(this).removeClass('hover'); });

	// Login Form
	$('#toggleLoginForm').click(function(){
		$(this).next('#loginForm').toggle();
		return false;
	});

	// Search
	var $searchBar = $('.searchBar');
	var $searchInput = $('.searchBar .search .searchInput');
	var $searchOptions = $('.searchBar .options');
	$searchInput.click(function(e){ e.stopPropagation(); });
	$searchInput.focusin(function(){ $searchBar.addClass('focused'); pre_search(); setTimeout(function(){$searchInput.select();}, 100); });
	$searchInput.focusout(function(){if (!$searchOptions.hasClass('over') && !$searchBar.hasClass('over')) $searchBar.removeClass('focused');});
	$searchBar.hover(
		function(/*in*/){$(this).addClass('over');},
		function(/*out*/){$(this).removeClass('over'); if(!$searchInput.is(":focus") && !$searchOptions.hasClass('over')) $searchBar.removeClass('focused');}
	);
	$searchOptions.hover(
		function(/*in*/){$(this).addClass('over'); $searchBar.addClass('focused');},
		function(/*out*/){$(this).removeClass('over'); if(!$searchInput.is(":focus") && !$searchBar.hasClass('over')) $searchBar.removeClass('focused');}
	);
	$('.searchBar .search').click(function(){ $('.searchBar .searchbutton .submit').click(); });
	$searchInput.keyup(function()
	{
		clearTimeout($.data(this, 'timer'));
		$(this).data('timer', setTimeout(pre_search, 500));
	});
	$('.searchBar .options .option').click(function()
	{
		// Visual effects
		if (!$(this).hasClass('multiple') && !$(this).hasClass('selected'))
			$('.searchBar .options .option[option="'+$(this).attr('option')+'"]').removeClass('selected');
		$(this).toggleClass('selected');

		// Fill form
		var value = '';
		var option = $(this).attr('option');
		if ($(this).hasClass('multiple'))
		{
			$('.searchBar .options .option.selected[option="'+option+'"]').each(function(){
				value += (value.length ? ',' : '') + $(this).attr('value');
			});
		}
		else if ($(this).hasClass('selected'))
		{
			value = $(this).attr('value');
		}
		$('.searchBar .searchForm input.'+option).val(value);
		pre_search();
	});

        $('.searchBar .resetButton').click(function(e) {
           $searchInput.val('');
           $('.searchBar .options .option.selected').trigger('click');
           pre_search();
           return false;
        });
});
