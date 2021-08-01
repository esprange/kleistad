<?php
/**
 * Toon de speciale pagina voor gelinkte formulieren
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.3
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

namespace Kleistad;

?>

<!DOCTYPE html>
<!--suppress HtmlRequiredLangAttribute -->
<html <?php language_attributes(); ?> class="no-js">
<!--suppress HtmlRequiredTitleElement -->
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
					<div style="float:left;padding-right:20px;">
						<img src="https://www.kleistad.nl/wp/wp-content/uploads/2016/03/cropped-logo-kleistad.jpg" alt="Kleistad" width="100" >
					</div>
					<span class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a><br/></span>
					<span class="site-description"><?php echo get_bloginfo( 'description', 'display' ); // phpcs:ignore ?></span>
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
