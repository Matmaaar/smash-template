<?php
/**
 * Page simple (mentions légales, confidentialité…) : en-tête éditable + contenu de l'éditeur.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main class="page light" id="contenu">
	<?php get_template_part( 'template-parts/page-head' ); ?>
	<section class="sec sec--flush">
		<?php while ( have_posts() ) : the_post(); ?>
			<div class="prose prose--page"><?php the_content(); ?></div>
		<?php endwhile; ?>
	</section>
</main>
<?php
get_footer();
