<?php
/**
 * Toon de showcase gallerij formulier
 *
 * @link       https://www.kleistad.nl
 * @since      7.7.0
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de cursus beheer formulier.
 */
class Public_Showcase_Gallerij_Display extends Public_Shortcode_Display {

	/**
	 * Render de gallerij
	 *
	 * @return void
	 */
	protected function overzicht() : void {
		$teller = (int) count( $this->data['showcases'] ) / 5;
		?>
		<div class="kleistad-gallerij">
		<?php
		foreach ( $this->data['showcases'] as $showcase ) :
			if ( ! $showcase->foto_id ) :
				continue;
			endif;
			$class = 'kleistad-gallerij-item';
			if ( 6 === wp_rand( 1, 6 ) ) :
				if ( $teller-- ) :
					$class .= ' kleistad-gallerij-itemx2';
				endif;
			endif;
			?>
			<div class="<?php echo esc_attr( $class ); ?>" >
				<a target="_blank" href="<?php echo esc_url( get_post_permalink( $showcase->id ) ); ?>" >
				<?php
					echo wp_get_attachment_image(
						$showcase->foto_id,
						'medium',
						false,
						[ 'class' => 'kleistad-gallerij' ]
					);
				?>
				</a>
			</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

}
