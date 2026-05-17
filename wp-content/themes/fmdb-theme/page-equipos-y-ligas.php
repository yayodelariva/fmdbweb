<?php
/**
 * Template: Equipos y Ligas
 * Automatically used by WordPress for the page with slug "equipos-y-ligas"
 */
get_header();

// Fetch all published teams with their ACF data
$all_teams = get_posts( [
    'post_type'      => 'fmdb_team',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
] );

// Group: state → league → [ teams ]
$by_state = [];
foreach ( $all_teams as $team ) {
    $state         = get_field( 'team_state', $team->ID )   ?: 'Sin estado';
    $league_obj    = get_field( 'team_league', $team->ID );
    $league_label  = ( $league_obj instanceof WP_Post ) ? $league_obj->post_title : 'Sin liga';
    $city          = get_field( 'team_city', $team->ID )    ?: '';
    $categories    = get_field( 'team_category', $team->ID ) ?: [];

    $by_state[ $state ][ $league_label ][] = [
        'id'         => $team->ID,
        'name'       => $team->post_title,
        'slug'       => $team->post_name,
        'city'       => $city,
        'categories' => $categories,
        'url'        => get_permalink( $team->ID ),
        'thumb'      => get_the_post_thumbnail_url( $team->ID, 'thumbnail' ),
    ];
}
ksort( $by_state );

// Collect all unique categories across all teams
$all_categories = [];
foreach ( $all_teams as $team ) {
    $cats = get_field( 'team_category', $team->ID ) ?: [];
    foreach ( $cats as $cat ) $all_categories[ $cat ] = true;
}
$all_categories = array_keys( $all_categories );
sort( $all_categories );

$state_names = array_keys( $by_state );

// Fetch all published leagues
$all_leagues = get_posts( [
    'post_type'      => 'fmdb_league',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
] );

// Count teams per league for the liga grid
$team_counts_per_league = [];
foreach ( $all_teams as $team ) {
    $lobj = get_field( 'team_league', $team->ID );
    if ( $lobj instanceof WP_Post ) {
        $team_counts_per_league[ $lobj->ID ] = ( $team_counts_per_league[ $lobj->ID ] ?? 0 ) + 1;
    }
}

// Collect unique states from leagues for the ligas filter
$league_states = [];
foreach ( $all_leagues as $liga ) {
    $s = get_field( 'league_state', $liga->ID );
    if ( $s ) $league_states[ $s ] = true;
}
$league_states = array_keys( $league_states );
sort( $league_states );
?>

<main id="fmdb-equipos" class="fmdb-equipos" data-view="todos">

    <div class="fmdb-equipos__header">
        <h1>Equipos y Ligas</h1>
        <p>Selecciona un estado en el mapa o usa los filtros para encontrar equipos.</p>
    </div>

    <!-- SVG Map -->
    <div class="fmdb-equipos__map">
        <?php
        $svg_path = get_stylesheet_directory() . '/assets/mexico-map.svg';
        if ( file_exists( $svg_path ) ) echo file_get_contents( $svg_path );
        ?>
        <div class="fmdb-map-legend">
            <span><i style="background:#D3D1C7"></i>Sin equipos</span>
            <span><i style="background:#9FE1CB"></i>1-2</span>
            <span><i style="background:#5DCAA5"></i>3-5</span>
            <span><i style="background:#1D9E75"></i>6-10</span>
            <span><i style="background:#085041"></i>10+</span>
        </div>
    </div>

    <!-- Map view toggle: Equipos / Ligas / Todos -->
    <div class="fmdb-equipos__toggle" role="tablist" aria-label="Vista del mapa">
        <button class="fmdb-equipos__toggle-btn" data-view="equipos" type="button" role="tab">
            Equipos <span class="fmdb-tab-count"><?php echo count( $all_teams ); ?></span>
        </button>
        <button class="fmdb-equipos__toggle-btn" data-view="ligas" type="button" role="tab">
            Ligas <span class="fmdb-tab-count"><?php echo count( $all_leagues ); ?></span>
        </button>
        <button class="fmdb-equipos__toggle-btn active" data-view="todos" type="button" role="tab" aria-selected="true">
            Todos
        </button>
    </div>

    <!-- ============ EQUIPOS PANEL ============ -->
    <div class="fmdb-equipos__panel" data-panel="equipos">
        <h2 class="fmdb-equipos__panel-title">Equipos por estado</h2>

        <!-- Filter bar -->
        <div class="fmdb-equipos__filters">
            <div class="fmdb-filter-group">
                <label for="filter-state">Estado</label>
                <select id="filter-state">
                    <option value="">Todos los estados</option>
                    <?php foreach ( $state_names as $s ) : ?>
                        <option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( $s ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ( $all_categories ) : ?>
            <div class="fmdb-filter-group">
                <label>Categoría</label>
                <div class="fmdb-filter-checkboxes">
                    <?php foreach ( $all_categories as $cat ) : ?>
                        <label class="fmdb-check-label">
                            <input type="checkbox" class="filter-category" value="<?php echo esc_attr( $cat ); ?>">
                            <?php echo esc_html( $cat ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <button id="fmdb-clear-filters" class="fmdb-btn-ghost">Limpiar filtros</button>
        </div>

        <!-- Content: sidebar + main -->
        <div class="fmdb-equipos__body">

            <!-- Sidebar: state list -->
            <aside class="fmdb-equipos__sidebar">
                <h3>Estados</h3>
                <ul class="fmdb-state-list">
                    <?php foreach ( $by_state as $state => $leagues ) :
                        $count = array_sum( array_map( 'count', $leagues ) );
                    ?>
                        <li class="fmdb-state-list__item"
                            data-state="<?php echo esc_attr( $state ); ?>">
                            <span class="fmdb-state-list__name"><?php echo esc_html( $state ); ?></span>
                            <span class="fmdb-state-list__count"><?php echo esc_html( $count ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>

            <!-- Main: state blocks → flat team grid -->
            <section class="fmdb-equipos__main" id="fmdb-teams-container">

                <?php if ( empty( $by_state ) ) : ?>
                    <p class="fmdb-empty">No hay equipos registrados aún.</p>
                <?php endif; ?>

                <?php foreach ( $by_state as $state => $leagues ) :
                    // Flatten teams under this state (drop the per-liga grouping in this view)
                    $flat_teams = [];
                    foreach ( $leagues as $teams_in_liga ) {
                        foreach ( $teams_in_liga as $t ) $flat_teams[] = $t;
                    }
                    usort( $flat_teams, fn( $a, $b ) => strcasecmp( $a['name'], $b['name'] ) );
                    $state_count = count( $flat_teams );
                ?>
                    <div class="fmdb-state-block" data-state="<?php echo esc_attr( $state ); ?>">
                        <div class="fmdb-state-banner">
                            <h2><?php echo esc_html( $state ); ?></h2>
                            <span><?php echo esc_html( $state_count ); ?> equipo<?php echo $state_count !== 1 ? 's' : ''; ?></span>
                        </div>

                        <div class="fmdb-team-grid">
                            <?php foreach ( $flat_teams as $team ) :
                                $initials = implode( '', array_map( fn( $w ) => strtoupper( $w[0] ), explode( ' ', $team['name'] ) ) );
                                $initials = substr( $initials, 0, 3 );
                            ?>
                                <a href="<?php echo esc_url( $team['url'] ); ?>"
                                   class="fmdb-team-card"
                                   data-categories="<?php echo esc_attr( implode( ',', $team['categories'] ) ); ?>">
                                    <div class="fmdb-team-card__avatar">
                                        <?php if ( $team['thumb'] ) : ?>
                                            <img src="<?php echo esc_url( $team['thumb'] ); ?>" alt="<?php echo esc_attr( $team['name'] ); ?>">
                                        <?php else : ?>
                                            <span><?php echo esc_html( $initials ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="fmdb-team-card__info">
                                        <strong><?php echo esc_html( $team['name'] ); ?></strong>
                                        <?php if ( $team['city'] ) : ?>
                                            <small><?php echo esc_html( $team['city'] ); ?></small>
                                        <?php endif; ?>
                                        <?php if ( $team['categories'] ) : ?>
                                            <div class="fmdb-team-card__cats">
                                                <?php foreach ( $team['categories'] as $cat ) : ?>
                                                    <span class="fmdb-badge fmdb-badge--<?php echo esc_attr( strtolower( $cat ) ); ?>"><?php echo esc_html( $cat ); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div id="fmdb-no-results" class="fmdb-empty" style="display:none;">
                    No se encontraron equipos con los filtros seleccionados.
                </div>
            </section>

        </div>
    </div>

    <!-- ============ LIGAS PANEL ============ -->
    <div class="fmdb-equipos__panel" data-panel="ligas">
        <h2 class="fmdb-equipos__panel-title">Ligas registradas</h2>

        <?php if ( $league_states ) : ?>
        <div class="fmdb-equipos__filters">
            <div class="fmdb-filter-group">
                <label for="filter-state-ligas">Estado</label>
                <select id="filter-state-ligas">
                    <option value="">Todos los estados</option>
                    <?php foreach ( $league_states as $s ) : ?>
                        <option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( $s ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button id="fmdb-clear-filters-ligas" class="fmdb-btn-ghost">Limpiar filtros</button>
        </div>
        <?php endif; ?>

        <?php if ( empty( $all_leagues ) ) : ?>
            <p class="fmdb-empty">No hay ligas registradas aún.</p>
        <?php else : ?>
            <div class="fmdb-league-grid">
                <?php foreach ( $all_leagues as $liga ) :
                    $l_state    = get_field( 'league_state', $liga->ID );
                    $l_desc     = get_field( 'league_description', $liga->ID );
                    $l_thumb    = get_the_post_thumbnail_url( $liga->ID, 'thumbnail' );
                    $l_count    = $team_counts_per_league[ $liga->ID ] ?? 0;
                    $l_words    = array_filter( explode( ' ', $liga->post_title ) );
                    $l_initials = substr( implode( '', array_map( fn( $w ) => strtoupper( $w[0] ), $l_words ) ), 0, 3 );
                ?>
                    <a href="<?php echo esc_url( get_permalink( $liga->ID ) ); ?>"
                       class="fmdb-league-card"
                       data-state="<?php echo esc_attr( $l_state ?: '' ); ?>">
                        <div class="fmdb-league-card__crest">
                            <?php if ( $l_thumb ) : ?>
                                <img src="<?php echo esc_url( $l_thumb ); ?>" alt="<?php echo esc_attr( $liga->post_title ); ?>">
                            <?php else : ?>
                                <span><?php echo esc_html( $l_initials ); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="fmdb-league-card__body">
                            <strong><?php echo esc_html( $liga->post_title ); ?></strong>
                            <?php if ( $l_state ) : ?>
                                <small><?php echo esc_html( $l_state ); ?></small>
                            <?php endif; ?>
                            <?php if ( $l_desc ) : ?>
                                <p><?php echo esc_html( wp_trim_words( $l_desc, 22 ) ); ?></p>
                            <?php endif; ?>
                            <span class="fmdb-league-card__count"><?php echo $l_count; ?> equipo<?php echo $l_count !== 1 ? 's' : ''; ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div id="fmdb-ligas-no-results" class="fmdb-empty" style="display:none;">
                No se encontraron ligas con los filtros seleccionados.
            </div>
        <?php endif; ?>

    </div>

</main>

<?php get_footer(); ?>
