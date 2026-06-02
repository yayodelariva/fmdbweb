<?php
/**
 * Template: Comisión de Eventos
 */
get_header();

while ( have_posts() ) : the_post();
    fmdb_render_org_page( [
        'lede'          => 'La comisión encargada de la planeación, organización y logística de los eventos oficiales de la FMDB.',
        'empty_message' => 'Los miembros de la comisión se publicarán próximamente.',
        'placeholders'  => [
            [ 'member_name' => 'Tomás Villaseñor', 'member_position' => 'Director de Comisión',   'member_bio' => 'Coordina el calendario nacional de eventos y torneos oficiales.',        'member_photo' => 'https://i.pravatar.cc/400?img=14' ],
            [ 'member_name' => 'Renata Cárdenas',  'member_position' => 'Coordinadora de Logística','member_bio' => 'Responsable de la operación y logística en cada evento.',              'member_photo' => 'https://i.pravatar.cc/400?img=26' ],
            [ 'member_name' => 'Felipe Garza',     'member_position' => 'Coordinador de Sedes',    'member_bio' => 'Gestiona la relación con sedes, venues y proveedores locales.',          'member_photo' => 'https://i.pravatar.cc/400?img=37' ],
            [ 'member_name' => 'Valentina Murillo','member_position' => 'Coordinadora de Patrocinios','member_bio' => 'Encargada del desarrollo y activación de patrocinios para eventos.',  'member_photo' => 'https://i.pravatar.cc/400?img=43' ],
        ],
    ] );
endwhile;

get_footer();
