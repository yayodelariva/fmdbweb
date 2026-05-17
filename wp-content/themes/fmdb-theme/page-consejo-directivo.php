<?php
/**
 * Template: Consejo Directivo
 * Slug-based page template — auto-applied to the page with slug "consejo-directivo"
 */
get_header();

while ( have_posts() ) : the_post();
    $members = function_exists( "get_field" ) ? get_field( "consejo_members" ) : [];
    if ( ! is_array( $members ) ) $members = [];
?>

<main class="fmdb-consejo">
    <div class="fmdb-consejo__wrap">

        <header class="fmdb-consejo__header">
            <h1 class="fmdb-consejo__title"><?php the_title(); ?></h1>
            <?php $content = trim( wp_strip_all_tags( get_the_content() ) ); ?>
            <?php if ( $content ) : ?>
                <div class="fmdb-consejo__intro"><?php the_content(); ?></div>
            <?php else : ?>
                <p class="fmdb-consejo__lede">Conoce a las personas que conforman el Consejo Directivo de la Federación Mexicana de Dodgeball.</p>
            <?php endif; ?>
        </header>

        <?php if ( ! empty( $members ) ) : ?>
            <div class="fmdb-consejo__grid">
                <?php foreach ( $members as $m ) :
                    $name      = $m["member_name"]     ?? "";
                    $position  = $m["member_position"] ?? "";
                    $bio       = $m["member_bio"]      ?? "";
                    $photo     = $m["member_photo"]    ?? null;
                    $photo_url = "";
                    $photo_alt = $name;
                    if ( is_array( $photo ) ) {
                        $photo_url = $photo["sizes"]["medium_large"] ?? ( $photo["sizes"]["medium"] ?? ( $photo["url"] ?? "" ) );
                        $photo_alt = $photo["alt"] ?: $name;
                    } elseif ( is_numeric( $photo ) ) {
                        $photo_url = wp_get_attachment_image_url( (int) $photo, "medium_large" );
                    }
                    if ( ! $name ) continue;
                ?>
                    <article class="fmdb-consejo-card">
                        <div class="fmdb-consejo-card__photo">
                            <?php if ( $photo_url ) : ?>
                                <img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( $photo_alt ); ?>" loading="lazy">
                            <?php else :
                                $words    = array_filter( explode( " ", trim( $name ) ) );
                                $initials = $words ? substr( implode( "", array_map( fn( $w ) => strtoupper( $w[0] ), $words ) ), 0, 2 ) : "?";
                            ?>
                                <div class="fmdb-consejo-card__initials"><?php echo esc_html( $initials ); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="fmdb-consejo-card__body">
                            <?php if ( $position ) : ?>
                                <span class="fmdb-consejo-card__position"><?php echo esc_html( $position ); ?></span>
                            <?php endif; ?>
                            <h3 class="fmdb-consejo-card__name"><?php echo esc_html( $name ); ?></h3>
                            <?php if ( $bio ) : ?>
                                <p class="fmdb-consejo-card__bio"><?php echo esc_html( $bio ); ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="fmdb-consejo__empty">
                <p>Los miembros del Consejo Directivo se publicarán próximamente.</p>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php endwhile; get_footer(); ?>
