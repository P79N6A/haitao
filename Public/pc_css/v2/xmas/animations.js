jQuery(function () {
	// IE6 doesn't support PNG with alpha channel
	if (jQuery.browser.msie && parseInt(jQuery.browser.version) < 7)
		return false;
	
	// Generate the stars
	var starMatrix = [
		{'left': '50%', 'marginLeft': -554, 'top': 0},
		{'left': '50%', 'marginLeft': -662, 'top': 0},
		{'left': '50%', 'marginLeft': -608, 'top': 59},
		{'left': '50%', 'marginLeft': -554, 'top': 118},
		{'left': '50%', 'marginLeft': -662, 'top': 118},
		{'left': '50%', 'marginLeft': -608, 'top': 177},
		{'left': '50%', 'marginLeft': -554, 'top': 236},
		{'left': '50%', 'marginLeft': -662, 'top': 236},
		{'left': '50%', 'marginLeft': 500, 'top': 0},
		{'left': '50%', 'marginLeft': 608, 'top': 0},
		{'left': '50%', 'marginLeft': 554, 'top': 59},
		{'left': '50%', 'marginLeft': 500, 'top': 118},
		{'left': '50%', 'marginLeft': 608, 'top': 118},
		{'left': '50%', 'marginLeft': 554, 'top': 177},
		{'left': '50%', 'marginLeft': 500, 'top': 236},
		{'left': '50%', 'marginLeft': 608, 'top': 236}
	];
	var i;
	var $sparklingStar = jQuery('<div class="sparklingStar"></div>');
	for (i = 0; i < starMatrix.length; ++i)
		$sparklingStar.clone().css(starMatrix[i]).appendTo('body');

	// Animate the stars
	var $stars = jQuery('.sparklingStar');
	(function() {
		var STARS_TO_SHOW = Math.ceil(Math.random() * $stars.length / 2);
		var selectedStarsIndex = [];
		var starIndex;

		$stars.hide();
		for (i = 0; selectedStarsIndex.length < STARS_TO_SHOW && i < 100; ++i) {
			starIndex = Math.floor(Math.random() * $stars.length);
			if (jQuery.inArray(starIndex, selectedStarsIndex) == -1)
				selectedStarsIndex.push(starIndex);
		}

		for (i = 0; i < selectedStarsIndex.length; ++i)
			$stars.eq(selectedStarsIndex[i]).show();

		setTimeout(arguments.callee, 500);
	})();
});