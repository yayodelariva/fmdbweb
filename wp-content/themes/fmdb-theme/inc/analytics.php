<?php
/**
 * Google Analytics 4 — gtag.js injection.
 *
 * The Measurement ID lives in the WP option `fmdb_ga4_id` (e.g. G-XXXXXXXXXX).
 * Set it once on the server:
 *
 *     wp option update fmdb_ga4_id G-XXXXXXXXXX
 *
 * Admins/editors are excluded so internal traffic doesn't skew the numbers.
 *
 * Consent gating: when Complianz is active we DO NOT pre-block the script via
 * type="text/plain" + data-src — Complianz only rewrites those tags reliably
 * for services registered in its Integrations panel, and GA isn't one of them.
 * Instead we inject gtag.js ourselves once Complianz signals statistics
 * consent (either already granted at page load or granted live via the
 * `cmplz_event_statistics` DOM event). If Complianz isn't installed we
 * inject unconditionally.
 */

add_action( 'wp_head', function () {
    $ga_id = trim( (string) get_option( 'fmdb_ga4_id', '' ) );
    if ( $ga_id === '' ) return;
    if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) return;
    if ( ! preg_match( '/^G-[A-Z0-9]+$/i', $ga_id ) ) return;

    $id            = esc_js( $ga_id );
    $has_complianz = function_exists( 'cmplz_get_value' )
        || defined( 'cmplz_version' )
        || function_exists( 'cmplz_init' );

    if ( $has_complianz ) {
        ?>
        <!-- Google tag (gtag.js) — loaded after Complianz statistics consent -->
        <script>
        (function () {
            var GA_ID = '<?php echo $id; ?>';
            function fmdbLoadGA4() {
                if (window.__fmdb_ga4_loaded) return;
                window.__fmdb_ga4_loaded = true;
                var s = document.createElement('script');
                s.async = true;
                s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_ID;
                document.head.appendChild(s);
                window.dataLayer = window.dataLayer || [];
                window.gtag = function () { dataLayer.push(arguments); };
                gtag('js', new Date());
                gtag('config', GA_ID);
            }
            if (typeof cmplz_has_consent === 'function' && cmplz_has_consent('statistics')) {
                fmdbLoadGA4();
            }
            document.addEventListener('cmplz_event_statistics', function (e) {
                if (e.detail && e.detail.value === 'allow') fmdbLoadGA4();
            });
        })();
        </script>
        <?php
    } else {
        ?>
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga_id ); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo $id; ?>');
        </script>
        <?php
    }
}, 1 );
