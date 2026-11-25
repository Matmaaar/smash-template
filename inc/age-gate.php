<?php
/**
 * Porte d'âge.
 *
 * Toujours présente dans le HTML (compatible avec le cache de page) ; masquée en CSS
 * quand le cookie `smash_age` existe (classe `age-ok` posée dans le <head>) ou sans JS.
 * Le clic sur « oui » pose le cookie pour un an (assets/js/core.js).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_body_open', function () {
	if ( is_admin() || is_customize_preview() ) {
		return;
	}
	?>
	<div class="gate" id="gate" role="dialog" aria-modal="true" aria-labelledby="gate-title" aria-describedby="gate-text">
		<div class="gate__in">
			<div class="S" id="gateS" aria-hidden="true"><i></i><i></i><i></i></div>
			<p class="gate__text" id="gate-text"><?php echo esc_html( smash_option( 'age_text', __( 'Vous devez avoir 18 ans ou plus pour accéder à ce site.', 'smash' ) ) ); ?></p>
			<h2 class="big" id="gate-title"><?php echo esc_html( smash_option( 'age_title', __( 'Êtes-vous âgé de 18 ans ou plus ?', 'smash' ) ) ); ?></h2>
			<div class="gate__btns">
				<button type="button" class="blk" id="yes"><?php echo esc_html( smash_option( 'age_yes', __( 'Oui', 'smash' ) ) ); ?></button>
				<button type="button" class="blk ghost" id="no"><?php echo esc_html( smash_option( 'age_no', __( 'Non', 'smash' ) ) ); ?></button>
			</div>
			<p class="gate__no" id="nomsg" role="status"><?php echo esc_html( smash_option( 'age_refused', __( "Désolé, l'accès à ce site est réservé aux personnes majeures.", 'smash' ) ) ); ?></p>
			<small class="mono"><?php echo esc_html( smash_option( 'warning' ) ); ?></small>
		</div>
	</div>
	<?php
} );
