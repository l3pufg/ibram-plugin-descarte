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
    public function __construct($plugin_name, $version) {
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
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ibram-tainacan-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/ibram-tainacan-public.js');
    }

    /**
     * Adds Tainacan collection's name to class
     *
     * @since    1.0.0
     */
    public function add_ibram_body_slug($classes) {
        global $post;
        if (is_singular()) {
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
     * @param    int       $evt_id    Related event's id
     * @return   boolean   $_ret      Whether may delete or not
     */
    public function verify_delete_object($act, $col_id, $evt_id) {
        $ibram_opts = get_option($this->plugin_name);

        $ret = true;
        if ("socialdb_collection_permission_delete_object" === $act) {
            if ($ibram_opts && is_array($ibram_opts)) {
                $_is_set_col = intval($ibram_opts['bem_permanente']) === intval($col_id) || intval($ibram_opts['bibliografico']) === intval($col_id) || intval($ibram_opts['arquivistico']) === intval($col_id);
                if ($_is_set_col) {
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
    public function delete_item_permanent($obj_id, $col_id) {
        $_ret = 0;
        $ibram_opts = get_option($this->plugin_name);                         
                
        if (is_int($obj_id) && $obj_id > 0) {
            if ($ibram_opts && is_array($ibram_opts)) {
                $_is_set_col = intval($ibram_opts['temporario']) === intval($col_id) || intval($ibram_opts['bem_permanente']) === intval($col_id) || intval($ibram_opts['bibliografico']) === intval($col_id) || intval($ibram_opts['arquivistico']) === intval($col_id);                
                if ($_is_set_col) {
                    $this->exclude_register_meta($obj_id);
                    $_ret = wp_update_post(['ID' => $obj_id, 'post_status' => 'trash']);
                } else if (intval($ibram_opts['descarte']) === intval($col_id) || intval($ibram_opts['desaparecimento']) === intval($col_id)) {
                    $related_items = get_post_meta($obj_id, 'socialdb_related_items', true);
                    if (is_array($related_items)) {
//                        $situacao_bens_term_id = 2312;
//                        global $wpdb;
//                        $situacao_bem = "Situação - Bem";
//                        $bens_envolvidos_arr = $wpdb->get_results("SELECT * FROM $wpdb->terms WHERE name LIKE '%$situacao_bem%'");
//
//                        if (is_array($bens_envolvidos_arr)) {
//                            foreach ($bens_envolvidos_arr as $selectable) {
//                                $_title_arr = explode(" ", $selectable->name);
//                                if (count($_title_arr) == 3 && $_title_arr[0] === "Situação" && $_title_arr[2] === "Bem") {
//                                    $situacao_bens_term_id = intval($selectable->term_id);
//                                }
//                            }
//                        }

                        foreach ($related_items as $index => $itm) {
                            foreach ($itm as $id) {
                                $situacao_id = $this->get_property_id($ibram_opts[$index], 'Situação');
                                $metas = get_post_meta($id, 'socialdb_previously_situation');
                                $category_root_situacao = $this->updateStatusProperty($id, $situacao_id, $metas);
                                $tipo_situacao_id = $this->get_property_id($category_root_situacao, 'Tipo de situação',false);
                                $metas_type = get_post_meta($id, 'socialdb_previously_situation_type');
                                $this->updateStatusProperty($id, $tipo_situacao_id, $metas_type);
                            }
                            /*
                             * === Keep this comment for now ===
                             * wp_update_post(['ID' => $itm, 'post_status' => 'publish']);
                             *
                             *
                            $terms = wp_get_post_terms($itm, 'socialdb_category_type');
                            $_item_terms = [];

                            foreach ($terms as $tm) {
                                array_push($_item_terms, $tm->term_id);
                            }

                            $cat_children = $this->get_tainacan_category_children($situacao_bens_term_id);
                            $previous_set_id = 0;
                            foreach ($cat_children['ids'] as $ch) {
                                $_int_id_ = intval($ch);
                                if (in_array($_int_id_, $_item_terms)) {
                                    $previous_set_id = $_int_id_;
                                }
                            }

                            wp_remove_object_terms($itm, get_term_by('id', $previous_set_id, 'socialdb_category_type')->term_id, 'socialdb_category_type');

                            $_localizado_index = 0;
                            foreach ($cat_children['labels'] as $ind => $labl) {
                                if (strpos($labl, '1 - Localizado') !== false) {
                                    $_localizado_index = $ind;
                                }
                            }
                            $pointer = $cat_children['ids'][$_localizado_index];

                            wp_set_object_terms($itm, get_term_by('id', $pointer, 'socialdb_category_type')->term_id, 'socialdb_category_type', true);*/
                        }
                    }
                }
            }
        }
        $data['msg'] = __('The event was successful confirmed','tainacan');
        $data['type'] = 'success';
        $data['title'] = __('Success','tainacan');
        return $data;
    }
    
    /**
     * 
     * @global type $wpdb
     * @param int $id
     * @param int $situacao_property_id 
     * @param array $new_values_array
     */
    public function updateStatusProperty($id, $situacao_property_id,$new_values_array,$only_update = false) {
        global $wpdb;
        $position = $this->getValueCompound($id, $situacao_property_id);
        if (isset($position[0]) && isset($position[0][0]['values']) ) {
            foreach ($position[0][0]['values'] as $rel_ids) {
                $meta_row = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_id = " . $rel_ids);
                if (is_array($meta_row)) {
                    if($only_update){
                        $query = "UPDATE $wpdb->postmeta SET meta_value = '$only_update' WHERE meta_id = ".$meta_row[0]->meta_id;
                        $result = $wpdb->get_results($query);
                        return true;
                    }
                    // itero sobre os valores anigos em ordem decrescente para
                    // retornar o valor anterior desde que seja diferente do atual
                    for($i = (count($new_values_array)-1);$i>=0;$i--){
                        if($new_values_array[$i] != $meta_row[0]->meta_value && $new_values_array[$i] != ''){
                            //var_dump($id, $meta_row[0], 'socialdb_category_type');
                            wp_remove_object_terms($id, [absint($meta_row[0]->meta_value)], 'socialdb_category_type');
                            $query = "UPDATE $wpdb->postmeta SET meta_value = '$new_values_array[$i]' WHERE meta_id = ".$meta_row[0]->meta_id;
                            $result = $wpdb->get_results($query);
                            wp_set_object_terms($id, [absint($new_values_array[$i])], 'socialdb_category_type', true);
                            return $new_values_array[$i];
                        }
                    }

                }
            }
        }
    }

    /**
     * Excludes permanently this post meta
     *
     * @since    1.0.0
     * @param    int    $post_id   Collection id
     */
    private function exclude_register_meta($post_id) {
        $item_metas = get_post_meta($post_id);
        if (is_array($item_metas)) {
            foreach ($item_metas as $prop => $val) {
                $pcs = explode("_", $prop);
                if (($pcs[0] . $pcs[1]) == "socialdbproperty") {
                    $_term = get_term($pcs[2]);
                    $_register_term = "Número de Registro"; // TODO: check out further info over this meta
                    if ($_register_term === $_term->name) {
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
    public function trash_related_item($data, $obj_id) {
        $related = $this->get_related_item_id($data);
        $ibram_opts = get_option($this->plugin_name);

        if ($obj_id > 0 && $ibram_opts && is_array($ibram_opts) && is_array($related)) {
            $_set_arr = [intval($ibram_opts['descarte']), intval($ibram_opts['desaparecimento'])];
            $colecao_id = intval($obj_id);
            if ($obj_id > 0 || in_array($colecao_id, $_set_arr)) {
                if (is_array($related)) {
                    update_post_meta($data['object_id'], 'socialdb_related_items', $related);
                    foreach ($related as $main_index => $itm) {

                        if(is_array($itm)) {
                            foreach ($itm as $ibram_related_id) {
                                $situacao_bens_term_id = $this->get_category_id($ibram_opts[$main_index], "Situação");
                                $situacao_property_id = $this->get_property_id($ibram_opts[$main_index], 'Situação');

                                // Save last option
                                $situacao_bens_saved = $this->last_option_saved($ibram_related_id, $situacao_bens_term_id);
                                add_post_meta($ibram_related_id, "socialdb_previously_situation", $situacao_bens_saved);

                                $terms = wp_get_post_terms($ibram_related_id, 'socialdb_category_type');

                                $_item_terms = [];
                                foreach ($terms as $tm) {
                                    array_push($_item_terms, $tm->term_id);
                                }

                                $cat_children = $this->get_tainacan_category_children($situacao_bens_term_id);
                                $previous_set_id = 0;
                                $available_children = [];
                                foreach ($cat_children['ids'] as $ch) {
                                    $_int_id_ = intval($ch);
                                    if (in_array($_int_id_, $_item_terms)) {
                                        $previous_set_id = $_int_id_;
                                    } else {
                                        array_push($available_children, $_int_id_);
                                    }
                                }
                                wp_remove_object_terms($ibram_related_id, get_term_by('id', $previous_set_id, 'socialdb_category_type')->term_id, 'socialdb_category_type');

                                $_nao_localizado_index = 0;
                                $_registro_excluido_index = 0;
                                foreach ($cat_children['labels'] as $ind => $labl) {
                                    if (strpos($labl, 'Registro Excluído') !== false) {
                                        $_registro_excluido_index = $ind;
                                    } else if (strpos($labl, 'Não') !== false) {
                                        $_nao_localizado_index = $ind;
                                    }
                                }

                                $pointer = 0;
                                // se é descarte ou desaparecimento
                                if ($_set_arr[0] == $colecao_id) {
                                    $pointer = $cat_children['ids'][$_registro_excluido_index];
                                } else if ($_set_arr[1] == $colecao_id) {
                                    $pointer = $cat_children['ids'][$_nao_localizado_index];
                                }

                                $option_id = get_term_by('id', $pointer, 'socialdb_category_type')->term_id;
                                $this->updateStatusProperty($ibram_related_id, $situacao_property_id,[],$option_id);
                                wp_set_object_terms($ibram_related_id, [$option_id], 'socialdb_category_type', true);

                                $modo_option_id = $this->get_category_id($colecao_id, "Modo");
                                if (!$modo_option_id) {
                                    $modo_option_id = $this->get_category_id($colecao_id, "Tipo de ocorrência");
                                }

                                $children_modos = $this->get_tainacan_category_children($modo_option_id);
                                $selected_category = wp_get_object_terms(intval($data['object_id']), 'socialdb_category_type', array('fields' => 'ids'));
                                foreach ($children_modos['ids'] as $index => $id) {
                                    if ($selected_category && is_array($selected_category) && in_array($id, $selected_category)) {
                                        $selected_category_name = $children_modos['labels'][$index];
                                        break;
                                    }
                                }
                                
                                $sub_option_id_before = $this->get_category_id($situacao_bens_saved, "Tipo de situação", false);
                                $sub_situacao_property_id = $this->get_property_id($option_id, "Tipo de situação", false);
                                $sub_option_id = $this->get_category_id($option_id, "Tipo de situação", false);
                                // Save last option
                                $tipo_situacao_bens_saved = $this->last_option_saved($ibram_related_id, $sub_option_id_before);
                                add_post_meta($ibram_related_id, "socialdb_previously_situation_type", $tipo_situacao_bens_saved);

                                $sub_option_children = $this->get_tainacan_category_children($sub_option_id);

                                $this->remove_last_option($sub_option_children['ids'], $ibram_related_id);

                                $sub_option_children_refactored = array();
                                foreach ($sub_option_children['labels'] as $index => $label) {
                                    $sub_option_children_refactored[$label] = $sub_option_children['ids'][$index];
                                }

                                switch ($selected_category_name) {
                                    case 'Alienação':
                                        $option_id = $sub_option_children_refactored['Alienado']; // metadado para categoria 'Registro Excluído'
                                        break;
                                    case 'Cessão':
                                        $option_id = $sub_option_children_refactored['Cedido']; // metadado para categoria 'Registro Excluído'
                                        break;
                                    case 'Inutilização':
                                        $option_id = $sub_option_children_refactored['Inutilizado']; // metadado para categoria 'Registro Excluído'
                                        break;
                                    case 'Transferência':
                                        $option_id = $sub_option_children_refactored['Transferido']; // metadado para categoria 'Registro Excluído'
                                        break;
                                    case 'Extraviado':
                                        $option_id = $sub_option_children_refactored['Extraviado']; // metadado para categoria 'Não Localizado'
                                        break;
                                    case 'Furtado':
                                        $option_id = $sub_option_children_refactored['Furtado']; // metadado para categoria 'Não Localizado'
                                        break;
                                    case 'Roubado':
                                        $option_id = $sub_option_children_refactored['Roubado']; // metadado para categoria 'Não Localizado'
                                        break;
                                }
                                
                                global $wpdb;
                                add_post_meta($ibram_related_id, 'socialdb_property_'.$sub_situacao_property_id.'_cat', $option_id);
                                $value = [ 0 => [ 0 => ['type'=>'term','values'=>[$wpdb->insert_id]] ]];
                                update_post_meta($ibram_related_id, 'socialdb_property_helper_'.$sub_situacao_property_id , serialize($value));
                                wp_set_object_terms($ibram_related_id, array(intval($option_id)), 'socialdb_category_type', true);
                            }
                        }
                    }
                }
            }
        } // has collection id
    }

// trash_related_item

    public function last_option_saved($post_id, $option_id) {
        $terms = wp_get_post_terms($post_id, 'socialdb_category_type');
        if ($terms && is_array($terms)) {
            foreach ($terms as $term) {
                $hierarchy = get_ancestors($term->term_id, 'socialdb_category_type');
                if (is_array($hierarchy) && in_array($option_id, $hierarchy)) {
                    return $term->term_id;
                }
            }
        }

        return false;
    }

    public function remove_last_option($sub_options_children_ids, $post_id) {
        $terms = wp_get_post_terms($post_id, 'socialdb_category_type');

        $_item_terms = [];
        foreach ($terms as $tm) {
            array_push($_item_terms, $tm->term_id);
        }

        $previous_set_ids = [];
        $available_children = [];
        foreach ($sub_options_children_ids as $ch) {
            $_int_id_ = intval($ch);
            if (in_array($_int_id_, $_item_terms)) {
                $previous_set_ids[] = $_int_id_;
            } else {
                array_push($available_children, $_int_id_);
            }
        }

        foreach ($previous_set_ids as $previous_set_id) {
            wp_remove_object_terms($post_id, get_term_by('id', $previous_set_id, 'socialdb_category_type')->term_id, 'socialdb_category_type');
        }
    }

    public function restore_descarted_item($descard_id) {
        $ibram_opts = get_option($this->plugin_name);
        $related_items = get_post_meta($descard_id, 'socialdb_related_items');
        $related_items = $related_items[0];
        if (is_array($related_items)) {
            foreach ($related_items as $index => $id) {
                $situation = get_post_meta($id, "socialdb_previously_situation", true);
                $situation_type = get_post_meta($id, "socialdb_previously_situation_type", true);

                if ($situation) {
                    $situacao_bens_term_id = $this->get_category_id($ibram_opts[$index], "Situação");
                    $cat_children = $this->get_tainacan_category_children($situacao_bens_term_id);
                    $this->remove_last_option($cat_children['ids'], $id);

                    wp_set_object_terms($id, ((int) $situation), 'socialdb_category_type', true);
                }

                if ($situation_type) {
                    $option_id = get_term_by('id', $situation, 'socialdb_category_type')->term_id;
                    $sub_option_id = $this->get_category_id($option_id, "Tipo de situação", false);
                    $sub_option_children = $this->get_tainacan_category_children($sub_option_id);

                    $this->remove_last_option($sub_option_children['ids'], $id);

                    wp_set_object_terms($id, ((int) $situation_type), 'socialdb_category_type', true);
                }
            }
        }
    }

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
    private function get_related_item_id($data) {
        global $wpdb;
        $related = "Bens envolvidos";
        $related_id = [];
        $bens_envolvidos = $wpdb->get_results("SELECT * FROM $wpdb->terms WHERE name LIKE '%$related%'");
        $root_id = get_post_meta($data['collection_id'], 'socialdb_collection_object_type', true);
        if (is_array($bens_envolvidos)) {
            foreach ($bens_envolvidos as $bem_obj) {
                if (is_object($bem_obj)) {
                    $_metas = get_term_meta($bem_obj->term_id);
                    if (is_array($_metas) && isset($_metas['socialdb_property_created_category'][0]) && $_metas['socialdb_property_created_category'][0] == $root_id) {
                        if (key_exists("socialdb_property_compounds_properties_id", $_metas)) {
                            $sub_properties = $_metas['socialdb_property_compounds_properties_id'][0];

                            $sub_properties = explode(",", $sub_properties);
                            foreach ($sub_properties as $index => $value) {
                                $name = get_term_by('id', $value, 'socialdb_property_type')->name;
                                $name = $this->remove_accents($name);
                                $name = strtolower($name);
                                $name = end(explode(" ", $name));
                                $name = substr($name, 0, -1);

                                if (strcmp($name, "museologico") == 0) {
                                    $name = "bem_permanente";
                                }
                                // Busco todos os valores ja inseridos inclusie de outras cardinalidades
                                $positions = $this->getValueCompound($data['object_id'], $bem_obj->term_id);
                                if ($positions) {
                                    foreach ($positions as $position) {
                                        if (isset($position[$value]) && is_array($position[$value]['values']) ) {
                                            foreach ($position[$value]['values'] as $rel_ids) {
                                                $meta_row = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_id = " . $rel_ids);
                                                if (is_array($meta_row)) {
                                                    $related_id[$name][] = $meta_row[0]->meta_value;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($related_id))
            return $related_id;
        else
            return false;
    }

    private function getValueCompound($item_id, $property_id) {
        $meta = get_post_meta($item_id, 'socialdb_property_helper_' . $property_id, true);
        if ($meta && $meta != '') {
            $array = unserialize($meta);
            return $array;
        } else {
            return false;
        }
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

        if (is_array($ibram)) {
            if ($item_id == $ibram['desaparecimento'] || $item_id == $ibram['descarte']) {
                $_show_edit_buttons = false;
            }
        }

        return $_show_edit_buttons;
    }

    public function verifyUniqueField($item_id){
        $item = get_post($item_id);
        $collection = get_post($item->post_parent);

        //se for na colecao entidades
        if($collection->post_title === 'Entidades'){
            //categorias
            $terms = wp_get_post_terms($item->ID, 'socialdb_category_type');
            foreach ($terms as $tm) {
                if($tm->name === 'Pessoa'){
                    $properties = get_term_meta($tm->term_id,'socialdb_category_property_id');
                    if($this->verifyValue($properties,$item_id,'CPF')){
                        return ['title'=>__('Attention','tainacan'),'msg'=>'O cpf deste item foi utilizado!','type'=>'error'];
                    }
                }else if(strpos($tm->name,'Entidade Coletiva')){
                    $properties = get_term_meta($tm->term_id,'socialdb_category_property_id');
                    if($this->verifyValue($properties,$item_id,'CNPJ')){
                        return ['title'=>__('Attention','tainacan'),'msg'=>'O cpf deste item foi utilizado!','type'=>'error'];
                    }
                }
            }
        }
        return false;
    }

    /**
    * @param $properties
    * @param $item_id
    * @param $unique_field
    * @return bool
    */
    public function verifyValue($properties,$item_id,$unique_field){
        if($properties){
            foreach ($properties as $property_id) {
                $term = get_term_by('id',$property_id,'socialdb_property_type');
                if($term->name === $unique_field){
                    $value_property = get_post_meta($item_id,'socialdb_property_'.$term->term_id,true);
                    $json =$this->get_data_by_property_json(
                        [
                        'property_id'=>$term->term_id,
                        'term'=>$value_property
                        ]);
                    $json_decode = json_decode($json);
                    if($json_decode && is_array($json_decode) && count($json_decode) > 0){
                        foreach ($json_decode as $value) {
                            if($value->value === $value_property && $value->item_id != $item_id ){
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

     /**
     * function get_objects_by_property_json()
     * @param int Os dados vindo do formulario
     * @return json com o id e o nome de cada objeto
     * @author Eduardo Humberto
     */
    public function get_data_by_property_json($data, $meta_key = '',$is_search = false) {
        global $wpdb;
        $json =[];
        $wp_posts = $wpdb->prefix . "posts";
        $wp_postmeta = $wpdb->prefix . "postmeta";
        $has_mask = get_term_meta($data['property_id'], 'socialdb_property_data_mask', true);
        $term_relationships = $wpdb->prefix . "term_relationships";
        if ($meta_key == '') {
            $meta_key = 'socialdb_property_' . $data['property_id'];
        }
        //verifico a mascara para o metadao eh apenas na colecao
        if(($has_mask && $has_mask == 'key') || $is_search){
            $createdCategory = get_term_meta($data['property_id'], 'socialdb_property_created_category', true);
            $category_root_id = get_term_by('id',$createdCategory, 'socialdb_category_type');
            $query = "
                        SELECT pm.* FROM $wp_posts p
                        INNER JOIN $wp_postmeta pm ON p.ID = pm.post_id    
                        INNER JOIN $term_relationships t ON p.ID = t.object_id    
                        WHERE t.term_taxonomy_id = {$category_root_id->term_taxonomy_id}
                        AND p.post_status LIKE 'publish' and pm.meta_key like '$meta_key' and pm.meta_value LIKE '%{$data['term']}%'
                ";
        }else if($has_mask){
            $query = "
                        SELECT pm.* FROM $wp_posts p
                        INNER JOIN $wp_postmeta pm ON p.ID = pm.post_id    
                        WHERE p.post_status LIKE 'publish' and pm.meta_key like '$meta_key' and pm.meta_value LIKE '%{$data['term']}%'
                ";
        }else{
            return json_encode([]);
        }
        $result = $wpdb->get_results($query);
        if ($result) {
            foreach ($result as $object) {
                $json[] = array('value' => $object->meta_value, 'label' => $object->meta_value,'item_id'=>$object->post_id);
            }
        }
        return json_encode($json);
    }

    /**
     * 
     * @param array $tax_query
     */
    public function update_tax_query($tax_query) {
        $index = 0;
        $roots = ['Categorias de Bem Permanente','Bem Permanente','Categorias de Bem Bibliográfico','Bem Bibliográfico','Categorias de Bem Temporário','Bem Temporário'];
        $situacoes = ['2 - Não Localizado','1 - Localizado'];
        $ids_roots = [];
        $ids_situacoes = [];
        while(isset($tax_query[$index])){
            $ids = $tax_query[$index]['terms'];
            if(is_array($ids)){
                foreach ($ids as $value) {
                    $term = get_term_by('id', $value, 'socialdb_category_type');
                    if(in_array($term->name, $roots)){
                        $ids_roots[] = $term->term_id;
                    }else if(in_array($term->name, $situacoes)){
                        $ids_situacoes[] = $term->term_id;
                    }
                }
            }
            if(count($ids_roots)>0 && count($ids_situacoes)>0){
                unset($tax_query[$index]);
                break;
            }
            $ids_roots = [];
            $ids_situacoes = [];
            $index++;
        }
        //arrumando o array de busca
        if(count($ids_roots)>0 && count($ids_situacoes)>0){
            $tax_query[0] = array(
                'taxonomy' => 'socialdb_category_type',
                'field' => 'term_id',
                'terms' => $ids_roots,
                'operator' => 'IN'
            );
            $tax_query[] = array(
                'taxonomy' => 'socialdb_category_type',
                'field' => 'term_id',
                'terms' => $ids_situacoes,
                'operator' => 'IN'
            );
        }
        //var_dump($tax_query);die;
        return $tax_query;
    }

    public function show_reason_modal() { ?>
        <div class="modal fade" id="reasonModal" tabindex="-1" role="dialog" aria-labelledby="reasonModal" aria-hidden="true" data-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><!--Cabeçalho-->
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                            <span class="sr-only"><?php _e('Close', 'tainacan'); ?></span>
                        </button>

                        <h4 class="modal-title">Motivo</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <form id="cancelReason">
                                <textarea class="form-control" rows="8" id="reasontext" name="reasontext" onkeyup="change_button()"></textarea>
                            </form>
                        </div>
                    </div>

                    <div class="modal-footer"><!--Rodapé-->
                        <button type="button" class="btn btn-danger" data-dismiss="modal" style="float: left;">
                             <?php _e('Cancel', 'tainacan'); ?>
                        </button>

                        <button type="button" class="btn btn-primary" id="btnRemoveReason" data-id-exclude=""
                                onclick="exclude_item()" disabled>
                             <?php _e('Confirm', 'tainacan'); ?>
                        </button>

                    </div><!--Fim rodapé-->
                </div>
            </div>
        </div>
        <?php
    }

    public function alter_home_page() {
        ?>
        <div id="main_part" class="home">
            <div class="row container-fluid">
                <div class="project-info">
                    <center>
                        <h1> <?php bloginfo('name') ?> </h1>
                        <h3> <?php bloginfo('description') ?> </h3>
                    </center>
                </div>
                <div id="searchBoxIndex" class="col-md-3 col-sm-12 center">
                    <form id="formSearchCollectionsIbram" role="search">
                        <div class="input-group search-collection search-home">
                            <input style="color:white;" type="text" class="form-control" name="search_collections" id="search_collections" onfocus="changeBoxWidth(this)" placeholder="<?php _e('Find', 'tainacan') ?>"/>
                            <span class="input-group-btn">
                                <!--button class="btn btn-default" type="button"  onclick="showIbramSearch($('#search_collections').val());"><span class="glyphicon glyphicon-search"></span></button-->
                                <button class="btn btn-default" type="button"  onclick="showAdvancedSearch($('#src').val(), $('#formSearchCollectionsIbram #search_collections').val());"><span class="glyphicon glyphicon-search"></span></button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
            <?php
        }

        public function limit_search_collections() {
            $ibram = get_option($this->plugin_name);
            $indexes = [];
            if (is_array($ibram)) {
                foreach ($ibram as $index => $value) {
                    if (in_array($index, ['bem_permanente', 'temporario', 'bibliografico', 'arquivistico'])) {
                        $indexes[] = get_post_meta($value, 'socialdb_collection_object_type', true);
                    }
                }
            }
            return $indexes;
        }

        public function filter_search_alter($home_search_term, $collection_id) {
            $ibram = get_option($this->plugin_name);
            $indexes = [];
            $categories = [];
            if (is_array($ibram)) {
                foreach ($ibram as $index => $value) {
                    if (in_array($index, ['bem_permanente', 'temporario', 'bibliografico', 'arquivistico'])) {
                        $indexes[] = $value;
                        $categories[] = get_post_meta($value, 'socialdb_collection_object_type', true);
                    }
                }
            }
            ?>
            <input type="hidden" name="collection_bens" value="<?php echo implode(',', $indexes) ?>">
            <input type="hidden" name="categories" value="<?php echo implode(',', $categories); ?>">
            <input type="hidden" name="advanced_search_collection" value="0">
            <input type="hidden" name="collection_id" value="0">
            <div id="container_filtros" class="row">
                <ol class="breadcrumb">
                    <li><a href="<?php echo site_url(); ?>"> <?php _e('Repository', 'tainacan') ?> </a></li>
                    <li><a href="#" onclick="backToMainPageSingleItem()"><?php echo get_post($collection_id)->post_title; ?></a></li>
                    <li class="active"><?php echo 'Pesquisar bens'; ?></li>
                </ol>
                <div class="quadrante">
                    <h3><?php echo 'Pesquisar bens'; ?>
                        <a class="btn btn-default pull-right" href="<?php echo site_url(); ?>" ><?php _e('Back', 'tainacan'); ?></a> 
                    </h3>
                    <hr>
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="advanced_search_general" id="advanced_search_general"
                                   value="<?php print_r(empty($home_search_term) ? "" : $home_search_term ); ?>"
                                   placeholder="<?php _e('Search in all metadata', 'tainacan'); ?>">
                        </div>
                        <button type="submit" class="col-md-2 btn btn-success pull-right">
        <?php _e('Find', 'tainacan') ?>
                        </button>
                    </div>
                    <br>
                </div>          
            </div>     
            <?php
        }

        public function alter_image_index_container() {
            return plugin_dir_url(__FILE__) . '/img/03_expo.jpg';
        }

        public function is_bens_collection($collection_id) {
            $ibram = get_option($this->plugin_name);

            if (is_array($ibram)) {
                if (isset($ibram['bens']) && $collection_id == $ibram['bens']) {
                    return true;
                }
            }
            return false;
        }
    
        public function verify_property_visibility($property,$collection_id) {
            $ibram = get_option($this->plugin_name);
            if(is_array($ibram)) {
                if(( $collection_id == $ibram['descarte'] || $collection_id == $ibram['desaparecimento'] ) && $property['name'] == 'Cancelamento') {
                    return false;
                }
            }
            return true;
        }
    
    
        public function verify_cancel_property_visibility($property,$object_id) {
            $ibram = get_option($this->plugin_name);
            $collection_id = $property['metas']['socialdb_property_collection_id'];
            if(is_array($ibram)) {
                if(( $collection_id == $ibram['descarte'] || $collection_id == $ibram['desaparecimento'] ) && $property['name'] == 'Cancelamento' && get_post($object_id)->post_status == 'publish') {
                    return true;
                }
            }
            return false;
        }
        
        /**
         * 
         * @param type $collection_id
         * @return type
         */
        public function alter_label_exclude($collection_id) {
            $ibram = get_option($this->plugin_name);
            if(is_array($ibram)) {
                if(( $collection_id == $ibram['descarte'] || $collection_id == $ibram['desaparecimento'] )) {
                    //return __('Cancel item','tainacan');
                    return 'Cancelar item';
                }
            }
            //return __('Excluded item','tainacan');
            return 'Excluir item';
        }
        
        /**
         * 
         * @param type $data
         * @return boolean
         */
        public function verify_has_relation_in_collection($compound_id,$property_id,$item_id){
            $ibram = get_option($this->plugin_name);
            if($property_id === '0' || $property_id === 0){
                $property_id = $compound_id;
            }
            $collection = get_post((int)get_term_meta($property_id,'socialdb_property_collection_id',true));
            if($collection && in_array($collection->post_title, ['Coleções','Conjuntos'])){
                if($collection->post_title === 'Coleções'){
                   $con = $this->get_post_by_title('Conjuntos'); 
                   if (is_object($con) ) {
                       // busco verifico se o BEM esta vinculado na colecao conjuntos  
                        $property_id = $this->findPropertyBens($con->ID);
                        if($property_id && $this->is_selected_property($property_id, $item_id))
                           return true;
                        } 
               }
               if($collection->post_title === 'Conjuntos'){
                   $con = $this->get_post_by_title('Coleções'); 
                   if (is_object($con) ) {
                        // busco verifico se o bem esta vinculado na colecao 'colecao'
                        $property_id = $this->findPropertyBens($con->ID);
                        if($property_id && $this->is_selected_property($property_id, $item_id))
                           return true;
                   }
                }
            }
            return false;
        }
        
        private function findPropertyBens($id,$name = 'Bens') {
            $category_root_id = get_post_meta($id, 'socialdb_collection_object_type', true);
            $properties = get_term_meta($category_root_id, 'socialdb_category_property_id');
            if($properties && is_array($properties)){
                foreach ($properties as $property) {
                    $term = get_term_by('id', $property,'socialdb_property_type');
                    if(isset($term->name) && $term->name == $name)
                        return $term->term_id;
                }
            }
            return false;
        }
        
        /**
        * 
        * @param type $property_id
        * @param type $item_id
        */
       private function is_selected_property($property_id,$item_id) {
           global $wpdb;
           $wp_posts = $wpdb->prefix . "posts";
           $wp_postmeta = $wpdb->prefix . "postmeta";
           if ($meta_key == '') {
               $meta_key = 'socialdb_property_' . $property_id;
           }
           $query = "
                           SELECT pm.* FROM $wp_posts p
                           INNER JOIN $wp_postmeta pm ON p.ID = pm.post_id    
                           WHERE p.post_status LIKE 'publish' and pm.meta_key like '$meta_key' and pm.meta_value LIKE '%{$item_id}%'
                   ";
           $result = $wpdb->get_results($query);
           if ($result && is_array($result) && count(array_filter($result)) > 0) {
               return true;
           }else{
               return false;
           }
       }
       
       private function get_post_by_title($post_name, $output = OBJECT, $type = 'socialdb_collection') {
            global $wpdb;
            $post = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type='$type'", trim($post_name)));
            if ($post)
                return get_post($post, $output);

            return null;
        }

        public function set_collection_delete_object($data) {
            $ibram = get_option($this->plugin_name);
            $collection_id = $data['socialdb_event_collection_id'];
            $item_id = $data['socialdb_event_object_item_id'];
            if (strpos(get_post($collection_id)->post_title, 'Conjuntos') !== false) {
                $category_root = get_post_meta($collection_id, 'socialdb_collection_object_type', true);
                $properties = get_term_meta($category_root, 'socialdb_category_property_id');
                if ($properties) {
                    foreach ($properties as $property) {
                        if (strpos(get_term_by('id', $property, 'socialdb_property_type')->name, 'Bens') !== false) {
                            $values = get_post_meta($item_id, 'socialdb_property_' . $property);
                            if ($values && is_array($values) && count(array_filter($values)) > 0) {
                                //$data['msg'] = __('There are items selected in this set', 'tainacan');
                                $data['msg'] = 'Há entidades relacionadas a este registro';
                                $data['type'] = 'info';
                                $data['title'] = __('Attention', 'tainacan');
                                return $data;
                            }
                            // break;
                        }
                    }
                }
            }
            return false;
        }

        public function cmp($needle, $data) {
            foreach ($data as $index => $value) {
                if (preg_match("/" . $needle . "/", $index)) {
                    return $index;
                }
            }
            return false;
        }

        public function get_category_id($collection_id, $metaname, $is_root = true) {
            if ($is_root) {

                $category_root_id = get_post_meta($collection_id, 'socialdb_collection_object_type', true);
                $ids = get_term_meta($category_root_id, "socialdb_category_property_id");
            } else {
                $ids = get_term_meta($collection_id, "socialdb_category_property_id");
            }

            foreach ($ids as $id) {
                $name = get_term_by("id", $id, "socialdb_property_type")->name;

                if (strcmp($name, $metaname) == 0) {

                    $term_id = get_term_meta($id, "socialdb_property_term_root", true);
                    break;
                }
            }

            return $term_id;
        }
        
        public function get_property_id($collection_id, $metaname, $is_root = true) {
            if ($is_root) {

                $category_root_id = get_post_meta($collection_id, 'socialdb_collection_object_type', true);
                $ids = get_term_meta($category_root_id, "socialdb_category_property_id");
            } else {
                $ids = get_term_meta($collection_id, "socialdb_category_property_id");
            }
            foreach ($ids as $id) {
                $name = get_term_by("id", $id, "socialdb_property_type")->name;

                if (strcmp($name, $metaname) == 0) {

                    $term_id = get_term_by("id", $id, "socialdb_property_type")->term_id;
                    break;
                }
            }

            return $term_id;
        }
        
        public function alter_repository_api_response($response) {
            $ibram = get_option($this->plugin_name);
            $response['ibram-config'] = $ibram;
            return $response;
        }

        public function remove_accents($string) {
            return preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $string);
        }

    }
    