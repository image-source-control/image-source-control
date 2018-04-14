<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<h1><?php _e('Missing Sources', 'isc'); ?></h1>

<?php
$attachments = ISC_Admin::get_attachments_with_empty_sources();
if (!empty($attachments)) {
    ?><h2><?php _e('Images with empty sources', 'image-source-control-isc'); ?></h2>
    <p><?php _e('Images with empty sources and not belonging to an author.', 'image-source-control-isc'); ?></p>
    <table class="widefat isc-table" style="width: 80%;" >
        <thead>
            <tr>
                <th><?php _e('ID', 'image-source-control-isc'); ?></th>
                <th><?php _e('Image title', 'image-source-control-isc'); ?></th>
                <th><?php _e('Post / Page', 'image-source-control-isc'); ?></th>
            </tr>
        </thead><tbody><?php
    foreach ($attachments as $_attachment) {
        ?><tr>
            <td><?php echo $_attachment->ID; ?></td>
            <td><a href="<?php echo admin_url('media.php?attachment_id=' . $_attachment->ID . '&action=edit'); ?>" title="<?php _e('edit this image', 'image-source-control-isc'); ?>"><?php echo $_attachment->post_title; ?></a></td>
            <td><?php if ( $_attachment->post_parent ) : ?><a href="<?php echo get_edit_post_link( $_attachment->post_parent ); ?>" title="<?php _e('show parent post\'s edit page', 'image-source-control-isc' ); ?>"><?php echo get_the_title( $_attachment->post_parent ); ?></a><?php else : _e('no connection', 'image-source-control-isc'); ?><?php endif; ?></td></tr><?php

    }
    ?></tbody></table><?php
} else {
    ?>
    <h2><?php _e('No empty sources found!', 'image-source-control-isc'); ?></h2>
    <?php
}

$attachments = ISC_Admin::get_attachments_without_sources();
if (!empty($attachments)) {
    ?><h2><?php _e('Unindexed images', 'image-source-control-isc'); ?></h2>
    <p><?php _e('Images that haven’t been indexed yet, e.g. after plugin installation. (technically speaking: no meta field created yet)', 'image-source-control-isc'); ?></p>
    <table class="widefat isc-table" style="width: 80%;" >
        <thead>
            <tr>
                <th><?php _e('ID', 'image-source-control-isc'); ?></th>
                <th><?php _e('Image title', 'image-source-control-isc'); ?></th>
                <th><?php _e('Post / Page', 'image-source-control-isc'); ?></th>
            </tr>
        </thead><tbody><?php
    foreach ($attachments as $_attachment) {
        ?><tr>
            <td><?php echo $_attachment->ID; ?></td>
            <td><a href="<?php echo admin_url('media.php?attachment_id=' . $_attachment->ID . '&action=edit'); ?>" title="<?php _e('edit this image', 'image-source-control-isc'); ?>"><?php echo $_attachment->post_title; ?></a></td>
            <td><?php if ( $_attachment->post_parent ) : ?><a href="<?php echo get_edit_post_link( $_attachment->post_parent ); ?>" title="<?php _e('show parent post\'s edit page', 'image-source-control-isc' ); ?>"><?php echo get_the_title( $_attachment->post_parent ); ?></a><?php else : _e('no connection', 'image-source-control-isc'); ?><?php endif; ?></td></tr><?php

    }
    ?></tbody></table><?php
} else {
    ?>
    <h2><?php _e('All images are indexed.', 'image-source-control-isc'); ?></h2>
    <?php
} ?>
<h2><?php _e('Debug', 'image-source-control-isc'); ?></h2>
<button type="button" id="isc-list-post-image-relation"><?php _e('list post-image relations', 'image-source-control-isc'); ?></button>
<p class="description"><?php _e('This will list the information ISC knows about the connection between posts and images.', 'image-source-control-isc'); ?></p>
<div id="isc-post-image-relations"></div>
<button type="button" id="isc-list-image-post-relation"><?php _e('list image-post relations', 'image-source-control-isc'); ?></button>
<p class="description"><?php _e('This will list the information ISC knows about the connection between images and posts.', 'image-source-control-isc'); ?></p>
<div id="isc-image-post-relations"></div>