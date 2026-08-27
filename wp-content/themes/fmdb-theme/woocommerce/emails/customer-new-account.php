<?php
/**
 * Customer new account email — FMDB override.
 * @version 10.4.0
 */
use Automattic\WooCommerce\Utilities\FeaturesUtil;
if ( ! defined( 'ABSPATH' ) ) exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

do_action( 'woocommerce_email_header', $email_heading, $email );
?>
<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p><?php printf( 'Hola %s,', esc_html( $user_login ) ); ?></p>
<?php if ( $email_improvements_enabled ) : ?>
	<p><?php printf( 'Gracias por crear una cuenta en %s. Aquí tienes una copia de tus datos de usuario.', esc_html( $blogname ) ); ?></p>
	<div class="hr hr-top"></div>
	<p><?php echo wp_kses( sprintf( 'Nombre de usuario: <b>%s</b>', esc_html( $user_login ) ), [ 'b' => [] ] ); ?></p>
	<?php if ( $password_generated && $set_password_url ) : ?>
		<p><a href="<?php echo esc_attr( $set_password_url ); ?>">Establece tu nueva contraseña.</a></p>
	<?php endif; ?>
	<div class="hr hr-bottom"></div>
	<p>Puedes acceder a tu cuenta para ver tus pedidos, cambiar tu contraseña y más a través del siguiente enlace:</p>
	<p><a href="<?php echo esc_attr( wc_get_page_permalink( 'myaccount' ) ); ?>">Mi cuenta</a></p>
<?php else : ?>
	<p><?php printf( 'Gracias por crear una cuenta en %1$s. Tu nombre de usuario es %2$s. Puedes acceder a tu cuenta para ver tus pedidos, cambiar tu contraseña y más en: %3$s', esc_html( $blogname ), '<strong>' . esc_html( $user_login ) . '</strong>', make_clickable( esc_url( wc_get_page_permalink( 'myaccount' ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	<?php if ( $password_generated && $set_password_url ) : ?>
		<p><a href="<?php echo esc_attr( $set_password_url ); ?>">Haz clic aquí para establecer tu nueva contraseña.</a></p>
	<?php endif; ?>
<?php endif; ?>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content email-additional-content-aligned">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

do_action( 'woocommerce_email_footer', $email );
