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
 * Expand a tribe_events post into one or more occurrences.
 *
 * Returns an ordered list of date occurrences for the event:
 *   - one entry for the primary _EventStartDate/_EventEndDate range
 *   - one extra entry per row in the `event_extra_dates` CMB2 repeater
 *
 * Each occurrence: [
 *   'post_id'    => int,
 *   'date'       => 'Y-m-d',
 *   'start_ts'   => int,
 *   'end_ts'     => int,
 *   'note'       => string,
 *   'is_primary' => bool,
 *   'index'      => int,   // 0 = primary, 1..N = extras (in repeater order)
 * ]
 *
 * Extra dates inherit the primary event's time-of-day when start_time is
 * blank, and inherit the primary's duration when end_time is blank.
 * Results are sorted ascending by start_ts.
 */
function fmdb_get_event_occurrences( $post_id ) {
    $start_raw = get_post_meta( $post_id, '_EventStartDate', true );
    if ( ! $start_raw ) return [];

    $end_raw          = get_post_meta( $post_id, '_EventEndDate', true );
    $primary_start_ts = strtotime( $start_raw );
    $primary_end_ts   = $end_raw ? strtotime( $end_raw ) : $primary_start_ts;
    $primary_duration = max( 0, $primary_end_ts - $primary_start_ts );
    $primary_time     = date( 'H:i:s', $primary_start_ts );

    $occurrences = [];
    $occurrences[] = [
        'post_id'    => (int) $post_id,
        'date'       => date( 'Y-m-d', $primary_start_ts ),
        'start_ts'   => $primary_start_ts,
        'end_ts'     => $primary_end_ts,
        'note'       => '',
        'is_primary' => true,
        'index'      => 0,
    ];

    $extras = get_post_meta( $post_id, 'event_extra_dates', true );
    if ( is_array( $extras ) ) {
        $i = 1;
        foreach ( $extras as $row ) {
            $d = isset( $row['date'] ) ? trim( $row['date'] ) : '';
            if ( ! $d ) continue;

            $start_t = ! empty( $row['start_time'] )
                ? date( 'H:i:s', strtotime( $row['start_time'] ) )
                : $primary_time;
            $occ_start = strtotime( $d . ' ' . $start_t );
            if ( ! $occ_start ) continue;

            if ( ! empty( $row['end_time'] ) ) {
                $occ_end = strtotime( $d . ' ' . date( 'H:i:s', strtotime( $row['end_time'] ) ) );
                if ( ! $occ_end ) $occ_end = $occ_start;
            } else {
                $occ_end = $occ_start + $primary_duration;
            }

            $occurrences[] = [
                'post_id'    => (int) $post_id,
                'date'       => date( 'Y-m-d', $occ_start ),
                'start_ts'   => $occ_start,
                'end_ts'     => $occ_end,
                'note'       => isset( $row['note'] ) ? trim( $row['note'] ) : '',
                'is_primary' => false,
                'index'      => $i,
            ];
            $i++;
        }
    }

    usort( $occurrences, function ( $a, $b ) {
        return $a['start_ts'] <=> $b['start_ts'];
    } );

    return $occurrences;
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

/**
 * Render an organigrama "people grid" page (Consejo Directivo, Comisiones,
 * Asociaciones). Must be called inside the WP loop — uses the_title() and
 * the_content() so each page's wp-admin content overrides the lede fallback.
 *
 * $args:
 *   - lede          string  Fallback intro shown when the page has no content
 *   - empty_message string  Shown when ACF has no members and no placeholders
 *   - placeholders  array   Member rows used until ACF has real data
 */
function fmdb_render_org_page( array $args ) {
    $lede          = $args['lede']          ?? '';
    $empty_message = $args['empty_message'] ?? 'Los miembros se publicarán próximamente.';
    $placeholders  = $args['placeholders']  ?? [];

    $members = get_post_meta( get_the_ID(), 'org_members', true );
    if ( ! is_array( $members ) ) $members = [];
    if ( empty( $members ) ) $members = $placeholders;
    ?>
    <main class="fmdb-org">
        <div class="fmdb-org__wrap">

            <header class="fmdb-org__header">
                <h1 class="fmdb-org__title"><?php the_title(); ?></h1>
                <?php $content = trim( wp_strip_all_tags( get_the_content() ) ); ?>
                <?php if ( $content ) : ?>
                    <div class="fmdb-org__intro"><?php the_content(); ?></div>
                <?php elseif ( $lede ) : ?>
                    <p class="fmdb-org__lede"><?php echo esc_html( $lede ); ?></p>
                <?php endif; ?>
            </header>

            <?php if ( ! empty( $members ) ) : ?>
                <div class="fmdb-org__grid">
                    <?php foreach ( $members as $m ) :
                        $name      = $m['member_name']     ?? '';
                        $position  = $m['member_position'] ?? '';
                        $bio       = $m['member_bio']      ?? '';
                        $photo     = $m['member_photo']    ?? null;
                        $photo_id  = isset( $m['member_photo_id'] ) ? (int) $m['member_photo_id'] : 0;
                        $photo_url = '';
                        $photo_alt = $name;
                        if ( $photo_id ) {
                            $photo_url = wp_get_attachment_image_url( $photo_id, 'medium_large' );
                            $photo_alt = get_post_meta( $photo_id, '_wp_attachment_image_alt', true ) ?: $name;
                        } elseif ( is_array( $photo ) ) {
                            $photo_url = $photo['sizes']['medium_large'] ?? ( $photo['sizes']['medium'] ?? ( $photo['url'] ?? '' ) );
                            $photo_alt = $photo['alt'] ?: $name;
                        } elseif ( is_numeric( $photo ) ) {
                            $photo_url = wp_get_attachment_image_url( (int) $photo, 'medium_large' );
                        } elseif ( is_string( $photo ) && $photo !== '' ) {
                            $photo_url = $photo;
                        }
                        if ( ! $name ) continue;
                    ?>
                        <article class="fmdb-org-card">
                            <div class="fmdb-org-card__photo">
                                <?php if ( $photo_url ) : ?>
                                    <img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( $photo_alt ); ?>" loading="lazy">
                                <?php else :
                                    $words    = array_filter( explode( ' ', trim( $name ) ) );
                                    $initials = $words ? substr( implode( '', array_map( fn( $w ) => strtoupper( $w[0] ), $words ) ), 0, 2 ) : '?';
                                ?>
                                    <div class="fmdb-org-card__initials"><?php echo esc_html( $initials ); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="fmdb-org-card__body">
                                <?php if ( $position ) : ?>
                                    <span class="fmdb-org-card__position"><?php echo esc_html( $position ); ?></span>
                                <?php endif; ?>
                                <h3 class="fmdb-org-card__name"><?php echo esc_html( $name ); ?></h3>
                                <?php if ( $bio ) : ?>
                                    <p class="fmdb-org-card__bio"><?php echo esc_html( $bio ); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="fmdb-org__empty">
                    <p><?php echo esc_html( $empty_message ); ?></p>
                </div>
            <?php endif; ?>

        </div>
    </main>
    <?php
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
