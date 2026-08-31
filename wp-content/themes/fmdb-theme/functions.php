<?php
/**
 * FMDB Theme bootstrap. All functionality lives under inc/.
 * helpers.php is loaded first because other files call its functions
 * (fmdb_mexican_states, fmdb_is_team_manager) from hook closures.
 */

$fmdb_inc = get_stylesheet_directory() . '/inc/';
foreach ( [
    'helpers',
    'assets',
    'cpt',
    'ranking-data',
    'roles',
    'templates',
    'acf-fields',
    'cmb2-fields',
    'events',
    'woocommerce',
    'tournament-registration',
    'tournament-admin',
    'nav',
    'login',
    'email-verification',
    'affiliation-verification',
    'analytics',
    'ga4-api',
    'cli-monthly-report',
    'shortcodes',
    'misc',
] as $fmdb_file ) {
    require_once $fmdb_inc . $fmdb_file . '.php';
}
unset( $fmdb_inc, $fmdb_file );

// Dev-only fixture data — remove before deploying to production.
if ( file_exists( get_stylesheet_directory() . '/inc/dev-fixtures.php' ) ) {
    require_once get_stylesheet_directory() . '/inc/dev-fixtures.php';
}
