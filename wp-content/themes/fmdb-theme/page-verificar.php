<?php
/**
 * Template: Verificar (email verification landing)
 * Used for the page with slug "verificar".
 */

$uid    = isset( $_GET['uid'] )   ? (int) $_GET['uid'] : 0;
$token  = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';
$result = null; // 'success' | 'invalid' | 'missing'

if ( ! $uid || ! $token ) {
    $result = 'missing';
} elseif ( fmdb_verify_token( $uid, $token ) ) {
    $result = 'success';
} else {
    $result = 'invalid';
}

get_header();
?>

<main class="fmdb-registro">
    <div class="fmdb-registro__card">

        <div class="fmdb-registro__logo">
            <?php
            $logo = get_theme_mod( 'custom_logo' );
            if ( $logo ) :
                echo wp_get_attachment_image( $logo, 'medium', false, [ 'class' => 'fmdb-registro__logo-img' ] );
            else : ?>
                <div class="fmdb-registro__logo-text">FMDB</div>
            <?php endif; ?>
        </div>

        <?php if ( $result === 'success' ) : ?>
            <h1 class="fmdb-registro__title">¡Cuenta verificada!</h1>
            <div class="fmdb-registro__notice fmdb-registro__notice--success">
                <p>Tu correo está confirmado. Ya puedes iniciar sesión.</p>
            </div>
            <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="fmdb-btn fmdb-btn--primary fmdb-registro__btn">Iniciar sesión</a>

        <?php else : ?>
            <h1 class="fmdb-registro__title">Enlace no válido</h1>
            <div class="fmdb-registro__notice fmdb-registro__notice--error">
                <p>El enlace de verificación no es válido o ya expiró.</p>
                <p>Inicia sesión e intenta reenviar el correo de verificación, o crea una cuenta nueva.</p>
            </div>
            <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="fmdb-btn fmdb-btn--primary fmdb-registro__btn">Ir a iniciar sesión</a>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
