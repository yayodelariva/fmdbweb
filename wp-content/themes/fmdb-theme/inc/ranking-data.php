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
            'label'    => 'FOAM Libre',
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
            'label'    => 'CLOTH Libre',
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
        'infantil' => [
            'label'    => 'Infantil',
            'years'    => [ 2023 => 0.7, 2024 => 0.8, 2025 => 0.9, 2026 => 1.0 ],
            'sections' => [
                'mixto' => [
                    'label' => 'Mixto',
                    'rows'  => [
                        [ 'state' => 'Hidalgo',     'total' => 1752 ],
                        [ 'state' => 'Oaxaca',      'total' => 1516 ],
                        [ 'state' => 'Morelos',     'total' => 1338 ],
                        [ 'state' => 'CDMX',        'total' =>  616 ],
                        [ 'state' => 'Querétaro',   'total' =>  344 ],
                        [ 'state' => 'Jalisco',     'total' =>  304 ],
                        [ 'state' => 'Veracruz',    'total' =>  280 ],
                        [ 'state' => 'Puebla',      'total' =>   96 ],
                    ],
                ],
                'femenil' => [
                    'label' => 'Femenil',
                    'rows'  => [
                        [ 'state' => 'Morelos',  'total' => 772 ],
                        [ 'state' => 'Hidalgo',  'total' => 482 ],
                        [ 'state' => 'Oaxaca',   'total' => 456 ],
                        [ 'state' => 'CDMX',     'total' => 126 ],
                    ],
                ],
                'varonil' => [
                    'label' => 'Varonil',
                    'rows'  => [
                        [ 'state' => 'Morelos', 'total' => 974 ],
                    ],
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
