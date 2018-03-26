<?php
/**
 * The template for displaying all single kleistad_recepten
 *
 * @package WordPress
 * @subpackage Kleistad
 * @since Kleistad 4.1.0
 */

get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<?php
		// Start the loop.
		while ( have_posts() ) :
			the_post();

			$glazuur_parent = get_term_by( 'name', '_glazuur', 'kleistad_recept_cat' );
			$kleur_parent = get_term_by( 'name', '_kleur', 'kleistad_recept_cat' );
			$uiterlijk_parent = get_term_by( 'name', '_uiterlijk', 'kleistad_recept_cat' );
			$glazuur_naam = '';
			$kleur_naam = '';
			$uiterlijk_naam = '';
			$terms = get_the_terms( get_the_ID(), 'kleistad_recept_cat' );
			foreach ( $terms as $term ) {
				if ( intval( $term->parent ) === intval( $glazuur_parent->term_id ) ) {
					$glazuur_naam = $term->name;
				}
				if ( intval( $term->parent ) === intval( $kleur_parent->term_id ) ) {
					$kleur_naam = $term->name;
				}
				if ( intval( $term->parent ) === intval( $uiterlijk_parent->term_id ) ) {
					$uiterlijk_naam = $term->name;
				}
			}
			$content = json_decode( get_the_content(), true );

		?>
		<script type="text/javascript">
		( function ( $ ){
			'use strict';

			$( document ).ready(function() {
				$( '#kleistad_recept_print' ).click( function(){
					var w = window.open();
					var c = Boolean( window.chrome );

					var elem = document.createElement('textarea');
					if ( c ) {
						elem.innerHTML = '&lt;script type="text/javascript"&gt;' + 
						'window.moveTo(0,0);window.resizeTo(640,480);window.print();setTimeout(function(){window.close();},500);' +
						'&lt;/script&gt;';
					} else {
						elem.innerHTML = '&lt;script type="text/javascript"&gt;' + 
						'window.print();window.close();' +
						'&lt;/script&gt;';						
					}
					//	function closeme(){window.close();}setTimeout(closeme,500);window.print();&lt;/script&gt;';
					var decoded = elem.value;

					w.document.write( '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' );
					w.document.write( '<html>' );
					w.document.write( '<head>' );
					w.document.write( '<meta charset="utf-8">' );
					w.document.write( '</head><body style="font-family:Verdana;">' );
					w.document.write( $( '.kleistad_recept' ).html() );
					w.document.write( decoded );
					w.document.write( '</body></html>' );
					w.document.close();
				});
				
				$( '#kleistad_recept_foto' ).click( function() {
					$( '#kleistad_recept_modal' ).show();
				});
				
				$( '#kleistad_close_modal' ).click( function() {
					$( '#kleistad_recept_modal').hide();
				})
			});
		} )( jQuery );
		</script>
		<a style="cursor:pointer;" onClick="window.history.back()">&lt; recepten</a><br/><br/>
		<button id="kleistad_recept_print">Afdrukken</button>
		<div id="kleistad_recept_modal" class="modal" 
			 style="display:none;position:fixed;z-index:1;padding-top:100px;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgb(0,0,0);background-color:rgba(0,0,0,0.9);">

		  <span id="kleistad_close_modal" style="position:absolute;top:35px;right:35px;color:#f1f1f1;font-size:40px;font-weight:bold;transition:0.3s;"
				onMouseOver="this.style.color='#bbb';this.style.cursor='pointer';this.style.textDecoration='none'"
				>&times;</span>

		  <img style="margin:auto;display:block;width:80%;max-width:700px;" src="<?php echo esc_url( $content['foto'] ); ?>">

		  <div style="margin:auto;display:block;width:80%;max-width:700px;text-align:center;color:#ccc;padding:10px 0;height:150px;animation-name:zoom;animation-duration:0.6s;">
				<?php the_title(); ?>
		  </div>
		</div> 
		
		<div class="kleistad_recept" >
			<style>
			table, td, th {
				border: 0px;
				vertical-align: top;
				text-align: left;
			}
			table {
				table-layout: auto;
				font-size: small;
				padding: 0px;
			}
			</style>

			<h2><?php the_title(); ?></h2>
			<div style="width:100%"> 
				<div style="float:left;width:40%;padding-bottom:25px;">
					<img src="<?php echo esc_url( $content['foto'] ); ?>" 
						 style="max-width:100%;max-height:100%;border-radius:5px;cursor:zoom-in;transition: 0.3s;" 
						 id="kleistad_recept_foto" 
						 onMouseOver="this.style.opacity='0.7'" >
				</div>
				<div style="float:left;width:60%;">
					<table style="padding-left:25px;">
					<tr>
						<th>Type glazuur</th>
						<td><?php echo esc_html( $glazuur_naam ); ?></td>
					</tr>
					<tr>
						<th>Uiterlijk</th>
						<td><?php echo esc_html( $uiterlijk_naam ); ?></td>
					</tr>
					<tr>
						<th>Kleur</th>
						<td><?php echo esc_html( $kleur_naam ); ?></td>
					</tr>
					<tr>
						<th>Stookschema</th>
						<td><?php echo $content['stookschema']; // WPCS: XSS ok. ?></td>
					</tr>
					</table>
				</div>
			</div>
			<div style="clear:both;">
				<table style="width:100%">
					<tr>
						<th>Auteur</th>
						<td><?php the_author(); ?></td>
						<th>Laatste wijziging</th>
						<td><?php the_modified_date(); ?></td>
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
				<h3>Kenmerken</h3>
				<?php echo $content['kenmerk']; // WPCS: XSS ok. ?>
			</div>
			<div>
				<h3>Oorsprong</h3>
				<?php echo $content['herkomst']; // WPCS: XSS ok. ?>
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
