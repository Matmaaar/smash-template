<?php
/**
 * Catalogue des bières : onglets « toutes » / permanentes / éphémères / anciennes.
 * « Toutes » affiche les trois listes à la suite, chacune avec son titre (#toutes).
 *
 * Les trois listes sont rendues ici (SEO, fonctionne sans JS) ; assets/js/pages/bieres.js
 * transforme le tout en onglets. Les ancres #permanentes, #ephemeres, #anciennes ouvrent l'onglet.
 *
 * @var array $args { selected: 'all' | 'perm' | 'eph' | 'old' }
 */

defined( 'ABSPATH' ) || exit;

$smash_selected = $args['selected'] ?? 'perm';
$smash_lists    = array(
	'perm' => smash_beers( 'perm' ),
	'eph'  => smash_beers( 'eph' ),
	'old'  => smash_beers( 'old' ),
);
$smash_labels   = array(
	'perm' => __( 'Permanentes', 'smash' ),
	'eph'  => __( 'Éphémères en ce moment', 'smash' ),
	'old'  => __( 'Anciennes éphémères', 'smash' ),
);
$smash_anchors  = smash_range_anchors();
$smash_start    = 'all' === $smash_selected ? 'toutes' : $smash_anchors[ $smash_selected ];
$smash_first    = 6;  // anciennes visibles au départ ; « voir plus » en ajoute 12 à chaque clic.
?>
<section class="sec" style="padding-top:2vh" data-catalog data-selected="<?php echo esc_attr( $smash_start ); ?>">
	<div class="view-toggle" role="group" aria-label="<?php esc_attr_e( 'Affichage', 'smash' ); ?>" hidden>
		<button type="button" data-cols="2" aria-pressed="true" aria-label="<?php esc_attr_e( 'Deux colonnes', 'smash' ); ?>"><svg viewBox="0 0 16 16" aria-hidden="true"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg></button>
		<button type="button" data-cols="1" aria-pressed="false" aria-label="<?php esc_attr_e( 'Une colonne', 'smash' ); ?>"><svg viewBox="0 0 16 16" aria-hidden="true"><rect x="1" y="1" width="14" height="6" rx="1"/><rect x="1" y="9" width="14" height="6" rx="1"/></svg></button>
	</div>
	<div class="tabs" role="tablist" aria-label="<?php esc_attr_e( 'Gammes', 'smash' ); ?>" hidden>
		<button type="button" class="tab" role="tab" id="tab-toutes" aria-controls="<?php echo esc_attr( implode( ' ', $smash_anchors ) ); ?>"
			aria-selected="<?php echo 'all' === $smash_selected ? 'true' : 'false'; ?>" tabindex="<?php echo 'all' === $smash_selected ? '0' : '-1'; ?>">
			<?php esc_html_e( 'Tout', 'smash' ); ?><sup class="tab__n"><?php echo (int) array_sum( array_map( 'count', $smash_lists ) ); ?></sup>
		</button>
		<?php foreach ( smash_range_anchors() as $smash_range => $smash_anchor ) : ?>
			<button type="button" class="tab" role="tab" id="tab-<?php echo esc_attr( $smash_anchor ); ?>" aria-controls="<?php echo esc_attr( $smash_anchor ); ?>"
				aria-selected="<?php echo $smash_range === $smash_selected ? 'true' : 'false'; ?>" tabindex="<?php echo $smash_range === $smash_selected ? '0' : '-1'; ?>" data-anchor="<?php echo esc_attr( $smash_anchor ); ?>">
				<?php echo esc_html( $smash_labels[ $smash_range ] ); ?><sup class="tab__n"><?php echo (int) count( $smash_lists[ $smash_range ] ); ?></sup>
			</button>
		<?php endforeach; ?>
	</div>

	<?php foreach ( smash_range_anchors() as $smash_range => $smash_anchor ) : ?>
		<div class="tabpanel" role="tabpanel" id="<?php echo esc_attr( $smash_anchor ); ?>" aria-labelledby="tab-<?php echo esc_attr( $smash_anchor ); ?>" tabindex="0">
			<h2 class="cat-title"><?php echo esc_html( $smash_labels[ $smash_range ] ); ?></h2>
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
					<div class="more-btn"><button type="button" class="blk dark" data-more><?php esc_html_e( 'Voir plus', 'smash' ); ?></button></div>
				<?php endif; ?>
			<?php else : ?>
				<p><?php esc_html_e( 'Aucune bière dans cette gamme pour le moment.', 'smash' ); ?></p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</section>
