<?php
/**
 * Template: Selecciones Nacionales — Hub
 * Automatically used by WordPress for the page with slug "selecciones"
 */
get_header();

$foam_page  = get_page_by_path( 'selecciones/foam' );
$cloth_page = get_page_by_path( 'selecciones/cloth' );
$foam_url   = $foam_page  ? get_permalink( $foam_page->ID )  : '#';
$cloth_url  = $cloth_page ? get_permalink( $cloth_page->ID ) : '#';

// Selecciones are announced on these dates — until each one, render the
// matching card as a non-clickable teaser with no CTA.
$today           = current_time( 'Y-m-d' );
$foam_revealed   = $today >= '2026-06-26';
$cloth_revealed  = $today >= '2026-07-31';

$ball_icons = [
    'foam'  => '<svg width="56" height="56" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="9.5" fill="currentColor" fill-opacity="0.18" stroke="currentColor" stroke-width="1.5"/><circle cx="8" cy="8.5" r="2.8" fill="currentColor" fill-opacity="0.32"/><circle cx="14" cy="13.5" r="2" fill="currentColor" fill-opacity="0.22"/></svg>',
    'cloth' => '<svg width="56" height="56" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="9.5" stroke="currentColor" stroke-width="1.5"/><path d="M1.5 11 Q11 4 20.5 11" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><path d="M1.5 11 Q11 18 20.5 11" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>',
];
?>

<main class="fmdb-selecciones">

    <div class="fmdb-selecciones__header">
        <h1>Selecciones Nacionales</h1>
        <p>Los mejores jugadores y jugadoras representando a México en el dodgeball de alto rendimiento.</p>
    </div>

    <div class="fmdb-selecciones__hub">
        <?php if ( $foam_revealed ) : ?>
            <a href="<?php echo esc_url( $foam_url ); ?>" class="fmdb-seleccion-hub-card">
                <div class="fmdb-seleccion-hub-card__icon"><?php echo $ball_icons['foam']; ?></div>
                <h2 class="fmdb-seleccion-hub-card__title">Foam</h2>
                <p class="fmdb-seleccion-hub-card__desc">Selecciones nacionales de dodgeball Foam</p>
                <span class="fmdb-seleccion-hub-card__cta">Ver selecciones →</span>
            </a>
        <?php else : ?>
            <div class="fmdb-seleccion-hub-card fmdb-seleccion-hub-card--teaser" aria-disabled="true">
                <div class="fmdb-seleccion-hub-card__icon"><?php echo $ball_icons['foam']; ?></div>
                <h2 class="fmdb-seleccion-hub-card__title">Foam</h2>
                <p class="fmdb-seleccion-hub-card__desc">Anuncio oficial el 26 de Junio</p>
            </div>
        <?php endif; ?>

        <?php if ( $cloth_revealed ) : ?>
            <a href="<?php echo esc_url( $cloth_url ); ?>" class="fmdb-seleccion-hub-card">
                <div class="fmdb-seleccion-hub-card__icon"><?php echo $ball_icons['cloth']; ?></div>
                <h2 class="fmdb-seleccion-hub-card__title">Cloth</h2>
                <p class="fmdb-seleccion-hub-card__desc">Selecciones nacionales de dodgeball Cloth</p>
                <span class="fmdb-seleccion-hub-card__cta">Ver selecciones →</span>
            </a>
        <?php else : ?>
            <div class="fmdb-seleccion-hub-card fmdb-seleccion-hub-card--teaser" aria-disabled="true">
                <div class="fmdb-seleccion-hub-card__icon"><?php echo $ball_icons['cloth']; ?></div>
                <h2 class="fmdb-seleccion-hub-card__title">Cloth</h2>
                <p class="fmdb-seleccion-hub-card__desc">Anuncio oficial el 31 de Julio</p>
            </div>
        <?php endif; ?>
    </div>

</main>

<?php get_footer(); ?>
