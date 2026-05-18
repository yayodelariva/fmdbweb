<?php
/**
 * Primary nav menu injection: Ranking, Tienda, Afiliación,
 * Organigrama dropdown, Iniciar sesión / profile pill, and cart icon.
 * Also: the footer JS that toggles the profile dropdown.
 */

// Inject profile pill into primary nav when logged in
add_filter( 'wp_nav_menu_items', function ( $items, $args ) {
    if ( ! isset( $args->theme_location ) || $args->theme_location !== 'primary' ) return $items;

    // Organigrama (with nested submenus) + Ranking
    $org_url        = home_url( '/organigrama/' );
    $consejo_url    = home_url( '/organigrama/consejo-directivo/' );
    $comisiones_url = home_url( '/organigrama/comisiones/' );
    $com_sel_url    = home_url( '/organigrama/comisiones/comision-selecciones-nacionales/' );
    $com_arb_url    = home_url( '/organigrama/comisiones/comision-arbitraje-jueceo/' );
    $com_evt_url    = home_url( '/organigrama/comisiones/comision-eventos/' );
    $asoc_url       = home_url( '/organigrama/asociaciones/' );
    $clubes_url     = home_url( '/organigrama/clubes/' );
    $ranking_url    = home_url( '/ranking/' );

    $items .= '<li class="menu-item fmdb-nav-ranking">'
        . '<a href="' . esc_url( $ranking_url ) . '">Ranking</a>'
        . '</li>';

    $tienda_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
    $items .= '<li class="menu-item fmdb-nav-tienda">'
        . '<a href="' . esc_url( $tienda_url ) . '">Tienda</a>'
        . '</li>';
    $items .= '<li class="menu-item fmdb-nav-afiliacion">'
        . '<a href="https://dodgeball.mx/login" target="_blank" rel="noopener noreferrer">Afiliación</a>'
        . '</li>';

    $items .= '<li class="menu-item menu-item-has-children fmdb-nav-organigrama">'
        . '<a href="' . esc_url( $org_url ) . '">Organigrama <span class="fmdb-nav-caret" aria-hidden="true">&#9662;</span></a>'
        . '<ul class="sub-menu fmdb-nav-submenu">'
            . '<li class="menu-item"><a href="' . esc_url( $consejo_url ) . '">Consejo directivo</a></li>'
            . '<li class="menu-item menu-item-has-children fmdb-nav-comisiones">'
                . '<a href="' . esc_url( $comisiones_url ) . '">Comisiones <span class="fmdb-nav-caret fmdb-nav-caret--right" aria-hidden="true">&#9656;</span></a>'
                . '<ul class="sub-menu fmdb-nav-submenu fmdb-nav-submenu--nested">'
                    . '<li class="menu-item"><a href="' . esc_url( $com_sel_url ) . '">Comisión de selecciones nacionales</a></li>'
                    . '<li class="menu-item"><a href="' . esc_url( $com_arb_url ) . '">Comisión de arbitraje y jueceo</a></li>'
                    . '<li class="menu-item"><a href="' . esc_url( $com_evt_url ) . '">Comisión de eventos</a></li>'
                . '</ul>'
            . '</li>'
            . '<li class="menu-item"><a href="' . esc_url( $asoc_url ) . '">Asociaciones</a></li>'
            . '<li class="menu-item"><a href="' . esc_url( $clubes_url ) . '">Clubes</a></li>'
        . '</ul>'
        . '</li>';

    // Build cart icon once — appended last so it stays rightmost
    $cart_li = '';
    if ( function_exists( 'WC' ) && WC()->cart ) {
        $cart_count = WC()->cart->get_cart_contents_count();
        $cart_url   = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
        $cart_li    = '<li class="fmdb-nav-cart">'
            . '<a href="' . esc_url( $cart_url ) . '" class="fmdb-nav-cart__link" aria-label="Carrito de compras">'
            . '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>'
            . '<span class="fmdb-nav-cart__count' . ( $cart_count ? ' has-items' : '' ) . '">' . $cart_count . '</span>'
            . '</a>'
            . '</li>';
    }

    if ( ! is_user_logged_in() ) {
        $items .= '<li class="fmdb-nav-login">'
            . '<a href="' . esc_url( wp_login_url( home_url( '/mi-equipo/' ) ) ) . '" class="fmdb-nav-login__link">Iniciar sesión</a>'
            . '</li>';
        return $items . $cart_li;
    }

    $user    = wp_get_current_user();
    $initial = esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) );
    $name    = esc_html( $user->display_name );
    $logout  = esc_url( wp_logout_url( home_url( '/' ) ) );
    $dash    = esc_url( home_url( '/mi-equipo/' ) );
    $perfil  = esc_url( home_url( '/mi-perfil/' ) );

    $pic_id  = get_user_meta( $user->ID, 'fmdb_profile_picture', true );
    $pic_url = $pic_id ? wp_get_attachment_image_url( (int) $pic_id, 'thumbnail' ) : '';
    $avatar  = $pic_url
        ? '<img src="' . esc_url( $pic_url ) . '" class="fmdb-nav-profile__avatar fmdb-nav-profile__avatar--img" alt="">'
        : '<span class="fmdb-nav-profile__avatar">' . $initial . '</span>';

    $items .= '<li class="fmdb-nav-profile">'
        . '<button type="button" class="fmdb-nav-profile__toggle" aria-expanded="false" aria-haspopup="true">'
        . $avatar
        . '<span class="fmdb-nav-profile__name">' . $name . '</span>'
        . '<span class="fmdb-nav-profile__caret" aria-hidden="true">&#9662;</span>'
        . '</button>'
        . '<ul class="fmdb-nav-profile__dropdown" role="menu">'
        . '<li role="none"><a href="' . $perfil . '" role="menuitem">Mi Perfil</a></li>'
        . '<li role="none"><a href="' . $dash . '" role="menuitem">Mis Equipos</a></li>'
        . '<li class="fmdb-nav-profile__dropdown-divider" role="none"></li>'
        . '<li role="none"><a href="' . $logout . '" class="fmdb-nav-profile__dropdown-logout" role="menuitem">Cerrar Sesión</a></li>'
        . '</ul>'
        . '</li>';

    return $items . $cart_li;
}, 10, 2 );

// Dropdown toggle for nav profile
add_action( 'wp_footer', function () {
    if ( ! is_user_logged_in() ) return;
    ?>
    <script>
    (function () {
        document.addEventListener('click', function (e) {
            var toggle  = e.target.closest('.fmdb-nav-profile__toggle');
            var profile = document.querySelector('.fmdb-nav-profile');
            if (!profile) return;
            if (toggle) {
                var open = profile.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            } else if (!e.target.closest('.fmdb-nav-profile')) {
                profile.classList.remove('is-open');
                var t = profile.querySelector('.fmdb-nav-profile__toggle');
                if (t) t.setAttribute('aria-expanded', 'false');
            }
        });
    })();
    </script>
    <?php
} );
