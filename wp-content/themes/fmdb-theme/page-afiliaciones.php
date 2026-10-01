<?php
/**
 * Template: Afiliaciones
 * Slug: afiliaciones
 */

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/login/' ) );
	exit;
}

$user_id      = get_current_user_id();
$user         = wp_get_current_user();
$affil_status = fmdb_affiliation_status( $user_id );
$is_verified  = $affil_status === 'verified';

$notices = [];

$tiers = [
	'basica'     => [
		'label' => 'Afiliación FMDB Básica',
		'sku'   => 'afiliacion-basica',
		'tag'   => 'Jugador',
		'desc'  => 'Para jugadores que participan en ligas y torneos oficiales de la FMDB.',
	],
	'plus'       => [
		'label' => 'Afiliación FMDB Plus',
		'sku'   => 'afiliacion-plus',
		'tag'   => 'Jugador Plus',
		'desc'  => 'Beneficios adicionales para jugadores con mayor actividad competitiva.',
	],
	'oro'        => [
		'label' => 'Afiliación FMDB Oro',
		'sku'   => 'afiliacion-oro',
		'tag'   => 'Élite',
		'desc'  => 'Afiliación premium para atletas de alto rendimiento en competencia nacional.',
	],
	'directivos' => [
		'label' => 'Directivos, Coaches y Árbitros',
		'sku'   => 'afiliacion-directivos',
		'tag'   => 'Cuerpo Técnico',
		'desc'  => 'Para directivos de club, entrenadores y árbitros certificados por la FMDB.',
	],
];

if ( ! $is_verified && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fmdb_afiliacion_nonce'] ) ) {
	if ( ! wp_verify_nonce( $_POST['fmdb_afiliacion_nonce'], 'fmdb_afiliacion_form' ) ) {
		$notices[] = [ 'type' => 'error', 'msg' => 'Solicitud inválida. Intenta de nuevo.' ];
	} elseif ( empty( trim( $_POST['fmdb_apellido_materno'] ?? '' ) ) ) {
		$notices[] = [ 'type' => 'error', 'msg' => 'El apellido materno es obligatorio.' ];
	} elseif ( empty( trim( $_POST['fmdb_curp'] ?? '' ) ) ) {
		$notices[] = [ 'type' => 'error', 'msg' => 'El CURP es obligatorio.' ];
	} else {
		// --- Name update ---
		$fn = sanitize_text_field( $_POST['first_name'] ?? '' ) ?: (string) $user->first_name;
		$ln = sanitize_text_field( $_POST['last_name']  ?? '' ) ?: (string) $user->last_name;
		wp_update_user( [
			'ID'           => $user_id,
			'first_name'   => $fn,
			'last_name'    => $ln,
			'display_name' => trim( "$fn $ln" ) ?: $user->user_login,
		] );

		// --- All meta fields ---
		$meta_keys = [
			'fmdb_apellido_materno', 'fmdb_fecha_nacimiento', 'fmdb_genero',
			'fmdb_curp', 'fmdb_telefono', 'fmdb_tipo_sangre', 'fmdb_email_tutor',
			'fmdb_direccion', 'fmdb_ciudad', 'fmdb_estado', 'fmdb_codigo_postal',
			'fmdb_emergencia_nombre', 'fmdb_emergencia_telefono', 'fmdb_emergencia_parentesco',
			'fmdb_club', 'fmdb_representa_estado', 'fmdb_posicion',
			'fmdb_categoria', 'fmdb_modalidad', 'fmdb_asociacion_estado',
		];
		foreach ( $meta_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_user_meta( $user_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		// --- Tier + checkout ---
		$chosen_tier = sanitize_key( $_POST['fmdb_afiliacion_tier'] ?? '' );

		if ( ! $chosen_tier || ! isset( $tiers[ $chosen_tier ] ) ) {
			$notices[] = [ 'type' => 'error', 'msg' => 'Selecciona un tipo de afiliación para continuar.' ];
		} else {
			$sku        = $tiers[ $chosen_tier ]['sku'];
			$product_id = function_exists( 'wc_get_product_id_by_sku' ) ? (int) wc_get_product_id_by_sku( $sku ) : 0;

			if ( $product_id && function_exists( 'WC' ) && WC()->cart ) {
				WC()->cart->add_to_cart( $product_id );
				wp_redirect( wc_get_checkout_url() );
				exit;
			}

			// Product not yet configured in WooCommerce — save intent and confirm.
			update_user_meta( $user_id, 'fmdb_tipo_afiliacion', $tiers[ $chosen_tier ]['label'] );
			$notices[] = [ 'type' => 'success', 'msg' => 'Tu información fue guardada. Un administrador te contactará para completar el proceso de pago.' ];
		}
	}
}

// Pre-fill values from existing user meta.
$g = fn( string $k ) => esc_attr( (string) get_user_meta( $user_id, $k, true ) );
$v = [
	'first_name'                 => esc_attr( (string) $user->first_name ),
	'last_name'                  => esc_attr( (string) $user->last_name ),
	'fmdb_apellido_materno'      => $g( 'fmdb_apellido_materno' ),
	'fmdb_fecha_nacimiento'      => $g( 'fmdb_fecha_nacimiento' ),
	'fmdb_genero'                => $g( 'fmdb_genero' ),
	'fmdb_curp'                  => $g( 'fmdb_curp' ),
	'fmdb_telefono'              => $g( 'fmdb_telefono' ),
	'fmdb_tipo_sangre'           => $g( 'fmdb_tipo_sangre' ),
	'fmdb_email_tutor'           => $g( 'fmdb_email_tutor' ),
	'fmdb_direccion'             => $g( 'fmdb_direccion' ),
	'fmdb_ciudad'                => $g( 'fmdb_ciudad' ),
	'fmdb_estado'                => $g( 'fmdb_estado' ),
	'fmdb_codigo_postal'         => $g( 'fmdb_codigo_postal' ),
	'fmdb_emergencia_nombre'     => $g( 'fmdb_emergencia_nombre' ),
	'fmdb_emergencia_telefono'   => $g( 'fmdb_emergencia_telefono' ),
	'fmdb_emergencia_parentesco' => $g( 'fmdb_emergencia_parentesco' ),
	'fmdb_club'                  => $g( 'fmdb_club' ),
	'fmdb_representa_estado'     => $g( 'fmdb_representa_estado' ),
	'fmdb_posicion'              => $g( 'fmdb_posicion' ),
	'fmdb_categoria'             => $g( 'fmdb_categoria' ),
	'fmdb_modalidad'             => $g( 'fmdb_modalidad' ),
	'fmdb_asociacion_estado'     => $g( 'fmdb_asociacion_estado' ),
];

$states = fmdb_mexican_states();

get_header();
?>

<main class="fmdb-afil">
	<div class="fmdb-afil__card">

		<div class="fmdb-afil__header">
			<h1 class="fmdb-afil__title">Afiliación FMDB</h1>
			<p class="fmdb-afil__subtitle">Completa tu información para afiliarte a la Federación Mexicana de Dodgeball</p>
		</div>

		<?php if ( $is_verified ) :
			$affiliation_id  = get_user_meta( $user_id, 'fmdb_affiliation_id', true );
			$tipo_afiliacion = get_user_meta( $user_id, 'fmdb_tipo_afiliacion', true );
			$vigencia        = get_user_meta( $user_id, 'fmdb_vigencia', true );
		?>

			<div class="fmdb-afil__already">
				<div class="fmdb-afil__already-icon">&#10003;</div>
				<h2 class="fmdb-afil__already-heading">Ya estás afiliado</h2>
				<p class="fmdb-afil__already-text">Tu membresía FMDB está activa y verificada.</p>
				<?php if ( $affiliation_id || $tipo_afiliacion || $vigencia ) : ?>
					<div class="fmdb-afil__already-details">
						<?php if ( $affiliation_id ) : ?>
							<div class="fmdb-afil__already-detail-item">
								<span class="fmdb-afil__already-detail-label">ID de afiliación</span>
								<span class="fmdb-afil__already-detail-value"><?php echo esc_html( $affiliation_id ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $tipo_afiliacion ) : ?>
							<div class="fmdb-afil__already-detail-item">
								<span class="fmdb-afil__already-detail-label">Tipo</span>
								<span class="fmdb-afil__already-detail-value"><?php echo esc_html( $tipo_afiliacion ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $vigencia ) : ?>
							<div class="fmdb-afil__already-detail-item">
								<span class="fmdb-afil__already-detail-label">Vigencia</span>
								<span class="fmdb-afil__already-detail-value"><?php echo esc_html( $vigencia ); ?></span>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<a href="<?php echo esc_url( home_url( '/mi-perfil/' ) ); ?>" class="fmdb-btn fmdb-btn--outline">Ver mi perfil</a>
			</div>

		<?php else : ?>

			<?php foreach ( $notices as $n ) : ?>
				<div class="fmdb-registro__notice fmdb-registro__notice--<?php echo esc_attr( $n['type'] ); ?>">
					<p><?php echo esc_html( $n['msg'] ); ?></p>
				</div>
			<?php endforeach; ?>

			<?php
			$has_success = ! empty( $notices ) && $notices[0]['type'] === 'success';
			if ( ! $has_success ) :
			?>

			<!-- Step indicator -->
			<div class="fmdb-afil__steps-nav" aria-label="Progreso del formulario">
				<?php
				$step_labels = [
					1 => 'Datos personales',
					2 => 'Dirección',
					3 => 'Emergencia',
					4 => 'Deporte',
					5 => 'Tipo de afiliación',
				];
				foreach ( $step_labels as $sn => $slabel ) :
				?>
					<div class="fmdb-afil__step-dot" data-step-dot="<?php echo $sn; ?>">
						<div class="fmdb-afil__step-dot-circle"><?php echo $sn; ?></div>
						<span class="fmdb-afil__step-dot-label"><?php echo esc_html( $slabel ); ?></span>
					</div>
					<?php if ( $sn < 5 ) : ?>
						<div class="fmdb-afil__step-dot-line"></div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<form class="fmdb-afil__form" method="post" id="fmdb-afil-form">
				<?php wp_nonce_field( 'fmdb_afiliacion_form', 'fmdb_afiliacion_nonce' ); ?>

				<!-- ─── Step 1: Datos personales ─────────────────────────────── -->
				<div class="fmdb-afil__step is-active" data-step="1">
					<h2 class="fmdb-afil__step-title">Datos personales</h2>

					<div class="fmdb-afil__grid fmdb-afil__grid--2">
						<div class="fmdb-registro__field">
							<label for="first_name">Nombre <span class="fmdb-afil__req">*</span></label>
							<input type="text" id="first_name" name="first_name"
								value="<?php echo $v['first_name']; ?>"
								required autocomplete="given-name">
						</div>
						<div class="fmdb-registro__field">
							<label for="last_name">Apellido paterno <span class="fmdb-afil__req">*</span></label>
							<input type="text" id="last_name" name="last_name"
								value="<?php echo $v['last_name']; ?>"
								required autocomplete="family-name">
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_apellido_materno">Apellido materno <span class="fmdb-afil__req">*</span></label>
							<input type="text" id="fmdb_apellido_materno" name="fmdb_apellido_materno"
								value="<?php echo $v['fmdb_apellido_materno']; ?>"
								autocomplete="additional-name" required>
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_fecha_nacimiento">Fecha de nacimiento <span class="fmdb-afil__req">*</span></label>
							<input type="date" id="fmdb_fecha_nacimiento" name="fmdb_fecha_nacimiento"
								value="<?php echo $v['fmdb_fecha_nacimiento']; ?>"
								required>
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_genero">Género <span class="fmdb-afil__req">*</span></label>
							<select id="fmdb_genero" name="fmdb_genero" required>
								<option value="">Selecciona…</option>
								<?php foreach ( [ 'Masculino', 'Femenino', 'No binario', 'Prefiero no decir' ] as $opt ) : ?>
									<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $v['fmdb_genero'], esc_attr( $opt ) ); ?>><?php echo esc_html( $opt ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_tipo_sangre">Tipo de sangre</label>
							<select id="fmdb_tipo_sangre" name="fmdb_tipo_sangre">
								<option value="">Selecciona…</option>
								<?php foreach ( [ 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-' ] as $bt ) : ?>
									<option value="<?php echo esc_attr( $bt ); ?>" <?php selected( $v['fmdb_tipo_sangre'], esc_attr( $bt ) ); ?>><?php echo esc_html( $bt ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_curp">CURP <span class="fmdb-afil__req">*</span></label>
							<input type="text" id="fmdb_curp" name="fmdb_curp"
								value="<?php echo $v['fmdb_curp']; ?>"
								maxlength="18" placeholder="ABCD000101HXYZXY09"
								oninput="this.value=this.value.toUpperCase()" required>
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_telefono">Teléfono <span class="fmdb-afil__req">*</span></label>
							<input type="tel" id="fmdb_telefono" name="fmdb_telefono"
								value="<?php echo $v['fmdb_telefono']; ?>"
								required autocomplete="tel"
								pattern="[0-9]+" inputmode="numeric"
								oninput="this.value=this.value.replace(/[^0-9]/g,'')">
						</div>
						<div class="fmdb-registro__field fmdb-afil__grid--span2">
							<label>Correo electrónico</label>
							<input type="email" value="<?php echo esc_attr( $user->user_email ); ?>" readonly disabled>
						</div>
						<div class="fmdb-registro__field fmdb-afil__grid--span2">
							<label for="fmdb_email_tutor">
								Correo del tutor
								<span class="fmdb-afil__field-note">(solo si eres menor de edad)</span>
							</label>
							<input type="email" id="fmdb_email_tutor" name="fmdb_email_tutor"
								value="<?php echo $v['fmdb_email_tutor']; ?>"
								autocomplete="email">
						</div>
					</div>
				</div>

				<!-- ─── Step 2: Dirección ────────────────────────────────────── -->
				<div class="fmdb-afil__step" data-step="2">
					<h2 class="fmdb-afil__step-title">Dirección</h2>

					<div class="fmdb-afil__grid fmdb-afil__grid--2">
						<div class="fmdb-registro__field fmdb-afil__grid--span2">
							<label for="fmdb_direccion">Dirección <span class="fmdb-afil__req">*</span></label>
							<input type="text" id="fmdb_direccion" name="fmdb_direccion"
								value="<?php echo $v['fmdb_direccion']; ?>"
								required placeholder="Calle, número, colonia"
								autocomplete="street-address">
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_ciudad">Ciudad <span class="fmdb-afil__req">*</span></label>
							<input type="text" id="fmdb_ciudad" name="fmdb_ciudad"
								value="<?php echo $v['fmdb_ciudad']; ?>"
								required autocomplete="address-level2">
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_codigo_postal">Código postal <span class="fmdb-afil__req">*</span></label>
							<input type="text" id="fmdb_codigo_postal" name="fmdb_codigo_postal"
								value="<?php echo $v['fmdb_codigo_postal']; ?>"
								required maxlength="5" pattern="[0-9]{5}"
								autocomplete="postal-code">
						</div>
						<div class="fmdb-registro__field fmdb-afil__grid--span2">
							<label for="fmdb_estado">Estado <span class="fmdb-afil__req">*</span></label>
							<select id="fmdb_estado" name="fmdb_estado" required>
								<option value="">Selecciona…</option>
								<?php foreach ( $states as $st ) : ?>
									<option value="<?php echo esc_attr( $st ); ?>" <?php selected( $v['fmdb_estado'], esc_attr( $st ) ); ?>><?php echo esc_html( $st ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>

				<!-- ─── Step 3: Contacto de emergencia ──────────────────────── -->
				<div class="fmdb-afil__step" data-step="3">
					<h2 class="fmdb-afil__step-title">Contacto de emergencia</h2>

					<div class="fmdb-afil__grid fmdb-afil__grid--2">
						<div class="fmdb-registro__field fmdb-afil__grid--span2">
							<label for="fmdb_emergencia_nombre">Nombre completo <span class="fmdb-afil__req">*</span></label>
							<input type="text" id="fmdb_emergencia_nombre" name="fmdb_emergencia_nombre"
								value="<?php echo $v['fmdb_emergencia_nombre']; ?>"
								required>
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_emergencia_telefono">Teléfono <span class="fmdb-afil__req">*</span></label>
							<input type="tel" id="fmdb_emergencia_telefono" name="fmdb_emergencia_telefono"
								value="<?php echo $v['fmdb_emergencia_telefono']; ?>"
								required pattern="[0-9]+" inputmode="numeric"
								oninput="this.value=this.value.replace(/[^0-9]/g,'')">
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_emergencia_parentesco">Parentesco <span class="fmdb-afil__req">*</span></label>
							<input type="text" id="fmdb_emergencia_parentesco" name="fmdb_emergencia_parentesco"
								value="<?php echo $v['fmdb_emergencia_parentesco']; ?>"
								required placeholder="Madre, padre, cónyuge…">
						</div>
					</div>
				</div>

				<!-- ─── Step 4: Información deportiva ───────────────────────── -->
				<div class="fmdb-afil__step" data-step="4">
					<h2 class="fmdb-afil__step-title">Información deportiva</h2>

					<div class="fmdb-afil__grid fmdb-afil__grid--2">
						<div class="fmdb-registro__field">
							<label for="fmdb_club">Club <span class="fmdb-afil__req">*</span></label>
							<input type="text" id="fmdb_club" name="fmdb_club"
								value="<?php echo $v['fmdb_club']; ?>"
								required>
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_asociacion_estado">Asociación / Estado</label>
							<input type="text" id="fmdb_asociacion_estado" name="fmdb_asociacion_estado"
								value="<?php echo $v['fmdb_asociacion_estado']; ?>">
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_posicion">Posición</label>
							<input type="text" id="fmdb_posicion" name="fmdb_posicion"
								value="<?php echo $v['fmdb_posicion']; ?>">
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_categoria">Categoría</label>
							<select id="fmdb_categoria" name="fmdb_categoria">
								<option value="">Selecciona…</option>
								<?php foreach ( [ 'Infantil', 'Juvenil', 'Libre' ] as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $v['fmdb_categoria'], esc_attr( $cat ) ); ?>><?php echo esc_html( $cat ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_modalidad">Modalidad</label>
							<select id="fmdb_modalidad" name="fmdb_modalidad">
								<option value="">Selecciona…</option>
								<option value="Foam"  <?php selected( $v['fmdb_modalidad'], 'Foam' );  ?>>Foam</option>
								<option value="Cloth" <?php selected( $v['fmdb_modalidad'], 'Cloth' ); ?>>Cloth</option>
							</select>
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_representa_estado">¿Representa a su estado?</label>
							<select id="fmdb_representa_estado" name="fmdb_representa_estado">
								<option value="">Selecciona…</option>
								<option value="Sí" <?php selected( $v['fmdb_representa_estado'], 'Sí' ); ?>>Sí</option>
								<option value="No" <?php selected( $v['fmdb_representa_estado'], 'No' ); ?>>No</option>
							</select>
						</div>
					</div>
				</div>

				<!-- ─── Step 5: Tipo de afiliación ───────────────────────────── -->
				<div class="fmdb-afil__step" data-step="5">
					<h2 class="fmdb-afil__step-title">Elige tu tipo de afiliación</h2>

					<div class="fmdb-afil__tier-grid">
						<?php foreach ( $tiers as $key => $tier ) : ?>
							<label class="fmdb-afil__tier-card" for="tier_<?php echo esc_attr( $key ); ?>">
								<input type="radio"
									id="tier_<?php echo esc_attr( $key ); ?>"
									name="fmdb_afiliacion_tier"
									value="<?php echo esc_attr( $key ); ?>"
									class="fmdb-afil__tier-radio">
								<div class="fmdb-afil__tier-check" aria-hidden="true"></div>
								<span class="fmdb-afil__tier-tag"><?php echo esc_html( $tier['tag'] ); ?></span>
								<span class="fmdb-afil__tier-name"><?php echo esc_html( $tier['label'] ); ?></span>
								<p class="fmdb-afil__tier-desc"><?php echo esc_html( $tier['desc'] ); ?></p>
							</label>
						<?php endforeach; ?>
					</div>
					<p class="fmdb-afil__tier-note">El costo se mostrará al proceder al pago.</p>
				</div>

				<!-- ─── Navigation ───────────────────────────────────────────── -->
				<div class="fmdb-afil__nav">
					<button type="button" class="fmdb-btn fmdb-btn--outline fmdb-afil__btn-prev" id="fmdb-afil-prev">Anterior</button>
					<button type="button" class="fmdb-btn fmdb-btn--primary fmdb-afil__btn-next" id="fmdb-afil-next">Siguiente</button>
					<button type="submit" class="fmdb-btn fmdb-btn--primary fmdb-afil__btn-submit" id="fmdb-afil-submit">Ir al pago</button>
				</div>

			</form>

			<?php endif; ?>

		<?php endif; ?>

	</div>
</main>

<script>
(function () {
	var TOTAL = 5;
	var current = 1;
	var form = document.getElementById('fmdb-afil-form');
	if (!form) return;

	var btnPrev   = document.getElementById('fmdb-afil-prev');
	var btnNext   = document.getElementById('fmdb-afil-next');
	var btnSubmit = document.getElementById('fmdb-afil-submit');

	function getStep(n) { return form.querySelector('[data-step="' + n + '"]'); }
	function getDot(n)  { return document.querySelector('[data-step-dot="' + n + '"]'); }

	function updateIndicator() {
		for (var i = 1; i <= TOTAL; i++) {
			var dot = getDot(i);
			if (!dot) continue;
			dot.classList.remove('is-active', 'is-done');
			if (i < current)  dot.classList.add('is-done');
			if (i === current) dot.classList.add('is-active');
		}
	}

	function showStep(n) {
		for (var i = 1; i <= TOTAL; i++) {
			var s = getStep(i);
			if (s) s.classList.toggle('is-active', i === n);
		}
		btnPrev.style.visibility   = n > 1 ? 'visible' : 'hidden';
		btnNext.style.display      = n < TOTAL ? 'inline-block' : 'none';
		btnSubmit.style.display    = n === TOTAL ? 'inline-block' : 'none';
		current = n;
		updateIndicator();
		var card = form.closest('.fmdb-afil__card');
		if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
	}

	function validateStep(n) {
		var step = getStep(n);
		if (!step) return true;

		var fields = step.querySelectorAll('[required]');
		var first  = null;
		var ok     = true;
		fields.forEach(function (f) {
			if (!f.value.trim()) {
				if (!first) first = f;
				ok = false;
			}
		});
		if (first) { first.focus(); first.reportValidity(); }

		if (n === TOTAL && !form.querySelector('input[name="fmdb_afiliacion_tier"]:checked')) {
			var firstCard = form.querySelector('.fmdb-afil__tier-card');
			if (firstCard) firstCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
			ok = false;
		}
		return ok;
	}

	btnNext.addEventListener('click', function () {
		if (validateStep(current)) showStep(current + 1);
	});
	btnPrev.addEventListener('click', function () {
		showStep(current - 1);
	});

	// Tier card visual toggle on radio change.
	form.querySelectorAll('.fmdb-afil__tier-radio').forEach(function (radio) {
		radio.addEventListener('change', function () {
			form.querySelectorAll('.fmdb-afil__tier-card').forEach(function (c) { c.classList.remove('is-selected'); });
			this.closest('.fmdb-afil__tier-card').classList.add('is-selected');
		});
	});

	// Enter in a text input advances to next step instead of submitting.
	form.addEventListener('keydown', function (e) {
		if (e.key !== 'Enter') return;
		if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'BUTTON') return;
		e.preventDefault();
		if (current < TOTAL && validateStep(current)) showStep(current + 1);
	});

	showStep(1);
}());
</script>

<?php get_footer(); ?>
