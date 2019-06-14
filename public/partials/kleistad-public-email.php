<?php
/**
 * Toon het email invoerscherm
 *
 * @link       https://www.kleistad.nl
 * @since      5.5.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

?>
	<form method="POST">
		<?php wp_nonce_field( 'kleistad_email' ); ?>

		<div class="kleistad_row">
			<div class="kleistad_label">
				<label for="kleistad_groep" >Selecteer de groep(en) waarvoor de email verzonden moet worden</label>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_5">
				<select name="groepen[]" required multiple>
				<?php foreach ( $data['groepen'] as $groep_id => $groep ) : ?>
					<option value='<?php echo esc_attr( $groep_id ); ?>' <?php selected( array_key_exists( $groep_id, $data['input']['groepen'] ) ); ?> ><?php echo esc_html( $groep ); ?></option>
				<?php endforeach ?>
				</select>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_label">
				<label for="kleistad_onderwerp" >Wat is het onderwerp van de email</label>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_5">
				<input type="text" name="onderwerp" required value="<?php echo esc_attr( $data['input']['onderwerp'] ); ?>" >
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_label">
				<label for="kleistad_email" >Voer de tekst in van de email</label>
			</div>
		</div>
		<div style="background:lightgray;" >
		<p>Beste X,</p>
	<?php
		wp_editor(
			$data['input']['email_content'],
			'kleistad_email',
			[
				'textarea_name' => 'email_content',
				'textarea_rows' => 6,
				'quicktags'     => false,
			]
		);
		?>
		<p>Met vriendelijke groet</p>
		<p><?php echo esc_html( wp_get_current_user()->display_name ); ?> namens Kleistad</p>
		</div>
		<div class="kleistad_row" >
			<button type="submit" name="kleistad_submit_email" >Verzenden</button>
		</div>
	</form>
<?php
