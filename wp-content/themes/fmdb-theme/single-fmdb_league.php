<?php
/**
 * Template: Ficha de liga (single fmdb_league)
 */
get_header();

while ( have_posts() ) :
    the_post();

    $league_id   = get_the_ID();
    $state       = get_field( 'league_state' );
    $description = get_field( 'league_description' );
    $founded     = get_field( 'league_founded' );
    $email       = get_field( 'league_email' );
    $website     = get_field( 'league_website' );
    $instagram   = get_field( 'league_instagram' );
    $facebook    = get_field( 'league_facebook' );

    $initials = implode( '', array_map( fn( $w ) => strtoupper( $w[0] ), explode( ' ', get_the_title() ) ) );
    $initials = substr( $initials, 0, 3 );

    // Teams that belong to this liga (team_league post_object stores the ID)
    $teams = get_posts( [
        'post_type'      => 'fmdb_team',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => [ [ 'key' => 'team_league', 'value' => $league_id ] ],
    ] );
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
                <p class="fmdb-league-hero__label">Liga</p>
                <h1><?php the_title(); ?></h1>
                <p class="fmdb-league-hero__meta">
                    <?php if ( $state )   : ?><span><?php echo esc_html( $state ); ?></span><?php endif; ?>
                    <?php if ( $founded ) : ?><span>Desde <?php echo esc_html( $founded ); ?></span><?php endif; ?>
                    <span><?php echo count( $teams ); ?> equipo<?php echo count( $teams ) !== 1 ? 's' : ''; ?></span>
                </p>
            </div>
        </div>
    </section>

    <section class="fmdb-league-body">
        <div class="fmdb-league-body__inner">

            <?php if ( $description ) : ?>
                <div class="fmdb-league-about">
                    <h2>Acerca de la liga</h2>
                    <p><?php echo nl2br( esc_html( $description ) ); ?></p>
                </div>
            <?php endif; ?>

            <div class="fmdb-league-teams">
                <h2>Equipos</h2>
                <?php if ( empty( $teams ) ) : ?>
                    <p class="fmdb-empty">Aún no hay equipos registrados en esta liga.</p>
                <?php else : ?>
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
                <?php endif; ?>
            </div>

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
                <a href="<?php echo esc_url( home_url( '/equipos-y-ligas/' ) ); ?>" class="fmdb-link-more">← Ver todas las ligas y equipos</a>
            </p>

        </div>
    </section>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
