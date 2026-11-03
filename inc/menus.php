<?php
/**
 * Menus : éléments de premier niveau, page courante, méga-menu « nos bières », bouton CTA.
 *
 * Dans l'admin (Apparence > Menus, « Classes CSS » activées dans les options de l'écran) :
 * - classe `cta`  : l'élément devient le bouton blanc à droite du header (« devis tireuse ») ;
 * - classe `mega` : l'élément ouvre le méga-menu des bières (automatique pour la page `nos-bieres`).
 *
 * Les autres liens ouvrent un aperçu de leur page, selon le slug de la page visée :
 * `la-brasserie`, page des articles (blog), `contact`, `telechargements`.
 * Classe `no-apercu` sur un élément : pas d'aperçu.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Éléments de premier niveau d'un emplacement de menu.
 *
 * @return array<int, array{title:string, url:string, target:string, current:bool, cta:bool, mega:bool, panel:string, page:int}>
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

		$panel = '';
		if ( $is_mega ) {
			$panel = 'beers';
		} elseif ( 'page' === $item->object && ! in_array( 'no-apercu', $classes, true ) ) {
			$slug  = get_post_field( 'post_name', $object_id );
			$panel = $object_id === $blog_page ? 'blog' : ( array( 'la-brasserie' => 'brew', 'contact' => 'contact', 'telechargements' => 'downloads' )[ $slug ] ?? '' );
		}

		$out[] = array(
			'title'   => $item->title,
			'url'     => $item->url,
			'target'  => $item->target,
			'current' => $current,
			'cta'     => in_array( 'cta', $classes, true ),
			'mega'    => $is_mega,
			'panel'   => in_array( 'cta', $classes, true ) ? '' : $panel,
			'page'    => $object_id,
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
 * Lien de la nav avec son panneau d'aperçu (méga-menu). Sans contenu à montrer : lien simple.
 */
function smash_nav_item( $item ) {
	$fn = array(
		'beers'     => 'smash_panel_beers',
		'brew'      => 'smash_panel_brew',
		'blog'      => 'smash_panel_blog',
		'contact'   => 'smash_panel_contact',
		'downloads' => 'smash_panel_downloads',
	)[ $item['panel'] ] ?? '';
	$html = '';
	if ( $fn ) {
		ob_start();
		$fn( $item );
		$html = trim( ob_get_clean() );
	}
	if ( '' === $html ) {
		smash_menu_link( $item );
		return;
	}
	?>
	<div class="dd" data-panel="<?php echo esc_attr( $item['panel'] ); ?>">
		<?php smash_menu_link( $item, '', ' <svg viewBox="0 0 10 10" aria-hidden="true"><path d="M1 3l4 4 4-4" fill="none" stroke="currentColor" stroke-width="2"/></svg>' ); ?>
		<div class="mega mega--<?php echo esc_attr( $item['panel'] ); ?>">
			<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans les fonctions smash_panel_*. ?>
		</div>
	</div>
	<?php
}

/**
 * Colonne de gauche commune aux aperçus : sur-titre, texte, liens.
 *
 * @param array<int, array{0:string,1:string}> $links libellé => url.
 */
function smash_panel_intro( $kick, $text, $links ) {
	?>
	<div class="mega__links">
		<span class="mono"><?php echo esc_html( $kick ); ?></span>
		<?php if ( $text ) : ?>
			<p class="mega__text"><?php echo esc_html( wp_trim_words( $text, 26 ) ); ?></p>
		<?php endif; ?>
		<?php foreach ( $links as list( $label, $url ) ) : ?>
			<?php if ( $url ) : ?>
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Méga-menu « nos bières » : les gammes à gauche, les étiquettes des permanentes à droite.
 */
function smash_panel_beers( $item ) {
	$catalog = untrailingslashit( $item['url'] );
	$perm    = smash_beers( 'perm' );
	$ranges  = array(
		'permanentes' => array( __( 'Permanentes', 'smash' ), (string) count( $perm ) ),
		'ephemeres'   => array( __( 'Éphémères', 'smash' ), __( 'En ce moment', 'smash' ) ),
		'anciennes'   => array( __( 'Anciennes éphémères', 'smash' ), __( 'Archives', 'smash' ) ),
	);
	?>
	<div class="mega__links">
		<span class="mono"><?php esc_html_e( 'La gamme', 'smash' ); ?></span>
		<?php foreach ( $ranges as $anchor => list( $label, $note ) ) : ?>
			<a href="<?php echo esc_url( $catalog . '/#' . $anchor ); ?>"><?php echo esc_html( $label ); ?> <small><?php echo esc_html( $note ); ?></small></a>
		<?php endforeach; ?>
	</div>
	<div class="mega__labs mega__in">
		<?php foreach ( $perm as $beer ) : ?>
			<a href="<?php echo esc_url( $beer['url'] ); ?>">
				<i><?php echo $beer['label'] ? wp_get_attachment_image( $beer['label'], 'smash-label-sm', false, array( 'alt' => '', 'loading' => 'lazy' ) ) : ''; ?></i>
				<span><b><?php echo esc_html( $beer['name'] ); ?></b><small><?php echo esc_html( $beer['style'] ); ?></small></span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Aperçu « la brasserie » : photo, 3 chiffres, lien vers l'équipe (champs de la page).
 */
function smash_panel_brew( $item ) {
	$id    = $item['page'];
	$photo = (int) smash_page_field( $id, 'brew_photo' );
	$stats = array();
	for ( $i = 1; $i <= 3; $i++ ) {
		if ( '' !== smash_page_field( $id, "stat_{$i}_label" ) ) {
			$stats[] = array( smash_page_field( $id, "stat_{$i}_value" ), smash_page_field( $id, "stat_{$i}_label" ) );
		}
	}
	if ( ! $photo && ! $stats ) {
		return;
	}
	smash_panel_intro(
		$item['title'],
		smash_page_field( $id, 'brew_text' ) ?: smash_page_field( $id, 'intro_text' ),
		array(
			array( __( 'Découvrir la brasserie', 'smash' ), $item['url'] ),
			array( __( 'Rencontrer l\'équipe', 'smash' ), untrailingslashit( $item['url'] ) . '/#la-team' ),
		)
	);
	?>
	<div class="mega__brew mega__in">
		<?php if ( $photo ) : ?>
			<div class="mega__photo"><?php echo wp_get_attachment_image( $photo, 'medium_large', false, array( 'alt' => '', 'loading' => 'lazy' ) ); ?></div>
		<?php endif; ?>
		<?php if ( $stats ) : ?>
			<ul class="mega__stats">
				<?php foreach ( $stats as list( $n, $l ) ) : ?>
					<li><b><?php echo esc_html( $n ); ?></b><span class="mono"><?php echo esc_html( $l ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Aperçu « le blog » : les 3 derniers articles et les étiquettes des bières citées.
 */
function smash_panel_blog( $item ) {
	$posts = get_posts( array( 'numberposts' => 3, 'suppress_filters' => false ) );
	if ( ! $posts ) {
		return;
	}
	smash_panel_intro( $item['title'], smash_page_field( $item['page'], 'intro_text' ), array( array( __( 'Tous les articles', 'smash' ), $item['url'] ) ) );
	?>
	<div class="mega__posts mega__in">
		<?php foreach ( $posts as $post ) : ?>
			<a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
				<span class="mega__mini" aria-hidden="true">
					<?php foreach ( array_slice( array_filter( smash_post_beers( $post ), fn( $b ) => $b['label'] ), 0, 3 ) as $beer ) : ?>
						<i><?php echo smash_label_img( $beer, 'smash-label-sm', true ); ?></i>
					<?php endforeach; ?>
				</span>
				<span class="mono"><?php echo esc_html( get_the_date( '', $post ) ); ?></span>
				<b><?php echo esc_html( get_the_title( $post ) ); ?></b>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Aperçu « contact » : adresse, horaires, téléphone et email cliquables (« Infos smash »).
 */
function smash_panel_contact( $item ) {
	$address = smash_option( 'address' );
	$hours   = smash_option( 'hours' );
	$email   = smash_option( 'email' );
	$phone   = smash_option( 'phone' );
	if ( ! $address && ! $hours && ! $email && ! $phone ) {
		return;
	}
	smash_panel_intro(
		$item['title'],
		smash_page_field( $item['page'], 'intro_text' ),
		array(
			array( __( 'Nous écrire', 'smash' ), $item['url'] ),
			array( __( 'Itinéraire', 'smash' ), smash_option( 'map_url' ) ),
		)
	);
	$br = array( 'br' => array() );
	?>
	<div class="mega__contact mega__in">
		<?php if ( $address ) : ?>
			<div><span class="mono"><?php esc_html_e( 'Adresse', 'smash' ); ?></span><p><?php echo wp_kses( $address, $br ); ?></p></div>
		<?php endif; ?>
		<?php if ( $hours ) : ?>
			<div><span class="mono"><?php esc_html_e( 'Horaires', 'smash' ); ?></span><p><?php echo wp_kses( $hours, $br ); ?></p></div>
		<?php endif; ?>
		<?php if ( $email || $phone ) : ?>
			<div>
				<span class="mono"><?php esc_html_e( 'Nous joindre', 'smash' ); ?></span>
				<?php if ( $phone ) : ?>
					<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^\d+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
				<?php endif; ?>
				<?php if ( $email ) : ?>
					<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Aperçu « téléchargements » : les fichiers, téléchargeables directement.
 */
function smash_panel_downloads( $item ) {
	$files = array_filter(
		array_map( 'smash_download', get_posts( array( 'post_type' => 'telechargement', 'numberposts' => 6, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) ),
		fn( $f ) => $f && $f['url']
	);
	if ( ! $files ) {
		return;
	}
	smash_panel_intro( $item['title'], smash_page_field( $item['page'], 'intro_text' ), array( array( __( 'Tous les fichiers', 'smash' ), $item['url'] ) ) );
	?>
	<ul class="mega__files mega__in">
		<?php foreach ( $files as $file ) : ?>
			<li><a href="<?php echo esc_url( $file['url'] ); ?>" download><b><?php echo esc_html( $file['title'] ); ?></b><span class="mono"><?php echo esc_html( $file['ext'] ); ?></span></a></li>
		<?php endforeach; ?>
	</ul>
	<?php
}
