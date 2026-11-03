<?php
/**
 * Formulaire de recherche.
 */

defined( 'ABSPATH' ) || exit;

$smash_sid = 's-' . wp_unique_id();
?>
<form role="search" method="get" class="sform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $smash_sid ); ?>"><?php esc_html_e( 'Rechercher', 'smash' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $smash_sid ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Une bière, un article…', 'smash' ); ?>">
	<button type="submit" class="blk dark"><?php esc_html_e( 'Chercher', 'smash' ); ?></button>
</form>
