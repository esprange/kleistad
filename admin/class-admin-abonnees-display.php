<?php
/**
 * De class voor de rendering van qbonnee admin-specifieke functies van de plugin.
 *
 * @link https://www.kleistad.nl
 *
 * @package Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * Admin display class
 */
class Admin_Abonnees_Display extends Admin_Display {

	/**
	 * Toon de metabox
	 *
	 * @param array $item  Het weer te geven object in de meta box.
	 * @param array $metabox De metabox argumenten.
	 * @return void
	 */
	public function form_meta_box( array $item, array $metabox ) : void {
		$actie = $metabox['args']['actie'];
		?>
		<table class="form-table">
		<tbody>
			<tr class="form-field">
				<th  scope="row"><label>Naam</label></th>
				<td>
					<?php echo esc_html( $item['naam'] ); ?> (<?php echo esc_html( $item['code'] ); ?>)
					<input type="hidden" name="naam" value="<?php echo esc_attr( $item['naam'] ); ?>" >
					<input type="hidden" name="code" value="<?php echo esc_attr( $item['code'] ); ?>" >
					<input type="hidden" name="gepauzeerd" value="<?php echo esc_attr( $item['gepauzeerd'] ); ?>" >
					<input type="hidden" name="geannuleerd" value="<?php echo esc_attr( $item['geannuleerd'] ); ?>" >
					<input type="hidden" name="actie" value="<?php echo esc_attr( $actie ); ?>" >
				</td>
			</tr>
		<?php
		if ( 'status' === $actie ) {
			$this->render_status( $item );
		} elseif ( 'mollie' === $actie ) {
			$this->render_mollie( $item );
		} elseif ( 'historie' === $actie ) {
			$this->render_historie( $item );
		}
		?>
		</tbody>
		</table>
		<?php
	}

	/**
	 * Toon de pagina
	 *
	 * @return void
	 */
	public function page() : void {
		$table = new Admin_Abonnees();
		?>
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<h2>Abonnees</h2>
			<form id="abonnees-table" method="GET">
				<input type="hidden" name="page" value="<?php echo filter_input( INPUT_GET, 'page' ); ?>"/>
				<?php
					$table->prepare_items();
					$table->search_box( 'zoek abonnee', 'search' );
					$table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Toon de status van de abonnee
	 *
	 * @param array $item De abonnee.
	 * @return void
	 */
	private function render_status( array $item ) : void {
		?>
			<tr class="form-field">
				<th scope="row"><label for="kleistad_soort">Soort</label></th>
				<td>
					<select id="kleistad_soort" name="soort" required class="code">
						<option value="">Selecteer een abonnement soort</option>
						<option value="onbeperkt" <?php selected( $item['soort'], 'onbeperkt' ); ?> >Onbeperkt</option>
						<option value="beperkt" <?php selected( $item['soort'], 'beperkt' ); ?> >Beperkt</option>
					</select>
				</td>
			</tr>
				<?php
				$index = 0;
				foreach ( opties()['extra'] as $extra ) :
					$index++;
					if ( 0 < $extra['prijs'] ) :
						?>
			<tr class="form-field">
				<th scope="row"><label for="extra_<?php echo esc_attr( $index ); ?>"><?php echo esc_html( $extra['naam'] ); ?></label></th>
				<td>
					<input type="checkbox" id="extra_<?php echo esc_attr( $index ); ?>" name="extras[]" class="code" <?php checked( in_array( $extra['naam'], $item['extras'], true ) ); ?>
						value="<?php echo esc_attr( $extra['naam'] ); ?>" >
				</td>
			</tr>
						<?php
					endif;
				endforeach;
				?>
			<tr>
				<th scope="row"><label for="kleistad_inschrijf_datum">Inschrijving per</label></th>
				<td>
					<input type="text" id="kleistad_inschrijf_datum" name="inschrijf_datum" required class="kleistad-datum" value="<?php echo esc_attr( $item['inschrijf_datum'] ); ?>" readonly >
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kleistad_start_datum">Startperiode</label></th>
				<td>
					<input type="text" id="kleistad_start_datum" name="start_datum" required class="kleistad-datum" value="<?php echo esc_attr( $item['start_datum'] ); ?>" autocomplete="off"
						<?php wp_readonly( $item['geannuleerd'] ); ?> >
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kleistad_start_eind_datum">Einde startperiode</label></th>
				<td>
					<input type="text" id="kleistad_start_eind_datum" name="start_eind_datum" required class="kleistad-datum" value="<?php echo esc_attr( $item['start_eind_datum'] ); ?>" autocomplete="off"
						<?php wp_readonly( $item['geannuleerd'] ); ?> >
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kleistad_pauze_datum">Pauze per</label></th>
				<td>
					<input type="text" id="kleistad_pauze_datum" name="pauze_datum" class="kleistad-datum" value="<?php echo esc_attr( $item['pauze_datum'] ); ?>" autocomplete="off"
						<?php wp_readonly( $item['geannuleerd'] ); ?> >
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kleistad_herstart_datum">Herstart per</label></th>
				<td>
					<input type="text" id="kleistad_herstart_datum" name="herstart_datum" class="kleistad-datum" value="<?php echo esc_attr( $item['herstart_datum'] ); ?>" autocomplete="off"
						<?php wp_readonly( $item['geannuleerd'] ); ?> >
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kleistad_eind_datum">Beëindiging per</label></th>
				<td>
					<input type="text" id="kleistad_eind_datum" name="eind_datum" class="kleistad-datum" value="<?php echo esc_attr( $item['eind_datum'] ); ?>" autocomplete="off"
						<?php wp_readonly( $item['geannuleerd'] ); ?> >
				</td>
			</tr>
		<?php
	}

	/**
	 * Toon de mollie info
	 *
	 * @param array $item De abonnee.
	 * @return void
	 */
	private function render_mollie( array $item ) {
		?>
			<tr class="form-field">
				<td>
					<?php submit_button( 'Verwijder mandaat', 'primary', 'submit', true, [ 'id' => 'mollie' ] ); ?>
				</td>
			</tr>
			<tr>
				<td colspan="2">Let op: bij verwijderen mandaat wordt een eventuele automatische incasso gestopt!</td>
			</tr>
			<tr>
				<td colspan="2">
					<?php echo esc_html( $item['mollie_info'] ?? '' ); ?>
				</td>
			</tr>
		<?php
	}

	/**
	 * Toon de historie van het abonnement
	 *
	 * @param array $item De abonnee.
	 * @return void
	 */
	private function render_historie( array $item ) : void {
		?>
			<tr>
				<td colspan="2">
				<ul style="list-style-type:square">
				<?php foreach ( $item['historie'] as $historie ) : ?>
					<li><?php echo esc_html( $historie ); ?></li>
				<?php endforeach ?>
				</ul>
				</td>
			</tr>
		<?php
	}

}
