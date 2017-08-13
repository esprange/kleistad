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

<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
    <tbody>
        <tr class="form-field">
            <th  scope="row">
                <label for="naam">Naam</label>
            </th>
            <td>
                <input id="naam" name="naam" type="text" style="width: 95%" value="<?php echo esc_attr($item['naam']) ?>"
                       size="50" class="code" placeholder="De oven naam" required>
            </td>
        </tr>
        <tr class="form-field">
            <th  scope="row">
                <label for="kosten">Tarief</label>
            </th>
            <td>
                <input id="kosten" name="kosten" type="number" style="width: 95%" value="<?php echo esc_attr($item['kosten']) ?>"
                       size="10" step="0.01" class="code" placeholder="99.99" required>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label>Beschikbaarheid></label>
            </th>
            <td>
                <input name="beschikbaarheid[]" value="zondag" type="checkbox" <?php echo array_search('zondag', $item['beschikbaarheid']) !== false ? 'checked' : '' ?> />Zondag
                <input name="beschikbaarheid[]" value="maandag" type="checkbox" <?php echo array_search('maandag', $item['beschikbaarheid']) !== false ? 'checked' : '' ?> />Maandag
                <input name="beschikbaarheid[]" value="dinsdag" type="checkbox" <?php echo array_search('dinsdag', $item['beschikbaarheid']) !== false ? 'checked' : '' ?> />Dinsdag
                <input name="beschikbaarheid[]" value="woensdag" type="checkbox" <?php echo array_search('woensdag', $item['beschikbaarheid']) !== false ? 'checked' : '' ?> />Woensdag
                <input name="beschikbaarheid[]" value="donderdag" type="checkbox" <?php echo array_search('donderdag', $item['beschikbaarheid']) !== false ? 'checked' : '' ?> />Donderdag
                <input name="beschikbaarheid[]" value="vrijdag" type="checkbox" <?php echo array_search('vrijdag', $item['beschikbaarheid']) !== false ? 'checked' : '' ?> />Vrijdag
                <input name="beschikbaarheid[]" value="zaterdag" type="checkbox" <?php echo array_search('zaterdag', $item['beschikbaarheid']) !== false ? 'checked' : '' ?> />Zaterdag
            </td>
        </tr>

    </tbody>
</table>

