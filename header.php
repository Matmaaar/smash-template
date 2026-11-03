<?php
/**
 * En-tête : logo, menu principal (avec méga-menu « nos bières ») et bouton CTA.
 */

defined( 'ABSPATH' ) || exit;

$smash_items = smash_menu_items( 'primary' );
$smash_cta   = array_values( wp_list_filter( $smash_items, array( 'cta' => true ) ) );
?><!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>document.documentElement.className=document.documentElement.className.replace('no-js','js')+(/(^|;\s*)smash_age=1/.test(document.cookie)?' age-ok':'');</script>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#contenu"><?php esc_html_e( 'Aller au contenu', 'smash' ); ?></a>

<?php // Logo hors du header : il flotte seul, sur sa propre pilule. ?>
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="top__logo" id="logo">
	<img src="<?php echo esc_url( SMASH_URI . '/assets/img/s-white.png' ); ?>" alt="" width="24" height="24"><?php bloginfo( 'name' ); ?>
</a>
<header class="top" id="top">
	<?php if ( $smash_items ) : ?>
		<nav id="nav" aria-label="<?php esc_attr_e( 'Menu principal', 'smash' ); ?>">
			<?php
			foreach ( $smash_items as $smash_item ) {
				if ( $smash_item['cta'] ) {
					continue;
				}
				smash_nav_item( $smash_item );
			}
			?>
		</nav>
	<?php endif; ?>
	<div class="top__end">
		<?php if ( $smash_cta ) : ?>
			<?php smash_menu_link( $smash_cta[0], 'top__cta' ); ?>
		<?php endif; ?>
		<?php if ( $smash_items ) : ?>
			<button type="button" class="top__menu" id="menu-btn" aria-expanded="false" aria-controls="mnav" data-open="<?php esc_attr_e( 'Menu', 'smash' ); ?>" data-close="<?php esc_attr_e( 'Fermer', 'smash' ); ?>"><?php esc_html_e( 'Menu', 'smash' ); ?></button>
		<?php endif; ?>
	</div>
</header>
<?php if ( $smash_items ) : ?>
	<?php // Menu plein écran (mobile) : mêmes liens que la nav, en très gros. ?>
	<div class="mnav" id="mnav" hidden>
		<nav aria-label="<?php esc_attr_e( 'Menu principal', 'smash' ); ?>">
			<?php
			foreach ( $smash_items as $smash_item ) {
				if ( ! $smash_item['cta'] ) {
					smash_menu_link( $smash_item, 'mnav__link' );
				}
			}
			if ( $smash_cta ) {
				smash_menu_link( $smash_cta[0], 'blk mnav__cta' );
			}
			?>
		</nav>
	</div>
<?php endif; ?>
<?php if ( $smash_cta && ! $smash_cta[0]['current'] ) : ?>
	<?php // Bouton flottant : visible quand la nav est cachée (on descend), masqué quand elle revient (core.js). ?>
	<a href="<?php echo esc_url( $smash_cta[0]['url'] ); ?>" class="fab-cta" id="fab-cta" tabindex="-1" aria-hidden="true"><?php echo esc_html( $smash_cta[0]['title'] ); ?></a>
<?php endif; ?>
