<?php
/**
 * Menus : éléments de premier niveau, page courante, méga-menu « nos bières », bouton CTA.
 *
 * Dans l'admin (Apparence > Menus, « Classes CSS » activées dans les options de l'écran) :
 * - classe `cta`  : l'élément devient le bouton blanc à droite du header (« devis tireuse ») ;
 * - classe `mega` : l'élément ouvre le méga-menu des bières (automatique pour la page `nos-bieres`).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Éléments de premier niveau d'un emplacement de menu.
 *
 * @return array<int, array{title:string, url:string, target:string, current:bool, cta:bool, mega:bool}>
 */
function smash_menu_items( $location ) {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return array();
	}
	$items = wp_get_nav_menu_items( $locations[ $location ] );
	if ( ! $items ) {
		return array();
	}

	$catalog   = get_page_by_path( 'nos-bieres' );
	$queried   = get_queried_object_id();
	$in_beers  = is_singular( 'bieres' ) || is_tax( 'type-de-bieres' );
	$in_blog   = is_singular( 'post' ) || is_category() || is_tag() || is_home();
	$blog_page = (int) get_option( 'page_for_posts' );

	$out = array();
	foreach ( $items as $item ) {
		if ( (int) $item->menu_item_parent ) {
			continue;
		}
		$classes   = array_filter( (array) $item->classes );
		$object_id = (int) $item->object_id;
		$is_mega   = in_array( 'mega', $classes, true ) || ( $catalog && 'page' === $item->object && $object_id === $catalog->ID );
		$current   = ( $queried && $object_id === $queried && 'custom' !== $item->type )
			|| ( $is_mega && $in_beers )
			|| ( $in_blog && $blog_page && $object_id === $blog_page );

		$out[] = array(
			'title'   => $item->title,
			'url'     => $item->url,
			'target'  => $item->target,
			'current' => $current,
			'cta'     => in_array( 'cta', $classes, true ),
			'mega'    => $is_mega,
		);
	}
	return $out;
}

/**
 * Lien de menu simple.
 */
function smash_menu_link( $item, $extra_class = '', $inner = '' ) {
	$class = trim( $extra_class . ( $item['current'] ? ' on' : '' ) );
	printf(
		'<a href="%s"%s%s%s>%s%s</a>',
		esc_url( $item['url'] ),
		$class ? ' class="' . esc_attr( $class ) . '"' : '',
		$item['current'] ? ' aria-current="page"' : '',
		$item['target'] ? ' target="' . esc_attr( $item['target'] ) . '" rel="noopener"' : '',
		esc_html( $item['title'] ),
		$inner // phpcs:ignore WordPress.Security.EscapeOutput -- markup fixe fourni par le thème.
	);
}

/**
 * Méga-menu « nos bières » : les gammes à gauche, les étiquettes des permanentes à droite.
 */
function smash_mega_menu( $item ) {
	$catalog = untrailingslashit( $item['url'] );
	$perm    = smash_beers( 'perm' );
	$ranges  = array(
		'permanentes' => array( __( 'permanentes', 'smash' ), (string) count( $perm ) ),
		'ephemeres'   => array( __( 'éphémères', 'smash' ), __( 'en ce moment', 'smash' ) ),
		'anciennes'   => array( __( 'anciennes éphémères', 'smash' ), __( 'archives', 'smash' ) ),
	);
	?>
	<div class="dd" id="dd-b">
		<?php smash_menu_link( $item, '', ' <svg viewBox="0 0 10 10" aria-hidden="true"><path d="M1 3l4 4 4-4" fill="none" stroke="currentColor" stroke-width="2"/></svg>' ); ?>
		<div class="mega">
			<div class="mega__links">
				<span class="mono"><?php esc_html_e( 'la gamme', 'smash' ); ?></span>
				<?php foreach ( $ranges as $anchor => list( $label, $note ) ) : ?>
					<a href="<?php echo esc_url( $catalog . '/#' . $anchor ); ?>"><?php echo esc_html( $label ); ?> <small><?php echo esc_html( $note ); ?></small></a>
				<?php endforeach; ?>
			</div>
			<div class="mega__labs" id="mm-labs">
				<?php foreach ( $perm as $beer ) : ?>
					<a href="<?php echo esc_url( $beer['url'] ); ?>">
						<i><?php echo $beer['label'] ? wp_get_attachment_image( $beer['label'], 'smash-label-sm', false, array( 'alt' => '' ) ) : ''; ?></i>
						<span><b><?php echo esc_html( $beer['name'] ); ?></b><small><?php echo esc_html( $beer['style'] ); ?></small></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
}
