<?php
/**
 * Toon de speciale betaling pagina
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.3
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<div class="site-inner">
		<header id="masthead" class="site-header" role="banner">
			<div class="site-header-main">
				<div class="site-branding">
					<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
					<p class="site-description"><?php echo get_bloginfo( 'description', 'display' ); // phpcs:ignore ?></p>
				</div><!-- .site-branding -->
			</div><!-- .site-header-main -->
		</header><!-- .site-header -->
		<div id="content" class="site-content">
			<div id="primary" class="content-area">
				<main id="main" class="site-main" role="main">
					<?php the_post(); ?>
						<div class="entry-content">
							<?php the_content(); ?>
						</div><!-- .entry-content -->
				</main><!-- .site-main -->
			</div><!-- .content-area -->
		</div>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
