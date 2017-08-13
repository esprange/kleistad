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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="initial-scale=1.0"/>
        <meta name="format-detection" content="telephone=no"/>
        <title><?php echo $subject ?></title>
    </head>
    <body>
        <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
            <tr>
                <td align="left" style="font-family:helvetica; font-size:13pt" >
                    <?php echo preg_replace('/\s+/', ' ', $text) ?><br />
                    <p>Met vriendelijke groet,</p>
                    <p>Kleistad</p>
                    <p><a href="mailto:<?php echo $emailadresses['info'] ?>" target="_top"><?php echo $emailadresses['info'] ?></a></p>
                </td>                         
            </tr>
            <tr>
                <td align="center" style="font-family:calibri; font-size:9pt" >
                    Deze e-mail is automatisch gegenereerd en kan niet beantwoord worden.
                </td>
            </tr>
        </table>
    </body>
</html>
