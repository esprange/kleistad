<?php
/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( ! Kleistad_Roles::override() ) :
	?>
  <p>Geen toegang tot dit formulier</p>
<?php
else :
?>

  <div id="kleistad_deelnemer_info">
	  <table class="kleistad_tabel" id="kleistad_deelnemer_tabel" >
	  </table>
  </div>
  <p><label for="kleistad_deelnemer_selectie">Selectie</label>
	  <select id="kleistad_deelnemer_selectie" name="selectie" >
		  <option value="*" >&nbsp;</option>
		  <option value="0" >Leden</option>
			<?php foreach ( $data['cursussen'] as $cursus ) : ?>
			<option value="C<?php echo $cursus->id; ?>;">C<?php echo $cursus->id . ' ' . $cursus->naam; ?></option>
			<?php endforeach ?>

	  </select>
  </p>
  <table class="kleistad_tabel, kleistad_rapport" id="kleistad_deelnemer_lijst">
	  <thead>
		  <tr>
			  <th>Lid</th>
			  <th>Cursuslijst</th>
			  <th>Achternaam</th>
			  <th>Voornaam</th>
			  <th>Email</th>
			  <th>Telnr</th>
		  </tr>
	  </thead>
	  <tbody>
			<?php foreach ( $data['registraties'] as $registratie ) : ?>
			<tr class="kleistad_deelnemer_info" 
				data-inschrijvingen='<?php echo json_encode( $registratie['inschrijvingen'] ); ?>'
				data-deelnemer='<?php echo json_encode( $registratie['deelnemer_info'] ); ?>' 
				data-abonnee='<?php echo json_encode( $registratie['abonnee_info'] ); ?>' >
				<td><?php echo $registratie['is_lid']; ?></td>
				<td><?php echo $registratie['cursuslijst']; ?></td>
				<td><?php echo $registratie['achternaam']; ?></td>
				<td><?php echo $registratie['voornaam']; ?></td>
				<td><?php echo $registratie['email']; ?></td>
				<td><?php echo $registratie['telnr']; ?></td>
			</tr>
			<?php endforeach ?>
	  </tbody>
  </table>
  <form action="#" method="post" >
		<?php wp_nonce_field( 'kleistad_registratie_overzicht' ); ?>
	  <button type="submit" name="kleistad_submit_registratie_overzicht" >Bestand aanmaken</button>
  </form>
<?php endif; ?>
