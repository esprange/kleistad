<?php
/**
 * De template voor het tonen van een enkel recept
 *
 * @package Kleistad
 * @subpackage Kleistad/public/partials
 * @since Kleistad 4.1.0
 */

namespace Kleistad;

wp_add_inline_script(
	'kleistad',
	'( function ( $ ){
			$(
				function()
				{
					$( ".recept-foto" ).on(
						"click",
						function() {
							$( "#kleistad_recept_modal" ).show();
						}
					);
					$( "#kleistad_close_modal" ).on(
						"click",
						function() {
							$( "#kleistad_recept_modal" ).hide();
						}
					)
				}
			)
		}
	)( jQuery );'
);

get_header();
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<?php
		// Start the loop.
		while ( have_posts() ) :
			the_post();
			$recept = new Recept( get_the_ID() );
			?>
		<a id="kleistad_terug" onClick="window.history.back()">&lt; recepten</a><br/><br/>
		<button class="kleistad-button" onClick="window.print()" >Afdrukken</button>
		<div class="kleistad-recept" >
			<h2><?php the_title(); ?></h2>
			<div style="width:100%">
				<div style="float:left;width:50%;padding-bottom:25px;">
					<?php
					if ( $recept->foto_id ) :
						echo wp_get_attachment_image( $recept->foto_id, 'medium', false, [ 'class' => 'recept-foto' ] );
					endif;
					?>
				</div>
				<div style="float:left;width:50%;">
					<div class="kleistad-row">
						<div class="kleistad-col-5 kleistad-label"><label>Type glazuur</label></div>
						<div class="kleistad-col-5"><?php echo esc_html( $recept->glazuur_naam ); ?></div>
					</div>
					<div class="kleistad-row">
						<div class="kleistad-col-5 kleistad-label"><label>Uiterlijk</label></div>
						<div class="kleistad-col-5"><?php echo esc_html( $recept->uiterlijk_naam ); ?></div>
					</div>
					<div class="kleistad-row">
						<div class="kleistad-col-5 kleistad-label"><label>Kleur</label></div>
						<div class="kleistad-col-5"><?php echo esc_html( $recept->uiterlijk_naam ); ?></div>
					</div>
					<div class="kleistad-row">
						<div class="kleistad-col-5 kleistad-label"><label>Stookschema</label></div>
						<div class="kleistad-col-5"><?php echo $recept->stookschema; // phpcs:ignore ?></div>
					</div>
				</div>
			</div>
			<div style="clear:both;">
				<div class="kleistad-row">
					<div class="kleistad-col-2 kleistad-label">Auteur</div>
					<div class="kleistad-col-3"><?php the_author(); ?></div>
					<div class="kleistad-col-2 kleistad-label">Laatste wijziging</div>
					<div class="kleistad-col-3"><?php the_modified_date(); ?></div>
				</div>
				<div class="kleistad-row">
					<div class="kleistad-col-5">
						<div class="kleistad-label"><label>Basis recept</label></div>
						<table>
							<?php foreach ( $recept->basis as $basis ) : ?>
							<tr>
								<td><?php echo esc_html( $basis['component'] ); ?></td>
								<td style="text-align:right;"><?php echo esc_html( number_format_i18n( $basis['norm_gewicht'], 2 ) ); ?> gr.</td>
							</tr>
							<?php endforeach; ?>
						</table>
					</div>
					<div class="kleistad-col-5">
						<div class="kleistad-label"><label>Toevoegingen</label></div>
						<table>
							<?php foreach ( $recept->toevoeging as $toevoeging ) : ?>
							<tr>
								<td><?php echo esc_html( $toevoeging['component'] ); ?></td>
								<td style="text-align:right;"><?php echo esc_html( number_format_i18n( $toevoeging['norm_gewicht'], 2 ) ); ?> gr.</td>
							</tr>
							<?php endforeach; ?>
						</table>
					</div>
				</div>
				<div class="kleistad-row">
					<div class="kleistad-col-2 kleistad-label"><label>Kenmerken</label></div>
				</div>
				<div class="kleistad-row">
					<div class="kleistad-col-10"><?php echo $recept->kenmerk; //  phpcs:ignore ?></div>
				</div>
				<div class="kleistad-row">
					<div class="kleistad-col-2 kleistad-label"><label>Oorsprong</label></div>
				</div>
				<div class="kleistad-row">
					<div class="kleistad-col-10"><?php echo $recept->herkomst; //  phpcs:ignore ?></div>
				</div>
			</div>
		</div>
		<div id="kleistad_recept_modal" class="modal"
			style="display:none;position:fixed;z-index:1;padding-top:100px;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgb(0,0,0);background-color:rgba(0,0,0,0.9);">

			<span id="kleistad_close_modal" style="position:absolute;top:35px;right:35px;color:#f1f1f1;font-size:40px;font-weight:bold;transition:0.3s;"
				onMouseOver="this.style.color='#bbb';this.style.cursor='pointer';this.style.textDecoration='none'"
				>&times;</span>

			<?php
			if ( $recept->foto_id ) :
				echo wp_get_attachment_image( $recept->foto_id, 'large' );
			endif;
			?>
			<div style="margin:auto;display:block;width:80%;max-width:700px;text-align:center;color:#ccc;padding:10px 0;height:150px;animation-name:zoom;animation-duration:0.6s;">
				<?php the_title(); ?>
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

</div><!-- .content-area -->
<?php get_footer(); ?>
