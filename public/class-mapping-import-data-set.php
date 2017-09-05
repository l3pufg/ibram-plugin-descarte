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
        $option = unserialize(get_option('mapping-tainacan-import'));
        //return is_file(self::$file_name);
        return $option;
    }

    /**
     *
     */
    public static function initMap(){
        $mapping = self::isMapped();
        if($mapping){
            //$rawMap = file_get_contents(self::$file_name);
            //self::$map =  json_decode($rawMap, true);
            if((is_array($mapping) && empty($mapping)) || is_bool($mapping) ){
                self::$map = [ 'collections' => [], 'properties' => [], 'categories' => [],'tabs'=>[],'references-properties'=>[],'references-categories'=>[],'references-collections'=>[],'menu'=>[]];
            }
            self::$map =  $mapping;
        }else{
            self::$map = [];
            self::$map = [ 'collections' => [], 'properties' => [], 'categories' => [],'tabs'=>[],'references-properties'=>[],'references-categories'=>[],'references-collections'=>[],'menu'=>[]];
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
        if($type && $id_file)
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
        if(is_array(self::$map))
            update_option('mapping-tainacan-import', serialize(self::$map));
        //self::generateJsonFile(self::$file_name);
    }

    public static function var_error_log( $object=null ){
        ob_start();                    // start buffer capture
        var_dump( $object );           // dump the values
        $contents = ob_get_contents(); // put the buffer into a variable
        ob_end_clean();                // end capture
        error_log( $contents );        // log contents of the result of var_dump( $object )
    }
}