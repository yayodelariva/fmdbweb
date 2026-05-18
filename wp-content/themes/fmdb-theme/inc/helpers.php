<?php
/**
 * Shared theme helpers used by templates and other inc/ files.
 */

function fmdb_mexican_states() {
    return [
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche',
        'Chiapas', 'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima',
        'Durango', 'Estado de México', 'Guanajuato', 'Guerrero', 'Hidalgo',
        'Jalisco', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca',
        'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa',
        'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz',
        'Yucatán', 'Zacatecas',
    ];
}

// Helper: format event start/end timestamps into a compact date-badge tuple
function fmdb_event_date_parts( $start_ts, $end_ts ) {
    $single = ! $end_ts
        || $end_ts === $start_ts
        || date( 'Y-m-d', $start_ts ) === date( 'Y-m-d', $end_ts );

    if ( $single ) {
        return [
            'day'      => date_i18n( 'j', $start_ts ),
            'month'    => strtoupper( date_i18n( 'M', $start_ts ) ),
            'year'     => date_i18n( 'Y', $start_ts ),
            'is_range' => false,
        ];
    }

    $same_month = date( 'Y-m', $start_ts ) === date( 'Y-m', $end_ts );
    return [
        'day'      => date_i18n( 'j', $start_ts ) . '-' . date_i18n( 'j', $end_ts ),
        'month'    => $same_month
            ? strtoupper( date_i18n( 'M', $start_ts ) )
            : strtoupper( date_i18n( 'M', $start_ts ) ) . '-' . strtoupper( date_i18n( 'M', $end_ts ) ),
        'year'     => date_i18n( 'Y', $start_ts ),
        'is_range' => true,
    ];
}

/**
 * Render a CMB2 box as a front-end form (for /mi-equipo/ panels).
 * Wraps cmb2_get_metabox_form() so we can pass our own submit label and
 * a hidden field telling the page-template which box to save on POST.
 */
function fmdb_render_cmb2_form( $box_id, $object_id, $tab_slug, $submit_label ) {
    if ( ! function_exists( 'cmb2_get_metabox_form' ) ) {
        echo '<p>CMB2 no disponible.</p>';
        return;
    }
    echo cmb2_get_metabox_form( $box_id, $object_id, [
        'form_format' => '<form class="cmb-form fmdb-cmb-form" method="post" id="%1$s" enctype="multipart/form-data" encoding="multipart/form-data">'
            . '<input type="hidden" name="object_id" value="%2$s">'
            . '<input type="hidden" name="fmdb_cmb_box" value="' . esc_attr( $box_id ) . '">'
            . '<input type="hidden" name="fmdb_active_tab" value="' . esc_attr( $tab_slug ) . '">'
            . '%3$s'
            . '<input type="submit" name="submit-cmb" value="' . esc_attr( $submit_label ) . '" class="fmdb-btn fmdb-btn--primary fmdb-form-submit">'
            . '</form>',
    ] );
}

// True for administrators and Editor FMDB — can manage any team
function fmdb_is_team_manager() {
    $roles = (array) wp_get_current_user()->roles;
    return current_user_can( 'manage_options' ) || in_array( 'editor_fmdb', $roles, true );
}

// Render a player avatar — linked WP user photo if available, initials fallback
function fmdb_player_avatar( $user_id, $fallback_name, $size = 'thumbnail' ) {
    $pic_id  = $user_id ? get_user_meta( (int) $user_id, 'fmdb_profile_picture', true ) : 0;
    $pic_url = $pic_id ? wp_get_attachment_image_url( (int) $pic_id, $size ) : '';
    if ( $pic_url ) {
        return '<img src="' . esc_url( $pic_url ) . '" alt="' . esc_attr( $fallback_name ) . '" class="fmdb-player-avatar">';
    }
    $words    = array_filter( explode( ' ', trim( $fallback_name ) ) );
    $initials = $words ? substr( implode( '', array_map( fn( $w ) => strtoupper( $w[0] ), $words ) ), 0, 2 ) : '?';
    return '<span class="fmdb-player-avatar fmdb-player-avatar--initials">' . esc_html( $initials ) . '</span>';
}
