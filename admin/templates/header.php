<?php
/**
 * Header on admin pages
 *
 * @var string $title page title.
 * @var string $screen_id ID of the current screen.
 */
?>
<div id="isc-header">
	<div id="isc-header-wrapper">
		<img src="<?php echo esc_url( ISCBASEURL ) . 'admin/assets/images/image_source_control_logo_positive_512px.png'; ?>" width="256" height="28" alt="<?php esc_html_e( 'Image Source Control', 'image-source-control-isc' ); ?>"/>
		<h1><?php echo esc_html( $title ); ?></h1>
	</div>
	<div id="isc-header-links">
		<?php if ( ! \ISC\Plugin::is_pro() ) :
			echo ISC_Admin::get_pro_link( 'header-pro' );
		endif; ?>
		<?php switch ( $screen_id ) :
			case 'settings_page_isc-settings' : ?>
				<a href="<?php echo esc_url( admin_url( 'upload.php?page=isc-sources' ) ); ?>"><?php esc_html_e( 'Tools', 'image-source-control-isc' ); ?></a>
		<?php break;
		case 'media_page_isc-sources' : ?>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=isc-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'image-source-control-isc' ); ?></a>
		<?php break;
		default : ?>
				<a href="<?php echo esc_url( admin_url( 'upload.php?page=isc-sources' ) ); ?>"><?php esc_html_e( 'Tools', 'image-source-control-isc' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=isc-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'image-source-control-isc' ); ?></a>
		<?php endswitch; ?>
		<a href="<?php echo esc_url( ISC_Admin::get_manual_url( 'header-manual' ) ); ?>" target="_blank"><?php esc_html_e( 'Manual', 'image-source-control-isc' ); ?></a>
	</div>
</div>
<div class="wrap">
	<!-- the empty h2 is intentional since WordPress admin moves admin notifications toward the first h2 -->
	<h2 style="display: none;"></h2>
</div>