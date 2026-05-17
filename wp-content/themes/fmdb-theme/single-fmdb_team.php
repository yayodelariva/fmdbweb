<?php
/**
 * Template: Ficha de equipo (single fmdb_team)
 */
get_header();

while ( have_posts() ) :
    the_post();

    $state      = get_field( 'team_state' );
    $city       = get_field( 'team_city' );
    $categories = get_field( 'team_category' ) ?: [];
    $league_obj = get_field( 'team_league' );
    $league     = ( $league_obj instanceof WP_Post ) ? $league_obj->post_title : '';
    $league_url = ( $league_obj instanceof WP_Post ) ? get_permalink( $league_obj->ID ) : '';
    $founded    = get_field( 'team_founded' );
    $fmdb_id    = get_field( 'team_fmdb_id' );
    $description= get_field( 'team_description' );
    $email      = get_field( 'team_contact_email' );
    $instagram  = get_field( 'team_instagram' );
    $facebook   = get_field( 'team_facebook' );
    $wins       = get_field( 'team_wins' );
    $losses     = get_field( 'team_losses' );
    $players    = get_field( 'team_players' );
    $roster     = get_post_meta( get_the_ID(), 'team_roster', true );
    $results    = get_post_meta( get_the_ID(), 'team_results', true );
    if ( ! is_array( $roster ) )  { $roster  = []; }
    if ( ! is_array( $results ) ) { $results = []; }

    $initials = implode( '', array_map( fn( $w ) => strtoupper( $w[0] ), explode( ' ', get_the_title() ) ) );
    $initials = substr( $initials, 0, 3 );
?>

<main id="fmdb-team-single" class="fmdb-team-single">

    <!-- Hero -->
    <section class="fmdb-team-hero">
        <div class="fmdb-team-hero__inner">
            <div class="fmdb-team-crest">
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'medium' ); ?>
                <?php else : ?>
                    <span class="fmdb-team-initials"><?php echo esc_html( $initials ); ?></span>
                <?php endif; ?>
            </div>
            <div class="fmdb-team-hero__info">
                <h1><?php the_title(); ?></h1>
                <p class="fmdb-team-meta">
                    <?php if ( $city ) echo esc_html( $city ) . ', '; ?>
                    <?php if ( $state ) echo esc_html( $state ); ?>
                </p>
                <?php if ( $categories ) : ?>
                    <div class="fmdb-team-badges">
                        <?php foreach ( $categories as $cat ) : ?>
                            <span class="fmdb-badge fmdb-badge--<?php echo esc_attr( strtolower( $cat ) ); ?>"><?php echo esc_html( $cat ); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="fmdb-team-stats-strip">
                    <?php if ( $wins !== '' && $wins !== false ) : ?>
                        <div class="fmdb-stat"><span><?php echo esc_html( $wins ); ?></span>Victorias</div>
                    <?php endif; ?>
                    <?php if ( $losses !== '' && $losses !== false ) : ?>
                        <div class="fmdb-stat"><span><?php echo esc_html( $losses ); ?></span>Derrotas</div>
                    <?php endif; ?>
                    <?php if ( $players ) : ?>
                        <div class="fmdb-stat"><span><?php echo esc_html( $players ); ?></span>Jugadores</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Tabs -->
    <section class="fmdb-team-tabs">
        <div class="fmdb-team-tabs__inner">
            <nav class="fmdb-tabs-nav">
                <button class="fmdb-tab-btn active" data-tab="plantel">Plantel</button>
                <button class="fmdb-tab-btn" data-tab="resultados">Resultados</button>
                <button class="fmdb-tab-btn" data-tab="acerca">Acerca del equipo</button>
            </nav>

            <!-- Plantel -->
            <div id="tab-plantel" class="fmdb-tab-panel active">
                <?php if ( $roster ) : ?>
                    <table class="fmdb-roster-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Posición</th>
                                <th>Rol</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $roster as $player ) :
                                $uid       = (int) ( $player['user_id'] ?? $player['linked_user_id'] ?? 0 );
                                $user_data = $uid ? get_userdata( $uid ) : null;
                                $name      = $user_data ? $user_data->display_name : ( $player['name'] ?? '' );
                            ?>
                                <tr>
                                    <td><?php echo esc_html( $player['number'] ); ?></td>
                                    <td class="fmdb-roster-name-cell">
                                        <?php echo fmdb_player_avatar( $uid, $name ); ?>
                                        <?php echo esc_html( $name ); ?>
                                        <?php if ( ! empty( $player['is_captain'] ) ) : ?>
                                            <span class="fmdb-captain-badge">C</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html( $player['position'] ); ?></td>
                                    <td><?php echo esc_html( $player['role'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p class="fmdb-empty">No hay jugadores registrados aún.</p>
                <?php endif; ?>
            </div>

            <!-- Resultados -->
            <div id="tab-resultados" class="fmdb-tab-panel">
                <?php if ( $results ) : ?>
                    <table class="fmdb-results-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Rival</th>
                                <th>Evento</th>
                                <th>Marcador</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $results as $r ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $r['date'] ); ?></td>
                                    <td><?php echo esc_html( $r['opponent'] ); ?></td>
                                    <td><?php echo esc_html( $r['event'] ); ?></td>
                                    <td><?php echo esc_html( $r['score'] ); ?></td>
                                    <td>
                                        <span class="fmdb-outcome fmdb-outcome--<?php echo esc_attr( strtolower( $r['outcome'] ) ); ?>">
                                            <?php echo $r['outcome'] === 'W' ? 'Victoria' : 'Derrota'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p class="fmdb-empty">No hay resultados registrados aún.</p>
                <?php endif; ?>
            </div>

            <!-- Acerca -->
            <div id="tab-acerca" class="fmdb-tab-panel">
                <div class="fmdb-team-about">
                    <?php if ( $description ) : ?>
                        <p><?php echo nl2br( esc_html( $description ) ); ?></p>
                    <?php endif; ?>
                    <ul class="fmdb-team-detail-list">
                        <?php if ( $league )   : ?><li><strong>Liga:</strong> <?php if ( $league_url ) : ?><a href="<?php echo esc_url( $league_url ); ?>"><?php echo esc_html( $league ); ?></a><?php else : ?><?php echo esc_html( $league ); ?><?php endif; ?></li><?php endif; ?>
                        <?php if ( $founded )  : ?><li><strong>Fundado:</strong> <?php echo esc_html( $founded ); ?></li><?php endif; ?>
                        <?php if ( $fmdb_id )  : ?><li><strong>ID FMDB:</strong> <?php echo esc_html( $fmdb_id ); ?></li><?php endif; ?>
                        <?php if ( $email )    : ?><li><strong>Contacto:</strong> <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li><?php endif; ?>
                    </ul>
                    <?php if ( $instagram || $facebook ) : ?>
                        <div class="fmdb-team-social">
                            <?php if ( $instagram ) : ?><a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
                            <?php if ( $facebook )  : ?><a href="<?php echo esc_url( $facebook ); ?>"  target="_blank" rel="noopener">Facebook</a><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

</main>

<script>
document.querySelectorAll('.fmdb-tab-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.fmdb-tab-btn').forEach(function(b) { b.classList.remove('active'); });
        document.querySelectorAll('.fmdb-tab-panel').forEach(function(p) { p.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});
</script>

<?php endwhile; ?>

<?php get_footer(); ?>
