<?php
/**
 * Carte étiquette : penche en 3D au survol (assets/js/core.js, bindTilt).
 *
 * @var array $args { beer: smash_beer(), heading: balise du nom }
 */

defined( 'ABSPATH' ) || exit;

$smash_beer = $args['beer'];
$smash_tag  = in_array( $args['heading'] ?? 'h3', array( 'h2', 'h3', 'h4' ), true ) ? $args['heading'] : 'h3';
?>
<a class="lab" href="<?php echo esc_url( $smash_beer['url'] ); ?>">
	<div class="lab__card">
		<?php echo smash_label_img( $smash_beer, 'smash-label', true ); // Décorative : le nom suit juste en dessous. ?>
		<?php if ( ! $smash_beer['label'] ) : ?>
			<span class="ph mono"><?php esc_html_e( 'étiquette à venir', 'smash' ); ?></span>
		<?php endif; ?>
	</div>
	<div class="lab__row">
		<<?php echo $smash_tag; ?>><?php echo esc_html( $smash_beer['name'] ); ?></<?php echo $smash_tag; ?>>
		<span class="lab__go" aria-hidden="true"><?php esc_html_e( 'la fiche', 'smash' ); ?> <span class="drop"></span></span>
	</div>
	<div class="mono"><?php echo esc_html( smash_beer_meta( $smash_beer ) ); ?></div>
</a>
