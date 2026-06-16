<?php
/**
 * WP-CLI command: `wp fmdb-report monthly [--month=YYYY-MM]`
 *
 * Aggregates the figures we own server-side for the monthly summary report
 * and prints a paste-ready markdown block. Run on Bluehost:
 *
 *     cd ~/public_html/website_09fb3217
 *     wp fmdb-report monthly --month=2026-05
 *
 * Traffic/source numbers come from GA4 — that section stays a placeholder.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

class FMDB_Monthly_Report_Command {

    /**
     * Prints the monthly summary.
     *
     * ## OPTIONS
     *
     * [--month=<yyyy-mm>]
     * : Month to report on, e.g. 2026-05. Defaults to the previous calendar month.
     *
     * @when after_wp_load
     */
    public function monthly( $args, $assoc_args ) {
        $month_arg = $assoc_args['month'] ?? gmdate( 'Y-m', strtotime( 'first day of last month' ) );
        if ( ! preg_match( '/^\d{4}-\d{2}$/', $month_arg ) ) {
            WP_CLI::error( 'Invalid --month. Use YYYY-MM (e.g. 2026-05).' );
        }
        $start = $month_arg . '-01 00:00:00';
        $end   = gmdate( 'Y-m-d 23:59:59', strtotime( 'last day of ' . $month_arg . '-01' ) );
        $label = ucfirst( strftime( '%B %Y', strtotime( $start ) ) ?: $month_arg );

        WP_CLI::log( "\n=== FMDB · Reporte mensual · {$label} ===\n" );

        $this->section_membership( $start, $end );
        $this->section_geographic( $start, $end );
        $this->section_editorial( $start, $end );
        $this->section_shop( $start, $end );
        $this->section_events( $start, $end );

        WP_CLI::log( "\n(Pega los números en `fmdb-reporte-mensual-template.docx`. Las secciones de tráfico y top-páginas se llenan desde GA4.)\n" );
    }

    private function section_membership( string $start, string $end ): void {
        global $wpdb;
        WP_CLI::log( "## 2. Membresía federativa\n" );

        $new_users = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->users WHERE user_registered BETWEEN %s AND %s",
            $start, $end
        ) );
        $total_users = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users" );

        // Email verification — meta '1' = verified, '0' = pending, absent = grandfathered.
        $emails_verified = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'fmdb_email_verified' AND meta_value = '1'"
        );
        $emails_pending  = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'fmdb_email_verified' AND meta_value = '0'"
        );

        // Affiliation — total + per status (cumulative; we don't timestamp transitions).
        $affil_total    = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'fmdb_affiliation_id' AND meta_value <> ''"
        );
        $affil_verified = $this->count_affiliation_status( 'verified' );
        $affil_rejected = $this->count_affiliation_status( 'rejected' );
        $affil_pending  = $this->count_affiliation_status( 'pending' );

        WP_CLI::log( "- Cuentas nuevas en el mes:           {$new_users}" );
        WP_CLI::log( "- Cuentas totales al cierre:          {$total_users}" );
        WP_CLI::log( "- Correos verificados (acumulado):    {$emails_verified}" );
        WP_CLI::log( "- Correos sin verificar:              {$emails_pending}" );
        WP_CLI::log( "- IDs de afiliación capturados:       {$affil_total}" );
        WP_CLI::log( "  · Verificados:                       {$affil_verified}" );
        WP_CLI::log( "  · Rechazados:                        {$affil_rejected}" );
        WP_CLI::log( "  · En revisión:                       {$affil_pending}" );
        WP_CLI::log( '' );
    }

    private function count_affiliation_status( string $status ): int {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'fmdb_affiliation_status' AND meta_value = %s",
            $status
        ) );
    }

    private function section_geographic( string $start, string $end ): void {
        WP_CLI::log( "### Nuevos registros geográficos\n" );
        foreach ( [
            'fmdb_team'       => [ 'Equipos',      'team_state' ],
            'fmdb_league'     => [ 'Ligas',        'league_state' ],
            'fmdb_asociacion' => [ 'Asociaciones', 'asociacion_state' ],
        ] as $cpt => [ $label, $state_field ] ) {
            $posts = get_posts( [
                'post_type'      => $cpt,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'date_query'     => [ [ 'after' => $start, 'before' => $end, 'inclusive' => true ] ],
                'fields'         => 'ids',
            ] );
            $states = [];
            foreach ( $posts as $pid ) {
                $s = get_field( $state_field, $pid );
                if ( $s ) $states[ $s ] = true;
            }
            $state_list = $states ? implode( ', ', array_keys( $states ) ) : '—';
            WP_CLI::log( sprintf( '- %s: %d (estados: %s)', $label, count( $posts ), $state_list ) );
        }
        WP_CLI::log( '' );
    }

    private function section_editorial( string $start, string $end ): void {
        WP_CLI::log( "## 3. Contenido editorial\n" );
        $posts = get_posts( [
            'post_type'      => 'post',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'date_query'     => [ [ 'after' => $start, 'before' => $end, 'inclusive' => true ] ],
        ] );
        WP_CLI::log( '- Posts publicados en el mes: ' . count( $posts ) );
        if ( $posts ) {
            WP_CLI::log( '  Lista:' );
            foreach ( $posts as $p ) {
                WP_CLI::log( '   · ' . get_the_title( $p ) . ' — ' . get_permalink( $p ) );
            }
        }
        WP_CLI::log( '' );
        WP_CLI::log( '> Vistas y "post más leído" se completan desde GA4.' );
        WP_CLI::log( '' );
    }

    private function section_shop( string $start, string $end ): void {
        WP_CLI::log( "## 4. Tienda\n" );
        if ( ! function_exists( 'wc_get_orders' ) ) {
            WP_CLI::log( '(WooCommerce no está activo en este sitio.)' );
            return;
        }
        $orders = wc_get_orders( [
            'limit'        => -1,
            'date_created' => $start . '...' . $end,
            'status'       => [ 'wc-completed', 'wc-processing', 'wc-on-hold' ],
        ] );

        $count    = count( $orders );
        $revenue  = 0.0;
        $products = []; // product_id => [ name, qty, revenue ]
        foreach ( $orders as $o ) {
            $revenue += (float) $o->get_total();
            foreach ( $o->get_items() as $item ) {
                $pid = $item->get_product_id();
                if ( ! isset( $products[ $pid ] ) ) {
                    $products[ $pid ] = [ 'name' => $item->get_name(), 'qty' => 0, 'rev' => 0.0 ];
                }
                $products[ $pid ]['qty'] += (int)   $item->get_quantity();
                $products[ $pid ]['rev'] += (float) $item->get_total();
            }
        }
        $avg = $count > 0 ? $revenue / $count : 0;
        WP_CLI::log( sprintf( '- Órdenes:          %d', $count ) );
        WP_CLI::log( sprintf( '- Ingresos totales: $%s MXN', number_format( $revenue, 2 ) ) );
        WP_CLI::log( sprintf( '- Ticket promedio:  $%s MXN', number_format( $avg, 2 ) ) );

        uasort( $products, fn( $a, $b ) => $b['qty'] <=> $a['qty'] );
        $top = array_slice( $products, 0, 3, true );
        if ( $top ) {
            WP_CLI::log( "\n### Top 3 productos" );
            $i = 1;
            foreach ( $top as $row ) {
                WP_CLI::log( sprintf( '%d. %s — %d uds — $%s', $i++, $row['name'], $row['qty'], number_format( $row['rev'], 2 ) ) );
            }
        }
        WP_CLI::log( '' );
        WP_CLI::log( '> Tasa de carritos abandonados se completa desde GA4 (o WooCommerce Cart Abandonment plugin).' );
        WP_CLI::log( '' );
    }

    private function section_events( string $start, string $end ): void {
        WP_CLI::log( "## 5. Eventos\n" );
        $cpt = post_type_exists( 'tribe_events' ) ? 'tribe_events' : 'fmdb_event';
        if ( ! post_type_exists( $cpt ) ) {
            WP_CLI::log( '(No hay CPT de eventos registrado.)' );
            return;
        }
        $created = get_posts( [
            'post_type'      => $cpt,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'date_query'     => [ [ 'after' => $start, 'before' => $end, 'inclusive' => true ] ],
            'fields'         => 'ids',
        ] );
        $updated = get_posts( [
            'post_type'      => $cpt,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'date_query'     => [ [ 'column' => 'post_modified', 'after' => $start, 'before' => $end, 'inclusive' => true ] ],
            'fields'         => 'ids',
        ] );
        $active = get_posts( [
            'post_type'      => $cpt,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ] );
        WP_CLI::log( sprintf( '- Eventos creados:    %d', count( $created ) ) );
        WP_CLI::log( sprintf( '- Eventos actualizados: %d', count( $updated ) ) );
        WP_CLI::log( sprintf( '- Activos al cierre:  %d', count( $active ) ) );
        WP_CLI::log( '' );
    }
}

WP_CLI::add_command( 'fmdb-report', 'FMDB_Monthly_Report_Command' );
