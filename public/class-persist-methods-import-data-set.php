<?php
/**
 * Created by PhpStorm.
 * User: desenvolvedor
 * Date: 25/08/2017
 * Time: 10:33
 */

class PersistMethodsImportDataSet{

    public function updateFixedProperty($property){
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

    public function createProperty($property,$token,$is_repo = false){
        $type = self::getTainacanTypeProperty($property);
        $array = wp_insert_term($property['name'], 'socialdb_property_type', array('parent' => $type->term_id,
            'slug' =>  self::generateSlug(trim($property['name']))));

        //metas comuns e especificos
        if (!is_wp_error($array) && isset($array['term_id'])) {
            $return = $property['metadata'];
            MappingImportDataSet::addMap( 'properties', $property['id'], $array['term_id']);
            //token dessa versao
            update_term_meta($array['term_id'], 'socialdb_token', $token);

            //se for metadado de colecao
            if(isset($return['collection_id']))
                update_term_meta($array['term_id'], 'socialdb_property_collection_id', MappingImportDataSet::hasMap('collections',$return['collection_id']));

            //obrigatorio
            update_term_meta($array['term_id'], 'socialdb_property_required', ($return['required']) ? 'true' : 'false');

            //categoria que foi criado
            $cat =  (MappingImportDataSet::hasMap('categories',$return['created_category']) ?  MappingImportDataSet::hasMap('categories',$return['created_category']) : get_term_by('slug','socialdb_category','socialdb_category_type')->term_id);
            update_term_meta($array['term_id'], 'socialdb_property_created_category', $cat);

            //usado pelas categorias
            foreach ($return['used_by_categories'] as $term_id){
                add_term_meta($array['term_id'], 'socialdb_property_used_by_categories', MappingImportDataSet::hasMap('categories',$term_id));
            }

            //visualizacao
            update_term_meta($array['term_id'], 'socialdb_property_required', $return['visualization']);

            //esta desativado?
            update_term_meta($array['term_id'], 'socialdb_property_locked', ($return['locked']) ? 'true' : 'false');

            //se eh metadado do repositorio
            update_term_meta($array['term_id'], 'is_repository_property', ($is_repo) ? 'true' : 'false');



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

    public function updateProperty(){

    }

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
                add_term_meta($term_id, 'socialdb_property_to_search_in', $search);
                MappingImportDataSet::addMap('references',$wpdb->insert_id,$search);
            }else{
                update_term_meta($term_id, 'socialdb_property_to_search_in', (!$return['column_ordenation']) ? 'false' : $return['column_ordenation']);
            }
        }

        update_term_meta($term_id, 'socialdb_property_to_search_in', (!$return['column_ordenation']) ? 'false' : $return['column_ordenation']);


        $return['search_in_properties'] =  empty($property['metas']['socialdb_property_to_search_in']) ? false : $property['metas']['socialdb_property_to_search_in'] ;
        $return['avoid_items'] = ( isset($property['metas']['socialdb_property_avoid_items'])) ? true : false;
        $return['habilitate_new_item'] = ( isset($property['metas']['socialdb_property_habilitate_new_item']) && $property['metas']['socialdb_property_habilitate_new_item'] === 'true' ) ? true : false;
        $return['object_category_id'] = empty($property['metas']['socialdb_property_object_category_id']) ? false : $property['metas']['socialdb_property_object_category_id'] ;
        $return['to_search_in'] = empty($property['metas']['socialdb_property_to_search_in']) ? false : $property['metas']['socialdb_property_to_search_in'] ;
        $return['is_filter'] = empty($property['metas']['socialdb_property_object_is_facet']) ? false : $property['metas']['socialdb_property_object_is_facet'] ;
        $return['reverse'] = empty($property['metas']['socialdb_property_object_reverse']) ? false : $property['metas']['socialdb_property_object_is_facet'] ;
        $return['cardinality'] = isset($property['metas']['socialdb_property_object_cardinality']) ? $property['metas']['socialdb_property_object_cardinality'] : '1';
    }


    /**
     * funcao que insere o postmeta e retorna o id gerado mesmo argumentos
     * e opcionais do postmeta
     */
    public function insertPostMetaRow($post_id, $key, $value, $is_single = false) {
        global $wpdb;
        add_post_meta($post_id, $key, $value, $is_single);
        return $wpdb->insert_id;
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