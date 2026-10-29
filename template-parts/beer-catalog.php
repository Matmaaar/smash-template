<?php
/**
 * Catalogue des bières : onglets permanentes / éphémères / anciennes.
 *
 * Les trois listes sont rendues ici (SEO, fonctionne sans JS) ; assets/js/pages/bieres.js
 * transforme le tout en onglets. Les ancres #permanentes, #ephemeres, #anciennes ouvrent l'onglet.
 *
 * @var array $args { selected: 'perm' | 'eph' | 'old' }
 */

defined( 'ABSPATH' ) || exit;

$smash_selected = $args['selected'] ?? 'perm';
$smash_lists    = array(
	'perm' => smash_beers( 'perm' ),
	'eph'  => smash_beers( 'eph' ),
	'old'  => smash_beers( 'old' ),
);
$smash_labels   = array(
	/* translators: %d : nombre de bières permanentes */
	'perm' => sprintf( __( 'permanentes · %d', 'smash' ), count( $smash_lists['perm'] ) ),
	'eph'  => __( 'éphémères en ce moment', 'smash' ),
	'old'  => __( 'anciennes éphémères', 'smash' ),
);
$smash_first    = 6;  // anciennes visibles au départ ; « voir plus » en ajoute 12 à chaque clic.
?>
<section class="sec" style="padding-top:2vh" data-catalog data-selected="<?php echo esc_attr( smash_range_anchors()[ $smash_selected ] ); ?>">
	<div class="tabs" role="tablist" aria-label="<?php esc_attr_e( 'Gammes', 'smash' ); ?>" hidden>
		<?php foreach ( smash_range_anchors() as $smash_range => $smash_anchor ) : ?>
			<button type="button" class="tab" role="tab" id="tab-<?php echo esc_attr( $smash_anchor ); ?>" aria-controls="<?php echo esc_attr( $smash_anchor ); ?>"
				aria-selected="<?php echo $smash_range === $smash_selected ? 'true' : 'false'; ?>" tabindex="<?php echo $smash_range === $smash_selected ? '0' : '-1'; ?>">
				<?php echo esc_html( $smash_labels[ $smash_range ] ); ?>
			</button>
		<?php endforeach; ?>
	</div>

	<?php foreach ( smash_range_anchors() as $smash_range => $smash_anchor ) : ?>
		<div class="tabpanel" role="tabpanel" id="<?php echo esc_attr( $smash_anchor ); ?>" aria-labelledby="tab-<?php echo esc_attr( $smash_anchor ); ?>" tabindex="0">
			<h2 class="screen-reader-text"><?php echo esc_html( $smash_labels[ $smash_range ] ); ?></h2>
			<?php if ( $smash_lists[ $smash_range ] ) : ?>
				<div class="labels">
					<?php
					foreach ( $smash_lists[ $smash_range ] as $smash_i => $smash_beer ) {
						$smash_more = 'old' === $smash_range && $smash_i >= $smash_first;
						echo $smash_more ? '<div class="lab-wrap" hidden>' : '<div class="lab-wrap">';
						smash_label_card( $smash_beer );
						echo '</div>';
					}
					?>
				</div>
				<?php if ( 'old' === $smash_range && count( $smash_lists['old'] ) > $smash_first ) : ?>
					<div class="more-btn"><button type="button" class="blk dark" data-more><?php esc_html_e( 'voir plus', 'smash' ); ?> <span class="drop"></span></button></div>
				<?php endif; ?>
			<?php else : ?>
				<p><?php esc_html_e( 'Aucune bière dans cette gamme pour le moment.', 'smash' ); ?></p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</section>
