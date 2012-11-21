<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<h1><?php _e('Missing Images', 'isc'); ?></h1>

<h2><?php _e('add meta fields', ISCTEXTDOMAIN); ?></h2>
<p><?php _e('ISC adds some fields to the attachments. If you install ISC, these fields are missing and attachments with missing fields are ignored in some of the queries. So use this button at least the first time you start using ISC.', ISCTEXTDOMAIN); ?></p>
<p><a id="isc_add_metafields" href="javascript:void(0);"><?php _e('add metafields to images', ISCTEXTDOMAIN); ?></a>
    <?php ISC_CLASS::show_loading_image(); ?>
    <span id="add_metafields_result"></span>
</p>


<?php
$attachments = ISC_CLASS::get_attachments_without_sources();
if (!empty($attachments)) {
    ?><table>
        <thead>
            <tr>
                <th><?php _e('ID', ISCTEXTDOMAIN); ?></th>
                <th><?php _e('image title', 'isc'); ?></th>
                <th><?php _e('post / page', ISCTEXTDOMAIN); ?></th>
            </tr>
        </thead><tbody><?php
    foreach ($attachments as $_attachment) {
        ?><tr>
            <td><?php echo $_attachment->ID; ?></td>
            <td><a href="<?php echo admin_url('media.php?attachment_id=' . $_attachment->ID . '&action=edit'); ?>" title="<?php _e('edit this image', ISCTEXTDOMAIN); ?>"><?php echo $_attachment->post_title; ?></a></td>
            <td><?php if ( $_attachment->post_parent ) : ?><a href="<?php echo get_edit_post_link( $_attachment->post_parent ); ?>" title="<?php _e('show parent post\'s edit page', ISCTEXTDOMAIN ); ?>"><?php echo get_the_title( $_attachment->post_parent ); ?></a><?php else : _e('no connection', ISCTEXTDOMAIN); ?><?php endif; ?></td></tr><?php
        
    }
    ?></tbody></table><?php
}