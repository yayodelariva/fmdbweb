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

    $rows = [];

    foreach ( $orders as $order ) {
        $reg_type = $order->get_meta( '_fmdb_reg_type' ) ?: 'team';

        $reg_item  = null;
        $hosp_item = null;

        foreach ( $order->get_items() as $item ) {
            if ( ! $reg_item && $item->get_meta( 'Equipo' ) ) {
                $reg_item = $item;
            }
            if ( ! $hosp_item && $item->get_meta( 'Habitación' ) ) {
                $hosp_item = $item;
            }
        }

        if ( ! $reg_item ) continue;

        $encargado_nombre   = $reg_item->get_meta( 'Encargado' ) ?: $reg_item->get_meta( 'Capitán' );
        $encargado_apellido = $reg_item->get_meta( 'Apellido' );
        $encargado          = trim( $encargado_nombre . ' ' . $encargado_apellido );

        // Build player list.
        $jugadores = [];
        if ( $reg_type === 'team' ) {
            $jugadores[] = $encargado;
            $total_slots = (int) $reg_item->get_meta( 'Jugadores' );
            for ( $i = 2; $i <= $total_slots; $i++ ) {
                $n = trim( $reg_item->get_meta( 'Jugador ' . $i ) );
                if ( $n ) $jugadores[] = $n;
            }
        } else {
            $jugadores[] = $encargado;
        }

        // Build hospedaje info.
        $habitacion = '';
        $huespedes  = [];
        if ( $hosp_item ) {
            $habitacion = $hosp_item->get_meta( 'Habitación' );
            for ( $i = 1; $i <= 10; $i++ ) {
                $h = trim( (string) $hosp_item->get_meta( 'Huésped ' . $i ) );
                if ( $h ) $huespedes[] = $h;
                else break;
            }
        }

        $rows[] = [
            'order_id'   => $order->get_id(),
            'status'     => $order->get_status(),
            'reg_type'   => $reg_type,
            'equipo'     => $reg_item->get_meta( 'Equipo' ),
            'encargado'  => $encargado,
            'telefono'   => $reg_item->get_meta( 'Teléfono' ),
            'email'      => $order->get_billing_email(),
            'rama'       => $reg_item->get_meta( 'Rama' ),
            'categoria'  => $reg_item->get_meta( 'Categoría' ),
            'modalidad'  => $reg_item->get_meta( 'Modalidad' ),
            'jugadores'  => $jugadores,
            'habitacion' => $habitacion,
            'huespedes'  => $huespedes,
        ];
    }

    return $rows;
}

/* ─── 2. Metabox registration ─────────────────────────────────────────── */

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

/* ─── 3. Metabox render ───────────────────────────────────────────────── */

function fmdb_render_reg_export_metabox( WP_Post $post ): void {
    $event_id = $post->ID;
    $rows     = fmdb_reg_export_rows( $event_id );

    $status_labels = [
        'pending'    => [ 'Pendiente de pago', '#b45309' ],
        'on-hold'    => [ 'En espera',          '#1d4ed8' ],
        'processing' => [ 'Confirmado',          '#15803d' ],
        'completed'  => [ 'Completado',          '#15803d' ],
    ];

    $nonce = wp_create_nonce( 'fmdb_reg_export_' . $event_id );
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
                <th>Encargado</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Rama / Cat. / Mod.</th>
                <th>Jugadores</th>
                <th>Hospedaje</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $rows as $i => $r ) :
            [ $st_label, $st_color ] = $status_labels[ $r['status'] ] ?? [ ucfirst( $r['status'] ), '#555' ];
            $order_url = get_edit_post_link( wc_get_order( $r['order_id'] )->get_id() );
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
                        <span title="Registró al equipo">Equipo</span>
                    <?php else : ?>
                        <span title="Se unió al equipo">Individual</span>
                    <?php endif; ?>
                </td>
                <td style="font-weight:600;"><?php echo esc_html( $r['equipo'] ); ?></td>
                <td><?php echo esc_html( $r['encargado'] ); ?></td>
                <td style="white-space:nowrap;"><?php echo esc_html( $r['telefono'] ); ?></td>
                <td><?php echo esc_html( $r['email'] ); ?></td>
                <td style="white-space:nowrap;"><?php echo esc_html( implode( ' / ', array_filter( [ $r['rama'], $r['categoria'], $r['modalidad'] ] ) ) ); ?></td>
                <td>
                    <?php if ( $r['jugadores'] ) : ?>
                        <ol style="margin:0;padding-left:16px;">
                        <?php foreach ( $r['jugadores'] as $j ) : ?>
                            <li><?php echo esc_html( $j ); ?></li>
                        <?php endforeach; ?>
                        </ol>
                    <?php else : ?>—<?php endif; ?>
                </td>
                <td>
                    <?php if ( $r['habitacion'] ) : ?>
                        <strong><?php echo esc_html( $r['habitacion'] ); ?></strong>
                        <?php if ( $r['huespedes'] ) : ?>
                            <ol style="margin:4px 0 0;padding-left:16px;">
                            <?php foreach ( $r['huespedes'] as $h ) : ?>
                                <li><?php echo esc_html( $h ); ?></li>
                            <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>
                    <?php else : ?>—<?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
    <?php
}

/* ─── 4. CSV download handler ─────────────────────────────────────────── */

add_action( 'admin_post_fmdb_reg_export', function () {
    $event_id = absint( $_GET['event_id'] ?? 0 );

    if ( ! $event_id
      || ! current_user_can( 'manage_options' )
      || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'fmdb_reg_export_' . $event_id ) ) {
        wp_die( 'Acceso no autorizado.', 403 );
    }

    $rows      = fmdb_reg_export_rows( $event_id );
    $event     = get_the_title( $event_id );
    $filename  = sanitize_file_name( 'inscripciones-' . $event . '-' . date( 'Y-m-d' ) . '.csv' );

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
        'Encargado',
        'Teléfono',
        'Email',
        'Rama',
        'Categoría',
        'Modalidad',
        'Jugadores',
        'Habitación',
        'Huéspedes',
    ] );

    $status_labels = [
        'pending'    => 'Pendiente de pago',
        'on-hold'    => 'En espera',
        'processing' => 'Confirmado',
        'completed'  => 'Completado',
    ];

    foreach ( $rows as $r ) {
        fputcsv( $out, [
            $r['order_id'],
            $status_labels[ $r['status'] ] ?? $r['status'],
            $r['reg_type'] === 'team' ? 'Equipo' : 'Individual',
            $r['equipo'],
            $r['encargado'],
            $r['telefono'],
            $r['email'],
            $r['rama'],
            $r['categoria'],
            $r['modalidad'],
            implode( ' | ', $r['jugadores'] ),
            $r['habitacion'],
            implode( ' | ', $r['huespedes'] ),
        ] );
    }

    fclose( $out );
    exit;
} );
