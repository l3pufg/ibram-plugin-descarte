<?php
/**
 * The core IBRAM_Tainacan class.
 *
 * @since      1.0.0
 * @package    Ibram_Tainacan
 * @subpackage Ibram_Tainacan/includes
 * @author     Rodrigo de Oliveira <emaildorodrigolg@gmail.com>
 */
class Ibram_Tainacan {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Ibram_Tainacan_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'ibram-tainacan';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Ibram_Tainacan_Loader. Orchestrates the hooks of the plugin.
	 * - Ibram_Tainacan_i18n. Defines internationalization functionality.
	 * - Ibram_Tainacan_Admin. Defines all hooks for the admin area.
	 * - Ibram_Tainacan_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ibram-tainacan-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ibram-tainacan-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ibram-tainacan-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-ibram-tainacan-public.php';

		$this->loader = new Ibram_Tainacan_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Ibram_Tainacan_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Ibram_Tainacan_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Ibram_Tainacan_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Add menu item
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_ibram_tainacan_menu');

        // Add Settings link to the plugin
        $plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' );
        $this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links');

        // Save/Update IBRAM plugin options
        $this->loader->add_action('admin_init', $plugin_admin, 'options_update');

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Ibram_Tainacan_Public( $this->get_plugin_name(), $this->get_version() );
                $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
                $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
                $this->loader->add_action( 'tainacan_delete_related_item', $plugin_public, 'trash_related_item', 10, 2 );
                
                //alter home page
                $this->loader->add_action('alter_home_page', $plugin_public, 'alter_home_page');
                
                $this->loader->add_action( 'filter_search_alter', $plugin_public, 'filter_search_alter', 10, 2);
                // Filters
                $this->loader->add_filter( 'avoid-items-list-items-property-object', $plugin_public, 'verify_has_relation_in_collection',10, 3 );
                $this->loader->add_filter( 'alter_label_exclude', $plugin_public, 'alter_label_exclude',10, 1 );
                $this->loader->add_filter( 'tainacan_alter_delete_object', $plugin_public, 'set_collection_delete_object',10, 1 );
                $this->loader->add_filter( 'skip_compound_property', $plugin_public, 'verify_cancel_property_visibility',10, 2 );
                $this->loader->add_filter( 'property_is_visible', $plugin_public, 'verify_property_visibility',10, 2 );
                $this->loader->add_filter( 'body_class', $plugin_public, 'add_ibram_body_slug' );
                $this->loader->add_filter( 'tainacan_alter_permission_actions', $plugin_public, 'verify_delete_object', 10, 3 );
                $this->loader->add_filter( 'tainacan_delete_item_perm', $plugin_public, 'delete_item_permanent', 10, 2 );
                $this->loader->add_filter( 'tainacan_show_restore_options', $plugin_public, 'set_restore_options');
                $this->loader->add_filter( 'tainacan_restore_descarted_item', $plugin_public, 'restore_descarted_item', 10, 1);
                $this->loader->add_filter( 'tainacan_show_reason_modal', $plugin_public, 'show_reason_modal', 10, 1);
                $this->loader->add_filter( 'tainacan_is_bens_collection', $plugin_public, 'is_bens_collection', 10, 1);
                $this->loader->add_filter( 'limit_search_collections', $plugin_public, 'limit_search_collections', 10, 1);
                $this->loader->add_filter( 'alter_image_index_container', $plugin_public, 'alter_image_index_container', 10, 1);
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Ibram_Tainacan_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
