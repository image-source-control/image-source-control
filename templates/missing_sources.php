<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<h1><?php _e('Missing Images', ISCTEXTDOMAIN); ?></h1>

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
                <th><?php _e('image title', ISCTEXTDOMAIN); ?></th>
                <th><?php _e('post / page', ISCTEXTDOMAIN); ?></th>
            </tr>
        </thead><tbody><?php
    foreach ($attachments as $_attachment) {
        ?><tr><td><?php echo $_attachment->ID; ?></td><td><a href="<?php echo admin_url('media.php?attachment_id=' . $_attachment->ID . '&action=edit'); ?>" title="<?php _e('edit this image', ISCTEXTDOMAIN); ?>"><?php echo $_attachment->post_title; ?></a></td><td><?php the_excerpt(); ?></td></tr><?php
        
    }
    ?></tbody></table><?php
}

/**
 * aim: list all posts where one or another image source is missing
 * 1. get all attachments that do not have the isc_image_source or isc_image_source_own meta field
 * problem: attachments without this value are not queried at all
 * 3 solutions:
 * a) build custom query with $wpdb
 * b) query all attachments and than loop through them one by one (not recommented for speed reasons)
 * planen: create image_source-fields for all attachments on install
 * 
 * 2. get all posts the attachments are attached to
 * 3. list them and link to them to easily fix it
 * list also unpublished posts?
 * also link to the image in the mediathek to change it directly there!
 * how to automatically create the meta fields for all attachments? => needed for the query
 */