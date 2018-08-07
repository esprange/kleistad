<?php
/**
 * Toon het extra veld op de user profile pagina
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 * @phan-file-suppress   PhanUndeclaredVariable
 */

?>
<table class="form-table">
	<tbody>
		<tr>
			<th>
				<label for="kleistad_disable_user"><?php echo 'Deactiveer account'; ?></label>
			</th>
			<td>
				<input type="checkbox" name="kleistad_disable_user" id="kleistad_disable_user" value="1" <?php checked( 1, get_the_author_meta( 'kleistad_disable_user', $user->ID ) ); ?> />
				<span class="description"><?php echo esc_html( 'bij aanvinken kan de gebruiker niet inloggen met dit account.' ); ?></span>
			</td>
		</tr>
	<tbody>
</table>
