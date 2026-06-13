<?php
/**
 * Primary nav menu injection: Ranking, Tienda, Afiliación,
 * Organigrama dropdown, Iniciar sesión / profile pill, and cart icon.
 * Also: the footer JS that toggles the profile dropdown.
 */

// Strip "Mapa Interactivo" and "Selecciones" from the WP-Admin-managed primary
// menu — they now live inside the injected "El Dodgeball en México" dropdown.
add_filter( 'wp_nav_menu_objects', function ( $items, $args ) {
    if ( ! isset( $args->theme_location ) || $args->theme_location !== 'primary' ) return $items;
    $strip = [ home_url( '/mapa-interactivo/' ), home_url( '/selecciones/' ) ];
    $strip = array_map( 'untrailingslashit', $strip );
    foreach ( $items as $k => $item ) {
        if ( in_array( untrailingslashit( $item->url ), $strip, true ) ) {
            unset( $items[ $k ] );
        }
    }
    return $items;
}, 10, 2 );

// Inject profile pill into primary nav when logged in
add_filter( 'wp_nav_menu_items', function ( $items, $args ) {
    if ( ! isset( $args->theme_location ) || $args->theme_location !== 'primary' ) return $items;

    // Organigrama (with nested submenus) + Ranking
    // Note: "Organigrama" and "Comisiones" themselves are non-clickable
    // headings — only their children navigate. Pages /organigrama/ and
    // /organigrama/comisiones/ aren't published.
    $consejo_url    = home_url( '/organigrama/consejo-directivo/' );
    $com_sel_url    = home_url( '/organigrama/comisiones/comision-selecciones-nacionales/' );
    $com_arb_url    = home_url( '/organigrama/comisiones/comision-arbitraje-jueceo/' );
    $com_evt_url    = home_url( '/organigrama/comisiones/comision-eventos/' );
    $asoc_url       = home_url( '/mapa-interactivo/?view=asociaciones' );
    $clubes_url     = home_url( '/mapa-interactivo/?view=equipos' );
    $ligas_url      = home_url( '/mapa-interactivo/?view=ligas' );
    $ranking_url    = home_url( '/ranking/' );
    $mapa_url       = home_url( '/mapa-interactivo/' );
    $selecciones_url = home_url( '/selecciones/' );
    $reglamentos_url = home_url( '/reglamentos/' );

    $tienda_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
    $items .= '<li class="menu-item fmdb-nav-tienda">'
        . '<a href="' . esc_url( $tienda_url ) . '">Tienda</a>'
        . '</li>';
    $items .= '<li class="menu-item fmdb-nav-afiliacion">'
        . '<a href="https://dodgeball.mx/login" target="_blank" rel="noopener noreferrer">Afiliación</a>'
        . '</li>';

    $items .= '<li class="menu-item menu-item-has-children fmdb-nav-organigrama">'
        . '<span class="fmdb-nav-heading" tabindex="0" role="button" aria-haspopup="true">Organigrama <span class="fmdb-nav-caret" aria-hidden="true">&#9662;</span></span>'
        . '<ul class="sub-menu fmdb-nav-submenu">'
            . '<li class="menu-item"><a href="' . esc_url( $consejo_url ) . '">Consejo directivo</a></li>'
            . '<li class="menu-item menu-item-has-children fmdb-nav-comisiones">'
                . '<span class="fmdb-nav-heading" tabindex="0" role="button" aria-haspopup="true">Comisiones <span class="fmdb-nav-caret fmdb-nav-caret--right" aria-hidden="true">&#9656;</span></span>'
                . '<ul class="sub-menu fmdb-nav-submenu fmdb-nav-submenu--nested">'
                    . '<li class="menu-item"><a href="' . esc_url( $com_sel_url ) . '">Comisión de selecciones nacionales</a></li>'
                    . '<li class="menu-item"><a href="' . esc_url( $com_arb_url ) . '">Comisión de arbitraje y jueceo</a></li>'
                    . '<li class="menu-item"><a href="' . esc_url( $com_evt_url ) . '">Comisión de eventos</a></li>'
                . '</ul>'
            . '</li>'
        . '</ul>'
        . '</li>';

    $items .= '<li class="menu-item menu-item-has-children fmdb-nav-mexico">'
        . '<span class="fmdb-nav-heading" tabindex="0" role="button" aria-haspopup="true">El Dodgeball en México <span class="fmdb-nav-caret" aria-hidden="true">&#9662;</span></span>'
        . '<ul class="sub-menu fmdb-nav-submenu">'
            . '<li class="menu-item menu-item-has-children fmdb-nav-mapa">'
                . '<span class="fmdb-nav-heading" tabindex="0" role="button" aria-haspopup="true">Mapa Interactivo <span class="fmdb-nav-caret fmdb-nav-caret--right" aria-hidden="true">&#9656;</span></span>'
                . '<ul class="sub-menu fmdb-nav-submenu fmdb-nav-submenu--nested">'
                    . '<li class="menu-item"><a href="' . esc_url( $asoc_url ) . '">Asociaciones</a></li>'
                    . '<li class="menu-item"><a href="' . esc_url( $ligas_url ) . '">Ligas</a></li>'
                    . '<li class="menu-item"><a href="' . esc_url( $clubes_url ) . '">Equipos</a></li>'
                . '</ul>'
            . '</li>'
            . '<li class="menu-item"><a href="' . esc_url( $selecciones_url ) . '">Selecciones</a></li>'
            . '<li class="menu-item"><a href="' . esc_url( $ranking_url ) . '">Ranking</a></li>'
            . '<li class="menu-item"><a href="' . esc_url( $reglamentos_url ) . '">Reglamentos</a></li>'
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
            . '<span class="fmdb-nav-cart__label">Mi carrito</span>'
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
        // Both the desktop nav and the mobile drawer render their own
        // .fmdb-nav-profile, so target the one we actually clicked (closest)
        // and close *all* of them on outside clicks.
        document.addEventListener('click', function (e) {
            var toggle = e.target.closest('.fmdb-nav-profile__toggle');
            if (toggle) {
                var profile = toggle.closest('.fmdb-nav-profile');
                if (!profile) return;
                var open = profile.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            } else if (!e.target.closest('.fmdb-nav-profile')) {
                document.querySelectorAll('.fmdb-nav-profile').forEach(function (profile) {
                    profile.classList.remove('is-open');
                    var t = profile.querySelector('.fmdb-nav-profile__toggle');
                    if (t) t.setAttribute('aria-expanded', 'false');
                });
            }
        });
    })();
    </script>
    <?php
} );

// Mobile drawer: tap an Organigrama/Comisiones heading to expand its submenu.
// These items are injected as raw HTML, so Kadence's drawer toggle never wraps
// them — we toggle an .is-open class ourselves (CSS reveals the submenu).
add_action( 'wp_footer', function () {
    ?>
    <script>
    (function () {
        // Toggle on pointerup, not click. The headings match :hover rules (for
        // the desktop dropdown), so touch browsers (and Firefox touch sim) treat
        // the first tap as "activate hover" and suppress the first click —
        // requiring a second tap. pointerup fires on the first tap regardless.
        // Capture phase + stopPropagation keeps Kadence's drawer handlers from
        // also reacting to the tap.
        document.addEventListener('pointerup', function (e) {
            var heading = e.target.closest('.fmdb-nav-heading');
            if (!heading || !heading.closest('#mobile-drawer')) return;
            e.preventDefault();
            e.stopPropagation();
            heading.parentElement.classList.toggle('is-open');
        }, true);
    })();
    </script>
    <?php
} );
