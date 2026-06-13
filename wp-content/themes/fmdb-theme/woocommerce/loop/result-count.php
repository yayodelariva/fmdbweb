<?php
/**
 * Result Count — Spanish override.
 *
 * Mirrors WC's default loop/result-count.php template but emits Spanish
 * literals. WC's _n() goes through ngettext_woocommerce, but during the
 * shop loop something (likely Polylang's gettext interceptor) overrides
 * our filter and ships English regardless of locale. Hardcoding here
 * bypasses that path entirely.
 *
 * @var int $total
 * @var int $per_page
 * @var int $current
 */

defined( 'ABSPATH' ) || exit;

if ( ! $total ) {
    return;
}
?>
<p class="woocommerce-result-count" role="status" aria-relevant="all">
<?php
if ( 1 === (int) $total ) {
    echo 'Mostrando el único resultado';
} elseif ( $total <= $per_page || -1 === $per_page ) {
    printf( 'Mostrando los %d resultados', (int) $total );
} else {
    $first = ( $per_page * $current ) - $per_page + 1;
    $last  = min( $total, $per_page * $current );
    printf( 'Mostrando %1$d&ndash;%2$d de %3$d resultados', (int) $first, (int) $last, (int) $total );
}
?>
</p>
