<?php
/**
 * Ranking data — points come from the per-year position table
 * (1° 200, 2° 180, 3° 160, 4° 140, 5° 120, 6° 100; in early years 5°=100/6°=80)
 * multiplied by the year's aging factor (older years discounted, current year = 1.0).
 * Values below are the "Total" column from the spreadsheet's Suma sheets —
 * already aged-weighted. Update annually after the season closes.
 *
 * Source: "Dodgeball México Resultados nacionales.xlsx" Suma-* sheets.
 */

function fmdb_get_ranking_data() {
    return [
        'foam' => [
            'label'    => 'Foam',
            'years'    => [
                2021 => 0.6, 2022 => 0.7, 2023 => 0.8, 2024 => 0.9, 2025 => 1.0,
            ],
            'sections' => [
                'varonil' => [
                    'label' => 'Varonil',
                    'rows'  => [
                        [ 'state' => 'CDMX',      'total' => 746 ],
                        [ 'state' => 'Veracruz',  'total' => 570 ],
                        [ 'state' => 'Morelos',   'total' => 470 ],
                        [ 'state' => 'Hidalgo',   'total' => 456 ],
                        [ 'state' => 'ADME',      'total' => 360 ],
                        [ 'state' => 'Oaxaca',    'total' => 330 ],
                        [ 'state' => 'Jalisco',   'total' => 228 ],
                        [ 'state' => 'EDOMEX',    'total' => 108 ],
                        [ 'state' => 'Guerrero',  'total' =>  90 ],
                        [ 'state' => 'Puebla',    'total' =>  72 ],
                        [ 'state' => 'Tlaxcala',  'total' =>  64 ],
                        [ 'state' => 'Querétaro', 'total' =>   0 ],
                    ],
                ],
                'femenil' => [
                    'label' => 'Femenil',
                    'rows'  => [
                        [ 'state' => 'CDMX',      'total' => 730 ],
                        [ 'state' => 'Veracruz',  'total' => 548 ],
                        [ 'state' => 'Hidalgo',   'total' => 520 ],
                        [ 'state' => 'Morelos',   'total' => 436 ],
                        [ 'state' => 'Jalisco',   'total' => 248 ],
                        [ 'state' => 'ADME',      'total' =>  96 ],
                        [ 'state' => 'Querétaro', 'total' =>   0 ],
                        [ 'state' => 'Oaxaca',    'total' =>   0 ],
                        [ 'state' => 'EDOMEX',    'total' =>   0 ],
                        [ 'state' => 'Puebla',    'total' =>   0 ],
                        [ 'state' => 'Tlaxcala',  'total' =>   0 ],
                        [ 'state' => 'Guerrero',  'total' =>   0 ],
                    ],
                ],
                'mixto' => [
                    'label' => 'Mixto',
                    'rows'  => [
                        [ 'state' => 'CDMX',      'total' => 768 ],
                        [ 'state' => 'Morelos',   'total' => 518 ],
                        [ 'state' => 'Hidalgo',   'total' => 378 ],
                        [ 'state' => 'ADME',      'total' => 248 ],
                        [ 'state' => 'Veracruz',  'total' => 238 ],
                        [ 'state' => 'Jalisco',   'total' => 230 ],
                        [ 'state' => 'EDOMEX',    'total' => 108 ],
                        [ 'state' => 'Guerrero',  'total' =>  90 ],
                        [ 'state' => 'Querétaro', 'total' =>  90 ],
                        [ 'state' => 'Oaxaca',    'total' =>   0 ],
                        [ 'state' => 'Tlaxcala',  'total' =>   0 ],
                        [ 'state' => 'Puebla',    'total' =>   0 ],
                    ],
                ],
            ],
            'totals' => [
                [ 'state' => 'CDMX',      'v' => 746, 'f' => 730, 'm' => 768, 'total' => 2244 ],
                [ 'state' => 'Morelos',   'v' => 470, 'f' => 436, 'm' => 518, 'total' => 1424 ],
                [ 'state' => 'Veracruz',  'v' => 570, 'f' => 548, 'm' => 238, 'total' => 1356 ],
                [ 'state' => 'Hidalgo',   'v' => 456, 'f' => 520, 'm' => 378, 'total' => 1354 ],
                [ 'state' => 'Jalisco',   'v' => 228, 'f' => 248, 'm' => 230, 'total' =>  706 ],
                [ 'state' => 'ADME',      'v' => 360, 'f' =>  96, 'm' => 248, 'total' =>  704 ],
                [ 'state' => 'Oaxaca',    'v' => 330, 'f' =>   0, 'm' =>   0, 'total' =>  330 ],
                [ 'state' => 'EDOMEX',    'v' => 108, 'f' =>   0, 'm' => 108, 'total' =>  216 ],
                [ 'state' => 'Guerrero',  'v' =>  90, 'f' =>   0, 'm' =>  90, 'total' =>  180 ],
                [ 'state' => 'Querétaro', 'v' =>   0, 'f' =>   0, 'm' =>  90, 'total' =>   90 ],
                [ 'state' => 'Puebla',    'v' =>  72, 'f' =>   0, 'm' =>   0, 'total' =>   72 ],
                [ 'state' => 'Tlaxcala',  'v' =>  64, 'f' =>   0, 'm' =>   0, 'total' =>   64 ],
            ],
        ],
        'cloth' => [
            'label'    => 'Cloth',
            'years'    => [ 2024 => 0.9, 2025 => 1.0 ],
            'sections' => [
                'varonil' => [
                    'label' => 'Varonil',
                    'rows'  => [
                        [ 'state' => 'Veracruz',  'total' => 362 ],
                        [ 'state' => 'CDMX',      'total' => 360 ],
                        [ 'state' => 'Morelos',   'total' => 286 ],
                        [ 'state' => 'Hidalgo',   'total' => 248 ],
                        [ 'state' => 'EDOMEX',    'total' => 228 ],
                        [ 'state' => 'ADME',      'total' =>   0 ],
                        [ 'state' => 'Oaxaca',    'total' =>   0 ],
                        [ 'state' => 'Jalisco',   'total' =>   0 ],
                        [ 'state' => 'Guerrero',  'total' =>   0 ],
                        [ 'state' => 'Tlaxcala',  'total' =>   0 ],
                        [ 'state' => 'Puebla',    'total' =>   0 ],
                        [ 'state' => 'Querétaro', 'total' =>   0 ],
                    ],
                ],
                'femenil' => [
                    'label' => 'Femenil',
                    'rows'  => [
                        [ 'state' => 'Morelos',   'total' => 380 ],
                        [ 'state' => 'Veracruz',  'total' => 162 ],
                        [ 'state' => 'Hidalgo',   'total' => 160 ],
                        [ 'state' => 'CDMX',      'total' => 144 ],
                        [ 'state' => 'Jalisco',   'total' =>   0 ],
                        [ 'state' => 'ADME',      'total' =>   0 ],
                        [ 'state' => 'Querétaro', 'total' =>   0 ],
                        [ 'state' => 'Oaxaca',    'total' =>   0 ],
                        [ 'state' => 'EDOMEX',    'total' =>   0 ],
                        [ 'state' => 'Puebla',    'total' =>   0 ],
                        [ 'state' => 'Tlaxcala',  'total' =>   0 ],
                        [ 'state' => 'Guerrero',  'total' =>   0 ],
                    ],
                ],
                'mixto' => [
                    'label' => 'Mixto',
                    'rows'  => [
                        [ 'state' => 'Veracruz',  'total' => 380 ],
                        [ 'state' => 'Morelos',   'total' => 324 ],
                        [ 'state' => 'CDMX',      'total' => 322 ],
                        [ 'state' => 'Hidalgo',   'total' => 140 ],
                        [ 'state' => 'EDOMEX',    'total' => 108 ],
                        [ 'state' => 'Jalisco',   'total' =>   0 ],
                        [ 'state' => 'Guerrero',  'total' =>   0 ],
                        [ 'state' => 'Querétaro', 'total' =>   0 ],
                        [ 'state' => 'ADME',      'total' =>   0 ],
                        [ 'state' => 'Oaxaca',    'total' =>   0 ],
                        [ 'state' => 'Tlaxcala',  'total' =>   0 ],
                        [ 'state' => 'Puebla',    'total' =>   0 ],
                    ],
                ],
            ],
            'totals' => [
                [ 'state' => 'Morelos',   'v' => 286, 'f' => 380, 'm' => 324, 'total' => 990 ],
                [ 'state' => 'Veracruz',  'v' => 362, 'f' => 162, 'm' => 380, 'total' => 904 ],
                [ 'state' => 'CDMX',      'v' => 360, 'f' => 144, 'm' => 322, 'total' => 826 ],
                [ 'state' => 'Hidalgo',   'v' => 248, 'f' => 160, 'm' => 140, 'total' => 548 ],
                [ 'state' => 'EDOMEX',    'v' => 228, 'f' =>   0, 'm' => 108, 'total' => 336 ],
            ],
        ],
        'infantil-menor' => [
            'label'    => 'Menor',
            'years'    => [ 2023 => 0.7, 2024 => 0.8, 2025 => 0.9, 2026 => 1.0 ],
            'sections' => [
                'varonil' => [
                    'label' => 'Varonil',
                    'rows'  => [],
                    'rows_tiered' => [],
                ],
                'femenil' => [
                    'label' => 'Femenil',
                    'rows'  => [],
                    'rows_tiered' => [],
                ],
                'mixto' => [
                    'label' => 'Mixto',
                    'rows'  => [
                        [ 'state' => 'Hidalgo',   'total' => 448 ],
                        [ 'state' => 'Oaxaca',    'total' => 380 ],
                        [ 'state' => 'Morelos',   'total' => 162 ],
                        [ 'state' => 'Querétaro', 'total' => 128 ],
                        [ 'state' => 'Jalisco',   'total' => 108 ],
                        [ 'state' => 'CDMX',      'total' =>  72 ],
                    ],
                    'rows_tiered' => [
                        [ 'state' => 'Oaxaca 1',    'total' => 380 ],
                        [ 'state' => 'Hidalgo 1',   'total' => 304 ],
                        [ 'state' => 'Morelos 1',   'total' => 162 ],
                        [ 'state' => 'Hidalgo 2',   'total' => 144 ],
                        [ 'state' => 'Querétaro 1', 'total' => 128 ],
                        [ 'state' => 'Jalisco 1',   'total' => 108 ],
                        [ 'state' => 'CDMX 1',      'total' =>  72 ],
                    ],
                ],
            ],
            'totals' => [
                [ 'state' => 'Hidalgo',   'v' => 0, 'f' => 0, 'm' => 448, 'total' => 448 ],
                [ 'state' => 'Oaxaca',    'v' => 0, 'f' => 0, 'm' => 380, 'total' => 380 ],
                [ 'state' => 'Morelos',   'v' => 0, 'f' => 0, 'm' => 162, 'total' => 162 ],
                [ 'state' => 'Querétaro', 'v' => 0, 'f' => 0, 'm' => 128, 'total' => 128 ],
                [ 'state' => 'Jalisco',   'v' => 0, 'f' => 0, 'm' => 108, 'total' => 108 ],
                [ 'state' => 'CDMX',      'v' => 0, 'f' => 0, 'm' =>  72, 'total' =>  72 ],
            ],
        ],
        'infantil-intermedia' => [
            'label'    => 'Intermedia',
            'years'    => [ 2023 => 0.7, 2024 => 0.8, 2025 => 0.9, 2026 => 1.0 ],
            'sections' => [
                'varonil' => [
                    'label' => 'Varonil',
                    'rows'  => [
                        [ 'state' => 'Oaxaca',  'total' => 612 ],
                        [ 'state' => 'Hidalgo', 'total' => 572 ],
                        [ 'state' => 'Morelos', 'total' => 522 ],
                        [ 'state' => 'CDMX',    'total' => 126 ],
                    ],
                    'rows_tiered' => [
                        [ 'state' => 'Oaxaca 1',  'total' => 504 ],
                        [ 'state' => 'Hidalgo 1', 'total' => 444 ],
                        [ 'state' => 'Morelos 1', 'total' => 362 ],
                        [ 'state' => 'Morelos 2', 'total' => 160 ],
                        [ 'state' => 'Hidalgo 2', 'total' => 128 ],
                        [ 'state' => 'CDMX 1',    'total' => 126 ],
                        [ 'state' => 'Oaxaca 2',  'total' => 108 ],
                    ],
                ],
                'femenil' => [
                    'label' => 'Femenil',
                    'rows'  => [],
                    'rows_tiered' => [],
                ],
                'mixto' => [
                    'label' => 'Mixto',
                    'rows'  => [
                        [ 'state' => 'Oaxaca',    'total' => 604 ],
                        [ 'state' => 'Hidalgo',   'total' => 596 ],
                        [ 'state' => 'Morelos',   'total' => 418 ],
                        [ 'state' => 'Veracruz',  'total' => 140 ],
                        [ 'state' => 'CDMX',      'total' => 126 ],
                        [ 'state' => 'Querétaro', 'total' => 108 ],
                        [ 'state' => 'Jalisco',   'total' =>  98 ],
                    ],
                    'rows_tiered' => [
                        [ 'state' => 'Oaxaca 1',    'total' => 508 ],
                        [ 'state' => 'Hidalgo 1',   'total' => 484 ],
                        [ 'state' => 'Morelos 1',   'total' => 418 ],
                        [ 'state' => 'Veracruz 1',  'total' => 140 ],
                        [ 'state' => 'CDMX 1',      'total' => 126 ],
                        [ 'state' => 'Hidalgo 2',   'total' => 112 ],
                        [ 'state' => 'Querétaro 1', 'total' => 108 ],
                        [ 'state' => 'Jalisco 1',   'total' =>  98 ],
                        [ 'state' => 'Oaxaca 2',    'total' =>  96 ],
                    ],
                ],
            ],
            'totals' => [
                [ 'state' => 'Oaxaca',    'v' => 612, 'f' => 0, 'm' => 604, 'total' => 1216 ],
                [ 'state' => 'Hidalgo',   'v' => 572, 'f' => 0, 'm' => 596, 'total' => 1168 ],
                [ 'state' => 'Morelos',   'v' => 522, 'f' => 0, 'm' => 418, 'total' =>  940 ],
                [ 'state' => 'CDMX',      'v' => 126, 'f' => 0, 'm' => 126, 'total' =>  252 ],
                [ 'state' => 'Veracruz',  'v' =>   0, 'f' => 0, 'm' => 140, 'total' =>  140 ],
                [ 'state' => 'Querétaro', 'v' =>   0, 'f' => 0, 'm' => 108, 'total' =>  108 ],
                [ 'state' => 'Jalisco',   'v' =>   0, 'f' => 0, 'm' =>  98, 'total' =>   98 ],
            ],
        ],
        'infantil-mayor' => [
            'label'    => 'Mayor',
            'years'    => [ 2023 => 0.7, 2024 => 0.8, 2025 => 0.9, 2026 => 1.0 ],
            'sections' => [
                'varonil' => [
                    'label' => 'Varonil',
                    'rows'  => [
                        [ 'state' => 'Morelos',   'total' => 452 ],
                        [ 'state' => 'Hidalgo',   'total' => 322 ],
                        [ 'state' => 'Oaxaca',    'total' => 144 ],
                        [ 'state' => 'CDMX',      'total' => 126 ],
                        [ 'state' => 'Querétaro', 'total' =>  96 ],
                    ],
                    'rows_tiered' => [
                        [ 'state' => 'Hidalgo 1',   'total' => 322 ],
                        [ 'state' => 'Morelos 1',   'total' => 308 ],
                        [ 'state' => 'Oaxaca 1',    'total' => 144 ],
                        [ 'state' => 'Morelos 2',   'total' => 144 ],
                        [ 'state' => 'CDMX 1',      'total' => 126 ],
                        [ 'state' => 'Querétaro 1', 'total' =>  96 ],
                    ],
                ],
                'femenil' => [
                    'label' => 'Femenil',
                    'rows'  => [
                        [ 'state' => 'Morelos', 'total' => 772 ],
                        [ 'state' => 'Hidalgo', 'total' => 482 ],
                        [ 'state' => 'Oaxaca',  'total' => 456 ],
                        [ 'state' => 'CDMX',    'total' => 126 ],
                    ],
                    'rows_tiered' => [
                        [ 'state' => 'Morelos 1', 'total' => 488 ],
                        [ 'state' => 'Hidalgo 1', 'total' => 482 ],
                        [ 'state' => 'Oaxaca 1',  'total' => 456 ],
                        [ 'state' => 'Morelos 2', 'total' => 284 ],
                        [ 'state' => 'CDMX 1',    'total' => 126 ],
                    ],
                ],
                'mixto' => [
                    'label' => 'Mixto',
                    'rows'  => [
                        [ 'state' => 'Morelos',   'total' => 758 ],
                        [ 'state' => 'Hidalgo',   'total' => 708 ],
                        [ 'state' => 'Oaxaca',    'total' => 532 ],
                        [ 'state' => 'CDMX',      'total' => 418 ],
                        [ 'state' => 'Veracruz',  'total' => 140 ],
                        [ 'state' => 'Querétaro', 'total' => 108 ],
                        [ 'state' => 'Jalisco',   'total' =>  98 ],
                        [ 'state' => 'Puebla',    'total' =>  96 ],
                    ],
                    'rows_tiered' => [
                        [ 'state' => 'Morelos 1',   'total' => 558 ],
                        [ 'state' => 'Hidalgo 1',   'total' => 460 ],
                        [ 'state' => 'CDMX 1',      'total' => 418 ],
                        [ 'state' => 'Oaxaca 1',    'total' => 304 ],
                        [ 'state' => 'Hidalgo 2',   'total' => 248 ],
                        [ 'state' => 'Oaxaca 2',    'total' => 228 ],
                        [ 'state' => 'Morelos 2',   'total' => 200 ],
                        [ 'state' => 'Veracruz 1',  'total' => 140 ],
                        [ 'state' => 'Querétaro 1', 'total' => 108 ],
                        [ 'state' => 'Jalisco 1',   'total' =>  98 ],
                        [ 'state' => 'Puebla 1',    'total' =>  96 ],
                    ],
                ],
            ],
            'totals' => [
                [ 'state' => 'Morelos',   'v' => 452, 'f' => 772, 'm' => 758, 'total' => 1982 ],
                [ 'state' => 'Hidalgo',   'v' => 322, 'f' => 482, 'm' => 708, 'total' => 1512 ],
                [ 'state' => 'Oaxaca',    'v' => 144, 'f' => 456, 'm' => 532, 'total' => 1132 ],
                [ 'state' => 'CDMX',      'v' => 126, 'f' => 126, 'm' => 418, 'total' =>  670 ],
                [ 'state' => 'Querétaro', 'v' =>  96, 'f' =>   0, 'm' => 108, 'total' =>  204 ],
                [ 'state' => 'Veracruz',  'v' =>   0, 'f' =>   0, 'm' => 140, 'total' =>  140 ],
                [ 'state' => 'Jalisco',   'v' =>   0, 'f' =>   0, 'm' =>  98, 'total' =>   98 ],
                [ 'state' => 'Puebla',    'v' =>   0, 'f' =>   0, 'm' =>  96, 'total' =>   96 ],
            ],
        ],
        'juvenil' => [
            'label'    => 'Juvenil',
            'years'    => [ 2023 => 1.0 ],
            'sections' => [
                'menor' => [
                    'label' => 'Menor',
                    'rows'  => [
                        [ 'state' => 'Veracruz', 'total' => 200 ],
                        [ 'state' => 'Morelos',  'total' => 160 ],
                        [ 'state' => 'Jalisco',  'total' => 140 ],
                    ],
                ],
                'intermedia' => [
                    'label' => 'Intermedia',
                    'rows'  => [
                        [ 'state' => 'Veracruz', 'total' => 200 ],
                        [ 'state' => 'Morelos',  'total' => 160 ],
                    ],
                ],
                'mayor' => [
                    'label' => 'Mayor',
                    'rows'  => [],
                ],
            ],
            'totals' => null,
        ],
    ];
}

/**
 * Points table used by the page (informational, displayed at top).
 */
function fmdb_get_ranking_points_table() {
    return [
        1 => 200, 2 => 180, 3 => 160, 4 => 140, 5 => 120, 6 => 100,
    ];
}

/* ---------- render helpers (shared by /ranking/ and /ranking/tablas/) ---------- */

function fmdb_render_podium( $rows ) {
    $top = array_slice( array_values( array_filter( $rows, function ( $r ) {
        return ! empty( $r['total'] );
    } ) ), 0, 3 );
    if ( empty( $top ) ) {
        echo '<p class="fmdb-ranking__empty">Sin resultados registrados.</p>';
        return;
    }
    $medals = [ '🥇', '🥈', '🥉' ];
    echo '<div class="fmdb-ranking__podium">';
    foreach ( $top as $i => $r ) {
        echo '<div class="fmdb-ranking__podium-card fmdb-ranking__podium-card--' . ( $i + 1 ) . '">';
        echo   '<span class="fmdb-ranking__podium-medal" aria-hidden="true">' . esc_html( $medals[ $i ] ) . '</span>';
        echo   '<span class="fmdb-ranking__podium-state">' . esc_html( $r['state'] ) . '</span>';
        echo   '<span class="fmdb-ranking__podium-pts">' . (int) $r['total'] . ' <small>pts</small></span>';
        echo '</div>';
    }
    echo '</div>';
}

function fmdb_render_section_table( $rows ) {
    echo '<div class="fmdb-ranking__table-wrap">';
    echo '<table class="fmdb-ranking__table">';
    echo   '<thead><tr><th>Pos.</th><th>Estado</th><th class="num">Puntos</th></tr></thead><tbody>';
    foreach ( $rows as $i => $r ) {
        $is_zero = empty( $r['total'] );
        echo '<tr class="' . ( $is_zero ? 'is-zero' : '' ) . ( $i < 3 && ! $is_zero ? ' is-top' : '' ) . '">';
        echo   '<td><span class="fmdb-ranking__pos">' . ( $i + 1 ) . '</span></td>';
        echo   '<td>' . esc_html( $r['state'] ) . '</td>';
        echo   '<td class="num">' . (int) $r['total'] . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function fmdb_render_combined_table( $rows ) {
    echo '<div class="fmdb-ranking__table-wrap">';
    echo '<table class="fmdb-ranking__table fmdb-ranking__table--combined">';
    echo   '<thead><tr><th>Pos.</th><th>Estado</th><th class="num">Varonil</th><th class="num">Femenil</th><th class="num">Mixto</th><th class="num">Total</th></tr></thead><tbody>';
    foreach ( $rows as $i => $r ) {
        echo '<tr class="' . ( $i < 3 ? 'is-top' : '' ) . '">';
        echo   '<td><span class="fmdb-ranking__pos">' . ( $i + 1 ) . '</span></td>';
        echo   '<td>' . esc_html( $r['state'] ) . '</td>';
        echo   '<td class="num">' . (int) $r['v'] . '</td>';
        echo   '<td class="num">' . (int) $r['f'] . '</td>';
        echo   '<td class="num">' . (int) $r['m'] . '</td>';
        echo   '<td class="num"><strong>' . (int) $r['total'] . '</strong></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
