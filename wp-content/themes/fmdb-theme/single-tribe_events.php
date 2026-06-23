<?php
/**
 * Template: Single Event (The Events Calendar)
 */

get_header();

while ( have_posts() ) : the_post();
    $id         = get_the_ID();
    $start_raw  = get_post_meta( $id, '_EventStartDate', true );
    $end_raw    = get_post_meta( $id, '_EventEndDate',   true );

    // Pick the active occurrence based on ?fecha=N (default: primary).
    $occurrences  = fmdb_get_event_occurrences( $id );
    $active_index = isset( $_GET['fecha'] ) ? (int) $_GET['fecha'] : 0;
    $active_occ   = null;
    foreach ( $occurrences as $_occ ) {
        if ( (int) $_occ['index'] === $active_index ) { $active_occ = $_occ; break; }
    }
    if ( ! $active_occ ) {
        $active_occ = $occurrences ? $occurrences[0] : null;
        $active_index = $active_occ ? (int) $active_occ['index'] : 0;
    }
    $start_ts   = $active_occ ? $active_occ['start_ts'] : strtotime( $start_raw );
    $end_ts     = $active_occ ? $active_occ['end_ts']   : strtotime( $end_raw );
    $active_note = ( $active_occ && ! $active_occ['is_primary'] ) ? $active_occ['note'] : '';
    $venue_id   = get_post_meta( $id, '_EventVenueID', true );
    $venue      = $venue_id ? get_the_title( $venue_id ) : '';
    $address    = $venue_id ? get_post_meta( $venue_id, '_VenueAddress', true ) : '';
    $city       = $venue_id ? get_post_meta( $venue_id, '_VenueCity',    true ) : '';
    $state      = $venue_id ? get_post_meta( $venue_id, '_VenueState',   true ) : '';
    $cost       = get_post_meta( $id, '_EventCost', true );
    $url        = get_post_meta( $id, '_EventURL',  true );
    $cats       = get_the_terms( $id, 'tribe_events_cat' );
    $pdfs       = get_post_meta( $id, 'event_pdfs', true );
    if ( ! is_array( $pdfs ) ) { $pdfs = []; }

    $same_day   = date( 'Y-m-d', $start_ts ) === date( 'Y-m-d', $end_ts );
?>

<main class="fmdb-evento-single">
    <div class="fmdb-evento-single__wrap">

        <a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>" class="fmdb-evento-single__back">← Regresar a eventos</a>

        <div class="fmdb-evento-single__header">
            <?php $dp = fmdb_event_date_parts( $start_ts, $end_ts ); ?>
            <div class="fmdb-evento-single__date-badge<?php echo $dp['is_range'] ? ' is-range' : ''; ?>">
                <span class="fmdb-evento-card__day"><?php echo esc_html( $dp['day'] ); ?></span>
                <span class="fmdb-evento-card__month"><?php echo esc_html( $dp['month'] ); ?></span>
                <span class="fmdb-evento-card__year"><?php echo esc_html( $dp['year'] ); ?></span>
            </div>
            <div class="fmdb-evento-single__heading">
                <?php if ( $cats && ! is_wp_error( $cats ) ) : ?>
                    <div class="fmdb-evento-card__cats">
                        <?php foreach ( $cats as $cat ) : ?>
                            <span class="fmdb-evento-card__cat"><?php echo esc_html( $cat->name ); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <h1 class="fmdb-evento-single__title"><?php
                    the_title();
                    if ( $active_note !== '' ) echo ' <span class="fmdb-evento-single__title-note">— ' . esc_html( $active_note ) . '</span>';
                ?></h1>
            </div>
        </div>

        <?php
        // Pre-compute all content flags before the layout div
        global $post;
        $_raw  = preg_replace( '/<!--.*?-->/s', '', $post->post_content ?? '' );
        $_body = trim( preg_replace( '/\s+/', '', html_entity_decode( wp_strip_all_tags( $_raw ), ENT_QUOTES | ENT_HTML5 ) ) );

        $tournament_matches = get_post_meta( $id, 'tournament_matches', true );
        $tournament_teams   = get_post_meta( $id, 'tournament_teams', true );
        if ( ! is_array( $tournament_matches ) ) { $tournament_matches = []; }
        if ( ! is_array( $tournament_teams ) )   { $tournament_teams   = []; }

        $event_type = get_post_meta( $id, 'event_type', true );
        if ( ! $event_type && $cats && ! is_wp_error( $cats ) ) {
            foreach ( $cats as $_c ) {
                if ( $_c->slug === 'torneo' ) { $event_type = 'torneo'; break; }
            }
        }

        $has_body    = $_body !== '';
        $has_bracket = ! empty( $tournament_matches ) && $event_type === 'torneo';
        $has_pdfs    = ! empty( $pdfs );
        $has_content = $has_body || has_post_thumbnail() || $has_bracket || $has_pdfs;
        ?>

        <div class="fmdb-evento-single__layout">

            <?php if ( $has_content ) : ?>
            <div class="fmdb-evento-single__content">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="fmdb-evento-single__thumbnail">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( $has_body ) : ?>
                <div class="fmdb-evento-single__body">
                    <?php the_content(); ?>
                </div>
                <?php endif; ?>

                <?php if ( $has_bracket ) :
                    $round_order = [ 'grupos' => 1, 'octavos' => 2, 'cuartos' => 3, 'semis' => 4, 'tercero' => 5, 'final' => 6 ];
                    $round_label = [
                        'grupos'  => 'Fase de grupos',
                        'octavos' => 'Octavos de final',
                        'cuartos' => 'Cuartos de final',
                        'semis'   => 'Semifinal',
                        'tercero' => 'Tercer lugar',
                        'final'   => 'Final',
                    ];
                    $by_round = [];
                    foreach ( $tournament_matches as $m ) {
                        $r = $m['match_round'] ?: 'final';
                        $by_round[ $r ][] = $m;
                    }
                    uksort( $by_round, function ( $a, $b ) use ( $round_order ) {
                        return ( $round_order[ $a ] ?? 99 ) <=> ( $round_order[ $b ] ?? 99 );
                    } );
                ?>
                <section class="fmdb-tournament">
                    <h2 class="fmdb-tournament__title">Bracket del torneo</h2>

                    <?php if ( $tournament_teams ) : ?>
                        <div class="fmdb-tournament__teams">
                            <span class="fmdb-tournament__teams-label">Equipos:</span>
                            <?php foreach ( $tournament_teams as $t ) : if ( ! empty( $t['team_name'] ) ) : ?>
                                <span class="fmdb-tournament__team-pill"><?php echo esc_html( $t['team_name'] ); ?></span>
                            <?php endif; endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="fmdb-tournament__rounds">
                        <?php foreach ( $by_round as $round_slug => $round_matches ) : ?>
                            <div class="fmdb-tournament__round">
                                <h3 class="fmdb-tournament__round-title"><?php echo esc_html( $round_label[ $round_slug ] ?? ucfirst( $round_slug ) ); ?></h3>
                                <?php foreach ( $round_matches as $m ) :
                                    $a       = $m['team_a_name'] ?? '';
                                    $b       = $m['team_b_name'] ?? '';
                                    $sa      = isset( $m['score_a'] ) && $m['score_a'] !== '' ? (int) $m['score_a'] : null;
                                    $sb      = isset( $m['score_b'] ) && $m['score_b'] !== '' ? (int) $m['score_b'] : null;
                                    $played  = ! empty( $m['match_played'] );
                                    $a_wins  = $played && $sa !== null && $sb !== null && $sa > $sb;
                                    $b_wins  = $played && $sa !== null && $sb !== null && $sb > $sa;
                                    $tie     = $played && $sa !== null && $sb !== null && $sa === $sb;
                                ?>
                                    <div class="fmdb-match<?php echo $played ? ' is-played' : ''; ?>">
                                        <div class="fmdb-match__row<?php echo $a_wins ? ' is-winner' : ''; ?>">
                                            <span class="fmdb-match__team"><?php echo esc_html( $a ?: '—' ); ?></span>
                                            <span class="fmdb-match__score"><?php echo $played && $sa !== null ? esc_html( $sa ) : '–'; ?></span>
                                        </div>
                                        <div class="fmdb-match__row<?php echo $b_wins ? ' is-winner' : ''; ?>">
                                            <span class="fmdb-match__team"><?php echo esc_html( $b ?: '—' ); ?></span>
                                            <span class="fmdb-match__score"><?php echo $played && $sb !== null ? esc_html( $sb ) : '–'; ?></span>
                                        </div>
                                        <?php if ( $tie ) : ?>
                                            <div class="fmdb-match__tie">Empate</div>
                                        <?php elseif ( ! $played ) : ?>
                                            <div class="fmdb-match__pending">Por jugarse</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ( ! empty( $pdfs ) ) : ?>
                    <div class="fmdb-evento-single__pdfs">
                        <h3 class="fmdb-evento-single__pdfs-title">Documentos</h3>
                        <ul class="fmdb-evento-single__pdf-list">
                            <?php foreach ( $pdfs as $pdf ) :
                                $file = is_array( $pdf ) ? $pdf : [ 'url' => $pdf ];
                                $url  = $file['url'] ?? '';
                                if ( ! $url ) continue;
                                $name = ! empty( $file['title'] )    ? $file['title']
                                      : ( ! empty( $file['filename'] ) ? $file['filename'] : basename( $url ) );
                            ?>
                                <li>
                                    <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" class="fmdb-evento-single__pdf-link">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        <?php echo esc_html( $name ); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; // $has_content ?>

            <aside class="fmdb-evento-single__sidebar">
                <div class="fmdb-evento-single__meta-card">
                    <h3 class="fmdb-evento-single__meta-title">Detalles del evento</h3>

                    <div class="fmdb-evento-single__meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <div>
                            <strong>Inicio</strong>
                            <span><?php echo date_i18n( 'j \d\e F, Y', $start_ts ); ?></span>
                            <span><?php echo date_i18n( 'g:i a', $start_ts ); ?></span>
                        </div>
                    </div>

                    <?php if ( $end_ts && ! $same_day ) : ?>
                        <div class="fmdb-evento-single__meta-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <div>
                                <strong>Fin</strong>
                                <span><?php echo date_i18n( 'j \d\e F, Y', $end_ts ); ?></span>
                                <span><?php echo date_i18n( 'g:i a', $end_ts ); ?></span>
                            </div>
                        </div>
                    <?php elseif ( $end_ts && $same_day ) : ?>
                        <div class="fmdb-evento-single__meta-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <div>
                                <strong>Horario</strong>
                                <span><?php echo date_i18n( 'g:i a', $start_ts ); ?> – <?php echo date_i18n( 'g:i a', $end_ts ); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( count( $occurrences ) > 1 ) : ?>
                        <div class="fmdb-evento-single__meta-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <div>
                                <strong>Todas las fechas</strong>
                                <ul class="fmdb-evento-single__dates">
                                    <?php foreach ( $occurrences as $occ ) :
                                        $occ_idx    = (int) $occ['index'];
                                        $occ_id     = 'fecha-' . $occ_idx;
                                        $day_label  = date_i18n( 'j \d\e F', $occ['start_ts'] );
                                        $time_label = date_i18n( 'g:i a', $occ['start_ts'] );
                                        $is_active  = $occ_idx === $active_index;
                                        $occ_href   = $occ['is_primary']
                                            ? get_permalink( $id ) . '#' . $occ_id
                                            : add_query_arg( 'fecha', $occ_idx, get_permalink( $id ) ) . '#' . $occ_id;
                                    ?>
                                        <li id="<?php echo esc_attr( $occ_id ); ?>" class="<?php echo $is_active ? 'is-active' : ''; ?>">
                                            <a href="<?php echo esc_url( $occ_href ); ?>">
                                                <span class="fmdb-evento-single__dates-date"><?php echo esc_html( $day_label ); ?></span>
                                                <span class="fmdb-evento-single__dates-time"><?php echo esc_html( $time_label ); ?></span>
                                                <?php if ( $occ['note'] !== '' ) : ?>
                                                    <span class="fmdb-evento-single__dates-note"><?php echo esc_html( $occ['note'] ); ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $venue || $city ) : ?>
                        <div class="fmdb-evento-single__meta-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <div>
                                <strong>Lugar</strong>
                                <?php if ( $venue )   : ?><span><?php echo esc_html( $venue );   ?></span><?php endif; ?>
                                <?php if ( $address ) : ?><span><?php echo esc_html( $address ); ?></span><?php endif; ?>
                                <?php
                                $loc = implode( ', ', array_filter( [ $city, $state ] ) );
                                if ( $loc ) echo '<span>' . esc_html( $loc ) . '</span>';
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $cost ) : ?>
                        <div class="fmdb-evento-single__meta-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            <div>
                                <strong>Costo</strong>
                                <span><?php echo esc_html( $cost ); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $url ) : ?>
                        <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" class="fmdb-btn fmdb-btn--primary" style="width:100%;text-align:center;margin-top:8px;display:block;">
                            Más información
                        </a>
                    <?php endif; ?>
                </div>

                <?php fmdb_event_registration_box( $id ); ?>
            </aside>

        </div>

    </div>
</main>

<?php endwhile; get_footer(); ?>
