<?php
/**
 * Template: Eventos
 * Slug: eventos
 */

$tab   = ( isset( $_GET['tab'] ) && $_GET['tab'] === 'pasados' ) ? 'pasados' : 'proximos';
$today = current_time( 'mysql' );

global $wpdb;
$compare = $tab === 'proximos' ? '>=' : '<';
$order   = $tab === 'proximos' ? 'ASC' : 'DESC';

// IDs for the list view (filtered by tab)
$event_ids = $wpdb->get_col( $wpdb->prepare(
    "SELECT p.ID
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_EventStartDate'
     WHERE p.post_type   = 'tribe_events'
       AND p.post_status = 'publish'
       AND pm.meta_value {$compare} %s
     ORDER BY pm.meta_value {$order}",
    $today
) );


// All events (for calendar view, regardless of tab)
$all_event_ids = $wpdb->get_col(
    "SELECT p.ID FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_EventStartDate'
     WHERE p.post_type='tribe_events' AND p.post_status='publish'
     ORDER BY pm.meta_value ASC"
);

$calendar_events = [];
foreach ( $all_event_ids as $eid ) {
    $start = get_post_meta( $eid, '_EventStartDate', true );
    $end   = get_post_meta( $eid, '_EventEndDate',   true );
    $cats  = get_the_terms( $eid, 'tribe_events_cat' );
    $cat_slug = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->slug : 'miscelaneo';
    $calendar_events[] = [
        'id'       => (int) $eid,
        'title'    => html_entity_decode( get_the_title( $eid ), ENT_QUOTES, 'UTF-8' ),
        'start'    => str_replace( ' ', 'T', $start ),
        'end'      => str_replace( ' ', 'T', $end ),
        'category' => $cat_slug,
        'url'      => get_permalink( $eid ),
    ];
}

get_header();
?>

<main class="fmdb-eventos">

    <div class="fmdb-eventos__header">
        <h1 class="fmdb-eventos__title">Eventos</h1>
        <p class="fmdb-eventos__subtitle">Torneos, campamentos y actividades de la Federación Mexicana de Dodgeball</p>
    </div>

    <div class="fmdb-eventos__wrap">

        <div class="fmdb-eventos__view-toggle">
            <button type="button" class="fmdb-eventos__view-btn is-active" data-view-toggle="list" aria-label="Vista de lista">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6"  x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6"  x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                Lista
            </button>
            <button type="button" class="fmdb-eventos__view-btn" data-view-toggle="calendar" aria-label="Vista de calendario">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Calendario
            </button>
        </div>

        <div id="fmdb-eventos-list">
            <div class="fmdb-eventos__tabs">
                <a href="?tab=proximos" class="fmdb-eventos__tab <?php echo $tab === 'proximos' ? 'is-active' : ''; ?>">Próximos</a>
                <a href="?tab=pasados"  class="fmdb-eventos__tab <?php echo $tab === 'pasados'  ? 'is-active' : ''; ?>">Pasados</a>
            </div>

            <?php if ( $event_ids ) : ?>
                <div class="fmdb-eventos__grid">
                    <?php foreach ( $event_ids as $id ) :
                        $post_obj = get_post( $id );
                        if ( ! $post_obj ) continue;
                        global $post;
                        $post = $post_obj;
                        setup_postdata( $post );
                        $start_raw  = get_post_meta( $id, '_EventStartDate', true );
                        $end_raw    = get_post_meta( $id, '_EventEndDate',   true );
                        $start_ts   = strtotime( $start_raw );
                        $end_ts     = strtotime( $end_raw );
                        $dp         = fmdb_event_date_parts( $start_ts, $end_ts );
                        $time_start = date_i18n( 'g:i a', $start_ts );
                        $time_end   = date_i18n( 'g:i a', $end_ts );
                        $venue_id   = get_post_meta( $id, '_EventVenueID', true );
                        $venue      = $venue_id ? get_the_title( $venue_id ) : '';
                        $city       = $venue_id ? get_post_meta( $venue_id, '_VenueCity',  true ) : '';
                        $state      = $venue_id ? get_post_meta( $venue_id, '_VenueState', true ) : '';
                        $location   = implode( ', ', array_filter( [ $venue, $city, $state ] ) );
                        $cats       = get_the_terms( $id, 'tribe_events_cat' );
                        $cat_slug   = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->slug : 'miscelaneo';
                    ?>
                    <article class="fmdb-evento-card fmdb-evento-card--<?php echo esc_attr( $cat_slug ); ?>">
                        <div class="fmdb-evento-card__date fmdb-cat--<?php echo esc_attr( $cat_slug ); ?><?php echo $dp['is_range'] ? ' is-range' : ''; ?>">
                            <span class="fmdb-evento-card__day"><?php echo esc_html( $dp['day'] ); ?></span>
                            <span class="fmdb-evento-card__month"><?php echo esc_html( $dp['month'] ); ?></span>
                            <span class="fmdb-evento-card__year"><?php echo esc_html( $dp['year'] ); ?></span>
                        </div>
                        <div class="fmdb-evento-card__body">
                            <?php if ( $cats && ! is_wp_error( $cats ) ) : ?>
                                <div class="fmdb-evento-card__cats">
                                    <?php foreach ( $cats as $cat ) : ?>
                                        <span class="fmdb-evento-card__cat fmdb-cat--<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <h2 class="fmdb-evento-card__title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            <?php if ( $location ) : ?>
                                <p class="fmdb-evento-card__location">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <?php echo esc_html( $location ); ?>
                                </p>
                            <?php endif; ?>
                            <p class="fmdb-evento-card__time">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <?php echo esc_html( $time_start ); ?><?php if ( $end_ts && $end_ts !== $start_ts ) echo ' – ' . esc_html( $time_end ); ?>
                            </p>
                            <?php if ( has_excerpt() ) : ?>
                                <p class="fmdb-evento-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
                            <?php endif; ?>
                            <a href="<?php the_permalink(); ?>" class="fmdb-evento-card__link">Ver detalles →</a>
                        </div>
                    </article>
                    <?php endforeach; wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <div class="fmdb-eventos__empty">
                    <?php if ( $tab === 'proximos' ) : ?>
                        <p>No hay eventos próximos por el momento. ¡Vuelve pronto!</p>
                    <?php else : ?>
                        <p>Aún no hay eventos pasados registrados.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="fmdb-calendar" class="fmdb-cal" style="display:none;"></div>

    </div>
</main>

<script>window.fmdbEvents = <?php echo wp_json_encode( $calendar_events ); ?>;</script>

<?php get_footer(); ?>
