<?php
/**
 * Template: Torneos hub — lists open tournaments and handles per-event registration.
 */

if ( ! is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/login/?redirect_to=' . rawurlencode( home_url( '/torneos/' ) . ( isset( $_GET['evento'] ) ? '?evento=' . (int) $_GET['evento'] : '' ) ) ) );
    exit;
}

get_header();

$current_user = wp_get_current_user();
$event_id     = isset( $_GET['evento'] ) ? absint( $_GET['evento'] ) : 0;
$event        = $event_id ? get_post( $event_id ) : null;
if ( $event && $event->post_type !== 'tribe_events' ) {
    $event_id = 0;
    $event    = null;
}

if ( $event_id ) {
    // ── Per-event registration view ──────────────────────────────────────────

    $reg_open    = get_post_meta( $event_id, '_fmdb_reg_open',    true ) === 'on';
    $reg_fee     = (float) get_post_meta( $event_id, '_fmdb_reg_fee', true );
    $deadline    = get_post_meta( $event_id, '_fmdb_reg_deadline', true );
    $reg_closed  = ! $reg_open || fmdb_reg_deadline_passed( $event_id );
    $has_access  = fmdb_user_has_event_access( $current_user->ID, $event_id );
    $has_paid    = fmdb_user_has_paid_for_event( $current_user->ID, $event_id );
    $has_pending = ! $has_paid && fmdb_user_has_pending_order_for_event( $current_user->ID, $event_id );

    // Event meta for display
    $start_raw = get_post_meta( $event_id, '_EventStartDate', true );
    $end_raw   = get_post_meta( $event_id, '_EventEndDate',   true );
    $start_ts  = $start_raw ? strtotime( $start_raw ) : 0;
    $end_ts    = $end_raw   ? strtotime( $end_raw )   : 0;
    $venue_id  = get_post_meta( $event_id, '_EventVenueID', true );
    $venue     = $venue_id  ? get_the_title( $venue_id ) : '';
    $city      = $venue_id  ? get_post_meta( $venue_id, '_VenueCity',  true ) : '';
    $state     = $venue_id  ? get_post_meta( $venue_id, '_VenueState', true ) : '';
    $location  = implode( ', ', array_filter( [ $venue, $city, $state ] ) );

    $allowed_ramas = array_values( array_filter( (array) get_post_meta( $event_id, '_fmdb_reg_ramas',      true ) ) );
    $allowed_cats  = array_values( array_filter( (array) get_post_meta( $event_id, '_fmdb_reg_categorias', true ) ) );
    $valid_ramas   = [ 'Varonil/Mixto', 'Femenil/Mixto' ];
    $allowed_ramas = array_values( array_intersect( $allowed_ramas, $valid_ramas ) );
    if ( empty( $allowed_ramas ) ) $allowed_ramas = $valid_ramas;
    if ( empty( $allowed_cats ) )  $allowed_cats  = [ 'Infantil', 'Libre' ];

    // Expand to display ramas
    $display_ramas = [];
    foreach ( $allowed_ramas as $ar ) {
        $primary = strpos( $ar, 'Varonil' ) !== false ? 'Varonil' : 'Femenil';
        if ( ! in_array( $primary, $display_ramas, true ) ) $display_ramas[] = $primary;
    }
    if ( ! empty( $allowed_ramas ) ) $display_ramas[] = 'Mixto';
?>
<main class="fmdb-torneos">
    <div class="fmdb-torneos__wrap">
        <a href="<?php echo esc_url( home_url( '/torneos/' ) ); ?>" class="fmdb-torneos__back">← Torneos</a>

        <div class="fmdb-torneos__event-header">
            <?php if ( $start_ts ) : ?>
                <div class="fmdb-torneos__event-date">
                    <span class="fmdb-torneos__event-day"><?php echo date_i18n( 'j', $start_ts ); ?></span>
                    <span class="fmdb-torneos__event-month"><?php echo date_i18n( 'M', $start_ts ); ?></span>
                </div>
            <?php endif; ?>
            <div class="fmdb-torneos__event-info">
                <h1 class="fmdb-torneos__event-title"><?php echo esc_html( get_the_title( $event_id ) ); ?></h1>
                <?php if ( $location ) : ?>
                    <p class="fmdb-torneos__event-location">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?php echo esc_html( $location ); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="fmdb-torneos__form-card">

            <?php if ( $has_pending ) : ?>

                <div class="fmdb-torneos__already">
                    <div class="fmdb-torneos__already-icon fmdb-torneos__already-icon--pending">⏳</div>
                    <h2 class="fmdb-torneos__already-title">Registro en proceso</h2>
                    <p class="fmdb-torneos__already-text">Tu registro está siendo procesado. Una vez que tu pago haya sido reflejado tendrás acceso al directorio de equipos.</p>
                    <a href="<?php echo esc_url( home_url( '/torneos/' ) ); ?>"
                       class="fmdb-btn fmdb-btn--secondary">← Volver a torneos</a>
                </div>

            <?php elseif ( $reg_closed ) : ?>

                <div class="fmdb-torneos__closed">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <h2>Inscripción cerrada</h2>
                    <p>La inscripción para este torneo ya no está disponible.</p>
                    <a href="<?php echo esc_url( home_url( '/torneos/' ) ); ?>" class="fmdb-btn fmdb-btn--secondary">Ver otros torneos</a>
                </div>

            <?php else : ?>

                <div class="fmdb-torneos__form-header">
                    <h2 class="fmdb-torneos__form-title">Inscripción al torneo</h2>
                    <?php if ( $has_paid ) : ?>
                        <span class="fmdb-torneos__fee-badge fmdb-torneos__fee-badge--free">Gratis</span>
                    <?php elseif ( $reg_fee > 0 ) : ?>
                        <span class="fmdb-torneos__fee-badge">$<?php echo number_format( $reg_fee, 0, '.', ',' ); ?> MXN</span>
                    <?php endif; ?>
                </div>
                <?php if ( $has_paid ) : ?>
                <p class="fmdb-torneos__form-desc">Ya tienes una inscripción pagada — puedes registrarte de nuevo de forma gratuita.</p>
                <?php else : ?>
                <p class="fmdb-torneos__form-desc">Al inscribirte obtienes acceso al directorio de equipos registrados donde podrás unirte a uno.</p>
                <?php endif; ?>

                <?php if ( $deadline ) : ?>
                <p class="fmdb-torneos__deadline">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Fecha límite: <?php echo esc_html( date_i18n( 'j \d\e F, Y', strtotime( $deadline ) ) ); ?>
                </p>
                <?php endif; ?>

                <?php
                $hub_teams    = fmdb_reg_get_event_teams( $event_id );
                $captain_keys = fmdb_get_user_captain_team_keys( $current_user->ID, $event_id );
                foreach ( $hub_teams as &$ht ) {
                    $key = mb_strtolower( $ht['name'] ?? '' ) . '|' . ( $ht['rama'] ?? '' ) . '|' . ( $ht['categoria'] ?? '' );
                    $ht['_captain'] = in_array( $key, $captain_keys, true );
                }
                unset( $ht );
                usort( $hub_teams, fn( $a, $b ) => strcasecmp( $a['name'] ?? '', $b['name'] ?? '' ) );
                ?>

                <form novalidate id="fmdb-hub-form" class="fmdb-torneos__form">
                    <input type="hidden" name="fmdb_event_id" value="<?php echo $event_id; ?>">

                    <!-- Captain / player choice -->
                    <div class="fmdb-torneos__captain-q">
                        <div class="fmdb-torneos__radio-group">
                            <label class="fmdb-torneos__radio-opt" id="fmdb-opt-yes">
                                <input type="radio" name="fmdb_is_captain" value="yes">
                                <span class="fmdb-torneos__radio-label">Soy capitán/a de un equipo y lo quiero registrar</span>
                            </label>
                            <label class="fmdb-torneos__radio-opt" id="fmdb-opt-no">
                                <input type="radio" name="fmdb_is_captain" value="no">
                                <span class="fmdb-torneos__radio-label">Quiero ver los equipos registrados e inscribirme</span>
                            </label>
                        </div>
                    </div>

                    <!-- Team fields — shown when captain = yes -->
                    <div id="fmdb-team-fields" class="fmdb-torneos__team-fields" hidden>
                        <h3 class="fmdb-torneos__section-title">Datos del equipo</h3>

                        <div class="fmdb-registro__field">
                            <label for="fmdb-team-name">Nombre del equipo <span style="color:#c0392b">*</span></label>
                            <input type="text" id="fmdb-team-name" name="fmdb_team_name"
                                   placeholder="Ej. Tiburones de Naucalpan" autocomplete="off">
                        </div>

                        <div class="fmdb-afil__grid fmdb-afil__grid--2" style="gap:14px;margin-top:14px;">
                            <div class="fmdb-registro__field">
                                <label for="fmdb-rama">Rama <span style="color:#c0392b">*</span></label>
                                <select id="fmdb-rama" name="fmdb_rama">
                                    <option value="">— Seleccionar —</option>
                                    <?php foreach ( $display_ramas as $r ) : ?>
                                        <option value="<?php echo esc_attr( $r ); ?>"><?php echo esc_html( $r ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="fmdb-registro__field">
                                <label for="fmdb-categoria">Categoría <span style="color:#c0392b">*</span></label>
                                <select id="fmdb-categoria" name="fmdb_categoria">
                                    <option value="">— Seleccionar —</option>
                                    <?php foreach ( $allowed_cats as $c ) :
                                        $clabel = $c === 'Infantil' ? 'Infantil (8-12 años)' : 'Libre (13+ años)';
                                    ?>
                                        <option value="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $clabel ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="fmdb-registro__field">
                                <label for="fmdb-modalidad">Modalidad <span style="color:#c0392b">*</span></label>
                                <select id="fmdb-modalidad" name="fmdb_modalidad">
                                    <option value="">— Seleccionar —</option>
                                    <option value="Cloth">Cloth</option>
                                    <option value="Foam">Foam</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Team picker — shown when captain = no -->
                    <div id="fmdb-team-picker" class="fmdb-torneos__team-fields" hidden>
                        <?php if ( empty( $hub_teams ) ) : ?>
                            <p class="fmdb-torneos__picker-empty">
                                Aún no hay equipos registrados en este torneo.<br>
                                Sé el primero usando la opción de arriba.
                            </p>
                        <?php else : ?>
                            <h3 class="fmdb-torneos__section-title">Elige el equipo al que quieres unirte</h3>
                            <div class="fmdb-torneos__team-checklist">
                                <?php foreach ( $hub_teams as $ht ) :
                                    $is_cap = ! empty( $ht['_captain'] );
                                ?>
                                <label class="fmdb-torneos__team-check-opt<?php echo $is_cap ? ' fmdb-torneos__team-check-opt--disabled' : ''; ?>">
                                    <input type="checkbox" class="fmdb-team-check"
                                           <?php echo $is_cap ? 'disabled' : ''; ?>
                                           value="<?php echo esc_attr( wp_json_encode( [ 'name' => $ht['name'], 'rama' => $ht['rama'], 'categoria' => $ht['categoria'] ] ) ); ?>">
                                    <span class="fmdb-torneos__team-check-body">
                                        <span class="fmdb-torneos__team-check-name"><?php echo esc_html( $ht['name'] ); ?></span>
                                        <span class="fmdb-torneos__team-check-tags">
                                            <?php if ( $is_cap ) : ?>
                                            <span class="fmdb-torneos__team-tag fmdb-torneos__team-tag--own">Tu equipo</span>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $ht['modalidad'] ) ) : ?>
                                            <span class="fmdb-torneos__team-tag fmdb-torneos__team-tag--cat"><?php echo esc_html( $ht['modalidad'] ); ?></span>
                                            <?php endif; ?>
                                            <span class="fmdb-torneos__team-tag fmdb-torneos__team-tag--<?php echo esc_attr( strtolower( $ht['rama'] ) ); ?>"><?php echo esc_html( $ht['rama'] ); ?></span>
                                            <span class="fmdb-torneos__team-tag fmdb-torneos__team-tag--cat"><?php echo esc_html( $ht['categoria'] ); ?></span>
                                        </span>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="fmdb-hub-msg" class="fmdb-reg-section__msg" style="margin-top:16px;"></div>

                    <button type="button" id="fmdb-hub-submit" class="fmdb-btn fmdb-btn--primary fmdb-torneos__submit-btn" disabled>
                        Continuar al pago →
                    </button>
                </form>

                <script>
                (function () {
                    var form     = document.getElementById('fmdb-hub-form');
                    var btn      = document.getElementById('fmdb-hub-submit');
                    var msgEl    = document.getElementById('fmdb-hub-msg');
                    var fields   = document.getElementById('fmdb-team-fields');
                    var picker   = document.getElementById('fmdb-team-picker');
                    var radios   = form.querySelectorAll('input[name="fmdb_is_captain"]');
                    var ajaxUrl  = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
                    var nonce    = '<?php echo esc_js( wp_create_nonce( 'fmdb_hub_reg_submit' ) ); ?>';
                    var hasTeams = <?php echo empty( $hub_teams ) ? 'false' : 'true'; ?>;

                    function updatePickerBtn() {
                        var checked = picker.querySelectorAll('.fmdb-team-check:checked');
                        btn.disabled = !hasTeams || checked.length === 0;
                    }

                    radios.forEach(function (r) {
                        r.addEventListener('change', function () {
                            var isYes = r.value === 'yes';
                            fields.hidden = !isYes;
                            picker.hidden = isYes;
                            fields.querySelectorAll('input, select').forEach(function (el) {
                                el.required = isYes;
                            });
                            if (isYes) {
                                btn.disabled = false;
                                btn.textContent = 'Continuar al pago →';
                            } else {
                                btn.textContent = 'Terminar registro →';
                                updatePickerBtn();
                            }
                            document.getElementById('fmdb-opt-yes').classList.toggle('is-selected', isYes);
                            document.getElementById('fmdb-opt-no').classList.toggle('is-selected', !isYes);
                        });
                    });

                    picker.addEventListener('change', function (e) {
                        if (e.target.classList.contains('fmdb-team-check')) {
                            e.target.closest('.fmdb-torneos__team-check-opt').classList.toggle('is-selected', e.target.checked);
                            updatePickerBtn();
                        }
                    });

                    btn.addEventListener('click', function () {
                        if (msgEl) { msgEl.textContent = ''; msgEl.className = 'fmdb-reg-section__msg'; }

                        var chosen = form.querySelector('input[name="fmdb_is_captain"]:checked');
                        if (!chosen) {
                            msgEl.textContent = 'Selecciona una opción para continuar.';
                            msgEl.classList.add('fmdb-reg-section__msg--err');
                            return;
                        }

                        var fd = new FormData(form);
                        fd.append('action', 'fmdb_hub_reg_submit');
                        fd.append('nonce',  nonce);

                        if (chosen.value === 'yes') {
                            var teamName = form.querySelector('#fmdb-team-name');
                            var rama     = form.querySelector('#fmdb-rama');
                            var cat      = form.querySelector('#fmdb-categoria');
                            var mod      = form.querySelector('#fmdb-modalidad');
                            if (!teamName.value.trim()) {
                                teamName.focus();
                                msgEl.textContent = 'Ingresa el nombre del equipo.';
                                msgEl.classList.add('fmdb-reg-section__msg--err');
                                return;
                            }
                            if (!rama.value) {
                                rama.focus();
                                msgEl.textContent = 'Selecciona la rama.';
                                msgEl.classList.add('fmdb-reg-section__msg--err');
                                return;
                            }
                            if (!cat.value) {
                                cat.focus();
                                msgEl.textContent = 'Selecciona la categoría.';
                                msgEl.classList.add('fmdb-reg-section__msg--err');
                                return;
                            }
                            if (!mod.value) {
                                mod.focus();
                                msgEl.textContent = 'Selecciona la modalidad.';
                                msgEl.classList.add('fmdb-reg-section__msg--err');
                                return;
                            }
                        } else {
                            var checked = picker.querySelectorAll('.fmdb-team-check:checked');
                            if (checked.length === 0) {
                                msgEl.textContent = 'Selecciona al menos un equipo para continuar.';
                                msgEl.classList.add('fmdb-reg-section__msg--err');
                                return;
                            }
                            var selectedTeams = Array.from(checked).map(function (c) {
                                try { return JSON.parse(c.value); } catch(e) { return null; }
                            }).filter(Boolean);
                            fd.append('fmdb_selected_teams', JSON.stringify(selectedTeams));
                        }

                        btn.disabled = true;
                        var origText = btn.textContent;
                        btn.textContent = 'Procesando…';

                        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                            .then(function (r) { return r.json(); })
                            .then(function (res) {
                                if (res.success && res.data && res.data.cart_url) {
                                    window.location.href = res.data.cart_url;
                                } else {
                                    var msg = (res.data && res.data.message) ? res.data.message : 'Error al procesar. Intenta de nuevo.';
                                    msgEl.textContent = msg;
                                    msgEl.classList.add('fmdb-reg-section__msg--err');
                                    btn.disabled = false;
                                    btn.textContent = origText;
                                }
                            })
                            .catch(function () {
                                msgEl.textContent = 'Error de conexión. Intenta de nuevo.';
                                msgEl.classList.add('fmdb-reg-section__msg--err');
                                btn.disabled = false;
                                btn.textContent = origText;
                            });
                    });
                })();
                </script>

            <?php endif; ?>
        </div>
    </div>
</main>

<?php

} else {
    // ── Event list view ──────────────────────────────────────────────────────

    $open_events = get_posts( [
        'post_type'                    => 'tribe_events',
        'post_status'                  => 'publish',
        'posts_per_page'               => -1,
        'meta_query'                   => [ [ 'key' => '_fmdb_reg_open', 'value' => 'on' ] ],
        'orderby'                      => 'date',
        'order'                        => 'ASC',
        'tribe_suppress_query_filters' => true,
    ] );
    $open_events = array_filter( $open_events, function ( $e ) {
        return ! fmdb_reg_deadline_passed( $e->ID );
    } );
    // Sort by event start date in PHP so missing _EventStartDate doesn't exclude events.
    usort( $open_events, function ( $a, $b ) {
        $ta = get_post_meta( $a->ID, '_EventStartDate', true );
        $tb = get_post_meta( $b->ID, '_EventStartDate', true );
        return strtotime( $ta ?: '9999-01-01' ) - strtotime( $tb ?: '9999-01-01' );
    } );
?>
<main class="fmdb-torneos">
    <div class="fmdb-torneos__wrap">
        <div class="fmdb-torneos__list-header">
            <h1 class="fmdb-torneos__list-title">Torneos con inscripción abierta</h1>
            <p class="fmdb-torneos__list-desc">Inscríbete para acceder al directorio de equipos registrados y unirte a uno.</p>
        </div>

        <?php if ( empty( $open_events ) ) : ?>
            <div class="fmdb-torneos__empty">
                <p>No hay torneos con inscripción abierta en este momento.</p>
                <a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>" class="fmdb-btn fmdb-btn--secondary">Ver todos los eventos</a>
            </div>
        <?php else : ?>
            <div class="fmdb-torneos__event-list">
            <?php foreach ( $open_events as $ev ) :
                $ev_id      = $ev->ID;
                $ev_start   = get_post_meta( $ev_id, '_EventStartDate', true );
                $ev_ts      = $ev_start ? strtotime( $ev_start ) : 0;
                $ev_fee     = (float) get_post_meta( $ev_id, '_fmdb_reg_fee', true );
                $ev_venue_id = get_post_meta( $ev_id, '_EventVenueID', true );
                $ev_city    = $ev_venue_id ? get_post_meta( $ev_venue_id, '_VenueCity', true ) : '';
                $ev_state   = $ev_venue_id ? get_post_meta( $ev_venue_id, '_VenueState', true ) : '';
                $ev_loc     = implode( ', ', array_filter( [ $ev_city, $ev_state ] ) );
                $ev_dead    = get_post_meta( $ev_id, '_fmdb_reg_deadline', true );
                $ev_reg_url = add_query_arg( 'evento', $ev_id, home_url( '/torneos/' ) );
                $ev_access  = fmdb_user_has_event_access( $current_user->ID, $ev_id );
                $ev_paid    = fmdb_user_has_paid_for_event( $current_user->ID, $ev_id );
            ?>
                <div class="fmdb-torneos__event-card">
                    <?php if ( $ev_ts ) : ?>
                        <div class="fmdb-torneos__card-date">
                            <span class="fmdb-torneos__card-day"><?php echo date_i18n( 'j', $ev_ts ); ?></span>
                            <span class="fmdb-torneos__card-month"><?php echo date_i18n( 'M', $ev_ts ); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="fmdb-torneos__card-body">
                        <h2 class="fmdb-torneos__card-title"><?php echo esc_html( get_the_title( $ev_id ) ); ?></h2>
                        <?php if ( $ev_loc ) : ?>
                            <p class="fmdb-torneos__card-meta">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <?php echo esc_html( $ev_loc ); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ( $ev_dead ) : ?>
                            <p class="fmdb-torneos__card-meta fmdb-torneos__card-meta--deadline">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                Límite: <?php echo esc_html( date_i18n( 'j M Y', strtotime( $ev_dead ) ) ); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="fmdb-torneos__card-cta">
                        <?php if ( $ev_paid ) : ?>
                            <span class="fmdb-torneos__card-price" style="color:#1a73e8;font-weight:700;">Gratis</span>
                        <?php elseif ( $ev_fee > 0 ) : ?>
                            <span class="fmdb-torneos__card-price">$<?php echo number_format( $ev_fee, 0, '.', ',' ); ?> MXN</span>
                        <?php endif; ?>
                        <?php if ( $ev_access && ! $ev_paid ) : ?>
                            <a href="<?php echo esc_url( get_permalink( $ev_id ) . '#fmdb-teams-' . $ev_id ); ?>"
                               class="fmdb-btn fmdb-btn--secondary fmdb-torneos__card-btn">Ver equipos →</a>
                        <?php else : ?>
                            <a href="<?php echo esc_url( $ev_reg_url ); ?>"
                               class="fmdb-btn fmdb-btn--primary fmdb-torneos__card-btn">Inscribirme →</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php
}

get_footer();
