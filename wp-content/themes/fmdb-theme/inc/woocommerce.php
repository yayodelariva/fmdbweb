<?php
/**
 * WooCommerce theme integration:
 *  - Theme support and gallery features
 *  - Remove reviews tab + star rating
 *  - Spanish strings for cart page and empty-cart messages
 *  - Hide Kadence hero/title on cart and checkout
 *  - Cart count fragment for nav badge
 *  - Guest gating: products visible, add-to-cart requires login
 */

// WooCommerce compatibility
add_action( 'after_setup_theme', function () {
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
} );

// Remove reviews tab and star rating from product pages
add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
    unset( $tabs['reviews'] );
    return $tabs;
}, 98 );
add_action( 'init', function () {
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
    remove_action( 'woocommerce_cart_is_empty', 'woocommerce_return_to_shop', 20 );
} );

// Remove popularity + average-rating options and rename default/date labels
add_filter( 'woocommerce_catalog_orderby', function ( $options ) {
    unset( $options['popularity'], $options['rating'] );
    if ( isset( $options['menu_order'] ) ) $options['menu_order'] = 'Predeterminado';
    if ( isset( $options['date'] ) )       $options['date']       = 'Agregados recientemente';
    if ( isset( $options['price'] ) )      $options['price']      = 'Precio: menor a mayor';
    if ( isset( $options['price-desc'] ) ) $options['price-desc'] = 'Precio: mayor a menor';
    return $options;
} );
add_filter( 'woocommerce_default_catalog_orderby_options', function ( $options ) {
    unset( $options['popularity'], $options['rating'] );
    if ( isset( $options['menu_order'] ) ) $options['menu_order'] = 'Predeterminado';
    if ( isset( $options['date'] ) )       $options['date']       = 'Agregados recientemente';
    if ( isset( $options['price'] ) )      $options['price']      = 'Precio: menor a mayor';
    if ( isset( $options['price-desc'] ) ) $options['price-desc'] = 'Precio: mayor a menor';
    return $options;
} );

// Cart page title in Spanish — covers both WooCommerce template (woocommerce_page_title)
// and Kadence hero (the_title). Same string + condition in both filters.
$fmdb_cart_title_es = 'Tu carrito de compras';

add_filter( 'woocommerce_page_title', function ( $title ) use ( $fmdb_cart_title_es ) {
    return ( function_exists( 'is_cart' ) && is_cart() ) ? $fmdb_cart_title_es : $title;
} );

add_filter( 'the_title', function ( $title, $post_id = null ) use ( $fmdb_cart_title_es ) {
    if ( function_exists( 'wc_get_page_id' ) && function_exists( 'is_cart' )
         && is_cart() && (int) $post_id === wc_get_page_id( 'cart' ) ) {
        return $fmdb_cart_title_es;
    }
    return $title;
}, 10, 2 );

// Hide Kadence hero/in-content title on cart and checkout
add_filter( 'kadence_post_layout', function ( $layout ) {
    if ( ( function_exists( 'is_checkout' ) && is_checkout() ) ||
         ( function_exists( 'is_cart' ) && is_cart() ) ) {
        $layout['title'] = 'hide';
    }
    return $layout;
} );

// Replace static English strings baked into the Cart block's saved post content
add_filter( 'render_block', function ( $block_content ) {
    return str_replace(
        [ 'Your cart is currently empty!', 'New in store', 'Add to cart', 'Add to Cart' ],
        [ 'Tu carrito de compras está vacío', 'Podría interesarte:', 'Agregar al carrito', 'Agregar al carrito' ],
        $block_content
    );
} );

// Cart/Checkout block overrides: WC blocks load some strings via wp.i18n (caught
// by the JS filter below) and others via PHP __() rendered into wcSettings JSON
// (caught by the PHP gettext_woocommerce filter further down). We override both.
// Add new strings here; key = exact English source string.
function fmdb_cart_checkout_overrides() {
    return [
        // Cart page
        'Shipping will be calculated at checkout' => 'El total de envío será calculado al final',
        'Ship'                                    => 'Envío',
        'Calculated at checkout'                  => 'Ingresar dirección para calcular estimado',
        'Enter address to calculate'              => 'Ingresar dirección para calcular estimado',
        'Product'                                 => 'Producto',
        'Cart totals'                             => 'Totales',
        'Free'                                    => 'Gratis',
        'Proceed to Checkout'                     => 'Continuar al pago',
        // Checkout page
        'Contact information'                                                                  => 'Información de contacto',
        'Email address'                                                                        => 'Correo electrónico',
        'I would like to receive exclusive emails with discounts and product information'      => 'Quiero recibir correos exclusivos con descuentos e información de productos',
        'Delivery'                                                                             => 'Entrega',
        'Pickup locations'                                                                     => 'Sucursales',
        'Billing address'                                                                      => 'Dirección de facturación',
        'Edit'                                                                                 => 'Editar',
        'Payment options'                                                                      => 'Opciones de pago',
        'There are no payment methods available. Please contact us for help placing your order.' => 'No hay métodos de pago disponibles. Contáctanos para ayudarte con tu pedido.',
        'Add a note to your order'                                                             => 'Agregar una nota a tu pedido',
        'Place Order'                                                                          => 'Realizar pedido',
        'Order summary'                                                                        => 'Resumen del pedido',
        'Add coupons'                                                                          => 'Agregar cupones',
        'Subtotal'                                                                             => 'Subtotal',
        'Total'                                                                                => 'Total',
        'By proceeding with your purchase you agree to our <a>Terms and Conditions</a> and <a>Privacy Policy</a>' => 'Al realizar tu compra aceptas nuestros <a>Términos y Condiciones</a> y la <a>Política de Privacidad</a>',
        // Address form fields (billing + shipping)
        'First name'                                       => 'Nombre',
        'Last name'                                        => 'Apellidos',
        'Company'                                          => 'Empresa',
        'Country/Region'                                   => 'País/Región',
        'Country / Region'                                 => 'País/Región',
        'Address'                                          => 'Dirección',
        'Street address'                                   => 'Dirección',
        'Apartment, suite, etc. (optional)'                => 'Departamento, suite, etc. (opcional)',
        'Add apartment, suite, unit, etc.'                 => 'Agregar departamento, suite, unidad, etc.',
        'Apartment, suite, unit, etc.'                     => 'Departamento, suite, unidad, etc.',
        // WC composes "Add" + lowercase label via sprintf, so override both forms.
        'apartment, suite, unit, etc.'                     => 'departamento, suite, unidad, etc.',
        'Add'                                              => 'Agregar',
        '+ Add'                                            => '+ Agregar',
        'Add %s'                                           => 'Agregar %s',
        '+ Add %s'                                         => '+ Agregar %s',
        'Add to cart'                                      => 'Agregar al carrito',
        'Add to Cart'                                      => 'Agregar al carrito',
        'City'                                             => 'Ciudad',
        'Town / City'                                      => 'Ciudad',
        'Town/City'                                        => 'Ciudad',
        'State'                                            => 'Estado',
        'State / County'                                   => 'Estado',
        'State/County'                                     => 'Estado',
        'ZIP Code'                                         => 'Código postal',
        'Postal code'                                      => 'Código postal',
        'Postcode / ZIP'                                   => 'Código postal',
        'Postcode/ZIP'                                     => 'Código postal',
        'Postcode'                                         => 'Código postal',
        'Phone (optional)'                                 => 'Teléfono (opcional)',
        'Phone'                                            => 'Teléfono',
        'Use same address for billing'                     => 'Usar la misma dirección para facturación',
        'Shipping address'                                 => 'Dirección de envío',
        'Save address to my account'                       => 'Guardar dirección en mi cuenta',
        'Add a coupon'                                     => 'Agregar un cupón',
        'Apply'                                            => 'Aplicar',
        'Coupon code'                                      => 'Código de cupón',
        'Order notes'                                      => 'Notas del pedido',
        'Notes about your order, e.g. special notes for delivery.' => 'Notas sobre tu pedido, ej. instrucciones especiales para la entrega.',
        // Single product page
        'Related products'                                 => 'Productos relacionados',
        // Add-to-cart success notice + View Cart button
        'View Cart'                                        => 'Ver carrito',
        'View cart'                                        => 'Ver carrito',
        '%s has been added to your cart.'                  => '%s se ha agregado a tu carrito.',
        '%s have been added to your cart.'                 => '%s se han agregado a tu carrito.',
        // Shop archive result count
        'Showing the single result'                        => 'Mostrando el único resultado',
        'Showing all %d results'                           => 'Mostrando los %d resultados',
        'Showing %1$d&#8211;%2$d of %3$d results'          => 'Mostrando %1$d&#8211;%2$d de %3$d resultados',
        'Showing %1$d–%2$d of %3$d results'                => 'Mostrando %1$d–%2$d de %3$d resultados',
        // ── Email headings and subjects ──────────────────────────────────────
        'Thank you for your order!'                                      => '¡Gracias por tu pedido!',
        'Thanks for shopping with us'                                    => 'Gracias por comprar con nosotros',
        'Your order is being processed'                                  => 'Tu pedido está siendo procesado',
        'Your {site_title} order has been received!'                     => 'Tu pedido en {site_title} ha sido recibido',
        'Your {site_title} order is now complete'                        => 'Tu pedido en {site_title} está completo',
        'Your {site_title} order receipt'                                => 'Tu recibo de compra en {site_title}',
        'Your {site_title} order has been cancelled'                     => 'Tu pedido en {site_title} fue cancelado',
        'Your {site_title} order has been refunded'                      => 'Tu pedido en {site_title} fue reembolsado',
        '[{site_title}]: New order #{order_number}'                      => '[{site_title}]: Nuevo pedido #{order_number}',
        '[{site_title}]: Order #{order_number} has been received'        => '[{site_title}]: Pedido #{order_number} recibido',
        'New order'                                                      => 'Nuevo pedido',
        'Your account on {site_title}'                                   => 'Tu cuenta en {site_title}',
        'Welcome to {site_title}'                                        => 'Bienvenido/a a {site_title}',
        'Password reset request for {site_title}'                        => 'Restablecimiento de contraseña en {site_title}',
        'Password reset request'                                         => 'Restablecimiento de contraseña',
        // ── email_improvements intro line (WC 9+) ───────────────────────────
        'Hi {customer_first_name},'                                      => 'Hola {customer_first_name},',
        "Just to let you know — we've received your order #{order_number}, and it is now being processed:" => '¡Hemos recibido tu pedido #{order_number} y está siendo procesado!',
        // ── Order details table ──────────────────────────────────────────────
        'Order details'      => 'Detalles del pedido',
        'Order #%s'          => 'Pedido #%s',
        'Order date:'        => 'Fecha del pedido:',
        'Telephone:'         => 'Teléfono:',
        'Payment method:'    => 'Método de pago:',
        'Quantity'           => 'Cantidad',
        'Note:'              => 'Nota:',
        'Tax'                => 'Impuesto',
        'Discount:'          => 'Descuento:',
        'Shipping:'          => 'Envío:',
        'Customer details'   => 'Datos del cliente',
        // ── New account email ────────────────────────────────────────────────
        'Hi, a new customer has registered on your website.'             => 'Un nuevo cliente se ha registrado en tu sitio web.',
        'Email address:'                                                  => 'Correo electrónico:',
        'Username:'                                                       => 'Nombre de usuario:',
        'Password:'                                                       => 'Contraseña:',
        'Click here to set your password'                                 => 'Haz clic aquí para establecer tu contraseña',
        // ── Password reset ───────────────────────────────────────────────────
        'Someone has requested a new password for the following account on {site_title}:' => 'Se ha solicitado una nueva contraseña para la siguiente cuenta en {site_title}:',
        "If you didn't make this request, just ignore this email. If you'd like to proceed:" => 'Si no solicitaste esto, ignora este correo. Si deseas continuar:',
        'Click here to reset your password'                               => 'Haz clic aquí para restablecer tu contraseña',
        // ── Shipping methods ─────────────────────────────────────────────────
        'Free shipping'  => 'Envío gratis',
        'Free Shipping'  => 'Envío gratis',
        'Local pickup'   => 'Recoger en tienda',
        'Local Pickup'   => 'Recoger en tienda',
        // ── Order status labels ──────────────────────────────────────────────
        'Processing'  => 'Procesando',
        'Completed'   => 'Completado',
        'On hold'     => 'En espera',
        'Cancelled'   => 'Cancelado',
        'Refunded'    => 'Reembolsado',
        'Failed'      => 'Fallido',
        'Pending'     => 'Pendiente',
    ];
}

// PHP-side: override WC strings server-side. Catches labels rendered into
// wcSettings JSON (address form fields like First name, Country/Region, etc.)
// that bypass wp.i18n on the JS side.
add_filter( 'gettext_woocommerce', function ( $translation, $text ) {
    $overrides = fmdb_cart_checkout_overrides();
    return $overrides[ $text ] ?? $translation;
}, 20, 2 );
add_filter( 'gettext_with_context_woocommerce', function ( $translation, $text ) {
    $overrides = fmdb_cart_checkout_overrides();
    return $overrides[ $text ] ?? $translation;
}, 20, 2 );
add_filter( 'ngettext_woocommerce', function ( $translation, $single, $plural, $number ) {
    $overrides = fmdb_cart_checkout_overrides();
    $key = ( $number == 1 ) ? $single : $plural;
    return $overrides[ $key ] ?? $translation;
}, 20, 4 );
add_filter( 'ngettext_with_context_woocommerce', function ( $translation, $single, $plural, $number ) {
    $overrides = fmdb_cart_checkout_overrides();
    $key = ( $number == 1 ) ? $single : $plural;
    return $overrides[ $key ] ?? $translation;
}, 20, 4 );

// JS-side: catch strings rendered through wp.i18n.__() in block JS.
// Scoped to cart/checkout pages — the filter is domain-agnostic and could
// otherwise affect unrelated strings (e.g. "City") elsewhere on the site.
add_action( 'wp_enqueue_scripts', function () {
    if ( ! wp_script_is( 'wp-i18n', 'registered' ) && ! wp_script_is( 'wp-i18n', 'enqueued' ) ) {
        return;
    }
    if ( ! function_exists( 'is_cart' ) || ! function_exists( 'is_checkout' ) ) {
        return;
    }
    if ( ! is_cart() && ! is_checkout() ) {
        return;
    }
    $overrides = fmdb_cart_checkout_overrides();
    wp_enqueue_script( 'wp-i18n' );
    wp_enqueue_script( 'wp-hooks' );
    wp_add_inline_script(
        'wp-i18n',
        '( function () {'
        . "if ( window.wp && wp.hooks && wp.i18n ) {"
        . 'var fmdbOverrides = ' . wp_json_encode( $overrides ) . ';'
        . 'var fmdbApply = function ( translation, text ) {'
        . 'return Object.prototype.hasOwnProperty.call(fmdbOverrides, text) ? fmdbOverrides[text] : translation;'
        . '};'
        . "wp.hooks.addFilter( 'i18n.gettext', 'fmdb/checkout-i18n', fmdbApply );"
        . "wp.hooks.addFilter( 'i18n.gettext_with_context', 'fmdb/checkout-i18n-ctx', fmdbApply );"
        . '}'
        . '} )();'
    );
}, 20 );

// Shop + checkout: under construction (checkout exempt when cart has tournament registrations)
add_action( 'template_redirect', function () {
    if ( function_exists( 'is_shop' ) && is_shop() ) {
        $is_wip = true;
    } elseif ( function_exists( 'is_checkout' ) && is_checkout() ) {
        $has_reg = false;
        if ( function_exists( 'WC' ) && WC()->cart ) {
            foreach ( WC()->cart->get_cart() as $_item ) {
                if ( ! empty( $_item['fmdb_event_id'] ) || ! empty( $_item['fmdb_hospedaje_type'] ) ) { $has_reg = true; break; }
            }
        }
        $is_wip = ! $has_reg;
    } else {
        $is_wip = false;
    }
    if ( ! $is_wip ) return;
    get_header();
    echo '<main class="fmdb-shop-wip">';
    echo '<div class="fmdb-shop-wip__wrap">';
    echo '<h1>Tienda en construcción</h1>';
    echo '<p>Estamos preparando la tienda. ¡Pronto estará disponible!</p>';
    echo '<a href="' . esc_url( home_url( '/' ) ) . '" class="fmdb-btn fmdb-btn--primary">Volver al inicio</a>';
    echo '</div>';
    echo '</main>';
    get_footer();
    exit;
} );

// Empty cart message with shop link
add_filter( 'wc_empty_cart_message', function () {
    $shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
    return 'Tu carrito está vacío. <a href="' . esc_url( $shop ) . '" class="fmdb-cart-empty__link">Visita la tienda</a>';
} );

// Fragment: keep cart counter in sync after AJAX add-to-cart
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) return $fragments;
    $count = WC()->cart->get_cart_contents_count();
    $fragments['.fmdb-nav-cart__count'] = '<span class="fmdb-nav-cart__count' . ( $count ? ' has-items' : '' ) . '">' . $count . '</span>';
    return $fragments;
} );

/**
 * Guest shop access — products visible to anyone, but add-to-cart is gated by login.
 * - Server-side: woocommerce_add_to_cart_validation blocks the request, adds a notice with a login link
 * - UI: replace "Añadir al carrito" with "Iniciar sesión" for guests on the shop archive
 * - UI: replace single-product add-to-cart form with a login CTA
 */
add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id ) {
    if ( is_user_logged_in() ) return $passed;
    $login_url = wp_login_url( get_permalink( $product_id ) ?: wc_get_page_permalink( 'shop' ) );
    if ( function_exists( 'wc_add_notice' ) ) {
        wc_add_notice(
            sprintf(
                'Debes <a href="%s"><strong>iniciar sesión</strong></a> para agregar productos al carrito.',
                esc_url( $login_url )
            ),
            'error'
        );
    }
    return false;
}, 10, 2 );

// Shop archive: replace the add-to-cart link with an "Iniciar sesión" CTA for guests
add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
    if ( is_user_logged_in() ) return $html;
    $login_url = wp_login_url( get_permalink( $product->get_id() ) );
    return sprintf(
        '<a href="%1$s" class="button fmdb-shop-login-cta" rel="nofollow" aria-label="%3$s">'
        . '<span class="fmdb-cta-text fmdb-cta-text--idle">%2$s</span>'
        . '<span class="fmdb-cta-text fmdb-cta-text--hover">%3$s</span>'
        . '</a>',
        esc_url( $login_url ),
        esc_html__( 'Agregar al carrito', 'fmdb' ),
        esc_html__( 'Iniciar sesión para comprar', 'fmdb' )
    );
}, 10, 2 );

// Single product page: swap the add-to-cart form for a login CTA when logged out
add_action( 'woocommerce_single_product_summary', function () {
    if ( is_user_logged_in() ) return;
    global $product;
    if ( ! $product ) return;
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
    add_action( 'woocommerce_single_product_summary', function () {
        $login_url = wp_login_url( get_permalink() );
        echo '<div class="fmdb-shop-login-prompt">';
        echo '<p class="fmdb-shop-login-prompt__msg">Inicia sesión para agregar este producto al carrito.</p>';
        printf(
            '<a href="%s" class="button alt fmdb-shop-login-cta">%s</a>',
            esc_url( $login_url ),
            esc_html__( 'Iniciar sesión', 'fmdb' )
        );
        $reg = home_url( '/registro/' );
        printf(
            '<a href="%s" class="fmdb-shop-login-prompt__register">%s</a>',
            esc_url( $reg ),
            esc_html__( '¿No tienes cuenta? Regístrate', 'fmdb' )
        );
        echo '</div>';
    }, 30 );
}, 1 );

/* ─── Email headings and subjects in Spanish ──────────────────────────────── */
// These override the values stored in WC settings (which bypass gettext).

add_filter( 'woocommerce_email_heading_customer_processing_order', fn() => '¡Gracias por tu pedido!' );
add_filter( 'woocommerce_email_heading_customer_completed_order',  fn() => 'Tu pedido está completo' );
add_filter( 'woocommerce_email_heading_customer_on_hold_order',    fn() => '¡Gracias por tu pedido!' );
add_filter( 'woocommerce_email_heading_customer_failed_order',     fn() => 'Tu pedido no se pudo completar' );
add_filter( 'woocommerce_email_heading_customer_cancelled_order',  fn() => 'Tu pedido fue cancelado' );
add_filter( 'woocommerce_email_heading_customer_refunded_order',   fn() => 'Tu pedido fue reembolsado' );
add_filter( 'woocommerce_email_heading_new_order',                 fn() => 'Nuevo pedido' );
add_filter( 'woocommerce_email_heading_customer_new_account',      fn() => 'Bienvenido/a a ' . get_bloginfo( 'name' ) );
add_filter( 'woocommerce_email_heading_customer_reset_password',   fn() => 'Restablecimiento de contraseña' );

// Replace WC's 'user_preview' placeholder with the current admin's login in preview/test emails.
add_filter( 'woocommerce_prepare_email_for_preview', function ( $email ) {
    if ( isset( $email->user_login ) && $email->user_login === 'user_preview' ) {
        $current = wp_get_current_user();
        $email->user_login = $current->ID ? $current->user_login : 'cliente';
    }
    return $email;
} );

add_filter( 'woocommerce_email_subject_customer_processing_order', fn( $s, $order ) =>
    'Tu pedido #' . $order->get_order_number() . ' ha sido recibido', 10, 2 );
add_filter( 'woocommerce_email_subject_customer_completed_order',  fn( $s, $order ) =>
    'Tu pedido #' . $order->get_order_number() . ' está completo', 10, 2 );
add_filter( 'woocommerce_email_subject_customer_on_hold_order',    fn( $s, $order ) =>
    'Tu pedido #' . $order->get_order_number() . ' está en espera', 10, 2 );
add_filter( 'woocommerce_email_subject_customer_failed_order',     fn( $s, $order ) =>
    'Tu pedido #' . $order->get_order_number() . ' no se pudo completar', 10, 2 );
add_filter( 'woocommerce_email_subject_customer_cancelled_order',  fn( $s, $order ) =>
    'Tu pedido #' . $order->get_order_number() . ' fue cancelado', 10, 2 );
add_filter( 'woocommerce_email_subject_customer_refunded_order',   fn( $s, $order ) =>
    'Tu pedido #' . $order->get_order_number() . ' fue reembolsado', 10, 2 );
add_filter( 'woocommerce_email_subject_new_order',                 fn( $s, $order ) =>
    'Nuevo pedido #' . $order->get_order_number(), 10, 2 );
add_filter( 'woocommerce_email_subject_customer_new_account',      fn() => 'Tu cuenta en ' . get_bloginfo( 'name' ) );
add_filter( 'woocommerce_email_subject_customer_reset_password',   fn() => 'Restablecimiento de contraseña en ' . get_bloginfo( 'name' ) );

add_filter( 'woocommerce_email_order_meta_fields', function ( $fields, $sent_to_admin, $order ) {
    $email = $order->get_billing_email();
    if ( $email ) {
        $fields['billing_email'] = [
            'label' => 'Correo electrónico',
            'value' => $email,
        ];
    }
    return $fields;
}, 10, 3 );

// Force Stripe.js (and OXXO voucher) to render in Spanish (Latin America).
add_filter( 'wc_stripe_params', function( $params ) {
    $params['stripe_locale'] = 'es-419';
    return $params;
} );

// Force Stripe Customer preferred_locales to Spanish so hosted OXXO voucher pages render in Spanish.
add_filter( 'wc_stripe_create_customer_args', function( $args ) {
    $args['preferred_locales'] = [ 'es-419' ];
    return $args;
} );
add_filter( 'wc_stripe_update_customer_args', function( $args ) {
    $args['preferred_locales'] = [ 'es-419' ];
    return $args;
} );

// Send customer a one-time email with the OXXO voucher link after checkout.
add_action( 'woocommerce_thankyou', function( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    // Only OXXO pending orders.
    if ( 'stripe_oxxo' !== $order->get_payment_method() ) return;
    if ( ! $order->has_status( 'pending' ) ) return;

    // Send only once — mark first to avoid double-send on page refresh.
    if ( $order->get_meta( '_fmdb_oxxo_voucher_email_sent' ) ) return;
    $order->update_meta_data( '_fmdb_oxxo_voucher_email_sent', '1' );
    $order->save();

    // Fetch the payment intent to get the hosted voucher URL.
    $intent_id = $order->get_meta( '_stripe_intent_id' );
    if ( ! $intent_id ) return;

    try {
        $intent = WC_Stripe_API::retrieve( 'payment_intents/' . $intent_id );
    } catch ( Exception $e ) {
        return;
    }

    if ( is_wp_error( $intent ) || empty( $intent->next_action->oxxo_display_details->hosted_voucher_url ) ) return;

    // Ensure the Stripe customer sees the voucher in Spanish.
    if ( ! empty( $intent->customer ) ) {
        try {
            WC_Stripe_API::request( [ 'preferred_locales' => [ 'es-419' ] ], 'customers/' . $intent->customer, 'POST' );
        } catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
        }
    }

    $voucher_url   = $intent->next_action->oxxo_display_details->hosted_voucher_url;
    $expires_after = $intent->next_action->oxxo_display_details->expires_after ?? 0;

    // Persist expiry so the cancel guard knows when the voucher lapses.
    if ( $expires_after ) {
        $order->update_meta_data( '_fmdb_oxxo_voucher_expires', $expires_after );
        $order->save();
    }
    $expires_line   = $expires_after
        ? '<p style="margin:0 0 16px;">Tu voucher vence el <strong>' . date_i18n( 'j \d\e F \d\e Y \a \l\a\s H:i', $expires_after ) . '</strong>. Paga antes de esa fecha para completar tu pedido.</p>'
        : '';

    $first_name  = esc_html( $order->get_billing_first_name() );
    $order_num   = $order->get_order_number();
    $order_total = $order->get_formatted_order_total();
    $site_name   = esc_html( get_bloginfo( 'name' ) );
    $subject     = 'Tu voucher OXXO — Pedido #' . $order_num;
    $heading     = 'Tu voucher OXXO';

    $body = '
        <p>Hola ' . $first_name . ',</p>
        <p>Gracias por tu pedido <strong>#' . $order_num . '</strong> en ' . $site_name . '. Para completar tu compra paga en efectivo en cualquier tienda OXXO presentando el siguiente voucher:</p>
        <p style="text-align:center;margin:24px 0;">
            <a href="' . esc_url( $voucher_url ) . '" style="background:#d32f2f;color:#fff;padding:14px 32px;text-decoration:none;border-radius:4px;font-size:16px;font-weight:bold;display:inline-block;">Ver mi voucher OXXO</a>
        </p>
        ' . $expires_line . '
        <p>Total a pagar: <strong>' . $order_total . '</strong></p>
        <p>Una vez que realices el pago en OXXO recibirás un correo de confirmación en cuanto procesemos tu pago (normalmente en unas horas).</p>
    ';

    $mailer  = WC()->mailer();
    $message = $mailer->wrap_message( $heading, $body );
    $mailer->send( $order->get_billing_email(), $subject, $message, '', [] );
}, 20, 1 );

// Cancel an OXXO order only after its voucher has actually expired — not on WC's generic timer.
add_filter( 'woocommerce_cancel_unpaid_order', function( $cancel, $order ) {
    if ( 'stripe_oxxo' !== $order->get_payment_method() ) {
        return $cancel;
    }
    $expires = (int) $order->get_meta( '_fmdb_oxxo_voucher_expires' );
    if ( ! $expires ) {
        return false; // expiry unknown — don't cancel
    }
    return time() > $expires; // cancel only after the voucher lapses
}, 10, 2 );
