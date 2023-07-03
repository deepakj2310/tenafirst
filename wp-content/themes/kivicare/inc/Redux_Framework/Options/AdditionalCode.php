<?php
/**
 * Kivicare\Utility\Jetpack\Component class
 *
 * @package kivicare
 */

namespace Kivicare\Utility\Redux_Framework\Options;

use Redux;
use Kivicare\Utility\Redux_Framework\Component;

class AdditionalCode extends Component {

	public function __construct() {
		$this->set_widget_option();
	}

	protected function set_widget_option() {

		Redux::set_section( $this->opt_name, array(
			'title' => esc_html__( 'Additional Code', 'kivicare' ),
			'id'    => 'additional-Code',
			'icon'  => 'el el-css',
			'desc'  => esc_html__('This section contains options for header.','kivicare'),
			'fields'=> array(
		
				array(
					'id'       => 'kivi_css_code',
					'type'     => 'ace_editor',
					'title'    => esc_html__('CSS Code','kivicare'),
					'subtitle' => esc_html__('Paste your css code here.','kivicare'),
					'mode'     => 'css',
					'desc'     => esc_html__('Paste your custom CSS code here.','kivicare'),
				),
		
				array(
					'id'       => 'kivi_js_code',
					'type'     => 'ace_editor',
					'title'    => esc_html__('JS Code','kivicare'),
					'subtitle' => esc_html__('Paste your js code in footer.','kivicare'),
					'mode'     => 'javascript',
					'theme'   => 'chrome',
					'desc'     => esc_html__('Paste your custom JS code here.','kivicare'),
					'default' => "jQuery(document).ready(function($){\n\n});"
				),
		
			)
		));

	}
}
