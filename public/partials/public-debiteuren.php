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

namespace Kleistad;

if ( 'blokkade' === $data['actie'] ) :
	$blok = strtotime( 'today' ) > $data['nieuwe_blokkade'];
	?>
	<?php $this->form(); ?>
	<div class="kleistad_row">
		<p>Alle orders voorafgaand <?php echo esc_html( date( 'd-m-Y', $data['huidige_blokkade'] ) ); ?> zijn nu niet meer te wijzigen.
		Dat betekent dat er geen correcties of kortingen op deze orders kunnen worden gedaan omdat dit dan invloed heeft op bijvoorbeeld
		de BTW aangifte (de factuur wordt gewijzigd) of op de jaarrekening. Een order kan natuurlijk wel nog geannuleerd worden.</p>
		<p>Omdat Kleistad per kwartaal de BTW aangifte doet, is de eerstvolgende blokkade datum <strong style="white-space:nowrap;" ><?php echo esc_html( date( 'd-m-Y', $data['nieuwe_blokkade'] ) ); ?></strong>.</p>
		<?php if ( $blok ) : ?>
			<p>Druk op 'doorvoeren' als je de huidige blokkade datum wilt wijzigen.</p>
		<?php else : ?>
			<p>Omdat deze datum nog in de toekomst ligt is het nu niet mogelijk om de blokkade datum te wijzigen.</p>
		<?php endif ?>
	</div>
	<div class="kleistad_row" style="padding-top:20px;">
		<div class="kleistad_col_3">
			<button name="kleistad_submit_debiteuren" type="submit" value="blokkade"
				<?php disabled( ! $blok ); ?> data-confirm="Debiteuren|Weet je zeker dat je de blokkade datum wilt wijzigen naar <?php echo esc_attr( date( 'd-m-Y', $data['nieuwe_blokkade'] ) ); ?> ?" >Bevestigen</button>
		</div>
	</div>
	</form>
	<?php
elseif ( 'debiteur' === $data['actie'] ) :
	?>
	<p><?php echo esc_html( ucfirst( $data['debiteur']['betreft'] ) . ', ' . ( ! $data['debiteur']['gesloten'] ? 'openstaand voor ' : 'besteld door ' ) . $data['debiteur']['naam'] ); ?></p>
	<table class="kleistad_form">
		<tr><th>referentie</th><td><?php echo esc_html( $data['debiteur']['referentie'] . ' geboekt op ' . date( 'd-m-Y', $data['debiteur']['sinds'] ) ); ?></td><th>historie</th></tr>
		<tr><th>factuur</th><td>
			<?php foreach ( Factuur::facturen( $data['debiteur']['factuur'] ) as $factuur_url ) : ?>
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
		<tr><th colspan="2"><?php echo $data['debiteur']['terugstorting'] ? 'Een stornering is ingediend' : ''; ?></th></tr>
	</table>
	<?php $this->form(); ?>
	<input type="hidden" name="id" value="<?php echo esc_attr( $data['debiteur']['id'] ); ?>"/>

	<?php if ( ! ( $data['debiteur']['gesloten'] || $data['debiteur']['terugstorting'] ) ) : ?>
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
			<?php if ( 0 < $data['debiteur']['openstaand'] ) : ?>
				<div class="kleistad_col_4 kleistad_label">
					<label for="kleistad_ontvangst">Ontvangen bedrag</label>
				</div>
				<div class="kleistad_col_3" >
					<input type="number" step="0.01" id="kleistad_ontvangst" name="ontvangst" min="0.00" max="<?php echo esc_attr( $data['debiteur']['openstaand'] ); ?>" value="<?php echo esc_attr( $data['debiteur']['ontvangst'] ); ?>">
				</div>
			<?php else : // Als een credit stand. ?>
				<div class="kleistad_col_4 kleistad_label">
					<label for="kleistad_terugstorting">Teruggestort bedrag</label>
				</div>
				<div class="kleistad_col_3" >
					<input type="number" step="0.01" id="kleistad_terugstorting" name="terugstorting" min="0.00" max="<?php echo esc_attr( - $data['debiteur']['openstaand'] ); ?>" value="<?php echo esc_attr( $data['debiteur']['ontvangst'] ); ?>">
				</div>
			<?php endif ?>
		</div>
	</div>
	<?php endif // Als nog niet gesloten. ?>

	<?php if ( ! $data['debiteur']['credit'] && $data['debiteur']['annuleerbaar'] ) : ?>
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
				<textarea class="kleistad_input" name="opmerking_annulering" id="kleistad_opmerking_annulering" maxlength="500" rows="5" cols="50"></textarea>
			</div>
		</div>
	</div>
	<?php endif // Debet stand. ?>

	<?php if ( $data['debiteur']['afboekbaar'] ) : ?>
	<div class="kleistad_row">
		<div class="kleistad_col_6">
			<input type="radio" name="debiteur_actie" id="kleistad_deb_afboeken" class="kleistad_input_cbr" value="afboeken" >
			<label class="kleistad_label_cbr" for="kleistad_deb_afboeken">Afboeken (dubieuze debiteur)</label>
		</div>
	</div>
	<?php endif // Dubieuze debiteur. ?>

	<?php if ( ! ( $data['debiteur']['geblokkeerd'] || $data['debiteur']['credit'] ) ) : ?>
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
				<textarea class="kleistad_input" name="opmerking_korting" id="kleistad_opmerking_korting" maxlength="500" rows="5" cols="50"></textarea>
			</div>
		</div>
	</div>
	<?php endif // Als factuur nog niet geblokkeerd. ?>

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
	<span style="font-size:75%" >facturen aangemaakt voor <?php echo esc_html( date( 'd-m-Y', get_blokkade() ) ); ?> zijn niet meer te wijzigen</span>
</form>
	<?php
	else : // Als niet 'debiteur'.
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
<?php endif // Als zoek. ?>
<p><strong>Totaal openstaand:</strong> &euro; <?php echo esc_html( number_format_i18n( $data['openstaand'], 2 ) ); ?></p>
<table class="kleistad_datatable display compact nowrap" data-page-length="10" data-order='[[ 3, "desc" ], [ 5, "asc" ]]' >
	<thead>
		<tr>
			<th>Code</th>
			<th>Naam</th>
			<th>Betreft</th>
			<th>Openstaand</th>
			<th>Sinds</th>
			<th>Vervaldatum</th>
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
			<tr style="<?php echo $debiteur['verval_datum'] <= strtotime( 'today' ) && ! $debiteur['gesloten'] ? 'color:#b30000' : ''; ?>" >
				<td><?php echo esc_html( $debiteur['referentie'] . ( $debiteur['credit'] ? '(C)' : '' ) ); ?></td>
				<td><?php echo esc_html( $debiteur['naam'] ); ?></td>
				<td><?php echo esc_html( $debiteur['betreft'] ); ?></td>
				<td style="text-align:right;" data-sort="<?php echo esc_attr( $debiteur['openstaand'] ); ?>">&euro; <?php echo esc_html( number_format_i18n( $debiteur['openstaand'], 2 ) ); ?></td>
				<td data-sort="<?php echo esc_attr( $debiteur['sinds'] ); ?>"><?php echo esc_html( $datum->format( 'd-m-Y H:i' ) ); ?></td>
				<td data-sort="<?php echo esc_attr( $debiteur['verval_datum'] ); ?>"><?php echo esc_html( date( 'd-m-Y', $debiteur['verval_datum'] ) ); ?></td>
				<td>
					<a href="#" title="wijzig order" class="<?php echo 'kleistad_edit'; ?> kleistad_edit_link" style="text-decoration:none !important;color:green;padding:.4em .8em;"
						data-id="<?php echo esc_attr( $debiteur['id'] ); ?>" data-actie="<?php echo 'debiteur'; ?>" >
						&nbsp;
					</a>
				</td>
			</tr>
	<?php endforeach ?>
	</tbody>
</table>
<?php endif ?>
