<?php
/**
 * En-tête. Version de base : le méga-menu « nos bières » arrive à l'étape 2.
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#contenu"><?php esc_html_e( 'Aller au contenu', 'smash' ); ?></a>

<header class="top" id="top">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="top__logo">
		<img src="<?php echo esc_url( SMASH_URI . '/assets/img/s-white.png' ); ?>" alt="" width="28" height="28"><?php bloginfo( 'name' ); ?>
	</a>
	<?php
	wp_nav_menu( array(
		'theme_location'  => 'primary',
		'container'       => 'nav',
		'container_id'    => 'nav',
		'container_aria_label' => __( 'Menu principal', 'smash' ),
		'items_wrap'      => '%3$s',
		'depth'           => 1,
		'fallback_cb'     => false,
		'walker'          => new Smash_Flat_Menu_Walker(),
	) );
	?>
</header>
<div class="pipe" aria-hidden="true"><span class="drop" id="pipedrop"></span></div>
