<?php
/**
 *  Class Approver Settings -- Abstract
 *	 Parent / Abstract Class for Approvers
 *  Also used solely for Approver Settings
 *  @author: prog82
 */
abstract class ApproverSettings extends Zend_Db_Table {
	
	/* Define array of Default Options */
	static $DEFAULTOPTIONS = array('attendance_adjustment' => '1', 'change_shift' => '1',
							'change_rest_day' => '1', 'leaves' => '1', 'overtime' => '1',
							'approvers_num' => '1', 'allow_main_approver' => '0',
							'warn_cutoff' => '0', 'warn_time_days' => '0'
						);
	/* Define Constants Encapsulation for all Application Types */
	const ATTENDANCEADJUSTMENT = 1;
	const CHANGESHIFT = 2;
	const CHANGERESTDAY = 3;
	const LEAVES = 4;
	const OVERTIME = 5;
	
	public function applySettings($employeeId, $companyId, $settings) {
		if(self::settingsExist($companyId)) {
			self::updateSettings($employeeId,$companyId, $settings);
		}else{
			self::createNewSetting($employeeId,$companyId, $settings);
		}
	}
	
	public function settingsExist($companyId) {
		$db = Zend_Registry::get('db');
		$where = "company_id = '{$companyId}'";
		$sql = $db->select();
		$sql = $sql->from('approver_settings');
		$sql = $sql->where($where);
		$row = $db->fetchRow($sql);
		
		if(!empty($row)) {
			return true;
		}else{
			return false;
		}
	}
	
	protected function createNewSetting($employeeId, $companyId, $settings) {
		$options = array();
		$optionsDefault = self::$DEFAULTOPTIONS;
		
		$options = array('attendance_adjustment' => $settings['attendance_adjustment'], 
							'change_shift' => $settings['change_shift'],
							'change_rest_day' => $settings['change_rest_day'],
							'leaves' => $settings['leaves'], 
							'overtime' => $settings['overtime'],
							'approvers_num' => $settings['approver_num'], 
							'allow_main_approver' => $settings['allow_main_approver'],
							'warn_cutoff' => $settings['warn_cutoff'], 
							'warn_time_days' => $settings['warn_time_days']
						);
		$options = array_merge($optionsDefault,$options);
		$opt = array('default' => $optionsDefault, 'current_settings' => $options , 'previous_settings' => array());
		$db = Zend_Registry::get('db');
		$db->insert('approver_settings', 
					array('id' => '', 'company_id' => trim($companyId),
						'defined_settings' => json_encode($opt),
						'updated_by' => trim($employeeId)
					));
		
	}
	
	protected function updateSettings($employeeId, $companyId, $settings) {
		$options = array();
		$companyId = trim($companyId);
		$optionsDefault = self::$DEFAULTOPTIONS;
		$currentOptions = self::getCurrentSettings($companyId);
		
		$options = array('attendance_adjustment' => $settings['attendance_adjustment'], 
							'change_shift' => $settings['change_shift'],
							'change_rest_day' => $settings['change_rest_day'],
							'leaves' => $settings['leaves'], 
							'overtime' => $settings['overtime'],
							'approvers_num' => $settings['approver_num'], 
							'allow_main_approver' => $settings['allow_main_approver'],
							'warn_cutoff' => $settings['warn_cutoff'], 
							'warn_time_days' => $settings['warn_time_days']
						);
		//$options = extract($currentOptions, EXTR_OVERWRITE
		$options = array_merge($currentOptions, $options);
		
		$opt = array('default' => $optionsDefault, 'current_settings' => $options , 'previous_settings' => $currentOptions);
		$db = Zend_Registry::get('db');
		$db->update('approver_settings', 
					array('defined_settings' => json_encode($opt),
						  'updated_by' => trim($employeeId),
						  'last_updated' => date('Y-m-d H:i:s')
					),"company_id = '{$companyId}'");
	}
	
	public function getCurrentSettings($companyId) {
		$db = Zend_Registry::get('db');
		$sql = $db->select();
		$sql = $sql->from('approver_settings')->where("company_id ='{$companyId}'");
		$row = $db->fetchRow($sql);
		return (array)json_decode($row['defined_settings'])->current_settings;
	}
	
	public function getDefaultSettings() {
		return self::$DEFAULTOPTIONS;
	}
	
	public function isOr($companyId, $application) {
		$settings = self::getCurrentSettings($companyId);
		if($application == self::ATTENDANCEADJUSTMENT) {
			if($settings['attendance_adjustment'] == '2')
				return true;
		}else if($application == self::CHANGESHIFT) {
			if($settings['change_shift'] == '2')
				return true;
		}else if($application == self::CHANGERESTDAY) {
			if($settings['change_rest_day'] == '2') 
				return true;
		}else if($application == self::LEAVES) {
			if($settings['leaves'] == '2') 
				return true;
		}else if($application == self::OVERTIME ) {
			if($settings['overtime'] == '2' ) 
				return true;
		}else{
			return false;
		}
		
		return false;
	}
	
	public function getNumApprovers($companyId) {
		$settings = self::getCurrentSettings($companyId);
		return $settings['approvers_num'];
	}
	
	public function isMainApproverAllowed($companyId) {
		$settings = self::getCurrentSettings($companyId);
		if((int)$settings['allow_main_approver']){
			return true;
		}else{
			return false;
		}
	}
}
?>