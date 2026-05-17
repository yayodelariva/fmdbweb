<?php
/**
 * Template: Noticias
 * Slug: noticias
 */

$active_slug = isset( $_GET['categoria'] ) ? sanitize_title_with_dashes( wp_strip_all_tags( $_GET['categoria'] ) ) : '';
$cat_obj     = $active_slug ? get_category_by_slug( $active_slug ) : null;
$cat_id      = $cat_obj ? (int) $cat_obj->term_id : 0;

$query_args = [
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => -1,
    'orderby'             => 'date',
    'order'               => 'DESC',
    'ignore_sticky_posts' => 1,
];
if ( $cat_id ) {
    $query_args['cat'] = $cat_id;
}

$news_query = new WP_Query( $query_args );
$all_posts  = $news_query->posts;
$featured   = ! empty( $all_posts ) ? array_shift( $all_posts ) : null;
$grid_posts = $all_posts;

$categories = get_categories( [ 'hide_empty' => true ] );
$page_url   = get_permalink();

get_header();
?>

<main class="fmdb-noticias">

    <div class="fmdb-noticias__header">
        <h1>Noticias</h1>
        <p>Mantente al día con todo lo que pasa en el dodgeball mexicano.</p>
    </div>

    <?php if ( ! empty( $categories ) ) : ?>
    <nav class="fmdb-noticias__filters" aria-label="Filtrar por categoría">
        <a href="<?php echo esc_url( $page_url ); ?>"
           class="fmdb-noticias__pill<?php echo ! $active_slug ? ' active' : ''; ?>">
            Todas
        </a>
        <?php foreach ( $categories as $cat ) : ?>
        <a href="<?php echo esc_url( add_query_arg( 'categoria', $cat->slug, $page_url ) ); ?>"
           class="fmdb-noticias__pill<?php echo $active_slug === $cat->slug ? ' active' : ''; ?>">
            <?php echo esc_html( $cat->name ); ?>
            <span class="fmdb-noticias__pill-count"><?php echo (int) $cat->count; ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <?php if ( $featured ) :
        $f_cats  = get_the_category( $featured->ID );
        $f_cat   = ! empty( $f_cats ) ? $f_cats[0] : null;
        $f_thumb = get_the_post_thumbnail_url( $featured->ID, 'large' );
        $f_date  = get_the_date( 'j F Y', $featured->ID );
        $f_url   = get_permalink( $featured->ID );
        $f_exc   = get_the_excerpt( $featured );
    ?>
    <a href="<?php echo esc_url( $f_url ); ?>" class="fmdb-noticias__featured">
        <div class="fmdb-noticias__featured-img">
            <?php if ( $f_thumb ) : ?>
                <img src="<?php echo esc_url( $f_thumb ); ?>" alt="<?php echo esc_attr( $featured->post_title ); ?>">
            <?php else : ?>
                <div class="fmdb-noticias__img-placeholder"></div>
            <?php endif; ?>
        </div>
        <div class="fmdb-noticias__featured-body">
            <?php if ( $f_cat ) : ?>
                <span class="fmdb-cat-badge"><?php echo esc_html( $f_cat->name ); ?></span>
            <?php endif; ?>
            <h2 class="fmdb-noticias__featured-title"><?php echo esc_html( $featured->post_title ); ?></h2>
            <?php if ( $f_exc ) : ?>
                <p class="fmdb-noticias__featured-exc"><?php echo esc_html( $f_exc ); ?></p>
            <?php endif; ?>
            <div class="fmdb-noticias__featured-footer">
                <span class="fmdb-noticias__featured-date"><?php echo esc_html( $f_date ); ?></span>
                <span class="fmdb-noticias__featured-cta">Leer nota →</span>
            </div>
        </div>
    </a>
    <?php endif; ?>

    <?php if ( ! empty( $grid_posts ) ) : ?>
    <div class="fmdb-noticias__grid">
        <?php foreach ( $grid_posts as $post ) :
            $cats  = get_the_category( $post->ID );
            $cat   = ! empty( $cats ) ? $cats[0] : null;
            $thumb = get_the_post_thumbnail_url( $post->ID, 'medium_large' );
            $date  = get_the_date( 'j F Y', $post->ID );
            $url   = get_permalink( $post->ID );
        ?>
        <a href="<?php echo esc_url( $url ); ?>" class="fmdb-noticia-card">
            <div class="fmdb-noticia-card__img">
                <?php if ( $thumb ) : ?>
                    <img src="<?php echo esc_url( $thumb ); ?>"
                         alt="<?php echo esc_attr( $post->post_title ); ?>"
                         loading="lazy">
                <?php else : ?>
                    <div class="fmdb-noticias__img-placeholder"></div>
                <?php endif; ?>
            </div>
            <div class="fmdb-noticia-card__body">
                <?php if ( $cat ) : ?>
                    <span class="fmdb-cat-badge fmdb-cat-badge--sm"><?php echo esc_html( $cat->name ); ?></span>
                <?php endif; ?>
                <h3 class="fmdb-noticia-card__title"><?php echo esc_html( $post->post_title ); ?></h3>
                <span class="fmdb-noticia-card__date"><?php echo esc_html( $date ); ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ( ! $featured ) : ?>
        <p class="fmdb-empty">No hay noticias publicadas aún<?php echo $active_slug ? ' en esta categoría' : ''; ?>.</p>
    <?php endif; ?>

</main>

<?php
wp_reset_postdata();
get_footer();
?>
