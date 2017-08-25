<?php
/**
 * Classe em responsavel em fazer a leitura dos arquivos json
 * User: desenvolvedor
 * Date: 24/08/2017
 * Time: 16:55
 */

class HelperFileImportDataSet{

    /**
     * metodo que le os jsons dos arquivos
     *
     * @param bool $is_collection
     * @param int $collection_id
     * @return array
     *
     */
    public static function readJson($is_collection = false,$collection_id = 0){
        if($is_collection){
            $rawFilters = file_get_contents(IBRAM_PATH.'files/'.$collection_id.'/filters.json');
            $rawMetadata = file_get_contents(IBRAM_PATH.'files/'.$collection_id.'/metadata.json');
            return ['filters'=>json_decode($rawFilters, true),'metadata'=>json_decode($rawMetadata, true)];
        }else{
            $rawSettings = file_get_contents(IBRAM_PATH.'files/repository-settings.json');
            $rawMetadata = file_get_contents(IBRAM_PATH.'files/repository-metadata.json');
            return ['settings'=>json_decode($rawSettings, true),'metadata'=>json_decode($rawMetadata, true)];
        }
    }

    /**
     * @return array
     */
    public static function getCollections(){
        $collections = [];
        foreach (new DirectoryIterator(IBRAM_PATH.'files') as $fileInfo) {
            if($fileInfo->isDot())
                continue;

            if(strpos($fileInfo->getFilename(),'repository')===false){
                $collection_id =  $fileInfo->getFilename();
                $collections[] = self::readJson(true,$collection_id);
            }
        }
        return $collections;
    }

    public static function  getRepository(){
        return self::readJson();
    }

    /**
     * @param $collection_id
     * @return array|bool
     */
    public static function getCollection($collection_id){
        if(is_dir(IBRAM_PATH.'/files/'.$collection_id)){
            return self::readJson(true,$collection_id);
        }
        return false;
    }

}