<?php
/**
 * Template: Login
 * Used for the page with slug "login"
 */

if ( is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/mi-equipo/' ) );
    exit;
}

$errors      = []; // simple strings, escaped at output
$errors_html = []; // pre-trusted HTML (e.g., unverified link), output as-is
$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( $_GET['redirect_to'] ) : home_url( '/mi-equipo/' );
$resend_mode = isset( $_GET['resend'] );
$resend_done = false;

if ( $resend_mode && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fmdb_resend_nonce'] ) ) {
    if ( ! wp_verify_nonce( $_POST['fmdb_resend_nonce'], 'fmdb_resend' ) ) {
        $errors[] = 'Solicitud inválida. Por favor intenta de nuevo.';
    } else {
        $email = sanitize_email( $_POST['email'] ?? '' );
        if ( is_email( $email ) ) {
            $user = get_user_by( 'email', $email );
            if ( $user && fmdb_user_is_unverified( $user ) ) {
                fmdb_send_verification_email( $user->ID );
            }
        }
        // Always show the same message — don't leak whether the email exists.
        $resend_done = true;
    }
} elseif ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fmdb_login_nonce'] ) ) {
    if ( ! wp_verify_nonce( $_POST['fmdb_login_nonce'], 'fmdb_login' ) ) {
        $errors[] = 'Solicitud inválida. Por favor intenta de nuevo.';
    } else {
        $user = wp_signon( [
            'user_login'    => sanitize_user( $_POST['username'] ?? '' ),
            'user_password' => $_POST['password'] ?? '',
            'remember'      => ! empty( $_POST['remember'] ),
        ], false );

        if ( is_wp_error( $user ) ) {
            if ( $user->get_error_code() === 'fmdb_email_unverified' ) {
                $errors_html[] = $user->get_error_message();
            } else {
                $errors[] = 'Usuario o contraseña incorrectos.';
            }
        } else {
            $dest = isset( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : home_url( '/mi-equipo/' );
            wp_safe_redirect( $dest );
            exit;
        }
    }
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

        <?php if ( $resend_mode ) : ?>

            <h1 class="fmdb-registro__title">Reenviar verificación</h1>
            <p class="fmdb-registro__subtitle">Te enviaremos un nuevo enlace por correo</p>

            <?php if ( $resend_done ) : ?>
                <div class="fmdb-registro__notice fmdb-registro__notice--success">
                    <p>Si existe una cuenta sin verificar con ese correo, te enviamos un nuevo enlace. Revisa tu bandeja de entrada y la carpeta de spam.</p>
                </div>
                <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="fmdb-btn fmdb-btn--primary fmdb-registro__btn">← Volver al inicio de sesión</a>
            <?php else : ?>
                <?php if ( $errors ) : ?>
                    <div class="fmdb-registro__notice fmdb-registro__notice--error">
                        <?php foreach ( $errors as $e ) : ?>
                            <p><?php echo esc_html( $e ); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form class="fmdb-registro__form" method="post">
                    <?php wp_nonce_field( 'fmdb_resend', 'fmdb_resend_nonce' ); ?>
                    <div class="fmdb-registro__field">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" value="<?php echo esc_attr( $_POST['email'] ?? '' ); ?>" required autocomplete="email">
                    </div>
                    <button type="submit" class="fmdb-btn fmdb-btn--primary fmdb-registro__btn">Reenviar correo</button>
                </form>
                <p class="fmdb-registro__login-link"><a href="<?php echo esc_url( home_url( '/login/' ) ); ?>">← Volver al inicio de sesión</a></p>
            <?php endif; ?>

        <?php else : ?>

        <h1 class="fmdb-registro__title">Iniciar sesión</h1>
        <p class="fmdb-registro__subtitle">Federación Mexicana de Dodgeball</p>

        <?php if ( isset( $_GET['password_reset'] ) ) : ?>
            <div class="fmdb-registro__notice fmdb-registro__notice--success">
                <p>¡Contraseña actualizada! Ya puedes iniciar sesión.</p>
            </div>
        <?php endif; ?>

        <?php if ( $errors || $errors_html ) : ?>
            <div class="fmdb-registro__notice fmdb-registro__notice--error">
                <?php foreach ( $errors as $e ) : ?>
                    <p><?php echo esc_html( $e ); ?></p>
                <?php endforeach; ?>
                <?php foreach ( $errors_html as $e ) : ?>
                    <p><?php echo wp_kses( $e, [ 'a' => [ 'href' => true ] ] ); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="fmdb-registro__form" method="post">
            <?php wp_nonce_field( 'fmdb_login', 'fmdb_login_nonce' ); ?>
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">

            <div class="fmdb-registro__field">
                <label for="username">Usuario o correo electrónico</label>
                <input type="text" id="username" name="username" value="<?php echo esc_attr( $_POST['username'] ?? '' ); ?>" required autocomplete="username">
            </div>

            <div class="fmdb-registro__field">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <label class="fmdb-login__remember">
                <input type="checkbox" name="remember" value="1"> Mantener sesión iniciada
            </label>

            <button type="submit" class="fmdb-btn fmdb-btn--primary fmdb-registro__btn">Entrar</button>
        </form>

        <p class="fmdb-registro__forgot">
            <a href="<?php echo esc_url( home_url( '/olvide-mi-contrasena/' ) ); ?>">¿Olvidaste tu contraseña?</a>
        </p>

        <p class="fmdb-registro__login-link">¿No tienes cuenta? <a href="<?php echo esc_url( home_url( '/registro/' ) ); ?>">Regístrate aquí</a></p>

        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
