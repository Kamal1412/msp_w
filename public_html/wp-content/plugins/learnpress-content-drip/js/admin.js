;(function ($) {
	var $doc = $(document);

	function isValidateDate(date) {
		var d = new Date(date);
		return !isNaN(d.getYear());
	}

	function _init_meta_box() {
		var $select = $('#_lp_drip_content_date_become_available'),
			$date = $('input[name="_lp_drip_content_specific_date"]');
		$select.bind('change update', function () {
			if ($select.val() == 'interval') {
				$('.content-drip-interval').show().siblings('.content-drip-specific-date').hide();
			} else if ($select.val() == 'specific_date') {
				$('.content-drip-specific-date').show().siblings('.content-drip-interval').hide();
			} else {
				$('.content-drip-interval, .content-drip-specific-date').hide();
			}
		}).trigger('update');
		$date.attr('readonly', true).attr('size', 15);
		$('#post').submit(function () {
			if ($select.val() == 'specific_date' && !isValidateDate($date.val())) {
				alert('error');
				$date.focus();
				return false;
			}
		});
	}

	function _ready() {
		_init_meta_box();
	}

	$doc.ready(_ready);
})(jQuery);
