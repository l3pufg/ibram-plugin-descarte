<?php
/**
 * Created by PhpStorm.
 * User: desenvolvedor
 * Date: 25/08/2017
 * Time: 10:33
 */

class PersistMethodsImportDataSet{

    /**
     * @param $collection
     * @return int|WP_Error
     */
    public static function manageCollection($collection){
         $collection_post = $collection['collection'];
         $collection_settings = $collection['metadata'];
         $has_id = MappingImportDataSet::hasMap('collections',$collection_post['ID']);
         $post = array(
            'post_title' => $collection_post['post_title'],
            'post_content' => $collection_post['post_content'],
            'post_status' => 'publish');
         if($has_id){
             $post['ID'] = $has_id;
             $collection_id = wp_update_post($post);
         }else{
             $post['post_type'] = 'socialdb_collection';
             $collection_id = wp_insert_post($post);
             MappingImportDataSet::addMap('collections',$collection_post['ID'],$collection_id);
         }

         $category_root_id = $collection_settings['category_root_id'];
         $has_category_root_id =  MappingImportDataSet::hasMap('categories',$category_root_id);
         if($has_category_root_id){
             update_post_meta($collection_id, 'socialdb_collection_object_type', $has_category_root_id);
         }else{
             $args = ['term'=>['name'=> $collection_post['post_title']]];
             $object_type = self::updateCategory($args,IBRAM_VERSION);
             update_post_meta($collection_id, 'socialdb_collection_object_type', $object_type);
         }

         update_post_meta($collection_id,'socialdb_token',IBRAM_VERSION);
         return $collection_id;
    }

    /**
     * @param $collection_id
     * @param $collection_post
     * @param $collection_settings
     */
    public function collectionMetas($collection_id,$collection_post,$collection_settings){
        update_post_meta($collection_id, 'socialdb_collection_default_ordering',$collection_settings['default_ordenation']);
        update_post_meta($collection_id, 'socialdb_collection_address',$collection_settings['address']);
        update_post_meta($collection_id, 'socialdb_collection_ordenation_form',$collection_settings['ordenation_form']);
        update_post_meta($collection_id, 'socialdb_collection_license',$collection_settings['license']);
        update_post_meta($collection_id, 'socialdb_collection_columns',$collection_settings['columns']);
        update_post_meta($collection_id, 'socialdb_collection_allow_hierarchy',$collection_settings['allow_hierarchy']);

        $parent = $collection_settings['collection_parent'];
        if($parent){
            $has_id_parent = MappingImportDataSet::hasMap('collections',$parent);
            if($has_id_parent){
                update_post_meta($collection_id, 'socialdb_collection_parent',$has_id_parent);
            }else{
                global $wpdb;
                update_post_meta($collection_id, 'socialdb_collection_parent',$collection_settings['collection_parent'].'_reference');
                MappingImportDataSet::addMap('references-collections',$wpdb->insert_id, $collection_settings['collection_parent']);
            }
        }



        update_post_meta($collection_id, 'socialdb_collection_license_pattern',$collection_settings['license_pattern']);
        update_post_meta($collection_id, 'socialdb_collection_license_enabled',$collection_settings['license_enabled']);
        update_post_meta($collection_id, 'socialdb_collection_list_mode',$collection_settings['list_mode']);
        //update_post_meta($collection_id, 'socialdb_collection_address',$collection_settings['add_watermark']);
        update_post_meta($collection_id, 'socialdb_collection_moderation_type',$collection_settings['moderation_type']);
        update_post_meta($collection_id, 'socialdb_collection_object_name',$collection_settings['item_name']);
        update_post_meta($collection_id, 'socialdb_collection_download_control',$collection_settings['download_control']);
        update_post_meta($collection_id, 'socialdb_collection_submission_visualization',$collection_settings['submission_visualization']);
        update_post_meta($collection_id, 'socialdb_collection_visualization_page_category',$collection_settings['visualization_page_category']);
        update_post_meta($collection_id, 'socialdb_collection_habilitate_media',$collection_settings['habilitate_media']);
        update_post_meta($collection_id, 'socialdb_collection_item_visualization',$collection_settings['item_visualization']);
        update_post_meta($collection_id, 'socialdb_default_color_scheme',$collection_settings['default_color_scheme']);
        update_post_meta($collection_id, 'socialdb_collection_show_header',$collection_settings['show_header']);
        update_post_meta($collection_id, 'socialdb_collection_add_item',$collection_settings['add_item']);
        //update_post_meta($collection_id, 'socialdb_collection_vinculated_object',$collection_settings['vinculated_items']);

        update_post_meta($collection_id, 'socialdb_collection_moderator',$collection_settings['moderators']);

        update_post_meta($collection_id, 'socialdb_collection_color_scheme', ( $collection_settings['color_scheme']) ? serialize( $collection_settings['color_scheme']) : '');
        update_post_meta($collection_id, 'socialdb_collection_slideshow_time',$collection_settings['slideshow_time']);
        update_post_meta($collection_id, 'socialdb_collection_use_prox_mode',$collection_settings['use_prox_mode']);
        update_post_meta($collection_id, 'socialdb_collection_latitude_meta',$collection_settings['latidude_meta']);
        update_post_meta($collection_id, 'socialdb_collection_longitude_meta',$collection_settings['socialdb_collection_longitude_meta']);
        //after
        //update_post_meta($collection_id, 'socialdb_collection_table_metas',serialize($collection_settings['table_metas']));
        wp_set_object_terms();
        //permissions
        update_post_meta($collection_id, 'socialdb_collection_permission_create_category', (string) $xml->permissions->socialdb_collection_permission_create_category);
        update_post_meta($collection_id, 'socialdb_collection_permission_edit_category', (string) $xml->permissions->socialdb_collection_permission_edit_category);
        update_post_meta($collection_id, 'socialdb_collection_permission_delete_category', (string) $xml->permissions->socialdb_collection_permission_delete_category);
        update_post_meta($collection_id, 'socialdb_collection_permission_add_classification', (string) $xml->permissions->socialdb_collection_permission_add_classification);
        update_post_meta($collection_id, 'socialdb_collection_permission_delete_classification', (string) $xml->permissions->socialdb_collection_permission_delete_classification);
        update_post_meta($collection_id, 'socialdb_collection_permission_create_object', (string) $xml->permissions->socialdb_collection_permission_create_object);
        update_post_meta($collection_id, 'socialdb_collection_permission_delete_object', (string) $xml->permissions->socialdb_collection_permission_delete_object);
        update_post_meta($collection_id, 'socialdb_collection_permission_create_property_data', (string) $xml->permissions->socialdb_collection_permission_create_property_data);
        update_post_meta($collection_id, 'socialdb_collection_permission_edit_property_data', (string) $xml->permissions->socialdb_collection_permission_edit_property_data);
        update_post_meta($collection_id, 'socialdb_collection_permission_delete_property_data', (string) $xml->permissions->socialdb_collection_permission_delete_property_data);
        update_post_meta($collection_id, 'socialdb_collection_permission_edit_property_data_value', (string) $xml->permissions->socialdb_collection_permission_edit_property_data_value);
        update_post_meta($collection_id, 'socialdb_collection_permission_create_property_object', (string) $xml->permissions->socialdb_collection_permission_create_property_object);
        update_post_meta($collection_id, 'socialdb_collection_permission_edit_property_object', (string) $xml->permissions->socialdb_collection_permission_edit_property_object);
        update_post_meta($collection_id, 'socialdb_collection_permission_delete_property_object', (string) $xml->permissions->socialdb_collection_permission_delete_property_object);
        update_post_meta($collection_id, 'socialdb_collection_permission_edit_property_object_value', (string) $xml->permissions->socialdb_collection_permission_edit_property_object_value);
        update_post_meta($collection_id, 'socialdb_collection_permission_create_comment', (string) $xml->permissions->socialdb_collection_permission_create_comment);
        update_post_meta($collection_id, 'socialdb_collection_permission_edit_comment', (string) $xml->permissions->socialdb_collection_permission_edit_comment);
        update_post_meta($collection_id, 'socialdb_collection_permission_delete_comment', (string) $xml->permissions->socialdb_collection_permission_delete_comment);
        update_post_meta($collection_id, 'socialdb_collection_permission_create_tags', (string) $xml->permissions->socialdb_collection_permission_delete_comment);
        update_post_meta($collection_id, 'socialdb_collection_permission_edit_tags', (string) $xml->permissions->socialdb_collection_permission_edit_tags);
        update_post_meta($collection_id, 'socialdb_collection_permission_delete_tags', (string) $xml->permissions->socialdb_collection_permission_delete_tags);
        update_post_meta($collection_id, 'socialdb_collection_permission_create_property_term', (string) $xml->permissions->socialdb_collection_permission_create_property_term);
        update_post_meta($collection_id, 'socialdb_collection_permission_edit_property_term', (string) $xml->permissions->socialdb_collection_permission_edit_property_term);
        update_post_meta($collection_id, 'socialdb_collection_permission_delete_property_term', (string) $xml->permissions->socialdb_collection_permission_delete_property_term);
    }
    /**
     * metodo que atualiza os metadados fixos
     * @param $property
     */
    public static function updateFixedProperty($property){
        $term = get_term_by('slug',$property['slug'],'socialdb_property_type');

        $array = wp_update_term($term->term_id, 'socialdb_property_type',
            array('name'=> $property['name']));

        update_term_meta($term->term_id, 'socialdb_property_real_name', $term->name );
        update_term_meta($term->term_id, 'socialdb_property_visibility', ($property['visibility'] === 'on') ? 'show' : 'off' );
        update_term_meta($term->term_id, 'socialdb_property_help', ($property['help']) ? $property['help'] : '' );
        update_term_meta($term->term_id, 'socialdb_property_locked', ($property['locked']) ? 'true' : 'false' );
        update_term_meta($term->term_id, 'socialdb_property_data_mask', ($property['is_mask']) ? $property['is_mask'] : '' );
        update_term_meta($term->term_id, 'socialdb_property_required', ($property['required']) ? 'true' : 'false' );
        MappingImportDataSet::addMap('properties',$property['id'],$term->term_id);

    }

    /**
     * Metodo que cria o metadado a partir dos dados dos arquivos
     *
     * @param $property
     * @param $token
     * @param bool $is_repo
     * @param bool $is_compound
     * @return int
     */
    public static function createProperty($property,$token,$is_repo = false,$is_compound = false){
        $type = self::getTainacanTypeProperty($property);
        $array = wp_insert_term($property['name'], 'socialdb_property_type', array('parent' => $type->term_id,
            'slug' =>  self::generateSlug(trim($property['name']))));

        //metas comuns e especificos
        if (!is_wp_error($array) && isset($array['term_id'])) {
            $return = $property['metadata'];
            self::updateMetasCommoms($return,$token,$is_repo,$is_compound);
            self::updateSpecificMeta($array['term_id'],$property,$property['type'],$token);
                /*foreach ($metas as $meta) {
                    $ids_category = ['socialdb_property_object_category_id', 'socialdb_property_term_root'];
                    if (in_array($meta['key'], $ids_category) && trim($meta['value']) != '') {
                        $ids = explode(',', $meta['value']);
                        $new_ids = [];
                        foreach ($ids as $value) {
                            $new_ids[] = MappingAPI::hasMapping($class->url, 'categories', $value);
                        }
                        update_term_meta($array['term_id'], $meta['key'], implode(',', $new_ids));
                    } else if ($meta['key'] == 'socialdb_property_object_reverse' && trim($meta['value']) != '') {
                        if (!MappingAPI::hasMapping($class->url, 'properties', $meta['value'])) {
                            $term = $class->readProperty($meta['value']);
                            $metas_reverse = $class->readPropertyMetas($meta['value']);
                            HelpersAPIModel::createProperty($term, $metas_reverse, $class);
                        } else {
                            update_term_meta($array['term_id'], $meta['key'], MappingAPI::hasMapping($class->url, 'properties', $meta['value']));
                        }
                    }else if($meta['key'] == 'socialdb_property_collection_id' && trim($meta['value']) != ''){
                        update_term_meta($array['term_id'], $meta['key'], MappingAPI::hasMapping($class->url, 'collections', $meta['value']));
                    }else if($meta['key'] == 'socialdb_property_created_category' && trim($meta['value']) != ''){
                        update_term_meta($array['term_id'], $meta['key'], MappingAPI::hasMapping($class->url, 'categories', $meta['value']));
                    }else if ($meta['key'] == 'socialdb_property_compounds_properties_id' && trim($meta['value']) != '') {
                        $ids = explode(',', $meta['value']);
                        $new_ids = [];
                        foreach ($ids as $value) {
                            $new_ids[] = MappingAPI::hasMapping($class->url, 'properties', $value);
                        }
                        update_term_meta($array['term_id'], $meta['key'], implode(',', $new_ids));
                    }else if ($meta['key'] == 'socialdb_property_is_compounds' && trim($meta['value']) != ''){
                        $array_serializado = unserialize(unserialize(base64_decode($meta['value'])));
                        $new_ids = [];
                        foreach ($array_serializado as $index => $value) {
                            $new_ids[MappingAPI::hasMapping($class->url, 'properties', $index)] = $value;
                            if(MappingAPI::hasMapping($class->url, 'properties', $index) !== false){
                                $id = MappingAPI::hasMapping($class->url, 'properties', $index);
                                $terms = get_term_meta($id, 'socialdb_property_compounds_properties_id', true);
                                $ids = array_filter(explode(',', $terms));
                                if(is_array($ids) && !in_array($array['term_id'], $ids)){
                                    $ids[] = $array['term_id'];
                                    update_term_meta($id,'socialdb_property_compounds_properties_id', implode(',', $ids));
                                }
                            }
                        }
                        update_term_meta($array['term_id'], $meta['key'], serialize($new_ids));
                    }else {
                        update_term_meta($array['term_id'], $meta['key'], $meta['value']);
                    }
                } */
        }
        return $array['term_id'];
    }

    /**
     * Metodo que atualiza
     *
     * @param $term_id O id que ja foi criado
     * @param $property O metaddo no arquivo
     * @param $token
     * @param bool $is_repo
     * @param bool $is_compound
     * @return mixed
     */
    public static function updateProperty($term_id,$property,$token,$is_repo = false,$is_compound = false){
        $type = self::getTainacanTypeProperty($property);
        $array = wp_update_term($term_id,'socialdb_property_type');
        if (!is_wp_error($array) && isset($array['term_id'])) {
            self::updateMetasCommoms($array['term_id'],$property,$token,$is_repo,$is_compound);
            self::updateSpecificMeta($array['term_id'],$property,$property['type'],$token);
        }
        return $array['term_id'];
    }

    /**
     * metodo que determina qual operacao se atualizacao de metas sera feito
     *
     * @param $term_id
     * @param $property
     * @param $type
     * @param $token
     */
    public function updateSpecificMeta($term_id,$property,$type,$token){
        $return = $property['metadata'];
        $data = ['text', 'textarea', 'date', 'number', 'numeric', 'auto-increment', 'user'];
        $term = ['selectbox', 'radio', 'checkbox', 'tree', 'tree_checkbox', 'multipleselect'];
        $object = (isset($return['object_category_id']) && !empty($return['object_category_id'])) ? true : false;
        if (in_array($type, $data) && !$object) {
            return self::updateMetasTextProperty($$term_id,$property);
        } else if (in_array($type, $term) && !$object) {
            return self::updateMetasTermProperty($term_id,$property,$token,$is_repo);
        } else if ($object) {
            return self::updateMetasObjectProperty($term_id,$property);
        } else if ($type == 'compound') {
            return self::updateMetasTermCompound($term_id,$property,$token,$is_repo);
        }
    }

    /**
     * @param $term_id
     * @param $property
     * @param $token
     * @param bool $is_repo
     * @param bool $is_compound
     */
    public function updateMetasCommoms($term_id,$property,$token,$is_repo = false,$is_compound = false){
        $return = $property['metadata'];
        MappingImportDataSet::addMap( 'properties', $property['id'], $term_id);
        //token dessa versao
        update_term_meta($term_id, 'socialdb_token', $token);

        //se for metadado de colecao
        if(isset($return['collection_id']))
            update_term_meta($term_id, 'socialdb_property_collection_id', MappingImportDataSet::hasMap('collections',$return['collection_id']));

        //obrigatorio
        update_term_meta($term_id, 'socialdb_property_required', ($return['required']) ? 'true' : 'false');

        //categoria que foi criado
        $cat =  (MappingImportDataSet::hasMap('categories',$return['created_category']) ?  MappingImportDataSet::hasMap('categories',$return['created_category']) : get_term_by('slug','socialdb_category','socialdb_category_type')->term_id);
        update_term_meta($term_id, 'socialdb_property_created_category', $cat);

        //usado pelas categorias
        delete_term_meta($term_id, 'socialdb_property_used_by_categories');
        foreach ($return['used_by_categories'] as $term_reference_id){
            add_term_meta($term_id, 'socialdb_property_used_by_categories', MappingImportDataSet::hasMap('categories',$term_reference_id));
        }

        //visualizacao
        update_term_meta($term_id, 'socialdb_property_visualization', $return['visualization']);

        //esta desativado?
        update_term_meta($term_id, 'socialdb_property_locked', ($return['locked']) ? 'true' : 'false');

        //se eh metadado do repositorio
        update_term_meta($term_id, 'is_repository_property', ($is_repo) ? 'true' : 'false');

        if($is_compound){
            $meta = [];
            $meta[$is_compound] = 'true';
            update_term_meta($term_id, 'socialdb_property_is_compounds', serialize($meta));
        }

        if($is_repo){
            update_term_meta($term_id, 'socialdb_property_visibility', ($property['visibility']==='on') ? 'show' : 'hide');
        }
    }

    /**
     * @param $term_id
     * @param $property
     */
    public static function updateMetasTextProperty($term_id,$property){
        $return = $property['metadata'];

        //se eh coluna de ordenacao
        update_term_meta($term_id, 'socialdb_property_data_column_ordenation', (!$return['column_ordenation']) ? 'false' : $return['column_ordenation']);

        //o widget do metadado
        update_term_meta($term_id, 'socialdb_property_data_widget', $return['widget']);

        //cardinalidade
        update_term_meta($term_id, 'socialdb_property_data_cardinality', $return['cardinality']);

        // se possui mascara
        update_term_meta($term_id, 'socialdb_property_data_mask', (!$return['is_mask']) ? 'false' : $return['is_mask']);

        // valor padrao
        update_term_meta($term_id, 'socialdb_property_default_value', ($return['default_value'] === '') ? '' : $return['default_value']);

        //texto de ajuda
        update_term_meta($term_id, 'socialdb_property_help', ($return['text_help'] === '') ? '' : $return['text_help']);

        //se for data aproximada
        update_term_meta($term_id, 'socialdb_property_help', ($return['is_aproximate_date']) ? '1': '0');
    }

    /**
     * @param $term_id
     * @param $property
     */
    public function updateMetasObjectProperty($term_id,$property){
        $return = $property['metadata'];

        //propriedades para fazer a busca
        if(!$return['search_in_properties']){
            update_term_meta($term_id, 'socialdb_property_to_search_in', '');
        }else{
            $ids_search_in = explode(',',$return['search_in_properties']);
            $ids_new = [];
            $has_reference = false;
            foreach ($ids_search_in as $id_search_in) {
                if(MappingImportDataSet::hasMap('properties',$id_search_in)){
                    $ids_new[] = MappingImportDataSet::hasMap('properties',$id_search_in);
                }else{
                    $has_reference = true;
                    $ids_new[] = $id_search_in.'_reference';
                }
            }
            $search = implode(',',$ids_new);

            if($has_reference){
                global $wpdb;
                delete_term_meta($term_id, 'socialdb_property_to_search_in');
                add_term_meta($term_id, 'socialdb_property_to_search_in', $search);
                MappingImportDataSet::addMap('references-properties',$wpdb->insert_id,$search);
            }else{
                update_term_meta($term_id, 'socialdb_property_to_search_in', $search);
            }
        }

        //evitar items repetidos
        update_term_meta($term_id, 'socialdb_property_avoid_items',  ($return['avoid_items']) ? 'true': 'false');

        //adicionar novos itens
        update_term_meta($term_id, 'socialdb_property_habilitate_new_item',  ($return['habilitate_new_item']) ? 'true': 'false');

        //categorias que serao utilizadas para buscar os itens vinculados
        if(!$return['object_category_id']){
            update_term_meta($term_id, 'socialdb_property_object_category_id', '');
        }else{
            $ids_categories = explode(',',$return['object_category_id']);
            $ids_new = [];
            $has_reference = false;
            foreach ($ids_categories as $id_category) {
                if(MappingImportDataSet::hasMap('categories',$id_category)){
                    $ids_new[] = MappingImportDataSet::hasMap('categories',$id_category);
                }else{
                    $has_reference = true;
                    $ids_new[] = $id_category.'_reference';
                }
            }
            $categories = implode(',',$ids_new);

            if($has_reference){
                global $wpdb;
                delete_term_meta($term_id, 'socialdb_property_object_category_id');
                add_term_meta($term_id, 'socialdb_property_object_category_id', $categories);
                MappingImportDataSet::addMap('references-categories',$wpdb->insert_id,$categories);
            }else{
                update_term_meta($term_id, 'socialdb_property_object_category_id', $categories);
            }
        }

        //se o metadado filtro
        update_term_meta($term_id, 'socialdb_property_object_is_facet',  ($return['is_filter']) ? $return['is_filter']: '');

        //se o metadado eh reverso
        if($return['reverse']){
            update_term_meta($term_id, 'socialdb_property_object_is_reverse', 'true');
            if(MappingImportDataSet::hasMap('properties',$return['reverse'])){
                $id = MappingImportDataSet::hasMap('properties',$return['reverse']);
                update_term_meta($term_id, 'socialdb_property_object_reverse', $id);
            }else{
                global $wpdb;
                $id = $return['reverse'].'_reference';
                delete_term_meta($term_id, 'socialdb_property_object_reverse');
                add_term_meta($term_id, 'socialdb_property_object_reverse', $id);
                MappingImportDataSet::addMap('references-properties',$wpdb->insert_id,$id);
            }
        }else{
            update_term_meta($term_id, 'socialdb_property_object_reverse', '');
            update_term_meta($term_id, 'socialdb_property_object_is_reverse', 'false');
        }

        //se o metadado filtro
        update_term_meta($term_id, 'socialdb_property_object_cardinality',  ($return['cardinality']) ? $return['cardinality']: '1');
    }

    /**
     * @param $term_id
     * @param $property
     * @param $token
     * @param $is_repo
     */
    public function updateMetasTermProperty($term_id,$property,$token,$is_repo){
        $return = $property['metadata'];

        update_term_meta($term_id, 'socialdb_property_term_cardinality',  ($return['cardinality']) ? $return['cardinality']: '1');

        //se o metadado eh reverso
        if($return['taxonomy']){
            if(MappingImportDataSet::hasMap('categories',$return['taxonomy'])){
                $taxonomy_id = MappingImportDataSet::hasMap('categories',$return['taxonomy']);
                update_term_meta($term_id, 'socialdb_property_term_root', $taxonomy_id);
            }else{
                $args = ['term'=>['name'=>$property['name']]];
                $taxonomy_id = self::updateCategory($args,$token);
                MappingImportDataSet::addMap( 'categories', $return['taxonomy'], $taxonomy_id);
                add_term_meta($term_id, 'socialdb_property_term_root', $taxonomy_id);
            }
            //obsevo a taxonomia criada
            foreach ($return['categories'] as $category) {
                $has_id = MappingImportDataSet::hasMap('categories',$category['id']);
                self::updateCategory($category,$token,$taxonomy_id,($has_id) ? $has_id : false);
            }
        }else{
            update_term_meta($term_id, 'socialdb_property_term_root', '');
        }
    }

    /**
     * @param $term_id
     * @param $property
     * @param $token
     * @param $is_repo
     */
    public function updateMetasTermCompound($term_id,$property,$token,$is_repo){
        $return = $property['metadata'];
        update_term_meta($term_id, 'socialdb_property_compounds_cardinality',  ($return['cardinality']) ? $return['cardinality']: '1');
        $ids = [];
        foreach ($return['children'] as $child){
            $has_id = MappingImportDataSet::hasMap('properties',$child['id']);
            if($has_id){
                $ids[] = self::updateProperty($has_id,$child,$token,$is_repo,$term_id);
            }else{
                $ids[] = self::createProperty($child,$token,$is_repo,$term_id);
            }
        }
        //$childrens =  $property['metas']['socialdb_property_compounds_properties_id'];
        update_term_meta($term_id, 'socialdb_property_compounds_cardinality',  ($ids) ? implode(',',$ids): '');
    }

    /**
     * @param $category
     * @param $token
     * @param $parent
     * @param bool $term_id
     */
    public static function updateCategory($category,$token, $parent = false,$term_id = false){
        //se caso estiver criando
        if(!$term_id) {
            $args = array('parent' => ( $parent ) ? $parent : get_term_by('slug','socialdb_category','socialdb_category_type')->term_id ,
                'slug' => self::generateSlug(trim($category['term']['name'])));
            $array = wp_insert_term($category['term']['name'], 'socialdb_category_type', $args);
            update_term_meta($array['term_id'],'socialdb_category_owner',get_current_user_id());
            if(isset($category['term']['term_id']))
                MappingImportDataSet::addMap( 'categories', $category['term']['term_id'], $array['term_id']);
        }else {
            $args = array('name' => $category['term']['name'] );
            $array = wp_update_term($term_id, 'socialdb_category_type',$args);
        }

        //propriedades desta categoria
        if( $category['properties-children']){
            delete_term_meta($array['term_id'],'socialdb_category_property_id');
            foreach ( $category['properties-children'] as $child){
                $has_id = MappingImportDataSet::hasMap('properties',$child['id']);
                if($has_id){
                    $id = self::updateProperty($has_id,$child,$token,false,false);
                }else{
                    $id = self::createProperty($child,$token,false,false);
                }
                add_term_meta($array['term_id'],'socialdb_category_property_id',$id);
            }
        }

        //se possuir categorias filhas
        if( isset($category['children']) ){
            foreach ($category['children'] as $child) {
                $has_id = MappingImportDataSet::hasMap('categories',$child['id']);
                self::updateCategory($child,$token,$array['term_id'],($has_id) ? $has_id : false);
            }
        }

        update_term_meta($array['term_id'], 'socialdb_token', $token);
        return $array['term_id'];
    }

    /**
     * function generate_slug($title)
     * @param string $string
     * @return string
     * metodo responsavel em normatizar uma string para ser um slug
     * @author Eduardo Humberto
     */
    public static function generateSlug($string) {
        return sanitize_title(remove_accent($string)) . "_" . time() . rand(0, 100);
    }

    /**
     * @param $property
     * @return array|false|WP_Term
     */
    public static function getTainacanTypeProperty($property){
        $type = '';
        $data = ['text', 'textarea', 'date', 'number', 'numeric', 'auto-increment', 'user'];
        $term = ['selectbox', 'radio', 'checkbox', 'tree', 'tree_checkbox', 'multipleselect'];
        if($property['type'] === 'item'){
            $type = 'socialdb_property_object';
        }else if($property['type'] === 'compound'){
            $type = 'socialdb_property_compounds';
        }else if (in_array($property['type'], $data)) {
            $type = 'socialdb_property_data';
        } else if (in_array($property['type'], $term)) {
            $type = 'socialdb_property_term';
        }
        return get_term_by('slug',$type,'socialdb_property_type');
    }
}