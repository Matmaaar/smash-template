<?php
/**
 * Archive d'une gamme (/type-de-bieres/{gamme}/) : le catalogue, onglet de la gamme ouvert.
 */

defined( 'ABSPATH' ) || exit;

$smash_term    = get_queried_object();
$smash_range   = array_search( $smash_term->slug, smash_map( 'ranges' ), true ) ?: 'perm';
$smash_catalog = smash_catalog_page();

smash_page_script( 'bieres' );
get_header();
?>
<main class="page light" id="contenu">
	<?php
	get_template_part( 'template-parts/page-head', null, array(
		'kick'  => $smash_catalog ? get_the_title( $smash_catalog ) : '',
		'title' => $smash_term->name,
		'text'  => wp_strip_all_tags( term_description( $smash_term ) ),
	) );
	get_template_part( 'template-parts/beer-catalog', null, array( 'selected' => $smash_range ) );
	?>
</main>
<?php
get_footer();
