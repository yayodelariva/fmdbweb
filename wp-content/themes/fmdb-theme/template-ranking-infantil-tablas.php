<?php
/**
 * Template Name: Ranking · Infantil · Tablas
 *
 * Assign to a page nested under "Ranking → Selección Infantil"
 * with slug "tablas", so it resolves at /ranking/infantil/tablas/.
 */
get_header();

$data   = fmdb_get_ranking_data();
$points = fmdb_get_ranking_points_table();

$infantil_keys = [ 'infantil-menor', 'infantil-intermedia', 'infantil-mayor' ];
$data = array_intersect_key( $data, array_flip( $infantil_keys ) );

$active = isset( $_GET['cat'] ) && isset( $data[ $_GET['cat'] ] )
    ? $_GET['cat']
    : 'infantil-menor';
?>
<main class="fmdb-ranking">
    <header class="fmdb-ranking__hero">
        <div class="fmdb-ranking__hero-inner">
            <p class="fmdb-ranking__eyebrow">Federación Mexicana de Dodgeball</p>
            <h1 class="fmdb-ranking__title">Tablas · Selección Infantil</h1>
            <p class="fmdb-ranking__subtitle">Resultados completos por estado, división y año.</p>
            <p class="fmdb-ranking__back">
                <a href="<?php echo esc_url( home_url( '/ranking/infantil/' ) ); ?>">← Volver a Infantil</a>
            </p>
        </div>
    </header>

    <section class="fmdb-ranking__system">
        <div class="fmdb-ranking__system-inner">
            <h2>Sistema de puntos</h2>
            <p class="fmdb-ranking__system-lead">
                Cada torneo nacional otorga puntos por lugar; el total de cada año
                se multiplica por un factor de antigüedad (el año más reciente
                vale 1.0; los anteriores se devalúan 0.1 por año).
            </p>
            <div class="fmdb-ranking__points">
                <?php foreach ( $points as $pos => $pts ) : ?>
                    <div class="fmdb-ranking__point">
                        <span class="fmdb-ranking__point-pos"><?php echo (int) $pos; ?>°</span>
                        <span class="fmdb-ranking__point-val"><?php echo (int) $pts; ?></span>
                        <span class="fmdb-ranking__point-label">pts</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="fmdb-ranking__body">
        <nav class="fmdb-ranking__tabs" role="tablist">
            <?php foreach ( $data as $cat_key => $cat ) : ?>
                <button type="button"
                        class="fmdb-ranking__tab<?php echo $cat_key === $active ? ' is-active' : ''; ?>"
                        data-cat="<?php echo esc_attr( $cat_key ); ?>"
                        role="tab"
                        aria-selected="<?php echo $cat_key === $active ? 'true' : 'false'; ?>">
                    <?php echo esc_html( $cat['label'] ); ?>
                </button>
            <?php endforeach; ?>
        </nav>

        <?php foreach ( $data as $cat_key => $cat ) : ?>
            <section class="fmdb-ranking__cat<?php echo $cat_key === $active ? ' is-active' : ''; ?>"
                     data-cat="<?php echo esc_attr( $cat_key ); ?>"
                     role="tabpanel">

                <?php
                $years_used = array_keys( $cat['years'] );
                $year_lo    = min( $years_used );
                $year_hi    = max( $years_used );
                ?>
                <div class="fmdb-ranking__cat-meta">
                    <span class="fmdb-ranking__cat-range">Temporadas <?php echo (int) $year_lo; ?>–<?php echo (int) $year_hi; ?></span>
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

                <?php if ( ! empty( $cat['totals'] ) ) : ?>
                    <div class="fmdb-ranking__combined">
                        <h2 class="fmdb-ranking__h2">Total combinado</h2>
                        <?php fmdb_render_combined_table( $cat['totals'] ); ?>
                    </div>
                <?php endif; ?>

                <?php foreach ( $cat['sections'] as $sec_key => $sec ) : ?>
                    <div class="fmdb-ranking__section">
                        <h2 class="fmdb-ranking__h2"><?php echo esc_html( $cat['label'] ) . ' · ' . esc_html( $sec['label'] ); ?></h2>
                        <?php fmdb_render_section_table( $sec['rows_tiered'] ?? $sec['rows'] ); ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>
</main>

<script>
(function () {
    var root = document.querySelector('.fmdb-ranking');
    if (!root) return;
    root.querySelectorAll('.fmdb-ranking__tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var cat = btn.getAttribute('data-cat');
            root.querySelectorAll('.fmdb-ranking__tab').forEach(function (b) {
                var active = b.getAttribute('data-cat') === cat;
                b.classList.toggle('is-active', active);
                b.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            root.querySelectorAll('.fmdb-ranking__cat').forEach(function (panel) {
                panel.classList.toggle('is-active', panel.getAttribute('data-cat') === cat);
            });
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('cat', cat);
                history.replaceState({}, '', url.toString());
            } catch (e) {}
        });
    });
})();
</script>

<?php
get_footer();
