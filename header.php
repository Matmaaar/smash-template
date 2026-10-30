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

<header class="top" id="top">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="top__logo">
		<img src="<?php echo esc_url( SMASH_URI . '/assets/img/s-white.png' ); ?>" alt="" width="30" height="30"><?php bloginfo( 'name' ); ?>
	</a>
	<?php if ( $smash_items ) : ?>
		<nav id="nav" aria-label="<?php esc_attr_e( 'Menu principal', 'smash' ); ?>">
			<?php
			foreach ( $smash_items as $smash_item ) {
				if ( $smash_item['cta'] ) {
					continue;
				}
				if ( $smash_item['mega'] ) {
					smash_mega_menu( $smash_item );
				} else {
					smash_menu_link( $smash_item );
				}
			}
			?>
		</nav>
	<?php endif; ?>
	<?php if ( $smash_cta ) : ?>
		<?php smash_menu_link( $smash_cta[0], 'top__cta', ' <span class="arw" aria-hidden="true"></span>' ); ?>
	<?php endif; ?>
</header>
<div class="pipe" aria-hidden="true"><span class="S" id="pipedrop"><i></i><i></i><i></i></span></div>
