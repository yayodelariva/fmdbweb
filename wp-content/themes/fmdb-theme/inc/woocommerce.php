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
    return $options;
} );
add_filter( 'woocommerce_default_catalog_orderby_options', function ( $options ) {
    unset( $options['popularity'], $options['rating'] );
    if ( isset( $options['menu_order'] ) ) $options['menu_order'] = 'Predeterminado';
    if ( isset( $options['date'] ) )       $options['date']       = 'Agregados recientemente';
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
        [ 'Your cart is currently empty!', 'New in store' ],
        [ 'Tu carrito de compras está vacío', 'Podría interesarte:' ],
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
        // WC composes "Add" + lowercase label, so override both halves.
        'apartment, suite, unit, etc.'                     => 'departamento, suite, unidad, etc.',
        'Add'                                              => 'Agregar',
        '+ Add'                                            => '+ Agregar',
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
