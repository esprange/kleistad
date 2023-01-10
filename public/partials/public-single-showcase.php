<?php
/**
 * De template voor het tonen van een enkele showcase
 *
 * @package Kleistad
 * @subpackage Kleistad/public/partials
 * @since Kleistad 7.6.4
 */

namespace Kleistad;

add_action(
	'wp_enqueue_scripts',
	function() {
		$dev = 'development' === wp_get_environment_type() ? '' : '.min';
		wp_enqueue_style( 'dashicons' );
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'wc-blocks-style' );
		wp_enqueue_script( 'kleistad_galerie', plugin_dir_url( __FILE__ ) . "../js/public-galerie$dev.js", [ 'jquery' ], versie(), true );
		wp_add_inline_script(
			'kleistad_galerie',
			'const kleistadData = ' . wp_json_encode(
				[
					'nonce'         => wp_create_nonce( 'wp_rest' ),
					'error_message' => 'het was niet mogelijk om de bewerking uit te voeren',
					'base_url'      => base_url(),
				]
			),
			'before'
		);
	}
);

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
			<div id="showcase" class="kleistad kleistad-showcase" data-id="<?php echo esc_attr( get_the_ID() ); ?>" >
					<a class="kleistad-showcase-prev dashicons dashicons-arrow-left-alt2 showcase-link" id="prev" ></a>
					<a class="kleistad-showcase-next dashicons dashicons-arrow-right-alt2 showcase-link" id="next" ></a>
					<div class="kleistad-showcase-item" id="foto"></div>
					<div>
						<div class="kleistad-showcase-item">
							<div class="kleistad-showcase-titel" id="titel" style="text-align: center;"></div>
							<br/>
							<div class="kleistad-showcase-status" id="status" style="text-align: center;"></div>
							<strong>Prijs</strong>
							<div style="padding-left:15px" id="prijs"></div>
							<div>
								<strong id="beschrijving_label">Beschrijving</strong>
								<div style="padding-left:15px" id="beschrijving"></div>
							</div>
						</div>
						<div class="kleistad-showcase-item">
							<strong id="keramist_label">Keramist</strong>
							<div style="padding-left:10px" id="keramist"></div>
							<div>
								<strong id="bio_label">Over de keramist</strong>
								<div>
									<div style="padding-left:15px;width:70%;float:left" id="bio"></div>
									<div id="keramist_foto"></div>
								</div>
							</div>
							<div style="clear: left">
								<strong id="website_label">Website van de keramist</strong>
								<div style="padding-left:15px" id="website"></div>
							</div>
							<div>
								<strong id="meer_panel_label">Meer van deze keramist</strong>
								<div id="meer_panel" class="kleistad-gallerij-keramist">
								</div>
							</div>
						</div>
					</div>
				</div>
			</main><!-- .site-main -->
		</div>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
