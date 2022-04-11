<?php
/**
 * Toon de omzetrapportage
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de omzetrapportage.
 */
class Public_Omzet_Rapportage_Display extends Public_Shortcode_Display {

	/**
	 * Render de details
	 */
	protected function details() {
		?>
		<input type="hidden" name="maand" value="<?php echo esc_attr( $this->data['maand'] ); ?>">
		<input type="hidden" name="jaar" value="<?php echo esc_attr( $this->data['jaar'] ); ?>">
		<p>Omzet in
			<?php echo esc_html( $this->data['maand'] ? strftime( '%B %Y', mktime( 0, 0, 0, $this->data['maand'], 1, $this->data['jaar'] ) ) : $this->data['jaar'] ); ?>
			voor <?php echo esc_html( $this->data['artikel'] ); ?></p>.

		<table class="kleistad-datatable display compact nowrap" >
			<thead>
				<tr>
					<th style="width:10%">Code</th>
					<th style="width:30%">Klant</th>
					<th style="width:15%">Datum</th>
					<th style="width:15%">Netto bedrag</th>
					<th style="width:15%">BTW</th>
					<th style="width:15%">Bruto bedrag</th>
				</tr>
			</thead>
			<tbody>
			<?php
			$totaal_netto = 0;
			$totaal_btw   = 0;
			foreach ( $this->data['omzetdetails'] as $detail ) :
					$totaal_netto += $detail['netto'];
					$totaal_btw   += $detail['btw'];
				?>
				<tr>
					<td><?php echo esc_html( $detail['code'] ); ?></td>
					<td><?php echo esc_html( $detail['klant'] ); ?></td>
					<td data-sort="<?php echo esc_attr( $detail['datum'] ); ?>" ><?php echo esc_html( strftime( '%d-%m-%Y', $detail['datum'] ) ); ?></td>
					<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $detail['netto'], 2 ) ); ?></td>
					<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $detail['btw'], 2 ) ); ?></td>
					<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $detail['netto'] + $detail['btw'], 2 ) ); ?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
			<tfoot>
			<tr>
				<th>Totaal</th>
				<th colspan="3" style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $totaal_netto, 2 ) ); ?></th>
				<th style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $totaal_btw, 2 ) ); ?></th>
				<th style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $totaal_netto + $totaal_btw, 2 ) ); ?></th>
			</tr>
			</tfoot>
		</table>
		<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
		<?php
	}

	/**
	 * Render de details
	 */
	protected function overzicht() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label class="kleistad-label" for="kleistad_maand" >Maand</label>
			</div>
			<div class="kleistad-col-3">
				<select name="maand" id="kleistad_maand" >
					<option value="0" <?php selected( 0, $this->data['maand'] ); ?>>-</option>
					<option value="1" <?php selected( 1, $this->data['maand'] ); ?>>januari</option>
					<option value="2" <?php selected( 2, $this->data['maand'] ); ?>>februari</option>
					<option value="3" <?php selected( 3, $this->data['maand'] ); ?>>maart</option>
					<option value="4" <?php selected( 4, $this->data['maand'] ); ?>>april</option>
					<option value="5" <?php selected( 5, $this->data['maand'] ); ?>>mei</option>
					<option value="6" <?php selected( 6, $this->data['maand'] ); ?>>juni</option>
					<option value="7" <?php selected( 7, $this->data['maand'] ); ?>>juli</option>
					<option value="8" <?php selected( 8, $this->data['maand'] ); ?>>augustus</option>
					<option value="9" <?php selected( 9, $this->data['maand'] ); ?>>september</option>
					<option value="10" <?php selected( 10, $this->data['maand'] ); ?>>oktober</option>
					<option value="11" <?php selected( 11, $this->data['maand'] ); ?>>november</option>
					<option value="12" <?php selected( 12, $this->data['maand'] ); ?>>december</option>
				</select>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label class="kleistad-label" for="kleistad_jaar" >Jaar</label>
			</div>
			<div class="kleistad-col-3">
				<select name="jaar" id="kleistad_jaar">
					<?php
						$huidig_jaar = (int) date( 'Y' );
						$jaar        = 2020;
					while ( $jaar <= $huidig_jaar ) :
						?>
						<option value="<?php echo esc_attr( $jaar ); ?>" <?php selected( $jaar, $this->data['jaar'] ); ?> ><?php echo esc_html( $jaar++ ); ?></option>
						<?php endwhile ?>
				</select>
			</div>
		</div>
		<button class="kleistad-button kleistad-edit-link" type="button" id="kleistad_rapport" style="display:none" data-id="<?php echo esc_attr( "{$this->data['jaar']}-{$this->data['maand']}" ); ?>" data-actie="overzicht" >Toon omzet</button>
		<br/><br/>
		<div>
			<table class="kleistad-datatable display compact nowrap" data-paging="false" data-searching="false" data-ordering="false" data-info="false">
				<thead>
					<tr>
						<th style="width:35%;">Omzet</th>
						<th style="width:20%;">Netto bedrag</th>
						<th style="width:20%;">BTW</th>
						<th style="width:20%;">Bruto bedrag</th>
						<th data-orderable="false" style="width:5%;"></th>
					</tr>
				</thead>
				<tbody>
			<?php
				$totaal_netto = 0;
				$totaal_btw   = 0;
			foreach ( $this->data['omzet'] as $naam => $omzet ) :
				$totaal_netto += $omzet['netto'];
				$totaal_btw   += $omzet['btw'];
				?>
				<tr>
					<td><?php echo esc_html( $naam ); ?></td>
					<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $omzet['netto'], 2 ) ); ?></td>
					<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $omzet['btw'], 2 ) ); ?></td>
					<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $omzet['netto'] + $omzet['btw'], 2 ) ); ?></td>
					<td>
						<?php
						if ( $omzet['details'] ) :
							?>
							<a href="#" title="details" class="kleistad-view kleistad-edit-link" data-id="<?php echo esc_attr( "{$this->data['jaar']}-{$this->data['maand']}-{$omzet['key']}" ); ?>" data-actie="details" >
							&nbsp;
						</a><?php endif ?>
					</td>
				</tr>
			<?php endforeach ?>
				</tbody>
				<tfoot>
				<tr>
					<th>Totaal</th>
					<th style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $totaal_netto, 2 ) ); ?></th>
					<th style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $totaal_btw, 2 ) ); ?></th>
					<th style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $totaal_netto + $totaal_btw, 2 ) ); ?></th>
					<th>&nbsp;</th>
				</tr>
				</tfoot>
			</table>
			<button id="kleistad_downloadrapport" class="kleistad-button kleistad-download-link" type="button" data-actie="omzetrapport" >Omzet rapport</button>
		</div>
		<?php
	}

}
