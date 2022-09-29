<?php
/**
 * De template voor het tonen van een enkele showcase
 *
 * @package Kleistad
 * @subpackage Kleistad/public/partials
 * @since Kleistad 7.6.4
 */

namespace Kleistad;

wp_enqueue_style( 'dashicons' );
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
				$showcase_id = get_the_ID();
				$showcases   = new Showcases(
					[
						'post_status' => [ Showcase::BESCHIKBAAR ],
						'orderby'     => 'ID',
					]
				);
				while ( $showcases->current()->id !== $showcase_id ) {
					$showcases->next();
				}
				if ( ! $showcases->valid() ) :
					// De showcase is waarschijnlijk niet meer beschikbaar en al verkocht. Dan een soort 404 tonen.
					?>
						<strong>Het werkstuk is helaas niet meer beschikbaar</strong>
					<?php
				else :
					$keramist = get_user_by( 'ID', $showcases->current()->keramist_id );
					?>
				<div class="kleistad kleistad-showcase" >
					<?php if ( $showcases->count() ) : ?>
						<a class="kleistad-showcase-prev dashicons dashicons-arrow-left-alt2"
							href="<?php echo esc_url( get_post_permalink( $showcases->get_prev()->id ) ); ?>">
						</a>
						<a class="kleistad-showcase-next dashicons dashicons-arrow-right-alt2"
							href="<?php echo esc_url( get_post_permalink( $showcases->get_next()->id ) ); ?>">
						</a>
					<?php endif; ?>
					<div class="kleistad-showcase-item"> <!-- first container -->
						<?php
						echo wp_get_attachment_image(
							$showcases->current()->foto_id,
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
							<div style="padding-left:15px">&euro; <?php echo esc_html( number_format_i18n( $showcases->current()->prijs, 2 ) ); ?>
								<?php echo $showcases->current()->is_tentoongesteld() ? ' (nu tentoongesteld)' : ''; ?>
							</div>
							<?php if ( $showcases->current()->beschrijving ) : ?>
								<div class="kleistad-label"><label>Beschrijving</label></div>
								<div style="padding-left:15px"><?php echo esc_html( $showcases->current()->beschrijving ); ?></div>
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
				<?php endif ?>
			</main><!-- .site-main -->
		</div>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
