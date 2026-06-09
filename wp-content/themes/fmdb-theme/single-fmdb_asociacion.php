<?php
/**
 * Template: Ficha de asociación (single fmdb_asociacion)
 */
get_header();

while ( have_posts() ) :
    the_post();

    $asoc_id     = get_the_ID();
    $state       = get_field( 'asociacion_state' );
    $description = get_field( 'asociacion_description' );
    $founded     = get_field( 'asociacion_founded' );
    $email       = get_field( 'asociacion_email' );
    $website     = get_field( 'asociacion_website' );
    $instagram   = get_field( 'asociacion_instagram' );
    $facebook    = get_field( 'asociacion_facebook' );

    $initials = implode( '', array_map( fn( $w ) => strtoupper( $w[0] ), explode( ' ', get_the_title() ) ) );
    $initials = substr( $initials, 0, 3 );

    // Ligas + equipos del mismo estado (referencia rápida)
    $leagues = $state ? get_posts( [
        'post_type'      => 'fmdb_league',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => [ [ 'key' => 'league_state', 'value' => $state ] ],
    ] ) : [];

    $teams = $state ? get_posts( [
        'post_type'      => 'fmdb_team',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => [ [ 'key' => 'team_state', 'value' => $state ] ],
    ] ) : [];
?>

<main id="fmdb-league-single" class="fmdb-league-single">

    <section class="fmdb-league-hero">
        <div class="fmdb-league-hero__inner">
            <div class="fmdb-team-crest">
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'medium' ); ?>
                <?php else : ?>
                    <span class="fmdb-team-initials"><?php echo esc_html( $initials ); ?></span>
                <?php endif; ?>
            </div>
            <div class="fmdb-league-hero__info">
                <p class="fmdb-league-hero__label">Asociación estatal</p>
                <h1><?php the_title(); ?></h1>
                <p class="fmdb-league-hero__meta">
                    <?php if ( $state )   : ?><span><?php echo esc_html( $state ); ?></span><?php endif; ?>
                    <?php if ( $founded ) : ?><span>Desde <?php echo esc_html( $founded ); ?></span><?php endif; ?>
                    <?php if ( $leagues ) : ?><span><?php echo count( $leagues ); ?> liga<?php echo count( $leagues ) !== 1 ? 's' : ''; ?></span><?php endif; ?>
                    <?php if ( $teams )   : ?><span><?php echo count( $teams );   ?> equipo<?php echo count( $teams )   !== 1 ? 's' : ''; ?></span><?php endif; ?>
                </p>
            </div>
        </div>
    </section>

    <section class="fmdb-league-body">
        <div class="fmdb-league-body__inner">

            <?php if ( $description ) : ?>
                <div class="fmdb-league-about">
                    <h2>Acerca de la asociación</h2>
                    <p><?php echo nl2br( esc_html( $description ) ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( $leagues ) : ?>
                <div class="fmdb-league-teams">
                    <h2>Ligas en <?php echo esc_html( $state ); ?></h2>
                    <div class="fmdb-team-grid">
                        <?php foreach ( $leagues as $liga ) :
                            $thumb = get_the_post_thumbnail_url( $liga->ID, 'thumbnail' );
                            $words = array_filter( explode( ' ', $liga->post_title ) );
                            $linit = substr( implode( '', array_map( fn( $w ) => strtoupper( $w[0] ), $words ) ), 0, 3 );
                        ?>
                            <a href="<?php echo esc_url( get_permalink( $liga->ID ) ); ?>" class="fmdb-team-card">
                                <div class="fmdb-team-card__avatar">
                                    <?php if ( $thumb ) : ?>
                                        <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $liga->post_title ); ?>">
                                    <?php else : ?>
                                        <span><?php echo esc_html( $linit ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="fmdb-team-card__info">
                                    <strong><?php echo esc_html( $liga->post_title ); ?></strong>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( $teams ) : ?>
                <div class="fmdb-league-teams">
                    <h2>Equipos en <?php echo esc_html( $state ); ?></h2>
                    <div class="fmdb-team-grid">
                        <?php foreach ( $teams as $team ) :
                            $thumb = get_the_post_thumbnail_url( $team->ID, 'thumbnail' );
                            $city  = get_field( 'team_city', $team->ID );
                            $cats  = get_field( 'team_category', $team->ID ) ?: [];
                            $words = array_filter( explode( ' ', $team->post_title ) );
                            $tinit = substr( implode( '', array_map( fn( $w ) => strtoupper( $w[0] ), $words ) ), 0, 3 );
                        ?>
                            <a href="<?php echo esc_url( get_permalink( $team->ID ) ); ?>" class="fmdb-team-card">
                                <div class="fmdb-team-card__avatar">
                                    <?php if ( $thumb ) : ?>
                                        <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $team->post_title ); ?>">
                                    <?php else : ?>
                                        <span><?php echo esc_html( $tinit ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="fmdb-team-card__info">
                                    <strong><?php echo esc_html( $team->post_title ); ?></strong>
                                    <?php if ( $city ) : ?><small><?php echo esc_html( $city ); ?></small><?php endif; ?>
                                    <?php if ( $cats ) : ?>
                                        <div class="fmdb-team-card__cats">
                                            <?php foreach ( $cats as $cat ) : ?>
                                                <span class="fmdb-badge fmdb-badge--<?php echo esc_attr( strtolower( $cat ) ); ?>"><?php echo esc_html( $cat ); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( $email || $website || $instagram || $facebook ) : ?>
                <div class="fmdb-league-contact">
                    <h2>Contacto</h2>
                    <ul class="fmdb-team-detail-list">
                        <?php if ( $email )   : ?><li><strong>Email:</strong> <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li><?php endif; ?>
                        <?php if ( $website ) : ?><li><strong>Sitio web:</strong> <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $website ); ?></a></li><?php endif; ?>
                    </ul>
                    <?php if ( $instagram || $facebook ) : ?>
                        <div class="fmdb-team-social">
                            <?php if ( $instagram ) : ?><a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
                            <?php if ( $facebook )  : ?><a href="<?php echo esc_url( $facebook ); ?>"  target="_blank" rel="noopener">Facebook</a><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <p class="fmdb-league-back">
                <a href="<?php echo esc_url( home_url( '/mapa-interactivo/?view=asociaciones' ) ); ?>" class="fmdb-link-more">← Ver todas las asociaciones</a>
            </p>

        </div>
    </section>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
