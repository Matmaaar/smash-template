<?php
/**
 * Plan du site (/sitemap/) : pages, bières par gamme, articles. Tout est généré automatiquement.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$smash_pages = get_pages( array( 'sort_column' => 'menu_order,post_title', 'exclude' => array( get_queried_object_id() ) ) );
$smash_names = array(
	'perm' => __( 'Bières permanentes', 'smash' ),
	'eph'  => __( 'Bières éphémères', 'smash' ),
	'old'  => __( 'Anciennes éphémères', 'smash' ),
);
$smash_posts = get_posts( array( 'numberposts' => -1 ) );
?>
<main class="page light" id="contenu">
	<?php get_template_part( 'template-parts/page-head' ); ?>
	<section class="sec" style="padding-top:0">
		<div class="smap">
			<div>
				<h2 class="smap__t mono"><?php esc_html_e( 'Pages', 'smash' ); ?></h2>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Accueil', 'smash' ); ?></a></li>
					<?php foreach ( $smash_pages as $smash_p ) : ?>
						<?php if ( (int) get_option( 'page_on_front' ) === $smash_p->ID ) { continue; } ?>
						<li><a href="<?php echo esc_url( get_permalink( $smash_p ) ); ?>"><?php echo esc_html( get_the_title( $smash_p ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php foreach ( $smash_names as $smash_range => $smash_name ) : ?>
				<?php $smash_list = smash_beers( $smash_range ); ?>
				<?php if ( $smash_list ) : ?>
					<div>
						<h2 class="smap__t mono"><?php echo esc_html( $smash_name ); ?></h2>
						<ul>
							<?php foreach ( $smash_list as $smash_beer ) : ?>
								<li><a href="<?php echo esc_url( $smash_beer['url'] ); ?>"><?php echo esc_html( $smash_beer['name'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php if ( $smash_posts ) : ?>
				<div>
					<h2 class="smap__t mono"><?php esc_html_e( 'Articles', 'smash' ); ?></h2>
					<ul>
						<?php foreach ( $smash_posts as $smash_p ) : ?>
							<li><a href="<?php echo esc_url( get_permalink( $smash_p ) ); ?>"><?php echo esc_html( get_the_title( $smash_p ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
