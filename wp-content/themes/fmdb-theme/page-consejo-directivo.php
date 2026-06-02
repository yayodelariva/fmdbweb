<?php
/**
 * Template: Consejo Directivo
 * Slug-based page template — auto-applied to the page with slug "consejo-directivo"
 */
get_header();

while ( have_posts() ) : the_post();
    fmdb_render_org_page( [
        'lede'          => 'Conoce a las personas que conforman el Consejo Directivo de la Federación Mexicana de Dodgeball.',
        'empty_message' => 'Los miembros del Consejo Directivo se publicarán próximamente.',
        'placeholders'  => [
            [ 'member_name' => 'María Fernanda Ortiz',  'member_position' => 'Presidenta',         'member_bio' => 'Lidera la federación desde 2023, con enfoque en el crecimiento del dodgeball nacional.', 'member_photo' => 'https://i.pravatar.cc/400?img=47' ],
            [ 'member_name' => 'Carlos Hernández Vega', 'member_position' => 'Vicepresidente',     'member_bio' => 'Coordina la relación con asociaciones estatales y desarrollo de ligas.',                'member_photo' => 'https://i.pravatar.cc/400?img=12' ],
            [ 'member_name' => 'Diana Rojas',           'member_position' => 'Secretaria General', 'member_bio' => 'Responsable de la administración y comunicación oficial de la FMDB.',                  'member_photo' => 'https://i.pravatar.cc/400?img=32' ],
            [ 'member_name' => 'Andrés Patiño',         'member_position' => 'Tesorero',           'member_bio' => 'Supervisa finanzas, presupuesto y reportes anuales de la federación.',                'member_photo' => 'https://i.pravatar.cc/400?img=15' ],
            [ 'member_name' => 'Lucía Mendoza',         'member_position' => 'Directora Deportiva','member_bio' => 'A cargo de selecciones nacionales y desarrollo de talento competitivo.',             'member_photo' => 'https://i.pravatar.cc/400?img=45' ],
            [ 'member_name' => 'Roberto Salinas',       'member_position' => 'Director de Eventos','member_bio' => 'Organiza torneos nacionales, clínicas y eventos oficiales de la federación.',        'member_photo' => 'https://i.pravatar.cc/400?img=58' ],
        ],
    ] );
endwhile;

get_footer();
