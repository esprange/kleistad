<?php
/**
 * Toon het rapport formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de email formulier.
 */
class Public_Rapport_Display extends Public_Shortcode_Display {

	/**
	 * Render het rapport voor de ingelogde gebruiker
	 *
	 * @return void
	 */
	protected function overzicht() {
		$this->rapport();
	}

	/**
	 * Render het rapport van de geselecteerde gebruiker
	 */
	protected function rapport_gebruiker() {
		$this->rapport();
		?>
		<button class="kleistad-button kleistad-terug-link" type="button" data-xactie="gebruikers" style="float:right" >Terug</button>
		<?php
	}

	/**
	 * Render het overzicht van de saldo van de gebruikers.
	 */
	protected function gebruikers() {
		?>
		<table class="kleistad-datatable display compact" data-order= '[[ 0, "asc" ]]'>
			<thead>
				<tr>
					<th>Naam</th>
					<th data-class-name="dt-body-right">Saldo</th>
					<th  data-class-name="dt-body-center" data-orderable="false"></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $this->data['stokers'] as $stoker ) : ?>
				<tr>
					<td><?php echo esc_html( $stoker['naam'] ); ?></td>
					<td>&euro; <?php echo esc_html( $stoker['saldo'] ); ?></td>
					<td>
						<a href="#" title="details" class="kleistad-view kleistad-edit-link"
							data-id="<?php echo esc_attr( $stoker['id'] ); ?>" data-actie="rapport_gebruiker" >
							&nbsp;
						</a>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Het overzicht van een enkele gebruikers
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function rapport() {
		$ovenstook = count( array_column( $this->data['items'], 'oven' ) );
		?>
		<p>Saldorapport voor <?php echo esc_html( $this->data['naam'] ); ?> (het huidig saldo is &euro; <?php echo esc_html( $this->data['saldo'] ); ?>)</p>
		<table class="kleistad-datatable display compact" data-order= '[[ 0, "desc" ]]' >
			<thead>
				<tr>
				<?php if ( $ovenstook ) : ?>
					<th>Datum</th>
					<th>Oven</th>
					<th>Stoker</th>
					<th>Stook</th>
					<th data-class-name="dt-body-right">Temp</th>
					<th data-class-name="dt-body-right">Prog</th>
					<th data-class-name="dt-body-right">%</th>
					<th data-class-name="dt-body-right">Bedrag</th>
					<th data-class-name="dt-body-center">Voorlopig</th>
				<?php else : ?>
					<th>Datum</th>
					<th>Actie</th>
					<th data-class-name="dt-body-right">Bedrag</th>
				<?php endif; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $this->data['items'] as $item ) : ?>
				<tr>
					<td data-sort=<?php echo esc_attr( $item['datum'] ); ?> ><?php echo esc_html( date( 'd-m-Y', $item['datum'] ) ); ?></td>
					<?php if ( $ovenstook ) : ?>
						<?php if ( isset( $item['oven'] ) ) : ?>
							<td><?php echo esc_html( $item['oven'] ); ?></td>
							<td><?php echo esc_html( $item['stoker'] ); ?></td>
							<td><?php echo esc_html( $item['stook'] ); ?></td>
							<td><?php echo esc_html( $item['temp'] ); ?></td>
							<td><?php echo esc_html( $item['prog'] ); ?></td>
							<td><?php echo esc_html( $item['perc'] ); ?></td>
						<?php else : ?>
							<td colspan="6"><?php echo esc_html( $item['status'] ); ?></td>
							<td style="display:none">
							<td style="display:none">
							<td style="display:none">
							<td style="display:none">
							<td style="display:none">
						<?php endif ?>
						<td>&euro; <?php echo esc_html( $item['bedrag'] ); ?></td>
						<td data-sort="<?php echo (int) $item['voorlopig']; ?>"><span <?php echo $item['voorlopig'] ? 'class="dashicons dashicons-yes"' : ''; ?> ></span></td>
					<?php else : ?>
						<td><?php echo esc_html( $item['status'] ); ?></td>
						<td>&euro; <?php echo esc_html( $item['bedrag'] ); ?></td>
					<?php endif; ?>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
		<?php
	}
}
