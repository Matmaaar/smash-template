<?php
/**
 * Accueil : sections de la maquette, textes et images éditables (groupe ACF « Accueil »).
 */

defined( 'ABSPATH' ) || exit;

smash_page_script( 'home' );
get_header();

$smash_f      = 'smash_front';
$smash_perm   = smash_beers( 'perm' );
$smash_latest = smash_latest_post();
$smash_series = $smash_latest ? smash_post_beers( $smash_latest ) : array();
// Le mur : éphémères du moment puis les plus récentes des anciennes.
$smash_wall   = array_merge( smash_beers( 'eph' ), smash_beers( 'old', 24 ) );

/** Image de fond / de section (décorative : le texte à côté porte le sens). */
$smash_img = function ( $name, $attr = array(), $size = 'large' ) {
	$id = (int) smash_front( $name );
	return $id ? wp_get_attachment_image( $id, $size, false, array_merge( array( 'alt' => '', 'loading' => 'lazy' ), $attr ) ) : '';
};
?>
<main class="page" id="contenu">

	<section class="hero">
		<div class="hero__img" data-par>
			<?php echo $smash_img( 'hero_image', array( 'loading' => false, 'fetchpriority' => 'high' ), 'full' ); ?>
		</div>
		<div class="hero__in">
			<div>
				<?php smash_kick( $smash_f( 'hero_kick' ) ); ?>
				<h1 class="big" data-lines><?php echo smash_multiline( $smash_f( 'hero_title' ) ?: get_bloginfo( 'name' ) ); ?></h1>
				<div class="hero__sub">
					<?php if ( $smash_f( 'hero_text' ) ) : ?>
						<p><?php echo esc_html( $smash_f( 'hero_text' ) ); ?></p>
					<?php endif; ?>
					<?php smash_btn( $smash_f( 'hero_btn_label' ), smash_link_url( $smash_f( 'hero_btn_link' ) ) ); ?>
				</div>
			</div>
			<div class="S" id="heroS" aria-hidden="true"><i></i><i></i><i></i></div>
		</div>
	</section>

	<?php if ( $smash_perm ) : ?>
		<section class="sec light stepped">
			<div class="head">
				<div>
					<?php smash_kick( $smash_f( 'perm_kick' ) ); ?>
					<h2 class="big" data-up><?php echo smash_multiline( $smash_f( 'perm_title' ) ); ?></h2>
				</div>
				<?php smash_btn( $smash_f( 'perm_btn_label' ), smash_link_url( $smash_f( 'perm_btn_link' ) ), 'dark' ); ?>
			</div>
			<div class="labels">
				<?php foreach ( $smash_perm as $smash_beer ) { smash_label_card( $smash_beer ); } ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $smash_latest ) : ?>
		<?php
		$smash_title = get_the_title( $smash_latest );
		// « bières éphémères : série #21 » => « série #21 » (le sur-titre dit déjà « bières éphémères »).
		$smash_short = false !== strpos( $smash_title, ':' ) ? trim( substr( strrchr( $smash_title, ':' ), 1 ) ) : $smash_title;
		$smash_rots  = array( -8, 4, -3, 7 );
		?>
		<section class="sec stepped flip">
			<div class="drop-sec">
				<div>
					<?php smash_kick( trim( $smash_f( 'series_kick' ) . ' · ' . get_the_date( '', $smash_latest ), ' ·' ) ); ?>
					<h2 class="big" data-up><?php echo esc_html( $smash_short ); ?></h2>
					<p><?php echo esc_html( get_the_excerpt( $smash_latest ) ); ?></p>
					<?php smash_btn( $smash_f( 'series_btn_label' ), get_permalink( $smash_latest ) ); ?>
				</div>
				<?php if ( $smash_series ) : ?>
					<div class="stack" data-stack>
						<?php foreach ( array_slice( $smash_series, 0, 4 ) as $smash_i => $smash_beer ) : ?>
							<a class="flat" href="<?php echo esc_url( $smash_beer['url'] ); ?>" data-rot="<?php echo (int) $smash_rots[ $smash_i ]; ?>"
								style="left:<?php echo 4 + $smash_i * 9; ?>%;top:<?php echo 6 + $smash_i * 15; ?>%;z-index:<?php echo (int) $smash_i; ?>;transform:rotate(<?php echo (int) $smash_rots[ $smash_i ]; ?>deg)">
								<?php echo smash_label_img( $smash_beer, 'smash-label' ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $smash_wall ) : ?>
		<section class="light stepped">
			<div class="sec head" style="padding-bottom:6vh;margin-bottom:0">
				<div>
					<?php smash_kick( $smash_f( 'wall_kick' ) ); ?>
					<h2 class="big" data-up><?php echo smash_multiline( $smash_f( 'wall_title' ) ); ?></h2>
				</div>
				<?php if ( $smash_f( 'wall_text' ) ) : ?>
					<p><?php echo esc_html( $smash_f( 'wall_text' ) ); ?></p>
				<?php endif; ?>
			</div>
			<div class="wall" data-wall>
				<?php for ( $smash_k = 0; $smash_k < 3; $smash_k++ ) : ?>
					<div class="wall__row">
						<?php
						$smash_n = count( $smash_wall );
						// Deux passages : le second sert à boucler le défilement, il est caché aux lecteurs d'écran.
						for ( $smash_copy = 0; $smash_copy < 2; $smash_copy++ ) {
							for ( $smash_i = 0; $smash_i < $smash_n; $smash_i++ ) {
								$smash_beer = $smash_wall[ ( $smash_i * 3 + $smash_k ) % $smash_n ];
								$smash_hide = $smash_copy || $smash_k ? ' aria-hidden="true" tabindex="-1"' : '';
								printf(
									'<a href="%s" class="tile"%s>%s<span class="mono">%s</span></a>',
									esc_url( $smash_beer['url'] ),
									$smash_hide, // phpcs:ignore WordPress.Security.EscapeOutput -- attributs fixes.
									smash_label_img( $smash_beer, 'smash-label-sm', true ), // phpcs:ignore WordPress.Security.EscapeOutput
									esc_html( $smash_beer['name'] . ' · ' . $smash_beer['style'] )
								);
							}
						}
						?>
					</div>
				<?php endfor; ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="sec stepped flip">
		<div class="teaser">
			<div class="teaser__img"><div data-par><?php echo $smash_img( 'brew_image' ); ?></div></div>
			<div>
				<?php smash_kick( $smash_f( 'brew_kick' ) ); ?>
				<?php if ( $smash_f( 'brew_text' ) ) : ?>
					<p><?php echo esc_html( $smash_f( 'brew_text' ) ); ?></p>
				<?php endif; ?>
				<?php smash_btn( $smash_f( 'brew_btn_label' ), smash_link_url( $smash_f( 'brew_btn_link' ) ) ); ?>
			</div>
		</div>
	</section>

	<section class="sec light stepped">
		<div class="teaser">
			<div>
				<?php smash_kick( $smash_f( 'loc_kick' ) ); ?>
				<h2 class="big" data-up style="font-size:clamp(3rem,7vw,7rem)"><?php echo smash_multiline( $smash_f( 'loc_title' ) ); ?></h2>
				<?php if ( $smash_f( 'loc_text' ) ) : ?>
					<p><?php echo esc_html( $smash_f( 'loc_text' ) ); ?></p>
				<?php endif; ?>
				<?php smash_btn( $smash_f( 'loc_btn_label' ), smash_link_url( $smash_f( 'loc_btn_link' ) ), 'dark' ); ?>
			</div>
			<div class="teaser__img"><div data-par><?php echo $smash_img( 'loc_image' ); ?></div></div>
		</div>
	</section>

	<?php $smash_insta = smash_option( 'instagram' ); ?>
	<section class="sec stepped flip">
		<div class="head">
			<div>
				<?php smash_kick( $smash_f( 'insta_kick' ) ); ?>
				<h2 class="big" data-up><?php echo smash_multiline( $smash_f( 'insta_title' ) ); ?></h2>
			</div>
			<?php smash_btn( $smash_f( 'insta_btn_label' ), $smash_insta ); ?>
		</div>
		<div class="insta">
			<?php for ( $smash_i = 1; $smash_i <= 3; $smash_i++ ) : ?>
				<?php $smash_pic = $smash_img( 'insta_image_' . $smash_i ); ?>
				<?php if ( $smash_pic ) : ?>
					<a href="<?php echo esc_url( $smash_insta ); ?>" class="post" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Instagram (nouvel onglet)', 'smash' ); ?>"><div><?php echo $smash_pic; ?></div></a>
				<?php endif; ?>
			<?php endfor; ?>
			<a href="<?php echo esc_url( $smash_insta ); ?>" class="post logo" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Instagram (nouvel onglet)', 'smash' ); ?>">
				<img src="<?php echo esc_url( SMASH_URI . '/assets/img/logo-white.png' ); ?>" alt="" loading="lazy">
			</a>
		</div>
	</section>

</main>
<?php
get_footer();
