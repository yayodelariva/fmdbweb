<?php
add_action( 'wp_enqueue_scripts', function () {
    // Per-asset cache-busting: filemtime() so each edit invalidates the browser cache.
    $ver = function ( $rel ) {
        $path = get_stylesheet_directory() . '/' . ltrim( $rel, '/' );
        return file_exists( $path ) ? (string) filemtime( $path ) : wp_get_theme()->get( 'Version' );
    };

    wp_enqueue_style( 'kadence-parent', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'fmdb-theme', get_stylesheet_uri(), [ 'kadence-parent' ], $ver( 'style.css' ) );
    wp_enqueue_style( 'fmdb-map', get_stylesheet_directory_uri() . '/assets/css/map.css', [], $ver( 'assets/css/map.css' ) );
    if ( is_singular( 'fmdb_team' ) ) {
        wp_enqueue_style( 'fmdb-team-single', get_stylesheet_directory_uri() . '/assets/css/team-single.css', [], $ver( 'assets/css/team-single.css' ) );
    }
    if ( is_singular( 'fmdb_league' ) ) {
        wp_enqueue_style( 'fmdb-team-single',   get_stylesheet_directory_uri() . '/assets/css/team-single.css',   [], $ver( 'assets/css/team-single.css' ) );
        wp_enqueue_style( 'fmdb-league-single', get_stylesheet_directory_uri() . '/assets/css/league-single.css', [ 'fmdb-team-single' ], $ver( 'assets/css/league-single.css' ) );
    }
    if ( is_page( 'selecciones' ) || is_page_template( 'page-seleccion-tipo.php' ) ) {
        wp_enqueue_style( 'fmdb-selecciones', get_stylesheet_directory_uri() . '/assets/css/selecciones.css', [], $ver( 'assets/css/selecciones.css' ) );
    }
    if ( is_page( 'noticias' ) || is_singular( 'post' ) ) {
        wp_enqueue_style( 'fmdb-noticias', get_stylesheet_directory_uri() . '/assets/css/noticias.css', [], $ver( 'assets/css/noticias.css' ) );
    }
    if ( is_page( 'equipos-y-ligas' ) ) {
        wp_enqueue_style(  'fmdb-equipos', get_stylesheet_directory_uri() . '/assets/css/equipos.css', [], $ver( 'assets/css/equipos.css' ) );
        wp_enqueue_script( 'fmdb-equipos', get_stylesheet_directory_uri() . '/assets/js/equipos.js', [ 'fmdb-map' ], $ver( 'assets/js/equipos.js' ), true );
    }
    if ( is_front_page() ) {
        wp_enqueue_style(  'fmdb-home', get_stylesheet_directory_uri() . '/assets/css/home.css', [], $ver( 'assets/css/home.css' ) );
        wp_enqueue_script( 'fmdb-home', get_stylesheet_directory_uri() . '/assets/js/home.js', [ 'fmdb-map' ], $ver( 'assets/js/home.js' ), true );
    }
    if ( is_page( 'registro' ) || is_page( 'login' ) || is_page( 'olvide-mi-contrasena' ) ) {
        wp_enqueue_style( 'fmdb-registro', get_stylesheet_directory_uri() . '/assets/css/registro.css', [], $ver( 'assets/css/registro.css' ) );
    }
    if ( is_page( 'mi-perfil' ) ) {
        wp_enqueue_style( 'fmdb-registro', get_stylesheet_directory_uri() . '/assets/css/registro.css', [], $ver( 'assets/css/registro.css' ) );
        wp_enqueue_style( 'fmdb-perfil',   get_stylesheet_directory_uri() . '/assets/css/mi-perfil.css', [ 'fmdb-registro' ], $ver( 'assets/css/mi-perfil.css' ) );
    }
    if ( is_page( 'eventos' ) || is_singular( 'tribe_events' ) ) {
        wp_enqueue_style( 'fmdb-eventos', get_stylesheet_directory_uri() . '/assets/css/eventos.css', [], $ver( 'assets/css/eventos.css' ) );
    }
    if ( is_page( 'eventos' ) ) {
        wp_enqueue_script( 'fmdb-eventos', get_stylesheet_directory_uri() . '/assets/js/eventos.js', [], $ver( 'assets/js/eventos.js' ), true );
    }
    if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
        wp_enqueue_style( 'fmdb-tienda', get_stylesheet_directory_uri() . '/assets/css/tienda.css', [], $ver( 'assets/css/tienda.css' ) );
    }
    if ( function_exists( 'is_cart' ) && is_cart() ) {
        wp_enqueue_script( 'fmdb-cart-i18n', get_stylesheet_directory_uri() . '/assets/js/cart-i18n.js', [], $ver( 'assets/js/cart-i18n.js' ), true );
    }
    if ( is_page( 'mi-equipo' ) ) {
        wp_enqueue_style(  'fmdb-dashboard', get_stylesheet_directory_uri() . '/assets/css/dashboard.css', [], $ver( 'assets/css/dashboard.css' ) );
        wp_enqueue_script( 'fmdb-dashboard', get_stylesheet_directory_uri() . '/assets/js/dashboard.js', [], $ver( 'assets/js/dashboard.js' ), true );
        if ( function_exists( 'acf_enqueue_scripts' ) ) {
            acf_enqueue_scripts();
        }
        // CMB2 frontend assets (the plantel + resultados panels use CMB2 forms)
        if ( class_exists( 'CMB2_Hookup' ) ) {
            CMB2_Hookup::enqueue_cmb_css();
            CMB2_Hookup::enqueue_cmb_js();
            wp_enqueue_media();
        }
    }

    wp_enqueue_script( 'fmdb-map', get_stylesheet_directory_uri() . '/assets/js/map.js', [], $ver( 'assets/js/map.js' ), true );

    // Pass per-state team and league counts to JS: [ 'Estado' => count ]
    $team_counts   = [];
    $league_counts = [];
    if ( function_exists( 'get_posts' ) ) {
        $teams = get_posts( [ 'post_type' => 'fmdb_team', 'posts_per_page' => -1, 'post_status' => 'publish' ] );
        foreach ( $teams as $team ) {
            $state = get_field( 'team_state', $team->ID );
            if ( $state ) {
                $team_counts[ $state ] = ( $team_counts[ $state ] ?? 0 ) + 1;
            }
        }
        $leagues = get_posts( [ 'post_type' => 'fmdb_league', 'posts_per_page' => -1, 'post_status' => 'publish' ] );
        foreach ( $leagues as $liga ) {
            $state = get_field( 'league_state', $liga->ID );
            // Skip "Nacional" — it doesn't map to a single state on the map
            if ( $state && $state !== 'Nacional' ) {
                $league_counts[ $state ] = ( $league_counts[ $state ] ?? 0 ) + 1;
            }
        }
    }
    wp_localize_script( 'fmdb-map', 'fmdbMapData', [
        'teams'   => $team_counts,
        'leagues' => $league_counts,
    ] );
} );

// Custom post type: Equipos
add_action( 'init', function () {
    register_post_type( 'fmdb_team', [
        'labels' => [
            'name'               => 'Equipos',
            'singular_name'      => 'Equipo',
            'add_new'            => 'Añadir equipo',
            'add_new_item'       => 'Añadir nuevo equipo',
            'edit_item'          => 'Editar equipo',
            'view_item'          => 'Ver equipo',
            'search_items'       => 'Buscar equipos',
            'not_found'          => 'No se encontraron equipos',
            'not_found_in_trash' => 'No hay equipos en la papelera',
        ],
        'public'       => true,
        'has_archive'  => false,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-groups',
        'supports'     => [ 'title', 'thumbnail', 'custom-fields' ],
        'rewrite'      => [ 'slug' => 'equipos' ],
    ] );

    register_post_type( 'fmdb_league', [
        'labels' => [
            'name'               => 'Ligas',
            'singular_name'      => 'Liga',
            'add_new'            => 'Añadir liga',
            'add_new_item'       => 'Añadir nueva liga',
            'edit_item'          => 'Editar liga',
            'view_item'          => 'Ver liga',
            'search_items'       => 'Buscar ligas',
            'not_found'          => 'No se encontraron ligas',
            'not_found_in_trash' => 'No hay ligas en la papelera',
        ],
        'public'       => true,
        'has_archive'  => false,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-awards',
        'supports'     => [ 'title', 'thumbnail', 'custom-fields' ],
        'rewrite'      => [ 'slug' => 'ligas' ],
    ] );

    register_post_type( 'fmdb_seleccion', [
        'labels' => [
            'name'               => 'Selecciones',
            'singular_name'      => 'Miembro de Selección',
            'add_new'            => 'Añadir miembro',
            'add_new_item'       => 'Añadir nuevo miembro',
            'edit_item'          => 'Editar miembro',
            'view_item'          => 'Ver miembro',
            'search_items'       => 'Buscar miembros',
            'not_found'          => 'No se encontraron miembros',
            'not_found_in_trash' => 'No hay miembros en la papelera',
        ],
        'public'       => true,
        'has_archive'  => false,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-shield',
        'supports'     => [ 'title', 'thumbnail', 'custom-fields' ],
        'rewrite'      => [ 'slug' => 'seleccion' ],
    ] );
} );

// Register custom roles and remove unused WP defaults
add_action( 'init', function () {
    if ( ! get_role( 'jugador' ) ) {
        add_role( 'jugador', 'Jugador', [ 'read' => true ] );
    }
    foreach ( [ 'subscriber', 'contributor', 'author', 'editor' ] as $r ) {
        remove_role( $r );
    }
} );

// ACF field groups for fmdb_team
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

// Force our own single-tribe_events.php to win over TEC's template hijack
add_filter( 'template_include', function ( $template ) {
    if ( is_singular( 'tribe_events' ) ) {
        $custom = locate_template( 'single-tribe_events.php' );
        if ( $custom ) return $custom;
    }
    return $template;
}, 999 );

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

// Block all WP admin access for jugadores and representantes — front-end only
add_action( 'admin_init', function () {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
    $user  = wp_get_current_user();
    $roles = (array) $user->roles;
    if ( array_intersect( $roles, [ 'jugador', 'representante_equipo' ] ) ) {
        wp_safe_redirect( home_url( '/mi-equipo/' ) );
        exit;
    }
} );

// Hide admin bar for everyone except administrators
add_action( 'after_setup_theme', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        show_admin_bar( false );
    }
} );

// WooCommerce compatibility
add_action( 'after_setup_theme', function () {
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
} );

// Remove reviews tab and star rating from product pages
add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
    unset( $tabs['reviews'] );
    return $tabs;
}, 98 );
add_action( 'init', function () {
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
    remove_action( 'woocommerce_cart_is_empty', 'woocommerce_return_to_shop', 20 );
} );

// Cart page title in Spanish — covers both WooCommerce template and Kadence hero
add_filter( 'woocommerce_page_title', function ( $title ) {
    if ( function_exists( 'is_cart' ) && is_cart() ) return 'Tu carrito de compras';
    return $title;
} );
add_filter( 'the_title', function ( $title, $post_id = null ) {
    if ( ! function_exists( 'wc_get_page_id' ) || ! function_exists( 'is_cart' ) ) return $title;
    if ( is_cart() && (int) $post_id === wc_get_page_id( 'cart' ) ) return 'Tu carrito de compras';
    return $title;
}, 10, 2 );

// Hide Kadence hero/in-content title on cart and checkout
add_filter( 'kadence_post_layout', function ( $layout ) {
    if ( ( function_exists( 'is_checkout' ) && is_checkout() ) ||
         ( function_exists( 'is_cart' ) && is_cart() ) ) {
        $layout['title'] = 'hide';
    }
    return $layout;
} );

// Replace static English strings baked into the Cart block's saved post content
add_filter( 'render_block', function ( $block_content ) {
    return str_replace(
        [ 'Your cart is currently empty!', 'New in store' ],
        [ 'Tu carrito de compras está vacío', 'Podría interesarte:' ],
        $block_content
    );
} );

// Empty cart message with shop link
add_filter( 'wc_empty_cart_message', function () {
    $shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
    return 'Tu carrito está vacío. <a href="' . esc_url( $shop ) . '" class="fmdb-cart-empty__link">Visita la tienda</a>';
} );

// Fragment: keep cart counter in sync after AJAX add-to-cart
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) return $fragments;
    $count = WC()->cart->get_cart_contents_count();
    $fragments['.fmdb-nav-cart__count'] = '<span class="fmdb-nav-cart__count' . ( $count ? ' has-items' : '' ) . '">' . $count . '</span>';
    return $fragments;
} );

// Inject profile pill into primary nav when logged in
add_filter( 'wp_nav_menu_items', function ( $items, $args ) {
    if ( ! isset( $args->theme_location ) || $args->theme_location !== 'primary' ) return $items;

    // Organigrama (with nested submenus) + Ranking
    $org_url        = home_url( '/organigrama/' );
    $consejo_url    = home_url( '/organigrama/consejo-directivo/' );
    $comisiones_url = home_url( '/organigrama/comisiones/' );
    $com_sel_url    = home_url( '/organigrama/comisiones/comision-selecciones-nacionales/' );
    $com_arb_url    = home_url( '/organigrama/comisiones/comision-arbitraje-jueceo/' );
    $com_evt_url    = home_url( '/organigrama/comisiones/comision-eventos/' );
    $asoc_url       = home_url( '/organigrama/asociaciones/' );
    $clubes_url     = home_url( '/organigrama/clubes/' );
    $ranking_url    = home_url( '/ranking/' );

    $items .= '<li class="menu-item menu-item-has-children fmdb-nav-organigrama">'
        . '<a href="' . esc_url( $org_url ) . '">Organigrama <span class="fmdb-nav-caret" aria-hidden="true">&#9662;</span></a>'
        . '<ul class="sub-menu fmdb-nav-submenu">'
            . '<li class="menu-item"><a href="' . esc_url( $consejo_url ) . '">Consejo directivo</a></li>'
            . '<li class="menu-item menu-item-has-children fmdb-nav-comisiones">'
                . '<a href="' . esc_url( $comisiones_url ) . '">Comisiones <span class="fmdb-nav-caret fmdb-nav-caret--right" aria-hidden="true">&#9656;</span></a>'
                . '<ul class="sub-menu fmdb-nav-submenu fmdb-nav-submenu--nested">'
                    . '<li class="menu-item"><a href="' . esc_url( $com_sel_url ) . '">Comisión de selecciones nacionales</a></li>'
                    . '<li class="menu-item"><a href="' . esc_url( $com_arb_url ) . '">Comisión de arbitraje y jueceo</a></li>'
                    . '<li class="menu-item"><a href="' . esc_url( $com_evt_url ) . '">Comisión de eventos</a></li>'
                . '</ul>'
            . '</li>'
            . '<li class="menu-item"><a href="' . esc_url( $asoc_url ) . '">Asociaciones</a></li>'
            . '<li class="menu-item"><a href="' . esc_url( $clubes_url ) . '">Clubes</a></li>'
        . '</ul>'
        . '</li>';
    $items .= '<li class="menu-item fmdb-nav-ranking">'
        . '<a href="' . esc_url( $ranking_url ) . '">Ranking</a>'
        . '</li>';

    $tienda_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
    $items .= '<li class="menu-item fmdb-nav-tienda">'
        . '<a href="' . esc_url( $tienda_url ) . '">Tienda</a>'
        . '</li>';
    $items .= '<li class="menu-item fmdb-nav-afiliacion">'
        . '<a href="https://dodgeball.mx/login" target="_blank" rel="noopener noreferrer">Afiliación</a>'
        . '</li>';

    // Build cart icon once — appended last so it stays rightmost
    $cart_li = '';
    if ( function_exists( 'WC' ) && WC()->cart ) {
        $cart_count = WC()->cart->get_cart_contents_count();
        $cart_url   = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
        $cart_li    = '<li class="fmdb-nav-cart">'
            . '<a href="' . esc_url( $cart_url ) . '" class="fmdb-nav-cart__link" aria-label="Carrito de compras">'
            . '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>'
            . '<span class="fmdb-nav-cart__count' . ( $cart_count ? ' has-items' : '' ) . '">' . $cart_count . '</span>'
            . '</a>'
            . '</li>';
    }

    if ( ! is_user_logged_in() ) {
        $items .= '<li class="fmdb-nav-login">'
            . '<a href="' . esc_url( wp_login_url( home_url( '/mi-equipo/' ) ) ) . '" class="fmdb-nav-login__link">Iniciar sesión</a>'
            . '</li>';
        return $items . $cart_li;
    }

    $user    = wp_get_current_user();
    $initial = esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) );
    $name    = esc_html( $user->display_name );
    $logout  = esc_url( wp_logout_url( home_url( '/' ) ) );
    $dash    = esc_url( home_url( '/mi-equipo/' ) );
    $perfil  = esc_url( home_url( '/mi-perfil/' ) );

    $pic_id  = get_user_meta( $user->ID, 'fmdb_profile_picture', true );
    $pic_url = $pic_id ? wp_get_attachment_image_url( (int) $pic_id, 'thumbnail' ) : '';
    $avatar  = $pic_url
        ? '<img src="' . esc_url( $pic_url ) . '" class="fmdb-nav-profile__avatar fmdb-nav-profile__avatar--img" alt="">'
        : '<span class="fmdb-nav-profile__avatar">' . $initial . '</span>';

    $items .= '<li class="fmdb-nav-profile">'
        . '<button type="button" class="fmdb-nav-profile__toggle" aria-expanded="false" aria-haspopup="true">'
        . $avatar
        . '<span class="fmdb-nav-profile__name">' . $name . '</span>'
        . '<span class="fmdb-nav-profile__caret" aria-hidden="true">&#9662;</span>'
        . '</button>'
        . '<ul class="fmdb-nav-profile__dropdown" role="menu">'
        . '<li role="none"><a href="' . $perfil . '" role="menuitem">Mi Perfil</a></li>'
        . '<li role="none"><a href="' . $dash . '" role="menuitem">Mis Equipos</a></li>'
        . '<li class="fmdb-nav-profile__dropdown-divider" role="none"></li>'
        . '<li role="none"><a href="' . $logout . '" class="fmdb-nav-profile__dropdown-logout" role="menuitem">Cerrar Sesión</a></li>'
        . '</ul>'
        . '</li>';

    return $items . $cart_li;
}, 10, 2 );

// Dropdown toggle for nav profile
add_action( 'wp_footer', function () {
    if ( ! is_user_logged_in() ) return;
    ?>
    <script>
    (function () {
        document.addEventListener('click', function (e) {
            var toggle  = e.target.closest('.fmdb-nav-profile__toggle');
            var profile = document.querySelector('.fmdb-nav-profile');
            if (!profile) return;
            if (toggle) {
                var open = profile.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            } else if (!e.target.closest('.fmdb-nav-profile')) {
                profile.classList.remove('is-open');
                var t = profile.querySelector('.fmdb-nav-profile__toggle');
                if (t) t.setAttribute('aria-expanded', 'false');
            }
        });
    })();
    </script>
    <?php
} );

// Point wp_login_url() to the custom login page
add_filter( 'lostpassword_url', function () {
    return home_url( '/olvide-mi-contrasena/' );
} );

add_filter( 'login_url', function ( $url, $redirect ) {
    $custom = home_url( '/login/' );
    return $redirect ? add_query_arg( 'redirect_to', urlencode( $redirect ), $custom ) : $custom;
}, 10, 2 );

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

// Shortcode: [fmdb_map]
add_shortcode( 'fmdb_map', function () {
    $svg_path = get_stylesheet_directory() . '/assets/mexico-map.svg';
    if ( ! file_exists( $svg_path ) ) {
        return '<p>Mapa no disponible.</p>';
    }
    $svg = file_get_contents( $svg_path );
    return '<div class="fmdb-map-wrapper">'
        . $svg
        . '<div class="fmdb-map-legend">'
        . '<span><i style="background:#D3D1C7"></i>Sin equipos</span>'
        . '<span><i style="background:#9FE1CB"></i>1-2 equipos</span>'
        . '<span><i style="background:#5DCAA5"></i>3-5 equipos</span>'
        . '<span><i style="background:#1D9E75"></i>6-10 equipos</span>'
        . '<span><i style="background:#085041"></i>10+ equipos</span>'
        . '</div>'
        . '</div>';
} );

/* ===================================================================
 * Tournament bracket fields (CMB2)
 * ACF Free does not include the Repeater field, so the tournament
 * bracket UI is built with CMB2's group field instead.
 * Storage: serialized array of associative arrays under postmeta keys
 * `tournament_teams` and `tournament_matches`.
 * =================================================================== */
add_action( 'cmb2_init', function () {
    $cmb = new_cmb2_box( [
        'id'           => 'fmdb_tournament_box',
        'title'        => __( 'Bracket del torneo', 'fmdb' ),
        'object_types' => [ 'tribe_events' ],
        'context'      => 'normal',
        'priority'     => 'default',
    ] );

    $cmb->add_field( [
        'name' => __( 'Equipos participantes', 'fmdb' ),
        'desc' => __( 'Solo se mostrará públicamente cuando el evento tenga la categoría "Torneo".', 'fmdb' ),
        'id'   => 'tournament_teams',
        'type' => 'group',
        'options' => [
            'group_title'   => __( 'Equipo {#}', 'fmdb' ),
            'add_button'    => __( 'Añadir equipo', 'fmdb' ),
            'remove_button' => __( 'Eliminar equipo', 'fmdb' ),
            'sortable'      => true,
        ],
    ] );
    $cmb->add_group_field( 'tournament_teams', [
        'name' => __( 'Nombre del equipo', 'fmdb' ),
        'id'   => 'team_name',
        'type' => 'text',
    ] );

    $cmb->add_field( [
        'name' => __( 'Partidos', 'fmdb' ),
        'id'   => 'tournament_matches',
        'type' => 'group',
        'options' => [
            'group_title'   => __( 'Partido {#}', 'fmdb' ),
            'add_button'    => __( 'Añadir partido', 'fmdb' ),
            'remove_button' => __( 'Eliminar partido', 'fmdb' ),
            'sortable'      => true,
        ],
    ] );
    $cmb->add_group_field( 'tournament_matches', [
        'name'    => __( 'Ronda', 'fmdb' ),
        'id'      => 'match_round',
        'type'    => 'select',
        'options' => [
            'grupos'  => __( 'Fase de grupos', 'fmdb' ),
            'octavos' => __( 'Octavos de final', 'fmdb' ),
            'cuartos' => __( 'Cuartos de final', 'fmdb' ),
            'semis'   => __( 'Semifinal', 'fmdb' ),
            'tercero' => __( 'Tercer lugar', 'fmdb' ),
            'final'   => __( 'Final', 'fmdb' ),
        ],
        'default' => 'cuartos',
    ] );
    $cmb->add_group_field( 'tournament_matches', [
        'name' => __( 'Equipo A', 'fmdb' ),
        'id'   => 'team_a_name',
        'type' => 'text',
    ] );
    $cmb->add_group_field( 'tournament_matches', [
        'name' => __( 'Puntos A', 'fmdb' ),
        'id'   => 'score_a',
        'type' => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0' ],
    ] );
    $cmb->add_group_field( 'tournament_matches', [
        'name' => __( 'Equipo B', 'fmdb' ),
        'id'   => 'team_b_name',
        'type' => 'text',
    ] );
    $cmb->add_group_field( 'tournament_matches', [
        'name' => __( 'Puntos B', 'fmdb' ),
        'id'   => 'score_b',
        'type' => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0' ],
    ] );
    $cmb->add_group_field( 'tournament_matches', [
        'name' => __( 'Jugado', 'fmdb' ),
        'id'   => 'match_played',
        'type' => 'checkbox',
    ] );
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

/* ===================================================================
 * Team roster (Plantel) — CMB2 group on fmdb_team
 * Replaces ACF Repeater (Pro-only). Storage: serialized array under
 * postmeta `team_roster`.
 * =================================================================== */
add_action( 'cmb2_init', function () {
    $cmb = new_cmb2_box( [
        'id'           => 'fmdb_team_roster_box',
        'title'        => __( 'Plantel', 'fmdb' ),
        'object_types' => [ 'fmdb_team' ],
        'context'      => 'normal',
        'priority'     => 'default',
        'cmb_styles'   => true,
    ] );

    $cmb->add_field( [
        'name'    => __( 'Jugadores', 'fmdb' ),
        'id'      => 'team_roster',
        'type'    => 'group',
        'options' => [
            'group_title'   => __( 'Jugador {#}', 'fmdb' ),
            'add_button'    => __( 'Añadir jugador', 'fmdb' ),
            'remove_button' => __( 'Eliminar jugador', 'fmdb' ),
            'sortable'      => false,
        ],
    ] );
    $cmb->add_group_field( 'team_roster', [
        'name'       => __( '#', 'fmdb' ),
        'id'         => 'number',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0' ],
    ] );
    $cmb->add_group_field( 'team_roster', [
        'name' => __( 'Nombre', 'fmdb' ),
        'id'   => 'name',
        'type' => 'text',
    ] );
    $cmb->add_group_field( 'team_roster', [
        'name'        => __( 'Email del jugador', 'fmdb' ),
        'id'          => 'user_email',
        'type'        => 'text',
        'description' => __( 'Vincula a la cuenta FMDB del jugador por su correo registrado.', 'fmdb' ),
        'attributes'  => [ 'type' => 'email', 'placeholder' => 'correo@ejemplo.com' ],
    ] );
    $cmb->add_group_field( 'team_roster', [
        'name' => __( 'Posición', 'fmdb' ),
        'id'   => 'position',
        'type' => 'text',
    ] );
    $cmb->add_group_field( 'team_roster', [
        'name'    => __( 'Rol', 'fmdb' ),
        'id'      => 'role',
        'type'    => 'select',
        'options' => [ 'Titular' => __( 'Titular', 'fmdb' ), 'Suplente' => __( 'Suplente', 'fmdb' ) ],
    ] );
    $cmb->add_group_field( 'team_roster', [
        'name' => __( 'Capitán', 'fmdb' ),
        'id'   => 'is_captain',
        'type' => 'checkbox',
    ] );
} );

/* ===================================================================
 * Team results (Resultados) — CMB2 group on fmdb_team
 * =================================================================== */
add_action( 'cmb2_init', function () {
    $cmb = new_cmb2_box( [
        'id'           => 'fmdb_team_results_box',
        'title'        => __( 'Resultados', 'fmdb' ),
        'object_types' => [ 'fmdb_team' ],
        'context'      => 'normal',
        'priority'     => 'default',
    ] );

    $cmb->add_field( [
        'name'    => __( 'Partidos', 'fmdb' ),
        'id'      => 'team_results',
        'type'    => 'group',
        'options' => [
            'group_title'   => __( 'Partido {#}', 'fmdb' ),
            'add_button'    => __( 'Añadir resultado', 'fmdb' ),
            'remove_button' => __( 'Eliminar resultado', 'fmdb' ),
            'sortable'      => false,
        ],
    ] );
    $cmb->add_group_field( 'team_results', [
        'name'        => __( 'Fecha', 'fmdb' ),
        'id'          => 'date',
        'type'        => 'text_date',
        'date_format' => 'Y-m-d',
    ] );
    $cmb->add_group_field( 'team_results', [
        'name' => __( 'Rival', 'fmdb' ),
        'id'   => 'opponent',
        'type' => 'text',
    ] );
    $cmb->add_group_field( 'team_results', [
        'name' => __( 'Evento', 'fmdb' ),
        'id'   => 'event',
        'type' => 'text',
    ] );
    $cmb->add_group_field( 'team_results', [
        'name'        => __( 'Marcador', 'fmdb' ),
        'id'          => 'score',
        'type'        => 'text_small',
        'attributes'  => [ 'placeholder' => 'ej. 3-1' ],
    ] );
    $cmb->add_group_field( 'team_results', [
        'name'    => __( 'Resultado', 'fmdb' ),
        'id'      => 'outcome',
        'type'    => 'select',
        'options' => [ 'W' => __( 'Victoria', 'fmdb' ), 'L' => __( 'Derrota', 'fmdb' ) ],
    ] );
} );

/* ===================================================================
 * Event PDFs (Documentos) — CMB2 group on tribe_events
 * =================================================================== */
add_action( 'cmb2_init', function () {
    $cmb = new_cmb2_box( [
        'id'           => 'fmdb_event_pdfs_box',
        'title'        => __( 'Documentos', 'fmdb' ),
        'object_types' => [ 'tribe_events' ],
        'context'      => 'normal',
        'priority'     => 'default',
    ] );

    $cmb->add_field( [
        'name'    => __( 'Archivos PDF', 'fmdb' ),
        'id'      => 'event_pdfs',
        'type'    => 'group',
        'options' => [
            'group_title'   => __( 'Documento {#}', 'fmdb' ),
            'add_button'    => __( 'Añadir documento', 'fmdb' ),
            'remove_button' => __( 'Eliminar documento', 'fmdb' ),
            'sortable'      => true,
        ],
    ] );
    $cmb->add_group_field( 'event_pdfs', [
        'name'         => __( 'Archivo', 'fmdb' ),
        'id'           => 'url',
        'type'         => 'file',
        'options'      => [ 'url' => false ],
        'text'         => [ 'add_upload_file_text' => __( 'Subir PDF', 'fmdb' ) ],
        'query_args'   => [ 'type' => 'application/pdf' ],
        'preview_size' => 'medium',
    ] );
    $cmb->add_group_field( 'event_pdfs', [
        'name' => __( 'Nombre', 'fmdb' ),
        'id'   => 'title',
        'type' => 'text',
    ] );
} );

// ACF field group: Consejo Directivo members (repeater on the Consejo Directivo page)
add_action( 'acf/init', function () {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

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
                [ 'param' => 'page', 'operator' => '==', 'value' => '116' ],
            ],
        ],
    ] );
} );

/**
 * Guest shop access — products visible to anyone, but add-to-cart is gated by login.
 * - Server-side: woocommerce_add_to_cart_validation blocks the request, adds a notice with a login link
 * - UI: replace "Añadir al carrito" with "Iniciar sesión" for guests on the shop archive
 * - UI: replace single-product add-to-cart form with a login CTA
 */
add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id ) {
    if ( is_user_logged_in() ) return $passed;
    $login_url = wp_login_url( get_permalink( $product_id ) ?: wc_get_page_permalink( 'shop' ) );
    if ( function_exists( 'wc_add_notice' ) ) {
        wc_add_notice(
            sprintf(
                'Debes <a href="%s"><strong>iniciar sesión</strong></a> para agregar productos al carrito.',
                esc_url( $login_url )
            ),
            'error'
        );
    }
    return false;
}, 10, 2 );

// Shop archive: replace the add-to-cart link with an "Iniciar sesión" CTA for guests
add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
    if ( is_user_logged_in() ) return $html;
    $login_url = wp_login_url( get_permalink( $product->get_id() ) );
    return sprintf(
        '<a href="%1$s" class="button fmdb-shop-login-cta" rel="nofollow" aria-label="%3$s">'
        . '<span class="fmdb-cta-text fmdb-cta-text--idle">%2$s</span>'
        . '<span class="fmdb-cta-text fmdb-cta-text--hover">%3$s</span>'
        . '</a>',
        esc_url( $login_url ),
        esc_html__( 'Agregar al carrito', 'fmdb' ),
        esc_html__( 'Iniciar sesión para comprar', 'fmdb' )
    );
}, 10, 2 );

// Single product page: swap the add-to-cart form for a login CTA when logged out
add_action( 'woocommerce_single_product_summary', function () {
    if ( is_user_logged_in() ) return;
    global $product;
    if ( ! $product ) return;
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
    add_action( 'woocommerce_single_product_summary', function () {
        $login_url = wp_login_url( get_permalink() );
        echo '<div class="fmdb-shop-login-prompt">';
        echo '<p class="fmdb-shop-login-prompt__msg">Inicia sesión para agregar este producto al carrito.</p>';
        printf(
            '<a href="%s" class="button alt fmdb-shop-login-cta">%s</a>',
            esc_url( $login_url ),
            esc_html__( 'Iniciar sesión', 'fmdb' )
        );
        $reg = home_url( '/registro/' );
        printf(
            '<a href="%s" class="fmdb-shop-login-prompt__register">%s</a>',
            esc_url( $reg ),
            esc_html__( '¿No tienes cuenta? Regístrate', 'fmdb' )
        );
        echo '</div>';
    }, 30 );
}, 1 );

// Disable comments and pings on news posts
add_filter( 'comments_open', function ( $open, $post_id ) {
    if ( get_post_type( $post_id ) === 'post' ) return false;
    return $open;
}, 10, 2 );

add_filter( 'pings_open', function ( $open, $post_id ) {
    if ( get_post_type( $post_id ) === 'post' ) return false;
    return $open;
}, 10, 2 );
