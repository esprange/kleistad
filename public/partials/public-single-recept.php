<?php
/**
 * De template voor het tonen van een enkel recept
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
			$the_id = get_the_ID();
			if ( false !== $the_id ) :
				$recept_terms = get_the_terms( $the_id, 'kleistad_recept_cat' );
				if ( is_array( $recept_terms ) ) :
					foreach ( $recept_terms as $recept_term ) :
						foreach ( [ Recept::GLAZUUR, Recept::KLEUR, Recept::UITERLIJK ] as $selector ) :
							if ( intval( Recept::hoofdtermen()[ $selector ]->term_id ) === $recept_term->parent ) :
								$naam[ $selector ] = $recept_term->name;
							endif;
						endforeach;
					endforeach;
				endif;
			endif;
			$content = json_decode( get_the_content(), true );

			?>
		<script type="text/javascript">
		( function ( $ ){
			'use strict';
			$(
				function()
				{
					$( '#kleistad_recept_print' ).on(
						'click',
						function() {
							let w       = window.open(),
								c       = Boolean( window.chrome ),
								elem    = document.createElement('textarea'),
								decoded = elem.value;
							if ( c ) {
								elem.innerHTML = '&lt;script type="text/javascript"&gt;' +
								'window.moveTo(0,0);window.resizeTo(640,480);window.print();setTimeout(function(){window.close();},500);' +
								'&lt;/script&gt;';
							} else {
								elem.innerHTML = '&lt;script type="text/javascript"&gt;' +
								'window.print();window.close();' +
								'&lt;/script&gt;';
							}

							w.document.write( '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' );
							w.document.write( '<html lang="NL">' );
							w.document.write( '<head>' );
							w.document.write( '<title><?php the_title(); ?></title>' );
							w.document.write( '<meta charset="utf-8">' );
							w.document.write( '</head><body style="font-family:Verdana, sans-serif;">' );
							w.document.write( $( '.kleistad_recept' ).html() );
							w.document.write( decoded );
							w.document.write( '</body></html>' );
							w.document.close();
						}
					);

					$( '#kleistad_recept_foto' ).on(
						'click',
						function() {
							$( '#kleistad_recept_modal' ).show();
						}
					);

					$( '#kleistad_close_modal' ).on(
						'click',
						function() {
							$( '#kleistad_recept_modal').hide();
						}
					)
				}
			)
		} )( jQuery );
		</script>
		<a style="cursor:pointer;" onClick="window.history.back()">&lt; recepten</a><br/><br/>
		<button class="kleistad-button" id="kleistad_recept_print">Afdrukken</button>
		<div id="kleistad_recept_modal" class="modal"
			style="display:none;position:fixed;z-index:1;padding-top:100px;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgb(0,0,0);background-color:rgba(0,0,0,0.9);">

			<span id="kleistad_close_modal" style="position:absolute;top:35px;right:35px;color:#f1f1f1;font-size:40px;font-weight:bold;transition:0.3s;"
				onMouseOver="this.style.color='#bbb';this.style.cursor='pointer';this.style.textDecoration='none'"
				>&times;</span>

			<img style="margin:auto;display:block;width:80%;max-width:700px;" src="<?php echo esc_url( $content['foto'] ); ?>" alt="<?php the_title(); ?>">

			<div style="margin:auto;display:block;width:80%;max-width:700px;text-align:center;color:#ccc;padding:10px 0;height:150px;animation-name:zoom;animation-duration:0.6s;">
				<?php the_title(); ?>
			</div>
		</div>

		<div class="kleistad_recept" >
			<style>
			table, td, th {
				border: 0;
				vertical-align: top;
				text-align: left;
			}
			table {
				table-layout: auto;
				font-size: small;
				padding: 0;
			}
			</style>

			<h2><?php the_title(); ?></h2>
			<div style="width:100%">
				<div style="float:left;width:50%;padding-bottom:25px;">
					<img src="<?php echo esc_url( $content['foto'] ); ?>"
						style="max-width:100%;max-height:100%;border-radius:5px;cursor:zoom-in;transition: 0.3s;"
						id="kleistad_recept_foto"
						onMouseOver="this.style.opacity=0.7"
						onMouseOut="this.style.opacity=1"
						alt="<?php the_title(); ?>" >
				</div>
				<div style="float:left;width:50%;">
					<table>
					<tr>
						<th>Type glazuur</th>
						<td><?php echo esc_html( $naam[ Recept::GLAZUUR ] ); ?></td>
					</tr>
					<tr>
						<th>Uiterlijk</th>
						<td><?php echo esc_html( $naam[ Recept::UITERLIJK ] ); ?></td>
					</tr>
					<tr>
						<th>Kleur</th>
						<td><?php echo esc_html( $naam[ Recept::KLEUR ] ); ?></td>
					</tr>
					<tr>
						<th>Stookschema</th>
						<td><?php echo $content['stookschema']; // phpcs:ignore ?></td>
					</tr>
					</table>
				</div>
			</div>
			<div style="clear:both;">
				<table style="width:100%">
					<tr>
						<th style="width:25%">Auteur</th>
						<td style="width:25%"><?php the_author(); ?></td>
						<th style="width:25%">Laatste wijziging</th>
						<td style="width:25%"><?php the_modified_date(); ?></td>
					</tr>
					<tr>
						<th colspan="2">Basis recept</th>
						<th colspan="2">Toevoegingen</th>
					</tr>
					<tr>
						<td colspan="2">
							<table>
						<?php
						$normeren = 0;
						foreach ( $content['basis'] as $basis ) :
							$normeren += $basis['gewicht'];
						endforeach;
						$som = 0;
						foreach ( $content['basis'] as $basis ) :
							$som += round( $basis['gewicht'] * 100 / $normeren, 2 );
						endforeach;
						$restant = 100.0 - $som;
						// To make sure that the total equals 100.
						foreach ( $content['basis'] as $basis ) :
							$gewicht = round( $basis['gewicht'] * 100 / $normeren, 2 ) + $restant;
							$restant = 0;
							?>
								<tr>
									<td><?php echo esc_html( $basis['component'] ); ?></td>
									<td style="text-align:right;"><?php echo esc_html( number_format_i18n( $gewicht, 2 ) ); ?> gr.</td>
								</tr>
							<?php
						endforeach;
						?>
							</table>
						</td>
						<td colspan="2">
							<table>
						<?php
						foreach ( $content['toevoeging'] as $toevoeging ) :
							$gewicht = round( $toevoeging['gewicht'] * 100 / $normeren, 2 );
							?>
								<tr>
									<td><?php echo esc_html( $toevoeging['component'] ); ?></td>
									<td style="text-align:right;"><?php echo esc_html( number_format_i18n( $gewicht, 2 ) ); ?> gr.</td>
								</tr>
							<?php
						endforeach;
						?>
							</table>
						</td>
					</tr>
				</table>
			</div>
			<div>
				<table>
					<tr>
						<th>Kenmerken</th>
					</tr>
					<tr>
						<td><?php echo $content['kenmerk']; //  phpcs:ignore ?></td>
					</tr>
				</table>
			</div>
			<div>
				<table>
					<tr>
						<th>Oorsprong</th>
					</tr>
					<tr>
						<td><?php echo $content['herkomst']; //  phpcs:ignore ?></td>
					</tr>
				</table>
			</div>
		</div>
			<?php
			// If comments are open or we have at least one comment, load up the comment template.
			if ( function_exists( 'the_ratings' ) ) {
				the_ratings();
			}
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}

			// End of the loop.
		endwhile;
		?>
	</main><!-- .site-main -->

	<?php get_sidebar( 'content-bottom' ); ?>

</div><!-- .content-area -->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
