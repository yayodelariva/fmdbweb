<?php
/**
 * Per-event registration admin UI — metabox on Tribe Events edit screen + CSV export.
 */

/* ─── 1. Data helper ─────────────────────────────────────────────────── */

function fmdb_reg_export_rows( int $event_id ): array {
    if ( ! function_exists( 'wc_get_orders' ) ) return [];

    $orders = wc_get_orders( [
        'meta_key'   => '_fmdb_reg_event_id',
        'meta_value' => $event_id,
        'status'     => [ 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-completed' ],
        'limit'      => -1,
        'orderby'    => 'date',
        'order'      => 'ASC',
    ] );

    // Build an ordered list of modalidades per 3-part prefix so old hub entries
    // (JSON without modalidad) can be assigned in registration order.
    // fmdb_reg_get_event_teams() is ordered by ID ASC, so Foam comes before Cloth etc.
    $prefix_lookup = [];
    foreach ( fmdb_reg_get_event_teams( $event_id ) as $t ) {
        if ( ! empty( $t['modalidad'] ) ) {
            $prefix_lookup[ fmdb_team_key( $t['name'], $t['rama'], $t['categoria'] ) ][] = $t['modalidad'];
        }
    }

    $rows = [];

    foreach ( $orders as $order ) {
        $reg_type = $order->get_meta( '_fmdb_reg_type' ) ?: 'team';

        $reg_item = null;
        foreach ( $order->get_items() as $item ) {
            if ( ! $reg_item && ( $item->get_meta( 'Equipo' ) || $item->get_meta( 'Hub Teams' ) || $item->get_meta( 'Captain Teams' ) ) ) {
                $reg_item = $item;
                break;
            }
        }

        if ( ! $reg_item ) continue;

        $is_hub         = (bool) $reg_item->get_meta( 'Hub Teams' );
        $is_captain_batch = ! $is_hub && (bool) $reg_item->get_meta( 'Captain Teams' );
        $nombre_first   = $reg_item->get_meta( 'Encargado' ) ?: $reg_item->get_meta( 'Capitán' ) ?: $reg_item->get_meta( 'Jugador' );
        $nombre         = trim( $nombre_first . ' ' . $reg_item->get_meta( 'Apellido' ) );

        $equipo    = $reg_item->get_meta( 'Equipo' );
        $rama      = $reg_item->get_meta( 'Rama' );
        $categoria = $reg_item->get_meta( 'Categoría' );
        $modalidad = $reg_item->get_meta( 'Modalidad' );

        // Build a display list of team entries for Jugador rows.
        // Each entry is a formatted string: "Name Rama [Modalidad] Categoria".
        $team_list = [];
        if ( $reg_type === 'team' && $is_captain_batch ) {
            // Captain multi-team batch: one row per team, fee shown only on the first.
            $ct_data      = json_decode( $reg_item->get_meta( 'Captain Teams' ), true ) ?? [];
            $fee_registro = (float) $reg_item->get_subtotal();
            $fee_total    = (float) $order->get_total();
            $base = [
                'order_id'  => $order->get_id(),
                'status'    => $order->get_status(),
                'reg_type'  => $reg_type,
                'is_hub'    => false,
                'nombre'    => $nombre,
                'telefono'  => $reg_item->get_meta( 'Teléfono' ),
                'email'     => $order->get_billing_email(),
                'team_list' => [],
            ];
            foreach ( $ct_data as $i => $ct ) {
                $ct_name = trim( $ct['team_name'] ?? '' );
                if ( ! $ct_name ) continue;
                $rows[] = $base + [
                    'equipo'       => $ct_name,
                    'rama'         => $ct['rama']      ?? '',
                    'categoria'    => $ct['categoria'] ?? '',
                    'modalidad'    => $ct['modalidad'] ?? '',
                    'fee_registro' => $i === 0 ? $fee_registro : 0.0,
                    'fee_total'    => $i === 0 ? $fee_total    : 0.0,
                    'fee_hidden'   => $i > 0,
                ];
            }
            continue; // rows already pushed above
        } elseif ( $reg_type === 'team' ) {
            $team_list = [ $equipo ];
        } elseif ( $is_hub ) {
            // Hub multi-team individual: one row per team, fee shown only on the first.
            $ht_seen      = []; // tracks assignment index per prefix for ordered modalidad back-fill
            $fee_registro = (float) $reg_item->get_subtotal();
            $fee_total    = (float) $order->get_total();
            $base = [
                'order_id'  => $order->get_id(),
                'status'    => $order->get_status(),
                'reg_type'  => $reg_type,
                'is_hub'    => true,
                'nombre'    => $nombre,
                'telefono'  => $reg_item->get_meta( 'Teléfono' ),
                'email'     => $order->get_billing_email(),
                'team_list' => [],
            ];
            $i = 0;
            foreach ( json_decode( $reg_item->get_meta( 'Hub Teams' ), true ) ?? [] as $ht ) {
                $ht_name = trim( $ht['name'] ?? '' );
                if ( ! $ht_name ) continue;
                $ht_rama = $ht['rama']      ?? '';
                $ht_cat  = $ht['categoria'] ?? '';
                $ht_mod  = $ht['modalidad'] ?? '';

                // Back-fill modalidad for old hub entries that lack it.
                if ( $ht_mod === '' ) {
                    $lk     = fmdb_team_key( $ht_name, $ht_rama, $ht_cat );
                    $mods   = $prefix_lookup[ $lk ] ?? [];
                    $idx    = $ht_seen[ $lk ] ?? 0;
                    $ht_mod = $mods[ $idx ] ?? ( end( $mods ) ?: '' );
                    $ht_seen[ $lk ] = $idx + 1;
                }

                $rows[] = $base + [
                    'equipo'       => $ht_name,
                    'rama'         => $ht_rama,
                    'categoria'    => $ht_cat,
                    'modalidad'    => $ht_mod,
                    'fee_registro' => $i === 0 ? $fee_registro : 0.0,
                    'fee_total'    => $i === 0 ? $fee_total    : 0.0,
                    'fee_hidden'   => $i > 0,
                ];
                $i++;
            }
            continue; // rows already pushed above
        } else {
            // Single-team individual join — has full rama/modalidad/categoria.
            $parts     = array_filter( [ $equipo, $rama, $modalidad, $categoria ] );
            $team_list = [ implode( ' ', $parts ) ];
        }

        // Rama / Categoría / Modalidad columns are only meaningful for Capitán rows.
        if ( $reg_type !== 'team' ) {
            $rama = $categoria = $modalidad = '';
        }

        $fee_registro = (float) $reg_item->get_subtotal();

        $rows[] = [
            'order_id'    => $order->get_id(),
            'status'      => $order->get_status(),
            'reg_type'    => $reg_type,
            'is_hub'      => $is_hub,
            'equipo'      => $equipo,
            'team_list'   => $team_list,
            'nombre'      => $nombre,
            'telefono'    => $reg_item->get_meta( 'Teléfono' ),
            'email'       => $order->get_billing_email(),
            'rama'        => $rama,
            'categoria'   => $categoria,
            'modalidad'   => $modalidad,
            'fee_registro' => $fee_registro,
            'fee_total'    => (float) $order->get_total(),
            'fee_hidden'   => false,
        ];
    }

    return $rows;
}

/* ─── 2. Status label map ─────────────────────────────────────────────── */

function fmdb_reg_status_labels(): array {
    return [
        'pending'    => [ 'Pendiente de pago', '#b45309' ],
        'on-hold'    => [ 'En espera',          '#1d4ed8' ],
        'processing' => [ 'Confirmado',          '#15803d' ],
        'completed'  => [ 'Completado',          '#15803d' ],
    ];
}

/* ─── 3. Metabox registration ─────────────────────────────────────────── */

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'fmdb_reg_export',
        'Inscripciones',
        'fmdb_render_reg_export_metabox',
        'tribe_events',
        'normal',
        'default'
    );
} );

/* ─── 4. Metabox render ───────────────────────────────────────────────── */

function fmdb_render_reg_export_metabox( WP_Post $post ): void {
    $event_id      = $post->ID;
    $rows          = fmdb_reg_export_rows( $event_id );
    $status_labels = fmdb_reg_status_labels();

    $nonce   = wp_create_nonce( 'fmdb_reg_export_' . $event_id );
    $csv_url = add_query_arg( [
        'action'   => 'fmdb_reg_export',
        'event_id' => $event_id,
        '_wpnonce' => $nonce,
    ], admin_url( 'admin-post.php' ) );
    ?>
    <div style="margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <span style="font-weight:600;font-size:13px;">
            <?php echo count( $rows ); ?> inscripción<?php echo count( $rows ) !== 1 ? 'es' : ''; ?>
        </span>
        <?php if ( $rows ) : ?>
        <a href="<?php echo esc_url( $csv_url ); ?>"
           class="button button-primary"
           style="display:inline-flex;align-items:center;gap:6px;">
            <span class="dashicons dashicons-download" style="margin-top:3px;font-size:16px;"></span>
            Descargar CSV
        </a>
        <?php endif; ?>
    </div>

    <?php if ( empty( $rows ) ) : ?>
        <p style="color:#666;font-style:italic;margin:0;">No hay inscripciones para este evento.</p>
    <?php else : ?>
    <div style="overflow-x:auto;">
    <table class="widefat striped" style="font-size:12px;">
        <thead>
            <tr>
                <th>#</th>
                <th>Estado</th>
                <th>Tipo</th>
                <th>Equipo</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Rama</th>
                <th>Categoría</th>
                <th>Modalidad</th>
                <th style="white-space:nowrap;">Registro</th>
                <th style="white-space:nowrap;">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $rows as $r ) :
            [ $st_label, $st_color ] = $status_labels[ $r['status'] ] ?? [ ucfirst( $r['status'] ), '#555' ];
            $order_url = admin_url( 'post.php?post=' . $r['order_id'] . '&action=edit' );
        ?>
            <tr>
                <td style="white-space:nowrap;">
                    <a href="<?php echo esc_url( $order_url ); ?>" target="_blank" style="font-weight:600;">
                        #<?php echo esc_html( $r['order_id'] ); ?>
                    </a>
                </td>
                <td>
                    <span style="display:inline-block;padding:2px 7px;border-radius:3px;font-size:11px;font-weight:700;background:<?php echo esc_attr( $st_color ); ?>22;color:<?php echo esc_attr( $st_color ); ?>;">
                        <?php echo esc_html( $st_label ); ?>
                    </span>
                </td>
                <td>
                    <?php if ( $r['reg_type'] === 'team' ) : ?>
                        <span title="Registró al equipo">Capitán</span>
                    <?php else : ?>
                        <span title="Se unió al equipo">Jugador</span>
                    <?php endif; ?>
                </td>
                <td style="font-weight:600;">
                    <?php if ( count( $r['team_list'] ) > 1 ) : ?>
                        <ol style="margin:0;padding-left:16px;">
                        <?php foreach ( $r['team_list'] as $tl ) : ?>
                            <li><?php echo esc_html( $tl ); ?></li>
                        <?php endforeach; ?>
                        </ol>
                    <?php elseif ( count( $r['team_list'] ) === 1 ) : ?>
                        <?php echo esc_html( $r['team_list'][0] ); ?>
                    <?php else : ?>
                        <?php echo esc_html( $r['equipo'] ); ?>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html( $r['nombre'] ); ?></td>
                <td style="white-space:nowrap;"><?php echo esc_html( $r['telefono'] ); ?></td>
                <td><?php echo esc_html( $r['email'] ); ?></td>
                <td><?php echo esc_html( $r['rama'] ); ?></td>
                <td><?php echo esc_html( $r['categoria'] ); ?></td>
                <td><?php echo esc_html( $r['modalidad'] ); ?></td>
                <td style="white-space:nowrap;font-variant-numeric:tabular-nums;">
                    <?php echo $r['fee_hidden'] ? '—' : '$' . number_format( $r['fee_registro'], 2 ); ?>
                </td>
                <td style="white-space:nowrap;font-variant-numeric:tabular-nums;font-weight:600;">
                    <?php echo $r['fee_hidden'] ? '—' : '$' . number_format( $r['fee_total'], 2 ); ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
    <?php
}

/* ─── 5. CSV download handler ─────────────────────────────────────────── */

add_action( 'admin_post_fmdb_reg_export', function () {
    $event_id = absint( $_GET['event_id'] ?? 0 );

    if ( ! $event_id
      || ! current_user_can( 'manage_options' )
      || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'fmdb_reg_export_' . $event_id ) ) {
        wp_die( 'Acceso no autorizado.', 403 );
    }

    $rows     = fmdb_reg_export_rows( $event_id );
    $event    = get_the_title( $event_id );
    $filename = sanitize_file_name( 'inscripciones-' . $event . '-' . date( 'Y-m-d' ) . '.csv' );

    header( 'Content-Type: text/csv; charset=UTF-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );

    $out = fopen( 'php://output', 'w' );
    // UTF-8 BOM so Excel opens accented characters correctly.
    fwrite( $out, "\xEF\xBB\xBF" );

    fputcsv( $out, [
        '# Pedido',
        'Estado',
        'Tipo',
        'Equipo',
        'Nombre',
        'Teléfono',
        'Email',
        'Rama',
        'Categoría',
        'Modalidad',
        'Registro (MXN)',
        'Total (MXN)',
    ] );

    $status_labels = array_map( fn( $v ) => $v[0], fmdb_reg_status_labels() );

    foreach ( $rows as $r ) {
        $equipo_csv = $r['reg_type'] === 'team'
            ? $r['equipo']
            : implode( ' | ', array_map(
                fn( $i, $t ) => ( $i + 1 ) . '. ' . $t,
                array_keys( $r['team_list'] ),
                $r['team_list']
            ) );

        fputcsv( $out, [
            $r['order_id'],
            $status_labels[ $r['status'] ] ?? $r['status'],
            $r['reg_type'] === 'team' ? 'Capitán' : 'Jugador',
            $equipo_csv,
            $r['nombre'],
            $r['telefono'],
            $r['email'],
            $r['rama'],
            $r['categoria'],
            $r['modalidad'],
            number_format( $r['fee_registro'], 2, '.', '' ),
            number_format( $r['fee_total'],    2, '.', '' ),
        ] );
    }

    fclose( $out );
    exit;
} );
