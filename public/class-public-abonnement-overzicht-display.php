<?php
/**
 * Toon het abonnement overzicht formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de abonnement overzicht.
 */
class Public_Abonnement_Overzicht_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() : void {
		?>
		<table class="kleistad-datatable display compact nowrap" id="kleistad_abonnementen" data-order='[[ 0, "asc" ]]'>
			<thead>
				<tr>
					<th>Code</th>
					<th>Naam</th>
					<th>E-mail</th>
					<th>Soort</th>
					<th>Extras</th>
					<th>Status</th>
					<th>Mandaat</th>
					<th data-orderable="false" data-priority="1"></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $this->data['abonnee_info'] as $abonnee_info ) : ?>
				<tr>
					<td><?php echo esc_html( $abonnee_info['code'] ); ?></td>
					<td><?php echo esc_html( $abonnee_info['naam'] ); ?></td>
					<td><?php echo esc_html( $abonnee_info['email'] ); ?></td>
					<td><?php echo esc_html( $abonnee_info['soort'] ); ?></td>
					<td><?php echo $abonnee_info['extras']; // phpcs:ignore ?></td>
					<td><?php echo esc_html( $abonnee_info['status'] ); ?></td>
					<td><?php if ( $abonnee_info['mandaat'] ) : ?>
						<span class="dashicons dashicons-yes"></span>
						<?php endif; ?>
					</td>
					<td>
						<a href="#" title="wijzig abonnement" class="kleistad-edit kleistad-edit-link" style="padding:.4em .8em;"
							data-id="<?php echo esc_attr( $abonnee_info['id'] ); ?>" data-actie="wijzigen" >
							&nbsp;
						</a>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<button class="kleistad-button kleistad-download-link" type="button" data-actie="abonnementen" >Download</button>
		<?php
	}

	/**
	 * Render de abonnement details
	 *
	 * @return void
	 */
	protected function wijzigen(): void {
		$this->form(
			function() {
				$readonly = $this->data['abonnee']->abonnement->is_geannuleerd();
				?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $this->data['abonnee']->ID ); ?>" ?>
			<div class="kleistad-row kleistad-label">
				<div class="kleistad-col-2">
					<?php echo esc_html( $this->data['abonnee']->abonnement->code ); ?>
				</div>
				<div class="kleistad-col-6">
					<?php echo esc_html( $this->data['abonnee']->display_name ); ?>
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-10" style="padding: 10px 10px;max-height: 10em;width: 100%;overflow-y: auto">
				<ul style="list-style-type:square">
					<?php foreach ( array_reverse( $this->data['abonnee']->abonnement->historie ?? [] )  as $historie ) : ?>
						<li><?php echo esc_html( $historie ); ?></li>
					<?php endforeach ?>
				</ul>
				</div>
			</div>
			<br/>
			<div class="kleistad-row">
				<div class="kleistad-col-2 kleistad-label">
					<label for="kleistad_soort">Soort</label>
				</div>
				<div class="kleistad-col-3">
					<select id="kleistad_soort" name="soort" <?php wp_readonly( $readonly ); ?> required >
						<option value="">Selecteer een abonnement soort</option>
						<option value="onbeperkt" <?php selected( $this->data['abonnee']->abonnement->soort, 'onbeperkt' ); ?> >Onbeperkt</option>
						<option value="beperkt" <?php selected( $this->data['abonnee']->abonnement->soort, 'beperkt' ); ?> >Beperkt</option>
					</select>
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-2 kleistad-label">
					<label for="kleistad_datum">Inschrijving per</label>
				</div>
				<div class="kleistad-col-3">
					<input type="text" id="kleistad_datum" name="datum"
						class="kleistad-datum" value="<?php echo esc_attr( $this->show_date( $this->data['abonnee']->abonnement->datum ) ); ?>" readonly >
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-2 kleistad-label">
					<label for="kleistad_start_datum">Start per</label>
				</div>
				<div class="kleistad-col-3">
					<input type="text" id="kleistad_start_datum" name="start_datum" required class="kleistad-datum"
						value="<?php echo esc_attr( $this->show_date( $this->data['abonnee']->abonnement->start_datum ) ); ?>" autocomplete="off"
						<?php wp_readonly( $readonly ); ?> >
				</div>
				<div class="kleistad-col-2 kleistad-label">
					<label for="kleistad_start_eind_datum">Einde start</label>
				</div>
				<div class="kleistad-col-3">
					<input type="text" id="kleistad_start_eind_datum" name="start_eind_datum" required class="kleistad-datum"
						value="<?php echo esc_attr( $this->show_date( $this->data['abonnee']->abonnement->start_eind_datum ) ); ?>" autocomplete="off"
						<?php wp_readonly( $readonly ); ?> >
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-2 kleistad-label">
					<label for="kleistad_pauze_datum">Pauze per</label>
				</div>
				<div class="kleistad-col-3">
					<input type="text" id="kleistad_pauze_datum" name="pauze_datum" class="kleistad-datum"
						value="<?php echo esc_attr( $this->show_date( $this->data['abonnee']->abonnement->pauze_datum ) ); ?>" autocomplete="off"
						<?php wp_readonly( $readonly ); ?> >
				</div>
				<div class="kleistad-col-2 kleistad-label">
					<label for="kleistad_herstart_datum">Herstart per</label>
				</div>
				<div class="kleistad-col-3">
					<input type="text" id="kleistad_herstart_datum" name="herstart_datum" class="kleistad-datum"
						value="<?php echo esc_attr( $this->show_date( $this->data['abonnee']->abonnement->herstart_datum ) ); ?>" autocomplete="off"
						<?php wp_readonly( $readonly ); ?> >
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-2 kleistad-label">
					<label for="kleistad_eind_datum">Beëindiging per</label>
				</div>
				<div class="kleistad-col-3">
					<input type="text" id="kleistad_eind_datum" name="eind_datum" class="kleistad-datum"
						value="<?php echo esc_attr( $this->show_date( $this->data['abonnee']->abonnement->eind_datum ) ); ?>" autocomplete="off"
						<?php wp_readonly( $readonly ); ?> >
				</div>
			</div>
				<?php
				$index = 0;
				foreach ( opties()['extra'] as $extra ) :
					$index++;
					if ( 0 < $extra['prijs'] ) :
						?>
				<div class="kleistad-row">
					<div class="kleistad-col-4 kleistad-label">
						<label for="extra_<?php echo esc_attr( $index ); ?>"><?php echo esc_html( $extra['naam'] ); ?></label>
					</div>
					<div class="kleistad-col-3">
						<input type="checkbox" class="kleistad-checkbox" id="extra_<?php echo esc_attr( $index ); ?>" name="extras[]"
							<?php checked( in_array( $extra['naam'], $this->data['abonnee']->abonnement->extras, true ) ); ?>
								value="<?php echo esc_attr( $extra['naam'] ); ?>" <?php wp_readonly( $readonly ); ?> >
					</div>
				</div>
						<?php
					endif;
				endforeach;
				?>
			<div class="kleistad-row">
				<div class="kleistad-col-5">
					<?php if ( ! $readonly ) : ?>
					<button class="kleistad-button" type="submit" id="kleistad_submit_abonnement_overzicht_bewaren" name="kleistad_submit_abonnement_overzicht" value="wijzigen" >Bewaren</button>
						<?php if ( $this->data['mandaat'] ) : ?>
					<button class="kleistad-button" type="submit" id="kleistad_submit_abonnement_overzicht_stop_mandaat" name="kleistad_submit_abonnement_overzicht" value="stop_mandaat" >Mandaat verwijderen</button>
							<?php
						endif;
					endif;
					?>
				</div>
				<div class="kleistad-col-5">
					<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
				</div>
			</div>
				<?php
			}
		);
	}

	/**
	 * Helper function, show date only when it is set.
	 *
	 * @param int|null $date The date.
	 *
	 * @return string
	 */
	private function show_date( ?int $date ) : string {
		return $date ? wp_date( 'd-m-Y', $date ) : '';
	}
}
