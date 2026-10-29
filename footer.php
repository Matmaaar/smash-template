<?php
/**
 * Pied de page : infos globales de la page d'options « Infos smash ».
 */

defined( 'ABSPATH' ) || exit;

$smash_social = array_filter( array(
	'instagram' => smash_option( 'instagram' ),
	'facebook'  => smash_option( 'facebook' ),
	'linkedin'  => smash_option( 'linkedin' ),
	'untappd'   => smash_option( 'untappd' ),
) );
?>
<footer class="foot">
	<div class="foot__word"><b><?php bloginfo( 'name' ); ?></b><span><?php bloginfo( 'description' ); ?></span></div>
	<div class="foot__cols">
		<?php if ( smash_option( 'address' ) ) : ?>
			<div><div class="mono"><?php esc_html_e( 'adresse', 'smash' ); ?></div><?php echo wp_kses( smash_option( 'address' ), array( 'br' => array() ) ); ?></div>
		<?php endif; ?>
		<?php if ( smash_option( 'hours' ) ) : ?>
			<div><div class="mono"><?php esc_html_e( 'horaires', 'smash' ); ?></div><?php echo wp_kses( smash_option( 'hours' ), array( 'br' => array() ) ); ?></div>
		<?php endif; ?>
		<?php if ( has_nav_menu( 'footer' ) ) : ?>
			<div><div class="mono"><?php esc_html_e( 'pages', 'smash' ); ?></div>
				<?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'items_wrap' => '%3$s', 'depth' => 1, 'walker' => new Smash_Flat_Menu_Walker( '<br>' ) ) ); ?>
			</div>
		<?php endif; ?>
		<?php if ( $smash_social ) : ?>
			<div><div class="mono"><?php esc_html_e( 'suivre', 'smash' ); ?></div>
				<?php
				$smash_links = array();
				foreach ( $smash_social as $smash_name => $smash_url ) {
					$smash_links[] = sprintf( '<a href="%s" rel="noopener" target="_blank">%s</a>', esc_url( $smash_url ), esc_html( $smash_name ) );
				}
				echo implode( ' · ', $smash_links ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé ci-dessus.
				?>
			</div>
		<?php endif; ?>
		<?php
		$smash_nl = (int) ( smash_map( 'gf' )['newsletter'] ?? 0 );
		if ( $smash_nl && function_exists( 'gravity_form' ) && class_exists( 'GFAPI' ) && GFAPI::form_id_exists( $smash_nl ) ) :
			?>
			<div class="foot__nl"><div class="mono"><?php esc_html_e( 'newsletter', 'smash' ); ?></div><?php gravity_form( $smash_nl, false, false, false, null, true ); ?></div>
		<?php endif; ?>
	</div>
	<div class="legal mono">
		<span><?php echo esc_html( smash_option( 'warning' ) ); ?></span>
		<?php if ( has_nav_menu( 'legal' ) ) : ?>
			<span><?php wp_nav_menu( array( 'theme_location' => 'legal', 'container' => false, 'items_wrap' => '%3$s', 'depth' => 1, 'walker' => new Smash_Flat_Menu_Walker( ' · ' ) ) ); ?></span>
		<?php endif; ?>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
