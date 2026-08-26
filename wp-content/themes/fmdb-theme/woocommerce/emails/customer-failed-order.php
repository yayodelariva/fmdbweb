<?php
/**
 * Customer failed order email — FMDB override.
 * @version 10.4.0
 */
use Automattic\WooCommerce\Utilities\FeaturesUtil;
if ( ! defined( 'ABSPATH' ) ) exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

do_action( 'woocommerce_email_header', $email_heading, $email );

$first_name = $order->get_billing_first_name();
?>
<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p><?php echo $first_name ? sprintf( 'Hola %s,', esc_html( $first_name ) ) : 'Hola,'; ?></p>
<p>Lo sentimos, no pudimos completar tu pedido debido a un problema con tu método de pago.</p>
<p>Si deseas continuar con tu compra, por favor regresa a <?php echo esc_html( get_bloginfo( 'name' ) ); ?> e intenta con otro método de pago.</p>
<p>Los detalles de tu pedido son los siguientes:</p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
    echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
    echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

do_action( 'woocommerce_email_footer', $email );
