<?php
/**
 * Toon het werkplekrapport formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van het werkplek formulier.
 */
class Public_Werkplekrapport_Display extends Public_Shortcode_Display {

	/**
	 * Render het rapport
	 */
	protected function individueel() {
		if ( ! isset( $this->data['rapport'] ) ) {
			?>
		<form method="GET" action="<?php echo esc_attr( get_permalink() ?: '#' ); ?>">
			<?php $this->datums()->werkplekgebruiker(); ?>
			<div class="kleistad-row" style="padding-top:20px;" >
				<button class="kleistad-button" type="submit" >Rapport</button>
			</div>
		</form>
			<?php
			return;
		}
		?>
		<h2>Overzicht werkplekgebruik vanaf <?php echo esc_html( date( 'd-m-Y', $this->data['vanaf_datum'] ) ); ?> tot <?php echo esc_html( date( 'd-m-Y', $this->data['tot_datum'] ) ); ?> door <?php echo esc_html( get_user_by( 'id', $this->data['gebruiker_id'] )->display_name ); ?></h2>
		<?php
		$this->tabel_individueel();
		?>
		<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
		<?php
	}

	/**
	 * De reserveringen van de gebruiker zelf.
	 *
	 * @return void
	 */
	protected function reserveringen() : void {
		?>
		<h2>Overzicht werkplek reserveringen</h2>
		<?php
		$this->tabel_individueel();
	}

	/**
	 * Render het rapport
	 */
	protected function overzicht() {
		if ( ! isset( $this->data['rapport'] ) ) {
			?>
		<form method="GET" action="<?php echo esc_attr( get_permalink() ?: '#' ); ?>">
			<?php $this->datums(); ?>
			<div class="kleistad-row" style="padding-top:20px;" >
				<button class="kleistad-button" type="submit" >Rapport</button>
			</div>
		</form>
			<?php
			return;
		}
		?>
		<h2>Overzicht werkplekgebruik vanaf <?php echo esc_html( date( 'd-m-Y', $this->data['vanaf_datum'] ) ); ?> tot <?php echo esc_html( date( 'd-m-Y', $this->data['tot_datum'] ) ); ?></h2>
		<table class="kleistad-datatable display compact" data-order= '[[ 0, "desc" ]]' >
			<thead>
				<tr>
					<th>Datum</th>
					<th>Dagdeel</th>
					<th>Activiteit</th>
					<th>Naam</th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $this->data['rapport'] as $datum => $regel ) :
				foreach ( $regel as $dagdeel => $activiteiten ) :
					foreach ( $activiteiten as $activiteit => $gebruiker_ids ) :
						foreach ( $gebruiker_ids as $gebruiker_id ) :
							if ( intval( $gebruiker_id ) ) :
								?>
				<tr>
					<td data-sort="<?php echo esc_attr( $datum ); ?>" ><?php echo esc_html( date( 'd-m-Y', $datum ) ); ?></td>
					<td><?php echo esc_html( $dagdeel ); ?></td>
					<td><?php echo esc_html( $activiteit ); ?></td>
					<td><?php echo esc_html( get_user_by( 'id', $gebruiker_id )->display_name ); ?></td>
				</tr>
								<?php
							endif;
						endforeach;
					endforeach;
				endforeach;
			endforeach
			?>
			</tbody>
		</table>
		<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
		<?php
	}

	/**
	 * Render het datum selectie
	 *
	 * @return Public_Werkplekrapport_Display
	 */
	private function datums() : Public_Werkplekrapport_Display {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label class="kleistad-label" for="kleistad_vanaf_datum" >Vanaf</label>
			</div>
			<div class="kleistad-col-3">
				<input type="text" name="vanaf_datum" id="kleistad_vanaf_datum" class="kleistad-datum" value="<?php echo esc_attr( date( 'd-m-Y', strtotime( '-2 week' ) ) ); ?>"  readonly="readonly" />
			</div>
		</div>
		<div class="kleistad-row" >
			<div class="kleistad-col-3">
				<label class="kleistad-label" for="kleistad_tot_datum" >Tot</label>
			</div>
			<div class="kleistad-col-3">
				<input type="text" name="tot_datum" id="kleistad_tot_datum" class="kleistad-datum" value="<?php echo esc_attr( date( 'd-m-Y' ) ); ?>"  readonly="readonly" />
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de gebruiker selectie
	 */
	private function werkplekgebruiker() {
		?>
		<div class="kleistad-row" >
			<div class="kleistad-col-3">
				<label class="kleistad-label" for="kleistad_gebruiker" >Gebruiker</label>
			</div>
			<div class="kleistad-col-3">
				<select class="kleistad-select" name="gebruiker_id" id="kleistad_gebruiker">
				<?php foreach ( $this->data['gebruikers'] as $gebruiker ) : ?>
					<option value="<?php echo esc_attr( $gebruiker['ID'] ); ?>" ><?php echo esc_html( $gebruiker['display_name'] ); ?></option>
				<?php endforeach ?>
				</select>
			</div>
		</div>
		<?php
	}

	/**
	 * Maak de tabel voor het individu.
	 *
	 * @return void
	 */
	private function tabel_individueel() : void {
		?>
		<table class="kleistad-datatable display compact" data-order= '[[ 0, "asc" ]]' data-searching="false" >
			<thead>
			<tr>
				<th>Datum</th>
				<th>Dagdeel</th>
				<th>Activiteit</th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $this->data['rapport'] as $datum => $regel ) :
				foreach ( $regel as $dagdeel => $activiteit ) :
					?>
					<tr>
						<td data-sort="<?php echo esc_attr( $datum ); ?>" ><?php echo esc_html( date( 'd-m-Y', $datum ) ); ?></td>
						<td><?php echo esc_html( $dagdeel ); ?></td>
						<td><?php echo esc_html( $activiteit ); ?></td>
					</tr>
					<?php
				endforeach;
			endforeach
			?>
			</tbody>
		</table>
		<?php
	}

}
