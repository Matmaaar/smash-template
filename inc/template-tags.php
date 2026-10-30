<?php
/**
 * Petits composants partagés par les templates.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu « à plat » : des liens <a> simples, comme dans la maquette (pas de <ul>).
 */
class Smash_Flat_Menu_Walker extends Walker_Nav_Menu {
	private $sep;
	private $count = 0;

	public function __construct( $sep = '' ) {
		$this->sep = $sep;
	}

	public function start_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {}

	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item    = $data_object;
		$current = in_array( 'current-menu-item', (array) $item->classes, true ) || in_array( 'current-menu-ancestor', (array) $item->classes, true );
		$output .= ( $this->count++ ? $this->sep : '' ) . sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $item->url ),
			$current ? ' class="on" aria-current="page"' : '',
			$item->target ? ' target="' . esc_attr( $item->target ) . '" rel="noopener"' : '',
			esc_html( $item->title )
		);
	}
}

/**
 * Le petit sur-titre en mono avec la goutte.
 */
function smash_kick( $text ) {
	if ( '' !== (string) $text ) {
		printf( '<div class="kick mono"><span class="mark" aria-hidden="true"></span>%s</div>', esc_html( $text ) );
	}
}

/**
 * Texte sur plusieurs lignes (titres de la maquette) : échappé, retours à la ligne => <br>.
 */
function smash_multiline( $text ) {
	return nl2br( esc_html( $text ), false );
}

/**
 * Bouton noir ou blanc de la maquette, avec la goutte.
 */
function smash_btn( $label, $url, $class = '' ) {
	if ( '' === (string) $label || '' === (string) $url ) {
		return;
	}
	printf(
		'<a href="%s" class="%s">%s <span class="arw" aria-hidden="true"></span></a>',
		esc_url( $url ),
		esc_attr( trim( 'blk ' . $class ) ),
		esc_html( $label )
	);
}

/**
 * Style et degré d'une bière : « NEIPA · 6 % ».
 */
function smash_beer_meta( $beer ) {
	$abv = '' !== $beer['abv'] ? $beer['abv'] . ' %' : '';
	return implode( ' · ', array_filter( array( $beer['style'], $abv ) ) );
}

/**
 * Image d'étiquette (2:1). Texte alternatif : « Étiquette de la bière X », sauf si décorative.
 */
function smash_label_img( $beer, $size = 'smash-label', $decorative = false, $attr = array() ) {
	if ( ! $beer['label'] ) {
		return '';
	}
	$alt = $decorative ? '' : sprintf( __( 'Étiquette de la bière %s', 'smash' ), $beer['name'] );
	return wp_get_attachment_image( $beer['label'], $size, false, array_merge( array( 'alt' => $alt, 'loading' => 'lazy' ), $attr ) );
}

/**
 * Carte étiquette cliquable (template-parts/label-card.php).
 */
function smash_label_card( $beer, $heading = 'h3' ) {
	get_template_part( 'template-parts/label-card', null, array( 'beer' => $beer, 'heading' => $heading ) );
}
