<?php
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
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ibram-tainacan-public.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Adds Tainacan collection's name to class
     *
     * @since    1.0.0
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
     *
     * @since    1.0.0
     * @param    string    $act       Sent controller action
     * @param    int       $col_id    Collection id
     * @return   boolean   $_ret      Whether may delete or not
     */
    public function verify_delete_object($act, $col_id) {
        $ibram_opts = get_option($this->plugin_name);

        $ret = true;
        if("socialdb_collection_permission_delete_object" === $act) {
            if($ibram_opts && is_array($ibram_opts)) {
                $_is_set_col = intval($ibram_opts['bem_permanente']) === intval($col_id) || intval($ibram_opts['bibliografico']) === intval($col_id) || intval($ibram_opts['arquivistico']) === intval($col_id);
                if($_is_set_col) {
                    $ret = false;
                }
            }
        }
        return $ret;
    }

    /**
     * Moves Tainacan's item into WP Trash, instead of collection's trash
     *
     * @since    1.0.0
     * @param    string   $obj_id    Item id
     * @param    array    $col_id    Collection id
     * @return   int      $_ret      Affected post id
     */
    public function delete_item_permanent($obj_id, $col_id)
    {
        $_ret = 0;
        $ibram_opts = get_option($this->plugin_name);

        if( is_int($obj_id) && $obj_id > 0) {
            if($ibram_opts && is_array($ibram_opts)) {
                $_is_set_col = intval($ibram_opts['bem_permanente']) === intval($col_id) || intval($ibram_opts['bibliografico']) === intval($col_id) || intval($ibram_opts['arquivistico']) === intval($col_id);
                if($_is_set_col) {
                    $this->exclude_register_meta($obj_id);
                    $_ret = wp_update_post( ['ID' => $obj_id, 'post_status' => 'trash'] );
                } else if(intval($ibram_opts['descarte']) === intval($col_id)) {
                    $related_items = get_post_meta($obj_id, 'socialdb_related_items', true);
                    if(is_array($related_items)) {
                        foreach ($related_items as $itm) {
                            /*
                             * === Keep this comment for now ===
                             * wp_update_post(['ID' => $itm, 'post_status' => 'publish']);
                             *
                             * TODO: Change the way to get the correct ID
                             */
                            $situacao_bens_term_id = 2312;
                            $terms = wp_get_post_terms($itm, 'socialdb_category_type');
                            $_item_terms = [];

                            foreach ($terms as $tm)
                            {
                                array_push($_item_terms, $tm->term_id);
                            }

                            $cat_children = $this->get_tainacan_category_children($situacao_bens_term_id);
                            $previous_set_id = 0;
                            foreach ($cat_children['ids'] as $ch)
                            {
                                $_int_id_ = intval($ch);
                                if( in_array($_int_id_, $_item_terms) )
                                {
                                    $previous_set_id = $_int_id_;
                                }
                            }

                            wp_remove_object_terms($itm, get_term_by('id', $previous_set_id, 'socialdb_category_type')->term_id, 'socialdb_category_type');

                            $_localizado_index = 0;
                            foreach ($cat_children['labels'] as $ind => $labl) {
                                if( strpos($labl, '1 - Localizado') !== false ) {
                                    $_localizado_index = $ind;
                                }
                            }
                            $pointer = $cat_children['ids'][$_localizado_index];

                            wp_set_object_terms($itm, get_term_by('id', $pointer, 'socialdb_category_type')->term_id, 'socialdb_category_type', true);
                        }
                    }
                }
            }
        }

        return $_ret;
    }

    /**
     * Excludes permanently this post meta
     *
     * @since    1.0.0
     * @param    int    $post_id   Collection id
     */
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

    /**
     * Sends items related to this collection to trash
     *
     * @param    array    $data         Collection data sent from form
     * @param    string   $obj_id       Collection id
     * @since    1.0.0
     */
    public function trash_related_item($data, $obj_id)
    {
        $related = $this->get_related_item_id($obj_id, $data["object_id"], $data);
        $ibram_opts = get_option($this->plugin_name);

        if($obj_id > 0 && $ibram_opts && is_array($ibram_opts) && ($related > 0 && is_array($related)))
        {
            $_set_arr = [intval($ibram_opts['descarte']), intval($ibram_opts['desaparecimento'])];
            $colecao_id = intval($obj_id);
            if($obj_id > 0 || in_array( $colecao_id, $_set_arr ) ) {
                if(is_array($related)) {
                    update_post_meta($data['object_id'], 'socialdb_related_items', $related);
                    foreach ($related as $main_index => $itm) {
                        /*
                         * === Keep this comment for now ===
                         * wp_update_post(['ID' => $itm, 'post_status' => 'draft']);
                         *
                         * TODO: Change the way to get the correct ID
                         */

                        $situacao_bens_term_id = $this->get_category_id($ibram_opts[$main_index], "Situação");
                        $terms = wp_get_post_terms($itm, 'socialdb_category_type');

                        $_item_terms = [];
                        foreach ($terms as $tm) {
                            array_push($_item_terms, $tm->term_id);
                        }

                        $cat_children = $this->get_tainacan_category_children($situacao_bens_term_id);
                        $previous_set_id = 0;
                        $available_children = [];
                        foreach ($cat_children['ids'] as $ch)
                        {
                            $_int_id_ = intval($ch);
                            if (in_array($_int_id_, $_item_terms))
                            {
                                $previous_set_id = $_int_id_;
                            }
                            else
                            {
                                array_push($available_children, $_int_id_);
                            }
                        }

                        wp_remove_object_terms($itm, get_term_by('id', $previous_set_id, 'socialdb_category_type')->term_id, 'socialdb_category_type');

                        $_nao_localizado_index = 0;
                        $_registro_excluido_index = 0;
                        foreach ($cat_children['labels'] as $ind => $labl)
                        {
                            if (strpos($labl, 'Registro Excluído') !== false)
                            {
                                $_registro_excluido_index = $ind;
                            }
                            else if (strpos($labl, 'Não') !== false)
                            {
                                $_nao_localizado_index = $ind;
                            }
                        }

                        $pointer = 0;
                        /* TODO: also improve this part */
                        // se é descarte ou desaparecimento
                        if ($_set_arr[0] == $colecao_id)
                        {
                            $pointer = $cat_children['ids'][$_registro_excluido_index];
                        }
                        else if ($_set_arr[1] == $colecao_id)
                        {
                            $pointer = $cat_children['ids'][$_nao_localizado_index];
                        }

                        $option_id = get_term_by('id', $pointer, 'socialdb_category_type')->term_id;
                        wp_set_object_terms($itm, $option_id, 'socialdb_category_type', true);

                        $modo_option_id = $this->get_category_id($colecao_id, "Modo");
                        $children_modos = $this->get_tainacan_category_children($modo_option_id);
                        $selected_category = $data['selected_categories'];
                        foreach ($children_modos['ids'] as $index => $id)
                        {
                            if($selected_category == $id)
                            {
                                $selected_category_name = $children_modos['labels'][$index];
                                break;
                            }
                        }

                        $sub_option_id = $this->get_category_id($option_id, "Tipo de situação", false);
                        $sub_option_children = $this->get_tainacan_category_children($sub_option_id);

                        $sub_option_children_refactored = array();
                        foreach ($sub_option_children['labels'] as $index => $label)
                        {
                            $sub_option_children_refactored[$label] = $sub_option_children['ids'][$index];
                        }

                        switch ($selected_category_name)
                        {
                            case 'Alienação':
                                $option_id = $sub_option_children_refactored['Alienado'];
                                break;
                            case 'Cessão':
                                $option_id = $sub_option_children_refactored['Cedido'];
                                break;
                            case 'Inutilização':
                                $option_id = $sub_option_children_refactored['Inutilizado'];
                                break;
                            case 'Tranferência':
                                $option_id = $sub_option_children_refactored['Tranferido'];
                                break;
                        }

                        wp_set_object_terms($itm, ((int) $option_id), 'socialdb_category_type', true);
                    }
                }
            }
        } // has collection id
    } // trash_related_item


    public function get_tainacan_category_children($parent_id) {
        global $wpdb;
        $data = [];
        $wp_term_taxonomy = $wpdb->prefix . "term_taxonomy";
        $query = "SELECT * FROM $wpdb->terms t INNER JOIN $wp_term_taxonomy tt ON t.term_id = tt.term_id
				WHERE tt.parent = {$parent_id}  ORDER BY tt.count DESC,t.name ASC";
        $result = $wpdb->get_results($query);
        if ($result && !empty($result)) {
            foreach ($result as $term) {
                $data['ids'][] = $term->term_id;
                $data['labels'][] = $term->name;
            }
        }
        return $data;
    }

    /**
     * Get this particular id related to this collection
     *
     * @since    1.0.0
     * @param    string    $obj_id          Collection id
     * @return   boolean   $related_id      Term meta id related to this collection
     */
    private function get_related_item_id($obj_id, $item_id, $data) {
        global $wpdb;
        $related = "Bens envolvidos";
        $related_id = 0;
        $bens_envolvidos = $wpdb->get_results("SELECT * FROM $wpdb->terms WHERE name LIKE '%$related%'");
        if( is_array($bens_envolvidos) ) {
            foreach( $bens_envolvidos as $bem_obj ) {
                if(is_object($bem_obj))
                {
                    $_metas = get_term_meta($bem_obj->term_id);
                    $related_id = [];
                    $related_id['comp'] = $bem_obj->term_id;

                    if(is_array($_metas))
                    {
                        if(key_exists("socialdb_property_compounds_properties_id", $_metas))
                        {
                            $related_id = [];
                            $sub_properties = $_metas['socialdb_property_compounds_properties_id'][0];

                            $sub_properties = explode(",", $sub_properties);
                            foreach ($sub_properties as $index => $value)
                            {
                                $name = get_term_by('id',$value,'socialdb_property_type')->name;
                                $name = $this->remove_accents($name);
                                $name = strtolower($name);
                                $name = end(explode(" ", $name));
                                $name = substr($name, 0, -1);

                                if(strcmp($name, "museologico") == 0)
                                {
                                    $name = "bem_permanente";
                                }

                                if($index = $this->cmp("socialdb_property_".$bem_obj->term_id."_".$value."_0", $data ))
                                {
                                    $ids[$name] = $data[$index];
                                }
                            }
                        }
                        else if(key_exists('socialdb_property_collection_id', $_metas))
                        {
                            $_meta_collection_id = $_metas['socialdb_property_collection_id'][0];
                            if( $_meta_collection_id === $obj_id )
                            {
                                $related_id = $bem_obj->term_id;
                            }
                        }
                    }
                }
            }
        }

        foreach ($ids as $index => $id)
        {
            if($id)
                $related_id[$index] = $id[0];
        }
        return $related_id;
    }

    /**
     * Checks if collection may be restored
     *
     * @since    1.0.0
     * @param    string    $item_id                  The name of this plugin.
     * @return   boolean   $_show_edit_buttons       Whether can or not
     */
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

    public function cmp($needle, $data)
    {
        foreach($data as $index => $value)
        {
            if(preg_match("/".$needle."/", $index))
            {
                return $index;
            }
        }
        return false;
    }

    public function get_category_id($collection_id, $metaname, $is_root = true)
    {
        if($is_root)
        {

            $category_root_id = get_post_meta($collection_id, 'socialdb_collection_object_type', true);
            $ids = get_term_meta($category_root_id, "socialdb_category_property_id");
        }else
        {
            $ids = get_term_meta($collection_id, "socialdb_category_property_id");
        }

        foreach ($ids as $id)
        {
            $name = get_term_by("id", $id, "socialdb_property_type")->name;

            if(strcmp($name,$metaname) == 0)
            {

                $term_id = get_term_meta($id, "socialdb_property_term_root", true);
                break;
            }
        }

        return $term_id;
    }

    public function remove_accents($string){
        return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"),$string);
    }
}
