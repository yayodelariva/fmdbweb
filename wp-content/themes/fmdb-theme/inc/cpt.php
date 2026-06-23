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

    // Rename "Imagen destacada" to "Logo del equipo" for fmdb_team
    add_filter( 'admin_post_thumbnail_html', function ( $html, $post_id ) {
        if ( get_post_type( $post_id ) === 'fmdb_team' ) {
            $html = str_replace(
                [ 'Imagen destacada', 'imagen destacada' ],
                [ 'Logo del equipo', 'logo del equipo' ],
                $html
            );
        }
        return $html;
    }, 10, 2 );
} );

// fmdb_team admin: hide LiteSpeed + post settings, rename featured image box
add_action( 'add_meta_boxes', function () {
    remove_meta_box( 'litespeed_meta_boxes', 'fmdb_team', 'side' );
    remove_meta_box( 'litespeed_meta_boxes', 'fmdb_team', 'normal' );
    remove_meta_box( 'litespeed_meta_boxes', 'fmdb_team', 'advanced' );

    // Remove the default featured image box and re-add with custom title
    remove_meta_box( 'postimagediv', 'fmdb_team', 'side' );
    add_meta_box( 'postimagediv', 'Logo del equipo', 'post_thumbnail_meta_box', 'fmdb_team', 'side', 'low' );
}, 99 );

add_action( 'admin_head', function () {
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'fmdb_team' ) return;
    // Hide Kadence post settings panel
    echo '<style>#kadence_classic_meta_control{display:none!important}</style>';
} );
