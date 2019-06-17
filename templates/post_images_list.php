<?php
if ($posts_with_images->have_posts()) : ?>
    <table class="widefat isc-table" style="width: 80%;" >
        <thead>
            <tr>
                <th><?php _e('Post / Page', ISCTEXTDOMAIN); ?></th>
                <th><?php _e('Images', ISCTEXTDOMAIN); ?></th>
            </tr>
        </thead><tbody><?php

    while ($posts_with_images->have_posts()) :
        $posts_with_images->the_post();
        $_images = get_post_meta(get_the_ID(), 'isc_post_images', true);
        if(is_array($_images) && count($_images) > 0) : ?>
        <tr>
            <td><a href="<?php echo get_edit_post_link( get_the_ID() ); ?>"><?php the_title(); ?></a></td>
            <td>
                    <ul><?php
                    foreach($_images as $_image_id => $_image) : ?>
                        <li><?php if($_image_id != '') : ?>
                            <a href="<?php echo admin_url('media.php?attachment_id=' . $_image_id . '&action=edit'); ?>" title="<?php _e('edit this image', ISCTEXTDOMAIN); ?>"><?php echo $_image['src']; ?></a></li>
                        <?php else : ?>
                            <?php print_r($_images); ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </ul>
            </td>
        </tr>
    <?php endif; ?>
    <?php endwhile; ?>
    </tbody></table><?php
endif;