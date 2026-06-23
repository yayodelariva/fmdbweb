<?php
/**
 * Affiliation ID verification — admin-approved.
 *
 * Flow:
 *  1. User saves an "ID de afiliación" on /mi-perfil/ and clicks "Verificar".
 *  2. We mark the user as pending and send the site admin an email with
 *     Approve + Reject links (each carries a one-time hashed token).
 *  3. Admin clicks a link → /verificar-afiliacion/ validates the token
 *     and updates the user's status to verified or rejected.
 *
 * Meta keys on the user:
 *  - fmdb_affiliation_id          : the claimed ID (string)
 *  - fmdb_affiliation_status      : '' | 'pending' | 'verified' | 'rejected'
 *  - fmdb_affiliation_token       : wp_hash_password() of the raw token
 *  - fmdb_affiliation_token_expires : unix ts
 */

const FMDB_AFFIL_TOKEN_TTL = 14 * DAY_IN_SECONDS;

function fmdb_affiliation_admin_email(): string {
    return apply_filters( 'fmdb_affiliation_admin_email', get_option( 'admin_email' ) );
}

function fmdb_request_affiliation_verification( int $user_id, string $affiliation_id ): bool {
    $user = get_userdata( $user_id );
    if ( ! $user || $affiliation_id === '' ) {
        return false;
    }

    update_user_meta( $user_id, 'fmdb_affiliation_id', $affiliation_id );
    update_user_meta( $user_id, 'fmdb_affiliation_status', 'pending' );

    $token = wp_generate_password( 32, false );
    update_user_meta( $user_id, 'fmdb_affiliation_token', wp_hash_password( $token ) );
    update_user_meta( $user_id, 'fmdb_affiliation_token_expires', time() + FMDB_AFFIL_TOKEN_TTL );

    $approve_url = add_query_arg(
        [ 'uid' => $user_id, 'token' => $token, 'action' => 'approve' ],
        home_url( '/verificar-afiliacion/' )
    );
    $reject_url = add_query_arg(
        [ 'uid' => $user_id, 'token' => $token, 'action' => 'reject' ],
        home_url( '/verificar-afiliacion/' )
    );

    $site    = get_bloginfo( 'name' );
    $name    = trim( $user->first_name . ' ' . $user->last_name ) ?: $user->display_name;
    $subject = sprintf( '[%s] Solicitud de verificación de afiliación', $site );
    $message = "Un usuario solicitó verificación de su ID de afiliación.\n\n"
        . "Usuario:       $name <{$user->user_email}>\n"
        . "Usuario WP:    {$user->user_login}\n"
        . "ID ingresado:  $affiliation_id\n\n"
        . "Verifica que coincida con el ID asignado a este usuario:\n\n"
        . "Aprobar:\n$approve_url\n\n"
        . "Rechazar:\n$reject_url\n\n"
        . "Los enlaces expiran en 14 días y solo funcionan una vez.\n\n"
        . "— $site";

    return wp_mail( fmdb_affiliation_admin_email(), $subject, $message );
}

function fmdb_resolve_affiliation_token( int $user_id, string $token, string $action ): bool {
    if ( ! in_array( $action, [ 'approve', 'reject' ], true ) ) {
        return false;
    }
    $hash    = get_user_meta( $user_id, 'fmdb_affiliation_token', true );
    $expires = (int) get_user_meta( $user_id, 'fmdb_affiliation_token_expires', true );
    if ( ! $hash || $expires < time() ) {
        return false;
    }
    if ( ! wp_check_password( $token, $hash ) ) {
        return false;
    }
    update_user_meta(
        $user_id,
        'fmdb_affiliation_status',
        $action === 'approve' ? 'verified' : 'rejected'
    );
    delete_user_meta( $user_id, 'fmdb_affiliation_token' );
    delete_user_meta( $user_id, 'fmdb_affiliation_token_expires' );
    return true;
}

function fmdb_affiliation_status( int $user_id ): string {
    return (string) get_user_meta( $user_id, 'fmdb_affiliation_status', true );
}

function fmdb_affiliation_status_label( string $status ): array {
    switch ( $status ) {
        case 'verified': return [ 'verified', 'Verificado' ];
        case 'pending':  return [ 'pending',  'En revisión' ];
        case 'rejected': return [ 'rejected', 'Rechazado' ];
        default:         return [ 'none',     'Sin verificar' ];
    }
}

/* =========================================================================
 * wp-admin → Users integration
 * =======================================================================*/

// New column on the Users list table.
add_filter( 'manage_users_columns', function ( $cols ) {
    $cols['fmdb_affiliation'] = 'ID afiliación';
    return $cols;
} );

add_filter( 'manage_users_custom_column', function ( $out, $col, $user_id ) {
    if ( $col !== 'fmdb_affiliation' ) return $out;
    $id     = get_user_meta( $user_id, 'fmdb_affiliation_id', true );
    $status = fmdb_affiliation_status( (int) $user_id );
    [ $state, $label ] = fmdb_affiliation_status_label( $status );
    $badge_color = [
        'verified' => '#0f6b48',
        'pending'  => '#856404',
        'rejected' => '#842029',
        'none'     => '#777',
    ][ $state ];
    $badge = sprintf(
        '<span style="display:inline-block;padding:1px 8px;border-radius:999px;background:%s;color:#fff;font-size:11px;font-weight:600;">%s</span>',
        esc_attr( $badge_color ),
        esc_html( $label )
    );
    $id_html = $id ? '<code>' . esc_html( $id ) . '</code><br>' : '<em style="color:#999;">(vacío)</em><br>';
    return $id_html . $badge;
}, 10, 3 );

// Make sortable by status.
add_filter( 'manage_users_sortable_columns', function ( $cols ) {
    $cols['fmdb_affiliation'] = 'fmdb_affiliation';
    return $cols;
} );

add_action( 'pre_get_users', function ( $query ) {
    if ( ! is_admin() ) return;
    if ( $query->get( 'orderby' ) !== 'fmdb_affiliation' ) return;
    $query->set( 'meta_key', 'fmdb_affiliation_status' );
    $query->set( 'orderby', 'meta_value' );
} );

// Editable section on the User Edit screen.
function fmdb_render_affiliation_profile_fields( $user ) {
    if ( ! current_user_can( 'fmdb_manage_affiliations' ) ) return;
    $id     = get_user_meta( $user->ID, 'fmdb_affiliation_id', true );
    $status = fmdb_affiliation_status( $user->ID );
    ?>
    <h2>Afiliación FMDB</h2>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="fmdb_affiliation_id">ID de afiliación</label></th>
            <td>
                <input type="text" name="fmdb_affiliation_id" id="fmdb_affiliation_id" value="<?php echo esc_attr( $id ); ?>" class="regular-text">
                <p class="description">ID oficial asignado al usuario en el sistema de afiliación.</p>
            </td>
        </tr>
        <tr>
            <th><label for="fmdb_affiliation_status">Estado</label></th>
            <td>
                <select name="fmdb_affiliation_status" id="fmdb_affiliation_status">
                    <option value=""         <?php selected( $status, '' );         ?>>Sin verificar</option>
                    <option value="pending"  <?php selected( $status, 'pending' );  ?>>En revisión</option>
                    <option value="verified" <?php selected( $status, 'verified' ); ?>>Verificado</option>
                    <option value="rejected" <?php selected( $status, 'rejected' ); ?>>Rechazado</option>
                </select>
                <p class="description">Cambiar el estado aquí omite el flujo de aprobación por correo.</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action( 'show_user_profile', 'fmdb_render_affiliation_profile_fields' );
add_action( 'edit_user_profile', 'fmdb_render_affiliation_profile_fields' );

function fmdb_save_affiliation_profile_fields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) || ! current_user_can( 'fmdb_manage_affiliations' ) ) return;

    if ( isset( $_POST['fmdb_affiliation_id'] ) ) {
        $id = sanitize_text_field( wp_unslash( $_POST['fmdb_affiliation_id'] ) );
        update_user_meta( $user_id, 'fmdb_affiliation_id', $id );
    }
    if ( isset( $_POST['fmdb_affiliation_status'] ) ) {
        $status = sanitize_key( wp_unslash( $_POST['fmdb_affiliation_status'] ) );
        $allowed = [ '', 'pending', 'verified', 'rejected' ];
        if ( in_array( $status, $allowed, true ) ) {
            update_user_meta( $user_id, 'fmdb_affiliation_status', $status );
            // Clear any pending token when the admin overrides directly.
            if ( $status !== 'pending' ) {
                delete_user_meta( $user_id, 'fmdb_affiliation_token' );
                delete_user_meta( $user_id, 'fmdb_affiliation_token_expires' );
            }
        }
    }
}
add_action( 'personal_options_update',  'fmdb_save_affiliation_profile_fields' );
add_action( 'edit_user_profile_update', 'fmdb_save_affiliation_profile_fields' );

/* =========================================================================
 * wp-admin → Usuarios → Afiliaciones page
 * =======================================================================*/

add_action( 'admin_menu', function () {
    add_submenu_page(
        'users.php',
        'Afiliaciones FMDB',
        'Afiliaciones',
        'fmdb_manage_affiliations',
        'fmdb-affiliations',
        'fmdb_render_affiliations_page'
    );
} );

function fmdb_render_affiliations_page() {
    if ( ! current_user_can( 'fmdb_manage_affiliations' ) ) {
        wp_die( 'No tienes permiso para acceder a esta página.' );
    }

    // Handle approve / reject actions.
    if ( isset( $_POST['fmdb_affil_action'], $_POST['fmdb_affil_uid'], $_POST['_wpnonce'] ) ) {
        $uid    = absint( $_POST['fmdb_affil_uid'] );
        $action = sanitize_key( $_POST['fmdb_affil_action'] );
        if ( wp_verify_nonce( $_POST['_wpnonce'], 'fmdb_affil_' . $uid ) && in_array( $action, [ 'approve', 'reject' ], true ) ) {
            $new_status = $action === 'approve' ? 'verified' : 'rejected';
            update_user_meta( $uid, 'fmdb_affiliation_status', $new_status );
            delete_user_meta( $uid, 'fmdb_affiliation_token' );
            delete_user_meta( $uid, 'fmdb_affiliation_token_expires' );
            $label = $action === 'approve' ? 'aprobada' : 'rechazada';
            echo '<div class="notice notice-success is-dismissible"><p>Afiliación ' . esc_html( $label ) . '.</p></div>';
        }
    }

    // Filter tabs.
    $current_filter = sanitize_key( $_GET['status'] ?? 'pending' );
    $filters = [
        'pending'  => 'Pendientes',
        'verified' => 'Verificados',
        'rejected' => 'Rechazados',
        'all'      => 'Todos',
    ];

    // Query users.
    $args = [ 'number' => 100, 'orderby' => 'registered', 'order' => 'DESC' ];
    if ( $current_filter !== 'all' ) {
        $args['meta_query'] = [ [ 'key' => 'fmdb_affiliation_status', 'value' => $current_filter ] ];
    } else {
        $args['meta_query'] = [ [ 'key' => 'fmdb_affiliation_id', 'compare' => 'EXISTS' ] ];
    }
    $users = get_users( $args );

    // Count pending for badge.
    $pending_count = count( get_users( [
        'fields'     => 'ID',
        'meta_query' => [ [ 'key' => 'fmdb_affiliation_status', 'value' => 'pending' ] ],
    ] ) );

    $badge_colors = [
        'verified' => '#0f6b48',
        'pending'  => '#856404',
        'rejected' => '#842029',
        'none'     => '#777',
    ];

    ?>
    <div class="wrap">
        <h1>Afiliaciones FMDB</h1>

        <ul class="subsubsub">
            <?php $i = 0; foreach ( $filters as $key => $label ) : $i++; ?>
                <li>
                    <a href="<?php echo esc_url( admin_url( 'users.php?page=fmdb-affiliations&status=' . $key ) ); ?>"
                       class="<?php echo $current_filter === $key ? 'current' : ''; ?>">
                        <?php echo esc_html( $label ); ?>
                        <?php if ( $key === 'pending' && $pending_count ) : ?>
                            <span class="count">(<?php echo (int) $pending_count; ?>)</span>
                        <?php endif; ?>
                    </a>
                    <?php echo $i < count( $filters ) ? ' |' : ''; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <table class="wp-list-table widefat fixed striped" style="margin-top:12px;">
            <thead>
                <tr>
                    <th style="width:22%;">Usuario</th>
                    <th style="width:22%;">Correo</th>
                    <th style="width:15%;">ID de afiliación</th>
                    <th style="width:12%;">Estado</th>
                    <th style="width:15%;">Fecha</th>
                    <th style="width:14%;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $users ) ) : ?>
                    <tr><td colspan="6" style="text-align:center;padding:20px;color:#888;">No hay solicitudes <?php echo $current_filter !== 'all' ? esc_html( strtolower( $filters[ $current_filter ] ) ) : ''; ?>.</td></tr>
                <?php endif; ?>
                <?php foreach ( $users as $u ) :
                    $af_id     = get_user_meta( $u->ID, 'fmdb_affiliation_id', true );
                    $af_status = fmdb_affiliation_status( $u->ID );
                    [ $state, $state_label ] = fmdb_affiliation_status_label( $af_status );
                    $name = trim( $u->first_name . ' ' . $u->last_name ) ?: $u->display_name;
                    $nonce = wp_create_nonce( 'fmdb_affil_' . $u->ID );
                ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo esc_url( get_edit_user_link( $u->ID ) ); ?>"><?php echo esc_html( $name ); ?></a></strong>
                            <br><small style="color:#888;"><?php echo esc_html( $u->user_login ); ?></small>
                        </td>
                        <td><?php echo esc_html( $u->user_email ); ?></td>
                        <td><code><?php echo esc_html( $af_id ?: '—' ); ?></code></td>
                        <td>
                            <span style="display:inline-block;padding:2px 10px;border-radius:999px;background:<?php echo esc_attr( $badge_colors[ $state ] ); ?>;color:#fff;font-size:12px;font-weight:600;">
                                <?php echo esc_html( $state_label ); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html( date_i18n( 'j M Y', strtotime( $u->user_registered ) ) ); ?></td>
                        <td>
                            <?php if ( $af_status === 'pending' ) : ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="fmdb_affil_uid" value="<?php echo (int) $u->ID; ?>">
                                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">
                                    <button type="submit" name="fmdb_affil_action" value="approve" class="button button-small button-primary">Aprobar</button>
                                    <button type="submit" name="fmdb_affil_action" value="reject" class="button button-small" style="color:#842029;">Rechazar</button>
                                </form>
                            <?php else : ?>
                                <span style="color:#999;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
