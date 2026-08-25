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
        'name'        => __( 'Fecha límite inscripción', 'fmdb' ),
        'id'          => '_fmdb_reg_deadline',
        'type'        => 'text_date',
        'date_format' => 'Y-m-d',
    ] );
    $cmb->add_field( [
        'name'       => __( 'Cupo por rama/modalidad (Libre)', 'fmdb' ),
        'desc'       => __( '0 = sin límite. Aplica por cada Rama × Modalidad.', 'fmdb' ),
        'id'         => '_fmdb_reg_max_teams',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0' ],
    ] );
    $cmb->add_field( [
        'name'       => __( 'Cupo por rama/modalidad (Infantil)', 'fmdb' ),
        'desc'       => __( '0 = sin límite. Aplica por cada Rama × Modalidad en categoría Infantil.', 'fmdb' ),
        'id'         => '_fmdb_reg_max_teams_infantil',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0' ],
    ] );
    $cmb->add_field( [
        'name'    => __( 'Ramas', 'fmdb' ),
        'desc'    => __( 'Dejar vacío para mostrar todas.', 'fmdb' ),
        'id'      => '_fmdb_reg_ramas',
        'type'    => 'multicheck',
        'options' => [ 'Femenil' => 'Femenil', 'Mixta' => 'Mixta', 'Varonil' => 'Varonil' ],
    ] );
    $cmb->add_field( [
        'name'    => __( 'Categorías', 'fmdb' ),
        'desc'    => __( 'Dejar vacío para mostrar todas.', 'fmdb' ),
        'id'      => '_fmdb_reg_categorias',
        'type'    => 'multicheck',
        'options' => [ 'Infantil' => 'Infantil (8-12 años)', 'Libre' => 'Libre (13+ años)' ],
    ] );
    $cmb->add_field( [
        'name'    => __( 'Modalidades', 'fmdb' ),
        'desc'    => __( 'Dejar vacío para mostrar todas.', 'fmdb' ),
        'id'      => '_fmdb_reg_modalidades',
        'type'    => 'multicheck',
        'options' => [ 'Foam' => 'Foam', 'Cloth' => 'Cloth' ],
    ] );
} );

/* ─── 2. Sync WC product on event save ────────────────────────────────── */

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
            $key = mb_strtolower( trim( $team_name ) ) . '|' . $rama . '|' . $categoria . '|' . $modalidad;

            if ( ! isset( $teams[ $key ] ) ) {
                $teams[ $key ] = [
                    'name'        => $team_name,
                    'rama'        => $rama,
                    'categoria'   => $categoria,
                    'modalidad'   => $modalidad,
                    'captain'     => '',
                    'bulk_count'  => 0,
                    'order_id'    => 0,
                    'status'      => '',
                    'on_waitlist' => false,
                    'confirmed'   => false,
                    'players'     => [],
                ];
            }

            if ( $reg_type === 'team' ) {
                $teams[ $key ]['captain']    = $item->get_meta( 'Capitán' );
                $teams[ $key ]['bulk_count'] = (int) $item->get_meta( 'Jugadores' );
                $teams[ $key ]['order_id']   = $order->get_id();
                $teams[ $key ]['status']     = $order->get_status();
                $teams[ $key ]['on_waitlist'] = $order->get_meta( '_fmdb_on_waitlist' ) === '1';
            } else {
                $player_name = $item->get_meta( 'Jugador' );
                if ( $player_name ) {
                    $teams[ $key ]['players'][] = [
                        'name'   => $player_name,
                        'status' => $order->get_status(),
                    ];
                }
            }
        }
    }

    // Compute confirmed: paid + ≥7 players + not on waitlist.
    foreach ( $teams as &$team ) {
        $total = $team['bulk_count'] + count( $team['players'] );
        $team['confirmed'] = in_array( $team['status'], [ 'processing', 'completed' ], true )
                          && $total >= 7
                          && ! ( $team['on_waitlist'] ?? false );
    }
    unset( $team );

    $all = apply_filters( 'fmdb_reg_event_teams', array_values( $teams ), $event_id );

    // Normalize fixture/filtered data that may be missing computed fields.
    return array_map( function ( $t ) {
        $t['on_waitlist'] = $t['on_waitlist'] ?? false;
        if ( ! isset( $t['confirmed'] ) ) {
            $total = ( $t['bulk_count'] ?? 0 ) + count( $t['players'] ?? [] );
            $t['confirmed'] = in_array( $t['status'] ?? '', [ 'processing', 'completed' ], true )
                           && $total >= 7
                           && ! $t['on_waitlist'];
        }
        return $t;
    }, $all );
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

// Count non-waitlist team registrations for a specific slot.
// Counts any non-cancelled order (pending/on-hold/processing/completed) so a reserved
// slot is held even before payment clears, preventing double-booking.
function fmdb_reg_slot_team_count( int $event_id, string $rama, string $modalidad, string $categoria ): int {
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
              && $item->get_meta( 'Modalidad' ) === $modalidad
              && $item->get_meta( 'Categoría' ) === $categoria ) {
                $count++;
                break;
            }
        }
    }
    return (int) apply_filters( 'fmdb_reg_slot_team_count', $count, $event_id, $rama, $modalidad, $categoria );
}

/* ─── 4. Frontend: registration card ──────────────────────────────────── */

function fmdb_event_registration_box( int $event_id ): void {
    if ( ! function_exists( 'wc_get_product' ) ) return;

    $open     = get_post_meta( $event_id, '_fmdb_reg_open', true ) === 'on';
    $fee      = (float) get_post_meta( $event_id, '_fmdb_reg_fee', true );
    $prod_id  = (int) get_post_meta( $event_id, '_fmdb_reg_product_id', true );
    $deadline = get_post_meta( $event_id, '_fmdb_reg_deadline', true );
    $max      = (int) get_post_meta( $event_id, '_fmdb_reg_max_teams', true );
    $ramas    = array_values( array_filter( (array) get_post_meta( $event_id, '_fmdb_reg_ramas', true ) ) );
    $cats     = array_values( array_filter( (array) get_post_meta( $event_id, '_fmdb_reg_categorias', true ) ) );
    $mods     = array_values( array_filter( (array) get_post_meta( $event_id, '_fmdb_reg_modalidades', true ) ) );

    if ( ! $open || $fee < 0 || ! $prod_id ) return;

    $product = wc_get_product( $prod_id );
    if ( ! $product || $product->get_status() !== 'publish' ) return;

    $past_deadline = $deadline && strtotime( $deadline . ' 23:59:59' ) < time();
    $closed        = $past_deadline;

    // Fallback: show all options if admin left division fields empty
    if ( empty( $ramas ) ) $ramas = [ 'Femenil', 'Mixta', 'Varonil' ];
    if ( empty( $cats ) )  $cats  = [ 'Infantil', 'Libre' ];
    if ( empty( $mods ) )  $mods  = [ 'Foam', 'Cloth' ];

    $cat_labels = [ 'Infantil' => 'Infantil (8-12 años)', 'Libre' => 'Libre (13+ años)' ];

    // Shared POST values (restored on validation error)
    $active_tab    = in_array( $_POST['fmdb_reg_type'] ?? '', [ 'team', 'individual' ], true )
                     ? $_POST['fmdb_reg_type'] : 'team';
    $rama_val      = sanitize_text_field( $_POST['fmdb_rama'] ?? '' );
    $cat_val       = sanitize_text_field( $_POST['fmdb_categoria'] ?? '' );
    $mod_val       = sanitize_text_field( $_POST['fmdb_modalidad'] ?? '' );

    // Team form values
    $team_post_id  = 0;
    $team_name_val = esc_attr( $_POST['fmdb_team_name'] ?? '' );
    $captain_val   = esc_attr( $_POST['fmdb_captain_name'] ?? '' );
    $phone_val     = esc_attr( $_POST['fmdb_captain_phone'] ?? '' );
    $count_val     = (int) ( $_POST['fmdb_player_count'] ?? 0 );

    // Individual form values
    $ind_team_val    = esc_attr( $_POST['fmdb_ind_team_name'] ?? '' );
    $player_name_val = esc_attr( $_POST['fmdb_player_name'] ?? '' );
    $player_phone_val = esc_attr( $_POST['fmdb_player_phone'] ?? '' );

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
                                $captain_val = esc_attr( $u->display_name );
                            }
                            break;
                        }
                    }
                }
                if ( ! $captain_val ) $captain_val = esc_attr( $current_user->display_name );
            }
        }

        if ( ! $player_name_val ) $player_name_val = esc_attr( $current_user->display_name );
    }

    // Teams already registered for this event (from WC orders)
    $registered_teams = fmdb_reg_get_event_teams( $event_id );

    // Check if user's team already has a bulk registration for this event
    $team_already_registered = false;
    $team_reg_details        = null;
    if ( $user_team ) {
        $team_lower = mb_strtolower( trim( $user_team->post_title ) );
        foreach ( $registered_teams as $rt ) {
            if ( mb_strtolower( trim( $rt['name'] ) ) === $team_lower && $rt['order_id'] ) {
                $team_already_registered = true;
                $team_reg_details        = $rt;
                break;
            }
        }
    }

    $confirmed_count = count( array_filter( $registered_teams, fn( $t ) => $t['confirmed'] ?? false ) );

    $fee_fmt  = number_format( $fee, 2 );
    $eid      = (int) $event_id;
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

        <?php if ( $deadline ) : ?>
        <div class="fmdb-evento-single__meta-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <div>
                <strong>Fecha límite</strong>
                <span><?php echo esc_html( date_i18n( 'j \d\e F, Y', strtotime( $deadline ) ) ); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $confirmed_count > 0 || $max > 0 ) : ?>
        <div class="fmdb-evento-single__meta-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <div>
                <strong>Equipos confirmados</strong>
                <span><?php echo esc_html( $confirmed_count ); ?><?php if ( $max > 0 ) echo ' · cupo ' . esc_html( $max ) . '/rama'; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $closed ) : ?>
            <span class="fmdb-reg-box__closed">Inscripción cerrada</span>

        <?php elseif ( is_user_logged_in() ) : ?>

            <!-- Registration type tabs -->
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
                        <input type="text" name="fmdb_captain_name" required value="<?php echo $captain_val; ?>">
                    </div>
                    <div class="fmdb-reg-form__field">
                        <label>Teléfono *</label>
                        <input type="tel" name="fmdb_captain_phone" required
                               value="<?php echo $phone_val; ?>" placeholder="55 xxxx xxxx">
                    </div>
                </div>

                <div class="fmdb-reg-form__section-title">División</div>
                <?php echo fmdb_reg_division_selects( $eid . 't', $ramas, $cats, $mods, $cat_labels, $rama_val, $cat_val, $mod_val ); ?>

                <div class="fmdb-reg-form__section-title">Plantel</div>

                <div class="fmdb-reg-form__field">
                    <label>Número de jugadores * <span class="fmdb-reg-form__range">(mín. 1, máx. 9)</span></label>
                    <input type="number" name="fmdb_player_count" required min="1" max="9"
                           value="<?php echo $count_val > 0 ? $count_val : ''; ?>"
                           class="fmdb-reg-count-input" id="fmdb-count-<?php echo $eid; ?>">
                    <span class="fmdb-reg-form__hint">El coach no cuenta como jugador.</span>
                </div>

                <div class="fmdb-reg-fee-preview" id="fmdb-fee-team-<?php echo $eid; ?>">
                    <span class="fmdb-reg-fee-preview__label">Total estimado</span>
                    <span class="fmdb-reg-fee-preview__amount" id="fmdb-fee-team-amt-<?php echo $eid; ?>">—</span>
                </div>

                <button type="submit" class="fmdb-btn fmdb-btn--primary fmdb-reg-box__btn">
                    Inscribir equipo →
                </button>
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
                                $rt_div = implode( ' · ', array_filter( [ $rt['rama'], $rt['categoria'], $rt['modalidad'] ] ) );
                            ?>
                                <option value="<?php echo esc_attr( $rt['name'] ); ?>"
                                        data-rama="<?php echo esc_attr( $rt['rama'] ); ?>"
                                        data-cat="<?php echo esc_attr( $rt['categoria'] ); ?>"
                                        data-mod="<?php echo esc_attr( $rt['modalidad'] ); ?>"
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
                        <input type="text" name="fmdb_player_name" required value="<?php echo $player_name_val; ?>">
                    </div>
                    <div class="fmdb-reg-form__field">
                        <label>Teléfono *</label>
                        <input type="tel" name="fmdb_player_phone" required
                               value="<?php echo $player_phone_val; ?>" placeholder="55 xxxx xxxx">
                    </div>
                </div>

                <?php if ( ! empty( $registered_teams ) ) : ?>
                <div class="fmdb-reg-form__section-title">División</div>
                <?php
                $ind_rama_val  = $ind_reg_match ? $ind_reg_match['rama']      : '';
                $ind_cat_val   = $ind_reg_match ? $ind_reg_match['categoria']  : '';
                $ind_mod_val   = $ind_reg_match ? $ind_reg_match['modalidad']  : '';
                $show_div_card = (bool) $ind_reg_match;
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
                    <div class="fmdb-reg-ind-div-card__row">
                        <span class="fmdb-reg-ind-div-card__label">Modalidad</span>
                        <span class="fmdb-reg-ind-div-card__val" id="fmdb-ind-div-mod-<?php echo $eid; ?>"><?php echo esc_html( $ind_mod_val ); ?></span>
                    </div>
                    <input type="hidden" name="fmdb_rama"      id="fmdb-ind-hrama-<?php echo $eid; ?>"
                           value="<?php echo esc_attr( $ind_rama_val ); ?>"<?php echo $show_div_card ? '' : ' disabled'; ?>>
                    <input type="hidden" name="fmdb_categoria" id="fmdb-ind-hcat-<?php echo $eid; ?>"
                           value="<?php echo esc_attr( $ind_cat_val ); ?>"<?php echo $show_div_card ? '' : ' disabled'; ?>>
                    <input type="hidden" name="fmdb_modalidad" id="fmdb-ind-hmod-<?php echo $eid; ?>"
                           value="<?php echo esc_attr( $ind_mod_val ); ?>"<?php echo $show_div_card ? '' : ' disabled'; ?>>
                </div>
                <?php endif; ?>

                <div class="fmdb-reg-fee-preview is-visible" style="margin-top:14px;">
                    <span class="fmdb-reg-fee-preview__label">Total</span>
                    <span class="fmdb-reg-fee-preview__amount">$<?php echo esc_html( $fee_fmt ); ?> MXN</span>
                </div>

                <button type="submit" class="fmdb-btn fmdb-btn--primary fmdb-reg-box__btn">
                    Registrarme →
                </button>
            </form>

            <script>
            (function () {
                var eid  = <?php echo $eid; ?>;
                var fee  = <?php echo (float) $fee; ?>;

                // Tab switching
                var tabs = document.querySelectorAll('#fmdb-reg-tabs-' + eid + ' .fmdb-reg-tab');
                tabs.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        tabs.forEach(function (b) { b.classList.remove('is-active'); });
                        btn.classList.add('is-active');
                        var target = btn.dataset.target;
                        ['fmdb-form-team-' + eid, 'fmdb-form-ind-' + eid].forEach(function (id) {
                            var el = document.getElementById(id);
                            if (el) el.classList.toggle('fmdb-reg-form--hidden', id !== target);
                        });
                    });
                });

                // Team fee preview
                var countInput = document.getElementById('fmdb-count-' + eid);
                var feeBox     = document.getElementById('fmdb-fee-team-' + eid);
                var feeAmt     = document.getElementById('fmdb-fee-team-amt-' + eid);
                if (countInput && feeBox && feeAmt) {
                    function updateFee() {
                        var n = parseInt(countInput.value, 10);
                        if (n >= 1 && n <= 9) {
                            feeAmt.textContent = '$' + (fee * n).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' MXN';
                            feeBox.classList.add('is-visible');
                        } else {
                            feeBox.classList.remove('is-visible');
                        }
                    }
                    countInput.addEventListener('input', updateFee);
                    updateFee();
                }

                // Individual tab: registered team select → division card
                var indSel      = document.getElementById('fmdb-ind-sel-' + eid);
                var indNameHid  = document.getElementById('fmdb-ind-name-' + eid);
                var indDivCard  = document.getElementById('fmdb-ind-div-card-' + eid);
                var indHRama    = document.getElementById('fmdb-ind-hrama-' + eid);
                var indHCat     = document.getElementById('fmdb-ind-hcat-' + eid);
                var indHMod     = document.getElementById('fmdb-ind-hmod-' + eid);
                var indRamaSpan = document.getElementById('fmdb-ind-div-rama-' + eid);
                var indCatSpan  = document.getElementById('fmdb-ind-div-cat-' + eid);
                var indModSpan  = document.getElementById('fmdb-ind-div-mod-' + eid);
                var catLabels   = <?php echo json_encode( $cat_labels ); ?>;

                function showDivCard(rama, cat, mod) {
                    if (indRamaSpan) indRamaSpan.textContent = rama;
                    if (indCatSpan)  indCatSpan.textContent  = catLabels[cat] || cat;
                    if (indModSpan)  indModSpan.textContent  = mod;
                    if (indHRama) { indHRama.value = rama; indHRama.disabled = false; }
                    if (indHCat)  { indHCat.value  = cat;  indHCat.disabled  = false; }
                    if (indHMod)  { indHMod.value  = mod;  indHMod.disabled  = false; }
                    if (indDivCard) indDivCard.classList.remove('fmdb-reg-form--hidden');
                }

                function hideDivCard() {
                    if (indDivCard) indDivCard.classList.add('fmdb-reg-form--hidden');
                    if (indHRama) { indHRama.value = ''; indHRama.disabled = true; }
                    if (indHCat)  { indHCat.value  = ''; indHCat.disabled  = true; }
                    if (indHMod)  { indHMod.value  = ''; indHMod.disabled  = true; }
                }

                if (indSel) {
                    indSel.addEventListener('change', function () {
                        var opt = indSel.options[indSel.selectedIndex];
                        if (opt.value === '') {
                            if (indNameHid) indNameHid.value = '';
                            hideDivCard();
                        } else {
                            if (indNameHid) indNameHid.value = opt.value;
                            showDivCard(opt.dataset.rama || '', opt.dataset.cat || '', opt.dataset.mod || '');
                        }
                    });
                }

                // Phone digit validation (min 8 digits, ignoring formatting chars).
                function validatePhone(input) {
                    var digits = (input.value || '').replace(/\D/g, '');
                    if (digits.length < 8) {
                        input.setCustomValidity('Ingresa al menos 8 dígitos.');
                    } else {
                        input.setCustomValidity('');
                    }
                }

                var teamForm  = document.getElementById('fmdb-form-team-' + eid);
                var indForm   = document.getElementById('fmdb-form-ind-'  + eid);

                [teamForm, indForm].forEach(function (form) {
                    if (!form) return;
                    var phoneInput = form.querySelector('input[type="tel"]');
                    if (!phoneInput) return;
                    phoneInput.addEventListener('input', function () {
                        var pos = phoneInput.selectionStart;
                        var cleaned = phoneInput.value.replace(/\D/g, '');
                        if (phoneInput.value !== cleaned) {
                            phoneInput.value = cleaned;
                            phoneInput.setSelectionRange(pos - 1, pos - 1);
                        }
                        validatePhone(phoneInput);
                    });
                    form.addEventListener('submit', function () { validatePhone(phoneInput); });
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

// Shared helper: render Rama / Categoría / Modalidad selects.
function fmdb_reg_division_selects( string $uid, array $ramas, array $cats, array $mods, array $cat_labels, string $rama_val, string $cat_val, string $mod_val, bool $disabled = false ): string {
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
    <div class="fmdb-reg-form__field">
        <label>Modalidad *</label>
        <select name="fmdb_modalidad"<?php echo $disabled ? ' disabled' : ' required'; ?>>
            <option value="">— Seleccionar —</option>
            <?php foreach ( $mods as $m ) : ?>
                <option value="<?php echo esc_attr( $m ); ?>" <?php selected( $mod_val, $m ); ?>><?php echo esc_html( $m ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
    return ob_get_clean();
}

/* ─── 4b. Public section: registered teams + rosters ──────────────────── */

function fmdb_event_registered_teams_section( int $event_id ): void {
    $open = get_post_meta( $event_id, '_fmdb_reg_open', true ) === 'on';
    if ( ! $open ) return;

    $teams = fmdb_reg_get_event_teams( $event_id );
    if ( empty( $teams ) ) return;

    $pay_status_labels = [
        'pending'    => [ 'label' => 'Pendiente de pago', 'mod' => 'pending' ],
        'on-hold'    => [ 'label' => 'Pendiente de pago', 'mod' => 'pending' ],
        'processing' => [ 'label' => 'Pago confirmado',   'mod' => 'confirmed' ],
        'completed'  => [ 'label' => 'Inscripción completa', 'mod' => 'confirmed' ],
    ];

    $confirmed   = array_values( array_filter( $teams, fn( $t ) => $t['confirmed'] ?? false ) );
    $waitlisted  = array_values( array_filter( $teams, fn( $t ) => ! ( $t['confirmed'] ?? false ) && ( $t['on_waitlist'] ?? false ) ) );
    $unconfirmed = array_values( array_filter( $teams, fn( $t ) => ! ( $t['confirmed'] ?? false ) && ! ( $t['on_waitlist'] ?? false ) ) );

    // Use event-configured options for filters (fallback to full list).
    $all_ramas = array_values( array_filter( (array) get_post_meta( $event_id, '_fmdb_reg_ramas', true ) ) );
    $all_cats  = array_values( array_filter( (array) get_post_meta( $event_id, '_fmdb_reg_categorias', true ) ) );
    $all_mods  = array_values( array_filter( (array) get_post_meta( $event_id, '_fmdb_reg_modalidades', true ) ) );
    if ( empty( $all_ramas ) ) $all_ramas = [ 'Femenil', 'Mixta', 'Varonil' ];
    if ( empty( $all_cats ) )  $all_cats  = [ 'Infantil', 'Libre' ];
    if ( empty( $all_mods ) )  $all_mods  = [ 'Foam', 'Cloth' ];

    $sid = 'fmdb-teams-' . $event_id;

    // Helper: render a single team card.
    $render_card = function ( array $team ) use ( $pay_status_labels ): void {
        $ind_count = count( $team['players'] );
        $total     = $team['bulk_count'] + $ind_count;

        if ( $team['confirmed'] ?? false ) {
            $st = [ 'label' => 'Confirmado', 'mod' => 'confirmed' ];
        } elseif ( $team['on_waitlist'] ?? false ) {
            $st = [ 'label' => 'Lista de espera', 'mod' => 'waitlist' ];
        } else {
            $st = $pay_status_labels[ $team['status'] ] ?? null;
        }
        ?>
        <div class="fmdb-reg-team-card"
             data-rama="<?php echo esc_attr( $team['rama'] ); ?>"
             data-cat="<?php echo esc_attr( $team['categoria'] ); ?>"
             data-mod="<?php echo esc_attr( $team['modalidad'] ); ?>">
            <div class="fmdb-reg-team-card__header">
                <span class="fmdb-reg-team-card__name"><?php echo esc_html( $team['name'] ); ?></span>
                <?php
                $div_str = implode( ' · ', array_filter( [ $team['rama'], $team['categoria'], $team['modalidad'] ] ) );
                if ( $div_str ) : ?>
                    <span class="fmdb-reg-team-card__div"><?php echo esc_html( $div_str ); ?></span>
                <?php endif; ?>
                <?php if ( $st ) : ?>
                    <span class="fmdb-reg-team-card__status fmdb-reg-status--<?php echo esc_attr( $st['mod'] ); ?>">
                        <?php echo esc_html( $st['label'] ); ?>
                    </span>
                <?php endif; ?>
            </div>

            <ul class="fmdb-reg-team-card__roster">
                <?php if ( $team['captain'] ) : ?>
                    <li class="fmdb-reg-team-card__player">
                        <span class="fmdb-reg-team-card__role">Cap.</span>
                        <?php echo esc_html( $team['captain'] ); ?>
                    </li>
                <?php endif; ?>
                <?php foreach ( $team['players'] as $p ) : ?>
                    <li class="fmdb-reg-team-card__player">
                        <?php echo esc_html( $p['name'] ); ?>
                    </li>
                <?php endforeach; ?>
                <?php
                $remaining = $team['bulk_count'] - 1; // all bulk slots besides captain are anonymous
                if ( $remaining > 0 ) : ?>
                    <li class="fmdb-reg-team-card__player fmdb-reg-team-card__player--pending">
                        + <?php echo $remaining; ?> jugador<?php echo $remaining > 1 ? 'es' : ''; ?> más
                    </li>
                <?php endif; ?>
            </ul>

            <?php if ( $total > 0 ) : ?>
            <div class="fmdb-reg-team-card__footer">
                <?php echo esc_html( $total . ' jugador' . ( $total !== 1 ? 'es' : '' ) . ' registrado' . ( $total !== 1 ? 's' : '' ) . ' de 9' ); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    };
    ?>
    <section class="fmdb-reg-teams" id="<?php echo esc_attr( $sid ); ?>">
        <h2 class="fmdb-reg-teams__title">Equipos inscritos</h2>

        <div class="fmdb-reg-teams__filters">
            <div class="fmdb-reg-teams__filter-group fmdb-reg-teams__filter-group--mod">
                <span class="fmdb-reg-teams__filter-label">Modalidad</span>
                <button class="fmdb-reg-filter fmdb-reg-filter--mod is-active" data-filter="mod" data-value="">Todas</button>
                <?php foreach ( $all_mods as $m ) : ?>
                    <button class="fmdb-reg-filter fmdb-reg-filter--mod" data-filter="mod" data-value="<?php echo esc_attr( $m ); ?>"><?php echo esc_html( $m ); ?></button>
                <?php endforeach; ?>
            </div>
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
            // Group confirmed by modalidad → rama → categoria.
            $confirmed_groups = [];
            foreach ( $confirmed as $team ) {
                $gkey = ( $team['modalidad'] ?? '' ) . '|' . ( $team['rama'] ?? '' ) . '|' . ( $team['categoria'] ?? '' );
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
                [ $g_mod, $g_rama, $g_cat ] = explode( '|', $gkey );
                $g_label = implode( ' · ', array_filter( [ $g_mod, $g_rama, $g_cat ] ) );
                $g_cap   = $g_cat ? fmdb_reg_slot_cap( $event_id, $g_cat ) : 0;
                $g_count = count( $group_teams );
            ?>
            <div class="fmdb-reg-teams__subgroup"
                 data-mod="<?php echo esc_attr( $g_mod ); ?>"
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

        <?php if ( ! empty( $unconfirmed ) ) :
            $total_teams = count( $confirmed ) + count( $waitlisted ) + count( $unconfirmed );
        ?>
        <div class="fmdb-reg-teams__section" id="<?php echo esc_attr( $sid ); ?>-registered">
            <h3 class="fmdb-reg-teams__section-title fmdb-reg-teams__section-title--muted">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Equipos registrados
                <span class="fmdb-reg-teams__section-count"><?php echo esc_html( $total_teams ); ?></span>
            </h3>
            <div class="fmdb-reg-teams__grid">
                <?php foreach ( $unconfirmed as $team ) { $render_card( $team ); } ?>
            </div>
        </div>
        <?php endif; ?>

        <script>
        (function () {
            var sid    = <?php echo json_encode( $sid ); ?>;
            var root   = document.getElementById(sid);
            if (!root) return;

            var active = { rama: '', cat: '', mod: '' };

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
                    var modMatch  = !active.mod  || card.dataset.mod  === active.mod;
                    card.classList.toggle('fmdb-reg-form--hidden', !(ramaMatch && catMatch && modMatch));
                });

                // Hide confirmed sub-groups when all their cards are filtered out.
                root.querySelectorAll('.fmdb-reg-teams__subgroup').forEach(function (grp) {
                    var visible = grp.querySelectorAll('.fmdb-reg-team-card:not(.fmdb-reg-form--hidden)').length;
                    grp.classList.toggle('fmdb-reg-form--hidden', visible === 0);
                });

                // Hide section headings when all their cards are filtered out.
                ['confirmed', 'waitlist', 'registered'].forEach(function (key) {
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
    $event_id = (int) get_post_meta( $product_id, '_fmdb_reg_event_id', true );
    if ( ! $event_id ) return $cart_item_data;

    $fee  = (float) get_post_meta( $event_id, '_fmdb_reg_fee', true );
    $type = in_array( $_POST['fmdb_reg_type'] ?? '', [ 'team', 'individual' ], true )
            ? $_POST['fmdb_reg_type'] : 'team';

    $cart_item_data['fmdb_event_id']   = $event_id;
    $cart_item_data['fmdb_unit_fee']   = $fee;
    $cart_item_data['fmdb_reg_type']   = $type;
    $cart_item_data['fmdb_rama']       = sanitize_text_field( wp_unslash( $_POST['fmdb_rama'] ?? '' ) );
    $cart_item_data['fmdb_categoria']  = sanitize_text_field( wp_unslash( $_POST['fmdb_categoria'] ?? '' ) );
    $cart_item_data['fmdb_modalidad']  = sanitize_text_field( wp_unslash( $_POST['fmdb_modalidad'] ?? '' ) );

    if ( $type === 'individual' ) {
        $cart_item_data['fmdb_team_name']    = sanitize_text_field( wp_unslash( $_POST['fmdb_ind_team_name'] ?? '' ) );
        $cart_item_data['fmdb_player_name']  = sanitize_text_field( wp_unslash( $_POST['fmdb_player_name'] ?? '' ) );
        $cart_item_data['fmdb_player_phone'] = sanitize_text_field( wp_unslash( $_POST['fmdb_player_phone'] ?? '' ) );
        $cart_item_data['fmdb_player_count'] = 1;
    } else {
        $cart_item_data['fmdb_team_post_id']  = (int) ( $_POST['fmdb_team_post_id'] ?? 0 );
        $cart_item_data['fmdb_team_name']     = sanitize_text_field( wp_unslash( $_POST['fmdb_team_name'] ?? '' ) );
        $cart_item_data['fmdb_captain_name']  = sanitize_text_field( wp_unslash( $_POST['fmdb_captain_name'] ?? '' ) );
        $cart_item_data['fmdb_captain_phone'] = sanitize_text_field( wp_unslash( $_POST['fmdb_captain_phone'] ?? '' ) );
        $cart_item_data['fmdb_player_count']  = absint( $_POST['fmdb_player_count'] ?? 0 );

        // Determine waitlist status: slot at capacity → waitlist.
        $slot_cap = fmdb_reg_slot_cap( $event_id, $cart_item_data['fmdb_categoria'] );
        if ( $slot_cap > 0 ) {
            $slot_count = fmdb_reg_slot_team_count(
                $event_id,
                $cart_item_data['fmdb_rama'],
                $cart_item_data['fmdb_modalidad'],
                $cart_item_data['fmdb_categoria']
            );
            $cart_item_data['fmdb_on_waitlist'] = $slot_count >= $slot_cap ? '1' : '0';
        } else {
            $cart_item_data['fmdb_on_waitlist'] = '0';
        }
    }

    return $cart_item_data;
}, 10, 2 );

/* ─── 6. Per-player price override ────────────────────────────────────── */

add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    foreach ( $cart->get_cart() as $item ) {
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
        $cart_item['fmdb_modalidad'] ?? '',
    ] ) );

    $data[] = [ 'name' => 'Evento',   'value' => get_the_title( $cart_item['fmdb_event_id'] ) ];
    $data[] = [ 'name' => 'Equipo',   'value' => $cart_item['fmdb_team_name'] ?? '' ];
    $data[] = [ 'name' => 'División', 'value' => $div_str ];

    if ( $type === 'individual' ) {
        $data[] = [ 'name' => 'Jugador',   'value' => $cart_item['fmdb_player_name'] ?? '' ];
        if ( ! empty( $cart_item['fmdb_player_phone'] ) ) {
            $data[] = [ 'name' => 'Teléfono', 'value' => $cart_item['fmdb_player_phone'] ];
        }
    } else {
        $data[] = [ 'name' => 'Capitán',   'value' => $cart_item['fmdb_captain_name'] ?? '' ];
        $data[] = [ 'name' => 'Teléfono',  'value' => $cart_item['fmdb_captain_phone'] ?? '' ];
        $data[] = [ 'name' => 'Jugadores', 'value' => $cart_item['fmdb_player_count'] ?? 0 ];
    }

    return $data;
}, 10, 2 );

/* ─── 8. Validate registration on add-to-cart ──────────────────────────── */

add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id ) {
    $event_id = (int) get_post_meta( $product_id, '_fmdb_reg_event_id', true );
    if ( ! $event_id ) return $passed;

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
    if ( empty( $_POST['fmdb_modalidad'] ) ) {
        wc_add_notice( 'Selecciona una modalidad.', 'error' );
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
        }
        if ( empty( $_POST['fmdb_player_phone'] ) ) {
            wc_add_notice( 'Ingresa tu teléfono.', 'error' );
            $passed = false;
        }
        // Enforce 9-player roster cap per team.
        if ( $passed && ! empty( $_POST['fmdb_ind_team_name'] ) ) {
            $teams = fmdb_reg_get_event_teams( $event_id );
            $target = mb_strtolower( trim( sanitize_text_field( $_POST['fmdb_ind_team_name'] ) ) );
            foreach ( $teams as $t ) {
                if ( mb_strtolower( trim( $t['name'] ) ) === $target ) {
                    $total = ( $t['bulk_count'] ?? 0 ) + count( $t['players'] ?? [] );
                    if ( $total >= 9 ) {
                        wc_add_notice( 'Este equipo ya alcanzó el límite de 9 jugadores.', 'error' );
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
            $submitted_mod  = sanitize_text_field( $_POST['fmdb_modalidad'] ?? '' );
            foreach ( fmdb_reg_get_event_teams( $event_id ) as $rt ) {
                if ( mb_strtolower( trim( $rt['name'] ) ) === $submitted_name
                  && $rt['rama']      === $submitted_rama
                  && $rt['categoria'] === $submitted_cat
                  && $rt['modalidad'] === $submitted_mod
                  && $rt['order_id'] ) {
                    wc_add_notice( 'Ya existe un equipo con ese nombre en esta división.', 'error' );
                    $passed = false;
                    break;
                }
            }
        }
        if ( empty( $_POST['fmdb_captain_name'] ) ) {
            wc_add_notice( 'Ingresa el nombre del capitán.', 'error' );
            $passed = false;
        }
        if ( empty( $_POST['fmdb_captain_phone'] ) ) {
            wc_add_notice( 'Ingresa el teléfono del capitán.', 'error' );
            $passed = false;
        }
        $count = (int) ( $_POST['fmdb_player_count'] ?? 0 );
        if ( $count < 1 || $count > 9 ) {
            wc_add_notice( 'El número de jugadores debe ser entre 1 y 9.', 'error' );
            $passed = false;
        }
    }

    return $passed;
}, 10, 2 );

/* ─── 9. Redirect to checkout after adding registration product ────────── */

add_filter( 'woocommerce_add_to_cart_redirect', function ( $url ) {
    if ( wc_notice_count( 'error' ) > 0 ) return $url;
    if ( ! isset( $_REQUEST['add-to-cart'] ) ) return $url;
    $pid = absint( $_REQUEST['add-to-cart'] );
    if ( get_post_meta( $pid, '_fmdb_reg_event_id', true ) ) {
        return wc_get_checkout_url();
    }
    return $url;
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

    $item->add_meta_data( 'Evento',    get_the_title( $values['fmdb_event_id'] ) );
    $item->add_meta_data( 'Equipo',    $values['fmdb_team_name'] ?? '' );
    $item->add_meta_data( 'Rama',      $values['fmdb_rama'] ?? '' );
    $item->add_meta_data( 'Categoría', $values['fmdb_categoria'] ?? '' );
    $item->add_meta_data( 'Modalidad', $values['fmdb_modalidad'] ?? '' );
    $item->add_meta_data( 'Tipo',      $type === 'individual' ? 'Individual' : 'Equipo' );

    if ( $type === 'individual' ) {
        $item->add_meta_data( 'Jugador',   $values['fmdb_player_name'] ?? '' );
        if ( ! empty( $values['fmdb_player_phone'] ) ) {
            $item->add_meta_data( 'Teléfono', $values['fmdb_player_phone'] );
        }
    } else {
        $item->add_meta_data( 'Capitán',   $values['fmdb_captain_name'] ?? '' );
        $item->add_meta_data( 'Teléfono',  $values['fmdb_captain_phone'] ?? '' );
        $item->add_meta_data( 'Jugadores', $values['fmdb_player_count'] ?? 0 );
        if ( ! empty( $values['fmdb_team_post_id'] ) ) {
            $item->add_meta_data( '_fmdb_team_post_id', $values['fmdb_team_post_id'], true );
        }
    }

    $order->update_meta_data( '_fmdb_reg_event_id', $values['fmdb_event_id'] );
    $order->update_meta_data( '_fmdb_reg_type', $type );

    if ( $type === 'team' && isset( $values['fmdb_on_waitlist'] ) ) {
        $order->update_meta_data( '_fmdb_on_waitlist', $values['fmdb_on_waitlist'] );
    }
}, 10, 4 );

/* ─── 11. Bank transfer instructions on order confirmation ─────────────── */

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
