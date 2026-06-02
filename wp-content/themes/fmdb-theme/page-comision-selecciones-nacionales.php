<?php
/**
 * Template: Comisión de Selecciones Nacionales
 */
get_header();

while ( have_posts() ) : the_post();
    fmdb_render_org_page( [
        'lede'          => 'La comisión encargada de la conformación, preparación y representación de las selecciones nacionales de México.',
        'empty_message' => 'Los miembros de la comisión se publicarán próximamente.',
        'placeholders'  => [
            [ 'member_name' => 'Jorge Camacho',     'member_position' => 'Director de Comisión',     'member_bio' => 'Dirige la planeación competitiva de las selecciones nacionales.',          'member_photo' => 'https://i.pravatar.cc/400?img=11' ],
            [ 'member_name' => 'Paola Estrada',     'member_position' => 'Head Coach Selección Femenil', 'member_bio' => 'Responsable de la preparación técnica de la selección femenil.',     'member_photo' => 'https://i.pravatar.cc/400?img=20' ],
            [ 'member_name' => 'Iván Domínguez',    'member_position' => 'Head Coach Selección Varonil', 'member_bio' => 'Encargado de la preparación técnica de la selección varonil.',       'member_photo' => 'https://i.pravatar.cc/400?img=33' ],
            [ 'member_name' => 'Sofía Aguilar',     'member_position' => 'Preparadora Física',       'member_bio' => 'Diseña y supervisa el acondicionamiento físico de las selecciones.',     'member_photo' => 'https://i.pravatar.cc/400?img=49' ],
            [ 'member_name' => 'Mateo Beltrán',     'member_position' => 'Analista de Video',        'member_bio' => 'Analiza partidos y rivales para apoyar la estrategia del cuerpo técnico.','member_photo' => 'https://i.pravatar.cc/400?img=64' ],
        ],
    ] );
endwhile;

get_footer();
