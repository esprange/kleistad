<?php
/**
 * Toon het debiteuren overzicht en formulier.
 *
 * @link https://www.kleistad.nl
 * @since 6.1.0
 *
 * @package Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( 'debiteur' === $data['actie'] ) :
	?>
	<p><?php echo esc_html( ucfirst( $data['debiteur']['betreft'] ) ); ?>, openstaand voor <?php echo esc_html( $data['debiteur']['naam'] ); ?></p>
	<table class="kleistad_form">
		<tr><th>referentie</th><td><?php echo esc_html( $data['debiteur']['referentie'] . ' geboekt op ' . date( 'd-m-Y', $data['debiteur']['sinds'] ) ); ?></td><th>historie</th></tr>
		<tr><th>factuur</th><td>
			<?php foreach ( \Kleistad\Factuur::facturen( $data['debiteur']['factuur'] ) as $factuur_url ) : ?>
				<a href="<?php echo esc_url( $factuur_url ); ?>" target="_blank"><?php echo esc_html( basename( $factuur_url ) ); ?></a><br/>
			<?php endforeach ?>
			</td><td rowspan="3">
			<?php foreach ( $data['debiteur']['historie'] as $historie ) : ?>
				<?php echo esc_html( $historie ); ?><br/>
			<?php endforeach ?>
			</td>
		</tr>
		<tr><th>betaald</th><td>&euro; <?php echo esc_html( number_format_i18n( $data['debiteur']['betaald'], 2 ) ); ?></td></tr>
		<tr><th>openstaand</th><td>&euro; <?php echo esc_html( number_format_i18n( $data['debiteur']['openstaand'], 2 ) ); ?></td></tr>
		<tr>
		</tr>
	</table>
	<?php
	if ( $data['bewerken'] ) :
		$this->form();
		?>
	<input type="hidden" name="id" value="<?php echo esc_attr( $data['debiteur']['id'] ); ?>"/>
	<div class="kleistad_row">
		<div class="kleistad_col_6">
				<input type="radio" name="debiteur_actie" id="kleistad_deb_bankbetaling" class="kleistad_input_cbr" value="bankbetaling" >
				<label class="kleistad_label_cbr" for="kleistad_deb_bankbetaling">Bankbetaling invoeren</label>
			</div>
		</div>
		<div class="kleistad_deb_bankbetaling kleistad_deb_veld" style="display:none" >
			<div class="kleistad_row">
				<div class="kleistad_col_3" >
					&nbsp;
				</div>
				<div class="kleistad_col_4 kleistad_label">
					<label for="kleistad_ontvangst"><?php echo esc_html( $data['debiteur']['credit'] ? 'Teruggestort bedrag' : 'Ontvangen bedrag' ); ?></label>
				</div>
				<div class="kleistad_col_3" >
				<input type="number" step="0.01" id="kleistad_ontvangst" name="ontvangst" value="<?php echo esc_attr( $data['debiteur']['ontvangst'] ); ?>">
				</div>
			</div>
		</div>
	</div>

	<div class="kleistad_row">
		<div class="kleistad_col_6">
				<input type="radio" name="debiteur_actie" id="kleistad_deb_annulering" class="kleistad_input_cbr" value="annulering" >
				<label class="kleistad_label_cbr" for="kleistad_deb_annulering">Annuleren</label>
			</div>
		</div>
		<div class="kleistad_deb_annulering kleistad_deb_veld" style="display:none" >
			<div class="kleistad_row">
				<div class="kleistad_col_3" >
					&nbsp;
				</div>
				<div class="kleistad_col_4 kleistad_label">
					<label for="kleistad_restant">Restant te betalen</label>
				</div>
				<div class="kleistad_col_3" >
					<input type="number" step="0.01" id="kleistad_restant" name="restant" min="0" value="<?php echo esc_attr( $data['debiteur']['restant'] ); ?>">
				</div>
			</div>
			<div class="kleistad_row">
				<div class="kleistad_col_3 kleistad_label">
					<label for="kleistad_opmerking_annulering">Opmerking</label>
				</div>
				<div class="kleistad_col_7" >
					<textarea class="kleistad_input" name="opmerking_annulering" id="kleistad_opmerking_annulering" rows="5" cols="50"></textarea>
				</div>
			</div>
		</div>
	</div>

	<div class="kleistad_row">
		<div class="kleistad_col_6">
				<input type="radio" name="debiteur_actie" id="kleistad_deb_korting" class="kleistad_input_cbr" value="korting" >
				<label class="kleistad_label_cbr" for="kleistad_deb_korting">Korting verstrekken</label>
			</div>
		</div>
		<div class="kleistad_deb_korting kleistad_deb_veld" style="display:none" >
			<div class="kleistad_row">
				<div class="kleistad_col_3" >
					&nbsp;
				</div>
				<div class="kleistad_col_4 kleistad_label">
					<label for="kleistad_korting">Korting</label>
				</div>
				<div class="kleistad_col_3" >
					<input type="number" step="0.01" id="kleistad_korting" name="korting" min="0" value="<?php echo esc_attr( $data['debiteur']['korting'] ); ?>">
				</div>
			</div>
			<div class="kleistad_row">
				<div class="kleistad_col_3 kleistad_label">
					<label for="kleistad_opmerking_korting">Opmerking</label>
				</div>
				<div class="kleistad_col_7" >
					<textarea class="kleistad_input" name="opmerking_korting" id="kleistad_opmerking_korting" rows="5" cols="50"></textarea>
				</div>
			</div>
		</div>
	</div>
	<div class="kleistad_row" style="padding-top:20px;">
		<div class="kleistad_col_3">
			<button name="kleistad_submit_debiteuren" type="submit" id="kleistad_submit_debiteuren" disabled >Bevestigen</button>
		</div>
		<div class="kleistad_col_4">
		</div>
		<div class="kleistad_col_3">
			<button type="button" style="position:absolute;right:0px;" class="kleistad_terug_link">Terug</button>
		</div>
	</div>
</form>
	<?php else : ?>
	<div class="kleistad_row" style="padding-top:20px;">
		<div class="kleistad_col_7">
		</div>
		<div class="kleistad_col_3">
			<button type="button" style="position:absolute;right:0px;" class="kleistad_terug_link">Terug</button>
		</div>
	</div>
	<?php endif ?>
	<?php
	elseif ( false !== strpos( 'openstaand zoek', $data['actie'] ) ) :
		if ( 'zoek' === $data['actie'] ) :
			?>
<div class="kleistad_row">
	<div class="kleistad_col_2">
		<label for="kleistad_zoek">Zoek naar</label>
	</div>
	<div class="kleistad_col_3">
		<input id="kleistad_zoek" name="zoek" type="text" />
	</div>
	<div class="kleistad_col3">
		<button type="button" id="kleistad_zoek_knop" class="kleistad_edit_link" data-id="" data-action="zoek">Zoek</button>
	</div>
</div>
<br/><hr><br/>
<?php endif ?>
<table class="kleistad_datatable display compact nowrap" data-page-length="10" data-order='[[ 0, "desc" ]]' >
	<thead>
		<tr>
			<th>Code</th>
			<th>Naam</th>
			<th>Betreft</th>
			<th>Openstaand</th>
			<th>Sinds</th>
			<th data-orderable="false"></th>
		</tr>
	</thead>
	<tbody>
		<?php
			$datum = new \Datetime();
			$datum->setTimezone( new \DateTimeZone( get_option( 'timezone_string' ) ?: 'Europe/Amsterdam' ) );

		foreach ( $data['debiteuren'] as $debiteur ) :
			$datum->setTimestamp( $debiteur['sinds'] );
			?>
			<tr>
				<td><?php echo esc_html( $debiteur['referentie'] . ( $debiteur['credit'] ? '(C)' : '' ) ); ?></td>
				<td><?php echo esc_html( $debiteur['naam'] ); ?></td>
				<td><?php echo esc_html( $debiteur['betreft'] ); ?></td>
				<td style="text-align:right;" data-sort="<?php echo esc_attr( $debiteur['openstaand'] ); ?>">&euro; <?php echo esc_html( number_format_i18n( $debiteur['openstaand'], 2 ) ); ?></td>
				<td data-sort="<?php echo esc_attr( $debiteur['sinds'] ); ?>"><?php echo esc_html( $datum->format( 'd-m-Y H:i' ) ); ?></td>
				<td>
					<a href="#" title="wijzig order" class=" <?php echo 'zoek' === $data['actie'] ? 'kleistad_view' : 'kleistad_edit'; ?> kleistad_edit_link" style="text-decoration:none !important;color:green;padding:.4em .8em;"
						data-id="<?php echo esc_attr( $debiteur['id'] ); ?>" data-actie="<?php echo 'zoek' === $data['actie'] ? 'toon_debiteur' : 'debiteur'; ?>" >
						&nbsp;
					</a>
				</td>
			</tr>
	<?php endforeach ?>
	</tbody>
</table>
<?php endif ?>
