<?php
/**
 * Template: Ranking
 * Slug-bound — used by the page with slug "ranking".
 */
get_header();

$data   = fmdb_get_ranking_data();
$points = fmdb_get_ranking_points_table();

// Default tab from ?cat=, else 'foam'
$active = isset( $_GET['cat'] ) && isset( $data[ sanitize_key( $_GET['cat'] ) ] )
    ? sanitize_key( $_GET['cat'] )
    : 'foam';
?>
<main class="fmdb-ranking">
    <header class="fmdb-ranking__hero">
        <div class="fmdb-ranking__hero-inner">
            <p class="fmdb-ranking__eyebrow">Federación Mexicana de Dodgeball</p>
            <h1 class="fmdb-ranking__title">Ranking Nacional</h1>
            <p class="fmdb-ranking__subtitle">Acumulado por estado con puntos ponderados por año.</p>
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
                        <?php fmdb_render_podium( $sec['rows'] ); ?>
                        <?php fmdb_render_section_table( $sec['rows'] ); ?>
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
            // Shallow URL update so deep-links survive page reloads
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

/* ---------- render helpers (file-scoped) ---------- */

function fmdb_render_podium( $rows ) {
    $top = array_slice( array_values( array_filter( $rows, function ( $r ) {
        return ! empty( $r['total'] );
    } ) ), 0, 3 );
    if ( empty( $top ) ) {
        echo '<p class="fmdb-ranking__empty">Sin resultados registrados.</p>';
        return;
    }
    $medals = [ '🥇', '🥈', '🥉' ];
    echo '<div class="fmdb-ranking__podium">';
    foreach ( $top as $i => $r ) {
        echo '<div class="fmdb-ranking__podium-card fmdb-ranking__podium-card--' . ( $i + 1 ) . '">';
        echo   '<span class="fmdb-ranking__podium-medal" aria-hidden="true">' . esc_html( $medals[ $i ] ) . '</span>';
        echo   '<span class="fmdb-ranking__podium-state">' . esc_html( $r['state'] ) . '</span>';
        echo   '<span class="fmdb-ranking__podium-pts">' . (int) $r['total'] . ' <small>pts</small></span>';
        echo '</div>';
    }
    echo '</div>';
}

function fmdb_render_section_table( $rows ) {
    echo '<div class="fmdb-ranking__table-wrap">';
    echo '<table class="fmdb-ranking__table">';
    echo   '<thead><tr><th>Pos.</th><th>Estado</th><th class="num">Puntos</th></tr></thead><tbody>';
    foreach ( $rows as $i => $r ) {
        $is_zero = empty( $r['total'] );
        echo '<tr class="' . ( $is_zero ? 'is-zero' : '' ) . ( $i < 3 && ! $is_zero ? ' is-top' : '' ) . '">';
        echo   '<td><span class="fmdb-ranking__pos">' . ( $i + 1 ) . '</span></td>';
        echo   '<td>' . esc_html( $r['state'] ) . '</td>';
        echo   '<td class="num">' . (int) $r['total'] . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function fmdb_render_combined_table( $rows ) {
    echo '<div class="fmdb-ranking__table-wrap">';
    echo '<table class="fmdb-ranking__table fmdb-ranking__table--combined">';
    echo   '<thead><tr><th>Pos.</th><th>Estado</th><th class="num">Varonil</th><th class="num">Femenil</th><th class="num">Mixto</th><th class="num">Total</th></tr></thead><tbody>';
    foreach ( $rows as $i => $r ) {
        echo '<tr class="' . ( $i < 3 ? 'is-top' : '' ) . '">';
        echo   '<td><span class="fmdb-ranking__pos">' . ( $i + 1 ) . '</span></td>';
        echo   '<td>' . esc_html( $r['state'] ) . '</td>';
        echo   '<td class="num">' . (int) $r['v'] . '</td>';
        echo   '<td class="num">' . (int) $r['f'] . '</td>';
        echo   '<td class="num">' . (int) $r['m'] . '</td>';
        echo   '<td class="num"><strong>' . (int) $r['total'] . '</strong></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
