<?php
/**
 * WP-CLI command: `wp fmdb-report monthly [--month=YYYY-MM] [--output=<path>]`
 *
 * Aggregates all report sections automatically and prints to terminal.
 * With --output, also writes a styled HTML file ready for print/PDF/Google Docs.
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
     * : Month to report on. Defaults to previous calendar month.
     *
     * [--from=<yyyy-mm-dd>]
     * : Start date for a custom range. Requires --to. Overrides --month.
     *
     * [--to=<yyyy-mm-dd>]
     * : End date (inclusive) for a custom range. Requires --from.
     *
     * [--output=<path>]
     * : Write a styled HTML report to this path.
     *
     * [--email=<address>]
     * : Email the report (PDF attachment) to this address via wp_mail.
     *   Comma-separate for multiple recipients.
     *
     * @when after_wp_load
     */
    public function monthly( $args, $assoc_args ) {
        $meses = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo',  '06' => 'Junio',   '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
        ];

        $from_arg = $assoc_args['from'] ?? '';
        $to_arg   = $assoc_args['to'] ?? '';

        if ( $from_arg !== '' || $to_arg !== '' ) {
            if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from_arg ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to_arg ) ) {
                WP_CLI::error( 'Invalid range. Use --from=YYYY-MM-DD --to=YYYY-MM-DD (both required).' );
            }
            if ( strtotime( $from_arg ) > strtotime( $to_arg ) ) {
                WP_CLI::error( '--from must be on or before --to.' );
            }
            $start_date = $from_arg;
            $end_date   = $to_arg;
            $start      = $from_arg . ' 00:00:00';
            $end        = $to_arg . ' 23:59:59';

            // Previous window: same length, ending the day before --from.
            $days       = (int) round( ( strtotime( $to_arg ) - strtotime( $from_arg ) ) / 86400 ) + 1;
            $prev_end   = gmdate( 'Y-m-d', strtotime( $from_arg . ' -1 day' ) );
            $prev_start = gmdate( 'Y-m-d', strtotime( $prev_end . ' -' . ( $days - 1 ) . ' day' ) );

            $fmt   = function ( $ds ) use ( $meses ) {
                $p = explode( '-', $ds );
                return (int) $p[2] . ' ' . ( $meses[ $p[1] ] ?? $p[1] ) . ' ' . $p[0];
            };
            $label = $fmt( $start_date ) . ' – ' . $fmt( $end_date );
        } else {
            $month_arg = $assoc_args['month'] ?? gmdate( 'Y-m', strtotime( 'first day of last month' ) );
            if ( ! preg_match( '/^\d{4}-\d{2}$/', $month_arg ) ) {
                WP_CLI::error( 'Invalid --month. Use YYYY-MM (e.g. 2026-05).' );
            }
            $start      = $month_arg . '-01 00:00:00';
            $end        = gmdate( 'Y-m-d 23:59:59', strtotime( 'last day of ' . $month_arg . '-01' ) );
            $start_date = $month_arg . '-01';
            $end_date   = gmdate( 'Y-m-d', strtotime( 'last day of ' . $month_arg . '-01' ) );

            $prev_month = gmdate( 'Y-m', strtotime( $start . ' -1 month' ) );
            $prev_start = $prev_month . '-01';
            $prev_end   = gmdate( 'Y-m-d', strtotime( 'last day of ' . $prev_month . '-01' ) );

            $parts = explode( '-', $month_arg );
            $label = ( $meses[ $parts[1] ] ?? $parts[1] ) . ' ' . $parts[0];
        }

        $this->init_ga4();

        $d = [
            'label'           => $label,
            'date'            => $end_date,
            'traffic'         => $this->collect_traffic( $start_date, $end_date, $prev_start, $prev_end ),
            'membership'      => $this->collect_membership( $start, $end ),
            'geographic'      => $this->collect_geographic( $start, $end ),
            'editorial'       => $this->collect_editorial( $start, $end, $start_date, $end_date ),
            'shop'            => $this->collect_shop( $start, $end ),
            'events'          => $this->collect_events( $start, $end ),
            'implementations' => $this->collect_implementations( $start_date, $end_date ),
        ];

        $this->render_text( $d );

        $output = $assoc_args['output'] ?? '';
        if ( $output !== '' ) {
            file_put_contents( $output, $this->render_html( $d ) );
            WP_CLI::success( "Reporte guardado en: {$output}" );
        }

        $email = $assoc_args['email'] ?? '';
        if ( $email !== '' ) {
            $this->email_report( $email, $d );
        }
    }

    /* ================================================================== */
    /*  PDF + email                                                        */
    /* ================================================================== */

    /**
     * Render the report HTML to PDF bytes via Dompdf. Dompdf is installed
     * outside the repo; its autoload path is stored in the fmdb_dompdf_autoload
     * option. Returns null (with a warning) if unavailable.
     */
    private function render_pdf( string $html ): ?string {
        $autoload = trim( (string) get_option( 'fmdb_dompdf_autoload', '' ) );
        if ( $autoload === '' || ! file_exists( $autoload ) ) {
            WP_CLI::warning( 'Dompdf no encontrado (option fmdb_dompdf_autoload). Se adjuntará HTML.' );
            return null;
        }
        require_once $autoload;
        if ( ! class_exists( '\Dompdf\Dompdf' ) ) {
            WP_CLI::warning( 'Dompdf autoload cargado pero la clase no existe. Se adjuntará HTML.' );
            return null;
        }
        $dompdf = new \Dompdf\Dompdf( [ 'isRemoteEnabled' => false, 'defaultFont' => 'Helvetica' ] );
        $dompdf->loadHtml( $html, 'UTF-8' );
        $dompdf->setPaper( 'letter', 'portrait' );
        $dompdf->render();
        return $dompdf->output();
    }

    private function email_report( string $to, array $d ): void {
        $html = $this->render_html( $d );
        $pdf  = $this->render_pdf( $html );

        $slug = 'reporte-' . preg_replace( '/[^0-9a-z\-]/i', '', str_replace( ' ', '', $d['label'] ) );
        $tmp  = trailingslashit( sys_get_temp_dir() ) . $slug . ( $pdf !== null ? '.pdf' : '.html' );
        file_put_contents( $tmp, $pdf ?? $html );

        $subject = 'FMDB · Reporte mensual · ' . $d['label'];
        $body    = "Adjunto el reporte mensual de la FMDB para el período: {$d['label']}.\n";
        $to_list = array_filter( array_map( 'trim', explode( ',', $to ) ) );

        $sent = wp_mail( $to_list, $subject, $body, [ 'Content-Type: text/plain; charset=UTF-8' ], [ $tmp ] );
        @unlink( $tmp );

        if ( $sent ) {
            WP_CLI::success( 'Reporte enviado a: ' . implode( ', ', $to_list ) );
        } else {
            WP_CLI::warning( 'wp_mail devolvió false — revisar configuración SMTP (wp-mail-smtp).' );
        }
    }

    /* ================================================================== */
    /*  GA4 init                                                           */
    /* ================================================================== */

    private function init_ga4(): void {
        if ( ! class_exists( 'FMDB_GA4_API' ) ) {
            WP_CLI::warning( 'FMDB_GA4_API class not found — skipping GA4 sections.' );
            return;
        }
        $this->ga4 = FMDB_GA4_API::from_options();
        if ( ! $this->ga4 ) {
            WP_CLI::warning( 'GA4 not configured. wp option update fmdb_ga4_credentials_path … && wp option update fmdb_ga4_property_id …' );
            return;
        }
        if ( ! $this->ga4->authenticate() ) {
            WP_CLI::warning( 'GA4 auth failed — check credentials + openssl.' );
            $this->ga4 = null;
        }
    }

    /* ================================================================== */
    /*  Data collectors                                                    */
    /* ================================================================== */

    private function collect_traffic( string $s, string $e, string $ps, string $pe ): array {
        $out = [ 'has_ga4' => (bool) $this->ga4 ];
        if ( ! $this->ga4 ) return $out;

        $ranges = [
            [ 'startDate' => $s, 'endDate' => $e ],
            [ 'startDate' => $ps, 'endDate' => $pe ],
        ];

        $ov = $this->ga4->run_report( [ 'dateRanges' => $ranges, 'metrics' => [
            [ 'name' => 'sessions' ], [ 'name' => 'totalUsers' ],
        ] ] );
        if ( $ov === null ) {
            $out['error'] = $this->ga4->last_error() ?? 'Unknown GA4 Data API error.';
            WP_CLI::warning( 'GA4 traffic unavailable — ' . $out['error'] );
            return $out;
        }
        $cur  = $this->range_metrics( $ov, 0 );
        $prev = $this->range_metrics( $ov, 1 );

        $out['sessions']      = [ 'cur' => $cur[0] ?? 0, 'prev' => $prev[0] ?? 0 ];
        $out['users']         = [ 'cur' => $cur[1] ?? 0, 'prev' => $prev[1] ?? 0 ];
        $out['sessions']['change'] = $this->pct_change( $out['sessions']['cur'], $out['sessions']['prev'] );
        $out['users']['change']    = $this->pct_change( $out['users']['cur'], $out['users']['prev'] );

        $devs = $this->ga4->pluck( $this->ga4->run_report( [
            'dateRanges' => [ $ranges[0] ],
            'dimensions' => [ [ 'name' => 'deviceCategory' ] ],
            'metrics'    => [ [ 'name' => 'sessions' ] ],
        ] ) );
        $td = array_sum( $devs ) ?: 1;
        $out['mobile_pct']  = round( ( ( $devs['mobile'] ?? 0 ) + ( $devs['tablet'] ?? 0 ) ) / $td * 100 );
        $out['desktop_pct'] = round( ( $devs['desktop'] ?? 0 ) / $td * 100 );

        $nvr = $this->ga4->pluck( $this->ga4->run_report( [
            'dateRanges' => [ $ranges[0] ],
            'dimensions' => [ [ 'name' => 'newVsReturning' ] ],
            'metrics'    => [ [ 'name' => 'totalUsers' ] ],
        ] ) );
        $tn = array_sum( $nvr ) ?: 1;
        $out['new_pct']       = round( ( $nvr['new'] ?? 0 ) / $tn * 100 );
        $out['returning_pct'] = round( ( $nvr['returning'] ?? 0 ) / $tn * 100 );

        $tp = $this->ga4->run_report( [
            'dateRanges' => [ $ranges[0] ],
            'dimensions' => [ [ 'name' => 'pagePath' ] ],
            'metrics'    => [ [ 'name' => 'screenPageViews' ] ],
            'limit'      => 5,
            'orderBys'   => [ [ 'metric' => [ 'metricName' => 'screenPageViews' ], 'desc' => true ] ],
        ] );
        $out['top_pages'] = [];
        foreach ( ( $tp['rows'] ?? [] ) as $r ) {
            $out['top_pages'][] = [
                'path'  => $r['dimensionValues'][0]['value'] ?? '',
                'views' => (int) ( $r['metricValues'][0]['value'] ?? 0 ),
            ];
        }

        $src = $this->ga4->pluck( $this->ga4->run_report( [
            'dateRanges' => [ $ranges[0] ],
            'dimensions' => [ [ 'name' => 'sessionDefaultChannelGrouping' ] ],
            'metrics'    => [ [ 'name' => 'sessions' ] ],
        ] ) );
        arsort( $src );
        $ts = array_sum( $src ) ?: 1;
        $out['sources'] = [];
        foreach ( $src as $ch => $cnt ) {
            $out['sources'][] = [ 'channel' => $ch, 'pct' => round( $cnt / $ts * 100, 1 ), 'count' => (int) $cnt ];
        }
        return $out;
    }

    private function collect_membership( string $start, string $end ): array {
        global $wpdb;
        return [
            'new'       => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->users WHERE user_registered BETWEEN %s AND %s", $start, $end ) ),
            'total'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users" ),
            'email_ok'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key='fmdb_email_verified' AND meta_value='1'" ),
            'email_no'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key='fmdb_email_verified' AND meta_value='0'" ),
            'affil'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key='fmdb_affiliation_id' AND meta_value<>''" ),
            'a_ok'      => $this->count_affil( 'verified' ),
            'a_no'      => $this->count_affil( 'rejected' ),
            'a_pending' => $this->count_affil( 'pending' ),
        ];
    }

    private function count_affil( string $s ): int {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key='fmdb_affiliation_status' AND meta_value=%s", $s ) );
    }

    private function collect_geographic( string $start, string $end ): array {
        $out = [];
        foreach ( [
            'fmdb_team'       => [ 'Equipos',      'team_state' ],
            'fmdb_league'     => [ 'Ligas',         'league_state' ],
            'fmdb_asociacion' => [ 'Asociaciones',  'asociacion_state' ],
        ] as $cpt => [ $label, $sf ] ) {
            $ids = get_posts( [ 'post_type' => $cpt, 'posts_per_page' => -1, 'post_status' => 'publish',
                'date_query' => [ [ 'after' => $start, 'before' => $end, 'inclusive' => true ] ], 'fields' => 'ids' ] );
            $states = [];
            foreach ( $ids as $id ) { $v = get_field( $sf, $id ); if ( $v ) $states[] = $v; }
            $out[] = [ 'label' => $label, 'count' => count( $ids ), 'states' => $states ? implode( ', ', array_unique( $states ) ) : '—' ];
        }
        return $out;
    }

    private function collect_editorial( string $start, string $end, string $sd, string $ed ): array {
        $posts = get_posts( [ 'post_type' => 'post', 'posts_per_page' => -1, 'post_status' => 'publish',
            'date_query' => [ [ 'after' => $start, 'before' => $end, 'inclusive' => true ] ] ] );
        $list = [];
        foreach ( $posts as $p ) $list[] = [ 'title' => get_the_title( $p ), 'url' => get_permalink( $p ) ];

        $out = [ 'count' => count( $posts ), 'posts' => $list, 'has_ga4' => (bool) $this->ga4, 'total_views' => 0, 'top_post' => null ];
        if ( ! $this->ga4 ) return $out;

        $views = $this->ga4->pluck( $this->ga4->run_report( [
            'dateRanges' => [ [ 'startDate' => $sd, 'endDate' => $ed ] ],
            'dimensions' => [ [ 'name' => 'pagePath' ] ],
            'metrics'    => [ [ 'name' => 'screenPageViews' ] ],
            'limit' => 100, 'orderBys' => [ [ 'metric' => [ 'metricName' => 'screenPageViews' ], 'desc' => true ] ],
        ] ) );

        $urls = [];
        foreach ( get_posts( [ 'post_type' => 'post', 'posts_per_page' => -1, 'post_status' => 'publish' ] ) as $p ) {
            $urls[ wp_parse_url( get_permalink( $p ), PHP_URL_PATH ) ] = get_the_title( $p );
        }
        $best = null; $best_v = 0;
        foreach ( $views as $path => $cnt ) {
            if ( isset( $urls[ $path ] ) ) {
                $out['total_views'] += $cnt;
                if ( $cnt > $best_v ) { $best = $urls[ $path ]; $best_v = $cnt; }
            }
        }
        if ( $best ) $out['top_post'] = [ 'title' => $best, 'views' => (int) $best_v ];
        return $out;
    }

    private function collect_shop( string $start, string $end ): array {
        if ( ! function_exists( 'wc_get_orders' ) ) return [ 'active' => false ];
        $orders = wc_get_orders( [ 'limit' => -1, 'date_created' => $start . '...' . $end,
            'status' => [ 'wc-completed', 'wc-processing', 'wc-on-hold' ] ] );
        $rev = 0.0; $prods = [];
        foreach ( $orders as $o ) {
            $rev += (float) $o->get_total();
            foreach ( $o->get_items() as $it ) {
                $pid = $it->get_product_id();
                if ( ! isset( $prods[$pid] ) ) $prods[$pid] = [ 'name' => $it->get_name(), 'qty' => 0, 'rev' => 0.0 ];
                $prods[$pid]['qty'] += (int)   $it->get_quantity();
                $prods[$pid]['rev'] += (float) $it->get_total();
            }
        }
        uasort( $prods, fn( $a, $b ) => $b['qty'] <=> $a['qty'] );
        $cnt = count( $orders );
        return [
            'active'   => true,
            'orders'   => $cnt,
            'revenue'  => $rev,
            'avg'      => $cnt > 0 ? $rev / $cnt : 0,
            'top'      => array_values( array_slice( $prods, 0, 3, true ) ),
        ];
    }

    private function collect_events( string $start, string $end ): array {
        $cpt = post_type_exists( 'tribe_events' ) ? 'tribe_events' : 'fmdb_event';
        if ( ! post_type_exists( $cpt ) ) return [ 'active' => false ];
        $q = fn( $extra ) => count( get_posts( array_merge( [ 'post_type' => $cpt, 'posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids' ], $extra ) ) );
        return [
            'active'  => true,
            'created' => $q( [ 'date_query' => [ [ 'after' => $start, 'before' => $end, 'inclusive' => true ] ] ] ),
            'updated' => $q( [ 'date_query' => [ [ 'column' => 'post_modified', 'after' => $start, 'before' => $end, 'inclusive' => true ] ] ] ),
            'total'   => $q( [] ),
        ];
    }

    private function collect_implementations( string $sd, string $ed ): array {
        $dir = realpath( get_stylesheet_directory() ) ?: get_stylesheet_directory();
        while ( $dir !== '/' && ! is_dir( $dir . '/.git' ) ) $dir = dirname( $dir );
        if ( ! is_dir( $dir . '/.git' ) ) return [ 'found' => false ];

        $next = gmdate( 'Y-m-d', strtotime( $ed . ' +1 day' ) );
        $raw  = shell_exec( sprintf( 'git -C %s log --since=%s --until=%s --oneline --no-merges 2>/dev/null',
            escapeshellarg( $dir ), escapeshellarg( $sd ), escapeshellarg( $next ) ) );
        $feats = []; $fixes = []; $others = [];
        foreach ( explode( "\n", trim( $raw ?: '' ) ) as $line ) {
            if ( ! $line ) continue;
            $msg = preg_replace( '/^[a-f0-9]+\s+/', '', $line );
            if ( preg_match( '/^feat/i', $msg ) )      $feats[]  = $msg;
            elseif ( preg_match( '/^fix/i', $msg ) )    $fixes[]  = $msg;
            else                                        $others[] = $msg;
        }
        return [ 'found' => true, 'feats' => $feats, 'fixes' => $fixes, 'others' => $others ];
    }

    /* ================================================================== */
    /*  Text renderer (terminal)                                           */
    /* ================================================================== */

    private function render_text( array $d ): void {
        WP_CLI::log( "\n=== FMDB · Reporte mensual · {$d['label']} ===\n" );

        // 1. Traffic
        WP_CLI::log( "## 1. Alcance y tráfico\n" );
        $t = $d['traffic'];
        if ( ! $t['has_ga4'] ) {
            WP_CLI::log( "(GA4 no configurado.)\n" );
        } elseif ( isset( $t['error'] ) ) {
            WP_CLI::log( "(Datos de tráfico no disponibles — error de la GA4 Data API.)" );
            WP_CLI::log( '  ' . $t['error'] . "\n" );
        } else {
            WP_CLI::log( sprintf( '- Visitas totales:      %s  (%s)', number_format( $t['sessions']['cur'] ), $t['sessions']['change'] ) );
            WP_CLI::log( sprintf( '- Visitantes únicos:    %s  (%s)', number_format( $t['users']['cur'] ), $t['users']['change'] ) );
            WP_CLI::log( sprintf( '- Mobile / Desktop:     %d%% / %d%%', $t['mobile_pct'], $t['desktop_pct'] ) );
            WP_CLI::log( sprintf( '- Nuevos / Recurrentes: %d%% / %d%%', $t['new_pct'], $t['returning_pct'] ) );
            if ( $t['top_pages'] ) {
                WP_CLI::log( "\n### Top 5 páginas" );
                foreach ( $t['top_pages'] as $i => $pg ) WP_CLI::log( sprintf( '%d. %s — %s vistas', $i + 1, $pg['path'], number_format( $pg['views'] ) ) );
            }
            if ( $t['sources'] ) {
                WP_CLI::log( "\n### Fuentes de tráfico" );
                foreach ( $t['sources'] as $src ) WP_CLI::log( sprintf( '- %s: %.1f%% (%s)', $src['channel'], $src['pct'], number_format( $src['count'] ) ) );
            }
            WP_CLI::log( '' );
        }

        // 2. Membership
        $m = $d['membership'];
        WP_CLI::log( "## 2. Membresías\n" );
        WP_CLI::log( "- Cuentas nuevas:                     {$m['new']}" );
        WP_CLI::log( "- Cuentas totales al cierre:          {$m['total']}" );
        WP_CLI::log( "- Correos verificados (acumulado):    {$m['email_ok']}" );
        WP_CLI::log( "- Correos sin verificar:              {$m['email_no']}" );
        WP_CLI::log( "- IDs de afiliación capturados:       {$m['affil']}" );
        WP_CLI::log( "  · Verificados:                       {$m['a_ok']}" );
        WP_CLI::log( "  · Rechazados:                        {$m['a_no']}" );
        WP_CLI::log( "  · En revisión:                       {$m['a_pending']}\n" );

        // Geographic
        WP_CLI::log( "### Nuevos registros geográficos\n" );
        foreach ( $d['geographic'] as $g ) WP_CLI::log( sprintf( '- %s: %d (estados: %s)', $g['label'], $g['count'], $g['states'] ) );
        WP_CLI::log( '' );

        // 3. Editorial
        $e = $d['editorial'];
        WP_CLI::log( "## 3. Contenido editorial\n" );
        WP_CLI::log( '- Posts publicados: ' . $e['count'] );
        foreach ( $e['posts'] as $p ) WP_CLI::log( '   · ' . $p['title'] . ' — ' . $p['url'] );
        if ( $e['has_ga4'] ) {
            WP_CLI::log( sprintf( '- Vistas totales a noticias: %s', number_format( $e['total_views'] ) ) );
            if ( $e['top_post'] ) WP_CLI::log( sprintf( '- Post más leído: "%s" — %s vistas', $e['top_post']['title'], number_format( $e['top_post']['views'] ) ) );
        }
        WP_CLI::log( '' );

        // 4. Shop
        $s = $d['shop'];
        WP_CLI::log( "## 4. Tienda\n" );
        if ( ! $s['active'] ) { WP_CLI::log( "(WooCommerce no activo.)\n" ); }
        else {
            WP_CLI::log( sprintf( '- Órdenes:          %d', $s['orders'] ) );
            WP_CLI::log( sprintf( '- Ingresos totales: $%s MXN', number_format( $s['revenue'], 2 ) ) );
            WP_CLI::log( sprintf( '- Ticket promedio:  $%s MXN', number_format( $s['avg'], 2 ) ) );
            if ( $s['top'] ) {
                WP_CLI::log( "\n### Top 3 productos" );
                foreach ( $s['top'] as $i => $p ) WP_CLI::log( sprintf( '%d. %s — %d uds — $%s', $i + 1, $p['name'], $p['qty'], number_format( $p['rev'], 2 ) ) );
            }
            WP_CLI::log( '' );
        }

        // 5. Events
        $ev = $d['events'];
        WP_CLI::log( "## 5. Eventos\n" );
        if ( ! $ev['active'] ) { WP_CLI::log( "(Sin CPT de eventos.)\n" ); }
        else {
            WP_CLI::log( sprintf( '- Creados:      %d', $ev['created'] ) );
            WP_CLI::log( sprintf( '- Actualizados: %d', $ev['updated'] ) );
            WP_CLI::log( sprintf( '- Activos:      %d', $ev['total'] ) );
            WP_CLI::log( '' );
        }

        // 6. Stability
        WP_CLI::log( "## 6. Estabilidad\n" );
        WP_CLI::log( "(Completar manualmente.)\n" );

        // 7. Implementations
        $im = $d['implementations'];
        WP_CLI::log( "## 7. Implementaciones del mes\n" );
        if ( ! $im['found'] || ( ! $im['feats'] && ! $im['fixes'] && ! $im['others'] ) ) {
            WP_CLI::log( "- Sin commits en el período.\n" );
        } else {
            if ( $im['feats'] )  { WP_CLI::log( '### Nuevas funcionalidades' ); foreach ( $im['feats']  as $f ) WP_CLI::log( '- ' . $f ); }
            if ( $im['fixes'] )  { WP_CLI::log( '### Correcciones' );           foreach ( $im['fixes']  as $f ) WP_CLI::log( '- ' . $f ); }
            if ( $im['others'] ) { WP_CLI::log( '### Otros' );                  foreach ( $im['others'] as $f ) WP_CLI::log( '- ' . $f ); }
            WP_CLI::log( '' );
        }

        WP_CLI::log( "(Solo la sección 6 necesita datos manuales.)\n" );
    }

    /* ================================================================== */
    /*  HTML renderer                                                      */
    /* ================================================================== */

    private function render_html( array $d ): string {
        $h = function ( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); };
        $n = fn( $v, $dec = 0 ) => number_format( (float) $v, $dec );

        $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
            . '<title>FMDB &middot; Reporte mensual &middot; ' . $h( $d['label'] ) . '</title>'
            . '<style>'
            . 'body{font-family:Calibri,sans-serif;color:#1a1a1a;line-height:1.5;max-width:820px;margin:40px auto;padding:0 24px}'
            . 'h1{font-size:22pt;color:#085041;margin-bottom:0}'
            . 'h1+p{color:#777;margin-top:4px;font-size:10pt}'
            . 'h2{font-size:14pt;color:#085041;border-bottom:2px solid #1d9e75;padding-bottom:3px;margin-top:32px}'
            . 'h3{font-size:11pt;color:#085041;margin-top:18px}'
            . 'table{border-collapse:collapse;width:100%;margin:8px 0}'
            . 'th,td{border:1px solid #ccc;padding:6px 10px;text-align:left;font-size:10pt}'
            . 'th{background:#f3f7f5;color:#085041;font-weight:700}'
            . '.num{text-align:right}'
            . 'ul,ol{margin:6px 0 12px 18px}'
            . 'blockquote{border-left:3px solid #1d9e75;padding-left:12px;color:#444;font-style:italic;margin:6px 0}'
            . 'hr{border:none;border-top:1px solid #ddd;margin:24px 0}'
            . '.meta{color:#777;font-size:9pt;font-style:italic}'
            . '.badge{display:inline-block;padding:2px 8px;border-radius:3px;font-size:9pt;font-weight:600}'
            . '.up{color:#1b7d3a}.down{color:#c0392b}'
            . '</style></head><body>';

        $html .= '<h1>FMDB &mdash; Reporte mensual &middot; ' . $h( $d['label'] ) . '</h1>';
        $html .= '<p>Federaci&oacute;n Mexicana de Dodgeball &middot; dodgeballmexico.com</p><hr>';

        // 1. Traffic
        $html .= '<h2>1. Alcance y tr&aacute;fico</h2>';
        $t = $d['traffic'];
        if ( $t['has_ga4'] && ! isset( $t['error'] ) ) {
            $html .= '<table><tr><th>M&eacute;trica</th><th>Mes actual</th><th>vs. mes anterior</th></tr>';
            $html .= '<tr><td>Visitas totales</td><td class="num">' . $n( $t['sessions']['cur'] ) . '</td><td class="num">' . $h( $t['sessions']['change'] ) . '</td></tr>';
            $html .= '<tr><td>Visitantes &uacute;nicos</td><td class="num">' . $n( $t['users']['cur'] ) . '</td><td class="num">' . $h( $t['users']['change'] ) . '</td></tr>';
            $html .= '<tr><td>Mobile / Desktop</td><td>' . $t['mobile_pct'] . '% / ' . $t['desktop_pct'] . '%</td><td>&mdash;</td></tr>';
            $html .= '<tr><td>Nuevos / Recurrentes</td><td>' . $t['new_pct'] . '% / ' . $t['returning_pct'] . '%</td><td>&mdash;</td></tr>';
            $html .= '</table>';
            if ( $t['top_pages'] ) {
                $html .= '<h3>Top 5 p&aacute;ginas m&aacute;s vistas</h3><ol>';
                foreach ( $t['top_pages'] as $pg ) $html .= '<li>' . $h( $pg['path'] ) . ' &mdash; ' . $n( $pg['views'] ) . ' vistas</li>';
                $html .= '</ol>';
            }
            if ( $t['sources'] ) {
                $html .= '<h3>Fuentes de tr&aacute;fico</h3><ul>';
                foreach ( $t['sources'] as $src ) $html .= '<li>' . $h( $src['channel'] ) . ': ' . $src['pct'] . '% (' . $n( $src['count'] ) . ')</li>';
                $html .= '</ul>';
            }
        } elseif ( isset( $t['error'] ) ) {
            $html .= '<p><em>Datos de tr&aacute;fico no disponibles &mdash; error de la GA4 Data API:</em><br>'
                . '<span class="meta">' . $h( $t['error'] ) . '</span></p>';
        } else {
            $html .= '<p><em>(GA4 no configurado.)</em></p>';
        }

        // 2. Membership
        $html .= '<hr><h2>2. Membres&iacute;as</h2>';
        $m = $d['membership'];
        $html .= '<table><tr><th>M&eacute;trica</th><th>Valor</th></tr>';
        $html .= '<tr><td>Cuentas nuevas en el mes</td><td class="num">' . $m['new'] . '</td></tr>';
        $html .= '<tr><td>Cuentas totales al cierre</td><td class="num">' . $m['total'] . '</td></tr>';
        $html .= '<tr><td>Correos verificados (acumulado)</td><td class="num">' . $m['email_ok'] . '</td></tr>';
        $html .= '<tr><td>Correos sin verificar</td><td class="num">' . $m['email_no'] . '</td></tr>';
        $html .= '<tr><td>IDs de afiliaci&oacute;n capturados</td><td class="num">' . $m['affil'] . '</td></tr>';
        $html .= '<tr><td>&nbsp;&nbsp;&middot; Verificados</td><td class="num">' . $m['a_ok'] . '</td></tr>';
        $html .= '<tr><td>&nbsp;&nbsp;&middot; Rechazados</td><td class="num">' . $m['a_no'] . '</td></tr>';
        $html .= '<tr><td>&nbsp;&nbsp;&middot; En revisi&oacute;n</td><td class="num">' . $m['a_pending'] . '</td></tr>';
        $html .= '</table>';

        $html .= '<h3>Nuevos registros geogr&aacute;ficos</h3><ul>';
        foreach ( $d['geographic'] as $g ) $html .= '<li>' . $h( $g['label'] ) . ': ' . $g['count'] . ' (estados: ' . $h( $g['states'] ) . ')</li>';
        $html .= '</ul>';

        // 3. Editorial
        $html .= '<hr><h2>3. Contenido editorial</h2>';
        $e = $d['editorial'];
        $html .= '<table><tr><th>M&eacute;trica</th><th>Valor</th></tr>';
        $html .= '<tr><td>Posts publicados</td><td class="num">' . $e['count'] . '</td></tr>';
        if ( $e['has_ga4'] ) {
            $html .= '<tr><td>Vistas totales a noticias</td><td class="num">' . $n( $e['total_views'] ) . '</td></tr>';
        }
        $html .= '</table>';
        if ( $e['posts'] ) {
            $html .= '<h3>Posts del mes</h3><ul>';
            foreach ( $e['posts'] as $p ) $html .= '<li><a href="' . $h( $p['url'] ) . '">' . $h( $p['title'] ) . '</a></li>';
            $html .= '</ul>';
        }
        if ( $e['top_post'] ) {
            $html .= '<h3>Post m&aacute;s le&iacute;do</h3>';
            $html .= '<blockquote>&ldquo;' . $h( $e['top_post']['title'] ) . '&rdquo; &mdash; ' . $n( $e['top_post']['views'] ) . ' vistas</blockquote>';
        }

        // 4. Shop
        $html .= '<hr><h2>4. Tienda</h2>';
        $s = $d['shop'];
        if ( $s['active'] ) {
            $html .= '<table><tr><th>M&eacute;trica</th><th>Valor</th></tr>';
            $html .= '<tr><td>&Oacute;rdenes</td><td class="num">' . $s['orders'] . '</td></tr>';
            $html .= '<tr><td>Ingresos totales</td><td class="num">$' . $n( $s['revenue'], 2 ) . ' MXN</td></tr>';
            $html .= '<tr><td>Ticket promedio</td><td class="num">$' . $n( $s['avg'], 2 ) . ' MXN</td></tr>';
            $html .= '</table>';
            if ( $s['top'] ) {
                $html .= '<h3>Top 3 productos</h3><ol>';
                foreach ( $s['top'] as $p ) $html .= '<li>' . $h( $p['name'] ) . ' &mdash; ' . $p['qty'] . ' uds &mdash; $' . $n( $p['rev'], 2 ) . '</li>';
                $html .= '</ol>';
            }
        } else {
            $html .= '<p><em>(WooCommerce no activo.)</em></p>';
        }

        // 5. Events
        $html .= '<hr><h2>5. Eventos</h2>';
        $ev = $d['events'];
        if ( $ev['active'] ) {
            $html .= '<table><tr><th>M&eacute;trica</th><th>Valor</th></tr>';
            $html .= '<tr><td>Eventos creados</td><td class="num">' . $ev['created'] . '</td></tr>';
            $html .= '<tr><td>Eventos actualizados</td><td class="num">' . $ev['updated'] . '</td></tr>';
            $html .= '<tr><td>Activos al cierre</td><td class="num">' . $ev['total'] . '</td></tr>';
            $html .= '</table>';
        } else {
            $html .= '<p><em>(Sin CPT de eventos.)</em></p>';
        }

        // 6. Stability
        $html .= '<hr><h2>6. Estabilidad</h2>';
        $html .= '<table><tr><th>M&eacute;trica</th><th>Valor</th></tr>';
        $html .= '<tr><td>Bugs reportados</td><td class="num">0</td></tr>';
        $html .= '<tr><td>Bugs resueltos</td><td class="num">0</td></tr>';
        $html .= '<tr><td>Pendientes al cierre</td><td class="num">0</td></tr>';
        $html .= '</table>';

        // 7. Implementations
        $html .= '<hr><h2>7. Implementaciones del mes</h2>';
        $im = $d['implementations'];
        if ( $im['found'] && ( $im['feats'] || $im['fixes'] || $im['others'] ) ) {
            if ( $im['feats'] ) {
                $html .= '<h3>Nuevas funcionalidades</h3><ul>';
                foreach ( $im['feats'] as $f ) $html .= '<li>' . $h( $f ) . '</li>';
                $html .= '</ul>';
            }
            if ( $im['fixes'] ) {
                $html .= '<h3>Correcciones</h3><ul>';
                foreach ( $im['fixes'] as $f ) $html .= '<li>' . $h( $f ) . '</li>';
                $html .= '</ul>';
            }
            if ( $im['others'] ) {
                $html .= '<h3>Otros</h3><ul>';
                foreach ( $im['others'] as $f ) $html .= '<li>' . $h( $f ) . '</li>';
                $html .= '</ul>';
            }
        } else {
            $html .= '<p>Sin commits en el per&iacute;odo.</p>';
        }

        $html .= '</body></html>';
        return $html;
    }

    /* ================================================================== */
    /*  Helpers                                                            */
    /* ================================================================== */

    /**
     * Metric values for date range #$i from a multi-dateRange report. GA4 tags
     * each row with an implicit `date_range_N` dimension and omits the `totals`
     * block unless metricAggregations is requested, so read straight from rows.
     */
    private function range_metrics( ?array $result, int $i ): array {
        $key = 'date_range_' . $i;
        foreach ( ( $result['rows'] ?? [] ) as $row ) {
            $dims = $row['dimensionValues'] ?? [];
            $last = end( $dims );
            if ( ( $last['value'] ?? '' ) === $key ) {
                $v = [];
                foreach ( ( $row['metricValues'] ?? [] ) as $mv ) $v[] = (float) ( $mv['value'] ?? 0 );
                return $v;
            }
        }
        return [];
    }

    private function pct_change( float $cur, float $prev ): string {
        if ( $prev == 0 ) return '0';
        $p = ( $cur - $prev ) / $prev * 100;
        return sprintf( '%s%.1f%%', $p >= 0 ? '+' : '', $p );
    }
}

WP_CLI::add_command( 'fmdb-report', 'FMDB_Monthly_Report_Command' );
