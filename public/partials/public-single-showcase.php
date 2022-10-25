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
					if ( ! $showcases->valid() ) :
						// De showcase is waarschijnlijk niet meer beschikbaar en al verkocht. Dan een soort 404 tonen.
						?>
						<strong>Het werkstuk is helaas niet meer beschikbaar</strong>
						<?php
						break;
					endif;
				}
				if ( $showcases->valid() ) :
					$keramist = get_user_by( 'ID', $showcases->current()->keramist_id );
					?>
				<div class="kleistad kleistad-showcase" >
					<?php if ( 1 < $showcases->count() ) : ?>
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
							<strong>Prijs</strong>
							<div style="padding-left:15px">&euro; <?php echo esc_html( number_format_i18n( $showcases->current()->prijs, 2 ) ); ?>
								<?php echo $showcases->current()->is_tentoongesteld() ? ' (nu tentoongesteld)' : ''; ?>
							</div>
							<?php if ( $showcases->current()->beschrijving ) : ?>
								<strong>Beschrijving</strong>
								<div style="padding-left:15px"><?php echo esc_html( $showcases->current()->beschrijving ); ?></div>
							<?php endif; ?>
						</div>
						<div class="kleistad-showcase-item">
							<strong>Keramist</strong>
							<div style="padding-left:10px"><?php echo esc_html( $keramist->display_name ); ?></div>
							<?php if ( $keramist->description ) : ?>
								<strong>Over de keramist</strong>
								<?php
								$profiel_foto_id = get_user_meta( $keramist->ID, 'profiel_foto', true );
								if ( $profiel_foto_id ) :
									?>
									<div>
										<div style="padding-left:15px;width:70%;float:left"><?php echo $keramist->description; // phpcs:ignore ?></div>
										<?php echo wp_get_attachment_image( $profiel_foto_id ); ?>
									</div>
								<?php else : ?>
									<div style="padding-left:15px"><?php echo $keramist->description; // phpcs:ignore ?></div>
								<?php endif ?>
							<?php endif; ?>
							<?php if ( $keramist->user_url ) : ?>
								<div style="clear: left">
									<strong>Website van de keramist</strong>
									<div style="padding-left:15px"><a href="<?php echo esc_url( $keramist->user_url ); ?>"><?php echo esc_url( $keramist->user_url ); ?></a></div>
								</div>
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
