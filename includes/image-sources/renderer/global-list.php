<?php

namespace ISC\Image_Sources\Renderer;

use ISC\Image_Sources\Renderer;
use ISC\Image_Sources\Image_Sources;
use ISC_Log;
use ISC\Standard_Source;

/**
 * Features around the Global List
 */
class Global_List extends Renderer {

	/**
	 * Create a shortcode to list all image sources in the frontend
	 * Initialized in ISC_Public::register_hooks()
	 *
	 * @param array $atts attributes.
	 *
	 * @return string
	 */
	public static function execute_shortcode( $atts = [] ): string {
		$options = self::get_options();

		$a = shortcode_atts(
			[
				'per_page'     => null,
				'before_links' => '',
				'after_links'  => '',
				'prev_text'    => '&#171; Previous',
				'next_text'    => 'Next &#187;',
				'included'     => null,
				'style'        => 'table',
			],
			$atts
		);

		// How many entries per page. Use plugin options or default, if the shortcode attribute is missing.
		if ( $a['per_page'] === null ) {
			$per_page = isset( $options['images_per_page'] ) ? absint( $options['images_per_page'] ) : 99999;
		} else {
			$per_page = absint( $a['per_page'] );
		}

		// Which types of images are included? Use plugin options or default, if the shortcode attribute is missing.
		if ( $a['included'] === null ) {
			$included = isset( $options['global_list_included_images'] ) ? $options['global_list_included_images'] : '';
		} else {
			$included = $a['included'];
		}

		// use proper translation if attribute is not given
		$prev_text = '&#171; Previous' === $a['prev_text'] ? __( '&#171; Previous', 'image-source-control-isc' ) : $a['prev_text'];
		$next_text = 'Next &#187;' === $a['next_text'] ? __( 'Next &#187;', 'image-source-control-isc' ) : $a['next_text'];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['isc-page'] ) ? intval( $_GET['isc-page'] ) : 1;

		$attachments_query_result = self::get_attachments(
			$a,
			$per_page,
			$page,
			$included
		);

		if ( is_a( $attachments_query_result, 'WP_Query' ) ) {
			$attachments       = $attachments_query_result->posts;
			$total_found_posts = $attachments_query_result->found_posts;
		} elseif ( is_array( $attachments_query_result ) && isset( $attachments_query_result['posts'], $attachments_query_result['found_posts'] ) ) {
			$attachments       = $attachments_query_result['posts'];
			$total_found_posts = $attachments_query_result['found_posts'];
		} else {
			$attachments       = $attachments_query_result;
			$total_found_posts = count( $attachments );
		}

		if ( empty( $attachments ) ) {
			return '';
		}

		$connected_atts = [];

		foreach ( $attachments as $_attachment ) {
			$connected_atts[ $_attachment->ID ]['source']      = Image_Sources::get_image_source_text_raw( $_attachment->ID );
			$connected_atts[ $_attachment->ID ]['standard']    = Standard_Source::use_standard_source( $_attachment->ID );
			$connected_atts[ $_attachment->ID ]['title']       = $_attachment->post_title;
			$connected_atts[ $_attachment->ID ]['author_name'] = '';
			if ( Standard_Source::standard_source_is( 'custom_text' ) && ! empty( $connected_atts[ $_attachment->ID ]['own'] ) ) {
				$connected_atts[ $_attachment->ID ]['author_name'] = Standard_Source::get_standard_source_text();
			} else {
				// show author name
				$connected_atts[ $_attachment->ID ]['author_name'] = get_the_author_meta( 'display_name', $_attachment->post_author );
			}

			$metadata   = get_post_meta( $_attachment->ID, 'isc_image_posts', true );
			$usage_data = '';

			if ( is_array( $metadata ) && [] !== $metadata ) {
				$usage_data_array = [];
				foreach ( $metadata as $data ) {
					// only list published posts
					if ( get_post_status( $data ) === 'publish' ) {
						$post_title = trim( get_the_title( $data ) );
						// Use post ID as fallback if title is empty
						if ( empty( $post_title ) ) {
							$post_title = '#' . $data;
						}
						$usage_data_array[] = sprintf(
							'<li><a href="%1$s">%2$s</a></li>',
							esc_url( get_permalink( $data ) ),
							esc_html( $post_title )
						);
					}
				}
				/**
				 * Note: Images might temporarily show fewer posts than expected if a post was recently
				 * unpublished/expired but the index hasn't been updated yet. This is an acceptable
				 * trade-off to avoid complex SQL queries on serialized meta data.
				 */
				if ( 'all' !== $included && $usage_data_array === [] ) {
					unset( $connected_atts[ $_attachment->ID ] );
					continue;
				}
				$usage_data .= "<ul style='margin: 0;'>";
				$usage_data .= implode( '', $usage_data_array );
				$usage_data .= '</ul>';
			}

			$connected_atts[ $_attachment->ID ]['posts'] = $usage_data;
		}

		$total = $total_found_posts;

		if ( 0 === $total ) {
			return '';
		}

		$up_limit = 1;

		// calculate total pages
		if ( $per_page && $per_page < $total ) {
			$up_limit = (int) ceil( $total / $per_page );
		}

		ob_start();
		self::display_all_attachment_list( $a, $connected_atts, $up_limit, $a['before_links'], $a['after_links'], $prev_text, $next_text );

		return ob_get_clean();
	}

	/**
	 * Get attachments based on the provided arguments
	 *
	 * @param array    $a        Shortcode attributes.
	 * @param int|null $per_page Number of items per page.
	 * @param int      $page     Current page number.
	 * @param string   $included Which types of images to include ('all' or '').
	 *
	 * @return \WP_Query Returns a WP_Query object.
	 */
	public static function get_attachments( array $a, int $per_page = 0, int $page = 1, string $included = '' ) {
		// Start with proper structure
		$meta_query = [ 'relation' => 'AND' ];

		if ( 'all' !== $included ) {
			// Add as subquery (single condition, but in array form for consistency)
			$meta_query[] = [
				'key'     => 'isc_image_posts',
				'value'   => 'a:0:{}',
				'compare' => '!=',
			];

			/**
			 * Filter the meta query for included images based on the isc_image_posts meta key
			 *
			 * @param array $meta_query Current meta query.
			 * @param string $included Included images setting.
			 */
			$meta_query = apply_filters( 'isc_global_list_meta_query_isc_image_posts', $meta_query, $included );
		}

		// Exclude standard source images if option is set to 'exclude'
		if ( Standard_Source::standard_source_is( 'exclude' ) ) {
			// Already has relation from above or we add it
			if ( ! isset( $meta_query['relation'] ) ) {
				$meta_query['relation'] = 'AND';
			}

			// Add as subquery
			$meta_query[] = [
				'relation' => 'OR',
				[
					'key'     => 'isc_image_source_own',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => 'isc_image_source_own',
					'value'   => '1',
					'compare' => '!=',
				],
			];
		}

		// Build default arguments
		$args = [
			'post_type'      => 'attachment',
			'posts_per_page' => $per_page === 0 ? get_option( 'posts_per_page' ) : (int) $per_page,
			'post_status'    => 'inherit',
			'post_parent'    => null,
			'paged'          => max( $page, 1 ),
			'no_found_rows'  => false,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		];

		if ( \ISC\Media_Type_Checker::enabled_images_only_option() ) {
			$args['post_mime_type'] = 'image';
		}

		// Add meta_query if we built one
		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		/**
		 * Modify query arguments for the global list attachments
		 *
		 * @param array $args Current query arguments.
		 * @param array $a Shortcode attributes.
		 */
		$args = apply_filters( 'isc_global_list_get_attachment_arguments', $args, $a );

		$query = new \WP_Query( $args );

		return $query;
	}

	/**
	 * Render the global list
	 *
	 * @param array   $shortcode_attributes attributes from the shortcode.
	 * @param array[] $atts attachments.
	 * @param int     $up_limit total page count.
	 * @param string  $before_links optional html to display before pagination links.
	 * @param string  $after_links optional html to display after pagination links.
	 * @param string  $prev_text text for the previous page link.
	 * @param string  $next_text text for the next page link.
	 */
	public static function display_all_attachment_list( $shortcode_attributes, $atts, $up_limit, $before_links = '', $after_links = '', $prev_text = '', $next_text = '' ) {
		if ( ! is_array( $atts ) || $atts === [] ) {
			return;
		}
		$options = self::get_options();

		$global_list_path = apply_filters( 'isc_public_global_list_view_path', ISCPATH . 'public/views/global-list.php' );
		if ( $global_list_path && file_exists( $global_list_path ) ) {
			require $global_list_path;
			self::pagination_links( $up_limit, $before_links, $after_links, $prev_text, $next_text );
		}

		do_action( 'isc_public_global_list_after', $shortcode_attributes, $atts, $up_limit, $before_links, $after_links, $prev_text, $next_text );
	}

	/**
	 * Render global list thumbnails
	 *
	 * @param int $attachment_id attachment ID.
	 */
	public static function render_global_list_thumbnail( $attachment_id ) {
		$options = self::get_options();

		if ( 'custom' !== $options['thumbnail_size'] ) {
			$thumbnail = wp_get_attachment_image( $attachment_id, $options['thumbnail_size'] );
		} else {
			$thumbnail = wp_get_attachment_image( $attachment_id, [ $options['thumbnail_width'], $options['thumbnail_height'] ] );
		}

		// a thumbnail might be missing for images that are not hosted within WordPress
		if ( ! $thumbnail ) {
			?>
			<img src="<?php echo esc_url( ISCBASEURL ) . '/public/assets/images/isc-icon-gray.svg'; ?>" style="width: 100%;"/>
			<?php
		}

		echo $thumbnail;
	}

	/**
	 * Render pagination links, use $before_links and after_links to wrap pagination links inside an additional block
	 *
	 * @param int    $max_page total page count.
	 * @param string $before_links optional html to display before pagination links.
	 * @param string $after_links optional html to display after pagination links.
	 * @param string $prev_text text for the previous page link.
	 * @param string $next_text text for the next page link.
	 */
	public static function pagination_links( $max_page, $before_links, $after_links, $prev_text, $next_text ) {
		if ( ( ! isset( $max_page ) ) || $max_page === 1 || ( ! isset( $before_links ) ) || ( ! isset( $after_links ) ) || ( ! isset( $prev_text ) ) || ( ! isset( $next_text ) ) ) {
			return;
		}
		if ( ! empty( $before_links ) ) {
			echo $before_links;
		}
		?>
		<div class="isc-paginated-links">
			<?php
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset( $_GET['isc-page'] ) ? intval( $_GET['isc-page'] ) : 1;
			if ( $max_page < $page ) {
				$page = $max_page;
			}
			if ( $page < 1 ) {
				$page = 1;
			}
			$min_page          = 1;
			$backward_distance = $page - $min_page;
			$forward_distance  = $max_page - $page;

			$page_link = get_page_link();

			/**
			 * Remove the query_string of the page_link (?page_id=xyz for the standard permalink structure),
			 * which is already captured in $_SERVER['QUERY_STRING'].
			 *
			 * @todo replace regex with other value (does WP store the url path without attributes somewhere?
			 * >get_page_link() returns the permalink but for the default WP permalink structure, the permalink looks like "http://domain.tld/?p=52", while $_GET
			 * still has a field named 'page_id' with the same value of 52.
			 */

			$pos = strpos( $page_link, '?' );
			if ( false !== $pos ) {
				$page_link = substr( $page_link, 0, $pos );
			}

			/**
			 * Unset the actual "$_GET['isc-page']" variable (if is set). Pagination variable will be appended to the new query string with a different value for each
			 * pagination link.
			 */

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['isc-page'] ) ) {
				unset( $_GET['isc-page'] );
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_string = http_build_query( $_GET );

			$isc_query_tag = '';
			if ( empty( $query_string ) ) {
				$isc_query_tag = '?isc-page=';
			} else {
				$query_string  = '?' . $query_string;
				$isc_query_tag = '&isc-page=';
			}

			if ( $min_page !== $page ) {
				?>
				<a href="<?php echo $page_link . $query_string . $isc_query_tag . ( $page - 1 ); ?>" class="prev page-numbers"><?php echo $prev_text; ?></a>
				<?php
			}

			if ( 5 < $max_page ) {
				if ( 3 < $backward_distance ) {
					?>
					<a href="<?php echo $page_link . $query_string . $isc_query_tag; ?>1" class="page-numbers">1</a>
					<span class="page-numbers dots">...</span>
					<a href="<?php echo $page_link . $query_string . $isc_query_tag . ( $page - 2 ); ?>" class="page-numbers"><?php echo $page - 2; ?></a>
					<a href="<?php echo $page_link . $query_string . $isc_query_tag . ( $page - 1 ); ?>" class="page-numbers"><?php echo $page - 1; ?></a>
					<span class="page-numbers current"><?php echo $page; ?></span>
					<?php
				} else {
					for ( $i = 1; $i <= $page; $i++ ) {
						if ( $i === $page ) {
							?>
							<span class="page-numbers current"><?php echo $i; ?></span>
							<?php
						} else {
							?>
							<a href="<?php echo $page_link . $query_string . $isc_query_tag . $i; ?>" class="page-numbers"><?php echo $i; ?></a>
							<?php
						}
					}
				}

				if ( 3 < $forward_distance ) {
					?>
					<a href="<?php echo $page_link . $query_string . $isc_query_tag . ( $page + 1 ); ?>" class="page-numbers"><?php echo $page + 1; ?></a>
					<a href="<?php echo $page_link . $query_string . $isc_query_tag . ( $page + 2 ); ?>" class="page-numbers"><?php echo $page + 2; ?></a>
					<span class="page-numbers dots">...</span>
					<a href="<?php echo $page_link . $query_string . $isc_query_tag . $max_page; ?>" class="page-numbers"><?php echo $max_page; ?></a>
					<?php
				} else {
					for ( $i = $page + 1; $i <= $max_page; $i++ ) {
						?>
						<a href="<?php echo $page_link . $query_string . $isc_query_tag . $i; ?>" class="page-numbers"><?php echo $i; ?></a>
						<?php
					}
				}
			} else {
				for ( $i = 1; $i <= $max_page; $i++ ) {
					if ( $i === $page ) {
						?>
						<span class="page-numbers current"><?php echo (int) $i; ?></span>
						<?php
					} else {
						?>
						<a href="<?php echo $page_link . $query_string . $isc_query_tag . $i; ?>" class="page-numbers"><?php echo (int) $i; ?></a>
						<?php
					}
				}
			}
			if ( $page !== $max_page ) {
				?>
				<a href="<?php echo $page_link . $query_string . $isc_query_tag . ( $page + 1 ); ?>" class="next page-numbers"><?php echo $next_text; ?></a>
				<?php
			}
			?>
		</div>
		<?php
		echo $after_links;
	}
}
