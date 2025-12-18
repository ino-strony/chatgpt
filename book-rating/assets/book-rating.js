(function ($) {
	'use strict';

	function updateTexts($container, payload) {
		if (payload.averageText) {
			$container.find('.bsr-rating__average').text(payload.averageText);
		}

		if (payload.userText) {
			$container.find('.bsr-rating__user-current').text(payload.userText);
		}
	}

	$(document).on('submit', '.bsr-rating-form', function (event) {
		event.preventDefault();

		var $form = $(this);
		var $container = $form.closest('.bsr-rating');
		var selected = $form.find('input[name="bsr_rating"]:checked').val();
		var rating = parseInt(selected, 10);

		if (isNaN(rating)) {
			rating = 0;
		}

		$container.addClass('bsr-rating--loading');
		$form.find('.bsr-rating__message').text(bsrRating.strings.saving);

		$.post(
			bsrRating.ajaxUrl,
			{
				action: 'bsr_save_rating',
				postId: $form.data('postId'),
				rating: rating,
				nonce: $form.data('nonce'),
			},
			function (response) {
				$container.removeClass('bsr-rating--loading');

				if (response && response.success) {
					updateTexts($container, response.data);
					$form.find('.bsr-rating__message').text(bsrRating.strings.success);
				} else {
					$form
						.find('.bsr-rating__message')
						.text((response && response.data && response.data.message) || bsrRating.strings.error);
				}
			}
		).fail(function () {
			$container.removeClass('bsr-rating--loading');
			$form.find('.bsr-rating__message').text(bsrRating.strings.error);
		});
	});
})(jQuery);
