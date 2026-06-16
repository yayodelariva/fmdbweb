<?php
/**
 * Template Name: Ranking · Selección Juvenil
 *
 * Assign to a page nested under "Ranking" with slug "juvenil",
 * so it resolves at /ranking/juvenil/.
 */
get_header();

$data = fmdb_get_ranking_data();
$cat  = $data['juvenil'];

$years_used = array_keys( $cat['years'] );
$year_lo    = min( $years_used );
$year_hi    = max( $years_used );
?>
<main class="fmdb-ranking">
    <header class="fmdb-ranking__hero">
        <div class="fmdb-ranking__hero-inner">
            <p class="fmdb-ranking__eyebrow">Federación Mexicana de Dodgeball</p>
            <h1 class="fmdb-ranking__title">Ranking · Selección Juvenil</h1>
            <p class="fmdb-ranking__subtitle">Acumulado por estado con puntos ponderados por año.</p>
            <p class="fmdb-ranking__back">
                <a href="<?php echo esc_url( home_url( '/ranking/' ) ); ?>">← Volver al ranking</a>
            </p>
        </div>
    </header>

    <div class="fmdb-ranking__body">
        <section class="fmdb-ranking__cat is-active" role="tabpanel">
            <div class="fmdb-ranking__cat-meta">
                <span class="fmdb-ranking__cat-range">Temporada <?php echo (int) $year_lo; if ( $year_hi !== $year_lo ) echo '–' . (int) $year_hi; ?></span>
                <span class="fmdb-ranking__cat-mods">
                    <?php
                    $mods = [];
                    foreach ( $cat['years'] as $y => $f ) {
                        $mods[] = $y . ' · ' . number_format( $f, 1 );
                    }
                    echo esc_html( implode( '   ·   ', $mods ) );
                    ?>
                </span>
            </div>

            <?php foreach ( $cat['sections'] as $sec_key => $sec ) : ?>
                <div class="fmdb-ranking__section">
                    <h2 class="fmdb-ranking__h2"><?php echo esc_html( $cat['label'] ) . ' · ' . esc_html( $sec['label'] ); ?></h2>
                    <?php fmdb_render_podium( $sec['rows'] ); ?>
                </div>
            <?php endforeach; ?>
        </section>
    </div>
</main>

<?php
get_footer();
