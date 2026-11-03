<?php
/**
 * Résultats de recherche (bières, articles, pages).
 */

defined( 'ABSPATH' ) || exit;

get_header();

$smash_types = array(
	'bieres' => __( 'bière', 'smash' ),
	'post'   => __( 'article', 'smash' ),
	'page'   => __( 'page', 'smash' ),
);
?>
<main class="page light" id="contenu">
	<?php
	get_template_part( 'template-parts/page-head', null, array(
		'kick'  => __( 'recherche', 'smash' ),
		/* translators: %s : termes recherchés */
		'title' => sprintf( __( '« %s »', 'smash' ), get_search_query() ),
		/* translators: %d : nombre de résultats */
		'text'  => sprintf( _n( '%d résultat', '%d résultats', (int) $wp_query->found_posts, 'smash' ), (int) $wp_query->found_posts ),
	) );
	?>
	<section class="sec" style="padding-top:0">
		<?php get_search_form(); ?>
		<?php if ( have_posts() ) : ?>
			<div class="posts">
				<?php while ( have_posts() ) : the_post(); ?>
					<a class="post-row" href="<?php the_permalink(); ?>">
						<span class="mono"><?php echo esc_html( $smash_types[ get_post_type() ] ?? '' ); ?></span>
						<h2><?php the_title(); ?></h2>
						<span class="mini" aria-hidden="true">
							<?php if ( 'bieres' === get_post_type() ) : ?>
								<i><?php echo smash_label_img( smash_beer(), 'smash-label-sm', true ); ?></i>
							<?php endif; ?>
						</span>
					</a>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => __( 'précédents', 'smash' ), 'next_text' => __( 'suivants', 'smash' ) ) ); ?>
		<?php endif; ?>
	</section>
</main>
<?php
get_footer();
