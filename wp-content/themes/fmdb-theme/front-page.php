<?php
/**
 * Template: Front Page / Home
 */
get_header();

$state_set = [];
$all_teams = get_posts( [ 'post_type' => 'fmdb_team', 'posts_per_page' => -1, 'post_status' => 'publish' ] );
foreach ( $all_teams as $t ) {
    $s = get_field( 'team_state', $t->ID );
    if ( $s ) $state_set[ $s ] = true;
}
$state_count = count( $state_set );

$latest_posts = get_posts( [ 'posts_per_page' => 3, 'post_status' => 'publish' ] );
?>

<main class="fmdb-home">

    <!-- Hero -->
    <?php
    $hero_video_path   = get_stylesheet_directory() . '/assets/hero.mp4';
    $hero_video_url    = get_stylesheet_directory_uri() . '/assets/hero.mp4';
    $hero_poster_path  = get_stylesheet_directory() . '/assets/hero-poster.jpg';
    $hero_poster_url   = get_stylesheet_directory_uri() . '/assets/hero-poster.jpg';
    $has_hero_video    = file_exists( $hero_video_path );
    $has_hero_poster   = file_exists( $hero_poster_path );
    if ( $has_hero_video )  $hero_video_url  .= '?v=' . filemtime( $hero_video_path );
    if ( $has_hero_poster ) $hero_poster_url .= '?v=' . filemtime( $hero_poster_path );
    ?>
    <section class="fmdb-hero<?php echo $has_hero_video ? ' has-video' : ''; ?>">
        <?php if ( $has_hero_video ) : ?>
            <video
                class="fmdb-hero__video"
                autoplay
                muted
                loop
                playsinline
                preload="metadata"
                <?php if ( $has_hero_poster ) : ?>poster="<?php echo esc_url( $hero_poster_url ); ?>"<?php endif; ?>
            >
                <source src="<?php echo esc_url( $hero_video_url ); ?>" type="video/mp4">
            </video>
            <div class="fmdb-hero__overlay" aria-hidden="true"></div>
        <?php endif; ?>
        <div class="fmdb-hero__inner">
            <p class="fmdb-hero__eyebrow">Federación Mexicana de Dodgeball</p>
            <h1 class="fmdb-hero__title">El dodgeball<br>organizado de México</h1>
            <p class="fmdb-hero__subtitle">Conectamos equipos, ligas y jugadores en todo el país.</p>
            <div class="fmdb-hero__ctas">
                <a href="<?php echo esc_url( home_url( '/mapa-interactivo/' ) ); ?>" class="fmdb-btn fmdb-btn--primary">Encuentra tu equipo</a>
                <a href="<?php echo esc_url( wp_login_url( home_url( '/mi-equipo/' ) ) ); ?>" class="fmdb-btn fmdb-btn--outline">Únete a la FMDB</a>
            </div>
        </div>
        <div class="fmdb-hero__deco" aria-hidden="true"></div>
    </section>

    <!-- Map section -->
    <section class="fmdb-home-map">
        <div class="fmdb-home-map__inner">
            <div class="fmdb-home-map__visual">
                <?php
                $svg_path = get_stylesheet_directory() . '/assets/mexico-map.svg';
                if ( file_exists( $svg_path ) ) echo file_get_contents( $svg_path );
                ?>
            </div>
            <div class="fmdb-home-map__text">
                <span class="fmdb-section-eyebrow">Directorio de equipos</span>
                <h2>Encuentra equipos cerca de ti</h2>
                <p>Tenemos equipos en <?php echo esc_html( $state_count ); ?> estados de la República. Haz clic en el mapa para explorar los equipos de cada estado.</p>
                <a href="<?php echo esc_url( home_url( '/mapa-interactivo/' ) ); ?>" class="fmdb-btn fmdb-btn--primary">Ver todos los equipos</a>
            </div>
        </div>
    </section>

    <!-- Latest news -->
    <?php if ( $latest_posts ) : ?>
    <section class="fmdb-home-news">
        <div class="fmdb-home-news__inner">
            <div class="fmdb-section-header">
                <h2>Últimas noticias</h2>
                <a href="<?php echo esc_url( home_url( '/noticias/' ) ); ?>" class="fmdb-link-more">Ver todas →</a>
            </div>
            <div class="fmdb-news-grid">
                <?php foreach ( $latest_posts as $p ) :
                    $thumb   = get_the_post_thumbnail_url( $p->ID, 'medium' );
                    $excerpt = wp_trim_words( get_the_excerpt( $p->ID ) ?: $p->post_content, 20 );
                ?>
                    <a href="<?php echo esc_url( get_permalink( $p->ID ) ); ?>" class="fmdb-news-card">
                        <div class="fmdb-news-card__img"<?php if ( $thumb ) echo ' style="background-image:url(' . esc_url( $thumb ) . ')"'; ?>></div>
                        <div class="fmdb-news-card__body">
                            <span class="fmdb-news-card__date"><?php echo esc_html( get_the_date( 'd M Y', $p->ID ) ); ?></span>
                            <h3 class="fmdb-news-card__title"><?php echo esc_html( $p->post_title ); ?></h3>
                            <p class="fmdb-news-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Próximos eventos -->
    <?php
    global $wpdb;
    // Pull anything that could have an upcoming occurrence (within ~3 months),
    // expand each into occurrences, then take the next 3 across all events.
    $candidate_ids = $wpdb->get_col(
        "SELECT p.ID FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_EventStartDate'
         WHERE p.post_type='tribe_events' AND p.post_status='publish'"
    );
    $now_ts = strtotime( current_time( 'mysql' ) );
    $home_occurrences = [];
    foreach ( $candidate_ids as $cid ) {
        foreach ( fmdb_get_event_occurrences( $cid ) as $occ ) {
            if ( $occ['start_ts'] >= $now_ts ) $home_occurrences[] = $occ;
        }
    }
    usort( $home_occurrences, function ( $a, $b ) {
        return $a['start_ts'] <=> $b['start_ts'];
    } );
    $home_occurrences = array_slice( $home_occurrences, 0, 3 );
    ?>
    <section class="fmdb-home-events">
        <div class="fmdb-home-events__inner">
            <div class="fmdb-section-header">
                <h2>Próximos eventos</h2>
                <a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>" class="fmdb-link-more">Ver calendario →</a>
            </div>
            <div class="fmdb-events-list">
                <?php if ( $home_occurrences ) : foreach ( $home_occurrences as $occ ) :
                    $eid      = $occ['post_id'];
                    $start_ts = $occ['start_ts'];
                    $end_ts   = $occ['end_ts'];
                    $dp       = $occ['is_primary']
                        ? fmdb_event_date_parts( $start_ts, $end_ts )
                        : fmdb_event_date_parts( $start_ts, $start_ts );
                    $venue_id = get_post_meta( $eid, '_EventVenueID', true );
                    $city     = $venue_id ? get_post_meta( $venue_id, '_VenueCity',  true ) : '';
                    $state    = $venue_id ? get_post_meta( $venue_id, '_VenueState', true ) : '';
                    $location = implode( ', ', array_filter( [ $city, $state ] ) ) ?: 'Por confirmar';
                    $cats     = get_the_terms( $eid, 'tribe_events_cat' );
                    $cat_slug = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->slug : 'miscelaneo';
                    $cat_name = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : 'Misceláneo';
                    $permalink = $occ['is_primary']
                        ? get_permalink( $eid )
                        : add_query_arg( 'fecha', $occ['index'], get_permalink( $eid ) ) . '#fecha-' . $occ['index'];
                    $title     = get_the_title( $eid ) . ( ( ! $occ['is_primary'] && $occ['note'] !== '' ) ? ' — ' . $occ['note'] : '' );
                ?>
                    <a href="<?php echo esc_url( $permalink ); ?>" class="fmdb-event-item fmdb-cat--<?php echo esc_attr( $cat_slug ); ?>">
                        <div class="fmdb-event-date<?php echo $dp['is_range'] ? ' is-range' : ''; ?>">
                            <span><?php echo esc_html( $dp['day'] ); ?></span>
                            <small><?php echo esc_html( $dp['month'] ); ?></small>
                        </div>
                        <div class="fmdb-event-info">
                            <strong><?php echo esc_html( $title ); ?></strong>
                            <span><?php echo esc_html( $location ); ?></span>
                        </div>
                        <span class="fmdb-badge"><?php echo esc_html( $cat_name ); ?></span>
                    </a>
                <?php endforeach; else : ?>
                    <p class="fmdb-events-empty">No hay eventos próximos por el momento.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Sponsors -->
    <section class="fmdb-home-sponsors">
        <div class="fmdb-home-sponsors__inner">
            <h2 class="fmdb-home-sponsors__title">Patrocinadores</h2>
            <div class="fmdb-sponsors-strip">
                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                    <div class="fmdb-sponsor-logo">
                        <span>Patrocinador <?php echo $i; ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>
