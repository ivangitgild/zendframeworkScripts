<?php
	/** This Script is for CRON Job Used only 
		* Used for email notifications to main email address
		* Will send alert if there are applications that need to be
		* approve before (number of days set) the set deadline
		* @author Prog82
	**/
	error_reporting(E_ALL | E_STRICT);  
	ini_set('display_startup_errors', 1);  
	ini_set('display_errors', 1); 
	date_default_timezone_set('Asia/Manila');

	define("APPLICATION_PATH",dirname(__FILE__) . '\application');
	set_include_path(dirname(__FILE__) .'\application' . PATH_SEPARATOR . dirname(__FILE__) . '..\..\library' . PATH_SEPARATOR . 
		dirname(__FILE__) . '\application\default\models/' . PATH_SEPARATOR . get_include_path());

	require_once 'Zend/loader/Autoloader.php' ; 
	//load the Initializer:
	require_once 'ModelLoader.php';
	require_once 'Zend/Loader.php';

	Zend_Loader_Autoloader::getInstance();
	//Start Session:
	//Zend_Session::start(array('name'=>'cpi_payroll'));
	@Zend_Loader::registerAutoload('ModelLoader');

	$config = new Zend_Config_Ini('config.ini', 'general');
	$registry = Zend_Registry::getInstance();
	$registry->set('config', $config);

	// setup database
	$db = Zend_Db::factory($config->db);
	Zend_Db_Table::setDefaultAdapter($db);
	$registry->set('db', $db);

	/* You can call all the classes that will be used for the functionality */
	$approver = new Approvers();
	$mailer = new EmailSender();
	$company = new Company();
	$companies = $company->fetchAll();
	$email_subj = "Warning : CutOff is Near";
	$email_body = "<div>
								<h2>CuttOff is already near</h2>
								<p>System reminds you that all pending application request must be addressed</p>
							</div>";
							
	foreach($companies as $comp) {
		//echo $comp['company_id'];
		$warn = $approver->doWarnByCutOff($comp['company_id']);
		if($warn) {
		//	echo 'test';
			$emps = $approver->getAllApproversEmail($comp['company_id']);
			foreach($emps as $e) {
				$mailer->sendEmail($email_body, $email_subj, $e['email'], $e['fullname']);
			}
			$main_email = Notifications::getMainEmailAddress($comp['company_id']);
			$mailer->sendEmail($email_body, $email_subj, $main_email,'Company Main Email Address');
		}
	}
?>