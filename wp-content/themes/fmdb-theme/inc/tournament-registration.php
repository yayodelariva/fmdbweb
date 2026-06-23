<?php
/**
 * Tournament registration — links a TEC "torneo" event to a WooCommerce
 * virtual product so teams can register and pay the fee at checkout.
 *
 * Admin:    CMB2 meta box on tribe_events for fee, deadline, max teams.
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
        'name'       => __( 'Cuota (MXN)', 'fmdb' ),
        'id'         => '_fmdb_reg_fee',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0', 'step' => '0.01' ],
    ] );
    $cmb->add_field( [
        'name'        => __( 'Fecha límite', 'fmdb' ),
        'id'          => '_fmdb_reg_deadline',
        'type'        => 'text_date',
        'date_format' => 'Y-m-d',
    ] );
    $cmb->add_field( [
        'name'       => __( 'Máximo de equipos', 'fmdb' ),
        'desc'       => __( '0 = sin límite', 'fmdb' ),
        'id'         => '_fmdb_reg_max_teams',
        'type'       => 'text_small',
        'attributes' => [ 'type' => 'number', 'min' => '0' ],
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

    if ( $open !== 'on' || $fee <= 0 ) {
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

    if ( $max > 0 ) {
        $was_managing = $product->get_manage_stock();
        $product->set_manage_stock( true );
        $product->set_backorders( 'no' );

        if ( ! $was_managing ) {
            $product->set_stock_quantity( $max );
        } else {
            $stored_max = (int) $product->get_meta( '_fmdb_reg_max_teams' );
            if ( $stored_max && $stored_max !== $max ) {
                $current = (int) $product->get_stock_quantity();
                $product->set_stock_quantity( max( 0, $current + ( $max - $stored_max ) ) );
            }
        }
        $product->update_meta_data( '_fmdb_reg_max_teams', $max );
    } else {
        $product->set_manage_stock( false );
        $product->delete_meta_data( '_fmdb_reg_max_teams' );
    }

    $product->save();
    $new_id = $product->get_id();

    update_post_meta( $post_id, '_fmdb_reg_product_id', $new_id );
    update_post_meta( $new_id, '_fmdb_reg_event_id', $post_id );
}, 30 );

/* ─── 3. Frontend: registration card ──────────────────────────────────── */

function fmdb_event_registration_box( int $event_id ): void {
    if ( ! function_exists( 'wc_get_product' ) ) return;

    $open     = get_post_meta( $event_id, '_fmdb_reg_open', true ) === 'on';
    $fee      = (float) get_post_meta( $event_id, '_fmdb_reg_fee', true );
    $prod_id  = (int) get_post_meta( $event_id, '_fmdb_reg_product_id', true );
    $deadline = get_post_meta( $event_id, '_fmdb_reg_deadline', true );
    $max      = (int) get_post_meta( $event_id, '_fmdb_reg_max_teams', true );

    if ( ! $open || $fee <= 0 || ! $prod_id ) return;

    $product = wc_get_product( $prod_id );
    if ( ! $product || $product->get_status() !== 'publish' ) return;

    $past_deadline = $deadline && strtotime( $deadline . ' 23:59:59' ) < time();
    $sold_out      = $max > 0 && ! $product->is_in_stock();
    $registered    = $max > 0 ? $max - (int) $product->get_stock_quantity() : 0;
    $closed        = $past_deadline || $sold_out;

    ?>
    <div class="fmdb-evento-single__meta-card fmdb-reg-box">
        <h3 class="fmdb-evento-single__meta-title">Inscripción</h3>

        <?php if ( function_exists( 'wc_print_notices' ) ) { wc_print_notices(); } ?>

        <div class="fmdb-evento-single__meta-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <div>
                <strong>Cuota por equipo</strong>
                <span>$<?php echo esc_html( number_format( $fee, 2 ) ); ?> MXN</span>
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

        <?php if ( $max > 0 ) : ?>
        <div class="fmdb-evento-single__meta-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <div>
                <strong>Equipos</strong>
                <span><?php echo esc_html( "$registered / $max inscritos" ); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $closed ) : ?>
            <span class="fmdb-reg-box__closed">
                <?php echo $sold_out ? 'Cupo lleno' : 'Inscripción cerrada'; ?>
            </span>
        <?php elseif ( is_user_logged_in() ) : ?>
            <form method="post" class="fmdb-reg-form">
                <input type="hidden" name="add-to-cart" value="<?php echo (int) $prod_id; ?>">

                <div class="fmdb-reg-form__field">
                    <label for="fmdb_team_name">Nombre del equipo *</label>
                    <input type="text" id="fmdb_team_name" name="fmdb_team_name" required
                           value="<?php echo esc_attr( $_POST['fmdb_team_name'] ?? '' ); ?>"
                           placeholder="Ej. Tiburones de Naucalpan">
                </div>

                <div class="fmdb-reg-form__row">
                    <div class="fmdb-reg-form__field">
                        <label for="fmdb_captain_name">Capitán *</label>
                        <input type="text" id="fmdb_captain_name" name="fmdb_captain_name" required
                               value="<?php echo esc_attr( $_POST['fmdb_captain_name'] ?? '' ); ?>">
                    </div>
                    <div class="fmdb-reg-form__field">
                        <label for="fmdb_captain_phone">Teléfono *</label>
                        <input type="tel" id="fmdb_captain_phone" name="fmdb_captain_phone" required
                               value="<?php echo esc_attr( $_POST['fmdb_captain_phone'] ?? '' ); ?>">
                    </div>
                </div>

                <div class="fmdb-reg-form__field">
                    <label for="fmdb_player_count">Número de jugadores *</label>
                    <input type="number" id="fmdb_player_count" name="fmdb_player_count" required
                           min="1" max="30"
                           value="<?php echo esc_attr( $_POST['fmdb_player_count'] ?? '' ); ?>">
                </div>

                <button type="submit" class="fmdb-btn fmdb-btn--primary fmdb-reg-box__btn">
                    Inscribir equipo
                </button>
            </form>
        <?php else : ?>
            <a href="<?php echo esc_url( wp_login_url( get_permalink( $event_id ) ) ); ?>"
               class="fmdb-btn fmdb-btn--primary fmdb-reg-box__btn">
                Inicia sesión para inscribirte
            </a>
        <?php endif; ?>
    </div>
    <?php
}

/* ─── 4. Capture team data in cart item ────────────────────────────────── */

add_filter( 'woocommerce_add_cart_item_data', function ( $cart_item_data, $product_id ) {
    $event_id = (int) get_post_meta( $product_id, '_fmdb_reg_event_id', true );
    if ( ! $event_id ) return $cart_item_data;

    $cart_item_data['fmdb_event_id']      = $event_id;
    $cart_item_data['fmdb_team_name']     = sanitize_text_field( wp_unslash( $_POST['fmdb_team_name'] ?? '' ) );
    $cart_item_data['fmdb_captain_name']  = sanitize_text_field( wp_unslash( $_POST['fmdb_captain_name'] ?? '' ) );
    $cart_item_data['fmdb_captain_phone'] = sanitize_text_field( wp_unslash( $_POST['fmdb_captain_phone'] ?? '' ) );
    $cart_item_data['fmdb_player_count']  = absint( $_POST['fmdb_player_count'] ?? 0 );

    return $cart_item_data;
}, 10, 2 );

/* ─── 5. Show team data in cart / checkout order summary ───────────────── */

add_filter( 'woocommerce_get_item_data', function ( $data, $cart_item ) {
    if ( empty( $cart_item['fmdb_team_name'] ) ) return $data;

    $data[] = [ 'name' => 'Evento',    'value' => get_the_title( $cart_item['fmdb_event_id'] ) ];
    $data[] = [ 'name' => 'Equipo',    'value' => $cart_item['fmdb_team_name'] ];
    $data[] = [ 'name' => 'Capitán',   'value' => $cart_item['fmdb_captain_name'] ];
    $data[] = [ 'name' => 'Teléfono',  'value' => $cart_item['fmdb_captain_phone'] ];
    $data[] = [ 'name' => 'Jugadores', 'value' => $cart_item['fmdb_player_count'] ];

    return $data;
}, 10, 2 );

/* ─── 6. Validate registration on add-to-cart ──────────────────────────── */

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

    if ( empty( $_POST['fmdb_team_name'] ) ) {
        wc_add_notice( 'Ingresa el nombre del equipo.', 'error' );
        $passed = false;
    }
    if ( empty( $_POST['fmdb_captain_name'] ) ) {
        wc_add_notice( 'Ingresa el nombre del capitán.', 'error' );
        $passed = false;
    }
    if ( empty( $_POST['fmdb_captain_phone'] ) ) {
        wc_add_notice( 'Ingresa el teléfono del capitán.', 'error' );
        $passed = false;
    }
    if ( empty( $_POST['fmdb_player_count'] ) || (int) $_POST['fmdb_player_count'] < 1 ) {
        wc_add_notice( 'Ingresa el número de jugadores.', 'error' );
        $passed = false;
    }

    return $passed;
}, 10, 2 );

/* ─── 7. Redirect to checkout after adding registration product ────────── */

add_filter( 'woocommerce_add_to_cart_redirect', function ( $url ) {
    if ( ! isset( $_REQUEST['add-to-cart'] ) ) return $url;
    $pid = absint( $_REQUEST['add-to-cart'] );
    if ( get_post_meta( $pid, '_fmdb_reg_event_id', true ) ) {
        return wc_get_checkout_url();
    }
    return $url;
} );

/* ─── 8. Save team data as order line-item meta ────────────────────────── */

add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $cart_item_key, $values, $order ) {
    if ( empty( $values['fmdb_team_name'] ) ) return;

    $item->add_meta_data( 'Evento',    get_the_title( $values['fmdb_event_id'] ) );
    $item->add_meta_data( 'Equipo',    $values['fmdb_team_name'] );
    $item->add_meta_data( 'Capitán',   $values['fmdb_captain_name'] );
    $item->add_meta_data( 'Teléfono',  $values['fmdb_captain_phone'] );
    $item->add_meta_data( 'Jugadores', $values['fmdb_player_count'] );

    $order->update_meta_data( '_fmdb_reg_event_id', $values['fmdb_event_id'] );
}, 10, 4 );
