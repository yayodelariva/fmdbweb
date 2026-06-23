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

// Fix TEC Spanish translations
add_filter( 'gettext_the-events-calendar', function ( $translation, $text ) {
    if ( $text === '%s Cost' ) return 'Costo del %s';
    return $translation;
}, 20, 2 );

// Create the three event categories (Torneo, Campamento, Misceláneo)
add_action( 'init', function () {
    if ( ! taxonomy_exists( 'tribe_events_cat' ) ) return;
    $cats = [
        'torneo'        => 'Torneo',
        'liga'          => 'Liga',
        'campamento'    => 'Campamento',
        'entrenamiento' => 'Entrenamiento',
        'anuncio'       => 'Anuncio',
        'miscelaneo'    => 'Misceláneo',
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
    if ( ! in_array( $type, [ 'torneo', 'liga', 'campamento', 'entrenamiento', 'anuncio', 'miscelaneo' ], true ) ) {
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
    remove_meta_box( 'tribe_events_event_options', 'tribe_events', 'side' );
    remove_meta_box( 'tagsdiv-post_tag', 'tribe_events', 'side' );
    remove_meta_box( 'tribe-events-status', 'tribe_events', 'side' );
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
                'torneo'        => [ 'label' => 'Torneo',        'color' => '#c0392b', 'bg' => '#fdecea' ],
                'liga'          => [ 'label' => 'Liga',          'color' => '#27ae60', 'bg' => '#e8f7ee' ],
                'campamento'    => [ 'label' => 'Campamento',    'color' => '#2980b9', 'bg' => '#e8f2fa' ],
                'entrenamiento' => [ 'label' => 'Entrenamiento', 'color' => '#8e44ad', 'bg' => '#f3e9f7' ],
                'anuncio'       => [ 'label' => 'Anuncio',       'color' => '#d35400', 'bg' => '#fdece0' ],
                'miscelaneo'    => [ 'label' => 'Misceláneo',    'color' => '#7f8c8d', 'bg' => '#eef0f1' ],
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

    $allowed = [ 'torneo', 'liga', 'campamento', 'entrenamiento', 'anuncio', 'miscelaneo' ];
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

// Enqueue admin styles + JS only on the tribe_events edit screen
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'tribe_events' ) return;

    $base = get_stylesheet_directory_uri() . '/assets/admin/';
    $dir  = get_stylesheet_directory() . '/assets/admin/';

    wp_enqueue_style(
        'fmdb-event-picker',
        $base . 'event-picker.css',
        [],
        file_exists( $dir . 'event-picker.css' ) ? (string) filemtime( $dir . 'event-picker.css' ) : wp_get_theme()->get( 'Version' )
    );
    wp_enqueue_script(
        'fmdb-event-picker',
        $base . 'event-picker.js',
        [ 'jquery' ],
        file_exists( $dir . 'event-picker.js' ) ? (string) filemtime( $dir . 'event-picker.js' ) : wp_get_theme()->get( 'Version' ),
        true
    );
} );
