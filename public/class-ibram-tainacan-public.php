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
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ibram-tainacan-public.js');
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
                        $situacao_bens_term_id = 2312;
                        global $wpdb;
                        $situacao_bem = "Situação - Bem";
                        $bens_envolvidos_arr = $wpdb->get_results("SELECT * FROM $wpdb->terms WHERE name LIKE '%$situacao_bem%'");

                        if(is_array($bens_envolvidos_arr)) {
                            foreach($bens_envolvidos_arr as $selectable) {
                                $_title_arr = explode(" ", $selectable->name);
                                if( count($_title_arr) == 3 && $_title_arr[0] === "Situação" && $_title_arr[2] === "Bem" ) {
                                    $situacao_bens_term_id = intval($selectable->term_id);
                                }
                            }
                        }

                        foreach ($related_items as $itm) {
                            /*
                             * === Keep this comment for now ===
                             * wp_update_post(['ID' => $itm, 'post_status' => 'publish']);
                             *
                             */
                            $terms = wp_get_post_terms($itm, 'socialdb_category_type');
                            $_item_terms = [];

                            foreach ($terms as $tm) {
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
        $related = $this->get_related_item_id($data);
        $ibram_opts = get_option($this->plugin_name);

        if($obj_id > 0 && $ibram_opts && is_array($ibram_opts) && is_array($related))
        {
            $_set_arr = [intval($ibram_opts['descarte']), intval($ibram_opts['desaparecimento'])];
            $colecao_id = intval($obj_id);
            if($obj_id > 0 || in_array( $colecao_id, $_set_arr ) ) {
                if(is_array($related)) {
                    update_post_meta($data['object_id'], 'socialdb_related_items', $related);
                    foreach ($related as $main_index => $itm) {

                        $situacao_bens_term_id = $this->get_category_id($ibram_opts[$main_index], "Situação");

                        //Save last option
                        $situacao_bens_saved = $this->last_option_saved($itm, $situacao_bens_term_id);
                        add_post_meta($itm, "socialdb_previously_situation", $situacao_bens_saved);
                        
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
                        if(!$modo_option_id)
                        {
                            $modo_option_id = $this->get_category_id($colecao_id, "Tipo de ocorrência");
                        }

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

                        //Save last option
                        $tipo_situacao_bens_saved = $this->last_option_saved($itm, $sub_option_id);
                        add_post_meta($itm, "socialdb_previously_situation_type", $tipo_situacao_bens_saved);

                        $sub_option_children = $this->get_tainacan_category_children($sub_option_id);

                        $this->remove_last_option($sub_option_children['ids'], $itm);

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
                            case 'Extraviado':
                                $option_id = $sub_option_children_refactored['Extraviado'];
                                break;
                            case 'Furtado':
                                $option_id = $sub_option_children_refactored['Furtado'];
                                break;
                            case 'Roubado':
                                $option_id = $sub_option_children_refactored['Roubado'];
                                break;
                        }
                        
                        wp_set_object_terms($itm, ((int) $option_id), 'socialdb_category_type', true);
                    }
                }
            }
        } // has collection id
    } // trash_related_item

    public function last_option_saved($post_id, $option_id)
    {
        $terms = wp_get_post_terms( $post_id, 'socialdb_category_type' );
        if($terms && is_array($terms)){
            foreach ($terms as $term) {
                $hierarchy = get_ancestors($term->term_id, 'socialdb_category_type');
                if(is_array($hierarchy) && in_array($option_id, $hierarchy)){
                    return $term->term_id;
                }
            }
        }

        return false;
    }

    public function remove_last_option($sub_options_children_ids, $post_id)
    {
        $terms = wp_get_post_terms($post_id, 'socialdb_category_type');

        $_item_terms = [];
        foreach ($terms as $tm) {
            array_push($_item_terms, $tm->term_id);
        }

        $previous_set_ids = [];
        $available_children = [];
        foreach ($sub_options_children_ids as $ch)
        {
            $_int_id_ = intval($ch);
            if (in_array($_int_id_, $_item_terms))
            {
                $previous_set_ids[] = $_int_id_;
            }
            else
            {
                array_push($available_children, $_int_id_);
            }
        }

        foreach ($previous_set_ids as $previous_set_id)
        {
            wp_remove_object_terms($post_id, get_term_by('id', $previous_set_id, 'socialdb_category_type')->term_id, 'socialdb_category_type');
        }
    }
    
    public function restore_descarted_item($descard_id)
    {
        $ibram_opts = get_option($this->plugin_name);
        $related_items = get_post_meta($descard_id, 'socialdb_related_items');
        $related_items = $related_items[0];
        if(is_array($related_items))
        {
            foreach ($related_items as $index => $id)
            {
                $situation = get_post_meta($id, "socialdb_previously_situation", true);
                $situation_type = get_post_meta($id, "socialdb_previously_situation_type", true);

                if($situation)
                {
                    $situacao_bens_term_id = $this->get_category_id($ibram_opts[$index], "Situação");
                    $cat_children = $this->get_tainacan_category_children($situacao_bens_term_id);
                    $this->remove_last_option($cat_children['ids'], $id);

                    wp_set_object_terms($id, ((int)$situation), 'socialdb_category_type', true);
                }

                if($situation_type)
                {
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

        if( is_array($bens_envolvidos) ) {
            foreach( $bens_envolvidos as $bem_obj ) {
                if(is_object($bem_obj))
                {
                    $_metas = get_term_meta($bem_obj->term_id);

                    if(is_array($_metas))
                    {
                        if(key_exists("socialdb_property_compounds_properties_id", $_metas))
                        {
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
                                    $related_id[$name] = $data[$index][0];
                                }
                            }
                        }
                    }
                }
            }
        }

        if(!empty($related_id))
            return $related_id;
        else return false;
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
            if($item_id == $ibram['desaparecimento'] || $item_id == $ibram['descarte']) {
                $_show_edit_buttons = false;
            }
        }
        
        return $_show_edit_buttons;
    }

    public function show_reason_modal()
    {
        ?>
        <div class="modal fade" id="reasonModal" tabindex="-1" role="dialog" aria-labelledby="reasonModal" aria-hidden="true" data-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><!--Cabeçalho-->
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                            <span class="sr-only"><?php _e('Close', 'tainacan'); ?></span>
                        </button>

                        <h4 class="modal-title">Motivo</h4>
                    </div><!--Fim cabeçalho-->

                    <div class="modal-body">
                        <div class="form-group">
                            <form>
                                <textarea class="form-control" rows="8" id="reasontext" name="reasontext" onkeyup="change_button()"></textarea>
                            </form>
                        </div>
                    </div>

                    <div class="modal-footer"><!--Rodapé-->
                        <button type="button" class="btn btn-danger" data-dismiss="modal">
                            <?php _e('Cancel', 'tainacan'); ?>
                        </button>

                            <button type="button" class="btn btn-primary" id="btnRemoveReason" data-id-exclude=""
                                onclick="exclude_item()" disabled>
                            <?php _e('Remover', 'tainacan'); ?>
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
                                <button class="btn btn-default" type="button"  onclick="showIbramSearch($('#search_collections').val());"><span class="glyphicon glyphicon-search"></span></button>
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
        if(is_array($ibram)) {
            foreach ($ibram as $index => $value) {
                if(in_array($index, ['bem_permanente','temporario','bibliografico','arquivistico'])){
                    $indexes[] = get_post_meta($value, 'socialdb_collection_object_type', true);
                }
            }
        }
        return $indexes;
    }
    
    public function filter_search_alter($home_search_term,$collection_id) {
        $ibram = get_option($this->plugin_name);
        $indexes = [];
        $categories = [];
        if(is_array($ibram)) {
            foreach ($ibram as $index => $value) {
                if(in_array($index, ['bem_permanente','temporario','bibliografico','arquivistico'])){
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
        return plugin_dir_url( __FILE__ ).'/img/03_expo.jpg';
    }
    
    public function is_bens_collection($collection_id){
        $ibram = get_option($this->plugin_name);
        
        if(is_array($ibram)) {
            if(isset($ibram['bens']) && $collection_id == $ibram['bens']) {
                return true;
            }
        }
        return false;
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
