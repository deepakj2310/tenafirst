<?php

/**
 * Kivicare\Utility\Notice\Component class
 *
 * @package Kivicare
 */

namespace Kivicare\Utility\Notice;

use Kivicare\Utility\Component_Interface;
use Kivicare\Utility\Templating_Component_Interface;
use function add_action;

/**
 * Class for managing notice UI.
 *
 * Exposes template tags:
 *
 * @link https://wordpress.org/plugins/amp/
 */
class Component implements Component_Interface, Templating_Component_Interface
{
	/**
	 * Gets the unique identifier for the theme component.
	 *
	 * @return string Component slug.
	 */
	public function get_slug(): string
	{
		return 'notice';
	}

	/**
	 * Adds the action and filter hooks to integrate with WordPress.
	 */
	public function initialize()
	{
		
	}

	public function __construct()
	{

		// sale banner
		add_action('admin_notices',  array($this, 'iqonic_sale_banner'), 0);
		add_action('wp_ajax_iqonic_dismiss_notice', array($this, 'iqonic_dismiss_notice'), 10);
		add_action('admin_enqueue_scripts', array($this, 'iqonic_notice_enqueue_admin_script'));
	}
	
	public function template_tags(): array
	{
		return array();
	}

	public function iqonic_sale_banner()
	{
		wp_enqueue_style( 'iqonic-admin-custom', get_stylesheet_uri() );
			if (!get_user_meta(get_current_user_id(), 'iqonic_sale_banner_announcement',true)) {  ?>
				<div class="notice iqonic-notice is-dismissible" id="iqonic_sale_banner_announcement">
				<div class="iqonic-notice-message iqonic-sale">
					<a href="<?php echo esc_url('https://iqonic.design/') ?>" target="_blank"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/sale.jpg'); ?>" alt="<?php esc_attr('sale-banner', 'wp-rig' ) ?>"></a>
				</div>
					<div class="iqonic-notice-cta">
						<button class="iqonic-notice-dismiss iqonic-dismiss-welcome notice-dismiss" data-msg="iqonic_sale_banner_announcement"><span class="screen-reader-text"><?php esc_html_e('Dismiss', 'wp-rig'); ?></span></button>
					</div>
				</div>
			<?php  } 
			wp_add_inline_style( 'iqonic-admin-custom', '.iqonic-notice { background: #fff; border:none; padding:0px 0px; box-shadow:none} .iqonic-notice img { max-width:100%; width:100% } .wp-core-ui .notice.is-dismissible#iqonic_sale_banner_announcement { padding:0; } .iqonic-notice a { display:grid; } #iqonic_sale_banner_announcement.iqonic-notice .notice-dismiss:before { color: #fff;}' );
	}

	public function iqonic_dismiss_notice()
	{
				add_user_meta(get_current_user_id(), 'iqonic_sale_banner_announcement', 'true', true);
				wp_send_json_success();
	}

	public function iqonic_notice_enqueue_admin_script()
	{
		wp_enqueue_script('admin-custom', get_template_directory_uri() . '/assets/js/admin-custom.min.js', array('jquery'), 1.0 , true);
	}
}
