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
		printf( '<div class="kick mono"><span class="drop"></span>%s</div>', esc_html( $text ) );
	}
}
