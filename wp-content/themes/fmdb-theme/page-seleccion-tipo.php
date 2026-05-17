<?php
/**
 * Template Name: Selección — Tipo de Balón
 * Used by /selecciones/foam/ and /selecciones/cloth/
 */
get_header();

$ball_slug  = get_post_field( 'post_name', get_the_ID() ); // 'foam' or 'cloth'
$ball_label = ucfirst( $ball_slug );                        // 'Foam' or 'Cloth'
$categories = [ 'Varonil', 'Femenil', 'Mixto' ];

$ball_icons = [
    'foam'  => '<svg width="32" height="32" viewBox="0 0 22 22" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="9.5" fill="currentColor" fill-opacity="0.18" stroke="currentColor" stroke-width="1.5"/><circle cx="8" cy="8.5" r="2.8" fill="currentColor" fill-opacity="0.32"/><circle cx="14" cy="13.5" r="2" fill="currentColor" fill-opacity="0.22"/></svg>',
    'cloth' => '<svg width="32" height="32" viewBox="0 0 22 22" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="9.5" stroke="currentColor" stroke-width="1.5"/><path d="M1.5 11 Q11 4 20.5 11" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><path d="M1.5 11 Q11 18 20.5 11" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>',
];

// Query and group by gender in PHP — single DB hit
$all_members = get_posts( [
    'post_type'      => 'fmdb_seleccion',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
    'meta_query'     => [
        [ 'key' => 'member_ball_type', 'value' => $ball_label ],
    ],
] );

$by_cat = [];
foreach ( $all_members as $m ) {
    $cat = get_field( 'member_seleccion', $m->ID );
    if ( $cat ) $by_cat[ $cat ][] = $m;
}

$parent_url = get_permalink( get_post_field( 'post_parent', get_the_ID() ) );
?>

<main class="fmdb-selecciones">

    <div class="fmdb-selecciones__header">
        <div class="fmdb-seleccion-tipo__icon"><?php echo $ball_icons[ $ball_slug ] ?? ''; ?></div>
        <h1>Selección <?php echo esc_html( $ball_label ); ?></h1>
        <p>Representantes nacionales de dodgeball <?php echo esc_html( $ball_label ); ?></p>
        <a href="<?php echo esc_url( $parent_url ); ?>" class="fmdb-seleccion-tipo__back">← Selecciones</a>
    </div>

    <div class="fmdb-selecciones__tabs" role="tablist" aria-label="Categoría">
        <?php foreach ( $categories as $i => $cat ) :
            $count = count( $by_cat[ $cat ] ?? [] );
        ?>
            <button class="fmdb-selecciones__tab<?php echo $i === 0 ? ' active' : ''; ?>"
                    data-tab="<?php echo esc_attr( sanitize_title( $cat ) ); ?>"
                    role="tab"
                    aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"
                    type="button">
                <?php echo esc_html( $cat ); ?>
                <span class="fmdb-tab-count"><?php echo $count; ?></span>
            </button>
        <?php endforeach; ?>
    </div>

    <?php foreach ( $categories as $i => $cat ) :
        $members = $by_cat[ $cat ] ?? [];
    ?>
        <div class="fmdb-seleccion-panel<?php echo $i === 0 ? ' active' : ''; ?>"
             data-panel="<?php echo esc_attr( sanitize_title( $cat ) ); ?>"
             role="tabpanel">

            <?php if ( empty( $members ) ) : ?>
                <p class="fmdb-empty">No hay miembros registrados en esta categoría aún.</p>
            <?php else : ?>
                <div class="fmdb-seleccion-grid">
                    <?php foreach ( $members as $member ) :
                        $position    = get_field( 'member_position', $member->ID );
                        $number      = get_field( 'member_number',   $member->ID );
                        $club_obj    = get_field( 'member_club',     $member->ID );
                        $club_name   = ( $club_obj instanceof WP_Post ) ? $club_obj->post_title : '';
                        $club_url    = ( $club_obj instanceof WP_Post ) ? get_permalink( $club_obj->ID ) : '';
                        $member_uid  = get_field( 'member_user', $member->ID );
                        $member_user = $member_uid ? get_userdata( (int) $member_uid ) : null;
                        $name        = $member_user ? $member_user->display_name : $member->post_title;
                        $words       = array_filter( explode( ' ', $name ) );
                        $initials    = substr( implode( '', array_map( fn( $w ) => strtoupper( $w[0] ), $words ) ), 0, 2 );
                        $pic_id      = $member_user ? get_user_meta( $member_user->ID, 'fmdb_profile_picture', true ) : 0;
                        $thumb       = $pic_id
                            ? wp_get_attachment_image_url( (int) $pic_id, 'medium' )
                            : get_the_post_thumbnail_url( $member->ID, 'medium' );
                    ?>
                        <div class="fmdb-member-card">
                            <div class="fmdb-member-card__photo">
                                <?php if ( $thumb ) : ?>
                                    <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $member->post_title ); ?>">
                                <?php else : ?>
                                    <span><?php echo esc_html( $initials ); ?></span>
                                <?php endif; ?>
                                <?php if ( $position ) : ?>
                                    <span class="fmdb-member-card__pos-badge fmdb-member-card__pos-badge--<?php echo esc_attr( sanitize_title( $position ) ); ?>">
                                        <?php echo esc_html( $position ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="fmdb-member-card__info">
                                <?php if ( $number !== '' && $number !== null && $number !== false ) : ?>
                                    <span class="fmdb-member-card__number">#<?php echo esc_html( $number ); ?></span>
                                <?php endif; ?>
                                <strong class="fmdb-member-card__name"><?php echo esc_html( $name ); ?></strong>
                                <?php if ( $club_name ) : ?>
                                    <div class="fmdb-member-card__club">
                                        <?php if ( $club_url ) : ?>
                                            <a href="<?php echo esc_url( $club_url ); ?>"><?php echo esc_html( $club_name ); ?></a>
                                        <?php else : ?>
                                            <?php echo esc_html( $club_name ); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

</main>

<script>
(function () {
    'use strict';
    var tabs   = document.querySelectorAll('.fmdb-selecciones__tab');
    var panels = document.querySelectorAll('.fmdb-seleccion-panel');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.dataset.tab;
            tabs.forEach(function (t) { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
            panels.forEach(function (p) { p.classList.remove('active'); });
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
            var panel = document.querySelector('.fmdb-seleccion-panel[data-panel="' + target + '"]');
            if (panel) panel.classList.add('active');
        });
    });
})();
</script>

<?php get_footer(); ?>
