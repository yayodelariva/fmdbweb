<?php
/**
 * WooCommerce wrapper template
 */
get_header();
?>

<main class="fmdb-tienda">

    <?php if ( ! is_checkout() && ! is_cart() ) : ?>
    <div class="fmdb-tienda__header">
        <?php woocommerce_breadcrumb(); ?>
        <h1 class="fmdb-tienda__title"><?php woocommerce_page_title(); ?></h1>
    </div>
    <?php endif; ?>

    <div class="fmdb-tienda__body">
        <?php woocommerce_content(); ?>
    </div>

</main>

<?php get_footer(); ?>
