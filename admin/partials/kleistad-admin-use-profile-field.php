<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */
?>
<table class="form-table">
    <tbody>
        <tr>
            <th>
                <label for="kleistad_disable_user"><?php echo 'Deactiveer account' ?></label>
            </th>
            <td>
                <input type="checkbox" name="kleistad_disable_user" id="kleistad_disable_user" value="1" <?php checked(1, get_the_author_meta('kleistad_disable_user', $user->ID)); ?> />
                <span class="description"><?php echo 'bij aanvinken kan de gebruiker niet inloggen met dit account.' ?></span>
            </td>
        </tr>
    <tbody>
</table>
