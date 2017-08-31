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
         foreach ($repository['metadata'] as $metadata) {
              if(strpos($metadata['slug'],'socialdb_property_fixed')!==false){
                    PersistMethodsImportDataSet::updateFixedProperty($metadata);
              }else{
                  if(MappingImportDataSet::hasMap('properties',$metadata['id'])){

                  }else{

                  }
              }
         }
     }


}