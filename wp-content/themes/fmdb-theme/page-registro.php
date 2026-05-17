<?php
/**
 * Template: Registro (New user registration)
 * Used for the page with slug "registro"
 */

if ( is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/mi-equipo/' ) );
    exit;
}

$errors   = [];
$success  = false;

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fmdb_register_nonce'] ) ) {
    if ( ! wp_verify_nonce( $_POST['fmdb_register_nonce'], 'fmdb_register' ) ) {
        $errors[] = 'Solicitud inválida. Por favor intenta de nuevo.';
    } else {
        $first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
        $last_name  = sanitize_text_field( $_POST['last_name'] ?? '' );
        $username   = sanitize_user( $_POST['username'] ?? '' );
        $email      = sanitize_email( $_POST['email'] ?? '' );
        $password   = $_POST['password'] ?? '';
        $password2  = $_POST['password2'] ?? '';
        $role = 'jugador';

        if ( ! $first_name ) $errors[] = 'El nombre es obligatorio.';
        if ( ! $last_name )  $errors[] = 'El apellido es obligatorio.';
        if ( ! $username )   $errors[] = 'El nombre de usuario es obligatorio.';
        if ( ! is_email( $email ) ) $errors[] = 'Ingresa un correo electrónico válido.';
        if ( strlen( $password ) < 8 ) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ( $password !== $password2 ) $errors[] = 'Las contraseñas no coinciden.';
        if ( username_exists( $username ) ) $errors[] = 'Ese nombre de usuario ya está en uso.';
        if ( email_exists( $email ) ) $errors[] = 'Ese correo electrónico ya está registrado.';

        if ( empty( $errors ) ) {
            $user_id = wp_create_user( $username, $password, $email );
            if ( is_wp_error( $user_id ) ) {
                $errors[] = $user_id->get_error_message();
            } else {
                $user = new WP_User( $user_id );
                $user->set_role( $role );
                wp_update_user( [
                    'ID'           => $user_id,
                    'first_name'   => $first_name,
                    'last_name'    => $last_name,
                    'display_name' => "$first_name $last_name",
                ] );
                $success = true;
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

        <h1 class="fmdb-registro__title">Crear cuenta</h1>
        <p class="fmdb-registro__subtitle">Únete a la comunidad FMDB</p>

        <?php if ( $success ) : ?>
            <div class="fmdb-registro__notice fmdb-registro__notice--success">
                <strong>¡Cuenta creada!</strong> Ahora puedes iniciar sesión.
            </div>
            <a href="<?php echo esc_url( wp_login_url( home_url( '/mi-equipo/' ) ) ); ?>" class="fmdb-btn fmdb-btn--primary fmdb-registro__btn">Iniciar sesión</a>
        <?php else : ?>

            <?php if ( $errors ) : ?>
                <div class="fmdb-registro__notice fmdb-registro__notice--error">
                    <?php foreach ( $errors as $e ) : ?>
                        <p><?php echo esc_html( $e ); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form class="fmdb-registro__form" method="post">
                <?php wp_nonce_field( 'fmdb_register', 'fmdb_register_nonce' ); ?>

                <div class="fmdb-registro__row">
                    <div class="fmdb-registro__field">
                        <label for="first_name">Nombre</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( $_POST['first_name'] ?? '' ); ?>" required autocomplete="given-name">
                    </div>
                    <div class="fmdb-registro__field">
                        <label for="last_name">Apellido</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( $_POST['last_name'] ?? '' ); ?>" required autocomplete="family-name">
                    </div>
                </div>

                <div class="fmdb-registro__field">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" value="<?php echo esc_attr( $_POST['email'] ?? '' ); ?>" required autocomplete="email">
                </div>

                <div class="fmdb-registro__field">
                    <label for="username">Nombre de usuario</label>
                    <input type="text" id="username" name="username" value="<?php echo esc_attr( $_POST['username'] ?? '' ); ?>" required autocomplete="username">
                </div>

<div class="fmdb-registro__row">
                    <div class="fmdb-registro__field">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8">
                    </div>
                    <div class="fmdb-registro__field">
                        <label for="password2">Confirmar contraseña</label>
                        <input type="password" id="password2" name="password2" required autocomplete="new-password" minlength="8">
                    </div>
                </div>

                <button type="submit" class="fmdb-btn fmdb-btn--primary fmdb-registro__btn">Crear cuenta</button>
            </form>

        <?php endif; ?>

        <p class="fmdb-registro__login-link">¿Ya tienes cuenta? <a href="<?php echo esc_url( wp_login_url( home_url( '/mi-equipo/' ) ) ); ?>">Iniciar sesión</a></p>

    </div>
</main>

<?php get_footer(); ?>
