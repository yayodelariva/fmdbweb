<?php
/**
 * WooCommerce wrapper template
 */
get_header();
?>

<main class="fmdb-tienda">

    <?php if ( is_product() || is_cart() || is_checkout() ) :
        $shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
    ?>
    <p class="fmdb-tienda__back">
        <a href="<?php echo esc_url( $shop_url ); ?>" class="fmdb-tienda__back-link">&larr; De regreso a la Tienda</a>
    </p>
    <?php endif; ?>

    <?php if ( ! is_checkout() && ! is_cart() ) : ?>
    <div class="fmdb-tienda__header">
        <h1 class="fmdb-tienda__title"><?php woocommerce_page_title(); ?></h1>
    </div>
    <?php endif; ?>

    <div class="fmdb-tienda__body">
        <?php woocommerce_content(); ?>
    </div>

</main>

<?php get_footer(); ?>
