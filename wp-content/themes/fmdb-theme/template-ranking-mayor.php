<?php
/**
 * Template Name: Ranking · Selección Mayor
 *
 * Assign to a page nested under "Ranking" with slug "mayor",
 * so it resolves at /ranking/mayor/.
 */
get_header();

$data = fmdb_get_ranking_data();

// Mayor only shows adult divisions — infantil/juvenil have their own sub-pages
unset( $data['infantil-menor'], $data['infantil-intermedia'], $data['infantil-mayor'], $data['juvenil'] );

// Default tab from ?cat=, else 'foam'
$active = isset( $_GET['cat'] ) && isset( $data[ sanitize_key( $_GET['cat'] ) ] )
    ? sanitize_key( $_GET['cat'] )
    : 'foam';
?>
<main class="fmdb-ranking">
    <header class="fmdb-ranking__hero">
        <div class="fmdb-ranking__hero-inner">
            <p class="fmdb-ranking__eyebrow">Federación Mexicana de Dodgeball</p>
            <h1 class="fmdb-ranking__title">Ranking · Selección Mayor</h1>
            <p class="fmdb-ranking__subtitle">Acumulado por estado con puntos ponderados por año.</p>
            <p class="fmdb-ranking__back">
                <a href="<?php echo esc_url( home_url( '/ranking/' ) ); ?>">← Volver al ranking</a>
            </p>
        </div>
    </header>

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

                <?php foreach ( $cat['sections'] as $sec_key => $sec ) : ?>
                    <div class="fmdb-ranking__section">
                        <h2 class="fmdb-ranking__h2"><?php echo esc_html( $cat['label'] ) . ' · ' . esc_html( $sec['label'] ); ?></h2>
                        <?php fmdb_render_podium( $sec['rows'] ); ?>
                    </div>
                <?php endforeach; ?>

                <div class="fmdb-ranking__more">
                    <a class="fmdb-btn fmdb-btn--primary" href="<?php echo esc_url( home_url( '/ranking/tablas/?cat=' . $cat_key ) ); ?>">Ver tablas completas</a>
                </div>
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
