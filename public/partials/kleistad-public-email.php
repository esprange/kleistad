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
		<input id="kleistad_selectie" name="adressen" type="hidden">
		<div class="kleistad_row">
			<div class="kleistad_label">
				<label>Selecteer de groep(en) waarvoor de email verzonden moet worden</label>
			</div>
		</div>
		<div class="kleistad_row">
			<div id="kleistad_gebruikers" class="kleistad_col_10">
				<ul>
				<?php foreach ( $data['input']['tree'] as $groep_id => $groep ) : ?>
					<li>
						<?php echo esc_html( $groep['naam'] ); ?>
						<ul>
						<?php foreach ( $groep['leden'] as $gebruiker_id => $gebruiker ) : ?>
							<li title="<?php echo esc_attr( $gebruiker_id ); ?>">
							<?php echo esc_html( $gebruiker ); ?>
							</li>
						<?php endforeach ?>
						</ul>
					</li>
				<?php endforeach ?>
				</ul>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_label">
				<label for="kleistad_onderwerp" >Wat is het onderwerp van de email</label>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_5">
				<input type="text" name="onderwerp" id="kleistad_onderwerp" required value="<?php echo esc_attr( $data['input']['onderwerp'] ); ?>" >
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_label">
				<label for="kleistad_email" >Voer de tekst in van de email</label>
			</div>
		</div>
		<div style="background:lightgray;" >
		<p>Beste Kleistad gebruiker,</p>
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
			<button type="submit" name="kleistad_submit_email" value="verzenden" >Verzenden</button>
			<button type="submit" name="kleistad_submit_email" value="test_email" >Test Email verzenden</button>
		</div>
	</form>
<?php
