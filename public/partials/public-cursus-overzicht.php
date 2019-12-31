<?php
/**
 * Toon het cursus overzicht
 *
 * @link       https://www.kleistad.nl
 * @since      4.5.4
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( 'cursisten' === $data['actie'] ) :
	?>
	<?php $this->form(); ?>
	<strong><?php echo esc_html( $data['cursus']['code'] . ' ' . $data['cursus']['naam'] ); ?></strong>
	<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $data['cursus']['id'] ); ?>">
	<table class="kleistad_datatable display" data-paging="false" data-searching="false">
		<thead>
		<tr>
			<th>Naam</th>
			<th>Technieken</th>
			<th>Betaald</th>
			<?php if ( $data['bestuur_rechten'] ) : ?>
			<th>Herinner Email</th>
			<th>Nog niet ingedeeld</th>
			<?php endif ?>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $data['cursisten'] as $cursist ) : ?>
			<tr>
				<td><?php echo esc_html( $cursist['naam'] ); ?></td>
				<td><?php echo esc_html( $cursist['technieken'] ); ?></td>
				<td><?php echo ( ( $cursist['c_betaald'] ) ? '<span class="dashicons dashicons-yes"></span>' : '' ); ?></td>
				<?php if ( $data['bestuur_rechten'] ) : ?>
				<td><?php echo ( ( $cursist['herinner_email'] ) ? '<span class="dashicons dashicons-yes"></span>' : '' ); ?></td>
				<td>
					<?php
					if ( ! $cursist['i_betaald'] && ! $cursist['c_betaald'] ) :
						if ( $data['cursus']['loopt'] && $cursist['wacht'] ) :
							?>
						<a href="#" title="indelen" class="kleistad_edit_link" style="text-decoration:none !important;color:green;padding:.4em .8em;"
							data-id="<?php echo esc_attr( $cursist['id'] . '-' . $data['cursus']['id'] ); ?>" data-actie="indelen" >wacht op factuur</a>
						<?php else : ?>
						nog niet betaald !
							<?php
						endif;
					endif
					?>
				</td>
				<?php endif ?>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
	<br/>
	<?php if ( $data['bestuur_rechten'] ) : ?>
	<button type="button" class="kleistad_download_link" name="kleistad_submit_cursus_overzicht" data-actie="cursisten" >Download</button>
	<?php endif ?>
	<button type="button" class="kleistad_download_link" name="kleistad_submit_cursus_overzicht" data-actie="presentielijst" >Presentielijst</button>
	<?php if ( $data['bestuur_rechten'] ) : ?>
	<button type="submit" name="kleistad_submit_cursus_overzicht" id="kleistad_herinner" value="herinner_email" data-confirm="Cursisten|weet je zeker dat je nu de herinneringsemail wilt versturen" >Verstuur herinner email</button>
	<?php endif ?>
	<button type="button" style="position:absolute;right:0px;" class="kleistad_terug_link">Terug</button>
	</form>
<?php elseif ( 'indelen' === $data['actie'] ) : ?>
	<?php $this->form(); ?>
	<input type="hidden" name="cursus_id" value="<?php echo esc_attr( $data['cursus']['id'] ); ?>">
	<input type="hidden" name="cursist_id" value="<?php echo esc_attr( $data['cursist']['id'] ); ?>">
	<h2>Indeling op lopende cursus</h2>
	<div class="kleistad_row">
		<div class="kleistad_col_3">
			<label>Cursist</label>
		</div>
		<div class="kleistad_col_5">
			<?php echo esc_html( $data['cursist']['naam'] ); ?>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3">
			<label>Inschrijfdatum</label>
		</div>
		<div class="kleistad_col_5">
			<?php echo esc_html( date( 'd-m-Y', $data['cursist']['datum'] ) ); ?>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3">
			<label>Prijs advies</label>
		</div>
		<div class="kleistad_col_5">
			<?php echo esc_html( "totaal {$data['cursus']['lessen']} lessen, resterend {$data['cursus']['lessen_rest']}" ); ?>
			<br/>
			<strong>advies prijs &euro; <?php echo esc_html( number_format_i18n( $data['cursus']['kosten'] * $data['cursist']['aantal'], 2 ) ); ?></strong>
		</div>
	</div>
	<div class="kleistad_row">
		<div class="kleistad_col_3">
			<label for="kleistad_kosten">Vastgestelde prijs</label>
		</div>
		<div class="kleistad_col_5">
			<input type=number name="kosten" id="kleistad_kosten" step="0.01" min="0" max="<?php echo esc_attr( $data['cursus']['max'] * $data['cursist']['aantal'] ); ?>"
				value="<?php echo esc_attr( number_format( $data['cursus']['kosten'] * $data['cursist']['aantal'], 2 ) ); ?>" >
		</div>
	</div>
	<div class="kleistad_row" style="padding-top:20px;">
		<div class="kleistad_col_3">
			<button name="kleistad_submit_cursus_overzicht" type="submit" value="indelen" >Bevestigen</button>
		</div>
		<div class="kleistad_col_4">
		</div>
		<div class="kleistad_col_3">
			<button type="button" style="position:absolute;right:0px;" class="kleistad_terug_link">Terug</button>
		</div>
	</div>
	</form>
<?php else : ?>
<table id="kleistad_cursussen" class="kleistad_datatable display" data-order='[[ 0, "desc" ]]'>
	<thead>
		<tr>
			<th>Code</th>
			<th>Naam</th>
			<th>Docent</th>
			<th>Start</th>
			<th data-orderable="false"></th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $data['cursus_info'] as $cursus_id => $cursus_info ) :
		?>
		<tr>
			<td data-sort="<?php echo esc_attr( $cursus_id ); ?>"><?php echo esc_html( $cursus_info['code'] ); ?></td>
			<td><?php echo esc_html( $cursus_info['naam'] ); ?></td>
			<td><?php echo esc_html( $cursus_info['docent'] ); ?></td>
			<td data-sort="<?php echo esc_attr( $cursus_info['start_dt'] ); ?>"><?php echo esc_html( $cursus_info['start_datum'] ); ?></td>
			<td>
				<?php if ( $cursus_info['inschrijvingen'] ) : ?>
				<a href="#" title="toon cursisten" class="kleistad_view kleistad_edit_link" style="text-decoration:none !important;color:green;padding:.4em .8em;"
					data-id="<?php echo esc_attr( $cursus_id ); ?>" data-actie="cursisten" >
					&nbsp;
				</a>
				<?php endif ?>
			</td>
		</tr>
	<?php endforeach ?>
	</tbody>
</table>

<?php endif ?>
