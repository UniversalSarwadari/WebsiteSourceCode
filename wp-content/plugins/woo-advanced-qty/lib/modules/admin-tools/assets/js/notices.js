(function($) {
	$(document).ready(function() {
		$('body').on('click', '.notice-dismiss', function() {
			var message = $(this).parents('.notice').attr('id');
			var action = 'dismiss_notice_' + message;
			$.ajax(ajaxurl, {'data': {'action': action, 'message': message}});
		})
	});
})(jQuery);