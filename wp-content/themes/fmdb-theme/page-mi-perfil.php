<?php
/**
 * Template: Mi Perfil
 */

if ( ! is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/login/' ) );
    exit;
}

$user_id = get_current_user_id();
$user    = wp_get_current_user();
$notices = [];

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    // --- Avatar form ---
    if ( isset( $_POST['fmdb_avatar_nonce'] ) ) {
        if ( ! wp_verify_nonce( $_POST['fmdb_avatar_nonce'], 'fmdb_avatar' ) ) {
            $notices[] = [ 'type' => 'error', 'msg' => 'Solicitud inválida. Intenta de nuevo.' ];
        } elseif ( empty( $_FILES['profile_picture']['tmp_name'] ) ) {
            $notices[] = [ 'type' => 'error', 'msg' => 'Selecciona una imagen antes de guardar.' ];
        } else {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $att_id = media_handle_upload( 'profile_picture', 0 );
            if ( is_wp_error( $att_id ) ) {
                $notices[] = [ 'type' => 'error', 'msg' => 'Error al subir la imagen: ' . $att_id->get_error_message() ];
            } else {
                $old = get_user_meta( $user_id, 'fmdb_profile_picture', true );
                if ( $old ) wp_delete_attachment( (int) $old, true );
                update_user_meta( $user_id, 'fmdb_profile_picture', $att_id );
                $notices[] = [ 'type' => 'success', 'msg' => 'Foto de perfil actualizada.' ];
            }
        }
    }

    // --- Name form ---
    if ( isset( $_POST['fmdb_perfil_nonce'] ) ) {
        if ( ! wp_verify_nonce( $_POST['fmdb_perfil_nonce'], 'fmdb_perfil' ) ) {
            $notices[] = [ 'type' => 'error', 'msg' => 'Solicitud inválida. Intenta de nuevo.' ];
        } else {
            $first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
            $last_name  = sanitize_text_field( $_POST['last_name']  ?? '' );

            // Blank input falls back to the existing value, so the placeholder UI
            // can't accidentally wipe what the user registered with.
            if ( $first_name === '' ) {
                $first_name = (string) get_user_meta( $user_id, 'first_name', true );
            }
            if ( $last_name === '' ) {
                $last_name = (string) get_user_meta( $user_id, 'last_name', true );
            }

            $result = wp_update_user( [
                'ID'           => $user_id,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => trim( "$first_name $last_name" ) ?: $user->user_login,
            ] );

            if ( is_wp_error( $result ) ) {
                $notices[] = [ 'type' => 'error', 'msg' => 'Error al guardar los cambios.' ];
            } else {
                $notices[] = [ 'type' => 'success', 'msg' => 'Perfil actualizado correctamente.' ];
                $user = wp_get_current_user();
            }
        }
    }

    // --- Affiliation ID form ---
    if ( isset( $_POST['fmdb_affiliation_nonce'] ) ) {
        if ( ! wp_verify_nonce( $_POST['fmdb_affiliation_nonce'], 'fmdb_affiliation' ) ) {
            $notices[] = [ 'type' => 'error', 'msg' => 'Solicitud inválida. Intenta de nuevo.' ];
        } else {
            $affiliation_id = sanitize_text_field( $_POST['fmdb_affiliation_id'] ?? '' );

            if ( $affiliation_id === '' ) {
                $notices[] = [ 'type' => 'error', 'msg' => 'Ingresa tu ID de afiliación antes de solicitar verificación.' ];
            } elseif ( fmdb_request_affiliation_verification( $user_id, $affiliation_id ) ) {
                $notices[] = [ 'type' => 'success', 'msg' => 'Enviamos tu ID a un administrador para revisión. Te avisaremos cuando esté verificado.' ];
            } else {
                $notices[] = [ 'type' => 'error',   'msg' => 'No pudimos enviar la solicitud de verificación. Intenta de nuevo más tarde.' ];
            }
        }
    }
}

$first_name     = get_user_meta( $user_id, 'first_name', true );
$last_name      = get_user_meta( $user_id, 'last_name',  true );
// Accounts created without first/last meta (e.g. via wp-admin) — derive from display_name.
if ( $first_name === '' && $last_name === '' && $user->display_name ) {
    $parts      = explode( ' ', $user->display_name, 2 );
    $first_name = $parts[0] ?? '';
    $last_name  = $parts[1] ?? '';
}
$pic_id         = get_user_meta( $user_id, 'fmdb_profile_picture', true );
$pic_url        = $pic_id ? wp_get_attachment_image_url( (int) $pic_id, 'medium' ) : '';
$initial        = esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) );
$affiliation_id = get_user_meta( $user_id, 'fmdb_affiliation_id', true );
$affil_status   = fmdb_affiliation_status( $user_id );
[ $affil_state, $affil_label ] = fmdb_affiliation_status_label( $affil_status );

get_header();
?>

<main class="fmdb-perfil">
    <div class="fmdb-perfil__card">

        <h1 class="fmdb-perfil__title">Mi Perfil</h1>

        <?php foreach ( $notices as $n ) : ?>
            <div class="fmdb-registro__notice fmdb-registro__notice--<?php echo esc_attr( $n['type'] ); ?>">
                <p><?php echo esc_html( $n['msg'] ); ?></p>
            </div>
        <?php endforeach; ?>

        <form class="fmdb-perfil__form fmdb-perfil__form--avatar" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'fmdb_avatar', 'fmdb_avatar_nonce' ); ?>

            <div class="fmdb-perfil__avatar-section">
                <div class="fmdb-perfil__avatar" id="fmdb-avatar-preview">
                    <?php if ( $pic_url ) : ?>
                        <img src="<?php echo esc_url( $pic_url ); ?>" alt="Foto de perfil">
                    <?php else : ?>
                        <span><?php echo $initial; ?></span>
                    <?php endif; ?>
                </div>
                <label class="fmdb-perfil__avatar-btn" for="profile_picture">
                    Cambiar foto
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="fmdb-perfil__file-input">
                </label>
                <p class="fmdb-perfil__avatar-hint">JPG, PNG o GIF · Máx. 2 MB</p>
                <button type="submit" class="fmdb-btn fmdb-btn--outline fmdb-perfil__avatar-save">Guardar</button>
            </div>
        </form>

        <form class="fmdb-perfil__form" method="post" onsubmit="return confirm('Se enviará una solicitud de cambio de nombre a un administrador. ¿Está seguro que desea continuar?');">
            <?php wp_nonce_field( 'fmdb_perfil', 'fmdb_perfil_nonce' ); ?>

            <div class="fmdb-registro__field fmdb-perfil__username">
                <label for="fmdb_username">Nombre de usuario</label>
                <input type="text" id="fmdb_username" value="<?php echo esc_attr( $user->user_login ); ?>" readonly disabled>
            </div>

            <div class="fmdb-perfil__name-row">
                <div class="fmdb-registro__field">
                    <label for="first_name">Nombre</label>
                    <input type="text" id="first_name" name="first_name" placeholder="<?php echo esc_attr( $first_name ); ?>">
                </div>
                <div class="fmdb-registro__field">
                    <label for="last_name">Apellido</label>
                    <input type="text" id="last_name" name="last_name" placeholder="<?php echo esc_attr( $last_name ); ?>">
                </div>
                <button type="submit" class="fmdb-btn fmdb-btn--primary fmdb-perfil__name-save">Actualizar</button>
            </div>
        </form>

        <form class="fmdb-perfil__form fmdb-perfil__form--affiliation" method="post" onsubmit="return confirm('¿Solicitar verificación de tu ID de afiliación?');">
            <?php wp_nonce_field( 'fmdb_affiliation', 'fmdb_affiliation_nonce' ); ?>

            <div class="fmdb-registro__field fmdb-perfil__affiliation">
                <label for="fmdb_affiliation_id">
                    ID de afiliación
                    <span class="fmdb-perfil__affiliation-badge fmdb-perfil__affiliation-badge--<?php echo esc_attr( $affil_state ); ?>"><?php echo esc_html( $affil_label ); ?></span>
                </label>
                <div class="fmdb-perfil__affiliation-row">
                    <input type="text" id="fmdb_affiliation_id" name="fmdb_affiliation_id" value="<?php echo esc_attr( $affiliation_id ); ?>" <?php echo $affil_state === 'verified' ? 'readonly' : ''; ?>>
                    <?php if ( $affil_state !== 'verified' ) : ?>
                        <button type="submit" class="fmdb-btn fmdb-btn--outline fmdb-perfil__affiliation-btn">Verificar</button>
                    <?php endif; ?>
                </div>
                <?php if ( $affil_state === 'pending' ) : ?>
                    <p class="fmdb-perfil__affiliation-hint">Un administrador está revisando tu solicitud. Te avisaremos por correo cuando esté lista.</p>
                <?php elseif ( $affil_state === 'rejected' ) : ?>
                    <p class="fmdb-perfil__affiliation-hint">Tu ID anterior no fue aprobado. Corrígelo y vuelve a solicitar verificación.</p>
                <?php endif; ?>
            </div>
        </form>

    </div>
</main>

<script>
document.getElementById('profile_picture').addEventListener('change', function () {
    var file = this.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    var reader = new FileReader();
    reader.onload = function (e) {
        var preview = document.getElementById('fmdb-avatar-preview');
        preview.innerHTML = '<img src="' + e.target.result + '" alt="Vista previa">';
    };
    reader.readAsDataURL(file);
});
</script>

<?php get_footer(); ?>
