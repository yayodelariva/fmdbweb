<?php
/**
 * Tournament registration — links a TEC "torneo" event to a WooCommerce
 * virtual product so teams can register and pay the per-player fee at checkout.
 *
 * Admin:    CMB2 meta box on tribe_events for fee, deadline, max teams, divisions.
 * Backend:  auto-creates a hidden WC product synced to event meta.
 * Frontend: registration form in the event sidebar; team data flows
 *           through WC cart → order line-item meta.
 */

/* ─── 1. Admin: registration settings ─────────────────────────────────── */

add_action( 'cmb2_init', function () {
    $cmb = new_cmb2_box( [
        'id'           => 'fmdb_event_registration_box',
        'title'        => __( 'Inscripción', 'fmdb' ),
        'object_types' => [ 'tribe_events' ],
        'context'      => 'side',
        'priority'     => 'default',
    ] );

    $cmb->add_field( [
        'name' => __( 'Inscripción abierta', 'fmdb' ),
        'id'   => '_fmdb_reg_open',
        'type' => 'checkbox',
    ] );
    $cmb->add_field( [
        'name'       => __( 'Cuota por jugador (MXN)', 'fmdb' ),
        'id'         => '_fmdb_reg_fee',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0', 'step' => '0.01' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Acceso al venue por jugador (MXN)', 'fmdb' ),
        'desc'       => __( 'Cobro de entrada al venue. Se aplica igual a todos los jugadores. 0 = sin cobro.', 'fmdb' ),
        'id'         => '_fmdb_reg_entrada_fee',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0', 'step' => '0.01' ],
    ] );
    $cmb->add_field( [
        'name'        => __( 'Fecha límite inscripción', 'fmdb' ),
        'id'          => '_fmdb_reg_deadline',
        'type'        => 'text_date',
        'date_format' => 'Y-m-d',
    ] );
    $cmb->add_field( [
        'name'       => __( 'Cupo de equipos por rama/modalidad (Libre)', 'fmdb' ),
        'desc'       => __( '0 = sin límite. Aplica por cada Rama × Modalidad.', 'fmdb' ),
        'id'         => '_fmdb_reg_max_teams',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Cupo de equipos por rama/modalidad (Infantil)', 'fmdb' ),
        'desc'       => __( '0 = sin límite. Aplica por cada Rama × Modalidad en categoría Infantil.', 'fmdb' ),
        'id'         => '_fmdb_reg_max_teams_infantil',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Mínimo de jugadores por equipo', 'fmdb' ),
        'desc'       => __( 'Jugadores necesarios para que el equipo sea confirmado. Vacío = 6.', 'fmdb' ),
        'id'         => '_fmdb_reg_min_players',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '1', 'placeholder' => '6' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Máximo de jugadores por equipo', 'fmdb' ),
        'desc'       => __( 'Límite del plantel; se rechazan registros adicionales. Vacío = 10.', 'fmdb' ),
        'id'         => '_fmdb_reg_max_players',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '1', 'placeholder' => '10' ],
    ] );
    $cmb->add_field( [
        'name'    => __( 'Ramas', 'fmdb' ),
        'desc'    => __( 'Dejar vacío para mostrar todas. Cada rama incluye Foam y Cloth automáticamente.', 'fmdb' ),
        'id'      => '_fmdb_reg_ramas',
        'type'    => 'multicheck',
        'options' => [ 'Varonil/Mixto' => 'Varonil/Mixto', 'Femenil/Mixto' => 'Femenil/Mixto' ],
    ] );
    $cmb->add_field( [
        'name'    => __( 'Categorías', 'fmdb' ),
        'desc'    => __( 'Dejar vacío para mostrar todas.', 'fmdb' ),
        'id'      => '_fmdb_reg_categorias',
        'type'    => 'multicheck',
        'options' => [ 'Infantil' => 'Infantil (8-12 años)', 'Libre' => 'Libre (13+ años)' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Hospedaje – Habitación Sencilla (MXN)', 'fmdb' ),
        'id'         => '_fmdb_hospedaje_sencilla_fee',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0', 'step' => '0.01', 'placeholder' => '1200' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Hospedaje – Habitación Doble (MXN)', 'fmdb' ),
        'id'         => '_fmdb_hospedaje_doble_fee',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0', 'step' => '0.01', 'placeholder' => '1415' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Hospedaje – Habitación Triple (MXN)', 'fmdb' ),
        'id'         => '_fmdb_hospedaje_triple_fee',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0', 'step' => '0.01', 'placeholder' => '1355' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Hospedaje – Habitación Cuádruple (MXN)', 'fmdb' ),
        'id'         => '_fmdb_hospedaje_cuadruple_fee',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0', 'step' => '0.01', 'placeholder' => '1500' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Hospedaje – Habitación Sencilla Solo Cuarto (MXN)', 'fmdb' ),
        'id'         => '_fmdb_hospedaje_sencilla_sc_fee',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0', 'step' => '0.01', 'placeholder' => '700' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Hospedaje – Habitación Doble Solo Cuarto (MXN)', 'fmdb' ),
        'id'         => '_fmdb_hospedaje_doble_sc_fee',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0', 'step' => '0.01', 'placeholder' => '800' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Hospedaje – Habitación Triple Solo Cuarto (MXN)', 'fmdb' ),
        'id'         => '_fmdb_hospedaje_triple_sc_fee',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0', 'step' => '0.01', 'placeholder' => '750' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Hospedaje – Habitación Cuádruple Solo Cuarto (MXN)', 'fmdb' ),
        'id'         => '_fmdb_hospedaje_cuadruple_sc_fee',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0', 'step' => '0.01', 'placeholder' => '900' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Hospedaje – Cupo Habitación Sencilla', 'fmdb' ),
        'desc'       => __( '0 = sin límite.', 'fmdb' ),
        'id'         => '_fmdb_hospedaje_sencilla_max',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Hospedaje – Cupo Habitación Doble', 'fmdb' ),
        'desc'       => __( '0 = sin límite.', 'fmdb' ),
        'id'         => '_fmdb_hospedaje_doble_max',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Hospedaje – Cupo Habitación Triple', 'fmdb' ),
        'desc'       => __( '0 = sin límite.', 'fmdb' ),
        'id'         => '_fmdb_hospedaje_triple_max',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Hospedaje – Cupo Habitación Cuádruple', 'fmdb' ),
        'desc'       => __( '0 = sin límite.', 'fmdb' ),
        'id'         => '_fmdb_hospedaje_cuadruple_max',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0' ],
    ] );
} );

/**
 * Returns [ 'min' => int, 'max' => int ] for team player roster limits.
 * Reads from per-event meta; falls back to 6/10.
 */
function fmdb_reg_player_limits( int $event_id ): array {
    $min = (int) get_post_meta( $event_id, '_fmdb_reg_min_players', true );
    $max = (int) get_post_meta( $event_id, '_fmdb_reg_max_players', true );
    if ( $min < 1 ) $min = 6;
    if ( $max < 1 ) $max = 10;
    if ( $max < $min ) $max = $min;
    return [ 'min' => $min, 'max' => $max ];
}

/* ─── 2. Sync WC product on event save ────────────────────────────────── */

// Explicitly save hospedaje capacity fields from $_POST — CMB2 saves at priority 10
// but TEC's edit flow sometimes bypasses the CMB2 nonce check for these fields.
add_action( 'save_post_tribe_events', function ( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    foreach ( [ '_fmdb_hospedaje_doble_max', '_fmdb_hospedaje_triple_max', '_fmdb_hospedaje_sencilla_max', '_fmdb_hospedaje_cuadruple_max' ] as $key ) {
        if ( ! isset( $_POST[ $key ] ) ) continue;
        $val = absint( $_POST[ $key ] );
        if ( $val > 0 ) {
            update_post_meta( $post_id, $key, $val );
        } else {
            delete_post_meta( $post_id, $key );
        }
    }
}, 20 );

add_action( 'save_post_tribe_events', function ( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    if ( ! class_exists( 'WC_Product_Simple' ) ) return;

    $open    = get_post_meta( $post_id, '_fmdb_reg_open', true );
    $fee     = (float) get_post_meta( $post_id, '_fmdb_reg_fee', true );
    $max     = (int) get_post_meta( $post_id, '_fmdb_reg_max_teams', true );
    $prod_id = (int) get_post_meta( $post_id, '_fmdb_reg_product_id', true );

    if ( $open !== 'on' || $fee < 0 ) {
        if ( $prod_id && get_post_status( $prod_id ) === 'publish' ) {
            wp_update_post( [ 'ID' => $prod_id, 'post_status' => 'draft' ] );
        }
        return;
    }

    $title = sprintf( 'Inscripción: %s', get_the_title( $post_id ) );

    if ( $prod_id && ( $product = wc_get_product( $prod_id ) ) ) {
        // Update existing product.
    } else {
        $product = new WC_Product_Simple();
        $product->set_catalog_visibility( 'hidden' );
        $product->set_virtual( true );
        $product->set_sold_individually( true );
    }

    $product->set_name( $title );
    $product->set_status( 'publish' );
    $product->set_regular_price( (string) $fee );

    $product->set_manage_stock( false );
    $product->set_stock_status( 'instock' );

    $product->save();
    $new_id = $product->get_id();

    update_post_meta( $post_id, '_fmdb_reg_product_id', $new_id );
    update_post_meta( $new_id, '_fmdb_reg_event_id', $post_id );
}, 30 );

/* ─── 3a. Helper: get all teams registered for an event ───────────────── */

/**
 * Expands Varonil/Mixto and Femenil/Mixto registrations into two display entries each:
 * a primary team (Varonil or Femenil) and a shared Mixto team that merges all contributions
 * for the same team name + categoria.
 */
function fmdb_expand_team_ramas( array $teams ): array {
    $out      = [];
    $mixto    = []; // lc-name|categoria → index in $out

    foreach ( $teams as $team ) {
        $rama = $team['rama'] ?? '';

        if ( strpos( $rama, '/' ) === false ) {
            $out[] = $team; // old-format or already expanded — pass through
            continue;
        }

        $primary_rama = ( strpos( $rama, 'Varonil' ) !== false ) ? 'Varonil' : 'Femenil';
        $mixto_key    = mb_strtolower( trim( $team['name'] ?? '' ) ) . '|' . ( $team['categoria'] ?? '' );

        // Primary team (Varonil or Femenil).
        $primary         = $team;
        $primary['rama'] = $primary_rama;
        $out[]           = $primary;

        // Mixto team.
        if ( ! isset( $mixto[ $mixto_key ] ) ) {
            $mt = $team;
            $mt['rama'] = 'Mixto';
            unset( $mt['confirmed'] ); // recomputed by normalization pass
            $out[] = $mt;
            $mixto[ $mixto_key ] = array_key_last( $out );
        } else {
            $idx = $mixto[ $mixto_key ];
            // Merge captain as extra player if different.
            $captain = trim( $team['captain'] ?? '' );
            if ( $captain && $captain !== ( $out[ $idx ]['captain'] ?? '' ) ) {
                $out[ $idx ]['extra_players'][] = $captain;
            }
            foreach ( $team['extra_players'] ?? [] as $ep ) {
                $out[ $idx ]['extra_players'][] = $ep;
            }
            $out[ $idx ]['bulk_count'] += (int) ( $team['bulk_count'] ?? 0 );
            foreach ( $team['players'] ?? [] as $p ) {
                $out[ $idx ]['players'][] = $p;
            }
            // Promote status to the "better" of the two registrations.
            $rank = [ 'completed' => 3, 'processing' => 2, 'on-hold' => 1, 'pending' => 0 ];
            if ( ( $rank[ $team['status'] ?? '' ] ?? -1 ) > ( $rank[ $out[ $idx ]['status'] ?? '' ] ?? -1 ) ) {
                $out[ $idx ]['status'] = $team['status'];
            }
        }
    }

    return $out;
}

function fmdb_reg_get_event_teams( int $event_id ): array {
    if ( ! function_exists( 'wc_get_orders' ) ) return [];

    $orders = wc_get_orders( [
        'meta_key'   => '_fmdb_reg_event_id',
        'meta_value' => $event_id,
        'status'     => [ 'wc-processing', 'wc-completed' ],
        'limit'      => -1,
    ] );

    $teams = []; // keyed by normalized team name

    foreach ( $orders as $order ) {
        $reg_type = $order->get_meta( '_fmdb_reg_type' ) ?: 'team';

        foreach ( $order->get_items() as $item ) {
            $team_name = $item->get_meta( 'Equipo' );
            if ( ! $team_name ) continue;

            $rama      = $item->get_meta( 'Rama' );
            $categoria = $item->get_meta( 'Categoría' );
            $modalidad = $item->get_meta( 'Modalidad' );

            // Key on name + division so same-named teams in different divisions are independent.
            // New format (rama contains '/'): key omits modalidad — one registration covers all modalidades.
            // Old format (pre-migration): include modalidad for backward compat.
            $is_new_fmt = strpos( $rama, '/' ) !== false;
            $key = mb_strtolower( trim( $team_name ) ) . '|' . $rama . '|' . $categoria
                 . ( $is_new_fmt ? '' : '|' . $modalidad );

            if ( ! isset( $teams[ $key ] ) ) {
                $teams[ $key ] = [
                    'name'         => $team_name,
                    'rama'         => $rama,
                    'categoria'    => $categoria,
                    'modalidad'    => $modalidad,
                    'captain'      => '',
                    'extra_players' => [],
                    'bulk_count'   => 0,
                    'order_id'     => 0,
                    'status'       => '',
                    'on_waitlist'  => false,
                    'confirmed'    => false,
                    'players'      => [],
                ];
            }

            if ( $reg_type === 'team' ) {
                $captain_full = trim( ( $item->get_meta( 'Encargado' ) ?: $item->get_meta( 'Capitán' ) ) . ' ' . $item->get_meta( 'Apellido' ) );
                $total_slots  = (int) $item->get_meta( 'Jugadores' );
                $extra        = [];
                for ( $i = 2; $i <= $total_slots; $i++ ) {
                    $n = trim( $item->get_meta( 'Jugador ' . $i ) );
                    if ( $n ) $extra[] = $n;
                }
                $teams[ $key ]['captain']       = $captain_full;
                $teams[ $key ]['extra_players']  = $extra;
                $teams[ $key ]['bulk_count']     = $total_slots;
                $teams[ $key ]['order_id']       = $order->get_id();
                $teams[ $key ]['status']         = $order->get_status();
                $teams[ $key ]['on_waitlist']    = $order->get_meta( '_fmdb_on_waitlist' ) === '1';
            } else {
                $player_name = trim( $item->get_meta( 'Jugador' ) . ' ' . $item->get_meta( 'Apellido' ) );
                if ( $player_name ) {
                    $teams[ $key ]['players'][] = [
                        'name'   => $player_name,
                        'status' => $order->get_status(),
                    ];
                }
            }
        }
    }

    // Compute confirmed: paid + ≥ min players + not on waitlist.
    $limits = fmdb_reg_player_limits( $event_id );
    $min_players = $limits['min'];
    foreach ( $teams as &$team ) {
        $total = $team['bulk_count'] + count( $team['players'] );
        $team['confirmed'] = in_array( $team['status'], [ 'processing', 'completed' ], true )
                          && $total >= $min_players
                          && ! ( $team['on_waitlist'] ?? false );
    }
    unset( $team );

    $all = apply_filters( 'fmdb_reg_event_teams', array_values( $teams ), $event_id );

    // Expand Varonil/Mixto → Varonil + Mixto, Femenil/Mixto → Femenil + Mixto.
    $all = fmdb_expand_team_ramas( $all );

    // Normalize fixture/filtered data that may be missing computed fields.
    $normalized = array_map( function ( $t ) use ( $min_players ) {
        $t['on_waitlist'] = $t['on_waitlist'] ?? false;
        if ( ! isset( $t['confirmed'] ) ) {
            $total = ( $t['bulk_count'] ?? 0 ) + count( $t['players'] ?? [] );
            $t['confirmed'] = in_array( $t['status'] ?? '', [ 'processing', 'completed' ], true )
                           && $total >= $min_players
                           && ! $t['on_waitlist'];
        }
        return $t;
    }, $all );

    // Apply slot cap to Mixto display teams (same cap as primary teams, first-come-first-served).
    $mixto_counts = [];
    foreach ( $normalized as &$t ) {
        if ( ( $t['rama'] ?? '' ) !== 'Mixto' || ! ( $t['confirmed'] ?? false ) ) continue;
        $cat = $t['categoria'] ?? '';
        $cap = fmdb_reg_slot_cap( $event_id, $cat );
        $mixto_counts[ $cat ] = ( $mixto_counts[ $cat ] ?? 0 ) + 1;
        if ( $cap > 0 && $mixto_counts[ $cat ] > $cap ) {
            $t['confirmed']   = false;
            $t['on_waitlist'] = true;
        }
    }
    unset( $t );

    return $normalized;
}

/* ─── 3b. Helper: resolve user's fmdb_team ────────────────────────────── */

function fmdb_reg_user_team( int $user_id ): ?WP_Post {
    $posts = get_posts( [
        'post_type'      => 'fmdb_team',
        'posts_per_page' => 1,
        'post_status'    => [ 'publish', 'pending', 'draft' ],
        'meta_query'     => [
            'relation' => 'OR',
            [ 'key' => 'team_rep', 'value' => '"' . $user_id . '"', 'compare' => 'LIKE' ],
            [ 'key' => 'team_rep', 'value' => $user_id, 'compare' => '=' ],
        ],
    ] );
    return $posts ? $posts[0] : null;
}

/* ─── 3c. Slot cap and confirmed-team count ───────────────────────────── */

// Cap per (rama × modalidad) for a given categoria. Infantil is always 12.
function fmdb_reg_slot_cap( int $event_id, string $categoria ): int {
    if ( $categoria === 'Infantil' ) {
        return (int) get_post_meta( $event_id, '_fmdb_reg_max_teams_infantil', true );
    }
    return (int) get_post_meta( $event_id, '_fmdb_reg_max_teams', true );
}

// Count non-waitlist team registrations for a given (rama × categoria) slot.
// A single registration now covers all modalidades, so modalidad is no longer a cap axis.
function fmdb_reg_slot_team_count( int $event_id, string $rama, string $categoria ): int {
    if ( ! function_exists( 'wc_get_orders' ) ) return 0;
    $orders = wc_get_orders( [
        'meta_key'   => '_fmdb_reg_event_id',
        'meta_value' => $event_id,
        'status'     => [ 'wc-processing', 'wc-completed' ],
        'limit'      => -1,
    ] );
    $count = 0;
    foreach ( $orders as $order ) {
        if ( $order->get_meta( '_fmdb_reg_type' ) !== 'team' ) continue;
        if ( $order->get_meta( '_fmdb_on_waitlist' ) === '1' ) continue;
        foreach ( $order->get_items() as $item ) {
            if ( $item->get_meta( 'Rama' )     === $rama
              && $item->get_meta( 'Categoría' ) === $categoria ) {
                $count++;
                break;
            }
        }
    }
    return (int) apply_filters( 'fmdb_reg_slot_team_count', $count, $event_id, $rama, $categoria );
}

/* ─── 3d. Hospedaje add-on products ───────────────────────────────────── */

function fmdb_hospedaje_product_ids(): array {
    static $cache = null;
    if ( $cache !== null ) return $cache;
    if ( ! class_exists( 'WC_Product_Simple' ) ) return $cache = [];

    $inc_full = '1 Noche de hospedaje · 1 Desayuno Americano · 1 Comida Emplatada (3 tiempos) · 1 Cena Emplatada (3 tiempos)';
    $inc_sc   = '1 Noche de hospedaje';

    $defs = [
        'doble'        => [ 'name' => 'Hospedaje – Habitación Doble',                'desc' => $inc_full, 'price' => '1415', 'opt' => 'fmdb_hospedaje_doble_id'        ],
        'triple'       => [ 'name' => 'Hospedaje – Habitación Triple',               'desc' => $inc_full, 'price' => '1355', 'opt' => 'fmdb_hospedaje_triple_id'       ],
        'sencilla'     => [ 'name' => 'Hospedaje – Habitación Sencilla',             'desc' => $inc_full, 'price' => '1200', 'opt' => 'fmdb_hospedaje_sencilla_id'     ],
        'cuadruple'    => [ 'name' => 'Hospedaje – Habitación Cuádruple',            'desc' => $inc_full, 'price' => '1500', 'opt' => 'fmdb_hospedaje_cuadruple_id'    ],
        'doble_sc'     => [ 'name' => 'Hospedaje – Habitación Doble Solo Cuarto',   'desc' => $inc_sc,   'price' => '800',  'opt' => 'fmdb_hospedaje_doble_sc_id'     ],
        'triple_sc'    => [ 'name' => 'Hospedaje – Habitación Triple Solo Cuarto',  'desc' => $inc_sc,   'price' => '750',  'opt' => 'fmdb_hospedaje_triple_sc_id'    ],
        'sencilla_sc'  => [ 'name' => 'Hospedaje – Habitación Sencilla Solo Cuarto','desc' => $inc_sc,   'price' => '700',  'opt' => 'fmdb_hospedaje_sencilla_sc_id'  ],
        'cuadruple_sc' => [ 'name' => 'Hospedaje – Habitación Cuádruple Solo Cuarto','desc'=> $inc_sc,   'price' => '900',  'opt' => 'fmdb_hospedaje_cuadruple_sc_id' ],
    ];

    $ids = [];
    foreach ( $defs as $key => $d ) {
        $pid = (int) get_option( $d['opt'] );
        if ( ! $pid || ! wc_get_product( $pid ) ) {
            $p = new WC_Product_Simple();
            $p->set_name( $d['name'] );
            $p->set_short_description( $d['desc'] );
            $p->set_regular_price( $d['price'] );
            $p->set_virtual( true );
            $p->set_catalog_visibility( 'hidden' );
            $p->set_status( 'publish' );
            $p->save();
            update_option( $d['opt'], $p->get_id() );
            $pid = $p->get_id();
        }
        $ids[ $key ] = $pid;
    }

    return $cache = $ids;
}

// AJAX: return current availability for both room types of an event (cache-safe).
add_action( 'wp_ajax_nopriv_fmdb_hospedaje_avail', 'fmdb_ajax_hospedaje_avail' );
add_action( 'wp_ajax_fmdb_hospedaje_avail',        'fmdb_ajax_hospedaje_avail' );
function fmdb_ajax_hospedaje_avail(): void {
    $event_id = absint( $_GET['event_id'] ?? 0 );
    if ( ! $event_id ) wp_send_json_error( [], 400 );

    $result = [];
    foreach ( [ 'doble', 'triple', 'sencilla', 'cuadruple' ] as $room ) {
        $max     = (int) get_post_meta( $event_id, "_fmdb_hospedaje_{$room}_max", true );
        $result[ $room ] = $max > 0
            ? max( 0, $max - fmdb_hospedaje_sold_count( $event_id, $room ) )
            : -1;
    }
    wp_send_json_success( $result );
}

// Returns count of hospedaje items sold for an event. Counts pending/on-hold too so slots don't race.
function fmdb_hospedaje_sold_count( int $event_id, string $room_type ): int {
    if ( ! function_exists( 'wc_get_orders' ) ) return 0;
    // Count both the full-board and room-only variants toward the same shared cap.
    $room_labels = [
        'doble'     => [ 'Doble',      'Doble SC'      ],
        'triple'    => [ 'Triple',     'Triple SC'     ],
        'sencilla'  => [ 'Sencilla',   'Sencilla SC'   ],
        'cuadruple' => [ 'Cuádruple',  'Cuádruple SC'  ],
    ];
    $labels = $room_labels[ $room_type ] ?? [ ucfirst( $room_type ) ];
    $orders = wc_get_orders( [
        'status'     => [ 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-completed' ],
        'limit'      => -1,
        'meta_query' => [
            'relation' => 'OR',
            [ 'key' => '_fmdb_reg_event_id',       'value' => $event_id, 'compare' => '=' ],
            [ 'key' => '_fmdb_hospedaje_event_id', 'value' => $event_id, 'compare' => '=' ],
        ],
    ] );
    $count = 0;
    foreach ( $orders as $order ) {
        foreach ( $order->get_items() as $item ) {
            if ( in_array( $item->get_meta( 'Habitación' ), $labels, true ) ) {
                $count++;
            }
        }
    }
    return $count;
}

// $avail: −1 = unlimited, 0 = sold out, N > 0 = slots remaining.
function fmdb_hospedaje_form_section( float $price_doble = 1415.0, float $price_triple = 1355.0, int $avail_doble = -1, int $avail_triple = -1 ): void {
    $fmt_d      = '$' . number_format( $price_doble,  0, '.', ',' ) . ' MXN';
    $fmt_t      = '$' . number_format( $price_triple, 0, '.', ',' ) . ' MXN';
    $soldout_d  = $avail_doble  === 0;
    $soldout_t  = $avail_triple === 0;
    ?>
<div class="fmdb-reg-form__section-title">Hospedaje <span class="fmdb-reg-form__range">(opcional)</span></div>
<p class="fmdb-reg-form__hint fmdb-reg-hospedaje__desc">Incluye: 1 noche · Desayuno Americano · Comida Emplatada (3 tiempos) · Cena Emplatada (3 tiempos)</p>
<div class="fmdb-reg-hospedaje">
    <label class="fmdb-reg-hospedaje__option">
        <input type="radio" name="fmdb_hospedaje" value="" checked>
        <span class="fmdb-reg-hospedaje__label">Sin hospedaje</span>
    </label>
    <label class="fmdb-reg-hospedaje__option<?php echo $soldout_d ? ' fmdb-reg-hospedaje__option--soldout' : ''; ?>">
        <input type="radio" name="fmdb_hospedaje" value="doble"<?php echo $soldout_d ? ' disabled' : ''; ?>>
        <span class="fmdb-reg-hospedaje__label">Habitación Doble</span>
        <span class="fmdb-reg-hospedaje__meta">
            <span class="fmdb-reg-hospedaje__price"><?php echo esc_html( $fmt_d ); ?></span>
            <?php if ( $avail_doble === 0 ) : ?>
                <span class="fmdb-reg-hospedaje__avail fmdb-reg-hospedaje__avail--soldout">Agotado</span>
            <?php elseif ( $avail_doble > 0 ) : ?>
                <span class="fmdb-reg-hospedaje__avail"><?php echo esc_html( $avail_doble ); ?> disponible<?php echo $avail_doble !== 1 ? 's' : ''; ?></span>
            <?php endif; ?>
        </span>
    </label>
    <label class="fmdb-reg-hospedaje__option<?php echo $soldout_t ? ' fmdb-reg-hospedaje__option--soldout' : ''; ?>">
        <input type="radio" name="fmdb_hospedaje" value="triple"<?php echo $soldout_t ? ' disabled' : ''; ?>>
        <span class="fmdb-reg-hospedaje__label">Habitación Triple</span>
        <span class="fmdb-reg-hospedaje__meta">
            <span class="fmdb-reg-hospedaje__price"><?php echo esc_html( $fmt_t ); ?></span>
            <?php if ( $avail_triple === 0 ) : ?>
                <span class="fmdb-reg-hospedaje__avail fmdb-reg-hospedaje__avail--soldout">Agotado</span>
            <?php elseif ( $avail_triple > 0 ) : ?>
                <span class="fmdb-reg-hospedaje__avail"><?php echo esc_html( $avail_triple ); ?> disponible<?php echo $avail_triple !== 1 ? 's' : ''; ?></span>
            <?php endif; ?>
        </span>
    </label>
</div>
<?php }

/* ─── 4. Frontend: registration card ──────────────────────────────────── */

function fmdb_event_registration_box( int $event_id ): void {
    if ( ! function_exists( 'wc_get_product' ) ) return;

    $open        = get_post_meta( $event_id, '_fmdb_reg_open', true ) === 'on';
    $fee         = (float) get_post_meta( $event_id, '_fmdb_reg_fee', true );
    $entrada_fee = (float) get_post_meta( $event_id, '_fmdb_reg_entrada_fee', true );
    $prod_id     = (int) get_post_meta( $event_id, '_fmdb_reg_product_id', true );
    $deadline = get_post_meta( $event_id, '_fmdb_reg_deadline', true );
    $max      = (int) get_post_meta( $event_id, '_fmdb_reg_max_teams', true );
    $ramas    = array_values( array_filter( (array) get_post_meta( $event_id, '_fmdb_reg_ramas', true ) ) );
    $cats     = array_values( array_filter( (array) get_post_meta( $event_id, '_fmdb_reg_categorias', true ) ) );

    if ( ! $open || $fee < 0 || ! $prod_id ) return;

    $product = wc_get_product( $prod_id );
    if ( ! $product || $product->get_status() !== 'publish' ) return;

    $past_deadline = $deadline && strtotime( $deadline . ' 23:59:59' ) < time();
    $closed        = $past_deadline;

    // Strip legacy values (pre-migration: Femenil, Mixta, Varonil) saved before the new rama model.
    $valid_ramas = [ 'Varonil/Mixto', 'Femenil/Mixto' ];
    $ramas = array_values( array_intersect( $ramas, $valid_ramas ) );
    // Fallback: show all options if admin left division fields empty (or only had legacy values).
    if ( empty( $ramas ) ) $ramas = $valid_ramas;
    if ( empty( $cats ) )  $cats  = [ 'Infantil', 'Libre' ];

    $cat_labels    = [ 'Infantil' => 'Infantil (8-12 años)', 'Libre' => 'Libre (13+ años)' ];
    $player_limits = fmdb_reg_player_limits( $event_id );
    $min_players   = $player_limits['min'];
    $max_players   = $player_limits['max'];

    // Shared POST values (restored on validation error)
    $active_tab    = in_array( $_POST['fmdb_reg_type'] ?? '', [ 'team', 'individual' ], true )
                     ? $_POST['fmdb_reg_type'] : 'team';
    $rama_val      = sanitize_text_field( $_POST['fmdb_rama'] ?? '' );
    $cat_val       = sanitize_text_field( $_POST['fmdb_categoria'] ?? '' );

    // Team form values
    $team_post_id        = 0;
    $team_name_val       = esc_attr( $_POST['fmdb_team_name'] ?? '' );
    $captain_val         = esc_attr( $_POST['fmdb_captain_name'] ?? '' );
    $captain_apellido_val = esc_attr( $_POST['fmdb_captain_apellido'] ?? '' );
    $phone_val           = esc_attr( $_POST['fmdb_captain_phone'] ?? '' );
    $count_val           = (int) ( $_POST['fmdb_player_count'] ?? 0 );

    // Individual form values
    $ind_team_val        = esc_attr( $_POST['fmdb_ind_team_name'] ?? '' );
    $player_name_val     = esc_attr( $_POST['fmdb_player_name'] ?? '' );
    $player_apellido_val = esc_attr( $_POST['fmdb_player_apellido'] ?? '' );
    $player_phone_val    = esc_attr( $_POST['fmdb_player_phone'] ?? '' );

    // Detect user's fmdb_team and pre-fill
    $user_team = null;
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $user_team    = fmdb_reg_user_team( $current_user->ID );

        if ( $user_team ) {
            $team_post_id = $user_team->ID;
            if ( ! $team_name_val ) $team_name_val = esc_attr( $user_team->post_title );
            if ( ! $ind_team_val )  $ind_team_val  = esc_attr( $user_team->post_title );

            if ( ! $captain_val ) {
                $roster = get_post_meta( $user_team->ID, 'team_roster', true );
                if ( is_array( $roster ) ) {
                    foreach ( $roster as $entry ) {
                        if ( ! empty( $entry['is_captain'] ) ) {
                            $uid = (int) ( $entry['user_id'] ?? $entry['linked_user_id'] ?? 0 );
                            if ( $uid && ( $u = get_userdata( $uid ) ) ) {
                                $captain_val         = esc_attr( $u->first_name );
                                if ( ! $captain_apellido_val ) $captain_apellido_val = esc_attr( $u->last_name );
                            }
                            break;
                        }
                    }
                }
                if ( ! $captain_val ) $captain_val = esc_attr( $current_user->first_name );
                if ( ! $captain_apellido_val ) $captain_apellido_val = esc_attr( $current_user->last_name );
            }
        }

        if ( ! $player_name_val )     $player_name_val     = esc_attr( $current_user->first_name );
        if ( ! $player_apellido_val ) $player_apellido_val = esc_attr( $current_user->last_name );
    }

    // Teams already registered for this event (from WC orders)
    $registered_teams = fmdb_reg_get_event_teams( $event_id );

    // Check if user's team already has a bulk registration for this event
    $team_already_registered = false;
    $team_reg_details        = null;
    if ( $user_team ) {
        $team_lower = mb_strtolower( trim( $user_team->post_title ) );
        foreach ( $registered_teams as $rt ) {
            if ( ( $rt['rama'] ?? '' ) === 'Mixto' ) continue;
            if ( mb_strtolower( trim( $rt['name'] ) ) === $team_lower && $rt['order_id'] ) {
                $team_already_registered = true;
                $team_reg_details        = $rt;
                break;
            }
        }
    }

    $confirmed_count = count( array_filter( $registered_teams, fn( $t ) => $t['confirmed'] ?? false ) );

    $fee_fmt     = number_format( $fee, 2 );
    $eid         = (int) $event_id;
    fmdb_hospedaje_product_ids(); // ensure products exist

    // Hospedaje prices: event meta overrides default.
    $h_price_doble      = (float) get_post_meta( $event_id, '_fmdb_hospedaje_doble_fee',        true ) ?: 1415.0;
    $h_price_triple     = (float) get_post_meta( $event_id, '_fmdb_hospedaje_triple_fee',       true ) ?: 1355.0;
    $h_price_sencilla   = (float) get_post_meta( $event_id, '_fmdb_hospedaje_sencilla_fee',     true ) ?: 1200.0;
    $h_price_cuadruple  = (float) get_post_meta( $event_id, '_fmdb_hospedaje_cuadruple_fee',    true ) ?: 1500.0;
    $h_price_doble_sc      = (float) get_post_meta( $event_id, '_fmdb_hospedaje_doble_sc_fee',      true ) ?: 800.0;
    $h_price_triple_sc     = (float) get_post_meta( $event_id, '_fmdb_hospedaje_triple_sc_fee',     true ) ?: 750.0;
    $h_price_sencilla_sc   = (float) get_post_meta( $event_id, '_fmdb_hospedaje_sencilla_sc_fee',   true ) ?: 700.0;
    $h_price_cuadruple_sc  = (float) get_post_meta( $event_id, '_fmdb_hospedaje_cuadruple_sc_fee',  true ) ?: 900.0;

    // Hospedaje availability: −1 = unlimited, 0 = sold out, N = slots remaining.
    $h_avail = [];
    foreach ( [ 'doble', 'triple', 'sencilla', 'cuadruple' ] as $_r ) {
        $max = (int) get_post_meta( $event_id, "_fmdb_hospedaje_{$_r}_max", true );
        $h_avail[ $_r ] = $max > 0 ? max( 0, $max - fmdb_hospedaje_sold_count( $event_id, $_r ) ) : -1;
    }
    $h_avail_doble     = $h_avail['doble'];
    $h_avail_triple    = $h_avail['triple'];
    $h_avail_sencilla  = $h_avail['sencilla'];
    $h_avail_cuadruple = $h_avail['cuadruple'];
    ?>
    <div class="fmdb-evento-single__meta-card fmdb-reg-box">
        <h3 class="fmdb-evento-single__meta-title">Inscripción al torneo</h3>

        <?php if ( function_exists( 'wc_print_notices' ) ) { wc_print_notices(); } ?>

        <div class="fmdb-evento-single__meta-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <div>
                <strong>Cuota por jugador</strong>
                <span>$<?php echo esc_html( $fee_fmt ); ?> MXN</span>
            </div>
        </div>

        <div class="fmdb-evento-single__meta-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <div>
                <strong>Acceso al venue</strong>
                <span><?php echo $entrada_fee > 0 ? '$' . esc_html( number_format( $entrada_fee, 2 ) ) . ' MXN' : 'Incluido'; ?></span>
            </div>
        </div>

        <?php if ( $deadline ) : ?>
        <div class="fmdb-evento-single__meta-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <div>
                <strong>Fecha límite de registro</strong>
                <span><?php echo esc_html( date_i18n( 'j \d\e F, Y', strtotime( $deadline ) ) ); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $confirmed_count > 0 || $max > 0 ) : ?>
        <div class="fmdb-evento-single__meta-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <div>
                <strong>Equipos confirmados</strong>
                <span><?php echo esc_html( $confirmed_count ); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $closed ) : ?>
            <span class="fmdb-reg-box__closed">Inscripción cerrada</span>

        <?php elseif ( is_user_logged_in() ) : ?>

            <!-- ── REGISTRO SECTION ── -->
            <div class="fmdb-reg-section__header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Registro al torneo
            </div>
            <p style="font-size:12px;color:var(--fmdb-text-muted,#666);margin:0 0 10px;">Inicia tu registro aquí — elige si deseas registrar a un equipo o inscribirte a un equipo que ya esté registrado.</p>
            <div class="fmdb-reg-tabs" id="fmdb-reg-tabs-<?php echo $eid; ?>">
                <button type="button" class="fmdb-reg-tab<?php echo $active_tab === 'team' ? ' is-active' : ''; ?>"
                        data-target="fmdb-form-team-<?php echo $eid; ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Equipo
                </button>
                <button type="button" class="fmdb-reg-tab<?php echo $active_tab === 'individual' ? ' is-active' : ''; ?>"
                        data-target="fmdb-form-ind-<?php echo $eid; ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/></svg>
                    Individual
                </button>
            </div>

            <!-- ── TEAM FORM ── -->
            <?php if ( $team_already_registered && $team_reg_details ) : ?>
            <div class="fmdb-reg-inscrito<?php echo $active_tab !== 'team' ? ' fmdb-reg-form--hidden' : ''; ?>"
                 id="fmdb-form-team-<?php echo $eid; ?>">
                <?php
                $status_labels = [
                    'pending'    => 'Pendiente de pago',
                    'on-hold'    => 'Pendiente de pago',
                    'processing' => 'Pago confirmado',
                    'completed'  => 'Inscripción completa',
                ];
                $status_label = $status_labels[ $team_reg_details['status'] ] ?? ucfirst( $team_reg_details['status'] );
                $div_str      = implode( ' · ', array_filter( [
                    $team_reg_details['rama'],
                    $team_reg_details['categoria'],
                    $team_reg_details['modalidad'],
                ] ) );
                ?>
                <div class="fmdb-reg-inscrito__icon">✓</div>
                <div class="fmdb-reg-inscrito__body">
                    <strong class="fmdb-reg-inscrito__name"><?php echo esc_html( $team_reg_details['name'] ); ?></strong>
                    <?php if ( $div_str ) : ?>
                        <span class="fmdb-reg-inscrito__div"><?php echo esc_html( $div_str ); ?></span>
                    <?php endif; ?>
                    <?php if ( $team_reg_details['bulk_count'] ) : ?>
                        <span class="fmdb-reg-inscrito__count"><?php echo (int) $team_reg_details['bulk_count']; ?> jugadores registrados</span>
                    <?php endif; ?>
                    <span class="fmdb-reg-inscrito__status fmdb-reg-status--<?php echo esc_attr( $team_reg_details['status'] ); ?>">
                        <?php echo esc_html( $status_label ); ?>
                    </span>
                    <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) . 'orders/' ); ?>"
                       class="fmdb-reg-inscrito__link">Ver mis pedidos →</a>
                </div>
            </div>
            <?php else : ?>
            <form method="post" class="fmdb-reg-form<?php echo $active_tab !== 'team' ? ' fmdb-reg-form--hidden' : ''; ?>"
                  id="fmdb-form-team-<?php echo $eid; ?>">
                <input type="hidden" name="add-to-cart"      value="<?php echo $prod_id; ?>">
                <input type="hidden" name="fmdb_reg_type"    value="team">
                <input type="hidden" name="fmdb_team_post_id" value="<?php echo (int) $team_post_id; ?>">

                <div class="fmdb-reg-form__section-title">Equipo</div>

                <div class="fmdb-reg-form__field">
                    <label>Nombre del equipo *</label>
                    <?php if ( $user_team ) : ?>
                        <input type="text" name="fmdb_team_name"
                               value="<?php echo $team_name_val; ?>" readonly class="fmdb-input--readonly">
                        <span class="fmdb-reg-form__hint">
                            ¿Otro equipo? <a href="<?php echo esc_url( home_url( '/mi-equipo/' ) ); ?>">Ir a Mi Equipo</a>
                        </span>
                    <?php else : ?>
                        <input type="text" name="fmdb_team_name" required
                               value="<?php echo $team_name_val; ?>" placeholder="Ej. Tiburones de Naucalpan">
                    <?php endif; ?>
                </div>

                <div class="fmdb-reg-form__row">
                    <div class="fmdb-reg-form__field">
                        <label>Nombre *</label>
                        <input type="text" name="fmdb_captain_name" required
                               pattern="[A-Za-záéíóúüñÁÉÍÓÚÜÑ '\-]+"
                               title="Solo se permiten letras, espacios y guiones"
                               value="<?php echo $captain_val; ?>">
                    </div>
                    <div class="fmdb-reg-form__field">
                        <label>Apellido *</label>
                        <input type="text" name="fmdb_captain_apellido" required
                               pattern="[A-Za-záéíóúüñÁÉÍÓÚÜÑ '\-]+"
                               title="Solo se permiten letras, espacios y guiones"
                               value="<?php echo $captain_apellido_val; ?>">
                    </div>
                </div>
                <div class="fmdb-reg-form__field">
                    <label>Teléfono *</label>
                    <input type="tel" name="fmdb_captain_phone" required
                           value="<?php echo $phone_val; ?>" placeholder="55 xxxx xxxx">
                </div>

                <div class="fmdb-reg-form__section-title">División</div>
                <?php echo fmdb_reg_division_selects( $eid . 't', $ramas, $cats, $cat_labels, $rama_val, $cat_val ); ?>

                <div class="fmdb-reg-form__section-title">Plantel</div>

                <div class="fmdb-reg-form__field">
                    <label>Número de jugadores * <span class="fmdb-reg-form__range">(mín. 1, máx. <?php echo $max_players; ?>)</span></label>
                    <input type="number" name="fmdb_player_count" required min="1" max="<?php echo $max_players; ?>"
                           value="<?php echo $count_val > 0 ? $count_val : ''; ?>"
                           class="fmdb-reg-count-input" id="fmdb-count-<?php echo $eid; ?>">
                    <span class="fmdb-reg-form__hint">El coach no cuenta como jugador.</span>
                </div>

                <div id="fmdb-extra-players-<?php echo $eid; ?>" class="fmdb-reg-extra-players"></div>

                <button type="submit" class="fmdb-btn fmdb-btn--primary fmdb-reg-section__btn">
                    Agregar inscripción →
                </button>
                <div class="fmdb-reg-section__msg" id="fmdb-reg-msg-team-<?php echo $eid; ?>"></div>
            </form>
            <?php endif; // team_already_registered ?>

            <!-- ── INDIVIDUAL FORM ── -->
            <form method="post" class="fmdb-reg-form<?php echo $active_tab !== 'individual' ? ' fmdb-reg-form--hidden' : ''; ?>"
                  id="fmdb-form-ind-<?php echo $eid; ?>">
                <input type="hidden" name="add-to-cart"   value="<?php echo $prod_id; ?>">
                <input type="hidden" name="fmdb_reg_type" value="individual">
                <input type="hidden" name="fmdb_player_count" value="1">

                <div class="fmdb-reg-form__section-title">Mi equipo</div>

                <?php
                $ind_lower     = mb_strtolower( trim( $ind_team_val ) );
                $ind_reg_match = null;
                foreach ( $registered_teams as $_rt ) {
                    if ( ( $_rt['rama'] ?? '' ) === 'Mixto' ) continue; // Mixto is derived, not a real picker option
                    if ( mb_strtolower( trim( $_rt['name'] ) ) === $ind_lower ) {
                        $ind_reg_match = $_rt; break;
                    }
                }
                ?>

                <div class="fmdb-reg-form__field">
                    <label>Equipo *</label>
                    <?php if ( ! empty( $registered_teams ) ) : ?>
                        <input type="hidden" name="fmdb_ind_team_name"
                               id="fmdb-ind-name-<?php echo $eid; ?>"
                               value="<?php echo $ind_team_val; ?>">
                        <select id="fmdb-ind-sel-<?php echo $eid; ?>" class="fmdb-reg-ind-sel">
                            <option value="">— Selecciona tu equipo —</option>
                            <?php foreach ( $registered_teams as $rt ) :
                                if ( ( $rt['rama'] ?? '' ) === 'Mixto' ) continue; // Mixto is derived; join via the primary team
                                $rt_div = implode( ' · ', array_filter( [ $rt['rama'], $rt['categoria'] ] ) );
                                // Stored/compound rama for the hidden field (must match team order's Rama meta).
                                $_rt_stored_rama = ( $rt['rama'] ?? '' ) === 'Varonil' ? 'Varonil/Mixto'
                                                 : ( ( $rt['rama'] ?? '' ) === 'Femenil' ? 'Femenil/Mixto' : ( $rt['rama'] ?? '' ) );
                            ?>
                                <option value="<?php echo esc_attr( $rt['name'] ); ?>"
                                        data-rama="<?php echo esc_attr( $rt['rama'] ); ?>"
                                        data-stored-rama="<?php echo esc_attr( $_rt_stored_rama ); ?>"
                                        data-cat="<?php echo esc_attr( $rt['categoria'] ); ?>"
                                        <?php selected( mb_strtolower( trim( $rt['name'] ) ) === $ind_lower ); ?>>
                                    <?php echo esc_html( $rt['name'] . ( $rt_div ? '  —  ' . $rt_div : '' ) ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?>
                        <p class="fmdb-reg-form__hint">Aún no hay equipos inscritos en este torneo. Para unirte como jugador individual, espera a que un equipo se inscriba primero o inscribe el tuyo en la pestaña Equipo.</p>
                    <?php endif; ?>
                </div>

                <div class="fmdb-reg-form__row">
                    <div class="fmdb-reg-form__field">
                        <label>Tu nombre *</label>
                        <input type="text" name="fmdb_player_name" required
                               pattern="[A-Za-záéíóúüñÁÉÍÓÚÜÑ '\-]+"
                               title="Solo se permiten letras, espacios y guiones"
                               value="<?php echo $player_name_val; ?>">
                    </div>
                    <div class="fmdb-reg-form__field">
                        <label>Apellido *</label>
                        <input type="text" name="fmdb_player_apellido" required
                               pattern="[A-Za-záéíóúüñÁÉÍÓÚÜÑ '\-]+"
                               title="Solo se permiten letras, espacios y guiones"
                               value="<?php echo $player_apellido_val; ?>">
                    </div>
                </div>
                <div class="fmdb-reg-form__field">
                    <label>Teléfono *</label>
                    <input type="tel" name="fmdb_player_phone" required
                           value="<?php echo $player_phone_val; ?>" placeholder="55 xxxx xxxx">
                </div>

                <?php if ( ! empty( $registered_teams ) ) : ?>
                <div class="fmdb-reg-form__section-title">División</div>
                <?php
                $_ind_disp_rama  = $ind_reg_match ? ( $ind_reg_match['rama'] ?? '' ) : '';
                $ind_cat_val     = $ind_reg_match ? $ind_reg_match['categoria']  : '';
                // Convert display rama back to stored/compound rama for the hidden field and cart.
                $ind_stored_rama = $_ind_disp_rama === 'Varonil' ? 'Varonil/Mixto'
                                 : ( $_ind_disp_rama === 'Femenil' ? 'Femenil/Mixto' : $_ind_disp_rama );
                $ind_rama_val    = $_ind_disp_rama; // display-only (visible span)
                $show_div_card   = (bool) $ind_reg_match;
                ?>
                <div class="fmdb-reg-ind-div-card<?php echo $show_div_card ? '' : ' fmdb-reg-form--hidden'; ?>"
                     id="fmdb-ind-div-card-<?php echo $eid; ?>">
                    <div class="fmdb-reg-ind-div-card__row">
                        <span class="fmdb-reg-ind-div-card__label">Rama</span>
                        <span class="fmdb-reg-ind-div-card__val" id="fmdb-ind-div-rama-<?php echo $eid; ?>"><?php echo esc_html( $ind_rama_val ); ?></span>
                    </div>
                    <div class="fmdb-reg-ind-div-card__row">
                        <span class="fmdb-reg-ind-div-card__label">Categoría</span>
                        <span class="fmdb-reg-ind-div-card__val" id="fmdb-ind-div-cat-<?php echo $eid; ?>"><?php echo esc_html( $cat_labels[ $ind_cat_val ] ?? $ind_cat_val ); ?></span>
                    </div>
                    <input type="hidden" name="fmdb_rama"      id="fmdb-ind-hrama-<?php echo $eid; ?>"
                           value="<?php echo esc_attr( $ind_stored_rama ); ?>"<?php echo $show_div_card ? '' : ' disabled'; ?>>
                    <input type="hidden" name="fmdb_categoria" id="fmdb-ind-hcat-<?php echo $eid; ?>"
                           value="<?php echo esc_attr( $ind_cat_val ); ?>"<?php echo $show_div_card ? '' : ' disabled'; ?>>
                </div>
                <?php endif; ?>

                <button type="submit" class="fmdb-btn fmdb-btn--primary fmdb-reg-section__btn">
                    Agregar inscripción →
                </button>
                <div class="fmdb-reg-section__msg" id="fmdb-reg-msg-ind-<?php echo $eid; ?>"></div>
            </form>

            <!-- ── HOSPEDAJE SECTION ── -->
            <div class="fmdb-reg-section__header fmdb-reg-section__header--sep">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Hospedaje <span class="fmdb-reg-form__range">(opcional)</span>
            </div>
            <div class="fmdb-reg-hospedaje" id="fmdb-hospedaje-opts-<?php echo $eid; ?>">
                <label class="fmdb-reg-hospedaje__option">
                    <input type="radio" name="fmdb_hospedaje_pick_<?php echo $eid; ?>" value="" checked>
                    <span class="fmdb-reg-hospedaje__label">Sin hospedaje</span>
                </label>

                <div class="fmdb-reg-hospedaje__group-label">Con 3 comidas</div>
                <p class="fmdb-reg-hospedaje__group-desc">1 Noche de hospedaje · 1 Desayuno Americano · 1 Comida Emplatada (3 tiempos) · 1 Cena Emplatada (3 tiempos) · Incluye acceso al venue.</p>
                <?php
                $h_room_opts = [
                    'sencilla'  => [ 'label' => 'Habitación Sencilla',  'price' => $h_price_sencilla,  'avail' => $h_avail_sencilla  ],
                    'doble'     => [ 'label' => 'Habitación Doble',     'price' => $h_price_doble,     'avail' => $h_avail_doble     ],
                    'triple'    => [ 'label' => 'Habitación Triple',    'price' => $h_price_triple,    'avail' => $h_avail_triple    ],
                    'cuadruple' => [ 'label' => 'Habitación Cuádruple', 'price' => $h_price_cuadruple, 'avail' => $h_avail_cuadruple ],
                ];
                foreach ( $h_room_opts as $rv => $ro ) : ?>
                <label class="fmdb-reg-hospedaje__option<?php echo $ro['avail'] === 0 ? ' fmdb-reg-hospedaje__option--soldout' : ''; ?>">
                    <input type="radio" name="fmdb_hospedaje_pick_<?php echo $eid; ?>" value="<?php echo esc_attr( $rv ); ?>"<?php echo $ro['avail'] === 0 ? ' disabled' : ''; ?>>
                    <span class="fmdb-reg-hospedaje__label"><?php echo esc_html( $ro['label'] ); ?></span>
                    <span class="fmdb-reg-hospedaje__meta">
                        <span class="fmdb-reg-hospedaje__price">$<?php echo esc_html( number_format( $ro['price'], 0, '.', ',' ) ); ?> MXN</span>
                        <?php if ( $ro['avail'] === 0 ) : ?>
                            <span class="fmdb-reg-hospedaje__avail fmdb-reg-hospedaje__avail--soldout">Agotado</span>
                        <?php elseif ( $ro['avail'] > 0 ) : ?>
                            <span class="fmdb-reg-hospedaje__avail"><?php echo esc_html( $ro['avail'] ); ?> disponible<?php echo $ro['avail'] !== 1 ? 's' : ''; ?></span>
                        <?php endif; ?>
                    </span>
                </label>
                <?php endforeach; ?>

                <div class="fmdb-reg-hospedaje__group-label">Solo cuarto</div>
                <p class="fmdb-reg-hospedaje__group-desc">1 Noche de hospedaje · No incluye comidas. Incluye acceso al venue.</p>
                <?php
                $h_sc_opts = [
                    'sencilla_sc'  => [ 'label' => 'Habitación Sencilla',  'price' => $h_price_sencilla_sc,  'avail' => $h_avail_sencilla  ],
                    'doble_sc'     => [ 'label' => 'Habitación Doble',     'price' => $h_price_doble_sc,     'avail' => $h_avail_doble     ],
                    'triple_sc'    => [ 'label' => 'Habitación Triple',    'price' => $h_price_triple_sc,    'avail' => $h_avail_triple    ],
                    'cuadruple_sc' => [ 'label' => 'Habitación Cuádruple', 'price' => $h_price_cuadruple_sc, 'avail' => $h_avail_cuadruple ],
                ];
                foreach ( $h_sc_opts as $rv => $ro ) : ?>
                <label class="fmdb-reg-hospedaje__option<?php echo $ro['avail'] === 0 ? ' fmdb-reg-hospedaje__option--soldout' : ''; ?>">
                    <input type="radio" name="fmdb_hospedaje_pick_<?php echo $eid; ?>" value="<?php echo esc_attr( $rv ); ?>"<?php echo $ro['avail'] === 0 ? ' disabled' : ''; ?>>
                    <span class="fmdb-reg-hospedaje__label"><?php echo esc_html( $ro['label'] ); ?></span>
                    <span class="fmdb-reg-hospedaje__meta">
                        <span class="fmdb-reg-hospedaje__price">$<?php echo esc_html( number_format( $ro['price'], 0, '.', ',' ) ); ?> MXN</span>
                        <?php if ( $ro['avail'] === 0 ) : ?>
                            <span class="fmdb-reg-hospedaje__avail fmdb-reg-hospedaje__avail--soldout">Agotado</span>
                        <?php elseif ( $ro['avail'] > 0 ) : ?>
                            <span class="fmdb-reg-hospedaje__avail"><?php echo esc_html( $ro['avail'] ); ?> disponible<?php echo $ro['avail'] !== 1 ? 's' : ''; ?></span>
                        <?php endif; ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- ── Guest names: doble=1, triple=2, cuádruple=3 ── -->
            <div id="fmdb-hospedaje-guests-<?php echo $eid; ?>" hidden>
                <div class="fmdb-reg-form__section-title fmdb-reg-hospedaje__guests-title">Datos de huéspedes</div>
                <?php for ( $g = 1; $g <= 3; $g++ ) : ?>
                <div id="fmdb-hosp-guest-<?php echo $eid; ?>-<?php echo $g; ?>" hidden>
                    <p class="fmdb-reg-hospedaje__guest-label">Huésped <?php echo $g; ?></p>
                    <div class="fmdb-reg-form__row">
                        <div class="fmdb-reg-form__field">
                            <label>Nombre</label>
                            <input type="text" id="fmdb-hosp-gname-<?php echo $eid; ?>-<?php echo $g; ?>"
                                   pattern="[A-Za-záéíóúüñÁÉÍÓÚÜÑ '\-]+"
                                   title="Solo se permiten letras, espacios y guiones"
                                   placeholder="Nombre">
                        </div>
                        <div class="fmdb-reg-form__field">
                            <label>Apellido</label>
                            <input type="text" id="fmdb-hosp-gapellido-<?php echo $eid; ?>-<?php echo $g; ?>"
                                   pattern="[A-Za-záéíóúüñÁÉÍÓÚÜÑ '\-]+"
                                   title="Solo se permiten letras, espacios y guiones"
                                   placeholder="Apellido">
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <button class="fmdb-btn fmdb-btn--secondary fmdb-reg-section__btn"
                    id="fmdb-hospedaje-submit-<?php echo $eid; ?>" type="button" disabled>
                Agregar hospedaje →
            </button>
            <div class="fmdb-reg-section__msg" id="fmdb-hospedaje-msg-<?php echo $eid; ?>"></div>

            <!-- ── TOTAL SECTION ── -->
            <div class="fmdb-reg-total">
                <div class="fmdb-reg-total__row">
                    <span class="fmdb-reg-total__label">Inscripción</span>
                    <span class="fmdb-reg-total__val" id="fmdb-total-reg-<?php echo $eid; ?>">—</span>
                </div>
                <div class="fmdb-reg-total__row">
                    <span class="fmdb-reg-total__label">Entrada al venue</span>
                    <span class="fmdb-reg-total__val" id="fmdb-total-venue-<?php echo $eid; ?>">$<?php echo esc_html( number_format( $entrada_fee, 2 ) ); ?> MXN</span>
                </div>
                <div class="fmdb-reg-total__row">
                    <span class="fmdb-reg-total__label">Hospedaje</span>
                    <span class="fmdb-reg-total__val" id="fmdb-total-hosp-<?php echo $eid; ?>">—</span>
                </div>
                <div class="fmdb-reg-total__divider"></div>
                <div class="fmdb-reg-total__row fmdb-reg-total__row--grand">
                    <span class="fmdb-reg-total__label">Total</span>
                    <span class="fmdb-reg-total__val" id="fmdb-total-grand-<?php echo $eid; ?>">—</span>
                </div>
                <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>"
                   class="fmdb-btn fmdb-btn--primary fmdb-reg-total__btn">
                    Ir al pago →
                </a>
            </div>

            <script>
            (function () {
                var eid        = <?php echo $eid; ?>;
                var fee        = <?php echo (float) $fee; ?>;
                var entradaFee = <?php echo (float) $entrada_fee; ?>;
                var maxPlayers = <?php echo (int) $max_players; ?>;
                var ajaxUrl    = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
                var regNonce  = '<?php echo esc_js( wp_create_nonce( 'fmdb_add_registration' ) ); ?>';
                var hospNonce = '<?php echo esc_js( wp_create_nonce( 'fmdb_add_hospedaje' ) ); ?>';
                var hospPrices = {
                    '': 0,
                    'doble':        <?php echo (float) $h_price_doble; ?>,
                    'triple':       <?php echo (float) $h_price_triple; ?>,
                    'sencilla':     <?php echo (float) $h_price_sencilla; ?>,
                    'cuadruple':    <?php echo (float) $h_price_cuadruple; ?>,
                    'doble_sc':     <?php echo (float) $h_price_doble_sc; ?>,
                    'triple_sc':    <?php echo (float) $h_price_triple_sc; ?>,
                    'sencilla_sc':  <?php echo (float) $h_price_sencilla_sc; ?>,
                    'cuadruple_sc': <?php echo (float) $h_price_cuadruple_sc; ?>
                };
                var catLabels  = <?php echo json_encode( $cat_labels ); ?>;

                function fmtMXN(n) {
                    return '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MXN';
                }

                // ── Extra player forms ──
                function renderExtraPlayers(n) {
                    var container = document.getElementById('fmdb-extra-players-' + eid);
                    if (!container) return;
                    // Preserve existing values so re-renders on validation error don't wipe names.
                    var prev = {};
                    container.querySelectorAll('input').forEach(function (inp) { prev[inp.name] = inp.value; });
                    container.innerHTML = '';
                    if (n < 2) return;
                    var pat = "[A-Za-záéíóúüñÁÉÍÓÚÜÑ '\\-]+";
                    for (var i = 2; i <= n; i++) {
                        var lbl = document.createElement('p');
                        lbl.className = 'fmdb-reg-extra-player__label';
                        lbl.textContent = 'Jugador ' + i;
                        container.appendChild(lbl);
                        var row = document.createElement('div');
                        row.className = 'fmdb-reg-form__row';
                        ['nombre', 'apellido'].forEach(function (field) {
                            var wrap = document.createElement('div');
                            wrap.className = 'fmdb-reg-form__field';
                            var label = document.createElement('label');
                            label.textContent = field.charAt(0).toUpperCase() + field.slice(1);
                            var input = document.createElement('input');
                            input.type        = 'text';
                            input.name        = 'fmdb_extra_player[' + i + '][' + field + ']';
                            input.required    = true;
                            input.pattern     = pat;
                            input.title       = 'Solo se permiten letras, espacios y guiones';
                            input.placeholder = label.textContent;
                            input.value       = prev[input.name] || '';
                            wrap.appendChild(label);
                            wrap.appendChild(input);
                            row.appendChild(wrap);
                        });
                        container.appendChild(row);
                    }
                }

                // ── Grand total ──
                var regAmt   = 0;
                var hospAmt  = 0;
                var venueAmt = 210; // updated dynamically; $210 base + entradaFee × extra players

                function updateGrandTotal() {
                    var regEl   = document.getElementById('fmdb-total-reg-'   + eid);
                    var venueEl = document.getElementById('fmdb-total-venue-' + eid);
                    var hospEl  = document.getElementById('fmdb-total-hosp-'  + eid);
                    var grandEl = document.getElementById('fmdb-total-grand-' + eid);
                    if (regEl)   regEl.textContent   = regAmt  > 0 ? fmtMXN(regAmt)  : '—';
                    if (venueEl) venueEl.textContent = venueAmt > 0 ? fmtMXN(venueAmt) : 'Incluido';
                    if (hospEl)  hospEl.textContent  = hospAmt > 0 ? fmtMXN(hospAmt) : '—';
                    if (grandEl) grandEl.textContent = (regAmt + venueAmt + hospAmt) > 0 ? fmtMXN(regAmt + venueAmt + hospAmt) : '—';
                }

                // ── Tab switching ──
                var tabs       = document.querySelectorAll('#fmdb-reg-tabs-' + eid + ' .fmdb-reg-tab');
                var countInput = document.getElementById('fmdb-count-' + eid);

                tabs.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        tabs.forEach(function (b) { b.classList.remove('is-active'); });
                        btn.classList.add('is-active');
                        var target = btn.dataset.target;
                        ['fmdb-form-team-' + eid, 'fmdb-form-ind-' + eid].forEach(function (id) {
                            var el = document.getElementById(id);
                            if (el) el.classList.toggle('fmdb-reg-form--hidden', id !== target);
                        });
                        // Recompute regAmt for active tab
                        if (target === 'fmdb-form-team-' + eid) {
                            var n = countInput ? parseInt(countInput.value, 10) : 0;
                            if (n >= 1 && n <= maxPlayers) {
                                regAmt   = fee * n;
                                venueAmt = computeVenueAmt(n, currentRoomCoverage);
                            } else {
                                regAmt = venueAmt = 0;
                            }
                        } else {
                            regAmt   = fee;
                            venueAmt = computeVenueAmt(1, currentRoomCoverage);
                        }
                        updateGrandTotal();
                    });
                });

                // Team: player count drives regAmt, venueAmt, and extra player forms
                if (countInput) {
                    countInput.addEventListener('input', function () {
                        var n = parseInt(countInput.value, 10);
                        if (n >= 1 && n <= maxPlayers) {
                            regAmt   = fee * n;
                            venueAmt = computeVenueAmt(n, currentRoomCoverage);
                        } else {
                            regAmt = venueAmt = 0;
                        }
                        renderExtraPlayers(isNaN(n) ? 0 : n);
                        updateGrandTotal();
                    });
                    var initN = parseInt(countInput.value, 10);
                    if (initN >= 1 && initN <= maxPlayers) {
                        regAmt   = fee * initN;
                        venueAmt = computeVenueAmt(initN, currentRoomCoverage);
                        renderExtraPlayers(initN);
                        updateGrandTotal();
                    }
                } else {
                    // Individual tab is active (no count input) — regAmt = fee × 1
                    var activeTabEl = document.querySelector('#fmdb-reg-tabs-' + eid + ' .fmdb-reg-tab.is-active');
                    if (activeTabEl && activeTabEl.dataset.target === 'fmdb-form-ind-' + eid) {
                        regAmt = fee;
                        updateGrandTotal();
                    }
                }

                // ── Hospedaje radio → hospAmt + enable button ──
                var hospOpts = document.getElementById('fmdb-hospedaje-opts-' + eid);
                var hospBtn  = document.getElementById('fmdb-hospedaje-submit-' + eid);

                // ── Guest fields ──
                var guestCounts   = { '': 0, 'sencilla': 0, 'sencilla_sc': 0, 'doble': 1, 'doble_sc': 1, 'triple': 2, 'triple_sc': 2, 'cuadruple': 3, 'cuadruple_sc': 3 };
                var roomCapacities = { '': 0, 'sencilla': 1, 'sencilla_sc': 1, 'doble': 2, 'doble_sc': 2, 'triple': 3, 'triple_sc': 3, 'cuadruple': 4, 'cuadruple_sc': 4 };
                var namePattern   = /^[A-Za-záéíóúüñÁÉÍÓÚÜÑ '\-]+$/;
                var currentRoomCoverage = 0;

                function computeVenueAmt(playerCount, coverage) {
                    var uncovered = Math.max(0, playerCount - coverage);
                    return entradaFee * uncovered;
                }

                function showGuestFields(roomVal) {
                    var count = guestCounts[roomVal] !== undefined ? guestCounts[roomVal] : 0;
                    var section = document.getElementById('fmdb-hospedaje-guests-' + eid);
                    if (section) section.hidden = (count === 0);
                    for (var g = 1; g <= 3; g++) {
                        var row   = document.getElementById('fmdb-hosp-guest-' + eid + '-' + g);
                        var nameI = document.getElementById('fmdb-hosp-gname-' + eid + '-' + g);
                        var apeI  = document.getElementById('fmdb-hosp-gapellido-' + eid + '-' + g);
                        var show  = g <= count;
                        if (row)   row.hidden = !show;
                        if (nameI) { if (!show) nameI.value = ''; }
                        if (apeI)  { if (!show) apeI.value  = ''; }
                    }
                }

                if (hospOpts) {
                    hospOpts.querySelectorAll('input[type="radio"]').forEach(function (r) {
                        r.addEventListener('change', function () {
                            hospAmt             = hospPrices[r.value] || 0;
                            currentRoomCoverage = roomCapacities[r.value] || 0;
                            var n = countInput ? parseInt(countInput.value, 10) : 1;
                            venueAmt = computeVenueAmt(isNaN(n) || n < 1 ? 1 : n, currentRoomCoverage);
                            if (hospBtn) hospBtn.disabled = (r.value === '');
                            showGuestFields(r.value);
                            updateGrandTotal();
                        });
                    });
                }

                // ── Registration AJAX submit ──
                function handleRegSubmit(form, msgId) {
                    if (!form || form.tagName !== 'FORM') return;
                    var btn = form.querySelector('button[type="submit"]');
                    if (!btn) return;
                    var msgEl = document.getElementById(msgId);

                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        var phoneInput = form.querySelector('input[type="tel"]');
                        if (phoneInput) {
                            var digits = (phoneInput.value || '').replace(/\D/g, '');
                            if (digits.length < 8) { phoneInput.setCustomValidity('Ingresa al menos 8 dígitos.'); phoneInput.reportValidity(); return; }
                            phoneInput.setCustomValidity('');
                        }
                        if (!form.checkValidity()) { form.reportValidity(); return; }

                        var origText = btn.textContent;
                        btn.disabled = true;
                        btn.textContent = 'Agregando…';
                        if (msgEl) { msgEl.textContent = ''; msgEl.className = 'fmdb-reg-section__msg'; }

                        var fd = new FormData(form);
                        fd.append('action', 'fmdb_add_registration');
                        fd.append('nonce',  regNonce);

                        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                if (data.success) {
                                    btn.textContent = '✓ Agregado';
                                    btn.classList.add('fmdb-btn--success');
                                    if (msgEl) { msgEl.textContent = 'Inscripción agregada al carrito.'; msgEl.classList.add('fmdb-reg-section__msg--ok'); }
                                } else {
                                    btn.disabled = false;
                                    btn.textContent = origText;
                                    if (msgEl) { msgEl.textContent = (data.data && data.data.message) || 'Error al agregar. Intenta de nuevo.'; msgEl.classList.add('fmdb-reg-section__msg--err'); }
                                }
                            })
                            .catch(function () {
                                btn.disabled = false;
                                btn.textContent = origText;
                                if (msgEl) { msgEl.textContent = 'Error de conexión. Intenta de nuevo.'; msgEl.classList.add('fmdb-reg-section__msg--err'); }
                            });
                    });
                }

                var teamForm = document.getElementById('fmdb-form-team-' + eid);
                var indForm  = document.getElementById('fmdb-form-ind-'  + eid);
                handleRegSubmit(teamForm, 'fmdb-reg-msg-team-' + eid);
                handleRegSubmit(indForm,  'fmdb-reg-msg-ind-'  + eid);

                // ── Hospedaje AJAX ──
                if (hospBtn) {
                    hospBtn.addEventListener('click', function () {
                        var room = hospOpts ? hospOpts.querySelector('input[type="radio"]:checked') : null;
                        if (!room || !room.value) return;
                        var msgEl    = document.getElementById('fmdb-hospedaje-msg-' + eid);
                        var origText = hospBtn.textContent;

                        // Validate guest fields before submitting
                        var gCount = guestCounts[room.value] || 0;
                        for (var g = 1; g <= gCount; g++) {
                            var gn = document.getElementById('fmdb-hosp-gname-' + eid + '-' + g);
                            var ga = document.getElementById('fmdb-hosp-gapellido-' + eid + '-' + g);
                            var nv = gn ? gn.value.trim() : '';
                            var av = ga ? ga.value.trim() : '';
                            if (!nv || !namePattern.test(nv) || !av || !namePattern.test(av)) {
                                if (msgEl) { msgEl.textContent = 'Ingresa nombre y apellido de todos los huéspedes (solo letras).'; msgEl.className = 'fmdb-reg-section__msg fmdb-reg-section__msg--err'; }
                                return;
                            }
                        }

                        hospBtn.disabled = true;
                        hospBtn.textContent = 'Agregando…';
                        if (msgEl) { msgEl.textContent = ''; msgEl.className = 'fmdb-reg-section__msg'; }

                        var fd = new FormData();
                        fd.append('action',                  'fmdb_add_hospedaje');
                        fd.append('nonce',                   hospNonce);
                        fd.append('fmdb_hospedaje_room',     room.value);
                        fd.append('fmdb_hospedaje_event_id', eid);
                        fd.append('fmdb_guest_count',        gCount);
                        for (var g2 = 1; g2 <= gCount; g2++) {
                            var gn2 = document.getElementById('fmdb-hosp-gname-' + eid + '-' + g2);
                            var ga2 = document.getElementById('fmdb-hosp-gapellido-' + eid + '-' + g2);
                            if (gn2) fd.append('fmdb_guest_name_' + g2,     gn2.value.trim());
                            if (ga2) fd.append('fmdb_guest_apellido_' + g2, ga2.value.trim());
                        }

                        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                if (data.success) {
                                    hospBtn.textContent = '✓ Hospedaje agregado';
                                    hospBtn.classList.add('fmdb-btn--success');
                                    if (msgEl) { msgEl.textContent = 'Habitación agregada al carrito.'; msgEl.classList.add('fmdb-reg-section__msg--ok'); }
                                } else {
                                    hospBtn.disabled = false;
                                    hospBtn.textContent = origText;
                                    if (msgEl) { msgEl.textContent = (data.data && data.data.message) || 'Error al agregar. Intenta de nuevo.'; msgEl.classList.add('fmdb-reg-section__msg--err'); }
                                }
                            })
                            .catch(function () { hospBtn.disabled = false; hospBtn.textContent = origText; });
                    });
                }

                // ── Avail badge refresh (bypasses LiteSpeed page cache) ──
                (function () {
                    var hospOptsEl = document.getElementById('fmdb-hospedaje-opts-' + eid);
                    if (!hospOptsEl) return;

                    function applyAvail(room, avail) {
                        var radio = hospOptsEl.querySelector('input[value="' + room + '"]');
                        if (!radio) return;
                        var label = radio.closest('.fmdb-reg-hospedaje__option');
                        if (!label) return;
                        var meta     = label.querySelector('.fmdb-reg-hospedaje__meta');
                        var oldBadge = label.querySelector('.fmdb-reg-hospedaje__avail');
                        if (oldBadge) oldBadge.remove();

                        if (avail === -1) {
                            radio.disabled = false;
                            label.classList.remove('fmdb-reg-hospedaje__option--soldout');
                        } else if (avail === 0) {
                            radio.disabled = true;
                            label.classList.add('fmdb-reg-hospedaje__option--soldout');
                            if (meta) {
                                var badge = document.createElement('span');
                                badge.className = 'fmdb-reg-hospedaje__avail fmdb-reg-hospedaje__avail--soldout';
                                badge.textContent = 'Agotado';
                                meta.appendChild(badge);
                            }
                        } else {
                            radio.disabled = false;
                            label.classList.remove('fmdb-reg-hospedaje__option--soldout');
                            if (meta) {
                                var badge = document.createElement('span');
                                badge.className = 'fmdb-reg-hospedaje__avail';
                                badge.textContent = avail + ' disponible' + (avail !== 1 ? 's' : '');
                                meta.appendChild(badge);
                            }
                        }
                    }

                    fetch(ajaxUrl + '?action=fmdb_hospedaje_avail&event_id=' + eid, { credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (!data.success) return;
                            applyAvail('doble',        data.data.doble);
                            applyAvail('doble_sc',     data.data.doble);
                            applyAvail('triple',       data.data.triple);
                            applyAvail('triple_sc',    data.data.triple);
                            applyAvail('sencilla',     data.data.sencilla);
                            applyAvail('sencilla_sc',  data.data.sencilla);
                            applyAvail('cuadruple',    data.data.cuadruple);
                            applyAvail('cuadruple_sc', data.data.cuadruple);
                        })
                        .catch(function () { /* silent — static values remain */ });
                }());

                // ── Individual: team select → division card ──
                var indSel      = document.getElementById('fmdb-ind-sel-'      + eid);
                var indNameHid  = document.getElementById('fmdb-ind-name-'     + eid);
                var indDivCard  = document.getElementById('fmdb-ind-div-card-' + eid);
                var indHRama    = document.getElementById('fmdb-ind-hrama-'    + eid);
                var indHCat     = document.getElementById('fmdb-ind-hcat-'     + eid);
                var indRamaSpan = document.getElementById('fmdb-ind-div-rama-' + eid);
                var indCatSpan  = document.getElementById('fmdb-ind-div-cat-'  + eid);

                function showDivCard(rama, storedRama, cat) {
                    if (indRamaSpan) indRamaSpan.textContent = rama;
                    if (indCatSpan)  indCatSpan.textContent  = catLabels[cat] || cat;
                    if (indHRama) { indHRama.value = storedRama || rama; indHRama.disabled = false; }
                    if (indHCat)  { indHCat.value  = cat;  indHCat.disabled  = false; }
                    if (indDivCard) indDivCard.classList.remove('fmdb-reg-form--hidden');
                }

                function hideDivCard() {
                    if (indDivCard) indDivCard.classList.add('fmdb-reg-form--hidden');
                    if (indHRama) { indHRama.value = ''; indHRama.disabled = true; }
                    if (indHCat)  { indHCat.value  = ''; indHCat.disabled  = true; }
                }

                if (indSel) {
                    indSel.addEventListener('change', function () {
                        var opt = indSel.options[indSel.selectedIndex];
                        if (opt.value === '') { if (indNameHid) indNameHid.value = ''; hideDivCard(); }
                        else { if (indNameHid) indNameHid.value = opt.value; showDivCard(opt.dataset.rama || '', opt.dataset.storedRama || opt.dataset.rama || '', opt.dataset.cat || ''); }
                    });
                }

                // ── Spanish required-field tooltip ──
                [teamForm, indForm].forEach(function (form) {
                    if (!form) return;
                    form.querySelectorAll('[required]').forEach(function (field) {
                        field.addEventListener('invalid', function () {
                            if (field.validity.valueMissing) field.setCustomValidity('Por favor llene este campo');
                        });
                        field.addEventListener('input',  function () { if (!field.validity.valueMissing) field.setCustomValidity(''); });
                        field.addEventListener('change', function () { if (!field.validity.valueMissing) field.setCustomValidity(''); });
                    });
                });

                // ── Phone digit-only validation ──
                function validatePhone(input) {
                    input.setCustomValidity((input.value || '').replace(/\D/g, '').length < 8 ? 'Ingresa al menos 8 dígitos.' : '');
                }
                [teamForm, indForm].forEach(function (form) {
                    if (!form) return;
                    var phoneInput = form.querySelector('input[type="tel"]');
                    if (!phoneInput) return;
                    phoneInput.addEventListener('input', function () {
                        var pos     = phoneInput.selectionStart;
                        var cleaned = phoneInput.value.replace(/\D/g, '');
                        if (phoneInput.value !== cleaned) { phoneInput.value = cleaned; phoneInput.setSelectionRange(pos - 1, pos - 1); }
                        validatePhone(phoneInput);
                    });
                });
            })();
            </script>

        <?php else : ?>
            <p class="fmdb-reg-box__login-msg">Debes iniciar sesión para inscribirte.</p>
            <a href="<?php echo esc_url( wp_login_url( get_permalink( $event_id ) ) ); ?>"
               class="fmdb-btn fmdb-btn--primary fmdb-reg-box__btn">
                Iniciar sesión
            </a>
            <a href="<?php echo esc_url( home_url( '/registro/' ) ); ?>"
               class="fmdb-reg-box__register-link">
                ¿No tienes cuenta? Regístrate
            </a>
        <?php endif; ?>
    </div>
    <?php
}

/* ─── 4a. AJAX: add registration product to cart ──────────────────────────
 *
 * Bluehost has request_order=GP so $_REQUEST never contains POST data.
 * WC's add_to_cart_action() uses $_REQUEST and never fires for our form POSTs.
 * We bypass it with a custom WP AJAX action; our existing woocommerce_add_cart_item_data
 * and woocommerce_add_to_cart_validation filters still fire and read from $_POST,
 * which IS populated in AJAX requests.
 *
 * On localhost (request_order=GCP), $_REQUEST includes $_POST, so WC's native
 * add_to_cart_action() would fire on wp_loaded (priority 20) before our wp_ajax_*
 * hook and preempt the response. We remove it during our AJAX calls.
 */
add_action( 'wp_loaded', function () {
    if ( ! wp_doing_ajax() ) return;
    if ( ! in_array( $_POST['action'] ?? '', [ 'fmdb_add_registration', 'fmdb_add_hospedaje' ], true ) ) return;
    remove_action( 'wp_loaded', [ 'WC_Form_Handler', 'add_to_cart_action' ], 20 );
}, 15 );

add_action( 'wp_ajax_fmdb_add_registration', 'fmdb_ajax_add_registration' );
function fmdb_ajax_add_registration(): void {
    check_ajax_referer( 'fmdb_add_registration', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => 'Debes iniciar sesión.' ] );
    }

    $prod_id = absint( $_POST['add-to-cart'] ?? 0 );
    if ( ! $prod_id || ! wc_get_product( $prod_id ) ) {
        wp_send_json_error( [ 'message' => 'Producto inválido.' ] );
    }

    // Enforce 1 registration per cart regardless of type (team or individual).
    foreach ( WC()->cart->get_cart() as $_existing ) {
        if ( ! empty( $_existing['fmdb_event_id'] ) ) {
            wp_send_json_error( [ 'message' => 'Ya tienes una inscripción en el carrito. Finaliza el pago antes de agregar otra.' ] );
            return;
        }
    }

    // Duplicate team check — block if same name + rama already exists in any active order.
    $_ajax_event_id  = (int) get_post_meta( $prod_id, '_fmdb_reg_event_id', true );
    $_ajax_reg_type  = in_array( $_POST['fmdb_reg_type'] ?? '', [ 'team', 'individual' ], true )
                       ? $_POST['fmdb_reg_type'] : 'team';
    if ( $_ajax_event_id && $_ajax_reg_type === 'team' && ! empty( $_POST['fmdb_team_name'] ) ) {
        $_ajax_name = mb_strtolower( trim( sanitize_text_field( $_POST['fmdb_team_name'] ) ) );
        $_ajax_rama = sanitize_text_field( $_POST['fmdb_rama'] ?? '' );
        $_ajax_cat  = sanitize_text_field( $_POST['fmdb_categoria'] ?? '' );
        $_ajax_orders = wc_get_orders( [
            'meta_key'   => '_fmdb_reg_event_id',
            'meta_value' => $_ajax_event_id,
            'status'     => [ 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-completed' ],
            'limit'      => -1,
        ] );
        foreach ( $_ajax_orders as $_ao ) {
            if ( $_ao->get_meta( '_fmdb_reg_type' ) !== 'team' ) continue;
            foreach ( $_ao->get_items() as $_ai ) {
                if ( mb_strtolower( trim( (string) $_ai->get_meta( 'Equipo' ) ) ) === $_ajax_name
                  && $_ai->get_meta( 'Rama' ) === $_ajax_rama
                  && $_ai->get_meta( 'Categoría' ) === $_ajax_cat ) {
                    wp_send_json_error( [ 'message' => 'Este equipo ya ha sido registrado.' ] );
                    return;
                }
            }
        }
    }

    $result = WC()->cart->add_to_cart( $prod_id, 1, 0, [], [] );

    if ( $result === false ) {
        $notices = wc_get_notices( 'error' );
        $msg     = ! empty( $notices ) ? wp_strip_all_tags( $notices[0]['notice'] ) : 'No se pudo agregar al carrito. Intenta de nuevo.';
        wc_clear_notices();
        wp_send_json_error( [ 'message' => $msg ] );
    }

    wc_clear_notices();
    wp_send_json_success( [ 'message' => 'Inscripción agregada.' ] );
}

// Shared helper: render Rama / Categoría selects. Each rama includes Foam + Cloth automatically.
function fmdb_reg_division_selects( string $uid, array $ramas, array $cats, array $cat_labels, string $rama_val, string $cat_val, bool $disabled = false ): string {
    ob_start();
    ?>
    <div class="fmdb-reg-form__row">
        <div class="fmdb-reg-form__field">
            <label>Rama *</label>
            <select name="fmdb_rama"<?php echo $disabled ? ' disabled' : ' required'; ?>>
                <option value="">— Seleccionar —</option>
                <?php foreach ( $ramas as $r ) : ?>
                    <option value="<?php echo esc_attr( $r ); ?>" <?php selected( $rama_val, $r ); ?>><?php echo esc_html( $r ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fmdb-reg-form__field">
            <label>Categoría *</label>
            <select name="fmdb_categoria"<?php echo $disabled ? ' disabled' : ' required'; ?>>
                <option value="">— Seleccionar —</option>
                <?php foreach ( $cats as $c ) : ?>
                    <option value="<?php echo esc_attr( $c ); ?>" <?php selected( $cat_val, $c ); ?>><?php echo esc_html( $cat_labels[ $c ] ?? $c ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <p class="fmdb-reg-form__hint">Incluye automáticamente Foam y Cloth en tu rama y en Mixto.</p>
    <?php
    return ob_get_clean();
}

/* ─── 4b. Public section: registered teams + rosters ──────────────────── */

function fmdb_event_registered_teams_section( int $event_id ): void {
    $open = get_post_meta( $event_id, '_fmdb_reg_open', true ) === 'on';
    if ( ! $open ) return;

    $teams = fmdb_reg_get_event_teams( $event_id );
    if ( empty( $teams ) ) return;

    $limits      = fmdb_reg_player_limits( $event_id );
    $min_players = $limits['min'];

    $pay_status_labels = [
        'pending'    => [ 'label' => 'Pendiente de pago', 'mod' => 'pending' ],
        'on-hold'    => [ 'label' => 'Pendiente de pago', 'mod' => 'pending' ],
        'processing' => [ 'label' => 'Pago confirmado',   'mod' => 'confirmed' ],
        'completed'  => [ 'label' => 'Inscripción completa', 'mod' => 'confirmed' ],
    ];

    $confirmed  = array_values( array_filter( $teams, fn( $t ) => $t['confirmed'] ?? false ) );
    $waitlisted = array_values( array_filter( $teams, function ( $t ) use ( $min_players ) {
        $total = ( $t['bulk_count'] ?? 0 ) + count( $t['players'] ?? [] );
        return ! ( $t['confirmed'] ?? false ) && ( $t['on_waitlist'] ?? false ) && $total >= $min_players;
    } ) );
    $incomplete = array_values( array_filter( $teams, function ( $t ) use ( $min_players ) {
        $total = ( $t['bulk_count'] ?? 0 ) + count( $t['players'] ?? [] );
        return ! ( $t['confirmed'] ?? false ) && $total < $min_players;
    } ) );

    // Use event-configured options for filters (fallback to full list).
    $all_ramas = array_values( array_filter( (array) get_post_meta( $event_id, '_fmdb_reg_ramas', true ) ) );
    $all_cats  = array_values( array_filter( (array) get_post_meta( $event_id, '_fmdb_reg_categorias', true ) ) );
    $all_ramas = array_values( array_intersect( $all_ramas, [ 'Varonil/Mixto', 'Femenil/Mixto' ] ) );
    if ( empty( $all_ramas ) ) $all_ramas = [ 'Varonil/Mixto', 'Femenil/Mixto' ];
    // Expand stored ramas to display ramas: Varonil/Mixto → Varonil; Femenil/Mixto → Femenil; Mixto appended once.
    $_display_ramas = [];
    $_has_mixto     = false;
    foreach ( $all_ramas as $_r ) {
        $_primary = strpos( $_r, 'Varonil' ) !== false ? 'Varonil' : 'Femenil';
        if ( ! in_array( $_primary, $_display_ramas, true ) ) $_display_ramas[] = $_primary;
        $_has_mixto = true;
    }
    if ( $_has_mixto ) $_display_ramas[] = 'Mixto';
    $all_ramas = $_display_ramas;
    if ( empty( $all_cats ) )  $all_cats  = [ 'Infantil', 'Libre' ];

    $sid = 'fmdb-teams-' . $event_id;

    // Helper: render a single team card.
    $render_card = function ( array $team ) use ( $pay_status_labels, $min_players ): void {
        $total = $team['bulk_count'] + count( $team['players'] );

        if ( $team['confirmed'] ?? false ) {
            $st = [ 'label' => 'Confirmado', 'mod' => 'confirmed' ];
        } elseif ( $total < $min_players ) {
            $st = [ 'label' => 'Incompleto', 'mod' => 'incomplete' ];
        } elseif ( $team['on_waitlist'] ?? false ) {
            $st = [ 'label' => 'Lista de espera', 'mod' => 'waitlist' ];
        } else {
            $st = $pay_status_labels[ $team['status'] ] ?? null;
        }
        ?>
        <div class="fmdb-reg-team-card"
             data-rama="<?php echo esc_attr( $team['rama'] ); ?>"
             data-cat="<?php echo esc_attr( $team['categoria'] ); ?>">
            <div class="fmdb-reg-team-card__header">
                <div class="fmdb-reg-team-card__header-main">
                    <span class="fmdb-reg-team-card__name"><?php echo esc_html( $team['name'] ); ?></span>
                    <?php
                    $div_str = implode( ' · ', array_filter( [ $team['rama'], $team['categoria'] ] ) );
                    if ( $div_str ) : ?>
                        <span class="fmdb-reg-team-card__div"><?php echo esc_html( $div_str ); ?></span>
                    <?php endif; ?>
                    <?php if ( $st ) : ?>
                        <span class="fmdb-reg-team-card__status fmdb-reg-status--<?php echo esc_attr( $st['mod'] ); ?>">
                            <?php echo esc_html( $st['label'] ); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ( $total > 0 ) : ?>
                <div class="fmdb-reg-team-card__count">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                    <?php echo $total; ?>
                </div>
                <?php endif; ?>
            </div>

            <ul class="fmdb-reg-team-card__roster">
                <?php if ( $team['captain'] ) : ?>
                    <li class="fmdb-reg-team-card__player">
                        <span class="fmdb-reg-team-card__role">Encargado</span>
                        <?php echo esc_html( $team['captain'] ); ?>
                    </li>
                <?php endif; ?>
                <?php foreach ( $team['extra_players'] ?? [] as $name ) : ?>
                    <li class="fmdb-reg-team-card__player">
                        <?php echo esc_html( $name ); ?>
                    </li>
                <?php endforeach; ?>
                <?php
                // Unnamed slots: total registered minus captain minus named extras.
                $unnamed = $team['bulk_count'] - ( $team['captain'] ? 1 : 0 ) - count( $team['extra_players'] ?? [] );
                if ( $unnamed > 0 ) : ?>
                    <li class="fmdb-reg-team-card__player fmdb-reg-team-card__player--pending">
                        + <?php echo $unnamed; ?> jugador<?php echo $unnamed > 1 ? 'es' : ''; ?> más
                    </li>
                <?php endif; ?>
                <?php foreach ( $team['players'] as $p ) : ?>
                    <li class="fmdb-reg-team-card__player">
                        <?php echo esc_html( $p['name'] ); ?>
                    </li>
                <?php endforeach; ?>
            </ul>

        </div>
        <?php
    };
    ?>
    <section class="fmdb-reg-teams" id="<?php echo esc_attr( $sid ); ?>">
        <h2 class="fmdb-reg-teams__title">Equipos inscritos</h2>

        <div class="fmdb-reg-teams__filters">
            <div class="fmdb-reg-teams__filter-group fmdb-reg-teams__filter-group--rama">
                <span class="fmdb-reg-teams__filter-label">Rama</span>
                <button class="fmdb-reg-filter fmdb-reg-filter--rama is-active" data-filter="rama" data-value="">Todas</button>
                <?php foreach ( $all_ramas as $r ) : ?>
                    <button class="fmdb-reg-filter fmdb-reg-filter--rama" data-filter="rama" data-value="<?php echo esc_attr( $r ); ?>"><?php echo esc_html( $r ); ?></button>
                <?php endforeach; ?>
            </div>
            <div class="fmdb-reg-teams__filter-group fmdb-reg-teams__filter-group--cat">
                <span class="fmdb-reg-teams__filter-label">Categoría</span>
                <button class="fmdb-reg-filter fmdb-reg-filter--cat is-active" data-filter="cat" data-value="">Todas</button>
                <?php foreach ( $all_cats as $c ) : ?>
                    <button class="fmdb-reg-filter fmdb-reg-filter--cat" data-filter="cat" data-value="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $c ); ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ( ! empty( $confirmed ) ) :
            // Group confirmed by rama → categoria.
            $confirmed_groups = [];
            foreach ( $confirmed as $team ) {
                $gkey = ( $team['rama'] ?? '' ) . '|' . ( $team['categoria'] ?? '' );
                $confirmed_groups[ $gkey ][] = $team;
            }
            ksort( $confirmed_groups );
        ?>
        <div class="fmdb-reg-teams__section" id="<?php echo esc_attr( $sid ); ?>-confirmed">
            <h3 class="fmdb-reg-teams__section-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Equipos confirmados
            </h3>
            <?php foreach ( $confirmed_groups as $gkey => $group_teams ) :
                [ $g_rama, $g_cat ] = explode( '|', $gkey );
                $g_label = implode( ' · ', array_filter( [ $g_rama, $g_cat ] ) );
                $g_cap   = $g_cat ? fmdb_reg_slot_cap( $event_id, $g_cat ) : 0;
                $g_count = count( $group_teams );
            ?>
            <div class="fmdb-reg-teams__subgroup"
                 data-rama="<?php echo esc_attr( $g_rama ); ?>"
                 data-cat="<?php echo esc_attr( $g_cat ); ?>">
                <h4 class="fmdb-reg-teams__subgroup-title">
                    <?php echo esc_html( $g_label ); ?>
                    <?php if ( $g_cap > 0 ) : ?>
                        <span class="fmdb-reg-teams__subgroup-cap <?php echo $g_count >= $g_cap ? 'is-full' : ''; ?>">
                            <?php echo esc_html( $g_count . '/' . $g_cap ); ?>
                        </span>
                    <?php endif; ?>
                </h4>
                <div class="fmdb-reg-teams__grid">
                    <?php foreach ( $group_teams as $team ) { $render_card( $team ); } ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $waitlisted ) ) : ?>
        <div class="fmdb-reg-teams__section" id="<?php echo esc_attr( $sid ); ?>-waitlist">
            <h3 class="fmdb-reg-teams__section-title fmdb-reg-teams__section-title--waitlist">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Lista de espera
            </h3>
            <div class="fmdb-reg-teams__grid">
                <?php foreach ( $waitlisted as $team ) { $render_card( $team ); } ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $incomplete ) ) : ?>
        <div class="fmdb-reg-teams__section" id="<?php echo esc_attr( $sid ); ?>-incomplete">
            <h3 class="fmdb-reg-teams__section-title fmdb-reg-teams__section-title--muted">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Equipos incompletos
                <span class="fmdb-reg-teams__section-count"><?php echo esc_html( count( $incomplete ) ); ?></span>
            </h3>
            <div class="fmdb-reg-teams__grid">
                <?php foreach ( $incomplete as $team ) { $render_card( $team ); } ?>
            </div>
        </div>
        <?php endif; ?>

        <script>
        (function () {
            var sid    = <?php echo json_encode( $sid ); ?>;
            var root   = document.getElementById(sid);
            if (!root) return;

            var active = { rama: '', cat: '' };

            root.querySelectorAll('.fmdb-reg-filter').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var filter = btn.dataset.filter;
                    var value  = btn.dataset.value;
                    active[filter] = value;

                    // Update active state within the same filter group.
                    btn.closest('.fmdb-reg-teams__filter-group')
                       .querySelectorAll('.fmdb-reg-filter')
                       .forEach(function (b) { b.classList.remove('is-active'); });
                    btn.classList.add('is-active');

                    applyFilters();
                });
            });

            function applyFilters() {
                root.querySelectorAll('.fmdb-reg-team-card').forEach(function (card) {
                    var ramaMatch = !active.rama || card.dataset.rama === active.rama;
                    var catMatch  = !active.cat  || card.dataset.cat  === active.cat;
                    card.classList.toggle('fmdb-reg-form--hidden', !(ramaMatch && catMatch));
                });

                // Hide confirmed sub-groups when all their cards are filtered out.
                root.querySelectorAll('.fmdb-reg-teams__subgroup').forEach(function (grp) {
                    var visible = grp.querySelectorAll('.fmdb-reg-team-card:not(.fmdb-reg-form--hidden)').length;
                    grp.classList.toggle('fmdb-reg-form--hidden', visible === 0);
                });

                // Hide section headings when all their cards are filtered out.
                ['confirmed', 'waitlist', 'incomplete'].forEach(function (key) {
                    var sec = document.getElementById(sid + '-' + key);
                    if (!sec) return;
                    var visible = sec.querySelectorAll('.fmdb-reg-team-card:not(.fmdb-reg-form--hidden)').length;
                    sec.classList.toggle('fmdb-reg-form--hidden', visible === 0);
                });
            }

        })();
        </script>
    </section>
    <?php
}

/* ─── 5. Capture team/division data in cart item ───────────────────────── */

add_filter( 'woocommerce_add_cart_item_data', function ( $cart_item_data, $product_id ) {
    // Hospedaje-only: tag item and capture event-specific price for override.
    if ( ! empty( $_POST['fmdb_hospedaje_only'] ) ) {
        $room = sanitize_text_field( wp_unslash( $_POST['fmdb_hospedaje_room'] ?? '' ) );
        if ( in_array( $room, [ 'doble', 'triple', 'sencilla', 'cuadruple', 'doble_sc', 'triple_sc', 'sencilla_sc', 'cuadruple_sc' ], true ) ) {
            $cart_item_data['fmdb_hospedaje_type'] = $room;
            $h_eid    = absint( $_POST['fmdb_hospedaje_event_id'] ?? 0 );
            $meta_map = [
                'doble'        => [ '_fmdb_hospedaje_doble_fee',        1415.0 ],
                'triple'       => [ '_fmdb_hospedaje_triple_fee',       1355.0 ],
                'sencilla'     => [ '_fmdb_hospedaje_sencilla_fee',     1200.0 ],
                'cuadruple'    => [ '_fmdb_hospedaje_cuadruple_fee',    1500.0 ],
                'doble_sc'     => [ '_fmdb_hospedaje_doble_sc_fee',      800.0 ],
                'triple_sc'    => [ '_fmdb_hospedaje_triple_sc_fee',     750.0 ],
                'sencilla_sc'  => [ '_fmdb_hospedaje_sencilla_sc_fee',   700.0 ],
                'cuadruple_sc' => [ '_fmdb_hospedaje_cuadruple_sc_fee',  900.0 ],
            ];
            [ $h_meta_k, $h_default ] = $meta_map[ $room ];
            $h_price  = $h_eid ? ( (float) get_post_meta( $h_eid, $h_meta_k, true ) ?: $h_default ) : $h_default;
            $cart_item_data['fmdb_hospedaje_price']    = $h_price;
            $cart_item_data['fmdb_hospedaje_event_id'] = $h_eid;
        }
        return $cart_item_data;
    }

    $event_id = (int) get_post_meta( $product_id, '_fmdb_reg_event_id', true );
    if ( ! $event_id ) return $cart_item_data;

    $fee         = (float) get_post_meta( $event_id, '_fmdb_reg_fee', true );
    $entrada_fee = (float) get_post_meta( $event_id, '_fmdb_reg_entrada_fee', true );
    $type        = in_array( $_POST['fmdb_reg_type'] ?? '', [ 'team', 'individual' ], true )
                   ? $_POST['fmdb_reg_type'] : 'team';

    $cart_item_data['fmdb_event_id']    = $event_id;
    $cart_item_data['fmdb_unit_fee']    = $fee;
    $cart_item_data['fmdb_entrada_fee'] = $entrada_fee;
    $cart_item_data['fmdb_reg_type']   = $type;
    $cart_item_data['fmdb_rama']       = sanitize_text_field( wp_unslash( $_POST['fmdb_rama'] ?? '' ) );
    $cart_item_data['fmdb_categoria']  = sanitize_text_field( wp_unslash( $_POST['fmdb_categoria'] ?? '' ) );
    $cart_item_data['fmdb_modalidad']  = ''; // no longer a registration axis — rama includes all modalidades

    if ( $type === 'individual' ) {
        $cart_item_data['fmdb_team_name']       = sanitize_text_field( wp_unslash( $_POST['fmdb_ind_team_name'] ?? '' ) );
        $cart_item_data['fmdb_player_name']     = sanitize_text_field( wp_unslash( $_POST['fmdb_player_name'] ?? '' ) );
        $cart_item_data['fmdb_player_apellido'] = sanitize_text_field( wp_unslash( $_POST['fmdb_player_apellido'] ?? '' ) );
        $cart_item_data['fmdb_player_phone']    = sanitize_text_field( wp_unslash( $_POST['fmdb_player_phone'] ?? '' ) );
        $cart_item_data['fmdb_player_count']    = 1;
    } else {
        $cart_item_data['fmdb_team_post_id']    = (int) ( $_POST['fmdb_team_post_id'] ?? 0 );
        $cart_item_data['fmdb_team_name']       = sanitize_text_field( wp_unslash( $_POST['fmdb_team_name'] ?? '' ) );
        $cart_item_data['fmdb_captain_name']    = sanitize_text_field( wp_unslash( $_POST['fmdb_captain_name'] ?? '' ) );
        $cart_item_data['fmdb_captain_apellido'] = sanitize_text_field( wp_unslash( $_POST['fmdb_captain_apellido'] ?? '' ) );
        $cart_item_data['fmdb_captain_phone']   = sanitize_text_field( wp_unslash( $_POST['fmdb_captain_phone'] ?? '' ) );
        $cart_item_data['fmdb_player_count']    = absint( $_POST['fmdb_player_count'] ?? 0 );

        // Capture extra player names (players 2..N).
        $extra_players = [];
        $raw_extra = isset( $_POST['fmdb_extra_player'] ) && is_array( $_POST['fmdb_extra_player'] )
                     ? $_POST['fmdb_extra_player'] : [];
        foreach ( $raw_extra as $p ) {
            if ( ! is_array( $p ) ) continue;
            $nombre   = sanitize_text_field( wp_unslash( $p['nombre']   ?? '' ) );
            $apellido = sanitize_text_field( wp_unslash( $p['apellido'] ?? '' ) );
            $extra_players[] = [ 'nombre' => $nombre, 'apellido' => $apellido ];
        }
        $cart_item_data['fmdb_extra_players'] = $extra_players;

        // Determine waitlist status: slot at capacity → waitlist.
        // Teams below the player minimum go to "Equipos incompletos" and never to waitlist.
        $player_count   = $cart_item_data['fmdb_player_count'] ?? 0;
        $_limits        = fmdb_reg_player_limits( $event_id );
        if ( $player_count < $_limits['min'] ) {
            $cart_item_data['fmdb_on_waitlist'] = '0';
        } else {
            $slot_cap = fmdb_reg_slot_cap( $event_id, $cart_item_data['fmdb_categoria'] );
            if ( $slot_cap > 0 ) {
                $slot_count = fmdb_reg_slot_team_count(
                    $event_id,
                    $cart_item_data['fmdb_rama'],
                    $cart_item_data['fmdb_categoria']
                );
                $cart_item_data['fmdb_on_waitlist'] = $slot_count >= $slot_cap ? '1' : '0';
            } else {
                $cart_item_data['fmdb_on_waitlist'] = '0';
            }
        }
    }

    return $cart_item_data;
}, 10, 2 );

/* ─── 6a. Venue entry fee ──────────────────────────────────────────────── */

// Venue fee: $210 captain + entradaFee × extras, discounted by hospedaje room capacity.
add_action( 'woocommerce_cart_calculate_fees', function ( \WC_Cart $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    $room_cap = [
        'sencilla' => 1, 'sencilla_sc' => 1,
        'doble'    => 2, 'doble_sc'    => 2,
        'triple'   => 3, 'triple_sc'   => 3,
        'cuadruple'=> 4, 'cuadruple_sc'=> 4,
    ];

    $room_label = [
        'sencilla'    => 'habitación sencilla',  'sencilla_sc'  => 'habitación sencilla',
        'doble'       => 'habitación doble',      'doble_sc'     => 'habitación doble',
        'triple'      => 'habitación triple',     'triple_sc'    => 'habitación triple',
        'cuadruple'   => 'habitación cuádruple',  'cuadruple_sc' => 'habitación cuádruple',
    ];

    $total_players  = 0;
    $total_coverage = 0;
    $coverage_label = '';
    $venue_fee      = 0.0;
    $has_reg        = false;

    foreach ( $cart->get_cart() as $item ) {
        if ( ! empty( $item['fmdb_hospedaje_type'] ) ) {
            $htype           = $item['fmdb_hospedaje_type'];
            $total_coverage += $room_cap[ $htype ] ?? 0;
            if ( ! $coverage_label ) {
                $coverage_label = $room_label[ $htype ] ?? 'hospedaje';
            } else {
                $coverage_label = 'hospedaje'; // multiple rooms — use generic label
            }
            continue;
        }
        if ( empty( $item['fmdb_event_id'] ) ) continue;
        $has_reg        = true;
        $total_players += max( 1, (int) ( $item['fmdb_player_count'] ?? 1 ) );
        $venue_fee      = (float) ( $item['fmdb_entrada_fee'] ?? 0 );
    }

    if ( ! $has_reg || $total_players === 0 || $venue_fee <= 0 ) return;

    // One line per player; each covered by hospedaje pays $0.
    for ( $i = 1; $i <= $total_players; $i++ ) {
        $label   = $i === 1 ? 'Encargado' : "Jugador {$i}";
        $covered = $total_coverage >= $i;
        if ( $covered ) {
            $cart->add_fee( "Entrada al venue - {$label} (incluida en {$coverage_label})", 0.0, false );
        } else {
            $cart->add_fee( "Entrada al venue - {$label}", $venue_fee, false );
        }
    }
} );

/* ─── 6. Per-player price override ────────────────────────────────────── */

add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    foreach ( $cart->get_cart() as $item ) {
        if ( ! empty( $item['fmdb_hospedaje_price'] ) ) {
            $item['data']->set_price( (float) $item['fmdb_hospedaje_price'] );
            continue;
        }
        if ( empty( $item['fmdb_event_id'] ) || empty( $item['fmdb_unit_fee'] ) ) continue;
        $count = max( 1, (int) $item['fmdb_player_count'] );
        $item['data']->set_price( (float) $item['fmdb_unit_fee'] * $count );
    }
}, 20 );

/* ─── 7. Show team/division data in cart / checkout order summary ──────── */

add_filter( 'woocommerce_get_item_data', function ( $data, $cart_item ) {
    if ( empty( $cart_item['fmdb_event_id'] ) ) return $data;

    $type    = $cart_item['fmdb_reg_type'] ?? 'team';
    $div_str = implode( ' · ', array_filter( [
        $cart_item['fmdb_rama']      ?? '',
        $cart_item['fmdb_categoria'] ?? '',
    ] ) );

    $data[] = [ 'name' => 'Evento',   'value' => get_the_title( $cart_item['fmdb_event_id'] ) ];
    $data[] = [ 'name' => 'Equipo',   'value' => $cart_item['fmdb_team_name'] ?? '' ];
    $data[] = [ 'name' => 'División', 'value' => $div_str ];

    if ( $type === 'individual' ) {
        $data[] = [ 'name' => 'Jugador',  'value' => $cart_item['fmdb_player_name'] ?? '' ];
        $data[] = [ 'name' => 'Apellido', 'value' => $cart_item['fmdb_player_apellido'] ?? '' ];
        if ( ! empty( $cart_item['fmdb_player_phone'] ) ) {
            $data[] = [ 'name' => 'Teléfono', 'value' => $cart_item['fmdb_player_phone'] ];
        }
    } else {
        $data[] = [ 'name' => 'Encargado',  'value' => $cart_item['fmdb_captain_name'] ?? '' ];
        $data[] = [ 'name' => 'Apellido',  'value' => $cart_item['fmdb_captain_apellido'] ?? '' ];
        $data[] = [ 'name' => 'Teléfono',  'value' => $cart_item['fmdb_captain_phone'] ?? '' ];
        $data[] = [ 'name' => 'Jugadores', 'value' => $cart_item['fmdb_player_count'] ?? 0 ];
        foreach ( $cart_item['fmdb_extra_players'] ?? [] as $i => $p ) {
            $data[] = [ 'name' => 'Jugador ' . ( $i + 2 ), 'value' => trim( $p['nombre'] . ' ' . $p['apellido'] ) ];
        }
    }

    return $data;
}, 10, 2 );

/* ─── 8. Validate registration on add-to-cart ──────────────────────────── */

add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id ) {
    // Hospedaje-only: only server-side capacity check needed; WC handles add-to-cart normally.
    if ( ! empty( $_POST['fmdb_hospedaje_only'] ) ) {
        $room      = sanitize_text_field( wp_unslash( $_POST['fmdb_hospedaje_room'] ?? '' ) );
        $h_eid     = absint( $_POST['fmdb_hospedaje_event_id'] ?? 0 );
        $base_room = str_replace( '_sc', '', $room ); // doble_sc → doble, etc.
        $max_key   = "_fmdb_hospedaje_{$base_room}_max";
        $max       = $h_eid ? (int) get_post_meta( $h_eid, $max_key, true ) : 0;
        if ( $max > 0 && fmdb_hospedaje_sold_count( $h_eid, $base_room ) >= $max ) {
            wc_add_notice( 'Lo sentimos, ya no hay disponibilidad para esa habitación.', 'error' );
            return false;
        }
        return $passed;
    }

    $event_id = (int) get_post_meta( $product_id, '_fmdb_reg_event_id', true );
    if ( ! $event_id ) return $passed;

    // Limit: only 1 registration per cart to prevent venue-fee gaming.
    foreach ( WC()->cart->get_cart() as $_existing ) {
        if ( ! empty( $_existing['fmdb_event_id'] ) ) {
            wc_add_notice( 'Ya tienes una inscripción en el carrito. Finaliza el pago antes de agregar otra.', 'error' );
            return false;
        }
    }

    if ( get_post_meta( $event_id, '_fmdb_reg_open', true ) !== 'on' ) {
        wc_add_notice( 'La inscripción para este torneo no está abierta.', 'error' );
        return false;
    }

    $deadline = get_post_meta( $event_id, '_fmdb_reg_deadline', true );
    if ( $deadline && strtotime( $deadline . ' 23:59:59' ) < time() ) {
        wc_add_notice( 'La fecha límite de inscripción ha pasado.', 'error' );
        return false;
    }

    $type = $_POST['fmdb_reg_type'] ?? 'team';

    if ( empty( $_POST['fmdb_rama'] ) ) {
        wc_add_notice( 'Selecciona una rama.', 'error' );
        $passed = false;
    }
    if ( empty( $_POST['fmdb_categoria'] ) ) {
        wc_add_notice( 'Selecciona una categoría.', 'error' );
        $passed = false;
    }

    if ( $type === 'individual' ) {
        if ( empty( $_POST['fmdb_ind_team_name'] ) ) {
            wc_add_notice( 'Ingresa el nombre de tu equipo.', 'error' );
            $passed = false;
        }
        if ( empty( $_POST['fmdb_player_name'] ) ) {
            wc_add_notice( 'Ingresa tu nombre.', 'error' );
            $passed = false;
        } elseif ( ! preg_match( '/^[A-Za-záéíóúüñÁÉÍÓÚÜÑ \'\-]+$/u', $_POST['fmdb_player_name'] ) ) {
            wc_add_notice( 'El nombre solo puede contener letras.', 'error' );
            $passed = false;
        }
        if ( empty( $_POST['fmdb_player_apellido'] ) ) {
            wc_add_notice( 'Ingresa tu apellido.', 'error' );
            $passed = false;
        } elseif ( ! preg_match( '/^[A-Za-záéíóúüñÁÉÍÓÚÜÑ \'\-]+$/u', $_POST['fmdb_player_apellido'] ) ) {
            wc_add_notice( 'El apellido solo puede contener letras.', 'error' );
            $passed = false;
        }
        if ( empty( $_POST['fmdb_player_phone'] ) ) {
            wc_add_notice( 'Ingresa tu teléfono.', 'error' );
            $passed = false;
        }
        // Enforce roster cap per team — match by name + primary display rama (not Mixto).
        if ( $passed && ! empty( $_POST['fmdb_ind_team_name'] ) ) {
            $_ind_limits      = fmdb_reg_player_limits( $event_id );
            $teams            = fmdb_reg_get_event_teams( $event_id );
            $target           = mb_strtolower( trim( sanitize_text_field( $_POST['fmdb_ind_team_name'] ) ) );
            $_ind_stored_rama = sanitize_text_field( $_POST['fmdb_rama'] ?? '' );
            // Derive display rama: 'Varonil/Mixto' → 'Varonil', 'Femenil/Mixto' → 'Femenil'.
            $_ind_disp_rama = strpos( $_ind_stored_rama, 'Varonil' ) !== false ? 'Varonil'
                            : ( strpos( $_ind_stored_rama, 'Femenil' ) !== false ? 'Femenil' : $_ind_stored_rama );
            foreach ( $teams as $t ) {
                if ( mb_strtolower( trim( $t['name'] ) ) === $target && $t['rama'] === $_ind_disp_rama ) {
                    $total = ( $t['bulk_count'] ?? 0 ) + count( $t['players'] ?? [] );
                    if ( $total >= $_ind_limits['max'] ) {
                        wc_add_notice( 'Este equipo ya alcanzó el límite de ' . $_ind_limits['max'] . ' jugadores.', 'error' );
                        $passed = false;
                    }
                    break;
                }
            }
        }
    } else {
        if ( empty( $_POST['fmdb_team_name'] ) ) {
            wc_add_notice( 'Ingresa el nombre del equipo.', 'error' );
            $passed = false;
        } else {
            $submitted_name = mb_strtolower( trim( sanitize_text_field( $_POST['fmdb_team_name'] ) ) );
            $submitted_rama = sanitize_text_field( $_POST['fmdb_rama'] ?? '' );
            $submitted_cat  = sanitize_text_field( $_POST['fmdb_categoria'] ?? '' );
            // Check orders in any non-cancelled status (blocks from the moment a previous checkout created an order).
            $_dup_orders = wc_get_orders( [
                'meta_key'   => '_fmdb_reg_event_id',
                'meta_value' => $event_id,
                'status'     => [ 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-completed' ],
                'limit'      => -1,
            ] );
            foreach ( $_dup_orders as $_dord ) {
                if ( $_dord->get_meta( '_fmdb_reg_type' ) !== 'team' ) continue;
                foreach ( $_dord->get_items() as $_ditem ) {
                    if ( mb_strtolower( trim( (string) $_ditem->get_meta( 'Equipo' ) ) ) === $submitted_name
                      && $_ditem->get_meta( 'Rama' ) === $submitted_rama
                      && $_ditem->get_meta( 'Categoría' ) === $submitted_cat ) {
                        wc_add_notice( 'Este equipo ya ha sido registrado.', 'error' );
                        $passed = false;
                        break 2;
                    }
                }
            }
            // Also block if the same team is already sitting in the current cart.
            if ( $passed && ! is_null( WC()->cart ) ) {
                foreach ( WC()->cart->get_cart() as $_citem ) {
                    if ( isset( $_citem['fmdb_team_name'], $_citem['fmdb_rama'] )
                      && mb_strtolower( trim( $_citem['fmdb_team_name'] ) ) === $submitted_name
                      && $_citem['fmdb_rama'] === $submitted_rama
                      && ( $_citem['fmdb_categoria'] ?? '' ) === $submitted_cat
                      && ( (int) ( $_citem['fmdb_event_id'] ?? 0 ) ) === $event_id ) {
                        wc_add_notice( 'Este equipo ya ha sido registrado.', 'error' );
                        $passed = false;
                        break;
                    }
                }
            }
        }
        if ( empty( $_POST['fmdb_captain_name'] ) ) {
            wc_add_notice( 'Ingresa el nombre del encargado.', 'error' );
            $passed = false;
        } elseif ( ! preg_match( '/^[A-Za-záéíóúüñÁÉÍÓÚÜÑ \'\-]+$/u', $_POST['fmdb_captain_name'] ) ) {
            wc_add_notice( 'El nombre del encargado solo puede contener letras.', 'error' );
            $passed = false;
        }
        if ( empty( $_POST['fmdb_captain_apellido'] ) ) {
            wc_add_notice( 'Ingresa el apellido del encargado.', 'error' );
            $passed = false;
        } elseif ( ! preg_match( '/^[A-Za-záéíóúüñÁÉÍÓÚÜÑ \'\-]+$/u', $_POST['fmdb_captain_apellido'] ) ) {
            wc_add_notice( 'El apellido del encargado solo puede contener letras.', 'error' );
            $passed = false;
        }
        if ( empty( $_POST['fmdb_captain_phone'] ) ) {
            wc_add_notice( 'Ingresa el teléfono del encargado.', 'error' );
            $passed = false;
        }
        $count = (int) ( $_POST['fmdb_player_count'] ?? 0 );
        $_team_limits = fmdb_reg_player_limits( $event_id );
        if ( $count < 1 || $count > $_team_limits['max'] ) {
            wc_add_notice( 'El número de jugadores debe ser entre 1 y ' . $_team_limits['max'] . '.', 'error' );
            $passed = false;
        }

        // Validate extra player names (jugadores 2..N).
        $raw_extra = isset( $_POST['fmdb_extra_player'] ) && is_array( $_POST['fmdb_extra_player'] )
                     ? $_POST['fmdb_extra_player'] : [];
        for ( $i = 2; $i <= $count; $i++ ) {
            $p        = $raw_extra[ $i ] ?? [];
            $nombre   = trim( $p['nombre']   ?? '' );
            $apellido = trim( $p['apellido'] ?? '' );
            if ( empty( $nombre ) ) {
                wc_add_notice( "Ingresa el nombre del jugador $i.", 'error' );
                $passed = false;
            } elseif ( ! preg_match( '/^[A-Za-záéíóúüñÁÉÍÓÚÜÑ \'\-]+$/u', $nombre ) ) {
                wc_add_notice( "El nombre del jugador $i solo puede contener letras.", 'error' );
                $passed = false;
            }
            if ( empty( $apellido ) ) {
                wc_add_notice( "Ingresa el apellido del jugador $i.", 'error' );
                $passed = false;
            } elseif ( ! preg_match( '/^[A-Za-záéíóúüñÁÉÍÓÚÜÑ \'\-]+$/u', $apellido ) ) {
                wc_add_notice( "El apellido del jugador $i solo puede contener letras.", 'error' );
                $passed = false;
            }
        }
    }

    return $passed;
}, 10, 2 );

/* ─── 9a. AJAX endpoint: hospedaje-only add-to-cart ───────────────────────
 *
 * Bluehost PHP has request_order=GP, so $_REQUEST never contains POST data.
 * WC's add_to_cart_action() uses $_REQUEST and therefore never fires for our
 * form POSTs. We bypass it entirely with a custom WP AJAX action. JS calls
 * this, we add to cart here, and WC's PHP-shutdown hook saves the session
 * normally before wp_die() returns the response. JS then redirects to checkout.
 */
add_action( 'wp_ajax_nopriv_fmdb_add_hospedaje', 'fmdb_ajax_add_hospedaje' );
add_action( 'wp_ajax_fmdb_add_hospedaje',        'fmdb_ajax_add_hospedaje' );
function fmdb_ajax_add_hospedaje(): void {
    check_ajax_referer( 'fmdb_add_hospedaje', 'nonce' );

    $room = sanitize_text_field( wp_unslash( $_POST['fmdb_hospedaje_room'] ?? '' ) );
    if ( ! in_array( $room, [ 'doble', 'triple', 'sencilla', 'cuadruple', 'doble_sc', 'triple_sc', 'sencilla_sc', 'cuadruple_sc' ], true ) ) {
        wp_send_json_error( [ 'message' => 'Habitación inválida.' ] );
    }

    $h_ids = fmdb_hospedaje_product_ids();
    $h_pid = $h_ids[ $room ] ?? 0;
    if ( ! $h_pid ) {
        wp_send_json_error( [ 'message' => 'Producto no encontrado.' ] );
    }

    $h_eid     = absint( $_POST['fmdb_hospedaje_event_id'] ?? 0 );
    $base_room = str_replace( '_sc', '', $room ); // doble_sc → doble, etc.
    $max_key   = "_fmdb_hospedaje_{$base_room}_max";
    $max       = $h_eid ? (int) get_post_meta( $h_eid, $max_key, true ) : 0;

    // Prevent adding hospedaje twice for the same event.
    foreach ( WC()->cart->get_cart() as $_item ) {
        if ( ! empty( $_item['fmdb_hospedaje_event_id'] ) && (int) $_item['fmdb_hospedaje_event_id'] === $h_eid ) {
            wp_send_json_error( [ 'message' => 'Ya tienes hospedaje en tu carrito para este evento.' ] );
        }
    }

    if ( $max > 0 && fmdb_hospedaje_sold_count( $h_eid, $base_room ) >= $max ) {
        wp_send_json_error( [ 'message' => 'Lo sentimos, ya no hay disponibilidad para esa habitación.' ] );
    }

    // Validate and collect guest names (doble=1, triple=2, cuádruple=3; sencilla=0).
    $guest_count_map = [ 'doble' => 1, 'triple' => 2, 'cuadruple' => 3, 'doble_sc' => 1, 'triple_sc' => 2, 'cuadruple_sc' => 3 ];
    $expected_guests = $guest_count_map[ $room ] ?? 0;
    $guests          = [];
    $name_rx         = '/^[A-Za-záéíóúüñÁÉÍÓÚÜÑ \'\-]+$/u';
    for ( $g = 1; $g <= $expected_guests; $g++ ) {
        $gname = sanitize_text_field( wp_unslash( $_POST["fmdb_guest_name_{$g}"] ?? '' ) );
        $gape  = sanitize_text_field( wp_unslash( $_POST["fmdb_guest_apellido_{$g}"] ?? '' ) );
        if ( empty( $gname ) || ! preg_match( $name_rx, $gname ) ) {
            wp_send_json_error( [ 'message' => "Ingresa un nombre válido para el huésped {$g}." ] );
        }
        if ( empty( $gape ) || ! preg_match( $name_rx, $gape ) ) {
            wp_send_json_error( [ 'message' => "Ingresa un apellido válido para el huésped {$g}." ] );
        }
        $guests[] = [ 'nombre' => $gname, 'apellido' => $gape ];
    }

    $meta_map  = [
        'doble'        => [ '_fmdb_hospedaje_doble_fee',        1415.0 ],
        'triple'       => [ '_fmdb_hospedaje_triple_fee',       1355.0 ],
        'sencilla'     => [ '_fmdb_hospedaje_sencilla_fee',     1200.0 ],
        'cuadruple'    => [ '_fmdb_hospedaje_cuadruple_fee',    1500.0 ],
        'doble_sc'     => [ '_fmdb_hospedaje_doble_sc_fee',      800.0 ],
        'triple_sc'    => [ '_fmdb_hospedaje_triple_sc_fee',     750.0 ],
        'sencilla_sc'  => [ '_fmdb_hospedaje_sencilla_sc_fee',   700.0 ],
        'cuadruple_sc' => [ '_fmdb_hospedaje_cuadruple_sc_fee',  900.0 ],
    ];
    [ $h_meta, $h_default ] = $meta_map[ $room ];
    $h_price   = $h_eid ? ( (float) get_post_meta( $h_eid, $h_meta, true ) ?: $h_default ) : $h_default;

    $result = WC()->cart->add_to_cart( $h_pid, 1, 0, [], [
        'fmdb_hospedaje_type'     => $room,
        'fmdb_hospedaje_price'    => $h_price,
        'fmdb_hospedaje_event_id' => $h_eid,
        'fmdb_hospedaje_guests'   => $guests,
    ] );

    if ( $result === false ) {
        wp_send_json_error( [ 'message' => 'No se pudo agregar al carrito. Intenta de nuevo.' ] );
    }

    wp_send_json_success( [ 'checkout_url' => wc_get_checkout_url() ] );
}

/* ─── 9b. Hard capacity gate at order creation ─────────────────────────────
 *
 * Fires for both classic and Blocks checkout, after the order row exists but
 * before payment is processed. We sort all valid orders for this event+room
 * by order ID (auto-increment = DB creation order) and cancel any that fall
 * outside the allowed count. This is deterministic and race-safe without
 * needing DB locks: the order with the lower ID always wins.
 */
add_action( 'woocommerce_checkout_order_created', function ( \WC_Order $order ) {
    foreach ( $order->get_items() as $item ) {
        $label = $item->get_meta( 'Habitación' );
        if ( ! $label ) continue;

        $label_base_map = [
            'Doble'       => 'doble',     'Doble SC'      => 'doble',
            'Triple'      => 'triple',    'Triple SC'     => 'triple',
            'Sencilla'    => 'sencilla',  'Sencilla SC'   => 'sencilla',
            'Cuádruple'   => 'cuadruple', 'Cuádruple SC'  => 'cuadruple',
        ];
        $room_labels_map = [
            'doble'     => [ 'Doble',     'Doble SC'     ],
            'triple'    => [ 'Triple',    'Triple SC'    ],
            'sencilla'  => [ 'Sencilla',  'Sencilla SC'  ],
            'cuadruple' => [ 'Cuádruple', 'Cuádruple SC' ],
        ];
        $base_room    = $label_base_map[ $label ] ?? null;
        if ( ! $base_room ) continue;
        $match_labels = $room_labels_map[ $base_room ];
        $h_eid = (int) $order->get_meta( '_fmdb_hospedaje_event_id' )
              ?: (int) $order->get_meta( '_fmdb_reg_event_id' );
        if ( ! $h_eid ) continue;

        $max_key = "_fmdb_hospedaje_{$base_room}_max";
        $max     = (int) get_post_meta( $h_eid, $max_key, true );
        if ( $max <= 0 ) continue;

        // Collect all order IDs for this event+base-room (both variants count toward shared cap).
        $all_orders = wc_get_orders( [
            'limit'      => -1,
            'status'     => [ 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-completed' ],
            'meta_query' => [
                'relation' => 'OR',
                [ 'key' => '_fmdb_reg_event_id',       'value' => $h_eid, 'compare' => '=' ],
                [ 'key' => '_fmdb_hospedaje_event_id', 'value' => $h_eid, 'compare' => '=' ],
            ],
        ] );

        $matching_ids = [];
        foreach ( $all_orders as $o ) {
            foreach ( $o->get_items() as $it ) {
                if ( in_array( $it->get_meta( 'Habitación' ), $match_labels, true ) ) {
                    $matching_ids[] = $o->get_id();
                    break;
                }
            }
        }
        sort( $matching_ids ); // ascending = DB creation order
        $allowed_ids = array_slice( $matching_ids, 0, $max );

        if ( ! in_array( $order->get_id(), $allowed_ids, true ) ) {
            $order->update_status( 'cancelled', 'Sin disponibilidad de hospedaje al momento del pago.' );
            $order->save();
            // Exception is caught by both classic WC checkout and WC Blocks REST handler.
            throw new \Exception( 'Lo sentimos, ya no hay disponibilidad para la habitación seleccionada. Tu pedido ha sido cancelado.' );
        }
    }
} );

/* ─── 9c. Redirect to checkout after adding a registration product (classic WC) ── */

add_filter( 'woocommerce_add_to_cart_redirect', function ( $url ) {
    if ( wc_notice_count( 'error' ) > 0 ) return $url;
    if ( ! isset( $_REQUEST['add-to-cart'] ) ) return $url;

    $pid = absint( $_REQUEST['add-to-cart'] );

    // Hospedaje-only: send straight to checkout; WC handled the cart add normally.
    if ( ! empty( $_POST['fmdb_hospedaje_only'] ) ) {
        $h_ids = fmdb_hospedaje_product_ids();
        if ( in_array( $pid, array_values( $h_ids ), true ) ) {
            return wc_get_checkout_url();
        }
    }

    if ( ! get_post_meta( $pid, '_fmdb_reg_event_id', true ) ) return $url;

    $hospedaje = sanitize_text_field( $_REQUEST['fmdb_hospedaje'] ?? '' );
    if ( in_array( $hospedaje, [ 'doble', 'triple' ], true ) ) {
        $h_ids    = fmdb_hospedaje_product_ids();
        $h_pid    = $h_ids[ $hospedaje ] ?? 0;
        $h_eid    = (int) get_post_meta( $pid, '_fmdb_reg_event_id', true );
        $h_meta   = $hospedaje === 'doble' ? '_fmdb_hospedaje_doble_fee'  : '_fmdb_hospedaje_triple_fee';
        $h_maxkey = $hospedaje === 'doble' ? '_fmdb_hospedaje_doble_max'  : '_fmdb_hospedaje_triple_max';
        $h_def    = $hospedaje === 'doble' ? 1415.0 : 1355.0;
        $h_price  = $h_eid ? ( (float) get_post_meta( $h_eid, $h_meta, true ) ?: $h_def ) : $h_def;
        $h_max    = $h_eid ? (int) get_post_meta( $h_eid, $h_maxkey, true ) : 0;
        $can_add  = ! $h_max || fmdb_hospedaje_sold_count( $h_eid, $hospedaje ) < $h_max;
        if ( $h_pid && $can_add ) {
            WC()->cart->add_to_cart( $h_pid, 1, 0, [], [
                'fmdb_hospedaje_type'  => $hospedaje,
                'fmdb_hospedaje_price' => $h_price,
            ] );
        } elseif ( ! $can_add ) {
            wc_add_notice( 'Lo sentimos, ya no hay disponibilidad para esa habitación.', 'error' );
        }
    }

    return wc_get_checkout_url();
} );

// Hard gate: if WC error notices are present when landing on checkout,
// bounce back to the referring event page so errors are visible.
add_action( 'template_redirect', function () {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) return;
    if ( wc_notice_count( 'error' ) === 0 ) return;

    $referer = wp_get_referer();
    wp_safe_redirect( $referer ?: home_url( '/' ) );
    exit;
}, 5 );

/* ─── 10. Save team/division data as order line-item meta ──────────────── */

add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $cart_item_key, $values, $order ) {
    if ( empty( $values['fmdb_event_id'] ) ) return;

    $type = $values['fmdb_reg_type'] ?? 'team';

    $item->update_meta_data( 'Evento',    get_the_title( $values['fmdb_event_id'] ) );
    $item->update_meta_data( 'Equipo',    $values['fmdb_team_name'] ?? '' );
    $item->update_meta_data( 'Rama',      $values['fmdb_rama'] ?? '' );
    $item->update_meta_data( 'Categoría', $values['fmdb_categoria'] ?? '' );
    $item->update_meta_data( 'Modalidad', $values['fmdb_modalidad'] ?? '' );
    $item->update_meta_data( 'Tipo',      $type === 'individual' ? 'Individual' : 'Equipo' );

    if ( $type === 'individual' ) {
        $item->update_meta_data( 'Jugador',  $values['fmdb_player_name'] ?? '' );
        $item->update_meta_data( 'Apellido', $values['fmdb_player_apellido'] ?? '' );
        if ( ! empty( $values['fmdb_player_phone'] ) ) {
            $item->update_meta_data( 'Teléfono', $values['fmdb_player_phone'] );
        }
    } else {
        $item->update_meta_data( 'Encargado',  $values['fmdb_captain_name'] ?? '' );
        $item->update_meta_data( 'Apellido',  $values['fmdb_captain_apellido'] ?? '' );
        $item->update_meta_data( 'Teléfono',  $values['fmdb_captain_phone'] ?? '' );
        $item->update_meta_data( 'Jugadores', $values['fmdb_player_count'] ?? 0 );
        foreach ( $values['fmdb_extra_players'] ?? [] as $i => $p ) {
            $item->update_meta_data( 'Jugador ' . ( $i + 2 ), trim( $p['nombre'] . ' ' . $p['apellido'] ) );
        }
        if ( ! empty( $values['fmdb_team_post_id'] ) ) {
            $item->update_meta_data( '_fmdb_team_post_id', $values['fmdb_team_post_id'] );
        }
    }

    $order->update_meta_data( '_fmdb_reg_event_id', $values['fmdb_event_id'] );
    $order->update_meta_data( '_fmdb_reg_type', $type );

    if ( $type === 'team' && isset( $values['fmdb_on_waitlist'] ) ) {
        $order->update_meta_data( '_fmdb_on_waitlist', $values['fmdb_on_waitlist'] );
    }
}, 10, 4 );

/* ─── 11. Hospedaje: cart display + order meta ──────────────────────────── */

// Append meal indicator to the product name on cart load — works for both classic and Blocks cart.
add_filter( 'woocommerce_get_cart_item_from_session', function ( $cart_item, $values ) {
    if ( empty( $cart_item['fmdb_hospedaje_type'] ) ) return $cart_item;
    if ( empty( $cart_item['data'] ) || ! is_object( $cart_item['data'] ) ) return $cart_item;
    $sc = in_array( $cart_item['fmdb_hospedaje_type'], [ 'doble_sc', 'triple_sc', 'sencilla_sc', 'cuadruple_sc' ], true );
    $cart_item['data']->set_name( $cart_item['data']->get_name() . ( $sc ? ' – Solo habitación' : ' – Con comidas' ) );
    return $cart_item;
}, 10, 2 );

add_filter( 'woocommerce_get_item_data', function ( $data, $cart_item ) {
    if ( empty( $cart_item['fmdb_hospedaje_type'] ) ) return $data;
    $type      = $cart_item['fmdb_hospedaje_type'];
    $label_map = [
        'doble'        => 'Habitación Doble',
        'triple'       => 'Habitación Triple',
        'sencilla'     => 'Habitación Sencilla',
        'cuadruple'    => 'Habitación Cuádruple',
        'doble_sc'     => 'Habitación Doble – Solo cuarto',
        'triple_sc'    => 'Habitación Triple – Solo cuarto',
        'sencilla_sc'  => 'Habitación Sencilla – Solo cuarto',
        'cuadruple_sc' => 'Habitación Cuádruple – Solo cuarto',
    ];
    $inc_full = '1 Noche de hospedaje · 1 Desayuno Americano · 1 Comida Emplatada (3 tiempos) · 1 Cena Emplatada (3 tiempos) · Acceso al venue';
    $data[] = [ 'name' => 'Habitación', 'value' => $label_map[ $type ] ?? $type ];
    $data[] = [ 'name' => 'Incluye',    'value' => in_array( $type, [ 'doble_sc', 'triple_sc', 'sencilla_sc', 'cuadruple_sc' ], true ) ? '1 Noche de hospedaje · Acceso al venue' : $inc_full ];
    foreach ( $cart_item['fmdb_hospedaje_guests'] ?? [] as $i => $guest ) {
        $data[] = [ 'name' => 'Huésped ' . ( $i + 1 ), 'value' => $guest['nombre'] . ' ' . $guest['apellido'] ];
    }
    return $data;
}, 10, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $cart_item_key, $values, $order ) {
    if ( empty( $values['fmdb_hospedaje_type'] ) ) return;
    $type     = $values['fmdb_hospedaje_type'];
    $hab_map  = [
        'doble'        => 'Doble',
        'triple'       => 'Triple',
        'sencilla'     => 'Sencilla',
        'cuadruple'    => 'Cuádruple',
        'doble_sc'     => 'Doble SC',
        'triple_sc'    => 'Triple SC',
        'sencilla_sc'  => 'Sencilla SC',
        'cuadruple_sc' => 'Cuádruple SC',
    ];
    $sc = in_array( $type, [ 'doble_sc', 'triple_sc', 'sencilla_sc', 'cuadruple_sc' ], true );
    $item->set_name( $item->get_name() . ( $sc ? ' – Solo habitación' : ' – Con comidas' ) );
    $inc_full = '1 Noche de hospedaje · 1 Desayuno Americano · 1 Comida Emplatada (3 tiempos) · 1 Cena Emplatada (3 tiempos) · Acceso al venue';
    $item->update_meta_data( 'Habitación', $hab_map[ $type ] ?? $type );
    $item->update_meta_data( 'Incluye', $sc ? '1 Noche de hospedaje · Acceso al venue' : $inc_full );
    foreach ( $values['fmdb_hospedaje_guests'] ?? [] as $i => $guest ) {
        $item->update_meta_data( 'Huésped ' . ( $i + 1 ), $guest['nombre'] . ' ' . $guest['apellido'] );
    }
    if ( ! empty( $values['fmdb_hospedaje_event_id'] ) ) {
        $order->update_meta_data( '_fmdb_hospedaje_event_id', (int) $values['fmdb_hospedaje_event_id'] );
    }
}, 10, 4 );

// Suppress quantity selector for hospedaje items (classic cart).
add_filter( 'woocommerce_cart_item_quantity', function ( $product_quantity, $cart_item_key, $cart_item ) {
    if ( isset( $cart_item['fmdb_hospedaje_type'] ) ) {
        return (string) $cart_item['quantity'];
    }
    return $product_quantity;
}, 10, 3 );

// Mark hospedaje products as sold-individually so WC Blocks hides the quantity stepper.
add_filter( 'woocommerce_is_sold_individually', function ( $sold_individually, $product ) {
    if ( $sold_individually ) return true;
    return in_array( $product->get_id(), array_values( fmdb_hospedaje_product_ids() ), true );
}, 10, 2 );

/* ─── 12. Bank transfer instructions on order confirmation ─────────────── */

add_action( 'woocommerce_thankyou', function ( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    $has_reg = false;
    foreach ( $order->get_items() as $item ) {
        if ( $item->get_meta( 'Equipo' ) ) { $has_reg = true; break; }
    }
    if ( ! $has_reg ) return;
    if ( $order->get_payment_method() !== 'bacs' ) return;

    $total = $order->get_formatted_order_total();
    ?>
    <div class="fmdb-reg-thankyou">
        <h2 class="fmdb-reg-thankyou__title">Instrucciones de pago</h2>
        <p>Tu inscripción está <strong>pendiente de confirmación de pago</strong>. Realiza tu depósito bancario con los datos a continuación y envía tu comprobante por WhatsApp al <strong>55 1432 9482</strong>.</p>

        <div class="fmdb-bank-details">
            <h3 class="fmdb-bank-details__title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="15" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                CUENTA SANTANDER
            </h3>
            <dl class="fmdb-bank-details__list">
                <dt>Sucursal</dt>       <dd>7796 Aeropuerto T2</dd>
                <dt>Número de cuenta</dt><dd>65-50757463-0</dd>
                <dt>CLABE</dt>          <dd class="fmdb-bank-details__clabe">014180655075746304</dd>
                <dt>Beneficiario</dt>   <dd>Federación Mexicana de Dodgeball AC</dd>
                <dt>Monto</dt>          <dd class="fmdb-bank-details__total"><?php echo wp_kses_post( $total ); ?></dd>
            </dl>
        </div>

        <p class="fmdb-reg-thankyou__ref">
            <strong>Referencia:</strong> Pedido #<?php echo esc_html( $order->get_order_number() ); ?>
        </p>
    </div>
    <?php
}, 5 );
