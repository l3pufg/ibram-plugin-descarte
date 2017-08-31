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
        $repository = HelperFileImportDataSet::getRepository();
        $collections = HelperFileImportDataSet::getCollections();
        echo '<pre>';
        var_dump($repository);die;
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


     public static function proccessCollections($collections){
         foreach ($collections as $collection){

         }
     }


}