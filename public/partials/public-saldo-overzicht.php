<?php
/**
 * Toon het saldo overzicht van de leden
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( ! \Kleistad\Roles::override() ) : ?>
<p>Geen toegang tot dit formulier</p>
	<?php
else :
	?>

<table class="kleistad_datatable display compact" data-order= '[[ 0, "asc" ]]'>
	<thead>
		<tr>
			<th>Naam</th>
			<th data-class-name="dt-body-right">Saldo</th>
		</tr>
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
