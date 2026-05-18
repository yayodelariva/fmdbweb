<?php
/**
 * Events (The Events Calendar) customizations.
 *
 *  - Default event categories: Torneo, Campamento, Misceláneo
 *  - Grant tribe_events caps to the Editor FMDB role
 *  - Required-field guard on publish (server-side + admin notice)
 *  - "Tipo de evento" color-pill metabox (replaces categories metabox)
 *  - Inline admin CSS/JS for picker, bracket visibility, and validation
 */

// Create the three event categories (Torneo, Campamento, Misceláneo)
add_action( 'init', function () {
    if ( ! taxonomy_exists( 'tribe_events_cat' ) ) return;
    $cats = [
        'torneo'     => 'Torneo',
        'campamento' => 'Campamento',
        'miscelaneo' => 'Misceláneo',
    ];
    foreach ( $cats as $slug => $name ) {
        if ( ! term_exists( $slug, 'tribe_events_cat' ) ) {
            wp_insert_term( $name, 'tribe_events_cat', [ 'slug' => $slug ] );
        }
    }
}, 20 );

// Grant TEC event capabilities to the Editor FMDB role
add_action( 'admin_init', function () {
    $role = get_role( 'editor_fmdb' );
    if ( ! $role || $role->has_cap( 'edit_tribe_events' ) ) return;

    $caps = [
        'edit_tribe_events', 'edit_others_tribe_events', 'edit_private_tribe_events',
        'edit_published_tribe_events', 'delete_tribe_events', 'delete_others_tribe_events',
        'delete_published_tribe_events', 'delete_private_tribe_events', 'publish_tribe_events',
        'read_private_tribe_events', 'edit_tribe_event', 'delete_tribe_event', 'read_tribe_event',
        'edit_tribe_venues', 'edit_others_tribe_venues', 'publish_tribe_venues',
        'edit_published_tribe_venues', 'delete_tribe_venues', 'edit_tribe_venue',
        'delete_tribe_venue', 'read_tribe_venue',
        'edit_tribe_organizers', 'edit_others_tribe_organizers', 'publish_tribe_organizers',
        'edit_published_tribe_organizers', 'delete_tribe_organizers', 'edit_tribe_organizer',
        'delete_tribe_organizer', 'read_tribe_organizer',
        'manage_categories',
    ];
    foreach ( $caps as $cap ) $role->add_cap( $cap );
} );

/* ===================================================================
 * Required-field enforcement for tribe_events
 * Required: post title, event_type, EventStartDate.
 * Server side: demotes to draft + stores error list in a transient.
 * Client side: highlights empty fields and blocks the publish button.
 * =================================================================== */

// Server-side: block publish if required fields are missing
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
    if ( $data['post_type'] !== 'tribe_events' ) return $data;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $data;
    if ( ! in_array( $data['post_status'], [ 'publish', 'future' ], true ) ) return $data;

    $missing = [];
    if ( empty( trim( $data['post_title'] ) ) || $data['post_title'] === __( 'Auto Draft' ) ) {
        $missing[] = 'Título';
    }
    $type = isset( $_POST['event_type'] ) ? sanitize_key( $_POST['event_type'] ) : '';
    if ( ! in_array( $type, [ 'torneo', 'campamento', 'miscelaneo' ], true ) ) {
        $missing[] = 'Tipo de evento';
    }
    if ( empty( trim( $_POST['EventStartDate'] ?? '' ) ) ) {
        $missing[] = 'Fecha de inicio';
    }

    if ( $missing ) {
        $data['post_status'] = 'draft';
        set_transient( 'fmdb_event_errors_' . get_current_user_id(), $missing, 60 );
    }
    return $data;
}, 10, 2 );

// Show the error notice after redirect back to the edit screen
add_action( 'admin_notices', function () {
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'tribe_events' ) return;
    $uid    = get_current_user_id();
    $errors = get_transient( 'fmdb_event_errors_' . $uid );
    if ( ! $errors ) return;
    delete_transient( 'fmdb_event_errors_' . $uid );
    $list = implode( ', ', array_map( 'esc_html', $errors ) );
    echo '<div class="notice notice-error is-dismissible"><p>';
    echo '<strong>El evento no puede publicarse.</strong> Completa los siguientes campos: ' . $list . '.';
    echo '</p></div>';
} );

// Hide noisy / redundant metaboxes from the tribe_events edit screen
add_action( 'add_meta_boxes', function () {
    foreach ( [ 'litespeed_meta_boxes', 'tec-events-qr-code', 'postimagediv', 'tribe_events_catdiv' ] as $id ) {
        remove_meta_box( $id, 'tribe_events', 'side' );
    }
}, 99 );

/* ===================================================================
 * Event type — merged color-pill selector (replaces separate "Categorías
 * de evento" metabox). Drives bracket visibility AND syncs to the
 * tribe_events_cat taxonomy so category pills on cards stay correct.
 * =================================================================== */
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'fmdb_event_type_box',
        __( 'Tipo de evento', 'fmdb' ),
        function ( $post ) {
            $current = get_post_meta( $post->ID, 'event_type', true );
            wp_nonce_field( 'fmdb_event_type_save', 'fmdb_event_type_nonce' );
            $types = [
                'torneo'     => [ 'label' => 'Torneo',     'color' => '#c0392b', 'bg' => '#fdecea' ],
                'campamento' => [ 'label' => 'Campamento', 'color' => '#2980b9', 'bg' => '#e8f2fa' ],
                'miscelaneo' => [ 'label' => 'Misceláneo', 'color' => '#7f8c8d', 'bg' => '#eef0f1' ],
            ];
            echo '<div class="fmdb-event-type-picker">';
            foreach ( $types as $val => $t ) {
                $checked = checked( $current, $val, false );
                printf(
                    '<label class="fmdb-et-pill%s" style="--et-color:%s;--et-bg:%s;">
                        <input type="radio" name="event_type" value="%s"%s>
                        <span>%s</span>
                    </label>',
                    $current === $val ? ' is-active' : '',
                    esc_attr( $t['color'] ),
                    esc_attr( $t['bg'] ),
                    esc_attr( $val ),
                    $checked,
                    esc_html( $t['label'] )
                );
            }
            echo '</div>';
        },
        'tribe_events',
        'side',
        'high'
    );
}, 98 );

// Save event_type and sync to tribe_events_cat taxonomy
add_action( 'save_post_tribe_events', function ( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! isset( $_POST['fmdb_event_type_nonce'] ) ||
         ! wp_verify_nonce( $_POST['fmdb_event_type_nonce'], 'fmdb_event_type_save' ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $allowed = [ 'torneo', 'campamento', 'miscelaneo' ];
    $type    = isset( $_POST['event_type'] ) ? sanitize_key( $_POST['event_type'] ) : '';

    if ( in_array( $type, $allowed, true ) ) {
        update_post_meta( $post_id, 'event_type', $type );
        $term = get_term_by( 'slug', $type, 'tribe_events_cat' );
        if ( $term ) {
            wp_set_object_terms( $post_id, [ $term->term_id ], 'tribe_events_cat' );
        }
    } else {
        delete_post_meta( $post_id, 'event_type' );
    }
}, 20 );

// Admin styles + JS for event type picker and bracket visibility
add_action( 'admin_footer', function () {
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'tribe_events' || $screen->base !== 'post' ) return;
    ?>
    <style>
    .fmdb-event-type-picker { display: flex; flex-direction: column; gap: 6px; padding: 2px 0; }
    .fmdb-et-pill { display: flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 6px; border: 2px solid transparent; cursor: pointer; font-size: 13px; font-weight: 500; color: var(--et-color); background: #fff; transition: background .15s, border-color .15s; }
    .fmdb-et-pill:hover { background: var(--et-bg); border-color: var(--et-color); }
    .fmdb-et-pill.is-active { background: var(--et-bg); border-color: var(--et-color); }
    .fmdb-et-pill input[type="radio"] { display: none; }
    .fmdb-et-pill::before { content: ''; width: 10px; height: 10px; border-radius: 50%; background: var(--et-color); flex-shrink: 0; }
    /* Required-field error highlight */
    .fmdb-field-error #title,
    .fmdb-field-error #EventStartDate { border-color: #d63638 !important; box-shadow: 0 0 0 1px #d63638 !important; }
    .fmdb-field-error #fmdb_event_type_box { border: 2px solid #d63638 !important; }
    .fmdb-required-msg { display: none; color: #d63638; font-size: 12px; margin-top: 4px; }
    .fmdb-field-error .fmdb-required-msg { display: block; }
    </style>
    <script>
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
            if ( ! $title.val().trim() ) {
                $title.closest('#titlediv, #titlewrap, .fmdb-field-wrap').addClass('fmdb-field-error');
                $title.addClass('fmdb-field-error');
                ok = false;
            } else {
                $title.removeClass('fmdb-field-error');
            }

            // Start date
            if ( ! $date.val() || ! $date.val().trim() ) {
                $date.addClass('fmdb-field-error');
                ok = false;
            } else {
                $date.removeClass('fmdb-field-error');
            }

            // Event type (always has a default value so this won't block, but marks the box)
            var hasType = $('input[name="event_type"]:checked').length > 0;
            $type.toggleClass('fmdb-field-error', ! hasType);

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
                if ( $btn.attr('id') === 'save-post' ) return;
                if ( status === 'draft' || status === 'pending' ) return;
                if ( ! validateEvent() ) {
                    e.preventDefault();
                    $('html, body').animate({ scrollTop: 0 }, 200);
                }
            });
        });
    }(jQuery));
    </script>
    <?php
} );
