<?php
/**
 * Per-page conditional CSS/JS enqueuing with filemtime() cache-busting.
 * Also localizes per-state team/league counts to the map JS.
 */

add_action( 'wp_enqueue_scripts', function () {
    // Per-asset cache-busting: filemtime() so each edit invalidates the browser cache.
    $ver = function ( $rel ) {
        $path = get_stylesheet_directory() . '/' . ltrim( $rel, '/' );
        return file_exists( $path ) ? (string) filemtime( $path ) : wp_get_theme()->get( 'Version' );
    };

    // Nunito webfont — used for the desktop + mobile nav menus.
    wp_enqueue_style( 'fmdb-nunito', 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap', [], null );

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
    if ( is_page( 'mapa-interactivo' ) ) {
        wp_enqueue_style(  'fmdb-equipos', get_stylesheet_directory_uri() . '/assets/css/equipos.css', [], $ver( 'assets/css/equipos.css' ) );
        wp_enqueue_script( 'fmdb-equipos', get_stylesheet_directory_uri() . '/assets/js/equipos.js', [ 'fmdb-map' ], $ver( 'assets/js/equipos.js' ), true );
    }
    if ( is_front_page() ) {
        wp_enqueue_style(  'fmdb-home', get_stylesheet_directory_uri() . '/assets/css/home.css', [], $ver( 'assets/css/home.css' ) );
        wp_enqueue_script( 'fmdb-home', get_stylesheet_directory_uri() . '/assets/js/home.js', [ 'fmdb-map' ], $ver( 'assets/js/home.js' ), true );
    }
    if ( is_404() ) {
        wp_enqueue_style( 'fmdb-home', get_stylesheet_directory_uri() . '/assets/css/home.css', [], $ver( 'assets/css/home.css' ) );
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

    // Pass per-state team, league and asociación counts to JS: [ 'Estado' => count ]
    $team_counts       = [];
    $league_counts     = [];
    $asociacion_counts = [];
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
        $asociaciones = get_posts( [ 'post_type' => 'fmdb_asociacion', 'posts_per_page' => -1, 'post_status' => 'publish' ] );
        foreach ( $asociaciones as $asoc ) {
            $state = get_field( 'asociacion_state', $asoc->ID );
            if ( $state ) {
                $asociacion_counts[ $state ] = ( $asociacion_counts[ $state ] ?? 0 ) + 1;
            }
        }
    }
    wp_localize_script( 'fmdb-map', 'fmdbMapData', [
        'teams'        => $team_counts,
        'leagues'      => $league_counts,
        'asociaciones' => $asociacion_counts,
    ] );
} );
