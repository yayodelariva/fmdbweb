<?php
/**
 * Template: Noticia (single post)
 */
get_header();

while ( have_posts() ) :
    the_post();

    $post_id  = get_the_ID();
    $cats     = get_the_category();
    $cat      = ! empty( $cats ) ? $cats[0] : null;
    $cat_ids  = wp_list_pluck( $cats, 'term_id' );
    $thumb    = get_the_post_thumbnail_url( $post_id, 'full' );
    $date     = get_the_date( 'j F Y' );
    $author   = get_the_author();
    $url      = get_permalink();
    $title    = get_the_title();

    $noticias_page = get_page_by_path( 'noticias' );
    $noticias_url  = $noticias_page ? get_permalink( $noticias_page->ID ) : home_url( '/noticias/' );

    $share_x  = 'https://x.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title );
    $share_fb = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url );
    $share_wa = 'https://api.whatsapp.com/send?text=' . rawurlencode( $title . ' ' . $url );

    $sidebar_posts = new WP_Query( [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 6,
        'post__not_in'   => [ $post_id ],
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );

    $related_posts = new WP_Query( [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 3,
        'post__not_in'   => [ $post_id ],
        'category__in'   => $cat_ids ?: [ 0 ],
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );
?>

<main class="fmdb-article">

    <a href="<?php echo esc_url( $noticias_url ); ?>" class="fmdb-article__back">← Noticias</a>

    <div class="fmdb-article__hero">
        <?php if ( $thumb ) : ?>
            <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>">
        <?php else : ?>
            <div class="fmdb-article__hero--empty"></div>
        <?php endif; ?>
    </div>

    <div class="fmdb-article__layout">

        <div class="fmdb-article__main">

            <header class="fmdb-article__header">
                <?php if ( $cat ) : ?>
                    <span class="fmdb-cat-badge"><?php echo esc_html( $cat->name ); ?></span>
                <?php endif; ?>
                <h1 class="fmdb-article__title"><?php the_title(); ?></h1>
                <div class="fmdb-article__meta">
                    <span><?php echo esc_html( $date ); ?></span>
                    <span>Por <?php echo esc_html( $author ); ?></span>
                </div>
            </header>

            <div class="fmdb-article__body">
                <?php the_content(); ?>
            </div>

            <div class="fmdb-article__share">
                <span class="fmdb-article__share-label">Compartir:</span>

                <a href="<?php echo esc_url( $share_x ); ?>" target="_blank" rel="noopener noreferrer"
                   class="fmdb-share-btn" aria-label="Compartir en X">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.254 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    X
                </a>

                <a href="<?php echo esc_url( $share_fb ); ?>" target="_blank" rel="noopener noreferrer"
                   class="fmdb-share-btn" aria-label="Compartir en Facebook">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Facebook
                </a>

                <a href="<?php echo esc_url( $share_wa ); ?>" target="_blank" rel="noopener noreferrer"
                   class="fmdb-share-btn" aria-label="Compartir en WhatsApp">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp
                </a>

                <button class="fmdb-share-btn" id="fmdb-copy-link"
                        data-url="<?php echo esc_attr( $url ); ?>" type="button">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <span>Copiar enlace</span>
                </button>
            </div>

            <?php if ( $related_posts->have_posts() ) : ?>
            <section class="fmdb-article__related">
                <h2 class="fmdb-article__related-title">Artículos relacionados</h2>
                <div class="fmdb-article__related-grid">
                    <?php while ( $related_posts->have_posts() ) : $related_posts->the_post();
                        $r_thumb = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
                        $r_cats  = get_the_category();
                        $r_cat   = ! empty( $r_cats ) ? $r_cats[0] : null;
                    ?>
                        <a href="<?php the_permalink(); ?>" class="fmdb-noticia-card">
                            <div class="fmdb-noticia-card__img">
                                <?php if ( $r_thumb ) : ?>
                                    <img src="<?php echo esc_url( $r_thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
                                <?php else : ?>
                                    <div class="fmdb-noticias__img-placeholder"></div>
                                <?php endif; ?>
                            </div>
                            <div class="fmdb-noticia-card__body">
                                <?php if ( $r_cat ) : ?>
                                    <span class="fmdb-cat-badge fmdb-cat-badge--sm"><?php echo esc_html( $r_cat->name ); ?></span>
                                <?php endif; ?>
                                <h3 class="fmdb-noticia-card__title"><?php the_title(); ?></h3>
                                <span class="fmdb-noticia-card__date"><?php echo get_the_date( 'j F Y' ); ?></span>
                            </div>
                        </a>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </section>
            <?php endif; ?>

        </div><!-- /.fmdb-article__main -->

        <aside class="fmdb-article__sidebar">
            <h3 class="fmdb-article__sidebar-title">Más artículos</h3>
            <?php if ( $sidebar_posts->have_posts() ) : ?>
                <div class="fmdb-sidebar-posts">
                    <?php while ( $sidebar_posts->have_posts() ) : $sidebar_posts->the_post();
                        $s_thumb = get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' );
                        $s_cats  = get_the_category();
                        $s_cat   = ! empty( $s_cats ) ? $s_cats[0] : null;
                    ?>
                        <a href="<?php the_permalink(); ?>" class="fmdb-sidebar-post">
                            <div class="fmdb-sidebar-post__img">
                                <?php if ( $s_thumb ) : ?>
                                    <img src="<?php echo esc_url( $s_thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
                                <?php else : ?>
                                    <div class="fmdb-noticias__img-placeholder"></div>
                                <?php endif; ?>
                            </div>
                            <div class="fmdb-sidebar-post__body">
                                <?php if ( $s_cat ) : ?>
                                    <span class="fmdb-cat-badge fmdb-cat-badge--sm"><?php echo esc_html( $s_cat->name ); ?></span>
                                <?php endif; ?>
                                <h4 class="fmdb-sidebar-post__title"><?php the_title(); ?></h4>
                                <span class="fmdb-sidebar-post__date"><?php echo get_the_date( 'j F Y' ); ?></span>
                            </div>
                        </a>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            <?php endif; ?>
        </aside>

    </div><!-- /.fmdb-article__layout -->

</main>

<script>
(function () {
    'use strict';
    var btn   = document.getElementById('fmdb-copy-link');
    if (!btn) return;
    var label = btn.querySelector('span');
    btn.addEventListener('click', function () {
        navigator.clipboard.writeText(btn.dataset.url).then(function () {
            label.textContent = '✓ Enlace copiado';
            btn.classList.add('fmdb-share-btn--copied');
            setTimeout(function () {
                label.textContent = 'Copiar enlace';
                btn.classList.remove('fmdb-share-btn--copied');
            }, 2000);
        });
    });
})();
</script>

<?php endwhile; ?>
<?php get_footer(); ?>
