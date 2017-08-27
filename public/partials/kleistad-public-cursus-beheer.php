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

  <div id="kleistad_cursus">
	  <div id="kleistad_cursus_tabs">
		  <ul>
			  <li><a href="#kleistad_cursus_gegevens">Cursus informatie</a></li>
			  <li><a href="#kleistad_cursus_indeling">Cursus indeling</a></li>
		  </ul>
		  <div id="kleistad_cursus_gegevens" >
			  <form id="kleistad_form_cursus_gegevens" action="#" method="post" >
					<?php wp_nonce_field( 'kleistad_cursus_beheer' ); ?>
				  <input type="hidden" name="cursus_id" value="0"/>
				  <input type="hidden" name="tab" value="info"/>
				  <table class="kleistad_form" >
					  <tr>
						  <th>Naam</th>
						  <td colspan="3"><input type="text" name="naam" id="kleistad_cursus_naam" placeholder="Bijv. cursus draaitechnieken" required /></td>
					  </tr>
					  <tr>
						  <th>Docent</th>
						  <td colspan="3"><input type="text" name="docent" id="kleistad_cursus_docent" list="kleistad_docenten" >
							  <datalist id="kleistad_docenten">
									<?php foreach ( $data['gebruikers'] as $gebruiker ) : ?>
									<option value="<?php echo $gebruiker->voornaam . ' ' . $gebruiker->achternaam; ?>">
										<?php endforeach ?>
							  </datalist></td>
					  </tr>
					  <tr>
						  <th>Start</th>
						  <td><input type="text" name="start_datum" id="kleistad_cursus_start_datum" class="kleistad_datum" required value="<?php echo date( 'd-m-Y' ); ?>" /></td>
						  <th>Eind</th>
						  <td><input type="text" name="eind_datum" id="kleistad_cursus_eind_datum" class="kleistad_datum" required value="<?php echo date( 'd-m-Y' ); ?>" /></td>
					  </tr>
					  <tr>
						  <th>Begintijd</th>
						  <td><input type="text" name="start_tijd" id="kleistad_cursus_start_tijd" placeholder="00:00" class="kleistad_tijd" /></td>
						  <th>Eindtijd</th>
						  <td><input type="text" name="eind_tijd" id="kleistad_cursus_eind_tijd" placeholder="00:00" class="kleistad_tijd" /></td>
					  </tr>
					  <tr>
						  <th>Technieken</th>
						  <td><input type="checkbox" name="technieken[]" id="kleistad_draaien" value="Draaien">Draaien</td>
						  <td><input type="checkbox" name="technieken[]" id="kleistad_handvormen" value="Handvormen">Handvormen</td>
						  <td><input type="checkbox" name="technieken[]" id="kleistad_boetseren" value="Boetseren">Boetseren</td></tr>
					  <tr>
						  <th>Inschrijf kosten</th>
						  <td><input type="number" step="any" name="inschrijfkosten" id="kleistad_inschrijfkosten" value="<?php echo $this->options['cursusinschrijfprijs']; ?>" min="0" required ></td>
						  <th>Cursus kosten, excl. inschrijf kosten</th>
						  <td><input type="number" step="any" name="cursuskosten" id="kleistad_cursuskosten" value="<?php echo $this->options['cursusprijs']; ?>" min="0" required ></td>
					  </tr>
					  <tr>
						  <th>Cursus vol</th>
						  <td><input type="checkbox" name="vol" id="kleistad_vol" ></td>
						  <th>Cursus vervallen</th>
						  <td><input type="checkbox" name="vervallen" id="kleistad_vervallen" ></td>
					  </tr>
					  <tr>
						  <th>Inschrijf email</th>
						  <td colspan="3"><input type="text" name="inschrijfslug" id="kleistad_inschrijfslug" value="kleistad_email_cursus_aanvraag" required /></td>
					  </tr>
					  <tr>
						  <th>Indeling email</th>
						  <td colspan="3"><input type="text" name="indelingslug" id="kleistad_indelingslug" value="kleistad_email_cursus_ingedeeld" required /></td>
					  </tr>
				  </table>
				  <button type="submit" name="kleistad_submit_cursus_beheer">Opslaan</button>
			  </form>
		  </div>

		  <div id="kleistad_cursus_indeling" >
			  <form id="kleistad_form_cursus_indeling" action="#" method="post" >
				  <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'kleistad_cursus_beheer' ); ?>" />
					<?php wp_referer_field(); ?>
				  <input type="hidden" name="cursus_id" value="0"/>
				  <input type="hidden" name="tab" value="indeling"/>
				  <input type="hidden" name="indeling_lijst" id="kleistad_indeling_lijst" /> 
				  <table class="kleistad_form" >
					  <tr>
						  <th>Wachtlijst</th>
						  <td></td>
						  <th>Indeling</th>
					  </tr>
					  <tr>
						  <td><select style="height:200px" size="10" id="kleistad_wachtlijst" ></select></td>
						  <td><button id="kleistad_wissel_indeling">&lt;-&gt;</button></td>
						  <td><select style="height:200px" size="10" id="kleistad_indeling" ></select></td>
					  </tr>
				  </table>
				  <div id="kleistad_cursist_technieken"></div>
				  <div id="kleistad_cursist_opmerking"></div>
				  <button type="submit" name="kleistad_submit_cursus_beheer" >Opslaan</button>
			  </form>
		  </div>
	  </div>
  </div>
  <table class="kleistad_rapport">
	  <thead>
		  <tr>
			  <th>Id</th>
			  <th>Code</th>
			  <th>Naam</th>
			  <th>Docent</th>
			  <th>Periode</th>
			  <th>Tijd</th>
			  <th>Technieken</th>
		  </tr>
	  </thead>
	  <tbody>
			<?php foreach ( $data['rows'] as $row ) : ?>
			<tr style="background-color:<?php $row['cursus']->vol ? 'lightblue' : ($row['cursus']->vervallen ? 'lightgray' : ''); ?>" class="kleistad_cursus_info" 
				data-cursus='
				<?php
				echo json_encode(
					[
						'id' => $row['cursus']->id,
						'naam' => $row['cursus']->naam,
						'start_datum' => date( 'd-m-Y', $row['cursus']->start_datum ),
						'eind_datum' => date( 'd-m-Y', $row['cursus']->eind_datum ),
						'start_tijd' => date( 'H:i', $row['cursus']->start_tijd ),
						'eind_tijd' => date( 'H:i', $row['cursus']->eind_tijd ),
						'docent' => $row['cursus']->docent,
						'technieken' => $row['cursus']->technieken,
						'vervallen' => $row['cursus']->vervallen,
						'vol' => $row['cursus']->vol,
						'techniekkeuze' => $row['cursus']->techniekkeuze,
						'inschrijfkosten' => $row['cursus']->inschrijfkosten,
						'cursuskosten' => $row['cursus']->cursuskosten,
						'inschrijfslug' => $row['cursus']->inschrijfslug,
						'indelingslug' => $row['cursus']->indelingslug,
					]
				)
				?>
				' 
				data-wachtlijst='<?php echo json_encode( $row['wachtlijst'] ); ?>' 
				data-ingedeeld='<?php echo json_encode( $row['ingedeeld'] ); ?>' >
				<td><?php echo $row['cursus']->id; ?></td>
				<td>C<?php echo $row['cursus']->id; ?></td>
				<td><?php echo $row['cursus']->naam; ?></td>
				<td><?php echo $row['cursus']->docent; ?></td>
				<td><?php echo strftime( '%d-%m', $row['cursus']->start_datum ) . ' .. ' . strftime( '%d-%m', $row['cursus']->eind_datum ); ?></td>
				<td><?php echo strftime( '%H:%M', $row['cursus']->start_tijd ) . ' - ' . strftime( '%H:%M', $row['cursus']->eind_tijd ); ?></td>
				<td>
					<?php
					$technieken = $row['cursus']->technieken;
					foreach ( $technieken as $techniek ) {
						echo $techniek . '<br/>';
					}
					?>
				</td>
			</tr>
	<?php endforeach ?>
	  </tbody>
  </table>
  <button id="kleistad_cursus_toevoegen" >Toevoegen</button>
<?php endif ?>
