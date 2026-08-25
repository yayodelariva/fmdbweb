<?php
/**
 * Customer processing order email — FMDB override.
 * Replaces the generic WooCommerce copy with registration-specific messaging.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

do_action( 'woocommerce_email_header', $email_heading, $email );

// Pull registration details from the first order item that has team meta.
$reg_equipo    = '';
$reg_rama      = '';
$reg_categoria = '';
$reg_modalidad = '';
$reg_capitan   = '';
$reg_jugadores = '';
$reg_evento    = '';

foreach ( $order->get_items() as $item ) {
    $equipo = $item->get_meta( 'Equipo' );
    if ( $equipo ) {
        $reg_equipo    = $equipo;
        $reg_rama      = $item->get_meta( 'Rama' );
        $reg_categoria = $item->get_meta( 'Categoría' );
        $reg_modalidad = $item->get_meta( 'Modalidad' );
        $reg_capitan   = $item->get_meta( 'Capitán' );
        $reg_jugadores = $item->get_meta( 'Jugadores' );
        $reg_evento    = $item->get_meta( 'Evento' );
        break;
    }
}

$first_name = $order->get_billing_first_name();
?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php echo $first_name
    ? sprintf( 'Hola %s,', esc_html( $first_name ) )
    : 'Hola,'; ?>
</p>

<?php if ( $reg_equipo ) : ?>
    <p>¡Tu inscripción al torneo ha sido recibida y está siendo procesada! Aquí tienes un resumen de tu registro:</p>

    <table cellpadding="0" cellspacing="0" style="width:100%;margin:16px 0;border-collapse:collapse;">
        <?php if ( $reg_evento ) : ?>
        <tr>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;font-weight:700;width:40%;">Evento</td>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;"><?php echo esc_html( $reg_evento ); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;font-weight:700;">Equipo</td>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;"><?php echo esc_html( $reg_equipo ); ?></td>
        </tr>
        <?php if ( $reg_capitan ) : ?>
        <tr>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;font-weight:700;">Capitán</td>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;"><?php echo esc_html( $reg_capitan ); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ( $reg_rama ) : ?>
        <tr>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;font-weight:700;">Rama</td>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;"><?php echo esc_html( $reg_rama ); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ( $reg_categoria ) : ?>
        <tr>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;font-weight:700;">Categoría</td>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;"><?php echo esc_html( $reg_categoria ); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ( $reg_modalidad ) : ?>
        <tr>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;font-weight:700;">Modalidad</td>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;"><?php echo esc_html( $reg_modalidad ); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ( $reg_jugadores ) : ?>
        <tr>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;font-weight:700;">Jugadores registrados</td>
            <td style="padding:8px 12px;border:1px solid #e5e5e5;"><?php echo esc_html( $reg_jugadores ); ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <p>Una vez que tu pago sea verificado, tu equipo quedará confirmado en la página del evento. Si tienes alguna duda, responde a este correo.</p>
<?php else : ?>
    <p>Hemos recibido tu orden #<?php echo esc_html( $order->get_order_number() ); ?> y está siendo procesada.</p>
<?php endif; ?>

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
