<?php
/**
 * Email verification for new account registrations.
 *
 * - Token + expiry stored as user meta (one-time, hashed).
 * - Login is blocked until the user clicks the emailed link.
 * - Existing users (no `fmdb_email_verified` meta set) are grandfathered.
 */

const FMDB_VERIFY_TTL = DAY_IN_SECONDS; // 24h

function fmdb_generate_verification_token( int $user_id ): string {
    $token = wp_generate_password( 32, false );
    update_user_meta( $user_id, 'fmdb_email_verified', '0' );
    update_user_meta( $user_id, 'fmdb_email_token', wp_hash_password( $token ) );
    update_user_meta( $user_id, 'fmdb_email_token_expires', time() + FMDB_VERIFY_TTL );
    return $token;
}

function fmdb_send_verification_email( int $user_id ): bool {
    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return false;
    }
    $token = fmdb_generate_verification_token( $user_id );
    $url   = add_query_arg(
        [ 'uid' => $user_id, 'token' => $token ],
        home_url( '/verificar/' )
    );
    $site    = get_bloginfo( 'name' );
    $name    = $user->first_name ?: $user->user_login;
    $subject = 'Verifica tu cuenta — ' . $site;
    $message = "Hola $name,\n\n"
        . "Gracias por crear tu cuenta en $site.\n\n"
        . "Para activarla, abre este enlace (válido por 24 horas):\n\n"
        . "$url\n\n"
        . "Si tú no creaste esta cuenta, puedes ignorar este correo.\n\n"
        . "— $site";
    return wp_mail( $user->user_email, $subject, $message );
}

function fmdb_verify_token( int $user_id, string $token ): bool {
    $hash    = get_user_meta( $user_id, 'fmdb_email_token', true );
    $expires = (int) get_user_meta( $user_id, 'fmdb_email_token_expires', true );
    if ( ! $hash || $expires < time() ) {
        return false;
    }
    if ( ! wp_check_password( $token, $hash ) ) {
        return false;
    }
    update_user_meta( $user_id, 'fmdb_email_verified', '1' );
    delete_user_meta( $user_id, 'fmdb_email_token' );
    delete_user_meta( $user_id, 'fmdb_email_token_expires' );
    return true;
}

function fmdb_user_is_unverified( WP_User $user ): bool {
    return get_user_meta( $user->ID, 'fmdb_email_verified', true ) === '0';
}

// Block login for accounts whose meta is explicitly '0'. Admins always pass.
add_filter( 'wp_authenticate_user', function ( $user ) {
    if ( ! $user instanceof WP_User ) {
        return $user;
    }
    if ( user_can( $user, 'manage_options' ) ) {
        return $user;
    }
    if ( fmdb_user_is_unverified( $user ) ) {
        $resend = esc_url( add_query_arg( 'resend', '1', home_url( '/login/' ) ) );
        return new WP_Error(
            'fmdb_email_unverified',
            sprintf(
                'Tu cuenta aún no está verificada. Revisa tu correo (y la carpeta de spam) o <a href="%s">reenvía el correo de verificación</a>.',
                $resend
            )
        );
    }
    return $user;
}, 30, 1 );
