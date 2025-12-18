(function ($) {
    function toggleItem($button, expand) {
        var $icon = $button.find('.sqa-icon');
        var $answer = $('#' + $button.attr('aria-controls'));
        var $item = $button.closest('.sqa-item');

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

    $(document).on('click', '.sqa-question', function (event) {
        event.preventDefault();
        var $button = $(this);
        var isExpanded = $button.attr('aria-expanded') === 'true';
        var $list = $button.closest('.sqa-list');

        $list.find('.sqa-question').not($button).each(function () {
            toggleItem($(this), false);
        });

        toggleItem($button, !isExpanded);
    });
})(jQuery);
