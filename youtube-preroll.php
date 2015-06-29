<?php
/*
Plugin Name: YoutubePreroll
Plugin URI: https://www.bingel.me/
Description: Integrates Youtube preroll video functionality into your WordPress install. Settings under Settings - Media
Version: 0.1
Author: mbingel
Author URI: https://www.bingel.me/
*/

defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );

if ( ! class_exists( 'YoutubePreroll' ) ) {
	class YoutubePreroll
	{
		/**
		 * Tag identifier used by file includes and selector attributes.
		 * @var string
		 */
		protected $tag = 'youtube-preroll';

		/**
		 * User friendly name used to identify the plugin.
		 * @var string
		 */
		protected $name = 'YoutubePreroll';

		/**
		 * Current version of the plugin.
		 * @var string
		 */
		protected $version = '0.1';

		/**
		 * List of options to determine plugin behaviour.
		 * @var array
		 */
		protected $options = array();

		/**
		 * List of options to be used by Javascript.
		 * @var array
		 */
		protected $local_options = array();

		/**
		 * List of settings displayed on the admin settings page.
		 * @var array
		 */
		protected $settings = array(
			'preroll' => array(
				'description' => 'Default pre-roll video. Empty if none. Can be overwritten by the shortcode.',
				'placeholder' => ''
			),
			/*
			'autostart' => array(
				'description' => 'Default autostart behaviour. 0=no, 1=yes. Can be overwritten by the shortcode.',
				'validator' => 'numeric',
				'placeholder' => 0
			),
			'controls' => array(
				'description' => 'Default controls behaviour. 0=hide, 1=show. Can be overwritten by the shortcode.',
				'validator' => 'numeric',
				'placeholder' => 0
			),
			*/
			'width' => array(
				'description' => 'Default width. 0 for none. Can be overwritten by the shortcode.',
				'validator' => 'numeric',
				'placeholder' => 0
			),
			'height' => array(
				'description' => 'Default height. 0 for none. Can be overwritten by the shortcode.',
				'validator' => 'numeric',
				'placeholder' => 0
			)
		);

		/**
		 * Initiate the plugin by setting the default values and assigning any
		 * required actions and filters.
		 *
		 * @access public
		 */
		public function __construct()
		{
			if ( $options = get_option( $this->tag ) ) {
				$this->options = $options;
			}
			add_shortcode( $this->tag, array( &$this, 'shortcode' ) );
			if ( is_admin() ) {
				add_action( 'admin_init', array( &$this, 'settings' ) );
			}
		}

		/**
		 * Allow the teletype shortcode to be used.
		 *
		 * @access public
		 * @param array $atts
		 * @param string $content
		 * @return string
		 */
		public function shortcode( $atts, $content = null )
		{
			extract( shortcode_atts( array(
				'video' => false,
				'preroll' => false,
				'autostart' => false,
				'controls' => false,
				'height' => false,
				'width' => false,
				'class' => false,
				'style' => false
			), $atts ) );

	 		// Enqueue the required styles and scripts...
			$this->_enqueue();

	 		// Add custom styles...
			$styles = array();
			if ( is_numeric( $height ) && ($height > 0) ) {
				$styles[] = esc_attr( 'height: ' . $height . 'px;' );
			}
			if ( is_numeric( $width ) && ($width > 0) ) {
				$styles[] = esc_attr( 'width: ' . $width . 'px;' );
			}
			if ( !empty( $style ) ) {
				$styles[] = esc_attr( $style );
			}

	 		// Build the list of class names...
			$classes = array(
				$this->tag
			);
			if ( !empty( $class ) ) {
				$classes[] = esc_attr( $class );
			}

			// set preroll by settings if not present in shortcode
	 		if (!isset($preroll) || strlen($preroll) == 0) {
	 			$preroll = $this->options['preroll'];
	 		}
	 		// clean preroll variable
	 		if (strpos($preroll, 'youtube') !== false) {
	 			$parts = parse_url($preroll);
				parse_str($parts['query'], $query);
	 			$preroll = $query['v'];
	 		}
	 		if (strpos($preroll, 'youtu.be') !== false) {
	 			$preroll = parse_url($preroll, PHP_URL_PATH);
	 			$preroll = substr($preroll, 1);
	 		}

	 		// clean video variable
	 		if (strpos($video, 'youtube') !== false) {
	 			$parts = parse_url($video);
				parse_str($parts['query'], $query);
	 			$video = $query['v'];
	 		}
	 		if (strpos($video, 'youtu.be') !== false) {
	 			$video = parse_url($video, PHP_URL_PATH);
	 			$video = substr($video, 1);
	 		}

			$local_options['preroll'] = $preroll;
			$local_options['mainroll'] = $video;
			$local_options['width'] = $width;
			$local_options['height'] = $height;

	 		// Output the terminal...	        
			ob_start();
			?>


<p>Player below</p>
<script>youtube_preroll_options = <?php echo json_encode($local_options);?>;</script>
<div id="youtube_preroll_player" class="<?php esc_attr_e( implode( ' ', $classes ) );?>"<?php echo ( count( $styles ) > 0 ? ' style="' . implode( ' ', $styles ) . '"' : '' );?>></div>
<p><?php echo $content; ?></p>





<?php
			return ob_get_clean();
		}

		/**
		 * Add the setting fields to the settings page.
		 *
		 * @access public
		 */
		public function settings()
		{
			// use the media section for the settings
			$section = 'media';
			add_settings_section(
				$this->tag . '_settings_section',
				$this->name . ' Settings',
				function () {
					echo '<p>Configuration options for the ' . esc_html( $this->name ) . ' plugin.</p>';
				},
				$section
			);
			foreach ( $this->settings AS $id => $options ) {
				$options['id'] = $id;
				add_settings_field(
					$this->tag . '_' . $id . '_settings',
					$id,
					array( &$this, 'settings_field' ),
					$section,
					$this->tag . '_settings_section',
					$options
				);
			}
			register_setting(
				$section,
				$this->tag,
				array( &$this, 'settings_validate' )
			);
		}

		/**
		 * Append a settings field to the the fields section.
		 *
		 * @access public
		 * @param array $args
		 */
		public function settings_field( array $options = array() )
		{
			$atts = array(
				'id' => $this->tag . '_' . $options['id'],
				'name' => $this->tag . '[' . $options['id'] . ']',
				'type' => ( isset( $options['type'] ) ? $options['type'] : 'text' ),
				'class' => 'small-text',
				'value' => ( array_key_exists( 'default', $options ) ? $options['default'] : null )
			);
			if ( isset( $this->options[$options['id']] ) ) {
				$atts['value'] = $this->options[$options['id']];
			}
			if ( isset( $options['placeholder'] ) ) {
				$atts['placeholder'] = $options['placeholder'];
			}
			if ( isset( $options['type'] ) && $options['type'] == 'checkbox' ) {
				if ( $atts['value'] ) {
					$atts['checked'] = 'checked';
				}
				$atts['value'] = true;
			}
			array_walk( $atts, function( &$item, $key ) {
				$item = esc_attr( $key ) . '="' . esc_attr( $item ) . '"';
			} );
			?>
			<label>
				<input <?php echo implode( ' ', $atts ); ?> />
				<?php if ( array_key_exists( 'description', $options ) ) : ?>
				<?php esc_html_e( $options['description'] ); ?>
				<?php endif; ?>
			</label>
			<?php
		}

		/**
		 * Validate the settings saved.
		 *
		 * @access public
		 * @param array $input
		 * @return array
		 */
		public function settings_validate( $input )
		{
			$errors = array();
			foreach ( $input AS $key => $value ) {
				if ( $value == '' ) {
					unset( $input[$key] );
					continue;
				}
				$validator = false;
				if ( isset( $this->settings[$key]['validator'] ) ) {
					$validator = $this->settings[$key]['validator'];
				}
				switch ( $validator ) {
					case 'numeric':
						if ( is_numeric( $value ) ) {
							$input[$key] = intval( $value );
						} else {
							$errors[] = $key . ' must be a numeric value.';
							unset( $input[$key] );
						}
					break;
					default:
						 $input[$key] = strip_tags( $value );
					break;
				}
			}
			if ( count( $errors ) > 0 ) {
				add_settings_error(
					$this->tag,
					$this->tag,
					implode( '<br />', $errors ),
					'error'
				);
			}
			return $input;
		}

		/**
		 * Enqueue the required scripts and styles, only if they have not
		 * previously been queued.
		 *
		 * @access public
		 */
		protected function _enqueue()
		{
	 		// Define the URL path to the plugin...
			$plugin_path = plugin_dir_url( __FILE__ );

			wp_register_script( 'youtube-iframe-api', 'https://www.youtube.com/iframe_api' );
			wp_register_script( 'youtube-preroll', $plugin_path.'youtube-preroll.js', array( 'youtube-iframe-api' ));
			//wp_localize_script( 'youtube-preroll', 'youtube_preroll_options', $local_options );

	 		// Enqueue the scripts if not already...
			wp_enqueue_script( 'youtube-preroll' );  
		}

	}
	new YoutubePreroll;
}
