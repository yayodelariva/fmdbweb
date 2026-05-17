<?php
/**
 * Template: Olvidé mi contraseña
 * Slug: olvide-mi-contrasena
 */

if ( is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/mi-equipo/' ) );
    exit;
}

$action  = sanitize_key( $_GET['action'] ?? 'request' );
$notices = [];
$done    = false;

// ── Step 2: show reset form or handle new password submission ──────────────
if ( $action === 'reset' ) {
    $rp_key   = sanitize_text_field( wp_unslash( $_GET['key']   ?? '' ) );
    $rp_login = sanitize_user( wp_unslash( $_GET['login'] ?? '' ) );

    if ( empty( $rp_key ) || empty( $rp_login ) ) {
        $notices[] = [ 'type' => 'error', 'msg' => 'El enlace de recuperación no es válido.' ];
        $action    = 'request';
    } else {
        $reset_user = check_password_reset_key( $rp_key, $rp_login );
        if ( is_wp_error( $reset_user ) ) {
            $notices[] = [ 'type' => 'error', 'msg' => 'El enlace ha expirado o no es válido. Solicita uno nuevo.' ];
            $action    = 'request';
        }
    }

    if ( $action === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fmdb_reset_nonce'] ) ) {
        if ( ! wp_verify_nonce( $_POST['fmdb_reset_nonce'], 'fmdb_reset' ) ) {
            $notices[] = [ 'type' => 'error', 'msg' => 'Solicitud inválida. Intenta de nuevo.' ];
        } else {
            $pass1 = $_POST['pass1'] ?? '';
            $pass2 = $_POST['pass2'] ?? '';

            if ( strlen( $pass1 ) < 8 ) {
                $notices[] = [ 'type' => 'error', 'msg' => 'La contraseña debe tener al menos 8 caracteres.' ];
            } elseif ( $pass1 !== $pass2 ) {
                $notices[] = [ 'type' => 'error', 'msg' => 'Las contraseñas no coinciden.' ];
            } else {
                reset_password( $reset_user, $pass1 );
                wp_safe_redirect( add_query_arg( 'password_reset', '1', home_url( '/login/' ) ) );
                exit;
            }
        }
    }
}

// ── Step 1: handle email/username submission ───────────────────────────────
if ( $action === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fmdb_forgot_nonce'] ) ) {
    if ( ! wp_verify_nonce( $_POST['fmdb_forgot_nonce'], 'fmdb_forgot' ) ) {
        $notices[] = [ 'type' => 'error', 'msg' => 'Solicitud inválida. Intenta de nuevo.' ];
    } else {
        $user_login = sanitize_text_field( wp_unslash( $_POST['user_login'] ?? '' ) );
        $found_user = is_email( $user_login )
            ? get_user_by( 'email', $user_login )
            : get_user_by( 'login', $user_login );

        if ( ! $found_user ) {
            $done = true; // don't reveal whether the account exists
        } else {
            $reset_key = get_password_reset_key( $found_user );
            if ( is_wp_error( $reset_key ) ) {
                $notices[] = [ 'type' => 'error', 'msg' => 'No se pudo generar el enlace. Intenta de nuevo más tarde.' ];
            } else {
                $reset_url = add_query_arg( [
                    'action' => 'reset',
                    'key'    => $reset_key,
                    'login'  => rawurlencode( $found_user->user_login ),
                ], home_url( '/olvide-mi-contrasena/' ) );

                $site  = get_bloginfo( 'name' );
                $body  = "Hola {$found_user->display_name},\n\n"
                       . "Recibimos una solicitud para restablecer la contraseña de tu cuenta en {$site}.\n\n"
                       . "Haz clic en el siguiente enlace para crear una nueva contraseña (válido por 24 horas):\n\n"
                       . $reset_url . "\n\n"
                       . "Si no solicitaste este cambio, puedes ignorar este mensaje.\n\n"
                       . "— {$site}";

                wp_mail( $found_user->user_email, "Recupera tu contraseña — {$site}", $body );
                $done = true;
            }
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

        <?php foreach ( $notices as $n ) : ?>
            <div class="fmdb-registro__notice fmdb-registro__notice--<?php echo esc_attr( $n['type'] ); ?>">
                <p><?php echo esc_html( $n['msg'] ); ?></p>
            </div>
        <?php endforeach; ?>

        <?php if ( $done ) : ?>
            <h1 class="fmdb-registro__title">Revisa tu correo</h1>
            <div class="fmdb-registro__notice fmdb-registro__notice--success">
                <p>Si existe una cuenta con esos datos, recibirás un correo con las instrucciones para restablecer tu contraseña.</p>
            </div>
            <p class="fmdb-registro__login-link"><a href="<?php echo esc_url( home_url( '/login/' ) ); ?>">← Volver al inicio de sesión</a></p>

        <?php elseif ( $action === 'reset' ) : ?>
            <h1 class="fmdb-registro__title">Nueva contraseña</h1>
            <p class="fmdb-registro__subtitle">Elige una contraseña segura de al menos 8 caracteres.</p>

            <form class="fmdb-registro__form" method="post">
                <?php wp_nonce_field( 'fmdb_reset', 'fmdb_reset_nonce' ); ?>
                <input type="hidden" name="action" value="reset">
                <input type="hidden" name="key"    value="<?php echo esc_attr( $rp_key ); ?>">
                <input type="hidden" name="login"  value="<?php echo esc_attr( $rp_login ); ?>">

                <div class="fmdb-registro__field">
                    <label for="pass1">Nueva contraseña</label>
                    <input type="password" id="pass1" name="pass1" required autocomplete="new-password" minlength="8">
                </div>
                <div class="fmdb-registro__field">
                    <label for="pass2">Confirmar contraseña</label>
                    <input type="password" id="pass2" name="pass2" required autocomplete="new-password" minlength="8">
                </div>

                <button type="submit" class="fmdb-btn fmdb-btn--primary fmdb-registro__btn">Guardar contraseña</button>
            </form>

        <?php else : ?>
            <h1 class="fmdb-registro__title">¿Olvidaste tu contraseña?</h1>
            <p class="fmdb-registro__subtitle">Ingresa tu usuario o correo y te enviaremos un enlace para recuperarla.</p>

            <form class="fmdb-registro__form" method="post">
                <?php wp_nonce_field( 'fmdb_forgot', 'fmdb_forgot_nonce' ); ?>

                <div class="fmdb-registro__field">
                    <label for="user_login">Usuario o correo electrónico</label>
                    <input type="text" id="user_login" name="user_login" value="<?php echo esc_attr( $_POST['user_login'] ?? '' ); ?>" required autocomplete="username">
                </div>

                <button type="submit" class="fmdb-btn fmdb-btn--primary fmdb-registro__btn">Enviar enlace</button>
            </form>

            <p class="fmdb-registro__login-link"><a href="<?php echo esc_url( home_url( '/login/' ) ); ?>">← Volver al inicio de sesión</a></p>

        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
