<?php
/**
 * Styles, scripts et polices.
 *
 * Les bibliothèques d'animation (GSAP, ScrollTrigger, SplitText, Lenis) sont hébergées
 * dans le thème. Chaque template ajoute son script de page via smash_page_script().
 */

defined( 'ABSPATH' ) || exit;

/**
 * Version d'un fichier du thème = empreinte de son contenu : elle change dès que le fichier change,
 * donc les navigateurs (et les caches) rechargent la nouvelle version au lieu de garder l'ancienne.
 */
function smash_asset_ver( $rel ) {
	$path = SMASH_DIR . $rel;
	return is_file( $path ) ? substr( md5_file( $path ), 0, 8 ) : SMASH_VERSION;
}

add_action( 'wp_enqueue_scripts', function () {
	$v = SMASH_VERSION; // bibliothèques : elles ne changent pas

	wp_enqueue_style( 'smash', SMASH_URI . '/assets/css/smash.css', array(), smash_asset_ver( '/assets/css/smash.css' ) );

	$vendor = array(
		'gsap'               => array( 'gsap.min.js', array() ),
		'gsap-scrolltrigger' => array( 'ScrollTrigger.min.js', array( 'gsap' ) ),
		'gsap-splittext'     => array( 'SplitText.min.js', array( 'gsap' ) ),
		'lenis'              => array( 'lenis.min.js', array() ),
	);
	foreach ( $vendor as $handle => list( $file, $deps ) ) {
		wp_register_script( $handle, SMASH_URI . '/assets/js/vendor/' . $file, $deps, $v, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

	wp_enqueue_script( 'smash-core', SMASH_URI . '/assets/js/core.js', array( 'gsap', 'gsap-scrolltrigger', 'gsap-splittext', 'lenis' ), smash_asset_ver( '/assets/js/core.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
} );

/**
 * Ajoute le script propre à une page (assets/js/pages/{name}.js), après le cœur.
 */
function smash_page_script( $name ) {
	add_action( 'wp_enqueue_scripts', function () use ( $name ) {
		$file = '/assets/js/pages/' . $name . '.js';
		if ( file_exists( SMASH_DIR . $file ) ) {
			wp_enqueue_script( 'smash-page-' . $name, SMASH_URI . $file, array( 'smash-core' ), smash_asset_ver( $file ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
	}, 20 );
}

// Préchargement des polices utilisées au-dessus de la ligne de flottaison.
add_action( 'wp_head', function () {
	foreach ( array( 'switzer-latin-800-normal', 'switzer-latin-400-normal', 'jetbrains-mono-latin-400-normal' ) as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( SMASH_URI . '/assets/fonts/' . $font . '.woff2' )
		);
	}
}, 1 );

/**
 * Styles de l'éditeur de blocs : inutiles ici (thème classique, le seul bloc utilisé, « Détails »
 * de la FAQ, est stylé par smash.css). Environ 100 Ko de moins sur chaque page.
 * Si des articles utilisent un jour des blocs mis en page (colonnes, galeries…), retirer cette fonction.
 */
add_action( 'wp_enqueue_scripts', function () {
	foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'global-styles' ) as $handle ) {
		wp_dequeue_style( $handle );
	}
}, 100 );
