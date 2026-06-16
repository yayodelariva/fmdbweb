<?php
/**
 * Template: Verificar Afiliación (admin approval landing)
 * Used for the page with slug "verificar-afiliacion".
 *
 * Resolves the approve/reject link emailed to the site admin when a user
 * requests verification of their affiliation ID from /mi-perfil/.
 */

$uid    = isset( $_GET['uid'] )    ? (int) $_GET['uid'] : 0;
$token  = isset( $_GET['token'] )  ? sanitize_text_field( $_GET['token'] )  : '';
$action = isset( $_GET['action'] ) ? sanitize_key(       $_GET['action'] ) : '';

$result = null; // 'approved' | 'rejected' | 'invalid' | 'missing' | 'unauthorized'

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
    $result = 'unauthorized';
} elseif ( ! $uid || ! $token || ! in_array( $action, [ 'approve', 'reject' ], true ) ) {
    $result = 'missing';
} elseif ( fmdb_resolve_affiliation_token( $uid, $token, $action ) ) {
    $result = $action === 'approve' ? 'approved' : 'rejected';
} else {
    $result = 'invalid';
}

$target_user = $uid ? get_userdata( $uid ) : null;
$target_name = $target_user ? trim( $target_user->first_name . ' ' . $target_user->last_name ) ?: $target_user->display_name : '';
$claimed_id  = $target_user ? get_user_meta( $uid, 'fmdb_affiliation_id', true ) : '';

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

        <?php if ( $result === 'unauthorized' ) : ?>
            <h1 class="fmdb-registro__title">Solo administradores</h1>
            <div class="fmdb-registro__notice fmdb-registro__notice--error">
                <p>Necesitas iniciar sesión como administrador para procesar verificaciones.</p>
            </div>
            <a href="<?php echo esc_url( wp_login_url( $_SERVER['REQUEST_URI'] ?? home_url( '/' ) ) ); ?>" class="fmdb-btn fmdb-btn--primary fmdb-registro__btn">Iniciar sesión</a>

        <?php elseif ( $result === 'approved' ) : ?>
            <h1 class="fmdb-registro__title">ID aprobado</h1>
            <div class="fmdb-registro__notice fmdb-registro__notice--success">
                <p>Marcaste el ID de <strong><?php echo esc_html( $target_name ?: '—' ); ?></strong> como verificado.</p>
                <?php if ( $claimed_id ) : ?>
                    <p>ID ingresado: <code><?php echo esc_html( $claimed_id ); ?></code></p>
                <?php endif; ?>
            </div>

        <?php elseif ( $result === 'rejected' ) : ?>
            <h1 class="fmdb-registro__title">ID rechazado</h1>
            <div class="fmdb-registro__notice fmdb-registro__notice--success">
                <p>Marcaste el ID de <strong><?php echo esc_html( $target_name ?: '—' ); ?></strong> como rechazado.</p>
                <?php if ( $claimed_id ) : ?>
                    <p>ID rechazado: <code><?php echo esc_html( $claimed_id ); ?></code></p>
                <?php endif; ?>
            </div>

        <?php else : ?>
            <h1 class="fmdb-registro__title">Enlace no válido</h1>
            <div class="fmdb-registro__notice fmdb-registro__notice--error">
                <p>El enlace de aprobación no es válido, ya expiró o fue usado anteriormente.</p>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
