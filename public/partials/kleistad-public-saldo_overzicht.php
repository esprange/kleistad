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
if (!Kleistad_Roles::override()) : ?>
<p>Geen toegang tot dit formulier</p>
<?php else : extract ($data); ?>

<table class="kleistad_rapport">
    <thead>
        <tr><th>Naam</th><th>Saldo</th></tr>
    </thead>
    <tbody>
    <?php foreach ($stokers as $stoker) : ?>
        <tr>
            <td><?php echo $stoker['naam'] ?></td>
            <td>&euro; <?php echo $stoker['saldo'] ?></td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
<?php endif ?>