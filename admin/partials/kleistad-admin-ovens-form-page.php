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
<div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2>Oven<a class="add-new-h2"
                 href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=ovens'); ?>">terug naar lijst</a>
    </h2>

    <?php if (!empty($notice)): ?>
      <div id="notice" class="error"><p><?php echo $notice ?></p></div>
    <?php endif; ?>
    <?php if (!empty($message)): ?>
      <div id="message" class="updated"><p><?php echo $message ?></p></div>
    <?php endif; ?>

    <form id="form" method="POST">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('kleistad_oven') ?>"/>
        <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <?php do_meta_boxes('oven', 'normal', $item); ?>
                    <input type="submit" value="Opslaan" id="submit" class="button-primary" name="submit">
                </div>
            </div>
        </div>
    </form>
</div>