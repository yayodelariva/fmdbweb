<?php
/**
 * Template: Mi Equipo (Representative Dashboard)
 * Automatically used for the page with slug "mi-equipo"
 */

// ACF form processing must run before get_header()
if ( function_exists( 'acf_form_head' ) ) {
    acf_form_head();
}

// Hide Alcaldía/Municipio from the team-info form on this page only.
add_filter( 'acf/prepare_field/key=field_team_city', '__return_false' );

if ( ! is_user_logged_in() ) {
    wp_safe_redirect( wp_login_url( home_url( '/mi-equipo/' ) ) );
    exit;
}

// Process CMB2 front-end form submissions before output.
if ( ! empty( $_POST['object_id'] ) && ! empty( $_POST['fmdb_cmb_box'] ) ) {
    $box_id    = sanitize_key( $_POST['fmdb_cmb_box'] );
    $object_id = (int) $_POST['object_id'];
    $reps      = get_field( 'team_rep', $object_id );
    if ( ! is_array( $reps ) ) $reps = $reps ? [ $reps ] : [];
    $is_auth   = in_array( get_current_user_id(), array_map( 'intval', $reps ), true )
                 || fmdb_is_team_manager();
    if ( $is_auth && function_exists( 'cmb2_get_metabox' ) ) {
        $cmb = cmb2_get_metabox( $box_id, $object_id );
        if ( $cmb && isset( $_POST[ $cmb->nonce() ] ) && wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() ) ) {
            $cmb->save_fields( $object_id, 'post', $_POST );

            // Resolve user_email → linked_user_id for each roster entry
            if ( $box_id === 'fmdb_team_roster_box' ) {
                $roster = get_post_meta( $object_id, 'team_roster', true );
                if ( is_array( $roster ) ) {
                    foreach ( $roster as &$entry ) {
                        $email = isset( $entry['user_email'] ) ? sanitize_email( $entry['user_email'] ) : '';
                        if ( $email ) {
                            $u = get_user_by( 'email', $email );
                            $entry['linked_user_id'] = $u ? $u->ID : 0;
                        } else {
                            $entry['linked_user_id'] = 0;
                        }
                    }
                    unset( $entry );
                    update_post_meta( $object_id, 'team_roster', $roster );
                }
            }

            wp_safe_redirect( add_query_arg( [ 'saved' => '1', 'tab' => sanitize_key( $_POST['fmdb_active_tab'] ?? '' ) ], home_url( '/mi-equipo/' ) ) );
            exit;
        }
    }
}

// Process plantel (registered-players-only roster form)
if ( isset( $_POST['fmdb_plantel_nonce'] ) && wp_verify_nonce( $_POST['fmdb_plantel_nonce'], 'fmdb_plantel_save' ) ) {
    $team_id = (int) ( $_POST['fmdb_plantel_team_id'] ?? 0 );
    if ( $team_id && get_post_type( $team_id ) === 'fmdb_team' ) {
        $reps     = get_field( 'team_rep', $team_id );
        if ( ! is_array( $reps ) ) $reps = $reps ? [ $reps ] : [];
        $can_edit = in_array( get_current_user_id(), array_map( 'intval', $reps ), true )
                    || fmdb_is_team_manager();
        if ( $can_edit ) {
            $roster  = [];
            $entries = is_array( $_POST['roster'] ?? null ) ? $_POST['roster'] : [];
            foreach ( $entries as $entry ) {
                $uid = (int) ( $entry['user_id'] ?? 0 );
                if ( ! $uid || ! get_userdata( $uid ) ) continue;
                $roster[] = [
                    'user_id'    => $uid,
                    'number'     => sanitize_text_field( $entry['number'] ?? '' ),
                    'position'   => sanitize_text_field( $entry['position'] ?? '' ),
                    'role'       => in_array( $entry['role'] ?? '', [ 'Titular', 'Suplente' ], true ) ? $entry['role'] : 'Titular',
                    'is_captain' => ! empty( $entry['is_captain'] ),
                ];
            }
            update_post_meta( $team_id, 'team_roster', $roster );
            wp_safe_redirect( add_query_arg( [ 'saved' => '1', 'tab' => 'plantel' ], home_url( '/mi-equipo/' ) ) );
            exit;
        }
    }
}

get_header();

$current_user = wp_get_current_user();
if ( fmdb_is_team_manager() ) {
    $team_posts = get_posts( [
        'post_type'      => 'fmdb_team',
        'posts_per_page' => -1,
        'post_status'    => [ 'publish', 'pending', 'draft' ],
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );
} else {
    $team_posts = get_posts( [
        'post_type'      => 'fmdb_team',
        'posts_per_page' => -1,
        'post_status'    => [ 'publish', 'pending', 'draft' ],
        'meta_query'     => [
            'relation' => 'OR',
            [ 'key' => 'team_rep', 'value' => '"' . $current_user->ID . '"', 'compare' => 'LIKE' ],
            [ 'key' => 'team_rep', 'value' => $current_user->ID, 'compare' => '=' ],
        ],
    ] );
}

if ( empty( $team_posts ) ) :
?>
    <main class="fmdb-dashboard">
        <div class="fmdb-dashboard__no-team">
            <div class="fmdb-dashboard__no-team-icon">?</div>
            <h1>Mi Equipo</h1>
            <p>No tienes un equipo asignado todavía.<br>Contacta al administrador de FMDB para que te asignen tu equipo.</p>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="fmdb-btn fmdb-btn--primary">Volver al inicio</a>
        </div>
    </main>
<?php
else :
    // Respect ?team=ID when the user has access to multiple teams
    $requested_team_id = (int) ( $_GET['team'] ?? 0 );
    $team = $team_posts[0];
    if ( $requested_team_id ) {
        foreach ( $team_posts as $_tp ) {
            if ( $_tp->ID === $requested_team_id ) { $team = $_tp; break; }
        }
    }
    $team_id = $team->ID;
    $thumb   = get_the_post_thumbnail_url( $team_id, 'thumbnail' );
    $words   = array_filter( explode( ' ', $team->post_title ) );
    $initials = substr( implode( '', array_map( fn( $w ) => strtoupper( $w[0] ), $words ) ), 0, 3 );
    $state   = get_field( 'team_state', $team_id );
    $city    = get_field( 'team_city', $team_id );
    $wins    = (int) get_field( 'team_wins', $team_id );
    $losses  = (int) get_field( 'team_losses', $team_id );
    $players = (int) get_field( 'team_players', $team_id );
    $cats    = get_field( 'team_category', $team_id ) ?: [];

    $form_base = [
        'post_id'         => $team_id,
        'return'          => add_query_arg( 'saved', '1', home_url( '/mi-equipo/' ) ),
        'updated_message' => false,
        'html_submit_button' => '<input type="submit" class="fmdb-btn fmdb-btn--primary fmdb-form-submit" value="%s">',
    ];
?>
    <main class="fmdb-dashboard">

        <div class="fmdb-dashboard-hero">
            <div class="fmdb-dashboard-hero__inner">
                <div class="fmdb-team-crest">
                    <?php if ( $thumb ) : ?>
                        <img src="<?php echo esc_url( $thumb ); ?>" alt="">
                    <?php else : ?>
                        <span class="fmdb-team-initials"><?php echo esc_html( $initials ); ?></span>
                    <?php endif; ?>
                </div>
                <div class="fmdb-dashboard-hero__info">
                    <p class="fmdb-dashboard-hero__label">Mi equipo</p>
                    <h1><?php echo esc_html( $team->post_title ); ?></h1>
                    <div class="fmdb-dashboard-hero__meta">
                        <span><?php echo esc_html( $city ? "$city, $state" : ( $state ?: 'Sin estado' ) ); ?></span>
                        <?php foreach ( $cats as $cat ) : ?>
                            <span class="fmdb-badge fmdb-badge--<?php echo esc_attr( strtolower( $cat ) ); ?>"><?php echo esc_html( $cat ); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="fmdb-dashboard-hero__stats">
                    <div class="fmdb-dash-stat"><span><?php echo $wins; ?></span><small>Victorias</small></div>
                    <div class="fmdb-dash-stat"><span><?php echo $losses; ?></span><small>Derrotas</small></div>
                    <div class="fmdb-dash-stat"><span><?php echo $players; ?></span><small>Jugadores</small></div>
                </div>
            </div>
        </div>

        <?php if ( count( $team_posts ) > 1 ) : ?>
        <div class="fmdb-team-switcher">
            <div class="fmdb-team-switcher__inner">
                <label for="fmdb-team-switch" class="fmdb-team-switcher__label">Equipo:</label>
                <select id="fmdb-team-switch" class="fmdb-team-switcher__select"
                        onchange="location.href='<?php echo esc_url( home_url( '/mi-equipo/' ) ); ?>?team='+this.value">
                    <?php foreach ( $team_posts as $_tp ) : ?>
                        <option value="<?php echo $_tp->ID; ?>" <?php selected( $_tp->ID, $team_id ); ?>>
                            <?php echo esc_html( $_tp->post_title ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>

        <div class="fmdb-dashboard__body">

            <?php if ( isset( $_GET['saved'] ) ) : ?>
                <div class="fmdb-dashboard__notice">Cambios guardados correctamente.</div>
            <?php endif; ?>

            <div class="fmdb-dashboard__tabs">
                <nav class="fmdb-tabs-nav">
                    <button class="fmdb-tab-btn active" data-tab="info">Información</button>
                    <button class="fmdb-tab-btn" data-tab="stats">Estadísticas</button>
                    <button class="fmdb-tab-btn" data-tab="plantel">Plantel</button>
                </nav>

                <div class="fmdb-tab-panel active" data-panel="info">
                    <?php acf_form( array_merge( $form_base, [
                        'field_groups' => [ 'group_fmdb_team_info' ],
                        'submit_value' => 'Guardar información',
                    ] ) ); ?>
                </div>

                <div class="fmdb-tab-panel" data-panel="stats">
                    <?php acf_form( array_merge( $form_base, [
                        'field_groups' => [ 'group_fmdb_team_stats' ],
                        'submit_value' => 'Guardar estadísticas',
                    ] ) ); ?>
                </div>

                <div class="fmdb-tab-panel" data-panel="plantel">
                <?php
                    $roster = get_post_meta( $team_id, 'team_roster', true );
                    if ( ! is_array( $roster ) ) $roster = [];
                    $rostered_ids = array_values( array_filter( array_map( function( $e ) {
                        return (int) ( $e['user_id'] ?? $e['linked_user_id'] ?? 0 );
                    }, $roster ) ) );
                    $available = get_users( [
                        'role__in' => [ 'jugador', 'representante_equipo' ],
                        'orderby'  => 'display_name',
                        'order'    => 'ASC',
                        'exclude'  => $rostered_ids,
                    ] );
                    $positions = [ 'Extremo', 'Lateral', 'Centro', 'Coach' ];
                ?>
                <form class="fmdb-plantel-form" method="post">
                    <?php wp_nonce_field( 'fmdb_plantel_save', 'fmdb_plantel_nonce' ); ?>
                    <input type="hidden" name="fmdb_plantel_team_id" value="<?php echo $team_id; ?>">

                    <div class="fmdb-plantel-rows" id="fmdb-plantel-rows">
                        <?php foreach ( $roster as $i => $entry ) :
                            $uid  = (int) ( $entry['user_id'] ?? $entry['linked_user_id'] ?? 0 );
                            $u    = $uid ? get_userdata( $uid ) : null;
                            if ( ! $u ) continue;
                        ?>
                        <div class="fmdb-plantel-row">
                            <input type="hidden" name="roster[<?php echo $i; ?>][user_id]" value="<?php echo $uid; ?>">
                            <div class="fmdb-plantel-row__player">
                                <?php echo fmdb_player_avatar( $uid, $u->display_name ); ?>
                                <span><?php echo esc_html( $u->display_name ); ?></span>
                            </div>
                            <input type="number" name="roster[<?php echo $i; ?>][number]"
                                   value="<?php echo esc_attr( $entry['number'] ?? '' ); ?>"
                                   class="fmdb-plantel-input fmdb-plantel-input--num" placeholder="#" min="0" max="99">
                            <select name="roster[<?php echo $i; ?>][position]" class="fmdb-plantel-select">
                                <option value="">— Posición —</option>
                                <?php foreach ( $positions as $pos ) : ?>
                                    <option value="<?php echo esc_attr( $pos ); ?>" <?php selected( $entry['position'] ?? '', $pos ); ?>><?php echo esc_html( $pos ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="roster[<?php echo $i; ?>][role]" class="fmdb-plantel-select fmdb-plantel-select--sm">
                                <option value="Titular" <?php selected( $entry['role'] ?? '', 'Titular' ); ?>>Titular</option>
                                <option value="Suplente" <?php selected( $entry['role'] ?? '', 'Suplente' ); ?>>Suplente</option>
                            </select>
                            <label class="fmdb-plantel-cap">
                                <input type="checkbox" name="roster[<?php echo $i; ?>][is_captain]" value="1" <?php checked( ! empty( $entry['is_captain'] ) ); ?>>
                                <span>Cap.</span>
                            </label>
                            <button type="button" class="fmdb-plantel-remove" aria-label="Eliminar jugador">✕</button>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ( ! empty( $available ) ) : ?>
                    <div class="fmdb-plantel-add">
                        <select id="fmdb-player-select" class="fmdb-plantel-add__select">
                            <option value="">— Seleccionar jugador —</option>
                            <?php foreach ( $available as $p ) : ?>
                                <option value="<?php echo $p->ID; ?>" data-name="<?php echo esc_attr( $p->display_name ); ?>">
                                    <?php echo esc_html( $p->display_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="fmdb-add-player" class="fmdb-btn fmdb-btn--secondary">+ Agregar</button>
                    </div>
                    <?php else : ?>
                        <p class="fmdb-empty fmdb-plantel-empty">No hay jugadores disponibles para agregar.</p>
                    <?php endif; ?>

                    <div class="fmdb-plantel-footer">
                        <button type="submit" name="fmdb_save_plantel" class="fmdb-btn fmdb-btn--primary fmdb-form-submit">Guardar plantel</button>
                    </div>
                </form>

                <script>
                (function () {
                    'use strict';
                    var counter   = <?php echo count( $roster ); ?>;
                    var rows      = document.getElementById('fmdb-plantel-rows');
                    var select    = document.getElementById('fmdb-player-select');
                    var addBtn    = document.getElementById('fmdb-add-player');
                    var positions = <?php echo json_encode( $positions ); ?>;

                    function posOpts() {
                        return '<option value="">— Posición —</option>' +
                            positions.map(function (p) { return '<option value="' + p + '">' + p + '</option>'; }).join('');
                    }

                    function attachRemove(row) {
                        row.querySelector('.fmdb-plantel-remove').addEventListener('click', function () {
                            var uid  = row.querySelector('input[type="hidden"]').value;
                            var name = row.querySelector('.fmdb-plantel-row__player span').textContent.trim();
                            row.remove();
                            if (select) {
                                var opt = new Option(name, uid);
                                opt.dataset.name = name;
                                select.appendChild(opt);
                            }
                        });
                    }

                    document.querySelectorAll('.fmdb-plantel-row').forEach(attachRemove);

                    if (addBtn && select) {
                        addBtn.addEventListener('click', function () {
                            var opt  = select.options[select.selectedIndex];
                            var uid  = opt ? opt.value : '';
                            var name = opt ? (opt.dataset.name || opt.text) : '';
                            if (!uid) return;

                            var i = counter++;
                            var initial = name.charAt(0).toUpperCase();
                            var row = document.createElement('div');
                            row.className = 'fmdb-plantel-row';
                            row.innerHTML =
                                '<input type="hidden" name="roster[' + i + '][user_id]" value="' + uid + '">' +
                                '<div class="fmdb-plantel-row__player">' +
                                    '<span class="fmdb-player-avatar fmdb-player-avatar--initials">' + initial + '</span>' +
                                    '<span>' + name + '</span>' +
                                '</div>' +
                                '<input type="number" name="roster[' + i + '][number]" class="fmdb-plantel-input fmdb-plantel-input--num" placeholder="#" min="0" max="99">' +
                                '<select name="roster[' + i + '][position]" class="fmdb-plantel-select">' + posOpts() + '</select>' +
                                '<select name="roster[' + i + '][role]" class="fmdb-plantel-select fmdb-plantel-select--sm">' +
                                    '<option value="Titular">Titular</option><option value="Suplente">Suplente</option>' +
                                '</select>' +
                                '<label class="fmdb-plantel-cap"><input type="checkbox" name="roster[' + i + '][is_captain]" value="1"> <span>Cap.</span></label>' +
                                '<button type="button" class="fmdb-plantel-remove" aria-label="Eliminar jugador">✕</button>';
                            rows.appendChild(row);
                            attachRemove(row);
                            opt.remove();
                        });
                    }
                })();
                </script>
                </div>
            </div>

            <div class="fmdb-dashboard__footer-link">
                <a href="<?php echo esc_url( get_permalink( $team_id ) ); ?>" class="fmdb-link-more">Ver página pública del equipo →</a>
            </div>

        </div>
    </main>
<?php endif; ?>

<?php get_footer(); ?>
