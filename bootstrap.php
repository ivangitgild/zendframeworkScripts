<?php
/**
 * My new Zend Framework project
 * 
 * @author  
 * @version 
 */

date_default_timezone_set('Asia/Manila');
define("APPLICATION_PATH",dirname(__FILE__));
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . dirname(__FILE__) . '\..\library' . PATH_SEPARATOR .  dirname(__FILE__) . '\default\models/' . PATH_SEPARATOR . get_include_path());

//set_include_path(dirname(__FILE__) . '\default\helpers' . PATH_SEPARATOR . get_include_path());
//For Production server:
//set_include_path('.' . PATH_SEPARATOR . '../../../library' . PATH_SEPARATOR . '../application/default/models/' . PATH_SEPARATOR . get_include_path());
require_once 'Zend/loader/Autoloader.php' ; 
//load the Initializer:
require_once 'ModelLoader.php';
require_once 'Initializer.php';
require_once 'TimeOutPlugin.php';
require_once 'MyAcl.php';
require_once 'MyAclPlugin.php';


// Set up autoload.

Zend_Loader_Autoloader::getInstance();
//Start Session:
Zend_Session::start(array('name'=>'cpi_payroll'));
@Zend_Loader::registerAutoload('ModelLoader');
// load configuration
$config = new Zend_Config_Ini('application/config.ini', 'general');
$registry = Zend_Registry::getInstance();
$registry->set('config', $config);

// setup database
$db = Zend_Db::factory($config->db);
Zend_Db_Table::setDefaultAdapter($db);
$registry->set('db', $db);

// Prepare the front controller. 
$frontController = Zend_Controller_Front::getInstance();

// Register Timeout Plugin:
$frontController->registerPlugin(new TimeOutPlugin($config->idletimeout));

// Change to 'production' parameter under production environment
$frontController->registerPlugin(new Initializer('production'));

//Auth and Acl
// Create auth object
$auth = Zend_Auth::getInstance();
// Create acl object
$acl = new MyAcl();

//DOJO Support
/*$view = new Zend_View();
Zend_Dojo::enableView($view);
$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
$viewRenderer->setView($view);*/

//Setup Acl Plugin
$frontController->registerPlugin(new MyAclPlugin($auth, $acl));

//Setup LastOnlinePlugin
//$frontController->registerPlugin(new LastOnlinePlugin());

// Dispatch the request using the front controller. 
$frontController->dispatch(); 
?>