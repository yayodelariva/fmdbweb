<?php
/**
 * Customer on-hold order email — FMDB override.
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
<p>Hemos recibido tu pedido y está en espera de confirmación de pago. A continuación encontrarás un resumen de tu compra:</p>
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
