<?php
/**
 * Template: Comisión de Arbitraje y Jueceo
 */
get_header();

while ( have_posts() ) : the_post();
    fmdb_render_org_page( [
        'lede'          => 'La comisión responsable de la formación, certificación y asignación de árbitros y jueces en los eventos oficiales de la FMDB.',
        'empty_message' => 'Los miembros de la comisión se publicarán próximamente.',
        'placeholders'  => [
            [ 'member_name' => 'Ricardo Núñez',  'member_position' => 'Director de Comisión',  'member_bio' => 'Coordina los procesos de arbitraje y certificación a nivel nacional.', 'member_photo' => 'https://i.pravatar.cc/400?img=8'  ],
            [ 'member_name' => 'Adriana Ríos',   'member_position' => 'Coordinadora de Jueces', 'member_bio' => 'Encargada de la designación y evaluación de jueces en torneos oficiales.', 'member_photo' => 'https://i.pravatar.cc/400?img=23' ],
            [ 'member_name' => 'Héctor Cordero', 'member_position' => 'Jefe de Capacitación',  'member_bio' => 'Diseña los programas de formación y certificación de árbitros.',       'member_photo' => 'https://i.pravatar.cc/400?img=53' ],
            [ 'member_name' => 'Marcela Téllez', 'member_position' => 'Árbitra Internacional', 'member_bio' => 'Representa al país en torneos internacionales y asesora al cuerpo arbitral nacional.', 'member_photo' => 'https://i.pravatar.cc/400?img=29' ],
        ],
    ] );
endwhile;

get_footer();
