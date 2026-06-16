<?php
/**
 * WP-CLI command: `wp fmdb-report monthly [--month=YYYY-MM]`
 *
 * Aggregates all report sections automatically:
 *   1. Traffic        — GA4 Data API (requires service account credentials)
 *   2. Membership     — WP user data
 *   3. Editorial      — WP posts + GA4 page views
 *   4. Shop           — WooCommerce orders
 *   5. Events         — Events CPT
 *   6. Stability      — placeholder (manual)
 *   7. Implementations — git log
 *
 * Setup (run once on the server):
 *
 *     wp option update fmdb_ga4_property_id 541893240
 *     wp option update fmdb_ga4_credentials_path /path/to/service-account.json
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

class FMDB_Monthly_Report_Command {

    private ?FMDB_GA4_API $ga4 = null;

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

        $start_date = $month_arg . '-01';
        $end_date   = gmdate( 'Y-m-d', strtotime( 'last day of ' . $month_arg . '-01' ) );

        $prev_month      = gmdate( 'Y-m', strtotime( $start . ' -1 month' ) );
        $prev_start_date = $prev_month . '-01';
        $prev_end_date   = gmdate( 'Y-m-d', strtotime( 'last day of ' . $prev_month . '-01' ) );

        $this->init_ga4();

        WP_CLI::log( "\n=== FMDB · Reporte mensual · {$label} ===\n" );

        $this->section_traffic( $start_date, $end_date, $prev_start_date, $prev_end_date );
        $this->section_membership( $start, $end );
        $this->section_geographic( $start, $end );
        $this->section_editorial( $start, $end, $start_date, $end_date );
        $this->section_shop( $start, $end );
        $this->section_events( $start, $end );
        $this->section_stability();
        $this->section_implementations( $start_date, $end_date );

        WP_CLI::log( "\n(Solo la sección 6 · Estabilidad necesita datos manuales.)\n" );
    }

    /* ------------------------------------------------------------------ */
    /*  GA4                                                                */
    /* ------------------------------------------------------------------ */

    private function init_ga4(): void {
        if ( ! class_exists( 'FMDB_GA4_API' ) ) {
            WP_CLI::warning( 'FMDB_GA4_API class not found — skipping GA4 sections.' );
            return;
        }
        $this->ga4 = FMDB_GA4_API::from_options();
        if ( ! $this->ga4 ) {
            WP_CLI::warning( 'GA4 credentials not configured. Run: wp option update fmdb_ga4_credentials_path /path/to/json && wp option update fmdb_ga4_property_id YOUR_ID' );
            return;
        }
        if ( ! $this->ga4->authenticate() ) {
            WP_CLI::warning( 'GA4 authentication failed — check credentials file and openssl extension.' );
            $this->ga4 = null;
        }
    }

    /* ------------------------------------------------------------------ */
    /*  1. Traffic                                                         */
    /* ------------------------------------------------------------------ */

    private function section_traffic( string $start, string $end, string $prev_start, string $prev_end ): void {
        WP_CLI::log( "## 1. Alcance y tráfico\n" );

        if ( ! $this->ga4 ) {
            WP_CLI::log( '(GA4 no configurado — completar manualmente desde analytics.google.com.)' );
            WP_CLI::log( '' );
            return;
        }

        $date_ranges = [
            [ 'startDate' => $start, 'endDate' => $end ],
            [ 'startDate' => $prev_start, 'endDate' => $prev_end ],
        ];

        $overview = $this->ga4->run_report( [
            'dateRanges' => $date_ranges,
            'metrics'    => [
                [ 'name' => 'sessions' ],
                [ 'name' => 'totalUsers' ],
            ],
        ] );
        $cur_totals  = $this->ga4->totals( $overview );
        $prev_totals = $this->totals_range( $overview, 1 );

        $sessions_cur  = $cur_totals[0]  ?? 0;
        $sessions_prev = $prev_totals[0] ?? 0;
        $users_cur     = $cur_totals[1]  ?? 0;
        $users_prev    = $prev_totals[1] ?? 0;

        WP_CLI::log( sprintf( '- Visitas totales:      %s  (%s)', number_format( $sessions_cur ), $this->pct_change( $sessions_cur, $sessions_prev ) ) );
        WP_CLI::log( sprintf( '- Visitantes únicos:    %s  (%s)', number_format( $users_cur ), $this->pct_change( $users_cur, $users_prev ) ) );

        $devices = $this->ga4->pluck( $this->ga4->run_report( [
            'dateRanges' => [ $date_ranges[0] ],
            'dimensions' => [ [ 'name' => 'deviceCategory' ] ],
            'metrics'    => [ [ 'name' => 'sessions' ] ],
        ] ) );
        $total_dev = array_sum( $devices ) ?: 1;
        $mobile  = ( $devices['mobile'] ?? 0 ) + ( $devices['tablet'] ?? 0 );
        $desktop = $devices['desktop'] ?? 0;
        WP_CLI::log( sprintf( '- Mobile / Desktop:     %.0f%% / %.0f%%', $mobile / $total_dev * 100, $desktop / $total_dev * 100 ) );

        $nvr = $this->ga4->pluck( $this->ga4->run_report( [
            'dateRanges' => [ $date_ranges[0] ],
            'dimensions' => [ [ 'name' => 'newVsReturning' ] ],
            'metrics'    => [ [ 'name' => 'totalUsers' ] ],
        ] ) );
        $total_nvr = array_sum( $nvr ) ?: 1;
        WP_CLI::log( sprintf( '- Nuevos / Recurrentes: %.0f%% / %.0f%%', ( $nvr['new'] ?? 0 ) / $total_nvr * 100, ( $nvr['returning'] ?? 0 ) / $total_nvr * 100 ) );

        $top_pages = $this->ga4->run_report( [
            'dateRanges' => [ $date_ranges[0] ],
            'dimensions' => [ [ 'name' => 'pagePath' ] ],
            'metrics'    => [ [ 'name' => 'screenPageViews' ] ],
            'limit'      => 5,
            'orderBys'   => [ [ 'metric' => [ 'metricName' => 'screenPageViews' ], 'desc' => true ] ],
        ] );
        WP_CLI::log( "\n### Top 5 páginas más vistas" );
        $i = 1;
        foreach ( ( $top_pages['rows'] ?? [] ) as $row ) {
            $path  = $row['dimensionValues'][0]['value'] ?? '';
            $views = number_format( (float) ( $row['metricValues'][0]['value'] ?? 0 ) );
            WP_CLI::log( sprintf( '%d. %s — %s vistas', $i++, $path, $views ) );
        }

        $sources = $this->ga4->pluck( $this->ga4->run_report( [
            'dateRanges' => [ $date_ranges[0] ],
            'dimensions' => [ [ 'name' => 'sessionDefaultChannelGrouping' ] ],
            'metrics'    => [ [ 'name' => 'sessions' ] ],
        ] ) );
        arsort( $sources );
        $total_src = array_sum( $sources ) ?: 1;
        WP_CLI::log( "\n### Fuentes de tráfico" );
        foreach ( $sources as $channel => $count ) {
            WP_CLI::log( sprintf( '- %s: %.1f%% (%s)', $channel, $count / $total_src * 100, number_format( $count ) ) );
        }
        WP_CLI::log( '' );
    }

    private function totals_range( ?array $result, int $range_index ): array {
        $vals = [];
        foreach ( ( $result['totals'][ $range_index ]['metricValues'] ?? [] ) as $mv ) {
            $vals[] = (float) ( $mv['value'] ?? 0 );
        }
        return $vals;
    }

    private function pct_change( float $current, float $previous ): string {
        if ( $previous == 0 ) return $current > 0 ? '+∞' : '—';
        $pct = ( $current - $previous ) / $previous * 100;
        $sign = $pct >= 0 ? '+' : '';
        return sprintf( '%s%.1f%%', $sign, $pct );
    }

    /* ------------------------------------------------------------------ */
    /*  2. Membership                                                      */
    /* ------------------------------------------------------------------ */

    private function section_membership( string $start, string $end ): void {
        global $wpdb;
        WP_CLI::log( "## 2. Membresía federativa\n" );

        $new_users   = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->users WHERE user_registered BETWEEN %s AND %s",
            $start, $end
        ) );
        $total_users = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users" );

        $emails_verified = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'fmdb_email_verified' AND meta_value = '1'"
        );
        $emails_pending  = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'fmdb_email_verified' AND meta_value = '0'"
        );

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

    /* ------------------------------------------------------------------ */
    /*  Geographic                                                         */
    /* ------------------------------------------------------------------ */

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

    /* ------------------------------------------------------------------ */
    /*  3. Editorial                                                       */
    /* ------------------------------------------------------------------ */

    private function section_editorial( string $start, string $end, string $start_date, string $end_date ): void {
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

        if ( $this->ga4 ) {
            $views = $this->ga4->run_report( [
                'dateRanges' => [ [ 'startDate' => $start_date, 'endDate' => $end_date ] ],
                'dimensions' => [ [ 'name' => 'pagePath' ] ],
                'metrics'    => [ [ 'name' => 'screenPageViews' ] ],
                'limit'      => 100,
                'orderBys'   => [ [ 'metric' => [ 'metricName' => 'screenPageViews' ], 'desc' => true ] ],
            ] );
            $page_views = $this->ga4->pluck( $views );

            $post_urls = [];
            $all_posts = get_posts( [
                'post_type'      => 'post',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ] );
            foreach ( $all_posts as $p ) {
                $path = wp_parse_url( get_permalink( $p ), PHP_URL_PATH );
                $post_urls[ $path ] = get_the_title( $p );
            }

            $post_views  = [];
            $total_views = 0;
            foreach ( $page_views as $path => $count ) {
                if ( isset( $post_urls[ $path ] ) ) {
                    $post_views[ $path ] = [ 'title' => $post_urls[ $path ], 'views' => $count ];
                    $total_views += $count;
                }
            }
            uasort( $post_views, fn( $a, $b ) => $b['views'] <=> $a['views'] );

            WP_CLI::log( sprintf( "\n- Vistas totales a noticias: %s", number_format( $total_views ) ) );
            if ( $post_views ) {
                $top = array_values( $post_views )[0];
                WP_CLI::log( sprintf( '- Post más leído: "%s" — %s vistas', $top['title'], number_format( $top['views'] ) ) );
            }
        } else {
            WP_CLI::log( "\n> Vistas y 'post más leído' se completan desde GA4." );
        }
        WP_CLI::log( '' );
    }

    /* ------------------------------------------------------------------ */
    /*  4. Shop                                                            */
    /* ------------------------------------------------------------------ */

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
        $products = [];
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
    }

    /* ------------------------------------------------------------------ */
    /*  5. Events                                                          */
    /* ------------------------------------------------------------------ */

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
        WP_CLI::log( sprintf( '- Eventos creados:      %d', count( $created ) ) );
        WP_CLI::log( sprintf( '- Eventos actualizados: %d', count( $updated ) ) );
        WP_CLI::log( sprintf( '- Activos al cierre:    %d', count( $active ) ) );
        WP_CLI::log( '' );
    }

    /* ------------------------------------------------------------------ */
    /*  6. Stability (manual)                                              */
    /* ------------------------------------------------------------------ */

    private function section_stability(): void {
        WP_CLI::log( "## 6. Estabilidad\n" );
        WP_CLI::log( '(Completar manualmente: bugs reportados, resueltos, pendientes al cierre.)' );
        WP_CLI::log( '' );
    }

    /* ------------------------------------------------------------------ */
    /*  7. Implementations (git log)                                       */
    /* ------------------------------------------------------------------ */

    private function section_implementations( string $start_date, string $end_date ): void {
        WP_CLI::log( "## 7. Implementaciones del mes\n" );

        $dir = realpath( get_stylesheet_directory() ) ?: get_stylesheet_directory();
        while ( $dir !== '/' && ! is_dir( $dir . '/.git' ) ) {
            $dir = dirname( $dir );
        }
        if ( ! is_dir( $dir . '/.git' ) ) {
            WP_CLI::log( '(No se encontró repositorio git.)' );
            return;
        }

        $next_day = gmdate( 'Y-m-d', strtotime( $end_date . ' +1 day' ) );
        $cmd      = sprintf(
            'git -C %s log --since=%s --until=%s --oneline --no-merges 2>/dev/null',
            escapeshellarg( $dir ),
            escapeshellarg( $start_date ),
            escapeshellarg( $next_day )
        );
        $output = shell_exec( $cmd );

        if ( ! $output || trim( $output ) === '' ) {
            WP_CLI::log( '- Sin commits en el período.' );
            WP_CLI::log( '' );
            return;
        }

        $feats  = [];
        $fixes  = [];
        $others = [];
        foreach ( explode( "\n", trim( $output ) ) as $line ) {
            if ( ! $line ) continue;
            $msg = preg_replace( '/^[a-f0-9]+\s+/', '', $line );
            if ( preg_match( '/^feat/i', $msg ) ) {
                $feats[] = $msg;
            } elseif ( preg_match( '/^fix/i', $msg ) ) {
                $fixes[] = $msg;
            } else {
                $others[] = $msg;
            }
        }

        if ( $feats ) {
            WP_CLI::log( '### Nuevas funcionalidades' );
            foreach ( $feats as $f ) WP_CLI::log( '- ' . $f );
        }
        if ( $fixes ) {
            WP_CLI::log( '### Correcciones' );
            foreach ( $fixes as $f ) WP_CLI::log( '- ' . $f );
        }
        if ( $others ) {
            WP_CLI::log( '### Otros' );
            foreach ( $others as $f ) WP_CLI::log( '- ' . $f );
        }
        WP_CLI::log( '' );
    }
}

WP_CLI::add_command( 'fmdb-report', 'FMDB_Monthly_Report_Command' );
