<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<h1><?php _e('Missing Sources', 'isc'); ?></h1>

<?php
$attachments = ISC_CLASS::get_attachments_without_sources();
if (!empty($attachments)) {
    ?><table class="widefat" style="width: 80%;" >
        <thead>
            <tr>
                <th><?php _e('ID', ISCTEXTDOMAIN); ?></th>
                <th><?php _e('Image title', 'isc'); ?></th>
                <th><?php _e('Post / Page', ISCTEXTDOMAIN); ?></th>
            </tr>
        </thead><tbody><?php
    foreach ($attachments as $_attachment) {
        ?><tr>
            <td><?php echo $_attachment->ID; ?></td>
            <td><a href="<?php echo admin_url('media.php?attachment_id=' . $_attachment->ID . '&action=edit'); ?>" title="<?php _e('edit this image', ISCTEXTDOMAIN); ?>"><?php echo $_attachment->post_title; ?></a></td>
            <td><?php if ( $_attachment->post_parent ) : ?><a href="<?php echo get_edit_post_link( $_attachment->post_parent ); ?>" title="<?php _e('show parent post\'s edit page', ISCTEXTDOMAIN ); ?>"><?php echo get_the_title( $_attachment->post_parent ); ?></a><?php else : _e('no connection', ISCTEXTDOMAIN); ?><?php endif; ?></td></tr><?php
        
    }
    ?></tbody></table><?php
} else {
    ?>
    <h2><?php _e('All sources have been specified!', ISCTEXTDOMAIN); ?></h2>
    <?php
}
