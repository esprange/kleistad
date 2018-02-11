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

					var elem = document.createElement('textarea');
					elem.innerHTML = '&lt;script type="text/javascript"&gt;function closeme(){window.close();}setTimeout(closeme,50);window.print();&lt;/script&gt;';
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
			});
		} )( jQuery );
		</script>
		<button id="kleistad_recept_print">Afdrukken</button>
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
				<div style="float:left;width:40%;">
					<img src="<?php echo esc_url( $content['foto'] ); ?>" width="100%" style="padding:15px;">
				</div>
				<div style="float:left;width:60%;">
					<table style="padding:15px;">
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
				<table>
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
						foreach ( $content['basis'] as $basis ) :
						?>
								<tr>
									<td><?php echo esc_html( $basis['component'] ); ?></td>
									<td><?php echo esc_html( $basis['gewicht'] ); ?> gr.</td>
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
						?>
								<tr>
									<td><?php echo esc_html( $toevoeging['component'] ); ?></td>
									<td><?php echo esc_html( $toevoeging['gewicht'] ); ?> gr.</td>
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
