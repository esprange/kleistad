<?php
/**
 * Toon het contact formulier
 *
 * @link       https://www.kleistad.nl
 * @since      6.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

$this->form();
?>
<input type="hidden" name="id" value="<?php echo esc_attr( $data['input']['id'] ); ?>">
<input type="hidden" id="kleistad_naam" value="<?php echo esc_attr( $data['input']['naam'] ); ?>">
<div class="kleistad_row">
	<div class="kleistad_col_3">
	<select name="datum" id="kleistad_datum" value="<?php echo esc_attr( $data['input']['datum'] ); ?> ">
<?php foreach ( $data['datums'] as $datum ) : ?>
		<option value="<?php echo esc_attr( $datum ); ?>" <?php selected( $data['input']['datum'], $datum ); ?> >
			<?php echo esc_html( strftime( '%A %x', $datum ) ); ?>
		</option>
<?php endforeach ?>
	</select>
	</div>
</div>
<div class="kleistad_row">
	<div class="kleistad_col_2">
		&nbsp;
	</div>
<?php foreach ( $data['beschikbaarheid'] as $beschikbaarheid ) : ?>
	<div class="kleistad_col_2">
		<?php echo esc_html( $beschikbaarheid['T'] ); ?>
	</div>
<?php endforeach ?>
</div>

<?php
foreach ( [
	'D' => 'draaien',
	'H' => 'handvormen',
] as $werk => $titel ) :
	?>
<div class="kleistad_row">
	<div class="kleistad_col_2">
		<?php echo esc_html( $titel ); ?>
	</div>
	<?php foreach ( $data['beschikbaarheid'] as $index => $beschikbaarheid ) : ?>
	<div class="kleistad_col_2">
		<table>
			<?php
				$button = false;
			for ( $plek = 0; $plek < $beschikbaarheid[ $werk ]; $plek++ ) :
				?>
			<tr>
				<td>
				<?php if ( isset( $data['reserveringen'][ $index ][ $werk ]['namen'][ $plek ] ) ) : ?>
					<span style="font-size:x-small"><?php echo esc_html( $data['reserveringen'][ $index ][ $werk ]['namen'][ $plek ] ); ?></span>
					<?php
					elseif ( ! $button ) :
						$button   = true;
						$aanwezig = $data['reserveringen'][ $index ][ $werk ]['aanwezig'] ?? false;
						?>
					<label for="<?php echo esc_attr( "res{$index}_{$werk}" ); ?>" style="width:100%" >
						<?php echo esc_html( $aanwezig ? $data['input']['naam'] : 'reserveren' ); ?>
					</label>
					<input type="checkbox" name="<?php echo esc_attr( "res[$index][$werk]" ); ?>" id="<?php echo esc_attr( "res{$index}_{$werk}" ); ?>"
						<?php checked( $aanwezig ); ?> class="kleistad_corona" >
				<?php else : ?>
					&nbsp;
				<?php endif ?>
				</td>
			</tr>
			<?php endfor ?>
		</table>
	</div>
	<?php endforeach ?>
</div>
<?php endforeach ?>
	<button name="kleistad_submit_corona" id="kleistad_submit" type="submit" >Opslaan</button>
</form>
