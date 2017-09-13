<?php
/**
 * Script de importação dos dados do ibram
 *
 */

define( 'WP_USE_THEMES', false);
define( 'IBRAM_VERSION','0.1');
define( 'IBRAM_PATH', dirname(__FILE__).'/../' );

//SE FOR MULTISITE ESTES PARAMETROS DEVEM SER ALTERADOS

define( 'MULTISITE', true);
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/wordpress/teste-nova-api/';


//////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////
//////////////////////// Não Edite daqui pra baixo ///////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////

global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header,$wpdb,$current_site;
require(dirname(__FILE__).'/../../../../wp-load.php');
require_once dirname(__FILE__).'/class-helper-file-import-data-set.php';
require_once dirname(__FILE__).'/class-mapping-import-data-set.php';
require_once dirname(__FILE__).'/class-persist-methods-import-data-set.php';
require_once dirname(__FILE__).'/class-import-data-set.php';

$start = microtime(true);
echo("==========================================================". PHP_EOL);
echo '=== Inicializando importacao, isso podera levar alguns minutos...'. PHP_EOL;
ImportDataSet::start();
$scripttime = microtime(true) - $start;
echo("==========================================================". PHP_EOL);
echo("==========================================================". PHP_EOL);
echo("=== Importacao realizada com sucesso! Tempo de execução: {$scripttime}s". PHP_EOL);
echo("==========================================================". PHP_EOL);
echo("==========================================================". PHP_EOL);