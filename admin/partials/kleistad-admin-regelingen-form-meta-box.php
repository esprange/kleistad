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
                <label for="gebruiker_naam">Naam gebruiker</label>
            </th>
            <td>
                <select name="gebruiker_id" id="gebruiker_id" style="width: 95%" required>
                    <?php foreach ($gebruikers as $gebruiker) : 
                      if (Kleistad_Roles::reserveer($gebruiker->id)) { 
                        $selected = ($item['gebruiker_id'] == $gebruiker->id) ? 'selected' : '';
                        ?>
                      <option value="<?php echo $gebruiker->id ?>" <?php echo $selected ?> ><?php echo $gebruiker->display_name ?></option>
                    <?php } endforeach ?>
                </select>
            </td>
        </tr>
        <tr class="form-field">
            <th  scope="row">
                <label for="oven_naam">Naam oven</label>
            </th>
            <td>
                <select name="oven_id" id="oven_id" style="width: 95%" required>
                    <?php foreach ($ovens as $oven) : 
                      $selected = ($item['oven_id'] == $oven->id) ? 'selected' : '';
                      ?>
                      <option value="<?php echo $oven->id ?>" <?php echo $selected ?> ><?php echo $oven->naam ?></option>
                    <?php endforeach ?>
                </select>
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
    </tbody>
</table>

