<?php
/**
 * Custom post types: fmdb_team, fmdb_league, fmdb_seleccion.
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
