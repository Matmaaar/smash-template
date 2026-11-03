<?php
/**
 * Contact (/contact/) : coordonnées (page d'options « Infos smash ») + formulaire.
 * Formulaire : celui de Gravity Forms s'il existe (notifications inchangées), sinon un formulaire démo.
 */

defined( 'ABSPATH' ) || exit;

smash_page_script( 'contact' );
get_header();

$smash_gf = smash_gf_contact_id();
?>
<main class="page light" id="contenu">
	<?php get_template_part( 'template-parts/page-head' ); ?>
	<section class="sec" style="padding-top:0">
		<div class="contact-grid">
			<div class="cbox">
				<h2 class="cbox__t"><?php bloginfo( 'name' ); ?></h2>
				<?php if ( smash_option( 'address' ) ) : ?>
					<p><?php echo wp_kses( smash_option( 'address' ), array( 'br' => array() ) ); ?></p>
				<?php endif; ?>
				<?php if ( smash_option( 'hours' ) ) : ?>
					<p class="mono cbox__hours"><?php echo wp_kses( smash_option( 'hours' ), array( 'br' => array() ) ); ?></p>
				<?php endif; ?>
				<p class="cbox__links">
					<?php if ( smash_option( 'email' ) ) : ?>
						<a href="<?php echo esc_url( 'mailto:' . smash_option( 'email' ) ); ?>"><?php echo esc_html( smash_option( 'email' ) ); ?></a>
					<?php endif; ?>
					<?php if ( smash_option( 'phone' ) ) : ?>
						<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^\d+]/', '', smash_option( 'phone' ) ) ); ?>"><?php echo esc_html( smash_option( 'phone' ) ); ?></a>
					<?php endif; ?>
				</p>
				<?php smash_btn( __( 'itinéraire', 'smash' ), smash_option( 'map_url' ), 'dark' ); ?>
			</div>

			<div class="cbox">
				<h2 class="cbox__t"><?php esc_html_e( 'écris-nous', 'smash' ); ?></h2>
				<?php if ( $smash_gf ) : ?>
					<?php gravity_form( $smash_gf, false, false, false, null, true ); ?>
				<?php else : ?>
					<form class="cform" id="cform" novalidate data-rest="<?php echo esc_url( rest_url( 'smash/v1/contact' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'smash_contact' ) ); ?>" data-wpnonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>">
						<div class="field"><label for="c-name"><?php esc_html_e( 'nom', 'smash' ); ?></label><input id="c-name" name="name" autocomplete="name" required></div>
						<div class="two">
							<div class="field"><label for="c-mail"><?php esc_html_e( 'email', 'smash' ); ?></label><input id="c-mail" name="email" type="email" autocomplete="email" required></div>
							<div class="field"><label for="c-tel"><?php esc_html_e( 'téléphone', 'smash' ); ?></label><input id="c-tel" name="phone" type="tel" autocomplete="tel"></div>
						</div>
						<div class="field"><label for="c-msg"><?php esc_html_e( 'message', 'smash' ); ?></label><textarea id="c-msg" name="message" rows="5" required></textarea></div>
						<div class="hp" aria-hidden="true"><label for="c-website">Website</label><input id="c-website" name="website" tabindex="-1" autocomplete="off"></div>
						<p class="t-error cform__error" role="alert" hidden data-fallback="<?php esc_attr_e( "L'envoi n'a pas marché. Réessaie, ou écris-nous directement.", 'smash' ); ?>"></p>
						<button type="submit" class="blk dark"><?php esc_html_e( 'envoyer', 'smash' ); ?></button>
						<p class="cform__done" role="status" hidden><?php esc_html_e( "C'est envoyé, on te répond vite.", 'smash' ); ?></p>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
