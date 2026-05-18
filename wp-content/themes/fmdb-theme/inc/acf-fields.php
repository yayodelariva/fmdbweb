<?php
/**
 * ACF field group registrations and their save/load hooks.
 *
 * Field groups:
 *  - fmdb_team:      info, stats, representantes
 *  - fmdb_league:    league info (with virtual league_teams field)
 *  - fmdb_seleccion: member info
 *  - page id 116:    Consejo Directivo members repeater
 *
 * Hooks:
 *  - load/save the virtual league_teams field
 *  - sync fmdb_seleccion title from linked WP user
 *  - promote/demote team representatives on team_rep changes
 *  - front-end ACF save validation for team managers
 */

add_action( 'acf/init', function () {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

    $state_choices = array_combine( fmdb_mexican_states(), fmdb_mexican_states() );

    // --- Group 1: Información general ---
    acf_add_local_field_group( [
        'key'    => 'group_fmdb_team_info',
        'title'  => 'Información del equipo',
        'fields' => [
            [
                'key'           => 'field_team_state',
                'label'         => 'Estado',
                'name'          => 'team_state',
                'type'          => 'select',
                'choices'       => $state_choices,
                'allow_null'    => 1,
                'placeholder'   => 'Selecciona un estado',
                'required'      => 1,
            ],
            [
                'key'   => 'field_team_city',
                'label' => 'Alcaldía/Municipio',
                'name'  => 'team_city',
                'type'  => 'text',
            ],
            [
                'key'     => 'field_team_category',
                'label'   => 'Categoría',
                'name'    => 'team_category',
                'type'    => 'checkbox',
                'choices' => [
                    'Mixto'   => 'Mixto',
                    'Varonil' => 'Varonil',
                    'Femenil' => 'Femenil',
                ],
                'layout' => 'horizontal',
            ],
            [
                'key'           => 'field_team_league',
                'label'         => 'Liga',
                'name'          => 'team_league',
                'type'          => 'post_object',
                'post_type'     => [ 'fmdb_league' ],
                'allow_null'    => 1,
                'multiple'      => 0,
                'return_format' => 'object',
                'ui'            => 1,
            ],
            [
                'key'   => 'field_team_founded',
                'label' => 'Año de fundación',
                'name'  => 'team_founded',
                'type'  => 'number',
                'min'   => 1990,
                'max'   => 2099,
            ],
            [
                'key'   => 'field_team_fmdb_id',
                'label' => 'ID de registro FMDB',
                'name'  => 'team_fmdb_id',
                'type'  => 'text',
            ],
            [
                'key'   => 'field_team_description',
                'label' => 'Acerca del equipo',
                'name'  => 'team_description',
                'type'  => 'textarea',
                'rows'  => 4,
            ],
            [
                'key'   => 'field_team_contact_email',
                'label' => 'Email de contacto',
                'name'  => 'team_contact_email',
                'type'  => 'email',
            ],
            [
                'key'   => 'field_team_instagram',
                'label' => 'Instagram',
                'name'  => 'team_instagram',
                'type'  => 'url',
            ],
            [
                'key'   => 'field_team_facebook',
                'label' => 'Facebook',
                'name'  => 'team_facebook',
                'type'  => 'url',
            ],
        ],
        'location' => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'fmdb_team' ] ] ],
    ] );

    // --- Group 2: Estadísticas de temporada ---
    acf_add_local_field_group( [
        'key'    => 'group_fmdb_team_stats',
        'title'  => 'Estadísticas de temporada',
        'fields' => [
            [
                'key'   => 'field_team_wins',
                'label' => 'Victorias',
                'name'  => 'team_wins',
                'type'  => 'number',
                'min'   => 0,
            ],
            [
                'key'   => 'field_team_losses',
                'label' => 'Derrotas',
                'name'  => 'team_losses',
                'type'  => 'number',
                'min'   => 0,
            ],
            [
                'key'   => 'field_team_players',
                'label' => 'Jugadores registrados',
                'name'  => 'team_players',
                'type'  => 'number',
                'min'   => 0,
            ],
        ],
        'location' => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'fmdb_team' ] ] ],
    ] );

    // --- Group: Representantes de equipo (WP admin only — not shown in /mi-equipo/) ---
    acf_add_local_field_group( [
        'key'    => 'group_fmdb_team_reps',
        'title'  => 'Representante de Equipo',
        'fields' => [
            [
                'key'           => 'field_team_rep',
                'label'         => 'Representante(s) de Equipo',
                'instructions'  => 'Selecciona los jugadores que gestionarán este equipo. Obtendrán automáticamente el rol de Representante de Equipo al guardar.',
                'name'          => 'team_rep',
                'type'          => 'user',
                'role'          => [ 'jugador', 'representante_equipo' ],
                'allow_null'    => 1,
                'multiple'      => 1,
                'return_format' => 'id',
            ],
        ],
        'location' => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'fmdb_team' ] ] ],
    ] );

    // --- Group: Información de la liga ---
    acf_add_local_field_group( [
        'key'    => 'group_fmdb_league_info',
        'title'  => 'Información de la liga',
        'fields' => [
            [
                'key'           => 'field_league_teams',
                'label'         => 'Agregar equipos',
                'instructions'  => 'Selecciona los equipos que pertenecen a esta liga. El campo "Liga" de cada equipo se actualizará automáticamente al guardar.',
                'name'          => 'league_teams',
                'type'          => 'post_object',
                'post_type'     => [ 'fmdb_team' ],
                'allow_null'    => 1,
                'multiple'      => 1,
                'return_format' => 'id',
                'ui'            => 1,
            ],
            [
                'key'         => 'field_league_state',
                'label'       => 'Estado',
                'name'        => 'league_state',
                'type'        => 'select',
                'choices'     => [ 'Nacional' => 'Nacional' ] + $state_choices,
                'allow_null'  => 1,
                'placeholder' => 'Selecciona un estado',
            ],
            [
                'key'   => 'field_league_description',
                'label' => 'Descripción',
                'name'  => 'league_description',
                'type'  => 'textarea',
                'rows'  => 5,
            ],
            [
                'key'   => 'field_league_founded',
                'label' => 'Año de fundación',
                'name'  => 'league_founded',
                'type'  => 'number',
                'min'   => 1990,
                'max'   => 2099,
            ],
            [
                'key'   => 'field_league_email',
                'label' => 'Email de contacto',
                'name'  => 'league_email',
                'type'  => 'email',
            ],
            [
                'key'   => 'field_league_website',
                'label' => 'Sitio web',
                'name'  => 'league_website',
                'type'  => 'url',
            ],
            [
                'key'   => 'field_league_instagram',
                'label' => 'Instagram',
                'name'  => 'league_instagram',
                'type'  => 'url',
            ],
            [
                'key'   => 'field_league_facebook',
                'label' => 'Facebook',
                'name'  => 'league_facebook',
                'type'  => 'url',
            ],
        ],
        'location' => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'fmdb_league' ] ] ],
    ] );

    // --- Group: Miembro de selección nacional ---
    acf_add_local_field_group( [
        'key'    => 'group_fmdb_seleccion_member',
        'title'  => 'Información del miembro',
        'fields' => [
            [
                'key'           => 'field_member_user',
                'label'         => 'Cuenta de jugador',
                'instructions'  => 'Busca al jugador por nombre o usuario. Solo aparecen cuentas con rol Jugador.',
                'name'          => 'member_user',
                'type'          => 'user',
                'role'          => [ 'jugador' ],
                'allow_null'    => 1,
                'multiple'      => 0,
                'return_format' => 'id',
            ],
            [
                'key'     => 'field_member_ball_type',
                'label'   => 'Tipo de balón',
                'name'    => 'member_ball_type',
                'type'    => 'select',
                'choices' => [
                    'Foam'  => 'Foam',
                    'Cloth' => 'Cloth',
                ],
                'allow_null' => 0,
                'required'   => 1,
            ],
            [
                'key'      => 'field_member_seleccion',
                'label'    => 'Selección',
                'name'     => 'member_seleccion',
                'type'     => 'select',
                'choices'  => [
                    'Varonil' => 'Varonil',
                    'Femenil' => 'Femenil',
                    'Mixto'   => 'Mixto',
                    'U-18'    => 'U-18',
                ],
                'allow_null' => 0,
                'required'   => 1,
            ],
            [
                'key'        => 'field_member_position',
                'label'      => 'Posición',
                'name'       => 'member_position',
                'type'       => 'select',
                'choices'    => [
                    'Extremo' => 'Extremo',
                    'Lateral' => 'Lateral',
                    'Centro'  => 'Centro',
                    'Coach'   => 'Coach',
                ],
                'allow_null' => 1,
            ],
            [
                'key'   => 'field_member_number',
                'label' => 'Número',
                'name'  => 'member_number',
                'type'  => 'number',
                'min'   => 0,
                'max'   => 99,
            ],
            [
                'key'           => 'field_member_club',
                'label'         => 'Club',
                'name'          => 'member_club',
                'type'          => 'post_object',
                'post_type'     => [ 'fmdb_team' ],
                'allow_null'    => 1,
                'multiple'      => 0,
                'return_format' => 'object',
                'ui'            => 1,
            ],
        ],
        'location' => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'fmdb_seleccion' ] ] ],
    ] );

} );

/**
 * Liga "Agregar equipos" field is a virtual view over each team's `team_league`.
 * - Load: populate from teams whose team_league points at this liga.
 * - Save: diff against current attachments, update each affected team's team_league.
 * Storing the value as postmeta is harmless (load filter overrides it on read).
 */
add_filter( 'acf/load_value/key=field_league_teams', function ( $value, $post_id, $field ) {
    if ( get_post_type( $post_id ) !== 'fmdb_league' ) return $value;
    return get_posts( [
        'post_type'      => 'fmdb_team',
        'posts_per_page' => -1,
        'post_status'    => [ 'publish', 'pending', 'draft', 'private' ],
        'fields'         => 'ids',
        'meta_query'     => [ [ 'key' => 'team_league', 'value' => (int) $post_id ] ],
    ] );
}, 10, 3 );

add_action( 'acf/save_post', function ( $post_id ) {
    if ( get_post_type( $post_id ) !== 'fmdb_league' ) return;

    // Read raw postmeta (bypasses load_value filter, which would return stale data)
    $raw      = get_post_meta( $post_id, 'league_teams', true );
    $selected = is_array( $raw ) ? array_filter( array_map( 'intval', $raw ) ) : [];

    $current = get_posts( [
        'post_type'      => 'fmdb_team',
        'posts_per_page' => -1,
        'post_status'    => [ 'publish', 'pending', 'draft', 'private' ],
        'fields'         => 'ids',
        'meta_query'     => [ [ 'key' => 'team_league', 'value' => (int) $post_id ] ],
    ] );
    $current = array_map( 'intval', $current );

    foreach ( array_diff( $selected, $current ) as $team_id ) {
        update_field( 'team_league', (int) $post_id, $team_id );
    }
    foreach ( array_diff( $current, $selected ) as $team_id ) {
        update_field( 'team_league', '', $team_id );
    }
}, 20 );

// Auto-set fmdb_seleccion post title from linked WP user account
add_action( 'acf/save_post', function ( $post_id ) {
    if ( get_post_type( $post_id ) !== 'fmdb_seleccion' ) return;
    $user_id = get_field( 'member_user', $post_id );
    if ( ! $user_id ) return;
    $user = get_userdata( (int) $user_id );
    if ( $user ) {
        wp_update_post( [ 'ID' => $post_id, 'post_title' => $user->display_name ] );
    }
}, 20 );

// Capture current team_rep value before ACF overwrites it on save
add_action( 'acf/save_post', function ( $post_id ) {
    if ( get_post_type( $post_id ) !== 'fmdb_team' ) return;
    $old = get_field( 'team_rep', $post_id );
    if ( ! is_array( $old ) ) $old = $old ? [ (int) $old ] : [];
    set_transient( 'fmdb_prev_reps_' . $post_id, array_map( 'intval', $old ), 60 );
}, 1 );

// Promote added reps → representante_equipo; demote removed reps → jugador
add_action( 'acf/save_post', function ( $post_id ) {
    if ( get_post_type( $post_id ) !== 'fmdb_team' ) return;

    $old = get_transient( 'fmdb_prev_reps_' . $post_id ) ?: [];
    delete_transient( 'fmdb_prev_reps_' . $post_id );

    $new = get_field( 'team_rep', $post_id );
    if ( ! is_array( $new ) ) $new = $new ? [ (int) $new ] : [];
    $new = array_map( 'intval', $new );

    foreach ( array_diff( $new, $old ) as $uid ) {
        $u = get_userdata( $uid );
        if ( $u && in_array( 'jugador', (array) $u->roles, true ) ) {
            $u->set_role( 'representante_equipo' );
        }
    }

    foreach ( array_diff( $old, $new ) as $uid ) {
        $still = get_posts( [
            'post_type'      => 'fmdb_team',
            'posts_per_page' => 1,
            'post__not_in'   => [ $post_id ],
            'fields'         => 'ids',
            'meta_query'     => [ [ 'key' => 'team_rep', 'value' => '"' . $uid . '"', 'compare' => 'LIKE' ] ],
        ] );
        if ( empty( $still ) ) {
            $u = get_userdata( $uid );
            if ( $u && in_array( 'representante_equipo', (array) $u->roles, true ) ) {
                $u->set_role( 'jugador' );
            }
        }
    }
}, 20 );

// Block front-end ACF saves for teams the current user doesn't manage
add_action( 'acf/validate_save_post', function () {
    $post_id = absint( $_POST['post_id'] ?? 0 );
    if ( ! $post_id || get_post_type( $post_id ) !== 'fmdb_team' ) return;
    if ( fmdb_is_team_manager() ) return;
    $reps = get_field( 'team_rep', $post_id );
    if ( ! is_array( $reps ) ) $reps = $reps ? [ $reps ] : [];
    if ( ! in_array( get_current_user_id(), array_map( 'intval', $reps ), true ) ) {
        acf_add_validation_error( '', 'No tienes permiso para editar este equipo.' );
    }
} );

// ACF field group: Consejo Directivo members (repeater on the Consejo Directivo page)
add_action( 'acf/init', function () {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

    // Resolve the Consejo Directivo page by path so the binding survives
    // re-imports or environments where the page has a different ID.
    $consejo_page = get_page_by_path( 'organigrama/consejo-directivo' );
    if ( ! $consejo_page ) return;

    acf_add_local_field_group( [
        'key'    => 'group_fmdb_consejo_directivo',
        'title'  => 'Miembros del Consejo Directivo',
        'fields' => [
            [
                'key'          => 'field_consejo_members',
                'label'        => 'Miembros',
                'name'         => 'consejo_members',
                'type'         => 'repeater',
                'button_label' => 'Añadir miembro',
                'min'          => 0,
                'layout'       => 'block',
                'sub_fields'   => [
                    [
                        'key'   => 'field_consejo_member_name',
                        'label' => 'Nombre',
                        'name'  => 'member_name',
                        'type'  => 'text',
                        'required' => 1,
                    ],
                    [
                        'key'   => 'field_consejo_member_position',
                        'label' => 'Cargo',
                        'name'  => 'member_position',
                        'type'  => 'text',
                        'instructions' => 'Ej. Presidente, Vicepresidente, Secretario...',
                    ],
                    [
                        'key'           => 'field_consejo_member_photo',
                        'label'         => 'Foto',
                        'name'          => 'member_photo',
                        'type'          => 'image',
                        'return_format' => 'array',
                        'preview_size'  => 'medium',
                    ],
                    [
                        'key'   => 'field_consejo_member_bio',
                        'label' => 'Biografía corta',
                        'name'  => 'member_bio',
                        'type'  => 'textarea',
                        'rows'  => 3,
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [ 'param' => 'page', 'operator' => '==', 'value' => (string) $consejo_page->ID ],
            ],
        ],
    ] );
} );
