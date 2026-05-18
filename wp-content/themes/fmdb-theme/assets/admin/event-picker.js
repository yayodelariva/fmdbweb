(function ($) {
    function toggleBracket() {
        var val = $('input[name="event_type"]:checked').val();
        $('#fmdb_tournament_box').toggle(val === 'torneo');
    }

    function syncPillActive() {
        var val = $('input[name="event_type"]:checked').val();
        $('.fmdb-et-pill').each(function () {
            $(this).toggleClass('is-active', $(this).find('input').val() === val);
        });
        toggleBracket();
    }

    function validateEvent() {
        var ok = true;
        var $title = $('#title');
        var $date  = $('#EventStartDate');
        var $type  = $('#fmdb_event_type_box');

        // Title
        if (!$title.val().trim()) {
            $title.closest('#titlediv, #titlewrap, .fmdb-field-wrap').addClass('fmdb-field-error');
            $title.addClass('fmdb-field-error');
            ok = false;
        } else {
            $title.removeClass('fmdb-field-error');
        }

        // Start date
        if (!$date.val() || !$date.val().trim()) {
            $date.addClass('fmdb-field-error');
            ok = false;
        } else {
            $date.removeClass('fmdb-field-error');
        }

        // Event type (always has a default value so this won't block, but marks the box)
        var hasType = $('input[name="event_type"]:checked').length > 0;
        $type.toggleClass('fmdb-field-error', !hasType);

        return ok;
    }

    $(document).ready(function () {
        syncPillActive();
        $(document).on('change', 'input[name="event_type"]', syncPillActive);

        // Add helper messages
        $('#title').after('<p class="fmdb-required-msg">El título es obligatorio.</p>');
        $('#EventStartDate').closest('td, .tribe-timepicker').after('<p class="fmdb-required-msg">La fecha de inicio es obligatoria.</p>');

        // Intercept publish/update
        $('#publish, #save-post').on('click', function (e) {
            var $btn = $(this);
            var status = $('#post_status').val();
            // Only enforce on publish; allow draft saves
            if ($btn.attr('id') === 'save-post') return;
            if (status === 'draft' || status === 'pending') return;
            if (!validateEvent()) {
                e.preventDefault();
                $('html, body').animate({ scrollTop: 0 }, 200);
            }
        });
    });
}(jQuery));
