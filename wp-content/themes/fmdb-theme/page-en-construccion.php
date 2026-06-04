<?php
/**
 * Template Name: En Construcción
 * Reusable placeholder for pages that aren't built yet. Assign it via
 * Page Attributes → Template in the page editor.
 */
get_header();
?>
<main id="primary" class="site-main fmdb-wip">
    <section class="fmdb-wip__inner">
        <div class="fmdb-wip__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2 2 7l10 5 10-5-10-5Z"/>
                <path d="m2 17 10 5 10-5"/>
                <path d="m2 12 10 5 10-5"/>
            </svg>
        </div>
        <p class="fmdb-wip__eyebrow">Federación Mexicana de Dodgeball</p>
        <h1 class="fmdb-wip__title">En construcción</h1>
        <p class="fmdb-wip__subtitle">Estamos trabajando en esta sección. Vuelve pronto.</p>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="fmdb-btn fmdb-btn--outline">Volver al inicio</a>
    </section>
</main>
<style>
    .fmdb-wip {
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #08302a;
        padding: 64px 24px;
    }
    .fmdb-wip__inner {
        text-align: center;
        color: #fff;
        max-width: 520px;
    }
    .fmdb-wip__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 96px;
        height: 96px;
        border-radius: 50%;
        background: rgba(255,255,255,0.06);
        color: #CEF9D7;
        margin-bottom: 24px;
    }
    .fmdb-wip__eyebrow {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #CEF9D7;
        margin: 0 0 12px;
    }
    .fmdb-wip__title {
        margin: 0 0 12px;
        font-size: clamp(2rem, 5vw, 3rem);
        color: #fff;
        line-height: 1.15;
    }
    .fmdb-wip__subtitle {
        margin: 0 0 32px;
        color: rgba(225, 245, 238, 0.85);
        line-height: 1.6;
        font-size: 1.05rem;
    }
</style>
<?php
get_footer();
