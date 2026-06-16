<?php
/**
 * Google Analytics 4 — gtag.js injection.
 *
 * The Measurement ID lives in the WP option `fmdb_ga4_id` (e.g. G-XXXXXXXXXX).
 * To enable, set it once on the server:
 *
 *     wp option update fmdb_ga4_id G-XXXXXXXXXX
 *
 * Admins/editors are excluded from tracking so internal traffic doesn't skew
 * the numbers; logged-out visitors and members get the snippet.
 *
 * When Complianz is active we emit the gtag tags pre-blocked
 * (`type="text/plain"` + `data-category="statistics"` + `data-src=`) so the
 * cookie consent banner gates them. Complianz rewrites them to executable
 * <script src=...> tags after the visitor opts into statistics cookies.
 * If Complianz is ever disabled the tags fall back to raw script execution.
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
        <!-- Google tag (gtag.js) — gated by Complianz (statistics consent) -->
        <script async type="text/plain" data-category="statistics" data-src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga_id ); ?>"></script>
        <script type="text/plain" data-category="statistics">
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo $id; ?>');
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
