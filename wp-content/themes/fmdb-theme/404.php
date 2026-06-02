<?php
/**
 * Template: 404 — página no encontrada
 */
get_header();

$home_url    = home_url( '/' );
$eventos_url = home_url( '/eventos/' );
$ranking_url = home_url( '/ranking/' );
$equipos_url = home_url( '/mapa-interactivo/' );
$tienda_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
?>

<main class="fmdb-404">
    <div class="fmdb-404__wrap">
        <h1 class="fmdb-404__title">No pudimos encontrar la página</h1>
        <p class="fmdb-404__lede">
            La dirección que buscas no existe o ya no está disponible.
            Mientras tanto, te dejamos algunos lugares por donde seguir.
        </p>

        <div class="fmdb-404__actions">
            <a class="fmdb-btn fmdb-btn--primary" href="<?php echo esc_url( $home_url ); ?>">Volver al inicio</a>
        </div>

        <ul class="fmdb-404__links">
            <li><a href="<?php echo esc_url( $eventos_url ); ?>">Eventos</a></li>
            <li><a href="<?php echo esc_url( $ranking_url ); ?>">Ranking</a></li>
            <li><a href="<?php echo esc_url( $equipos_url ); ?>">Equipos y ligas</a></li>
            <li><a href="<?php echo esc_url( $tienda_url ); ?>">Tienda</a></li>
        </ul>

        <form role="search" method="get" class="fmdb-404__search" action="<?php echo esc_url( $home_url ); ?>">
            <label for="fmdb-404-s" class="screen-reader-text">Buscar en el sitio</label>
            <input type="search" id="fmdb-404-s" name="s" placeholder="Buscar en el sitio…" value="<?php echo esc_attr( get_search_query() ); ?>">
            <button type="submit" class="fmdb-btn fmdb-btn--primary">Buscar</button>
        </form>
    </div>
</main>

<?php
get_footer();
