<?php
/**
 * Toon het recept beheer formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de cursus beheer formulier.
 */
class Public_Saldo_Overzicht_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function html() {
		$this->overzicht();
	}

	/**
	 * Toon het overzicht van cursussen
	 */
	private function overzicht() {
		?>
		<table class="kleistad-datatable display compact" data-order= '[[ 0, "asc" ]]'>
			<thead>
				<tr>
					<th>Naam</th>
					<th data-class-name="dt-body-right">Saldo</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $this->data['stokers'] as $stoker ) : ?>
				<tr>
					<td><?php echo esc_html( $stoker['naam'] ); ?></td>
					<td>&euro; <?php echo esc_html( $stoker['saldo'] ); ?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<?php
	}

}
