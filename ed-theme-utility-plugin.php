<?php
defined( 'ABSPATH' ) or die( "No script kiddies please!" );
/**
 * Plugin Name: ED Theme Utility Plugin
 * Plugin URI: http://8degreethemes.com/wordpress-plugins/
 * Description: An ultimate WordPress 8degreethemes demo importer
 * Author: 8degreethemes
 * Author URI:  http://8degreethemes.com/
 * Version: 1.0.2
 * Domain Path: /languages/
 * Text Domain: ed-theme-utility-plugin
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/** Necessary Constants **/
defined( 'EDTU_VERSION' ) or define( 'EDTU_VERSION', '1.0.0' ); //plugin version
defined( 'EDTU_TD' ) or define( 'EDTU_TD', 'ed-theme-utility-plugin' ); //plugin's text domain
defined( 'EDTU_JS_DIR' ) or define( 'EDTU_JS_DIR', plugin_dir_url( __FILE__ ) . 'js/' );  //plugin js directory
defined( 'EDTU_CSS_DIR' ) or define( 'EDTU_CSS_DIR', plugin_dir_url( __FILE__ ) . 'css/' ); // plugin css dir
defined( 'EDTU_IMPORT_DIR' ) or define( 'EDTU_IMPORT_DIR', plugin_dir_url( __FILE__ ) . 'importer/' );  //plugin demo import directory
defined( 'EDTU_PATH' ) or define( 'EDTU_PATH', plugin_dir_path( __FILE__ ) );

if(!class_exists('EDTU_Class')) :

	$importer_error = false;
	// Check if wordpress importer plugin is activated or not if not 
	if ( !defined('WP_LOAD_IMPORTERS') ) define('WP_LOAD_IMPORTERS', true);

    require_once EDTU_PATH.'importer/class-edtu-importer.php';

	class EDTU_Class extends ED_EDTU_Import {

		public $demos = array(); // Get the Demos Informations
		public $demo_dir = ''; // Path to the Demo Directory
		public $demo_dir_uri = ''; // Path to the Demo Directory
		public $options_replace_url = ''; // URL in options field to be changed
		public $option_name = ''; // Set the name for the option (useful for the theme built on themeoptions)

		public function __construct( $demos, $demo_dir, $demo_dir_uri, $option_name ) {

			$default_demo_lists = array(
				'demo1' => array(
					'title' => esc_html__('Demo 1', EDTU_TD),
					'name' => 'demo1',
					'screenshot' => EDTU_IMPORT_DIR.'demo/demo1/screen.png',
					'home_page' => 'Home',
					'menus' => array(
						'Menu 1' => 'primary'
					)
				),
			);

			/** Assigning Demos **/
			$this->demos = empty( $demos ) ? $default_demo_lists : $demos;

			/** Assigning demo folder path **/
			$this->demo_dir = ( $demo_dir == '' ) ? EDTU_PATH.'importer/demo/' : $demo_dir;
			$this->demo_dir_uri = ( $demo_dir_uri == '' ) ? get_template_directory_uri() .'importer/demo/' : $demo_dir_uri;
			
			$this->options_replace_url = ''; // Assign url that needs to replaced in the theme options value
			$this->option_name = !empty( $option_name ) ? $option_name : ''; // Assign name for the option (useful for the theme based on theme options)

            /** Loads plugin text domain for internationalization **/
			add_action('init', array($this, 'load_text_domain'));

			/** Hook Demo Importer Section to accesspress_demo_importer hook **/
			add_action('EDTU_demo_importer', array($this, 'demo_importer') );

			/** Demo Import Ajax Call **/
			add_action( 'wp_ajax_demo_import_action', array( $this, 'demo_import_callback' ) );

			/** Enqueue Styles & Scripts **/
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
		}

		/** Enqueue Necessary Plugin Scripts and Styles **/
		public function enqueue_styles_and_scripts() {

			/** Admin Styles **/
			wp_enqueue_style( 'IDM-admin', EDTU_CSS_DIR . 'admin.css', EDTU_VERSION );

			/** Admin Scripts **/
			wp_enqueue_script( 'IDM-admin', EDTU_JS_DIR . 'admin.js', array( 'jquery' ), EDTU_VERSION );

			/** Localizing the text to be used in Scripts **/
			wp_localize_script( 'IDM-admin', 'IDMObject', array(
				'ajaxurl'		=> esc_url( admin_url( 'admin-ajax.php' ) ),
				'demo_installing' => __('Installing Demo', EDTU_TD),
				'demo_installed' => __('Demo Installed', EDTU_TD),
				'demo_import_success' => __('Demo has been successfully installed', EDTU_TD),
				'demo_confirm' => __('Are you sure to import demo content ?', EDTU_TD),
			) );
		}

		/** Add Demo Importer Section to `EDTU_Class` hook **/
		public function demo_importer() {
			$demos = $this->demos;
			?>
				<h3 class="choose-demo-title"><?php echo esc_html__("Choose any one demo from the available following demos !", EDTU_TD ); ?></h3>
                <?php if(!empty($demos)) : ?>
                    <div class="all-demo-wrapper">
    				<?php foreach($demos as $demo) : $men = ''; ?>
                        <div class="demo">
        					<?php
        						foreach($demo['menus'] as $label => $id) {
        							$men .=$label.'>'.$id.'|';
        						}
        					?>
        
        					<?php if($demo['screenshot'] != '') : ?>
        						<img src="<?php echo esc_url($demo['screenshot']); ?>">
        					<?php endif; ?>
                            <div class="demoinstall-title-btn-wrap clearfix">
            					<h4><?php echo $demo['title']; ?></h4>
            					<div class="demo-install-button">
            						<a data-menu="<?php echo $men; ?>"  data-homepage="<?php echo $demo['home_page']; ?>" data-folder="<?php echo esc_attr($demo['name']); ?>" class="button demo_import_btn" href="javascript(void);"><?php echo esc_html__('Install Demo', EDTU_TD) ?></a>
            					</div>
                            </div>
                        </div>
    				<?php endforeach; ?>
                    </div>
                <?php endif; ?>
			<?php
		}

		/** Demo Import Ajax Callback **/
		public function demo_import_callback() {


			$folder = isset($_POST['folder']) ? sanitize_file_name($_POST['folder']) : '';
			$mmenu = isset($_POST['menu']) ? $_POST['menu'] : '';
			$homepage = isset($_POST['homepage']) ? esc_html($_POST['homepage']) : '';
            $demo_dir_path = $this->demo_dir;
			$path = $demo_dir_path.$folder;
            $demo_dir_path_uri = $this->demo_dir_uri;
			$path_uri = $demo_dir_path_uri.$folder;
			
			/** Site Content Import **/
			$this->fetch_attachments = true;
			$this->import($path.'/content.xml');

			/** Set Theme Options **/
			$this->set_theme_options($path.'/theme_options.json',$path_uri.'/theme_options.json');

			/** Set Customizer Options **/
			$this->set_customizer_options($path.'/customizer_options.dat');

			/** Set Widgets **/
			$this->save_widgets($path.'/widgets.wie');

			/** Set Featured Home Page **/
			if($homepage) {
				$this->set_home_page($homepage);
			}

			/** Configure Menus **/
			if( $mmenu != '' ) {
				$this->set_menus($mmenu);
			}

			die();
		}

		/** Function to Set the theme option (Demo Import) **/
		public function set_theme_options($filepath, $filepath_uri) {
			if(file_exists($filepath)) {
				$json_file = wp_remote_get( $filepath_uri );	
					
			    update_option( $this->option_name, json_decode($json_file[ 'body' ], true) );
			}

		}

		/** Function to Set the theme Customizer Options (Demo Import) **/
		public function set_customizer_options($filepath) {
		  
			if(file_exists($filepath)) {
				$opt_unserialized = file_get_contents($filepath, true);
				$options = unserialize($opt_unserialized);
                
                // Loop through the mods.
        		foreach ( $options['mods'] as $key => $val ) {
        			
        			// Save the mod.
        			set_theme_mod( $key, $val );
        		}

			}

		}

		/** Function to Save the widgets **/
		public function save_widgets($filepath) {

			if(file_exists($filepath)) {
				global $wp_registered_sidebars;

				$widget_file = file_get_contents($filepath, true);
				$data = json_decode($widget_file);
	            // Have valid data?
	            // If no data or could not decode
	            if ( empty( $data ) || ! is_object( $data ) ) {
	                wp_die(
	                        _e( 'Widget data is not available', EDTU_TD ), '', array( 'back_link' => true )
	                );
	            }

	            global $wp_registered_widget_controls;

	            $widget_controls = $wp_registered_widget_controls;

	            $available_widgets = array();

	            foreach ( $widget_controls as $widget ) {


	                if ( ! empty( $widget[ 'id_base' ] ) && ! isset( $available_widgets[ $widget[ 'id_base' ] ] ) ) { // no dupes
	                    $available_widgets[ $widget[ 'id_base' ] ][ 'id_base' ] = $widget[ 'id_base' ];
	                    $available_widgets[ $widget[ 'id_base' ] ][ 'name' ] = $widget[ 'name' ];
	                }
	            }

	            // Get all existing widget instances
	            $widget_instances = array();
	            foreach ( $available_widgets as $widget_data ) {

	                $widget_instances[ $widget_data[ 'id_base' ] ] = get_option( 'widget_' . $widget_data[ 'id_base' ] );
	            }

	            // Begin results
	            $results = array();

	            // Loop import data's sidebars
	            foreach ( $data as $sidebar_id => $widgets ) {

	                // Skip inactive widgets
	                // (should not be in export file)
	                if ( 'wp_inactive_widgets' == $sidebar_id ) {
	                    continue;
	                }

	                // Check if sidebar is available on this site
	                // Otherwise add widgets to inactive, and say so
	                if ( isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
	                    $sidebar_available = true;
	                    $use_sidebar_id = $sidebar_id;
	                    $sidebar_message_type = 'success';
	                    $sidebar_message = '';
	                } else {
	                    $sidebar_available = false;
	                    $use_sidebar_id = 'wp_inactive_widgets'; // add to inactive if sidebar does not exist in theme
	                    $sidebar_message_type = 'error';
	                    $sidebar_message = _e( 'Widget area does not exist in theme (using Inactive)', EDTU_TD );
	                }

	                // Result for sidebar
	                $results[ $sidebar_id ][ 'name' ] = ! empty( $wp_registered_sidebars[ $sidebar_id ][ 'name' ] ) ? $wp_registered_sidebars[ $sidebar_id ][ 'name' ] : $sidebar_id; // sidebar name if theme supports it; otherwise ID
	                $results[ $sidebar_id ][ 'message_type' ] = $sidebar_message_type;
	                $results[ $sidebar_id ][ 'message' ] = $sidebar_message;
	                $results[ $sidebar_id ][ 'widgets' ] = array();

	                // Loop widgets
	                foreach ( $widgets as $widget_instance_id => $widget ) {

	                    $fail = false;

	                    // Get id_base (remove -# from end) and instance ID number
	                    $id_base = preg_replace( '/-[0-9]+$/', '', $widget_instance_id );
	                    $instance_id_number = str_replace( $id_base . '-', '', $widget_instance_id );

	                    // Does site support this widget?
	                    if ( ! $fail && ! isset( $available_widgets[ $id_base ] ) ) {
	                        $fail = true;
	                        $widget_message_type = 'error';
	                        $widget_message = _e( 'Site does not support widget', EDTU_TD ); // explain why widget not imported
	                    }

	                    // Filter to modify settings object before conversion to array and import
	                    // Leave this filter here for backwards compatibility with manipulating objects (before conversion to array below)
	                    // Ideally the newer wie_widget_settings_array below will be used instead of this
	                    $widget = apply_filters( 'wie_widget_settings', $widget ); // object
	                    // Convert multidimensional objects to multidimensional arrays
	                    // Some plugins like Jetpack Widget Visibility store settings as multidimensional arrays
	                    // Without this, they are imported as objects and cause fatal error on Widgets page
	                    // If this creates problems for plugins that do actually intend settings in objects then may need to consider other approach: https://wordpress.org/support/topic/problem-with-array-of-arrays
	                    // It is probably much more likely that arrays are used than objects, however
	                    $widget = json_decode( wp_json_encode( $widget ), true );

	                    // Filter to modify settings array
	                    // This is preferred over the older wie_widget_settings filter above
	                    // Do before identical check because changes may make it identical to end result (such as URL replacements)
	                    $widget = apply_filters( 'wie_widget_settings_array', $widget );

	                    // Does widget with identical settings already exist in same sidebar?
	                    if ( ! $fail && isset( $widget_instances[ $id_base ] ) ) {

	                        // Get existing widgets in this sidebar
	                        $sidebars_widgets = get_option( 'sidebars_widgets' );
	                        $sidebar_widgets = isset( $sidebars_widgets[ $use_sidebar_id ] ) ? $sidebars_widgets[ $use_sidebar_id ] : array(); // check Inactive if that's where will go
	                        // Loop widgets with ID base
	                        $single_widget_instances = ! empty( $widget_instances[ $id_base ] ) ? $widget_instances[ $id_base ] : array();
	                        foreach ( $single_widget_instances as $check_id => $check_widget ) {

	                            // Is widget in same sidebar and has identical settings?
	                            if ( in_array( "$id_base-$check_id", $sidebar_widgets ) && ( array ) $widget == $check_widget ) {

	                                $fail = true;
	                                $widget_message_type = 'warning';
	                                // $widget_message = _e( 'Widget already exists', EDTU_TD ); // explain why widget not imported

	                                break;
	                            }
	                        }
	                    }

	                    // No failure
	                    if ( ! $fail ) {

	                        // Add widget instance
	                        $single_widget_instances = get_option( 'widget_' . $id_base ); // all instances for that widget ID base, get fresh every time
	                        $single_widget_instances = ! empty( $single_widget_instances ) ? $single_widget_instances : array( '_multiwidget' => 1 ); // start fresh if have to
	                        $single_widget_instances[] = $widget; // add it
	                        // Get the key it was given
	                        end( $single_widget_instances );
	                        $new_instance_id_number = key( $single_widget_instances );

	                        // If key is 0, make it 1
	                        // When 0, an issue can occur where adding a widget causes data from other widget to load, and the widget doesn't stick (reload wipes it)
	                        if ( '0' === strval( $new_instance_id_number ) ) {
	                            $new_instance_id_number = 1;
	                            $single_widget_instances[ $new_instance_id_number ] = $single_widget_instances[ 0 ];
	                            unset( $single_widget_instances[ 0 ] );
	                        }

	                        // Move _multiwidget to end of array for uniformity
	                        if ( isset( $single_widget_instances[ '_multiwidget' ] ) ) {
	                            $multiwidget = $single_widget_instances[ '_multiwidget' ];
	                            unset( $single_widget_instances[ '_multiwidget' ] );
	                            $single_widget_instances[ '_multiwidget' ] = $multiwidget;
	                        }

	                        // Update option with new widget
	                        update_option( 'widget_' . $id_base, $single_widget_instances );

	                        // Assign widget instance to sidebar
	                        $sidebars_widgets = get_option( 'sidebars_widgets' ); // which sidebars have which widgets, get fresh every time
	                        // Avoid rarely fatal error when the option is an empty string
	                        // https://github.com/churchthemes/widget-importer-exporter/pull/11
	                        if ( ! $sidebars_widgets ) {
	                            $sidebars_widgets = array();
	                        }

	                        $new_instance_id = $id_base . '-' . $new_instance_id_number; // use ID number from new widget instance
	                        $sidebars_widgets[ $use_sidebar_id ][] = $new_instance_id; // add new instance to sidebar

	                        update_option( 'sidebars_widgets', $sidebars_widgets ); // save the amended data
	                        // After widget import action
	                        $after_widget_import = array(
	                            'sidebar' => $use_sidebar_id,
	                            'sidebar_old' => $sidebar_id,
	                            'widget' => $widget,
	                            'widget_type' => $id_base,
	                            'widget_id' => $new_instance_id,
	                            'widget_id_old' => $widget_instance_id,
	                            'widget_id_num' => $new_instance_id_number,
	                            'widget_id_num_old' => $instance_id_number
	                        );
	                        do_action( 'wie_after_widget_import', $after_widget_import );

	                        // Success message
	                        if ( $sidebar_available ) {
	                            $widget_message_type = 'success';
	                            $widget_message = esc_html__( 'Imported', EDTU_TD );
	                        } else {
	                            $widget_message_type = 'warning';
	                            $widget_message = esc_html__( 'Imported to Inactive', EDTU_TD );
	                        }
	                    }

	                    // Result for widget instance
	                    $results[ $sidebar_id ][ 'widgets' ][ $widget_instance_id ][ 'name' ] = isset( $available_widgets[ $id_base ][ 'name' ] ) ? $available_widgets[ $id_base ][ 'name' ] : $id_base; // widget name or ID if name not available (not supported by site)
	                    $results[ $sidebar_id ][ 'widgets' ][ $widget_instance_id ][ 'title' ] = ! empty( $widget[ 'title' ] ) ? $widget[ 'title' ] : esc_html__( 'No Title', EDTU_TD ); // show "No Title" if widget instance is untitled
	                    $results[ $sidebar_id ][ 'widgets' ][ $widget_instance_id ][ 'message_type' ] = $widget_message_type;
	                    $results[ $sidebar_id ][ 'widgets' ][ $widget_instance_id ][ 'message' ] = $widget_message;
	                }
	            }
			}
			
		}

		/** Function to Set the featured Home Page **/
		public function set_home_page($homepage) {

			$fpage = get_page_by_path( $homepage );

			// Set Frontpage to display Page
	        update_option( 'show_on_front', 'page' );

	        // Set front page to display specific page
	        update_option( 'page_on_front', $fpage->ID);

		}

		/** Function to set the Menus **/
		public function set_menus($menu) {
			if( $menu != '' ) {
				global $wpdb;
				$table_db_name = $wpdb->prefix . "terms";
				$menu_ids = array();
				$save_menu = array();

				$menu = rtrim($menu, '|');
				$menu_arr = explode('|', $menu);

				foreach($menu_arr as $menu_single_ar) {
					$mens = explode('>', $menu_single_ar);

					$rows = $wpdb->get_results("SELECT * FROM $table_db_name where name='".$mens[0]."'",ARRAY_A);

					foreach($rows as $row) {
			            $menu_ids[$row["name"]] = $row["term_id"] ;
			        }

			        if ( has_nav_menu( $mens[0] ) ) {
			             continue;
			        }
			        else {
			        	$save_menu[$mens[1]] = $menu_ids[$mens[0]];
			        }
			        $save_menu[$mens[1]] = $menu_ids[$mens[0]];
				}

				set_theme_mod('nav_menu_locations', $save_menu);
			}

		}

		/** Function to change modify the url within the options **/
		public function recursive_array_replace($find, $replace, $array){
	        $newArray = array();

	        foreach ($array as $key => $value) {
		        if (!is_array($value)) {
		        	$newArray[$key] = str_replace($find, $replace, $value);
		        } else {
		        	$newArray[$key] = $this->recursive_array_replace($find, $replace, $value);
		        }
	        }
	        return $newArray;
	    }

		/**
         * Loads Plugin Text Domain
         * 
         */
        function load_text_domain() {
            load_plugin_textdomain('ed-theme-utility-plugin', false, basename(dirname(__FILE__)) . '/languages');
        }

	}

endif;