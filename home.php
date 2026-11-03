<?php
/**
 * Le blog (/le-blog/, page des articles) : en-tête éditable + liste des articles.
 * Les étiquettes à droite = les bières citées (liens /bieres/…) dans chaque article.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$smash_blog = (int) get_option( 'page_for_posts' );
?>
<main class="page light" id="contenu">
	<?php
	get_template_part( 'template-parts/page-head', null, array(
		'kick'  => smash_page_field( $smash_blog, 'intro_kick' ),
		'title' => smash_page_field( $smash_blog, 'intro_title' ) ?: get_the_title( $smash_blog ),
		'text'  => smash_page_field( $smash_blog, 'intro_text' ),
	) );
	?>
	<section class="sec" style="padding-top:0">
		<?php if ( have_posts() ) : ?>
			<div class="posts">
				<?php while ( have_posts() ) : the_post(); ?>
					<a class="post-row" href="<?php the_permalink(); ?>">
						<span class="mono"><?php echo esc_html( get_the_date() ); ?></span>
						<h2><?php the_title(); ?></h2>
						<span class="mini" aria-hidden="true">
							<?php foreach ( array_slice( smash_post_beers(), 0, 5 ) as $smash_beer ) : ?>
								<i><?php echo smash_label_img( $smash_beer, 'smash-label-sm', true ); ?></i>
							<?php endforeach; ?>
						</span>
					</a>
				<?php endwhile; ?>
			</div>
			<?php
			the_posts_pagination( array(
				'mid_size'  => 1,
				'prev_text' => __( 'Précédents', 'smash' ),
				'next_text' => __( 'Suivants', 'smash' ),
			) );
			?>
		<?php else : ?>
			<p><?php esc_html_e( 'Aucun article pour le moment.', 'smash' ); ?></p>
		<?php endif; ?>
	</section>
</main>
<?php
get_footer();
