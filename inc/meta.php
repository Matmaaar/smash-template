<?php
/**
 * Icône du site et aperçu des liens partagés (WhatsApp, Messenger, réseaux sociaux…).
 *
 * - Icône : le S du thème, sauf si une « Icône du site » est réglée dans Réglages > Général.
 * - Aperçu (Open Graph) : seulement si Rank Math est absent. Sur le vrai site, Rank Math s'en charge.
 *   Image : étiquette de la bière, image d'en-tête de la page, image de fond de l'accueil,
 *   sinon l'« Image de partage » de la page d'options « Infos smash ».
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', function () {
	if ( ! has_site_icon() ) {
		$img = SMASH_URI . '/assets/img/';
		printf( '<link rel="icon" href="%s" sizes="48x48">' . "\n", esc_url( $img . 'favicon-48.png' ) );
		printf( '<link rel="icon" href="%s" sizes="512x512">' . "\n", esc_url( $img . 'favicon-512.png' ) );
		printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( $img . 'apple-touch-icon.png' ) );
	}

	if ( class_exists( 'RankMath' ) ) {
		return;
	}

	$id    = get_queried_object_id();
	$title = wp_get_document_title();
	$desc  = '';
	$image = 0;

	if ( is_singular( 'bieres' ) ) {
		$beer  = smash_beer();
		$desc  = $beer['intro'] ?: wp_strip_all_tags( (string) $beer['desc'] );
		$image = (int) $beer['label'];
	} elseif ( is_front_page() ) {
		$desc  = smash_front( 'hero_text' );
		$image = (int) smash_front( 'hero_image' );
	} elseif ( is_singular( 'post' ) ) {
		$desc  = get_the_excerpt( $id );
		$beers = smash_post_beers( $id );
		$image = has_post_thumbnail( $id ) ? get_post_thumbnail_id( $id ) : ( $beers ? (int) $beers[0]['label'] : 0 );
	} elseif ( $id ) {
		$desc  = smash_page_field( $id, 'intro_text' ) ?: smash_page_field( $id, 'intro_sub' );
		$image = (int) smash_page_field( $id, 'intro_image' );
	}
	$desc  = wp_trim_words( $desc ?: get_bloginfo( 'description' ), 30 );
	$image = $image ?: (int) smash_option( 'share_image' );
	$src   = $image ? wp_get_attachment_image_src( $image, 'large' ) : false;
	// Une image de partage dédiée (format 1200 × 630) passe avant les autres sur l'accueil.
	if ( is_front_page() && smash_option( 'share_image' ) ) {
		$src = wp_get_attachment_image_src( (int) smash_option( 'share_image' ), 'full' );
	}

	$tags = array(
		'og:type'        => is_singular( 'post' ) ? 'article' : 'website',
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:locale'      => str_replace( '-', '_', get_bloginfo( 'language' ) ),
		'og:title'       => $title,
		'og:description' => $desc,
		'og:url'         => is_front_page() ? home_url( '/' ) : ( $id ? get_permalink( $id ) : '' ),
	);
	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	foreach ( array_filter( $tags ) as $property => $content ) {
		printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $property ), esc_attr( $content ) );
	}
	if ( $src ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $src[0] ) );
		printf( '<meta property="og:image:width" content="%d"><meta property="og:image:height" content="%d">' . "\n", (int) $src[1], (int) $src[2] );
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}, 5 );
