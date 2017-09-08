<?php
/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
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
  <form id="kleistad_form_betalingen" action="#" method="post" >
		<?php wp_nonce_field( 'kleistad_betalingen' ); ?>
	  <table class="kleistad_rapport" >
		  <thead>
			  <tr>
				  <th>Datum<br/>inschrijving</th>
				  <th>datum sorted (1)</th>
				  <th>Code</th>
				  <th>Naam</th>
				  <th>Inschrijfgeld<br/>betaald</th>
				  <th>igeld sorted (5)</th>
				  <th>Cursusgeld<br/>betaald</th>
				  <th>cgeld sorted (7)</th>
				  <th>Geannuleerd</th>
				  <th>annul sorted (9)</th>
			  </tr>
		  </thead>
		  <tbody>
				<?php foreach ( $data['rows'] as $row ) : ?>
				<tr style="<?php echo $row['geannuleerd'] ? 'color:grey' : ''; ?>" >
					<td><?php echo date( 'd-m-y', $row['datum'] ); ?></td>
					<td><?php echo date( 'ymd', $row['datum'] ); ?></td>
					<td><?php echo $row['code']; ?></td>
					<td><?php echo $row['naam']; ?></td>
					<td style="text-align:center" ><?php if ( $row['i_betaald'] ) : ?>
						  <span class="genericon genericon-checkmark"></span>
						<?php else : ?>
						  <input type="checkbox" name="i_betaald[]" value="<?php echo $row['value']; ?>" >
						<?php endif ?>
					</td>
					<td><?php echo $row['i_betaald'] ? 1 : 0; ?></td>
					<td style="text-align:center" ><?php if ( $row['c_betaald'] ) : ?>
						  <span class="genericon genericon-checkmark"></span>
						<?php else : ?>
						  <input type="checkbox" name="c_betaald[]" value="<?php echo $row['value']; ?>" >
						<?php endif ?>
					</td>
					<td><?php echo $row['c_betaald'] ? 1 : 0; ?></td>
					<td style="text-align:center" ><?php if ( $row['geannuleerd'] ) : ?>
						  <span class="genericon genericon-checkmark"></span>
						<?php else : ?>
						  <input type="checkbox" name="c_betaald[]" value="<?php echo $row['value']; ?>" >
						<?php endif ?>
					</td>
					<td><?php echo $row['geannuleerd'] ? 1 : 0; ?></td>
				</tr>
				<?php endforeach ?>
		  </tbody>
	  </table>
	  <button type="submit" name="kleistad_submit_betalingen" >Opslaan</button>
  </form>

<?php endif ?>
