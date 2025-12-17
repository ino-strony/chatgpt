(function ($) {
    function toggleItem($button, expand) {
        var $icon = $button.find('.sqa-icon');
        var $answer = $('#' + $button.attr('aria-controls'));

        if (expand) {
            $button.attr('aria-expanded', 'true');
            $answer.removeAttr('hidden');
            $icon.text('−');
        } else {
            $button.attr('aria-expanded', 'false');
            $answer.attr('hidden', 'hidden');
            $icon.text('+');
        }
    }

    $(document).on('click', '.sqa-question', function (event) {
        event.preventDefault();
        var $button = $(this);
        var isExpanded = $button.attr('aria-expanded') === 'true';
        toggleItem($button, !isExpanded);
    });
})(jQuery);
