<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * One-time repair: assign Foam / Cloth to paired team registrations that
 * share the same name + rama + categoria and have an empty Modalidad.
 * Lower order_id (earlier) → Foam; higher → Cloth.
 * Delete this file immediately after running.
 */

$event_id = 184;

$orders = wc_get_orders( [
    'meta_key'   => '_fmdb_reg_event_id',
    'meta_value' => (string) $event_id,
    'status'     => [ 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-completed' ],
    'limit'      => -1,
] );

$by_key = [];
foreach ( $orders as $order ) {
    if ( ( $order->get_meta( '_fmdb_reg_type' ) ?: 'team' ) !== 'team' ) continue;
    foreach ( $order->get_items() as $item ) {
        $equipo = trim( (string) $item->get_meta( 'Equipo' ) );
        if ( ! $equipo ) continue;
        $key = mb_strtolower( $equipo )
             . '|' . $item->get_meta( 'Rama' )
             . '|' . $item->get_meta( 'Categoría' );
        $by_key[ $key ][] = [
            'order_id'  => $order->get_id(),
            'item'      => $item,
            'modalidad' => (string) $item->get_meta( 'Modalidad' ),
        ];
        break;
    }
}

$patched = 0;
foreach ( $by_key as $key => $entries ) {
    if ( count( $entries ) !== 2 ) {
        if ( count( $entries ) > 2 ) {
            echo "[skip] {$key} — " . count( $entries ) . " matches, expected exactly 2\n";
        }
        continue;
    }
    $has_gap = (bool) array_filter( $entries, fn( $e ) => $e['modalidad'] === '' );
    if ( ! $has_gap ) {
        echo "[ok]   {$key} — both already set, no change\n";
        continue;
    }
    usort( $entries, fn( $a, $b ) => $a['order_id'] <=> $b['order_id'] );
    foreach ( [ 'Foam', 'Cloth' ] as $i => $m ) {
        $entries[ $i ]['item']->update_meta_data( 'Modalidad', $m );
        $entries[ $i ]['item']->save();
        echo "[patch] order #{$entries[$i]['order_id']} ({$key}) → {$m}\n";
        $patched++;
    }
}

echo "\nDone. Patched {$patched} item(s).\n";
