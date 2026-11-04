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
			<span class="lab__ph" aria-hidden="true"><?php echo esc_html( $smash_beer['name'] ); ?></span>
		<?php endif; ?>
	</div>
	<div class="lab__row">
		<?php // phpcs:disable WordPress.Security.EscapeOutput -- $smash_tag vient d'une liste fermée (h2, h3, h4). ?>
		<<?php echo $smash_tag; ?>><?php echo esc_html( $smash_beer['name'] ); ?></<?php echo $smash_tag; ?>>
		<?php // phpcs:enable ?>
		<span class="lab__go blk dark" aria-hidden="true"><?php esc_html_e( 'La fiche', 'smash' ); ?></span>
	</div>
	<div class="mono"><?php echo esc_html( smash_beer_meta( $smash_beer ) ); ?></div>
</a>
