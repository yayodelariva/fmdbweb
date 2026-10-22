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
    $valid_ramas   = [ 'Varonil', 'Femenil', 'Mixto' ];
    $allowed_ramas = array_values( array_intersect( $allowed_ramas, $valid_ramas ) );
    if ( empty( $allowed_ramas ) ) $allowed_ramas = $valid_ramas;
    if ( empty( $allowed_cats ) )  $allowed_cats  = array_keys( fmdb_reg_all_categories() );
    $display_ramas = $allowed_ramas;
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
                <p class="fmdb-torneos__form-desc">Al hacer el pago único de inscripción, obtienes acceso a la lista de equipos donde podrás unirte a cualquiera de ellos.</p>
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
                $player_keys  = fmdb_get_user_player_team_keys( $current_user->ID, $event_id );
                foreach ( $hub_teams as &$ht ) {
                    $key = fmdb_team_key( $ht['name'] ?? '', $ht['rama'] ?? '', $ht['categoria'] ?? '' );
                    $ht['_captain'] = in_array( $key, $captain_keys, true );
                    $ht['_member']  = ! $ht['_captain'] && in_array( $key, $player_keys, true );
                }
                unset( $ht );
                $hub_cat_order = array_flip( array_keys( fmdb_reg_all_categories() ) );
                $hub_mod_order = [ 'Cloth' => 0, 'Foam' => 1 ];
                $hub_rama_order = [ 'Femenil' => 0, 'Mixto' => 1, 'Varonil' => 2 ];
                usort( $hub_teams, function ( $a, $b ) use ( $hub_cat_order, $hub_mod_order, $hub_rama_order ) {
                    $nc = strcasecmp( $a['name'] ?? '', $b['name'] ?? '' );
                    if ( $nc !== 0 ) return $nc;
                    $ra = $hub_rama_order[ $a['rama'] ?? '' ] ?? 99;
                    $rb = $hub_rama_order[ $b['rama'] ?? '' ] ?? 99;
                    if ( $ra !== $rb ) return $ra <=> $rb;
                    $ma = $hub_mod_order[ $a['modalidad'] ?? '' ] ?? 99;
                    $mb = $hub_mod_order[ $b['modalidad'] ?? '' ] ?? 99;
                    if ( $ma !== $mb ) return $ma <=> $mb;
                    $ca = $hub_cat_order[ $a['categoria'] ?? '' ] ?? 99;
                    $cb = $hub_cat_order[ $b['categoria'] ?? '' ] ?? 99;
                    return $ca <=> $cb;
                } );
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

                    <!-- Captain area — shown when captain = yes -->
                    <div id="fmdb-captain-area" hidden>

                        <!-- Added team cards -->
                        <div id="fmdb-team-cards" class="fmdb-team-cards"></div>

                        <!-- Inline add/edit form -->
                        <div id="fmdb-team-form-wrap" class="fmdb-team-form-card" hidden>
                            <h4 id="fmdb-team-form-title" class="fmdb-team-form-card__title">Nuevo equipo</h4>
                            <div class="fmdb-afil__grid fmdb-afil__grid--2" style="gap:14px;margin-bottom:14px;">
                                <div class="fmdb-registro__field">
                                    <label for="fmdb-tf-nombre">Nombre</label>
                                    <input type="text" id="fmdb-tf-nombre" autocomplete="off" readonly class="fmdb-input--readonly">
                                </div>
                                <div class="fmdb-registro__field">
                                    <label for="fmdb-tf-apellido">Apellido</label>
                                    <input type="text" id="fmdb-tf-apellido" autocomplete="off" readonly class="fmdb-input--readonly">
                                </div>
                            </div>
                            <div class="fmdb-registro__field">
                                <label for="fmdb-tf-name">Nombre del equipo <span aria-hidden="true" style="color:#c0392b">*</span></label>
                                <input type="text" id="fmdb-tf-name" placeholder="Ej. Tiburones de Naucalpan" autocomplete="off">
                            </div>
                            <div class="fmdb-afil__grid fmdb-afil__grid--2" style="gap:14px;margin-top:14px;">
                                <div class="fmdb-registro__field">
                                    <label for="fmdb-tf-rama">Rama <span aria-hidden="true" style="color:#c0392b">*</span></label>
                                    <select id="fmdb-tf-rama">
                                        <option value="">— Seleccionar —</option>
                                        <?php foreach ( $display_ramas as $r ) : ?>
                                            <option value="<?php echo esc_attr( $r ); ?>"><?php echo esc_html( $r ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="fmdb-registro__field">
                                    <label for="fmdb-tf-categoria">Categoría <span aria-hidden="true" style="color:#c0392b">*</span></label>
                                    <select id="fmdb-tf-categoria">
                                        <option value="">— Seleccionar —</option>
                                        <?php
                                        $_all_cat_labels = fmdb_reg_all_categories();
                                        foreach ( $allowed_cats as $c ) :
                                            $clabel = $_all_cat_labels[ $c ] ?? $c;
                                        ?>
                                            <option value="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $clabel ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="fmdb-registro__field">
                                    <label for="fmdb-tf-modalidad">Modalidad <span aria-hidden="true" style="color:#c0392b">*</span></label>
                                    <select id="fmdb-tf-modalidad">
                                        <option value="">— Seleccionar —</option>
                                        <option value="Cloth">Cloth</option>
                                        <option value="Foam">Foam</option>
                                    </select>
                                </div>
                            </div>
                            <div id="fmdb-tf-msg" class="fmdb-reg-section__msg" style="margin-top:10px;"></div>
                            <div class="fmdb-team-form-card__actions">
                                <button type="button" id="fmdb-tf-save" class="fmdb-btn fmdb-btn--primary">Guardar equipo</button>
                                <button type="button" id="fmdb-tf-cancel" class="fmdb-btn fmdb-btn--secondary">Cancelar</button>
                            </div>
                        </div>

                        <!-- Add-another card (always visible) -->
                        <button type="button" id="fmdb-add-team-card" class="fmdb-add-team-card">
                            <span class="fmdb-add-team-card__plus" aria-hidden="true">+</span>
                            <span>Agregar equipo</span>
                        </button>

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
                                    $is_cap  = ! empty( $ht['_captain'] );
                                    $is_mem  = ! empty( $ht['_member'] );
                                    $is_dis  = $is_cap || $is_mem;
                                ?>
                                <label class="fmdb-torneos__team-check-opt<?php echo $is_dis ? ' fmdb-torneos__team-check-opt--disabled' : ''; ?>">
                                    <input type="checkbox" class="fmdb-team-check"
                                           <?php echo $is_dis ? 'disabled' : ''; ?>
                                           value="<?php echo esc_attr( wp_json_encode( [ 'name' => $ht['name'], 'rama' => $ht['rama'], 'categoria' => $ht['categoria'], 'modalidad' => $ht['modalidad'] ?? '' ] ) ); ?>">
                                    <span class="fmdb-torneos__team-check-body">
                                        <span class="fmdb-torneos__team-check-name"><?php echo esc_html( $ht['name'] ); ?></span>
                                        <span class="fmdb-torneos__team-check-tags">
                                            <?php if ( $is_cap ) : ?>
                                            <span class="fmdb-torneos__team-tag fmdb-torneos__team-tag--own">Tu equipo</span>
                                            <?php elseif ( $is_mem ) : ?>
                                            <span class="fmdb-torneos__team-tag fmdb-torneos__team-tag--member">Ya eres miembro</span>
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

                    <!-- Captain checkout (shown when captain = yes) -->
                    <button type="button" id="fmdb-captain-checkout"
                            class="fmdb-btn fmdb-btn--primary fmdb-torneos__submit-btn" hidden disabled>
                        Continuar al pago →
                    </button>

                    <!-- Player submit (shown when captain = no) -->
                    <button type="button" id="fmdb-hub-submit" class="fmdb-btn fmdb-btn--primary fmdb-torneos__submit-btn" disabled>
                        Continuar al pago →
                    </button>
                </form>

                <script>
                (function () {
                    var form        = document.getElementById('fmdb-hub-form');
                    var playerBtn   = document.getElementById('fmdb-hub-submit');
                    var captainBtn  = document.getElementById('fmdb-captain-checkout');
                    var msgEl       = document.getElementById('fmdb-hub-msg');
                    var captainArea = document.getElementById('fmdb-captain-area');
                    var picker      = document.getElementById('fmdb-team-picker');
                    var radios      = form.querySelectorAll('input[name="fmdb_is_captain"]');
                    var ajaxUrl     = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
                    var nonce       = '<?php echo esc_js( wp_create_nonce( 'fmdb_hub_reg_submit' ) ); ?>';
                    var hasTeams    = <?php echo empty( $hub_teams ) ? 'false' : 'true'; ?>;
                    <?php
                    $dn_js = is_user_logged_in() ? fmdb_get_display_names( get_current_user_id() ) : [ 'nombre' => '', 'apellido' => '' ];
                    ?>
                    var defaultNombre   = '<?php echo esc_js( $dn_js['nombre'] ); ?>';
                    var defaultApellido = '<?php echo esc_js( $dn_js['apellido'] ); ?>';

                    var formWrap  = document.getElementById('fmdb-team-form-wrap');
                    var formTitle = document.getElementById('fmdb-team-form-title');
                    var tfNombre  = document.getElementById('fmdb-tf-nombre');
                    var tfApellido= document.getElementById('fmdb-tf-apellido');
                    var tfName    = document.getElementById('fmdb-tf-name');
                    var tfRama    = document.getElementById('fmdb-tf-rama');
                    var tfCat     = document.getElementById('fmdb-tf-categoria');
                    var tfMod     = document.getElementById('fmdb-tf-modalidad');
                    var tfMsg     = document.getElementById('fmdb-tf-msg');
                    var tfSave    = document.getElementById('fmdb-tf-save');
                    var tfCancel  = document.getElementById('fmdb-tf-cancel');
                    var addCard   = document.getElementById('fmdb-add-team-card');
                    var cardsEl   = document.getElementById('fmdb-team-cards');

                    var teams     = [];
                    var editingId = null;
                    var nextId    = 0;

                    function escHtml(s) {
                        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                    }

                    function renderTeams() {
                        cardsEl.innerHTML = '';
                        teams.forEach(function (t) {
                            var meta = [t.rama, t.modalidad, t.categoria].filter(Boolean).join(' · ');
                            var card = document.createElement('div');
                            card.className = 'fmdb-team-card';
                            card.innerHTML =
                                '<div class="fmdb-team-card__info">' +
                                    '<div class="fmdb-team-card__name">' + escHtml(t.name) + '</div>' +
                                    '<div class="fmdb-team-card__meta">' + escHtml(meta) + '</div>' +
                                '</div>' +
                                '<div class="fmdb-team-card__actions">' +
                                    '<button type="button" class="fmdb-team-card__btn fmdb-team-card__btn--edit" data-id="' + t.id + '">Editar</button>' +
                                    '<button type="button" class="fmdb-team-card__btn fmdb-team-card__btn--del" data-id="' + t.id + '">Eliminar</button>' +
                                '</div>';
                            cardsEl.appendChild(card);
                        });
                        captainBtn.disabled = teams.length === 0;
                    }

                    function openForm(team) {
                        editingId           = team ? team.id : null;
                        formTitle.textContent = team ? 'Editar equipo' : 'Nuevo equipo';
                        tfNombre.value   = team ? team.nombre   : defaultNombre;
                        tfApellido.value = team ? team.apellido : defaultApellido;
                        tfName.value = team ? team.name      : '';
                        tfRama.value = team ? team.rama      : '';
                        tfCat.value  = team ? team.categoria : '';
                        tfMod.value  = team ? team.modalidad : '';
                        tfMsg.textContent = '';
                        tfMsg.className   = 'fmdb-reg-section__msg';
                        formWrap.hidden   = false;
                        tfName.focus();
                        formWrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }

                    function closeForm() {
                        formWrap.hidden = true;
                        editingId = null;
                    }

                    function saveTeam() {
                        var nombre   = tfNombre.value.trim();
                        var apellido = tfApellido.value.trim();
                        var name = tfName.value.trim();
                        var rama = tfRama.value;
                        var cat  = tfCat.value;
                        var mod  = tfMod.value;
                        if (!name) { tfMsg.textContent = 'Ingresa el nombre del equipo.'; tfMsg.classList.add('fmdb-reg-section__msg--err'); tfName.focus(); return; }
                        if (!rama) { tfMsg.textContent = 'Selecciona la rama.';            tfMsg.classList.add('fmdb-reg-section__msg--err'); tfRama.focus(); return; }
                        if (!cat)  { tfMsg.textContent = 'Selecciona la categoría.';       tfMsg.classList.add('fmdb-reg-section__msg--err'); tfCat.focus();  return; }
                        if (!mod)  { tfMsg.textContent = 'Selecciona la modalidad.';       tfMsg.classList.add('fmdb-reg-section__msg--err'); tfMod.focus();  return; }
                        var dup = teams.some(function (t) {
                            if (editingId !== null && t.id === editingId) return false;
                            return t.name.trim().toLowerCase() === name.toLowerCase() && t.rama === rama && t.categoria === cat;
                        });
                        if (dup) { tfMsg.textContent = 'Ya tienes ese equipo en tu lista.'; tfMsg.classList.add('fmdb-reg-section__msg--err'); return; }
                        if (editingId !== null) {
                            var idx = teams.findIndex(function (t) { return t.id === editingId; });
                            if (idx >= 0) teams[idx] = { id: editingId, nombre: nombre, apellido: apellido, name: name, rama: rama, categoria: cat, modalidad: mod };
                        } else {
                            teams.push({ id: nextId++, nombre: nombre, apellido: apellido, name: name, rama: rama, categoria: cat, modalidad: mod });
                        }
                        closeForm();
                        renderTeams();
                    }

                    // Radio toggle
                    radios.forEach(function (r) {
                        r.addEventListener('change', function () {
                            var isYes = r.value === 'yes';
                            captainArea.hidden = !isYes;
                            picker.hidden      = isYes;
                            captainBtn.hidden  = !isYes;
                            playerBtn.hidden   = isYes;
                            if (!isYes) updatePickerBtn();
                            document.getElementById('fmdb-opt-yes').classList.toggle('is-selected', isYes);
                            document.getElementById('fmdb-opt-no').classList.toggle('is-selected', !isYes);
                        });
                    });

                    function updatePickerBtn() {
                        var checked = picker.querySelectorAll('.fmdb-team-check:checked');
                        playerBtn.disabled = !hasTeams || checked.length === 0;
                    }

                    picker.addEventListener('change', function (e) {
                        if (e.target.classList.contains('fmdb-team-check')) {
                            e.target.closest('.fmdb-torneos__team-check-opt').classList.toggle('is-selected', e.target.checked);
                            updatePickerBtn();
                        }
                    });

                    addCard.addEventListener('click', function () {
                        if (!formWrap.hidden && editingId === null) { tfName.focus(); return; }
                        openForm(null);
                    });

                    tfSave.addEventListener('click', saveTeam);
                    tfCancel.addEventListener('click', closeForm);

                    cardsEl.addEventListener('click', function (e) {
                        var editBtn = e.target.closest('.fmdb-team-card__btn--edit');
                        var delBtn  = e.target.closest('.fmdb-team-card__btn--del');
                        if (editBtn) {
                            var id   = parseInt(editBtn.dataset.id, 10);
                            var team = teams.find(function (t) { return t.id === id; });
                            if (team) openForm(team);
                        } else if (delBtn) {
                            var id = parseInt(delBtn.dataset.id, 10);
                            teams = teams.filter(function (t) { return t.id !== id; });
                            if (editingId === id) closeForm();
                            renderTeams();
                        }
                    });

                    // Captain checkout — send all teams at once
                    captainBtn.addEventListener('click', function () {
                        if (teams.length === 0) return;
                        if (msgEl) { msgEl.textContent = ''; msgEl.className = 'fmdb-reg-section__msg'; }
                        captainBtn.disabled = true;
                        var origText = captainBtn.textContent;
                        captainBtn.textContent = 'Procesando…';

                        var fd = new FormData(form);
                        fd.set('fmdb_is_captain', 'yes');
                        fd.append('action', 'fmdb_hub_reg_submit');
                        fd.append('nonce', nonce);
                        fd.append('fmdb_teams', JSON.stringify(teams.map(function (t) {
                            return { nombre: t.nombre, apellido: t.apellido, name: t.name, rama: t.rama, categoria: t.categoria, modalidad: t.modalidad };
                        })));

                        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                            .then(function (r) { return r.json(); })
                            .then(function (res) {
                                if (res.success && res.data && res.data.cart_url) {
                                    window.location.href = res.data.cart_url;
                                } else {
                                    var msg = (res.data && res.data.message) ? res.data.message : 'Error al procesar. Intenta de nuevo.';
                                    if (msgEl) { msgEl.textContent = msg; msgEl.classList.add('fmdb-reg-section__msg--err'); }
                                    captainBtn.disabled = false;
                                    captainBtn.textContent = origText;
                                }
                            })
                            .catch(function () {
                                if (msgEl) { msgEl.textContent = 'Error de conexión. Intenta de nuevo.'; msgEl.classList.add('fmdb-reg-section__msg--err'); }
                                captainBtn.disabled = false;
                                captainBtn.textContent = origText;
                            });
                    });

                    // Player submit
                    playerBtn.addEventListener('click', function () {
                        if (msgEl) { msgEl.textContent = ''; msgEl.className = 'fmdb-reg-section__msg'; }
                        var checked = picker.querySelectorAll('.fmdb-team-check:checked');
                        if (checked.length === 0) {
                            if (msgEl) { msgEl.textContent = 'Selecciona al menos un equipo para continuar.'; msgEl.classList.add('fmdb-reg-section__msg--err'); }
                            return;
                        }
                        var selectedTeams = Array.from(checked).map(function (c) {
                            try { return JSON.parse(c.value); } catch(e) { return null; }
                        }).filter(Boolean);
                        playerBtn.disabled = true;
                        var origText = playerBtn.textContent;
                        playerBtn.textContent = 'Procesando…';

                        var fd = new FormData(form);
                        fd.append('action', 'fmdb_hub_reg_submit');
                        fd.append('nonce', nonce);
                        fd.append('fmdb_selected_teams', JSON.stringify(selectedTeams));

                        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                            .then(function (r) { return r.json(); })
                            .then(function (res) {
                                if (res.success && res.data && res.data.cart_url) {
                                    window.location.href = res.data.cart_url;
                                } else {
                                    var msg = (res.data && res.data.message) ? res.data.message : 'Error al procesar. Intenta de nuevo.';
                                    if (msgEl) { msgEl.textContent = msg; msgEl.classList.add('fmdb-reg-section__msg--err'); }
                                    playerBtn.disabled = false;
                                    playerBtn.textContent = origText;
                                }
                            })
                            .catch(function () {
                                if (msgEl) { msgEl.textContent = 'Error de conexión. Intenta de nuevo.'; msgEl.classList.add('fmdb-reg-section__msg--err'); }
                                playerBtn.disabled = false;
                                playerBtn.textContent = origText;
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
