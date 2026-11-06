<?php
/**
 * Template: Afiliaciones
 * Slug: afiliaciones
 */

// Respect draft status — non-admins get a 404 when the page is unpublished.
$_afil_page = get_page_by_path( 'afiliaciones' );
if ( ( ! $_afil_page || $_afil_page->post_status !== 'publish' ) && ! current_user_can( 'manage_options' ) ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	exit;
}

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

// Form submission is handled via wp_ajax_fmdb_afiliacion_submit in inc/woocommerce.php.

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
	'fmdb_asociacion_estado'       => $g( 'fmdb_asociacion_estado' ),
	'fmdb_es_extranjero'           => (string) get_user_meta( $user_id, 'fmdb_es_extranjero', true ),
	'fmdb_pais_origen'             => $g( 'fmdb_pais_origen' ),
	'fmdb_pasaporte'               => $g( 'fmdb_pasaporte' ),
	'fmdb_telefono_codigo_pais'    => esc_attr( (string) ( get_user_meta( $user_id, 'fmdb_telefono_codigo_pais',   true ) ?: '+52' ) ),
	'fmdb_emergencia_codigo_pais'  => esc_attr( (string) ( get_user_meta( $user_id, 'fmdb_emergencia_codigo_pais', true ) ?: '+52' ) ),
];

$states          = fmdb_mexican_states();
$fmdb_countries  = fmdb_countries_data();

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

		<?php elseif ( $affil_status === 'pending' ) :
			$tipo_afiliacion = get_user_meta( $user_id, 'fmdb_tipo_afiliacion', true );
			$folio_pago      = get_user_meta( $user_id, 'fmdb_folio_pago', true );
			$vigencia        = get_user_meta( $user_id, 'fmdb_vigencia', true );
		?>

			<div class="fmdb-afil__already fmdb-afil__already--pending">
				<div class="fmdb-afil__already-icon fmdb-afil__already-icon--pending">&#8987;</div>
				<h2 class="fmdb-afil__already-heading">Solicitud en revisión</h2>
				<p class="fmdb-afil__already-text">Tu solicitud de afiliación está siendo revisada por un administrador. Te notificaremos por correo cuando esté lista.</p>
				<?php if ( $tipo_afiliacion || $folio_pago || $vigencia ) : ?>
					<div class="fmdb-afil__already-details">
						<?php if ( $tipo_afiliacion ) : ?>
							<div class="fmdb-afil__already-detail-item">
								<span class="fmdb-afil__already-detail-label">Tipo</span>
								<span class="fmdb-afil__already-detail-value"><?php echo esc_html( $tipo_afiliacion ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $folio_pago ) : ?>
							<div class="fmdb-afil__already-detail-item">
								<span class="fmdb-afil__already-detail-label">Folio de pago</span>
								<span class="fmdb-afil__already-detail-value"><?php echo esc_html( $folio_pago ); ?></span>
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

			<form class="fmdb-afil__form" method="post" id="fmdb-afil-form" novalidate>
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
							<label for="fmdb_apellido_materno">Apellido materno <span class="fmdb-afil__req" id="fmdb-apellido-mat-req">*</span></label>
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
						<div class="fmdb-registro__field" id="fmdb-curp-wrap">
							<label for="fmdb_curp">CURP <span class="fmdb-afil__req">*</span></label>
							<input type="text" id="fmdb_curp" name="fmdb_curp"
								value="<?php echo $v['fmdb_curp']; ?>"
								maxlength="18" minlength="18" placeholder="ABCD000101HXYZXY09"
								oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,''); fmdbCURPLiveCheck(this);"
								onblur="fmdbCURPLiveCheck(this);"
								required>
						</div>
						<div class="fmdb-registro__field">
							<label for="fmdb_telefono">Teléfono <span class="fmdb-afil__req">*</span></label>
							<div class="fmdb-phone-wrap">
								<select id="fmdb_telefono_codigo_pais" name="fmdb_telefono_codigo_pais" class="fmdb-phone-dial">
									<?php foreach ( $fmdb_countries as [ $iso, $dial, $name_es ] ) : ?>
										<option value="<?php echo esc_attr( $dial ); ?>"
											<?php selected( $v['fmdb_telefono_codigo_pais'], $dial ); ?>>
											<?php echo esc_html( $dial . ' ' . $name_es ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<input type="tel" id="fmdb_telefono" name="fmdb_telefono"
									value="<?php echo $v['fmdb_telefono']; ?>"
									required autocomplete="tel" inputmode="numeric"
									oninput="this.value=this.value.replace(/[^0-9]/g,'')">
							</div>
						</div>
						<!-- Foreigner toggle — full width -->
						<div class="fmdb-registro__field fmdb-afil__grid--span2 fmdb-afil__foreigner-row">
							<label class="fmdb-afil__foreigner-label">
								<input type="checkbox" id="fmdb_es_extranjero" name="fmdb_es_extranjero" value="1"
									<?php checked( $v['fmdb_es_extranjero'], '1' ); ?>>
								No tengo CURP — soy extranjero o no resido en México
							</label>
						</div>
						<!-- Passport fields — shown when foreigner checkbox is on -->
						<div class="fmdb-registro__field" id="fmdb-pais-wrap"
							<?php echo $v['fmdb_es_extranjero'] !== '1' ? 'hidden' : ''; ?>>
							<label for="fmdb_pais_origen">País de origen <span class="fmdb-afil__req">*</span></label>
							<select id="fmdb_pais_origen" name="fmdb_pais_origen">
								<option value="">Selecciona…</option>
								<?php foreach ( $fmdb_countries as [ $iso, $dial, $name_es ] ) : ?>
									<option value="<?php echo esc_attr( $iso ); ?>"
										<?php selected( $v['fmdb_pais_origen'], $iso ); ?>>
										<?php echo esc_html( $name_es ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="fmdb-registro__field" id="fmdb-pasaporte-wrap"
							<?php echo $v['fmdb_es_extranjero'] !== '1' ? 'hidden' : ''; ?>>
							<label for="fmdb_pasaporte">Núm. de pasaporte <span class="fmdb-afil__req">*</span></label>
							<input type="text" id="fmdb_pasaporte" name="fmdb_pasaporte"
								value="<?php echo $v['fmdb_pasaporte']; ?>"
								maxlength="20" placeholder="AB123456"
								oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,''); fmdbPassportLiveCheck();">
							<small id="fmdb-pasaporte-hint" class="fmdb-afil__field-note"></small>
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
							<div class="fmdb-phone-wrap">
								<select id="fmdb_emergencia_codigo_pais" name="fmdb_emergencia_codigo_pais" class="fmdb-phone-dial">
									<?php foreach ( $fmdb_countries as [ $iso, $dial, $name_es ] ) : ?>
										<option value="<?php echo esc_attr( $dial ); ?>"
											<?php selected( $v['fmdb_emergencia_codigo_pais'], $dial ); ?>>
											<?php echo esc_html( $dial . ' ' . $name_es ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<input type="tel" id="fmdb_emergencia_telefono" name="fmdb_emergencia_telefono"
									value="<?php echo $v['fmdb_emergencia_telefono']; ?>"
									required inputmode="numeric"
									oninput="this.value=this.value.replace(/[^0-9]/g,'')">
							</div>
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
							<label for="fmdb_club">Club</label>
							<input type="text" id="fmdb_club" name="fmdb_club"
								value="<?php echo $v['fmdb_club']; ?>">
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
					<button type="button" class="fmdb-btn fmdb-btn--primary fmdb-afil__btn-submit" id="fmdb-afil-submit">Ir al pago</button>
				</div>

			</form>

			<?php endif; ?>

		<?php endif; ?>

	</div>
</main>

<script>
// CURP structure validator (format only — RENAPO registry lookup requires a paid API).
function fmdbValidateCURP(curp) {
	if (!curp || curp.length !== 18) return 'El CURP debe tener exactamente 18 caracteres.';
	var re = /^[A-Z][AEIOUX][A-Z]{2}\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[HM](?:AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[BCDFGHJKLMNPQRSTVWXYZ]{3}[0-9A-Z]\d$/;
	if (!re.test(curp)) return 'El CURP no tiene un formato válido. Revisa fecha, código de estado y consonantes.';
	return null;
}
function fmdbCURPLiveCheck(input) {
	var err = fmdbValidateCURP(input.value);
	input.setCustomValidity(err || '');
	if (err && input.value.length === 18) input.reportValidity();
}

// Passport format patterns by ISO 3166-1 alpha-2 country code.
var FMDB_PASSPORT_FORMATS = {
	'AR':{re:/^[A-Z]{3}[0-9]{6}$/,ex:'ABC123456'},
	'BO':{re:/^[0-9]{6,8}$/,ex:'12345678'},
	'BR':{re:/^[A-Z]{2}[0-9]{6}$/,ex:'AB123456'},
	'CA':{re:/^[A-Z]{2}[0-9]{6}$/,ex:'AB123456'},
	'CL':{re:/^[A-Z][0-9]{7,9}$/,ex:'A12345678'},
	'CO':{re:/^[A-Z]{2}[0-9]{6}$/,ex:'AB123456'},
	'CR':{re:/^[A-Z][0-9]{7}$/,ex:'A1234567'},
	'CU':{re:/^[A-Z][0-9]{7}$/,ex:'K1234567'},
	'DE':{re:/^[A-Z0-9]{9}$/,ex:'C01X00T47'},
	'DO':{re:/^[A-Z]{2}[0-9]{6}$/,ex:'AB123456'},
	'EC':{re:/^[A-Z][0-9]{7,8}$/,ex:'A12345678'},
	'ES':{re:/^[A-Z0-9]{9}$/,ex:'XYZ123456'},
	'FR':{re:/^[0-9]{2}[A-Z]{2}[0-9]{5}$/,ex:'12AB12345'},
	'GB':{re:/^[0-9]{9}$/,ex:'123456789'},
	'GT':{re:/^[A-Z][0-9]{7}$/,ex:'A1234567'},
	'HN':{re:/^[A-Z][0-9]{7}$/,ex:'A1234567'},
	'IT':{re:/^[A-Z]{2}[0-9]{7}$/,ex:'AB1234567'},
	'JP':{re:/^[A-Z]{2}[0-9]{7}$/,ex:'AB1234567'},
	'MX':{re:/^[A-Z][0-9]{8}$/,ex:'G12345678'},
	'NI':{re:/^[A-Z][0-9]{7}$/,ex:'A1234567'},
	'PA':{re:/^[A-Z0-9]{9}$/,ex:'A12345678'},
	'PE':{re:/^[A-Z0-9]{8,9}$/,ex:'AB123456'},
	'PY':{re:/^[A-Z]{2}[0-9]{6}$/,ex:'AB123456'},
	'SV':{re:/^[A-Z][0-9]{7}$/,ex:'A1234567'},
	'US':{re:/^[A-Z0-9]{9}$/,ex:'A12345678'},
	'UY':{re:/^[A-Z]{2}[0-9]{6}$/,ex:'AB123456'},
	'VE':{re:/^[A-Z][0-9]{7,8}$/,ex:'A12345678'}
};
// Map ISO code → dial code (for auto-syncing the phone selector).
var FMDB_DIAL_CODES = {
	'AR':'+54','BO':'+591','BR':'+55','CA':'+1','CL':'+56','CO':'+57',
	'CR':'+506','CU':'+53','DE':'+49','DO':'+1','EC':'+593','ES':'+34',
	'FR':'+33','GB':'+44','GT':'+502','HN':'+504','IT':'+39','JP':'+81',
	'MX':'+52','NI':'+505','PA':'+507','PE':'+51','PY':'+595','SV':'+503',
	'US':'+1','UY':'+598','VE':'+58'
};

function fmdbValidatePassport(country, passport) {
	if (!passport) return 'El número de pasaporte es obligatorio.';
	var fmt = FMDB_PASSPORT_FORMATS[country];
	if (fmt) {
		if (!fmt.re.test(passport)) return 'Formato para este país: ' + fmt.ex;
	} else {
		if (!/^[A-Z0-9]{4,20}$/.test(passport)) return 'Ingresa entre 4 y 20 caracteres alfanuméricos.';
	}
	return null;
}
function fmdbPassportLiveCheck() {
	var passInput = document.getElementById('fmdb_pasaporte');
	var paisSel   = document.getElementById('fmdb_pais_origen');
	if (!passInput) return;
	var err = fmdbValidatePassport(paisSel ? paisSel.value : '', passInput.value);
	passInput.setCustomValidity(err || '');
}

(function () {
	var TOTAL = 5;
	var current = 1;
	var form = document.getElementById('fmdb-afil-form');
	if (!form) return;

	var btnPrev   = document.getElementById('fmdb-afil-prev');
	var btnNext   = document.getElementById('fmdb-afil-next');
	var btnSubmit = document.getElementById('fmdb-afil-submit');

	// ── Foreigner toggle ──────────────────────────────────────────────────
	var foreignerChk     = document.getElementById('fmdb_es_extranjero');
	var curpInput        = document.getElementById('fmdb_curp');
	var paisWrap         = document.getElementById('fmdb-pais-wrap');
	var pasaporteWrap    = document.getElementById('fmdb-pasaporte-wrap');
	var paisSel          = document.getElementById('fmdb_pais_origen');
	var pasInput         = document.getElementById('fmdb_pasaporte');
	var dialSel          = document.getElementById('fmdb_telefono_codigo_pais');
	var apellidoMatInput = document.getElementById('fmdb_apellido_materno');
	var apellidoMatReq   = document.getElementById('fmdb-apellido-mat-req');

	function fmdbToggleForeigner(isForeigner) {
		if (curpInput) {
			curpInput.required = !isForeigner;
			curpInput.disabled = isForeigner;
			curpInput.setCustomValidity('');
			if (isForeigner) curpInput.value = '';
		}
		if (paisWrap)      paisWrap.hidden      = !isForeigner;
		if (pasaporteWrap) pasaporteWrap.hidden  = !isForeigner;
		if (paisSel)       paisSel.required      = isForeigner;
		if (pasInput) {
			pasInput.required = isForeigner;
			pasInput.setCustomValidity('');
		}
		// Apellido materno is optional for foreigners
		if (apellidoMatInput) apellidoMatInput.required = !isForeigner;
		if (apellidoMatReq)   apellidoMatReq.hidden      = isForeigner;
	}

	if (foreignerChk) {
		foreignerChk.addEventListener('change', function () { fmdbToggleForeigner(this.checked); });
		fmdbToggleForeigner(foreignerChk.checked);
	}

	// Auto-update passport placeholder + phone dial code when origin country changes
	if (paisSel) {
		paisSel.addEventListener('change', function () {
			var fmt = FMDB_PASSPORT_FORMATS[this.value];
			if (pasInput) pasInput.placeholder = fmt ? fmt.ex : 'AB123456';
			if (dialSel && FMDB_DIAL_CODES[this.value]) dialSel.value = FMDB_DIAL_CODES[this.value];
		});
	}
	// ─────────────────────────────────────────────────────────────────────

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

		if (n === 1) {
			var isForeigner = foreignerChk && foreignerChk.checked;
			if (!isForeigner) {
				// CURP format check
				if (curpInput && curpInput.value) {
					var curpErr = fmdbValidateCURP(curpInput.value);
					if (curpErr) {
						curpInput.setCustomValidity(curpErr);
						curpInput.focus();
						curpInput.reportValidity();
						ok = false;
					}
				}
			} else {
				// Passport format check
				if (pasInput && pasInput.value) {
					var country = paisSel ? paisSel.value : '';
					var passErr = fmdbValidatePassport(country, pasInput.value);
					if (passErr) {
						pasInput.setCustomValidity(passErr);
						pasInput.focus();
						pasInput.reportValidity();
						ok = false;
					}
				}
			}
		}

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

	btnSubmit.addEventListener('click', function () {
		if (!validateStep(TOTAL)) return;

		btnSubmit.disabled = true;
		btnSubmit.textContent = 'Procesando…';

		var data = new FormData(form);
		data.append('action', 'fmdb_afiliacion_submit');
		data.append('nonce',  '<?php echo wp_create_nonce( "fmdb_afiliacion_submit" ); ?>');

		fetch('<?php echo esc_url( admin_url( "admin-ajax.php" ) ); ?>', {
			method: 'POST',
			body: data,
			credentials: 'same-origin'
		})
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (res.success && res.data && res.data.checkout_url) {
				window.location.href = res.data.checkout_url;
			} else {
				var msg = (res.data && res.data.message) ? res.data.message : 'Error al procesar. Intenta de nuevo.';
				alert(msg);
				btnSubmit.disabled = false;
				btnSubmit.textContent = 'Ir al pago';
			}
		})
		.catch(function() {
			alert('Error de conexión. Intenta de nuevo.');
			btnSubmit.disabled = false;
			btnSubmit.textContent = 'Ir al pago';
		});
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
