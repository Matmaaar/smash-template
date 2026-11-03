<?php
/**
 * Fiche bière (/bieres/{slug}/).
 */

defined( 'ABSPATH' ) || exit;

smash_page_script( 'fiche' );
get_header();

$smash_b       = smash_beer();
$smash_catalog = smash_catalog_page();
$smash_kicks   = array(
	'perm' => __( 'Gamme permanente', 'smash' ),
	'eph'  => __( 'Gamme éphémère', 'smash' ),
	'old'  => __( 'Ancienne éphémère', 'smash' ),
);
// Fiche hiérarchisée : 3 infos clés en gros, puis ingrédients (courts) et textes (longs) en grille.
// Seules les infos remplies s'affichent.
$smash_key   = array(
	'abv'     => __( 'Degré', 'smash' ),
	'style'   => __( 'Style', 'smash' ),
	'formats' => __( 'Formats', 'smash' ),
);
$smash_cells = array(
	'malts' => array( __( 'Malts', 'smash' ), 'ingr' ),
	'hops'  => array( __( 'Houblons', 'smash' ), 'ingr' ),
	'yeast' => array( __( 'Levure', 'smash' ), 'ingr' ),
	'other' => array( __( 'Autres', 'smash' ), 'ingr' ),
	'look'  => array( __( 'Aspect', 'smash' ), 'text' ),
	'desc'  => array( __( 'Dégustation', 'smash' ), 'text' ),
	'when'  => array( __( 'À boire', 'smash' ), 'text' ),
	'food'  => array( __( 'Accords', 'smash' ), 'text' ),
);
$smash_location = get_page_by_path( 'location-tireuses-a-bieres' );
$smash_rentable = $smash_location && has_term( '', 'type-de-location', $smash_b['id'] );
?>
<main class="page light" id="contenu">
	<section class="sec fiche-sec">
		<?php if ( $smash_catalog ) : ?>
			<a href="<?php echo esc_url( get_permalink( $smash_catalog ) ); ?>" class="mono fiche__back">← <?php echo esc_html( sprintf( __( 'Toutes %s', 'smash' ), mb_strtolower( get_the_title( $smash_catalog ) ) ) ); ?></a>
		<?php endif; ?>
		<?php
		$smash_cta = '';
		if ( $smash_rentable ) {
			$smash_keg = smash_keg_price( $smash_b['id'] );
			ob_start();
			smash_btn( __( 'En fût pour ta soirée', 'smash' ) . ( null !== $smash_keg ? ' · ' . smash_money( $smash_keg ) : '' ), get_permalink( $smash_location ), 'dark' );
			$smash_cta = ob_get_clean();
		}
		?>
		<div class="fiche" id="fiche">
			<div class="fiche__label">
				<div class="lab" style="cursor:default">
					<div class="lab__card">
						<?php echo smash_label_img( $smash_b, 'smash-label', false, array( 'loading' => false, 'fetchpriority' => 'high', 'sizes' => '(max-width: 900px) 100vw, 40vw' ) ); ?>
						<?php if ( ! $smash_b['label'] ) : ?>
							<span class="lab__ph" aria-hidden="true"><?php echo esc_html( $smash_b['name'] ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<?php if ( $smash_cta ) : ?>
					<div class="fiche__cta fiche__cta--desk"><?php echo $smash_cta; // phpcs:ignore WordPress.Security.EscapeOutput -- échappé dans smash_btn(). ?></div>
				<?php endif; ?>
			</div>
			<div class="fiche__info">
				<?php smash_kick( $smash_kicks[ $smash_b['range'] ] ?? '' ); ?>
				<h1 class="big big--sm"><?php echo esc_html( $smash_b['name'] ); ?></h1>
				<?php if ( $smash_b['intro'] ) : ?>
					<p class="intro"><?php echo esc_html( $smash_b['intro'] ); ?></p>
				<?php endif; ?>
				<?php if ( $smash_cta ) : ?>
					<div class="fiche__cta fiche__cta--mob"><?php echo $smash_cta; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<?php endif; ?>

				<dl class="fiche__key">
					<?php foreach ( $smash_key as $smash_k => $smash_label ) : ?>
						<?php if ( '' === $smash_b[ $smash_k ] ) { continue; } ?>
						<div class="is-<?php echo esc_attr( $smash_k ); ?>"><dt class="mono"><?php echo esc_html( $smash_label ); ?></dt><dd><?php echo esc_html( $smash_b[ $smash_k ] . ( 'abv' === $smash_k ? ' %' : '' ) ); ?></dd></div>
					<?php endforeach; ?>
				</dl>

				<dl class="fiche__grid">
					<?php foreach ( $smash_cells as $smash_k => list( $smash_label, $smash_kind ) ) : ?>
						<?php if ( '' === $smash_b[ $smash_k ] ) { continue; } ?>
						<div class="is-<?php echo esc_attr( $smash_kind ); ?>"><dt class="mono"><?php echo esc_html( $smash_label ); ?></dt><dd><?php echo wp_kses_post( $smash_b[ $smash_k ] ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
