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

if ( ! Kleistad_Roles::override() ) : ?>
<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>

<table class="kleistad_rapport" id="kleistad_saldo_overzicht">
	<thead>
		<tr><th>Naam</th><th>Saldo</th></tr>
	</thead>
	<tbody>
	<?php foreach ( $data['stokers'] as $stoker ) : ?>
		<tr>
			<td><?php echo esc_html( $stoker['naam'] ); ?></td>
			<td>&euro; <?php echo esc_html( $stoker['saldo'] ); ?></td>
		</tr>
	<?php endforeach ?>
	</tbody>
</table>
<?php endif ?>
