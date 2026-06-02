<?php
/**
 * Template: Asociaciones
 */
get_header();

while ( have_posts() ) : the_post();
    fmdb_render_org_page( [
        'lede'          => 'Las asociaciones estatales son la base organizativa de la FMDB. Conoce a sus representantes en todo el país.',
        'empty_message' => 'Las asociaciones se publicarán próximamente.',
        'placeholders'  => [
            [ 'member_name' => 'Alejandro Vidal',  'member_position' => 'Asociación de Jalisco',        'member_bio' => 'Representante de la asociación de Jalisco, una de las más activas del país.', 'member_photo' => 'https://i.pravatar.cc/400?img=5'  ],
            [ 'member_name' => 'Marisol Aguirre',  'member_position' => 'Asociación de Nuevo León',     'member_bio' => 'Impulsa el crecimiento del dodgeball en el noreste del país.',                'member_photo' => 'https://i.pravatar.cc/400?img=24' ],
            [ 'member_name' => 'David Quintero',   'member_position' => 'Asociación de la CDMX',        'member_bio' => 'Coordina ligas y torneos en la capital del país.',                            'member_photo' => 'https://i.pravatar.cc/400?img=18' ],
            [ 'member_name' => 'Karla Mendoza',    'member_position' => 'Asociación de Querétaro',      'member_bio' => 'Encargada del desarrollo del dodgeball juvenil en el Bajío.',                'member_photo' => 'https://i.pravatar.cc/400?img=36' ],
            [ 'member_name' => 'Eduardo Pizarro',  'member_position' => 'Asociación de Yucatán',        'member_bio' => 'Representa al sureste mexicano en el consejo de asociaciones.',              'member_photo' => 'https://i.pravatar.cc/400?img=51' ],
            [ 'member_name' => 'Brenda Solís',     'member_position' => 'Asociación de Baja California','member_bio' => 'Coordina el crecimiento del dodgeball en la frontera norte.',                 'member_photo' => 'https://i.pravatar.cc/400?img=40' ],
        ],
    ] );
endwhile;

get_footer();
