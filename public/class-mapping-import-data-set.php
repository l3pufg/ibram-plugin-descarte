<?php
/**
 * Classe responsavel em fazer o mapeamento das entidades dos arquivos para os do tainacan atual
 * User: desenvolvedor
 * Date: 25/08/2017
 * Time: 09:48
 */

class MappingImportDataSet{

    public static $map;

    public static function isMapped(){
        $option = get_option('mapping-tainacan-import');
        //return is_file(self::$file_name);
        return $option && is_array($option);
    }

    /**
     *
     */
    public static function initMap(){
        $mapping = self::isMapped();
        if($mapping){
            //$rawMap = file_get_contents(self::$file_name);
            //self::$map =  json_decode($rawMap, true);
            self::$map =  $mapping;
        }else{
            self::$map = [ 'collections' => [], 'properties' => [], 'categories' => [],'tabs'=>[],'references-properties'=>[],'references-categories'=>[]];
        }
    }

    /**
     * @param $type
     * @param $id_file
     * @param $id_tainacan
     */
    public static function addMap($type, $id_file, $id_tainacan){
        if(!is_array(self::$map)){
            self::initMap();
        }
        self::$map[$type][$id_file] = $id_tainacan;
    }

    /**
     * @param $type
     * @param $id_file
     * @return bool
     */
    public static function hasMap($type, $id_file){
        return (isset( self::$map[$type][$id_file])) ? self::$map[$type][$id_file] : false;
    }

    /**
     *
     */
    public static function saveMap(){
        //if(self::isMapped())
            //(self::$file_name);
        update_option('mapping-tainacan-import', self::$map);
        //self::generateJsonFile(self::$file_name);
    }
}