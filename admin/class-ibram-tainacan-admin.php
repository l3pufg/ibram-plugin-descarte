<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ibram_Tainacan
 * @subpackage Ibram_Tainacan/admin
 * @author     Rodrigo de Oliveira <emaildorodrigolg@gmail.com>
 */
class Ibram_Tainacan_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ibram-tainacan-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/ibram-tainacan-admin.js', array('jquery'), $this->version, false);
    }

    /**
     * Register IBRAM_Tainacan's option link at wordpress admin panel
     *
     * @since    1.0.0
     */
    public function add_ibram_tainacan_menu() {
        add_options_page('IBRAM Plugin', 'IBRAM Config', 'manage_options', $this->plugin_name, array($this, 'display_ibram_setup_page'));
    }

    /**
     * Adds IBRAM_Tainacan's settings shortcut at plugins' list
     *
     * @since    1.0.0
     */
    public function add_action_links($links) {
        $settings_link = array(
            '<a href="' . admin_url('options-general.php?page=' . $this->plugin_name) . '">' . __('Settings', $this->plugin_name) . '</a>',
        );
        return array_merge($settings_link, $links);
    }

    /**
     * Loads admin internal pages
     *
     * @since    1.0.0
     */
    public function display_ibram_setup_page() {
        include_once( 'partials/class-ibram-tainacan-helper.php' );
        include_once( 'partials/ibram-tainacan-admin-display.php' );
    }

    /**
     * Checks and return if plugin fields are correctly set up
     *
     * @since    1.0.0
     * @param      string    $input       The name of this plugin.
     * @return     array     $valid       An array with valid options set up
     */
    public function validate($input) {
        $valid = array();
        $valid['bem_permanente'] = (isset($input['bem_permanente']) && !empty($input['bem_permanente'])) ? $input['bem_permanente'] : 0;
        $valid['bibliografico'] = (isset($input['bibliografico']) && !empty($input['bibliografico'])) ? $input['bibliografico'] : 0;
        $valid['arquivistico'] = (isset($input['arquivistico']) && !empty($input['arquivistico'])) ? $input['arquivistico'] : 0;
        $valid['descarte'] = (isset($input['descarte']) && !empty($input['descarte'])) ? $input['descarte'] : 0;
        $valid['desaparecimento'] = (isset($input['desaparecimento']) && !empty($input['desaparecimento'])) ? $input['desaparecimento'] : 0;
        $valid['temporario'] = (isset($input['temporario']) && !empty($input['temporario'])) ? $input['temporario'] : 0;

        return $valid;
    }

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $user_sent    array data sent from user form
     * @param      string    $ret_array    Previoues filled array
     * @return     array     $ret_array    Validated values
     */
    private function validate_collection_preset($user_sent, $ret_array) {
        $cols = ['bem_permanente', 'descarte', 'desaparecimento'];
        foreach ($cols as $c => $val) {
            if (isset($user_sent[$val]) && !empty($user_sent[$val])) {
                array_push($ret_array, $user_sent[$val]);
            } else {
                array_push($ret_array, 0);
            }
        }

        return $ret_array;
    }

    /**
     * Register IBRAM_Tainacan's settings with custom validation
     *
     * @since    1.0.0
     */
    public function options_update() {
        register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
    }

}
