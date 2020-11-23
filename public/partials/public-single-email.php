<?php
/**
 * De template voor het tonen van een enkel recepten
 *
 * @package Kleistad
 * @subpackage Kleistad/public/partials
 * @since Kleistad 4.1.0
 */

namespace Kleistad;

get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<?php
		// Start the loop.
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
			</header><!-- .entry-header -->

			<div class="entry-content">
				<?php the_content(); ?>
			</div><!-- .entry-content -->

			<footer class="entry-footer">
				<?php
					edit_post_link(
						sprintf(
							/* translators: %s: Name of current post */
							__( 'Edit<span class="screen-reader-text"> "%s"</span>', 'twentysixteen' ),
							get_the_title()
						),
						'<span class="edit-link">',
						'</span>'
					);
				?>
			</footer><!-- .entry-footer -->
			</article><!-- #post-<?php the_ID(); ?> -->
			<?php
			// End of the loop.
		endwhile;
		?>
	</main><!-- .site-main -->

</div><!-- .content-area -->
<?php get_footer(); ?>
