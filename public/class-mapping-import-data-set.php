<?php
/**
 * Classe responsavel em fazer o mapeamento das entidades dos arquivos para os do tainacan atual
 * User: desenvolvedor
 * Date: 25/08/2017
 * Time: 09:48
 */

class MappingImportDataSet{

    public static $file_name = IBRAM_PATH.'files/repository-mapping.json';
    public static $map;

    public static function isMapped(){
        return is_file(self::$file_name);
    }

    /**
     *
     */
    public static function setJson(){
        if(self::isMapped()){
            $rawMap = file_get_contents(self::$file_name);
            self::$map =  json_decode($rawMap, true);
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
        if(self::isMapped())
            unlink(self::$file_name);

        self::generateJsonFile(self::$file_name);
    }

    /**
     * @param $name_file
     */
    public static function generateJsonFile($name_file) {
        ob_start();
        ob_end_clean();
        $df = fopen($name_file, 'w');
        fputs($df, self::$map);
        fclose($df);
    }
}