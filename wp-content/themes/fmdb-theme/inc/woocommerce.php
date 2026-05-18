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
