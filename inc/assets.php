<?php
/**
 * Styles, scripts et polices.
 *
 * Les bibliothèques d'animation (GSAP, ScrollTrigger, SplitText, Lenis) sont hébergées
 * dans le thème. Chaque template ajoute son script de page via smash_page_script().
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	$v = SMASH_VERSION;

	wp_enqueue_style( 'smash', SMASH_URI . '/assets/css/smash.css', array(), $v );

	$vendor = array(
		'gsap'               => array( 'gsap.min.js', array() ),
		'gsap-scrolltrigger' => array( 'ScrollTrigger.min.js', array( 'gsap' ) ),
		'gsap-splittext'     => array( 'SplitText.min.js', array( 'gsap' ) ),
		'lenis'              => array( 'lenis.min.js', array() ),
	);
	foreach ( $vendor as $handle => list( $file, $deps ) ) {
		wp_register_script( $handle, SMASH_URI . '/assets/js/vendor/' . $file, $deps, $v, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

	wp_enqueue_script( 'smash-core', SMASH_URI . '/assets/js/core.js', array( 'gsap', 'gsap-scrolltrigger', 'gsap-splittext', 'lenis' ), $v, array( 'in_footer' => true, 'strategy' => 'defer' ) );
} );

/**
 * Ajoute le script propre à une page (assets/js/pages/{name}.js), après le cœur.
 */
function smash_page_script( $name ) {
	add_action( 'wp_enqueue_scripts', function () use ( $name ) {
		$file = '/assets/js/pages/' . $name . '.js';
		if ( file_exists( SMASH_DIR . $file ) ) {
			wp_enqueue_script( 'smash-page-' . $name, SMASH_URI . $file, array( 'smash-core' ), SMASH_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
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
