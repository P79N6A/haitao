var OFFSET = 5;
var page = 1;
var PAGESIZE = 10;

var myScroll,
	pullDownEl, pullDownOffset,
	pullUpEl, pullUpOffset,
	generatedCount = 0;
var maxScrollY = 0;

var hasMoreData = false;

document.addEventListener('touchmove', function(e) {
	e.preventDefault();
}, false);

document.addEventListener('DOMContentLoaded', function() {
	$(document).ready(function() {
		loaded();
	});
}, false);

function loaded() {
	pullDownEl = document.getElementById('pullDown');
	pullDownOffset = pullDownEl.offsetHeight;
	pullUpEl = document.getElementById('pullUp');
	pullUpOffset = pullUpEl.offsetHeight;

	hasMoreData = false;
	// $("#thelist").hide();
	$("#pullUp").hide();

	pullDownEl.className = 'loading';
	pullDownEl.querySelector('.pullDownLabel').innerHTML = 'Loading...';

	page = 1;
	$.post(
		"/index.php?m=Index&a=getitem", {
			"page": page,
			"pagesize": PAGESIZE
		},
		function(response, status) {
			if (status == "success") {
				$("#thelist").show();

				if (response.list.length < PAGESIZE) {
					hasMoreData = false;
					$("#pullUp").hide();
				} else {
					hasMoreData = true;
					$("#pullUp").show();
				}

				// document.getElementById('wrapper').style.left = '0';

				myScroll = new iScroll('wrapper', {
					useTransition: true,
					topOffset: pullDownOffset,
					onRefresh: function() {
						if (pullDownEl.className.match('loading')) {
							pullDownEl.className = 'idle';
							pullDownEl.querySelector('.pullDownLabel').innerHTML = 'Pull down to refresh...';
							this.minScrollY = -pullDownOffset;
						}
						if (pullUpEl.className.match('loading')) {
							pullUpEl.className = 'idle';
							pullUpEl.querySelector('.pullUpLabel').innerHTML = 'Pull up to load more...';
						}
					},
					onScrollMove: function() {
						if (this.y > OFFSET && !pullDownEl.className.match('flip')) {
							pullDownEl.className = 'flip';
							pullDownEl.querySelector('.pullDownLabel').innerHTML = 'Release to refresh...';
							this.minScrollY = 0;
						} else if (this.y < OFFSET && pullDownEl.className.match('flip')) {
							pullDownEl.className = 'idle';
							pullDownEl.querySelector('.pullDownLabel').innerHTML = 'Pull down to refresh...';
							this.minScrollY = -pullDownOffset;
						} 
						if (this.y < (maxScrollY - pullUpOffset - OFFSET) && !pullUpEl.className.match('flip')) {
							if (hasMoreData) {
								this.maxScrollY = this.maxScrollY - pullUpOffset;
								pullUpEl.className = 'flip';
								pullUpEl.querySelector('.pullUpLabel').innerHTML = 'Release to refresh...';
							}
						} else if (this.y > (maxScrollY - pullUpOffset - OFFSET) && pullUpEl.className.match('flip')) {
							if (hasMoreData) {
								this.maxScrollY = maxScrollY;
								pullUpEl.className = 'idle';
								pullUpEl.querySelector('.pullUpLabel').innerHTML = 'Pull up to load more...';
							}
						}
					},
					onScrollEnd: function() {
						if (pullDownEl.className.match('flip')) {
							pullDownEl.className = 'loading';
							pullDownEl.querySelector('.pullDownLabel').innerHTML = 'Loading...';
							// pullDownAction(); // Execute custom function (ajax call?)
							refresh();
						}
						if (hasMoreData && pullUpEl.className.match('flip')) {
							pullUpEl.className = 'loading';
							pullUpEl.querySelector('.pullUpLabel').innerHTML = 'Loading...';
							// pullUpAction(); // Execute custom function (ajax call?)
							nextPage();
						}
					}
				});

				$("#thelist").empty();
				$.each(response.list, function(key, value) {
					var brand_html = '';
					brand_html += '<div class="u-brand"><div class="p-relative">';
					brand_html += '<a href="'+ value.this_url +'" class="u-brand-pic">';
					brand_html += '<img src="'+ value.pic +'" class="lazy"></a></div>';
					brand_html += '<div class="u-brand-msg e-border-b"><p class="u-brand-name">';
					brand_html += '<span class="u-brand-discount">'+ value.description +'</span>'+ value.title +'';
					brand_html += '</p></div></div>';
					// $("#thelist").append('<li>' + value.name + '\t' + value.time + '</li>');
					$("#thelist").append(brand_html);
				});
				// $("#thelist").listview("refresh");
				myScroll.refresh(); // Remember to refresh when contents are loaded (ie: on ajax completion)
				// pullDownEl.className = 'idle';
				// pullDownEl.querySelector('.pullDownLabel').innerHTML = 'Pull down to refresh...';
				// this.minScrollY = -pullDownOffset;

				if (hasMoreData) {
					myScroll.maxScrollY = myScroll.maxScrollY + pullUpOffset;
				} else {
					myScroll.maxScrollY = myScroll.maxScrollY;
				}
				maxScrollY = myScroll.maxScrollY;
			};
		},
		"json");
}

function refresh() {
	page = 1;
	$.post(
		"/index.php?m=Index&a=getitem", {
			"page": page,
			"pagesize": PAGESIZE
		},
		function(response, status) {
			if (status == "success") {
				$("#thelist").empty();

				myScroll.refresh();

				if (response.list.length < PAGESIZE) {
					hasMoreData = false;
					$("#pullUp").hide();
				} else {
					hasMoreData = true;
					$("#pullUp").show();
				}

				$.each(response.list, function(key, value) {
					// $("#thelist").append('<li>' + value.name + '\t' + value.time + '</li>');
					var brand_html = '';
					brand_html += '<div class="u-brand"><div class="p-relative">';
					brand_html += '<a href="'+ value.this_url +'" class="u-brand-pic">';
					brand_html += '<img src="'+ value.pic +'" class="lazy"></a></div>';
					brand_html += '<div class="u-brand-msg e-border-b"><p class="u-brand-name">';
					brand_html += '<span class="u-brand-discount">'+ value.description +'</span>'+ value.title +'';
					brand_html += '</p></div></div>';
					// $("#thelist").append('<li>' + value.name + '\t' + value.time + '</li>');
					$("#thelist").append(brand_html);
				});
				// $("#thelist").listview("refresh");
				myScroll.refresh(); // Remember to refresh when contents are loaded (ie: on ajax completion)

				if (hasMoreData) {
					myScroll.maxScrollY = myScroll.maxScrollY + pullUpOffset;
				} else {
					myScroll.maxScrollY = myScroll.maxScrollY;
				}
				maxScrollY = myScroll.maxScrollY;
			};
		},
		"json");
}

function nextPage() {
	page++;
	$.post(
		"/index.php?m=Index&a=getitem", {
			"page": page,
			"pagesize": PAGESIZE
		},
		function(response, status) {
			if (status == "success") {
				if (response.list.length < PAGESIZE) {
					hasMoreData = false;
					$("#pullUp").hide();
				} else {
					hasMoreData = true;
					$("#pullUp").show();
				}

				$.each(response.list, function(key, value) {
					// $("#thelist").append('<li>' + value.name + '\t' + value.time + '</li>');
					var brand_html = '';
					brand_html += '<div class="u-brand"><div class="p-relative">';
					brand_html += '<a href="'+ value.this_url +'" class="u-brand-pic">';
					brand_html += '<img src="'+ value.pic +'" class="lazy"></a></div>';
					brand_html += '<div class="u-brand-msg e-border-b"><p class="u-brand-name">';
					brand_html += '<span class="u-brand-discount">'+ value.description +'</span>'+ value.title +'';
					brand_html += '</p></div></div>';
					// $("#thelist").append('<li>' + value.name + '\t' + value.time + '</li>');
					$("#thelist").append(brand_html);
				});
				// $("#thelist").listview("refresh");
				myScroll.refresh(); // Remember to refresh when contents are loaded (ie: on ajax completion)
				if (hasMoreData) {
					myScroll.maxScrollY = myScroll.maxScrollY + pullUpOffset;
				} else {
					myScroll.maxScrollY = myScroll.maxScrollY;
				}
				maxScrollY = myScroll.maxScrollY;
			};
		},
		"json");
}