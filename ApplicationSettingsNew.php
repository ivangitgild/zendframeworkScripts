<?php
/**
 *  Class Model Application Settings
 *  Global Settings
 *  @author: prog82
 */
class ApplicationSettingsNew extends Zend_Db_Table {
	const MAIN = '4';
	const NOTIFICATIONS = '1';
	const CUTOFFDATES = '2';
	const REPORTS = '3';

	static $default_main = array(	'num_days_valid' => '1', 
									'num_days_preapproved' => '1', 
									'holiday_display' => '1', 
									'remind_user' =>'0', 
									'historical_settings' => 'display_cutoff'
							);
							
	
	static $CPI_APPLICATIONS = array(	'1' => 'Attendance Adjustments',
										'2' => 'Change Shift', 
										'3' => 'Change Rest Day',
										'4' => 'Leave', '5' => 'Overtime'
									);
    protected $_name = 'settings';
	protected $_name_2 = 'application_settings_2';
	
    public function getDaysValid($companyId, $applicationName) {
        $where = "company_id = '{$companyId}' AND application_name = '{$applicationName}'";
        $row = $this->fetchRow($where);
        return $row->days_valid;
    }
    public function isPreApproved($companyId, $applicationName) {
        $where = "company_id = '{$companyId}' AND application_name = '{$applicationName}'";
        $row = $this->fetchRow($where);
        if($row->preapproved == 0) {
            return false;
        } else {
            return true;
        }
    }
	
	/* Apply Settings - Depends on Application, Company ID
	*  @params companyid, postdata array, app = applicationid
	*/
	public function applySettings( $companyId, $postdata , $app ) {
		 if(self::isSettingsExist($companyId, $app)) {
			self::updateSettings($companyId, $postdata, $app);
		}else{
			 self::createSettings($companyId, $postdata, $app);
		}
		//self::getSubmittedSettings($app, $postdata);
	}
	
	static function objectToArray($d) {
		if (is_object($d)) {
			// Gets the properties of the given object
			// with get_object_vars function
			$d = get_object_vars($d);
		}

		if (is_array($d)) {
			/*
			* Return array converted to object
			* Using __FUNCTION__ (Magic constant)
			* for recursive call
			*/
			return array_map(__FUNCTION__, $d);
			//return self::objectToArray($d);
		}
		else {
			// Return array
			return $d;
		}
	}
	
	/**
     *  Update Existing / Current Settings
     *  Updates Main Application and Notification Settings
     *  @access protected
     *  @params: companyid, 
	 *  @param array postdata -- form submitted data
	 *  @param app -- application type id
     *  @return none
     */
	protected function updateSettings($companyId, $postdata, $app) {
		
		$setting_id = self::getSettingID($companyId, $app);
		$where = "id = '{$setting_id}'";
		$row = $this->fetchRow($where);
		
		$option = self::getSubmittedSettings($app, $postdata);
		
		if($app == self::MAIN){
			$current = (array)json_decode($row->defined_settings)->current_settings;
			$default = $this->getDefaultSettings($app);
			$option = array_merge($current, $option);
            
			$opt = array('default' => $default, 'current_settings' => $option, 'previous_settings' => $current);
		}else if($app == self::NOTIFICATIONS) {
			$current = json_decode($row->defined_settings,true);
			$app_changed = false;
			$e_content_changed = false;
			$main_email_address = $current['main_email_address'];
			if($postdata['add_main_email']!='') {
				unset($current['main_email_address']);
				$current['main_email_address'] = $postdata['main_email_add'];
				$app_changed = true;
			}else{
				foreach($current['notifications'] as $k => $i) {
					if($k == $postdata['application_type']){
						foreach($i as $ks => $j) {
							if(($ks+1) == $postdata['notification_for']) {
								unset($current['notifications'][$k][$ks][$postdata['notification_for']]);
								$current['notifications'][$k][$ks] = array($postdata['notification_for'] => array('email_content' => $postdata['email_template']));
								break;
							}
						}
						$app_changed = true;
						break;
					}
				}
			}
			if(!$app_changed) {
				$opt = $current['notifications'] + $option['notifications'];
				$opt = array('main_email_address' => $main_email_address,'notifications' => $opt);
			}else{
				$opt = $current;
			}
		}
		$row->defined_settings = json_encode($opt);
		$row->last_updated = date('Y-m-d H:i:s');
		$row->save();
	}
	
	/**
     *  Creates New Settings
     *  Creates Main Application and Notification Settings
     *  @access protected
     *  @params: companyid, 
	 *  @param array postdata -- form submitted data
	 *  @param app -- application type id
     *  @return none
     */
	protected function createSettings($companyId, $postdata, $app) {
		$new = $this->createRow();	
		$option = self::getSubmittedSettings($app, $postdata);
	
		if($app == self::MAIN){
			$default = $this->getDefaultSettings($app);
			$option = array_merge($default, $option);
			$opt = array('default' => $default, 'current_settings' => $option, 'previous_settings' => array());
		}else if($app == self::NOTIFICATIONS){
			$opt = $option;
		}	
		$new->application_id = $app;
		$new->defined_settings = json_encode($opt);
		$new->save();
		
		if(self::compSettingsExist($companyId)) {
			$settings = self::getSettingIDs($companyId);
			$new_setting = array();
			foreach($settings as $key => $opt) {
				if($key == $app) {
					unset($settings->{$key});
				}
			}
			$settings->{$app} = $new->id;
			$settings = json_encode( array('applications' => (array)$settings));
			$this->_db->update('application_settings_2',array( 'settings_ids' => $settings), "company_id = '{$companyId}'");
		}else{
			
			$settings = array($app => $new->id);
			$settings = json_encode(array('applications' => $settings));
			$this->_db->insert('application_settings_2', array('id' => '', 'company_id' => $companyId, 'settings_ids' => $settings));
		}
	}
	
	/**
     *  Get the Setting IDs for company
     *
     *  @access public
     *  @params: companyid
     *  @return array
     */
	protected function getSettingIDs($companyId) {
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('application_settings_2');
		$sql = $sql->where("company_id = '{$companyId}'");
		$row = $this->_db->fetchRow($sql);
		
		return json_decode($row['settings_ids'])->applications;
	}
	
	/**
     *  Get the Setting ID for an application type
     *
     *  @access public
     *  @params: companyid, app -> Application Ida
     *  @return string
     */
	public function getSettingID($companyId, $app) {
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('application_settings_2');
		$sql = $sql->where("company_id = '{$companyId}'");
		$row = $this->_db->fetchRow($sql);
		$setting_id = '';
		$setting_ids = json_decode($row['settings_ids'])->applications;
		
		foreach($setting_ids as $key => $opt) {
			if($key == $app) {
				$setting_id = $opt;
				break;
			}
		}
		
		return $setting_id;
	}
	
	/**
     *  Check If Company ID defined settings
     *
     *  @access public
     *  @params: companyid
     *  @return bool
     */
	protected function compSettingsExist($companyId) {
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from($this->_name_2);
		$sql = $sql->where("company_id = '{$companyId}'");
		$row = $this->_db->fetchRow($sql);
		if(!empty($row))
			return true;
		else
			return false;
	}
	
	/**
     *  Get the Current Defined Settings
     *
     *  @access public
     *  @params: companyid, app -> Application Id
     *  @return array of setting
     */
	public function getCurrentSetting($companyId, $app) {
		if(!self::isSettingsExist($companyId, $app)) {
			//return false;
			return array('current_settings' => self::$default_main);
		}
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from($this->_name_2)->where("company_id = '{$companyId}'");
		$row = $this->_db->fetchRow($sql);
		
		$settings_id = json_decode($row['settings_ids'])->applications;
		$setting_id = '';
		foreach($settings_id as $key => $opt) {
			if($key == $app){
				$setting_id = $opt;
				break;
			}
		}
		
		
		$where = "id = '{$setting_id}'";
		$s_row = $this->fetchRow($where);
		return json_decode($s_row['defined_settings'],true);
		
	}
	
	 /**
     *  Check If Company has defined settings for the Application
     *
     *  @access public
     *  @params: companyid, app -> Application Ida
     *  @return bool
     */
	public function isSettingsExist($companyId, $app) {
		$where = "company_id = '{$companyId}'";
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('application_settings_2');
		$sql = $sql->where($where);
		$row = $this->_db->fetchRow($sql);
		
		$settings_id = json_decode($row['settings_ids'])->applications;
		$check = false;
		foreach($settings_id as $key => $opt) {
			if($key == $app){
				$check = true;
				break;
			}
		}
		
		if(!empty($row) && $check) {
			return true;
		}else{
			return false;
		}
	}
	
	public function getNotificationApp() {
		return self::NOTIFICATIONS;
	}
	
	public function getCutoffdatesApp() {
		return self::CUTOFFDATES;
	}
	
	public function getReportsApp() {
		return self::REPORTS;
	}
	
	public function getMainApp() {
		return self::MAIN;
	}
	
	public function getDefaultSettings( $app ) {
		if($app == self::MAIN) {
			return self::$default_main;
		}else if($app == self::NOTIFICATIONS) {
			
		}
	}

	 /**
     *  Parse/Builds an Structured Assoc Array for the Post Data
     *
     * @access public
     * @param int app -- application type
     * @param array postdata -- form submitted data
     * @return array of options build
     */
	public function getSubmittedSettings( $app, $postdata ) {
		$option = array();
		if($app == self::MAIN) {
			
			$option = array('num_days_valid' => $postdata['num_valid_days'], 
						'num_days_preapproved' => $postdata['num_preapproved_days'], 
						'holiday_display' => $postdata['holiday_display'], 
						'remind_user' => $postdata['remind'], 
						'historical_settings' => $postdata['historical_transaction'],
                        'past_days' => $postdata['past_days'],
                        'last_days' => $postdata['last_days']
					);
		}else if($app == self::NOTIFICATIONS) {
		
			$user_notifs = array('1' => array());
			$approver_notifs = array('2' => array());
			if($postdata['notification_for'] == '1'){
				$user_notifs = array('1' => array('email_content' => $postdata['email_template']));
			}else{
				$approver_notifs = array('2' => array('email_content' => $postdata['email_template']));
			}
			
			$notifications = array($postdata['application_type'] => array($user_notifs, $approver_notifs));
			$option = array('main_email_address' => $postdata['main_email_add'], 'notifications' => $notifications);
		}
		
		return $option;
	}
	
	public function parseEmailContent($content) {
		$mat = preg_match_all('/<p><span id="test">(&lt;([a-zA-Z\s])+&gt;)<\/span><\/p>/',$content,$mats);
		foreach($mats[1] as $m) {
			echo $m;
			echo '\n';
		}
	}
	
	public static function getCPIApplications() {
		return self::$CPI_APPLICATIONS;
	}
	 /**
     * get email content for selected company , application type, and user type
     *
     * @access public
     * @param string companyid
     * @param int id -- application type id
     * @param int type_id -- user type
     * @return bool
     */
	public function getEmailContent($companyId, $id, $type_id){
		$settings = self::getCurrentSetting($companyId, self::NOTIFICATIONS);
		$hasEmailContent = false;
		$email_content = array();
		if(!empty($settings) && $settings!=false) {
			//var_dump($settings);
			foreach($settings['notifications'] as $k => $i) {
				if($k == (int)$id){
					foreach($i as $ks => $j) {
						if(($ks+1) == $type_id) {
							$email_content = $j[$type_id]['email_content'];
							$hasEmailContent = true;
							break;
						}
					}
					break;
				}
			}
			if($hasEmailContent) {
				return $email_content;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
    public function getMainEmailAddress($companyId) {
		
		$settings = self::getCurrentSetting($companyId, 1);
		
		if(!empty($settings) && $settings!= false) {
			return $settings['main_email_address'];
		}else{
			return false;
		}
	}
    /**
     * compute difference between current date and previous date in terms of day
     *
     * @access public
     * @param $date
     * return int $days
     */
    static function getPastDays($date) {
        $current = time();
        $days = round(abs($current-strtotime($date))/86400);
        
        return $days;
    }
    
    /**
     * get transactions base on selected setting on application setting
     *
     * @access public
     * @param array $applications
     *  - array of approvers
     * @param array $current_settings
     *  - application settings of admin
     * @return array $new_application
     */
    public function getHistoricalTransaction($applications, $current_setting) {
        $new_application = array();
        $current_setting = (array)$current_setting;
        $past_day_setting = (int)$current_setting['past_days'];
        
        foreach ($applications as $application) {
            $past_day = self::getPastDays($application['date_created']);
            if ($past_day <= $past_day_setting) {
                $new_application[] = $application;    
            }
        }
        return $new_application;
    }
	
	/**
     * get pre-approved days set
     *
     * @access public
     * @param $companyId
     * @return int / string
     */
	public function getPreapprovedDays($companyId) {
	
		// Get Current settings for main application
		$settings = self::getCurrentSetting($companyId, self::MAIN);
		$num_days_preapproved = $settings['current_settings']['num_days_preapproved'];
		return ($num_days_preapproved!= '')? $num_days_preapproved:0;
	}
	
	/**
     * get remind user set
     *
     * @access public
     * @param $companyId
     * @return int / string ( 1 or 0 )
     */
	public function remindUser($companyId) {
		$settings = self::getCurrentSetting($companyId, self::MAIN);
		$remind = $settings['current_settings']['remind_user'];
		return $remind;
	}
}
?>
