(function ($) {
    function toggleItem($button, expand) {
        var $icon = $button.find('.cqa-icon');
        var $answer = $('#' + $button.attr('aria-controls'));
        var $item = $button.closest('.cqa-item');

        if (expand) {
            $button.attr('aria-expanded', 'true');
            $answer.removeAttr('hidden');
            $item.addClass('is-open');
            $icon.text('−');
        } else {
            $button.attr('aria-expanded', 'false');
            $answer.attr('hidden', 'hidden');
            $item.removeClass('is-open');
            $icon.text('+');
        }
    }

    $(document).on('click', '.cqa-question', function (event) {
        event.preventDefault();
        var $button = $(this);
        var isExpanded = $button.attr('aria-expanded') === 'true';
        toggleItem($button, !isExpanded);
    });
})(jQuery);
