<?php
/**
 * CMB2 field groups used where ACF Free's missing Repeater would otherwise
 * be needed. Storage is serialized postmeta keyed by the group id.
 *
 * Groups:
 *  - tribe_events: tournament_teams, tournament_matches, event_pdfs
 *  - fmdb_team:    team_roster, team_results
 */

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
 * Organigrama "Miembros" repeater — CMB2 group on Organigrama people-grid
 * pages (Consejo Directivo, the three Comisiones, Asociaciones).
 *
 * Originally built as an ACF Repeater, but ACF Free does not include the
 * Repeater field. Same field name (`org_members`) and sub-field names so
 * the shared render helper in inc/helpers.php works unchanged.
 * Storage: serialized array under postmeta `org_members`.
 * =================================================================== */
add_action( 'cmb2_init', function () {
    $cmb = new_cmb2_box( [
        'id'           => 'fmdb_org_members_box',
        'title'        => __( 'Miembros', 'fmdb' ),
        'object_types' => [ 'page' ],
        'context'      => 'normal',
        'priority'     => 'default',
        'show_on_cb'   => function ( $cmb ) {
            $page_paths = [
                'organigrama/consejo-directivo',
                'organigrama/comisiones/comision-selecciones-nacionales',
                'organigrama/comisiones/comision-arbitraje-jueceo',
                'organigrama/comisiones/comision-eventos',
                'organigrama/asociaciones',
            ];
            $allowed = [];
            foreach ( $page_paths as $path ) {
                $p = get_page_by_path( $path );
                if ( $p ) $allowed[] = (int) $p->ID;
            }
            return in_array( (int) $cmb->object_id(), $allowed, true );
        },
    ] );

    $cmb->add_field( [
        'name'    => __( 'Miembros', 'fmdb' ),
        'id'      => 'org_members',
        'type'    => 'group',
        'options' => [
            'group_title'   => __( 'Miembro {#}', 'fmdb' ),
            'add_button'    => __( 'Añadir miembro', 'fmdb' ),
            'remove_button' => __( 'Eliminar miembro', 'fmdb' ),
            'sortable'      => true,
        ],
    ] );
    $cmb->add_group_field( 'org_members', [
        'name' => __( 'Nombre', 'fmdb' ),
        'id'   => 'member_name',
        'type' => 'text',
    ] );
    $cmb->add_group_field( 'org_members', [
        'name'        => __( 'Cargo', 'fmdb' ),
        'desc'        => __( 'Ej. Presidente, Vicepresidente, Representante por Jalisco…', 'fmdb' ),
        'id'          => 'member_position',
        'type'        => 'text',
    ] );
    $cmb->add_group_field( 'org_members', [
        'name'         => __( 'Foto', 'fmdb' ),
        'id'           => 'member_photo',
        'type'         => 'file',
        'options'      => [ 'url' => false ],
        'text'         => [ 'add_upload_file_text' => __( 'Subir foto', 'fmdb' ) ],
        'query_args'   => [ 'type' => 'image' ],
        'preview_size' => 'medium',
    ] );
    $cmb->add_group_field( 'org_members', [
        'name' => __( 'Biografía corta', 'fmdb' ),
        'id'   => 'member_bio',
        'type' => 'textarea_small',
    ] );
} );

/* ===================================================================
 * Extra event dates — CMB2 group on tribe_events
 * One main event with multiple non-contiguous occurrences. Each row
 * adds another date that appears as its own card/dot in the calendar,
 * all linking back to the same event post. Time/note are optional
 * overrides; when blank they inherit from the main event.
 * Storage: serialized array under postmeta `event_extra_dates`.
 * =================================================================== */
add_action( 'cmb2_init', function () {
    $cmb = new_cmb2_box( [
        'id'           => 'fmdb_event_extra_dates_box',
        'title'        => __( 'Fechas adicionales', 'fmdb' ),
        'object_types' => [ 'tribe_events' ],
        'context'      => 'normal',
        'priority'     => 'default',
    ] );

    $cmb->add_field( [
        'name'        => __( 'Otras fechas del evento', 'fmdb' ),
        'desc'        => __( 'Cada fecha aparece como una tarjeta y un punto adicional en el calendario, todas enlazadas al mismo evento. Deja el horario en blanco para reutilizar el del evento principal.', 'fmdb' ),
        'id'          => 'event_extra_dates',
        'type'        => 'group',
        'options'     => [
            'group_title'   => __( 'Fecha {#}', 'fmdb' ),
            'add_button'    => __( 'Añadir fecha', 'fmdb' ),
            'remove_button' => __( 'Eliminar fecha', 'fmdb' ),
            'sortable'      => true,
        ],
    ] );
    $cmb->add_group_field( 'event_extra_dates', [
        'name'        => __( 'Fecha', 'fmdb' ),
        'id'          => 'date',
        'type'        => 'text_date',
        'date_format' => 'Y-m-d',
    ] );
    $cmb->add_group_field( 'event_extra_dates', [
        'name'        => __( 'Hora de inicio (opcional)', 'fmdb' ),
        'id'          => 'start_time',
        'type'        => 'text_time',
        'time_format' => 'g:i a',
    ] );
    $cmb->add_group_field( 'event_extra_dates', [
        'name'        => __( 'Hora de fin (opcional)', 'fmdb' ),
        'id'          => 'end_time',
        'type'        => 'text_time',
        'time_format' => 'g:i a',
    ] );
    $cmb->add_group_field( 'event_extra_dates', [
        'name'        => __( 'Nota (opcional)', 'fmdb' ),
        'desc'        => __( 'Ej. "Inauguración", "Jornada 2", "Final".', 'fmdb' ),
        'id'          => 'note',
        'type'        => 'text',
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
