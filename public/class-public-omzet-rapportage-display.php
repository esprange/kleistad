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
	 * Het geselecteerde jaar
	 *
	 * @var int $select_jaar Het jaar.
	 */
	private int $select_jaar;

	/**
	 * De geselecteerde maand
	 *
	 * @var int $select_maand De maand.
	 */
	private int $select_maand;

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function html() {
		$this->select_maand = $this->data['maand'];
		$this->select_jaar  = $this->data['jaar'];
		if ( 'details' === $this->data['actie'] ) {
			$this->details();
			return;
		}
		$this->overzicht();
	}

	/**
	 * Render de details
	 *
	 * @return Public_Omzet_Rapportage_Display
	 */
	private function details() {
		?>
		<input type="hidden" name="maand" value="<?php echo esc_attr( $this->select_maand ); ?>">
		<input type="hidden" name="jaar" value="<?php echo esc_attr( $this->select_jaar ); ?>">
		<p>Omzet in
			<?php echo esc_html( $this->select_maand ? strftime( '%B %Y', mktime( 0, 0, 0, $this->select_maand, 1, $this->select_jaar ) ) : $this->select_jaar ); ?>
			voor <?php echo esc_html( $this->data['artikel'] ); ?></p>.

		<table class="kleistad-datatable display compact nowrap" >
			<thead>
				<tr>
					<th>Code</th>
					<th>Klant</th>
					<th>Datum</th>
					<th>Bedrag</th>
					<th>BTW</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $this->data['omzetdetails'] as $detail ) : ?>
				<tr>
					<td><?php echo esc_html( $detail['code'] ); ?></td>
					<td><?php echo esc_html( $detail['klant'] ); ?></td>
					<td data-sort="<?php echo esc_attr( $detail['datum'] ); ?>" ><?php echo esc_html( strftime( '%d-%m-%Y', $detail['datum'] ) ); ?></td>
					<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $detail['netto'], 2 ) ); ?></td>
					<td style="text-align:right">&euro; <?php echo esc_html( number_format_i18n( $detail['btw'], 2 ) ); ?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
		<?php
		return $this;
	}

	/**
	 * Render de details
	 *
	 * @return Public_Omzet_Rapportage_Display
	 */
	private function overzicht() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label class="kleistad-label" for="kleistad_maand" >Maand</label>
			</div>
			<div class="kleistad-col-3">
				<select name="maand" id="kleistad_maand" >
					<option value="0" <?php selected( 0, $this->select_maand ); ?>>-</option>
					<option value="1" <?php selected( 1, $this->select_maand ); ?>>januari</option>
					<option value="2" <?php selected( 2, $this->select_maand ); ?>>februari</option>
					<option value="3" <?php selected( 3, $this->select_maand ); ?>>maart</option>
					<option value="4" <?php selected( 4, $this->select_maand ); ?>>april</option>
					<option value="5" <?php selected( 5, $this->select_maand ); ?>>mei</option>
					<option value="6" <?php selected( 6, $this->select_maand ); ?>>juni</option>
					<option value="7" <?php selected( 7, $this->select_maand ); ?>>juli</option>
					<option value="8" <?php selected( 8, $this->select_maand ); ?>>augustus</option>
					<option value="9" <?php selected( 9, $this->select_maand ); ?>>september</option>
					<option value="10" <?php selected( 10, $this->select_maand ); ?>>oktober</option>
					<option value="11" <?php selected( 11, $this->select_maand ); ?>>november</option>
					<option value="12" <?php selected( 12, $this->select_maand ); ?>>december</option>
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
						<option value="<?php echo esc_attr( $jaar ); ?>" <?php selected( $jaar, $this->select_jaar ); ?> ><?php echo esc_html( $jaar++ ); ?></option>
						<?php endwhile ?>
				</select>
			</div>
		</div>
		<button class="kleistad-button kleistad-edit-link" type="button" id="kleistad_rapport" style="display:none" data-id="<?php echo esc_attr( "$this->select_jaar-$this->select_maand" ); ?>" data-actie="rapport" >Toon omzet</button>
		<br/><br/>
		<div>
			<table class="kleistad-datatable display compact nowrap" data-paging="false" data-searching="false" data-ordering="false" data-info="false">
				<thead>
					<tr>
						<th>Omzet</th>
						<th>Bedrag</th>
						<th>BTW</th>
						<th data-orderable="false"></th>
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
					<td>
						<?php
						if ( $omzet['details'] ) :
							?>
							<a href="#" title="details" class="kleistad-view kleistad-edit-link" data-id="<?php echo esc_attr( $this->select_jaar . '-' . $this->select_maand . '-' . $omzet['key'] ); ?>" data-actie="details" >
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
					<th>&nbsp;</th>
				</tr>
			</tfoot>
			</table>
			<button id="kleistad_downloadrapport" class="kleistad-button kleistad-download-link" type="button" data-actie="omzetrapport" >Omzet rapport</button>
		</div>
		<?php
		return $this;
	}

}
