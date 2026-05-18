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
