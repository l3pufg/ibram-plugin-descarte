<?php
/**
 * Classe responsavel por iniciar a execuacao da importacao do set
 * User: desenvolvedor
 * Date: 24/08/2017
 * Time: 16:41
 */
include_once dirname(__FILE__).'/class-helper-file-import-data-set.php';
include_once dirname(__FILE__).'/class-mapping-import-data-set.php';
include_once dirname(__FILE__).'/class-persist-methods-import-data-set.php.php';

 class ImportDataSet{

    public static function start(){
        // busco os dados do repositorio metadados/informacoes gerais
        $repository = HelperFileImportDataSet::getRepository();

        // busco os dados das colecoes  metadados/informacoes gerais
        $collections = HelperFileImportDataSet::getCollections();

        // inicio o mapeamento ou busco o ja existente
        MappingImportDataSet::initMap();

        // adicionando ou atualizando os metadados do repositorio
        self::proccessRepositoryProperties($repository);

        // adicionando ou atualizando os metadados da colecao e suas configuracoes gerais
        self::proccessCollections($collections);

        // atualizo as informacoes gerais do repositorio, desmembrado pois depende das colecoes
        PersistMethodsImportDataSet::updateRepository($repository);

        //atualizo as referencias de metadados para valores antigos
        self::updateReferences();

        //removo possiveis colecoes,metadados ou categorias desatualizadas
        self::garbageCollector();

        //salvo o mapeamento
        MappingImportDataSet::saveMap();
    }


     public static function proccessRepositoryProperties($repository){
         $cat_id = get_term_by('slug','socialdb_category','socialdb_category_type')->term_id;
         foreach ($repository['metadata'] as $metadata) {
              if(strpos($metadata['slug'],'socialdb_property_fixed')!==false){
                    PersistMethodsImportDataSet::updateFixedProperty($metadata);
              }else{
                  $has_id = MappingImportDataSet::hasMap('properties',$metadata['id']);
                  if($has_id){
                      PersistMethodsImportDataSet::updateProperty($has_id,$metadata,IBRAM_VERSION,true,false);
                  }else{
                        $id = PersistMethodsImportDataSet::createProperty($metadata,IBRAM_VERSION,true);
                        add_term_meta($cat_id,'socialdb_category_property_id',$id);
                  }
              }
         }
     }


     /**
      * @param $collections
      */
     public static function proccessCollections($collections){
         foreach ($collections as $collection){
             PersistMethodsImportDataSet::manageCollection($collection);
         }
     }

     /**
      *
      */
     public static function updateReferences(){
         $map = MappingImportDataSet::$map;
         if(isset($map['references-collection']) && is_array($map['references-collection'])){
             foreach ($map['references-collection'] as $meta_id => $old_id) {
                    $old_id = str_replace('_reference','',$old_id);
                    PersistMethodsImportDataSet::saveMetaById('post',$meta_id,$map['collections'][$old_id]);
             }
         }
         if(isset($map['references-properties']) && is_array($map['references-properties'])){
             foreach ($map['references-properties'] as $meta_id => $old_id) {
                 if(strpos($old_id,',')!==false){
                     $explode = explode(',',$old_id);
                     $values = [];
                     foreach ($explode as $p) {
                         if(strpos($p,'_reference')!==false){
                             $p = str_replace('_reference','',$p);
                             $values[]  =  $map['properties'][$p];
                         }else{
                             $values[]  =  $p;
                         }
                         PersistMethodsImportDataSet::saveMetaById('term',$meta_id,implode(',',$values));
                     }
                 }else{
                     $old_id = str_replace('_reference','',$old_id);
                     PersistMethodsImportDataSet::saveMetaById('term',$meta_id,$map['properties'][$old_id]);
                 }
             }
         }
         if(isset($map['references-categories']) && is_array($map['references-categories'])){
             foreach ($map['references-categories'] as $meta_id => $old_id) {
                 if(strpos($old_id,',')!==false){
                     $explode = explode(',',$old_id);
                     $values = [];
                     foreach ($explode as $p) {
                         if(strpos($p,'_reference')!==false){
                             $p = str_replace('_reference','',$p);
                             $values[]  =  $map['categories'][$p];
                         }else{
                             $values[]  =  $p;
                         }
                         PersistMethodsImportDataSet::saveMetaById('term',$meta_id,implode(',',$values));
                     }
                 }else{
                     $old_id = str_replace('_reference','',$old_id);
                     PersistMethodsImportDataSet::saveMetaById('term',$meta_id,$map['categories'][$old_id]);
                 }
             }
         }
     }

     /**
      *
      */
     public static function garbageCollector() {
         $map = MappingImportDataSet::$map;
         $token = IBRAM_VERSION;
         $nopes = [get_term_by('slug','socialdb_category','socialdb_category_type')->term_id,get_term_by('slug','socialdb_taxonomy','socialdb_category_type')->term_id];
         if ($map) {
             foreach ($map as $index => $array) {
                 if($index == 'collections'){
                     foreach ($array as $id_blog) {
                         $has_token = get_post_meta($id_blog, 'socialdb_token', true);
                         if(!$has_token || $has_token != $token){
                             $collection = array(
                                 'ID'=> $id_blog,
                                 'post_status' => 'draft'
                             );
                             wp_update_post($collection);
                         }
                     }
                 }else if($index == 'categories' || $index == 'properties'){
                     foreach ($array as $id_blog) {
                         $has_token = get_term_meta($id_blog, 'socialdb_token', true);
                         if(!$has_token || $has_token != $token){
                             if(!in_array($id_blog,$nopes))
                                 wp_delete_term($id_blog,($index == 'categories') ? 'socialdb_category_type': 'socialdb_property_type');
                         }
                     }
                 }
             }
         }
     }


}