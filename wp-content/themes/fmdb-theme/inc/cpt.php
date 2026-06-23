<?php
/**
 * Custom post types: fmdb_team, fmdb_league, fmdb_asociacion, fmdb_seleccion.
 */

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

    register_post_type( 'fmdb_asociacion', [
        'labels' => [
            'name'               => 'Asociaciones',
            'singular_name'      => 'Asociación',
            'add_new'            => 'Añadir asociación',
            'add_new_item'       => 'Añadir nueva asociación',
            'edit_item'          => 'Editar asociación',
            'view_item'          => 'Ver asociación',
            'search_items'       => 'Buscar asociaciones',
            'not_found'          => 'No se encontraron asociaciones',
            'not_found_in_trash' => 'No hay asociaciones en la papelera',
        ],
        'public'       => true,
        'has_archive'  => false,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-flag',
        'supports'     => [ 'title', 'thumbnail', 'custom-fields' ],
        'rewrite'      => [ 'slug' => 'asociaciones' ],
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

    // Rename "Imagen destacada" to "Logo" for teams and leagues
    add_filter( 'admin_post_thumbnail_html', function ( $html, $post_id ) {
        $logos = [ 'fmdb_team', 'fmdb_league' ];
        if ( in_array( get_post_type( $post_id ), $logos, true ) ) {
            $html = str_replace(
                [ 'Imagen destacada', 'imagen destacada' ],
                [ 'Logo', 'logo' ],
                $html
            );
        }
        return $html;
    }, 10, 2 );
} );

// fmdb_team + fmdb_league admin: hide LiteSpeed + post settings, rename featured image box
add_action( 'add_meta_boxes', function () {
    foreach ( [ 'fmdb_team', 'fmdb_league' ] as $pt ) {
        remove_meta_box( 'litespeed_meta_boxes', $pt, 'side' );
        remove_meta_box( 'litespeed_meta_boxes', $pt, 'normal' );
        remove_meta_box( 'litespeed_meta_boxes', $pt, 'advanced' );
        remove_meta_box( 'postimagediv', $pt, 'side' );
        add_meta_box( 'postimagediv', 'Logo', 'post_thumbnail_meta_box', $pt, 'side', 'low' );
    }
}, 99 );

add_action( 'admin_head', function () {
    $screen = get_current_screen();
    if ( ! $screen || ! in_array( $screen->post_type, [ 'fmdb_team', 'fmdb_league' ], true ) ) return;
    echo '<style>#kadence_classic_meta_control{display:none!important}</style>';
} );
