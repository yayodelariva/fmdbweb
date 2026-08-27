<?php
/**
 * Customer reset password email — FMDB override.
 * @version 10.4.0
 */
use Automattic\WooCommerce\Utilities\FeaturesUtil;
if ( ! defined( 'ABSPATH' ) ) exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p><?php printf( 'Hola %s,', esc_html( $user_login ) ); ?></p>
<p><?php printf( 'Se ha solicitado una nueva contraseña para la siguiente cuenta en %s:', esc_html( $blogname ) ); ?></p>
<?php if ( $email_improvements_enabled ) : ?>
	<div class="hr hr-top"></div>
	<p><?php echo wp_kses( sprintf( 'Nombre de usuario: <b>%s</b>', esc_html( $user_login ) ), [ 'b' => [] ] ); ?></p>
	<div class="hr hr-bottom"></div>
	<p>Si no realizaste esta solicitud, ignora este correo. Si deseas continuar, restablece tu contraseña mediante el siguiente enlace:</p>
<?php else : ?>
	<p><?php printf( 'Nombre de usuario: %s', esc_html( $user_login ) ); ?></p>
	<p>Si no realizaste esta solicitud, ignora este correo. Si deseas continuar:</p>
<?php endif; ?>
<p>
	<a class="link" href="<?php echo esc_url( add_query_arg( [ 'key' => $reset_key, 'id' => $user_id, 'login' => rawurlencode( $user_login ) ], wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) ) ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound ?>">
		<?php echo $email_improvements_enabled ? 'Restablecer tu contraseña' : 'Haz clic aquí para restablecer tu contraseña'; ?>
	</a>
</p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content email-additional-content-aligned">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

do_action( 'woocommerce_email_footer', $email );
