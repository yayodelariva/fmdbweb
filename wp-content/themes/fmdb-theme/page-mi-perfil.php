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

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fmdb_perfil_nonce'] ) ) {
    if ( ! wp_verify_nonce( $_POST['fmdb_perfil_nonce'], 'fmdb_perfil' ) ) {
        $notices[] = [ 'type' => 'error', 'msg' => 'Solicitud inválida. Intenta de nuevo.' ];
    } else {
        $first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
        $last_name  = sanitize_text_field( $_POST['last_name']  ?? '' );

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

        if ( ! empty( $_FILES['profile_picture']['tmp_name'] ) ) {
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
}

$first_name = get_user_meta( $user_id, 'first_name', true );
$last_name  = get_user_meta( $user_id, 'last_name',  true );
$pic_id     = get_user_meta( $user_id, 'fmdb_profile_picture', true );
$pic_url    = $pic_id ? wp_get_attachment_image_url( (int) $pic_id, 'medium' ) : '';
$initial    = esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) );

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

        <form class="fmdb-perfil__form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'fmdb_perfil', 'fmdb_perfil_nonce' ); ?>

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
            </div>

            <div class="fmdb-registro__row">
                <div class="fmdb-registro__field">
                    <label for="first_name">Nombre</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( $first_name ); ?>" required>
                </div>
                <div class="fmdb-registro__field">
                    <label for="last_name">Apellido</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( $last_name ); ?>" required>
                </div>
            </div>

            <button type="submit" class="fmdb-btn fmdb-btn--primary fmdb-registro__btn">Guardar cambios</button>
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
