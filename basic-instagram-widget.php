<?php
/**
 * Plugin Name: Basic Instagram Widget
 * Description: Simply displays recent isntagram images from specified user
 * Author: Tammy Hart
 * Author URI: http://tammyhartdesigns.com
 * Version: 1.2
 * Text Domain: biw
 * Domain Path: languages
 */
 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Basic_Instagram_Widget' ) ) :

/**
 * Main Basic_Instagram_Widget Class
 *
 * @since 1.0
 */
final class Basic_Instagram_Widget {
	/** Singleton *************************************************************/

	/**
	 * @var Basic_Instagram_Widget The one true Basic_Instagram_Widget
	 * @since 1.0
	 */
	private static $instance;

	/**
	 * Main Basic_Instagram_Widget Instance
	 *
	 * Insures that only one instance of Basic_Instagram_Widget exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0
	 * @static
	 * @staticvar array $instance
	 * @uses Basic_Instagram_Widget::includes() Include the required files
	 * @see biw()
	 * @return The one true Basic_Instagram_Widget
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Basic_Instagram_Widget ) ) {
			self::$instance = new Basic_Instagram_Widget;
			self::$instance->setup_constants();
			self::$instance->load_textdomain();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.0
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'biw' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since 1.6
	 * @access protected
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'biw' ), '1.0' );
	}

	/**
	 * Setup plugin constants
	 *
	 * @access private
	 * @since 1.4
	 * @return void
	 */
	private function setup_constants() {
		
		// Plugin version
		if ( ! defined( 'BIW_VERSION' ) ) {
			define( 'BIW_VERSION', '1.0' );
		}

		// Plugin Folder Path
		if ( ! defined( 'BIW_PLUGIN_DIR' ) ) {
			define( 'BIW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Folder URL
		if ( ! defined( 'BIW_PLUGIN_URL' ) ) {
			define( 'BIW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File
		if ( ! defined( 'BIW_PLUGIN_FILE' ) ) {
			define( 'BIW_PLUGIN_FILE', __FILE__ );
		}

		// Client Acess Token
		if ( ! defined( 'BIW_CLIENT_ID' ) ) {
			define( 'BIW_CLIENT_ID', '9783646bdb41462abedf723be97a047b' );
		}
	}

	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function load_textdomain() {
		// Set filter for plugin's languages directory
		$biw_lang_dir = dirname( plugin_basename( BIW_PLUGIN_FILE ) ) . '/languages/';
		$biw_lang_dir = apply_filters( 'biw_languages_directory', $biw_lang_dir );

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale',  get_locale(), 'biw' );
		$mofile        = sprintf( '%1$s-%2$s.mo', 'biw', $locale );

		// Setup paths to current locale file
		$mofile_local  = $biw_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/biw/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/biw folder
			load_textdomain( 'biw', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/basic-instagram-widget/languages/ folder
			load_textdomain( 'biw', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'biw', false, $biw_lang_dir );
		}
	}
}

endif; // End if class_exists check


/**
 * The main function responsible for returning the one true Basic_Instagram_Widget
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $biw = biw(); ?>
 *
 * @since 1.0
 * @return object The one true Basic_Instagram_Widget Instance
 */
function biw() {
	return Basic_Instagram_Widget::instance();
}

// Get biw Running
biw();

/**
 * Load Scripts
 *
 * Enqueues the required stylesheet
 *
 * @since 1.0
 * @global $post
 * @return void
 */
function biw_load_styles() {	
	wp_enqueue_style( 'biw', BIW_PLUGIN_URL . 'biw.css' );
}
add_action( 'wp_enqueue_scripts', 'biw_load_styles' );

/**
 * The API call
 */
function biw_instagram_response( $userid = null, $count = 9, $columns = 3 ) {
	
	if ( intval( $userid ) === 0 ) {
		return '<p>No user ID specified.</p>';
	}
	
	$transient_var = 'biw_' . $userid . '_' . $count;
	
	if ( false === ( $items = get_transient( $transient_var ) ) ) {
	
		$response = wp_remote_get( 'https://api.instagram.com/v1/users/' . $userid . '/media/recent/?client_id=' . BIW_CLIENT_ID . '&count=' . esc_attr( $count ) );
	
		$response_body = json_decode( $response['body'] );
		
		//echo '<pre>'; print_r( $response_body ); echo '</pre>';
		
		if ( $response_body->meta->code !== 200 ) {
			return '<p>Incorrect user ID specified.</p>';
		}
		
		$items_as_objects = $response_body->data;
		$items = array();
		foreach ( $items_as_objects as $item_object ) {
			$item['link'] = $item_object->link;
			$item['src'] = $item_object->images->low_resolution->url;
			$items[] = $item;
		}
		
		set_transient( $transient_var, $items, 60 * 60 );
	}
	
	$output = '<ul class="biw-items biw-columns-' . esc_attr( $columns ) . '">';
	
	foreach ( $items as $item ) {
		$link	= $item['link'];
		$image	= $item['src'];
		$output	.= '<li><a href="' . esc_url( $link ) .'"><img src="' . esc_url( $image ) . '" /></a></li>';
	}
	
	$output .= '</ul>';
	
	return $output;
}

/**
 * The Shortcode
 */
add_shortcode( 'basic_instagram', 'biw_shortcode' );
function biw_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'id' 		=> 0,
		'count' 	=> 9,
		'columns'	=> 3
	), $atts, 'basic_instagram' );
	
	$userid		= $atts['id'];
	$count		= $atts['count'];
	$columns	= $atts['columns']; 
	
	return biw_instagram_response( $userid, $count, $columns );
}

/**
 * The Widget
 */
class BIW_Widget extends WP_Widget {
	
	var	$title	= 'Instagram',
		$desc	= 'A widget for displaying recent images from a specified user';
	
	/** constructor */
	function biw_widget() {
		parent::WP_Widget( 'name', $this->title, array( 'description' => $this->desc ) );
	}

	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract( $args ); // $before_widget, $after_widget, $before_title, after_title
		$title		= isset( $instance['title'] )	? $instance['title']	: $this->title;
		$userid		= isset( $instance['userid'] )	? $instance['userid']	: 0;
		$count		= isset( $instance['count'] )	? $instance['count']	: 9;
		$columns	= isset( $instance['columns'] )	? $instance['columns']	: 3;
		
		// the output
		echo $before_widget;
		echo $before_title . $title . $after_title;
		echo biw_instagram_response( $userid, $count, $columns );
		echo $after_widget;
	}

	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']		= sanitize_text_field( $new_instance['title'] );
		$instance['userid']		= intval( $new_instance['userid'] );
		$instance['count']		= intval( $new_instance['count'] );
		$instance['columns']	= intval( $new_instance['columns'] );
		return $instance;
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
		$title 		= isset( $instance['title'] ) 	? $instance['title'] 	: $this->title;
		$userid		= isset( $instance['userid'] )	? $instance['userid']	: 0;
		$count		= isset( $instance['count'] )	? $instance['count']	: 9;
		$columns	= isset( $instance['columns'] )	? $instance['columns']	: 3;

		?>
		<p><?php esc_html_e( $this->desc ); ?></p>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Widget Title:</label><br />
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'userid' ); ?>">User ID: (<a href="http://jelled.com/instagram/lookup-user-id" target="_blank">Lookup your User ID</a>)</label><br />
			<input type="text" id="<?php echo $this->get_field_id( 'userid' ); ?>" name="<?php echo $this->get_field_name( 'userid' ); ?>" value="<?php echo esc_attr( $userid ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'count' ); ?>">Count: (1-30)</label><br />
			<input type="text" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" value="<?php echo esc_attr( $count ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'columns' ); ?>">Columns: (1-5)</label><br />
			<input type="text" id="<?php echo $this->get_field_id( 'columns' ); ?>" name="<?php echo $this->get_field_name( 'columns' ); ?>" value="<?php echo esc_attr( $columns ); ?>" />
		</p>
		<?php
	}

}

add_action( 'widgets_init', 'biw_register_widget' );
function biw_register_widget() {
	register_widget( 'BIW_Widget' );
}