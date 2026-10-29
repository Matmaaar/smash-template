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
	'perm' => __( 'gamme permanente', 'smash' ),
	'eph'  => __( 'gamme éphémère', 'smash' ),
	'old'  => __( 'ancienne éphémère', 'smash' ),
);
// Lignes de la fiche, dans l'ordre de la maquette ; seules celles qui sont remplies s'affichent.
$smash_rows = array(
	'style'   => __( 'style', 'smash' ),
	'abv'     => __( 'degré', 'smash' ),
	'formats' => __( 'formats', 'smash' ),
	'malts'   => __( 'malts', 'smash' ),
	'hops'    => __( 'houblons', 'smash' ),
	'yeast'   => __( 'levure', 'smash' ),
	'other'   => __( 'autres', 'smash' ),
	'look'    => __( 'aspect', 'smash' ),
	'desc'    => __( 'dégustation', 'smash' ),
	'when'    => __( 'à boire', 'smash' ),
	'food'    => __( 'accords', 'smash' ),
);
$smash_location = get_page_by_path( 'location-tireuses-a-bieres' );
$smash_rentable = $smash_location && has_term( '', 'type-de-location', $smash_b['id'] );
?>
<main class="page light" id="contenu">
	<section class="sec" style="padding-top:20vh">
		<?php if ( $smash_catalog ) : ?>
			<a href="<?php echo esc_url( get_permalink( $smash_catalog ) ); ?>" class="mono" style="display:inline-flex;gap:.5rem;align-items:center;margin-bottom:4vh">← <?php echo esc_html( mb_strtolower( sprintf( __( 'toutes %s', 'smash' ), get_the_title( $smash_catalog ) ) ) ); ?></a>
		<?php endif; ?>
		<div class="fiche" id="fiche">
			<div class="fiche__label">
				<div class="lab" style="cursor:default">
					<div class="lab__card">
						<?php echo smash_label_img( $smash_b, 'smash-label', false, array( 'loading' => false, 'fetchpriority' => 'high', 'sizes' => '(max-width: 900px) 100vw, 55vw' ) ); ?>
						<?php if ( ! $smash_b['label'] ) : ?>
							<span class="ph mono"><?php esc_html_e( 'étiquette à venir', 'smash' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div>
				<?php smash_kick( $smash_kicks[ $smash_b['range'] ] ?? '' ); ?>
				<h1 class="big" style="font-size:clamp(3.4rem,8vw,7.6rem)"><?php echo esc_html( $smash_b['name'] ); ?></h1>
				<?php if ( $smash_b['intro'] ) : ?>
					<p class="intro"><?php echo esc_html( $smash_b['intro'] ); ?></p>
				<?php endif; ?>
				<dl>
					<?php foreach ( $smash_rows as $smash_key => $smash_label ) : ?>
						<?php
						$smash_value = $smash_b[ $smash_key ];
						if ( '' === $smash_value ) {
							continue;
						}
						if ( 'abv' === $smash_key ) {
							$smash_value .= ' %';
						}
						?>
						<div><dt class="mono"><?php echo esc_html( $smash_label ); ?></dt><dd><?php echo wp_kses_post( $smash_value ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
				<?php if ( $smash_rentable ) : ?>
					<?php smash_btn( __( 'en fût pour ta soirée', 'smash' ), get_permalink( $smash_location ), 'dark' ); ?>
				<?php endif; ?>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
