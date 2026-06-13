<?php
/**
 * Template: Reglamentos
 * Used for the page with slug "reglamentos".
 *
 * Regulations downloads. PDFs live in Media Library — replace the
 * URLs below once they're uploaded.
 */

get_header();

// TODO: replace these with the actual uploaded media URLs once available.
// Easiest way: upload via wp-admin → Media → Add New, copy the file URL,
// paste into the corresponding $url field below.
$reglamentos = [
    [
        'tipo'        => 'foam',
        'titulo'      => 'Reglamento Foam',
        'descripcion' => 'Reglas oficiales para la modalidad Foam de dodgeball reconocidas por la FMDB.',
        'url'         => '#', // TODO: replace with PDF URL
    ],
    [
        'tipo'        => 'cloth',
        'titulo'      => 'Reglamento Cloth',
        'descripcion' => 'Reglas oficiales para la modalidad Cloth de dodgeball reconocidas por la FMDB.',
        'url'         => '#', // TODO: replace with PDF URL
    ],
];
?>

<main class="fmdb-reglamentos">
    <div class="fmdb-reglamentos__wrap">

        <header class="fmdb-reglamentos__header">
            <h1>Reglamentos</h1>
            <p>Descarga los reglamentos oficiales reconocidos por la Federación Mexicana de Dodgeball.</p>
        </header>

        <div class="fmdb-reglamentos__grid">
            <?php foreach ( $reglamentos as $r ) :
                $disabled = $r['url'] === '#';
            ?>
                <article class="fmdb-reglamentos__card fmdb-reglamentos__card--<?php echo esc_attr( $r['tipo'] ); ?>">
                    <div class="fmdb-reglamentos__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="9" y1="13" x2="15" y2="13"/>
                            <line x1="9" y1="17" x2="15" y2="17"/>
                        </svg>
                    </div>
                    <h2 class="fmdb-reglamentos__title"><?php echo esc_html( $r['titulo'] ); ?></h2>
                    <p class="fmdb-reglamentos__desc"><?php echo esc_html( $r['descripcion'] ); ?></p>
                    <?php if ( $disabled ) : ?>
                        <span class="fmdb-reglamentos__btn fmdb-reglamentos__btn--disabled">Próximamente</span>
                    <?php else : ?>
                        <a class="fmdb-reglamentos__btn" href="<?php echo esc_url( $r['url'] ); ?>" download>
                            Descargar PDF
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

    </div>
</main>

<?php get_footer(); ?>
