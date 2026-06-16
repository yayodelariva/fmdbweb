<?php
/**
 * Template: Ranking
 * Slug-bound — used by the page with slug "ranking".
 */
get_header();
?>
<main class="fmdb-ranking">
    <header class="fmdb-ranking__hero">
        <div class="fmdb-ranking__hero-inner">
            <p class="fmdb-ranking__eyebrow">Federación Mexicana de Dodgeball</p>
            <h1 class="fmdb-ranking__title">Ranking Nacional</h1>
            <p class="fmdb-ranking__subtitle">Acumulado por estado con puntos ponderados por año.</p>
        </div>
    </header>

    <section class="fmdb-ranking__selecciones">
        <div class="fmdb-ranking__selecciones-inner">
            <?php
            $selecciones = [
                [ 'titulo' => 'Selección Mayor',    'url' => home_url( '/ranking/mayor/' ) ],
                [ 'titulo' => 'Selección Juvenil',  'url' => home_url( '/ranking/juvenil/' ) ],
                [ 'titulo' => 'Selección Infantil', 'url' => home_url( '/ranking/infantil/' ) ],
            ];
            foreach ( $selecciones as $s ) : ?>
                <a class="fmdb-ranking__sel-card" href="<?php echo esc_url( $s['url'] ); ?>">
                    <span class="fmdb-ranking__sel-title"><?php echo esc_html( $s['titulo'] ); ?></span>
                    <span class="fmdb-ranking__sel-arrow" aria-hidden="true">→</span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php
get_footer();
