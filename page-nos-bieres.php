<?php
/**
 * Page « nos bières » (/nos-bieres/) : en-tête éditable + catalogue en onglets.
 */

defined( 'ABSPATH' ) || exit;

smash_page_script( 'bieres' );
get_header();
?>
<main class="page light" id="contenu">
	<?php get_template_part( 'template-parts/page-head' ); ?>
	<?php get_template_part( 'template-parts/beer-catalog', null, array( 'selected' => 'perm' ) ); ?>
</main>
<?php
get_footer();
