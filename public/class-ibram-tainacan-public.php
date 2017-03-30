<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/l3pufg
 * @since      1.0.0
 *
 * @package    Ibram_Tainacan
 * @subpackage Ibram_Tainacan/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Ibram_Tainacan
 * @subpackage Ibram_Tainacan/public
 * @author     Rodrigo de Oliveira <emaildorodrigolg@gmail.com>
 */
class Ibram_Tainacan_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->ibram_options = get_option($this->plugin_name);
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ibram-tainacan-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * The Ibram_Tainacan_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ibram-tainacan-public.js', array( 'jquery' ), $this->version, false );
	}

    /**
     * Adds Tainacan collection's name to class
     */
	public function add_ibram_body_slug($classes) {
	    global $post;
	    if(is_singular()) {
	        $classes[] = $post->post_name;
        }

        return $classes;
    }

    /**
     * If user is trying to delete a pre-selected colleciton as 'bem permanente',
     * it will be sent automatically for modaration.
     */
    public function verify_delete_object($act, $col_id) {
	    $ibram_opts = get_option($this->plugin_name);

	    $ret = true;
        if("socialdb_collection_permission_delete_object" === $act) {
            if($ibram_opts && is_array($ibram_opts)) {
                if(intval($ibram_opts['bem_permanente']) === intval($col_id)) {
                    $ret = false;
                }
            }
        }
        return $ret;
    }

    /**
     * Moves Tainacan's item into WP Trash, instead of collection's trash
     */
    public function delete_item_permanent($obj_id, $col_id) {
        $_ret = 0;
        $ibram_opts = get_option($this->plugin_name);

        if( is_int($obj_id) && $obj_id > 0) {
            if($ibram_opts && is_array($ibram_opts)) {
                if(intval($ibram_opts['bem_permanente']) === intval($col_id)) {
                    $this->exclude_register_meta($obj_id);
                    $_ret = wp_update_post( ['ID' => $obj_id, 'post_status' => 'trash'] );
                }
            }
        }

        return $_ret;
    }

    private function exclude_register_meta($post_id) {
        $item_metas = get_post_meta($post_id);
        if(is_array($item_metas)) {
            foreach ($item_metas as $prop => $val) {
                $pcs = explode("_", $prop);
                if (($pcs[0] . $pcs[1]) == "socialdbproperty") {
                    $_term = get_term($pcs[2]);
                    $_register_term = "Número de Registro"; // TODO: check out further info over this meta
                    if($_register_term === $_term->name) {
                        delete_post_meta($post_id, $prop);
                    }
                }
            }
        }
    }

    public function trash_related_item($data, $obj_id) {
        $related_id = $this->get_related_item_id($obj_id);
        $ibram_opts = get_option($this->plugin_name);

        if( $obj_id > 0 && $ibram_opts && is_array($ibram_opts) && $related_id > 0) {
            $_set_arr = [ intval($ibram_opts['descarte']), intval($ibram_opts['desaparecimento'])];
            $colecao_id = intval($obj_id);
            if( in_array( $colecao_id, $_set_arr ) ) {
                $special_term = 'socialdb_property_' . $related_id;
                if( key_exists($special_term, $data) ) {
                    $related = $data[$special_term];
                    if(is_array($related)) {
                        foreach($related as $itm) {
                           wp_update_post(['ID' => $itm, 'post_status' => 'draft']);
                        }
                    }
                }
            }
        } // has collection id
    } // trash_related_item

    private function get_related_item_id($obj_id) {
        global $wpdb;
        $related = "Bens envolvidos";
        $related_id = 0;
        $bens_envolvidos = $wpdb->get_results("SELECT * FROM $wpdb->terms WHERE name LIKE '%$related%'");

        if( is_array($bens_envolvidos) ) {
            foreach( $bens_envolvidos as $bem_obj ) {
                if(is_object($bem_obj)) {
                    $_metas = get_term_meta($bem_obj->term_id);
                    if(is_array($_metas) && key_exists('socialdb_property_collection_id', $_metas)) {
                        $_meta_collection_id = $_metas['socialdb_property_collection_id'][0];
                        if( $_meta_collection_id === $obj_id ) {
                            $related_id = $bem_obj->term_id;
                        }
                    }
                }
            }
        }

        return $related_id;
    }

    public function set_restore_options($item_id) {
        $ibram = get_option($this->plugin_name);
        $_show_edit_buttons = true;

        if(is_array($ibram)) {
            if($item_id == $ibram['bem_permanente'] || $item_id == $ibram['descarte']) {
                $_show_edit_buttons = false;
            }
        }

        return $_show_edit_buttons;
    }

}
