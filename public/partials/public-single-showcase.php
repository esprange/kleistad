<?php
/**
 * De template voor het tonen van een enkele showcase
 *
 * @package Kleistad
 * @subpackage Kleistad/public/partials
 * @since Kleistad 7.6.4
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
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<div class="site-inner">
		<header id="masthead" role="banner">
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
			<main id="main" class="site-main" role="main">
				<?php
				// Start the loop.
				while ( have_posts() ) :
					the_post();
					$showcase = new Showcase( get_the_ID() );
					$keramist = get_user_by( 'ID', $showcase->keramist_id );
					?>
				<div class="kleistad kleistad-showcase" >
					<div class="kleistad-showcase-item"> <!-- first container -->
						<?php
						echo wp_get_attachment_image(
							$showcase->foto_id,
							'large',
							false,
							[ 'class' => 'kleistad-showcase' ]
						);
						?>
					</div>
					<div>  <!-- second container -->
						<div class="kleistad-showcase-item">
							<div class="kleistad-showcase-titel"><?php the_title(); ?></div>
							<div class="kleistad-label"><label>Prijs</label></div>
							<div style="padding-left:15px">&euro; <?php echo esc_html( number_format_i18n( $showcase->prijs, 2 ) ); ?>
								<?php echo $showcase->is_tentoongesteld() ? ' (nu tentoongesteld)' : ''; ?>
							</div>
							<?php if ( $showcase->beschrijving ) : ?>
								<div class="kleistad-label"><label>Beschrijving</label></div>
								<div style="padding-left:15px"><?php echo esc_html( $showcase->beschrijving ); ?></div>
							<?php endif; ?>
						</div>
						<div class="kleistad-showcase-item">
							<div class="kleistad-label"><label>Keramist</label></div>
							<div style="padding-left:10px"><?php echo esc_html( $keramist->display_name ); ?></div>
							<?php if ( $keramist->description ) : ?>
								<div class="kleistad-label"><label>Over de keramist</label></div>
								<div style="padding-left:15px"><?php echo esc_html( $keramist->description ); ?></div>
							<?php endif; ?>
							<?php if ( $keramist->user_url ) : ?>
								<div class="kleistad-label"><label>Website van de keramist</label></div>
								<div style="padding-left:15px"><a href="<?php echo esc_url( $keramist->user_url ); ?>"><?php echo esc_url( $keramist->user_url ); ?></a></div>
							<?php endif; ?>
						</div>
					</div>
				</div>
					<?php
					// End of the loop.
				endwhile;
				?>
			</main><!-- .site-main -->
		</div>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
