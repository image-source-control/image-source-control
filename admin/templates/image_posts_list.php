<?php
if ($images_with_posts->have_posts()) : ?>
    <table class="widefat isc-table" style="width: 80%;" >
        <thead>
            <tr>
                <th><?php _e('Image', ISCTEXTDOMAIN); ?></th>
                <th><?php _e('Posts', ISCTEXTDOMAIN); ?></th>
            </tr>
        </thead><tbody><?php

    while ($images_with_posts->have_posts()) :
        $images_with_posts->the_post();
        $_posts = get_post_meta(get_the_ID(), 'isc_image_posts', true);
        if(is_array($_posts) && count($_posts) > 0) : ?>
        <tr>
            <td><a href="<?php echo admin_url('media.php?attachment_id=' . get_the_ID() . '&action=edit'); ?>"><?php the_title(); ?></a></td>
            <td>
                    <ul><?php
                    foreach($_posts as $_post_id) : ?>
                        <li><a href="<?php echo get_edit_post_link( $_post_id ); ?>"><?php echo $_post_id; ?></a></li>
                    <?php endforeach; ?>
                    </ul>
           </td>
        </tr>
    <?php endif; ?>
    <?php endwhile; ?>
    </tbody></table><?php
endif;