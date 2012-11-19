<?php

class AjaxController extends Zend_Controller_Action {
	var $employeeId;
	var $companyId;
	var $baseUrl = '';
	public function init() {
		$sessionVariables = new Zend_Session_Namespace('userSessionVariables');
        $this->employeeId = $sessionVariables->employeeId;
		$this->companyId = $sessionVariables->companyId;
		$fc = Zend_Controller_Front::getInstance();
		$this->baseUrl = $fc->getBaseUrl();
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
	}
	
	public function indexAction() {
	
	}
	
	public function checkexistsAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$form_data = $this->_request->getPost();
		$attendanceadjustment = new AttendanceAdjustmentApplications();
		foreach($form_data['dates'] as $data) {
			$res = $attendanceadjustment->fetchRow("date = '".$data['date']."' AND in_out = '".$data['tType']."' AND e_empno = '".$this->employeeId."' AND (status <> 'cancelled' OR status <> 'expired') ");
			
			if(!empty($res)) {
				$response [] = array('response' => true,'id' => $res['id'], 'status' => $res['status'], 'index' => $data['index']);
			}else{
				$response [] = array('response' => false,'id' => $res['id'], 'status' => $res['status'], 'index' => $data['index']);
			}
		}
		
		//var_dump($form_data);
		echo json_encode(array('response' => $response));
		//$res = $attendanceadjustment->checkExists($form_data);
		//var_dump($form_data);
		//echo json_encode($res);
	}
    
    public function holidayexistsAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$form_data = $this->_request->getPost();
		$holiday = new Holiday();
		$res = $holiday->holidayExists($form_data['date']);
		//var_dump($form_data);
        // var_dump($res);
		echo json_encode($res);
        die();
	}
	
	public function checkleavesexistAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$form_data = $this->_request->getPost();
		$leaveapplications = new LeaveApplications();
		$response = array();
		foreach($form_data['dates'] as $data) {
			$where = " `l_code` = '".$data['leavecode']."' AND `from` = '".$data['from']."' 
								AND `to` = '".$data['to']."' AND `e_empno` = '".$this->employeeId."' 
								AND `status` <> 'cancelled' AND `status` <> 'expired' AND `status` <> 'disapproved'";
			$res = $leaveapplications->fetchRow($where);
			
			if(!empty($res)) {
				$response [] = array('response' => true,'id' => $res['id'], 'status' => $res['status'], 'index' => $data['index']);
			}else{
				$response [] = array('response' => false,'id' => $res['id'], 'status' => $res['status'], 'index' => $data['index']);
			}
		}
		
		//var_dump($form_data);
		echo json_encode(array('response' => $response));
		//echo json_encode($res);
	}
	public function checkovertimeexistsAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$form_data = $this->_request->getPost();
		$overtimeapplications = new OvertimeApplications();
		foreach($form_data['dates'] as $data) {
			$timeIn = $data['hour_in'] . $data['minute_in'];
			$timeOut = $data['hour_out'] . $data['minute_out'];
			//$res = $overtimeapplications->checkExists($form_data, $this->employeeId);
			$res = $overtimeapplications->fetchRow("`date` = '".$data['date']."' AND `e_empno` = '".$this->employeeId."' 
													AND `status` <> 'cancelled' AND `status` <> 'expired' AND `status` <> 'disapproved'");
			$duplicate = '';
			if(!empty($res)) {
				$dup = $overtimeapplications->checkRange($res,$timeIn,$timeOut);
				$duplicate = $dup['duplicate'];
				$status = $dup['status'];
			}
			if($duplicate != '') {
				$response [] = array('response' => true, 'id' => $duplicate, 'status' => $res['status'], 'index' => $data['index']);
			}else{
				$response [] = array('response' => false,'id' => '', 'status' => $res['status'], 'index' => $data['index']);
			}
		}
		//var_dump($form_data);
		echo json_encode(array('response' => $response));
	}
	
	public function checkrestdayexistsAction() {
	
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$form_data = $this->_request->getPost();
		$applications = new ChangeRestDayApplications();
		foreach($form_data['dates'] as $data) {
			$res = $applications->fetchRow("new_rest_day = '".$data['date']."' AND e_empno = '".$this->employeeId."' AND status <> 'cancelled'");
			
			if(!empty($res)) {
				$response [] = array('response' => true,'id' => $res['id'], 'status' => $res['status'], 'index' => $data['index']);
			}else{
				$response [] = array('response' => false,'id' => $res['id'], 'status' => $res['status'], 'index' => $data['index']);
			}
		}
		
		//var_dump($form_data);
		echo json_encode(array('response' => $response));
	}
	public function cancelchangerestdaysAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$form_data = $this->_request->getPost();
		$applications = new ChangeRestDayApplications();
		$applications->setCancelled($form_data['id']);
		
		echo 'success';
	}
	
	public function cancelovertimeAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$form_data = $this->_request->getPost();
		$leaveapplications = new LeaveApplications();
		$leaveapplications->setCancelled($form_data['id']);
		
		echo 'success';
	}
	
	public function cancelleaveAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$form_data = $this->_request->getPost();
		$leaveapplications = new LeaveApplications();
		$leaveapplications->setCancelled($form_data['id']);
		
		echo 'success';
	}
	
	public function cancelattendanceadjustmentAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$form_data = $this->_request->getPost();
		$attendanceadjustment = new AttendanceAdjustmentApplications();
		$attendanceadjustment->setCancelled($form_data['id']);
		
		echo 'success';
	}
	
	public function checkschedrestdaysAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$form_data = $this->_request->getPost();
		$schedrestdays = new SchedRestDay();
		$response = array();
		foreach($form_data['date'] as $data) {
			$check = $schedrestdays->fetchRow("e_empno = '".$this->employeeId."' AND '".$data['date']."' BETWEEN date_from AND date_to");
			if(!empty($check)) {
				$response[] = array('index' => $data['index'], 'date' => $data['date'], 'isexist' => true);
			}else{
				$response[] = array('index' => $data['index'], 'date' => $data['date'], 'isexist' => false);
			}
		}
		echo json_encode(array('response' => $response));
	}
	public function checkschedshiftsAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$form_data = $this->_request->getPost();
		$schedshifts = new WorkSchedules();
		$response = array();
		foreach($form_data['date'] as $data) {
			$check = $schedshifts->fetchRow("e_empno = '".$this->employeeId."' AND '".$data['date']."' BETWEEN date_from AND date_to AND shift_code = '".$data['shift_code']."'");
			if(!empty($check)) {
				$response[] = array('index' => $data['index'], 'date' => $data['date'], 'isexist' => true);
			}else{
				$response[] = array('index' => $data['index'], 'date' => $data['date'], 'isexist' => false);
			}
		}
		echo json_encode(array('response' => $response));
	}
	public function checkchangeshiftsAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$form_data = $this->_request->getPost();
		$changeshift = new ChangeShiftApplications();
		$response = array();
		foreach($form_data['dates'] as $data) {
			$res = $changeshift->fetchRow("`date` = '".$data['date']."' AND `to_shift_code` = '".$data['to_code']."' AND 
						`from_shift_code` = '".$data['from_code']."' AND `e_empno` = '".$this->employeeId."' 
						AND `status` <> 'cancelled' AND `status` <> 'disapproved' AND `status` <> 'expired'");
			
			if(!empty($res)) {
				$response [] = array('response' => true,'id' => $res['id'], 'status' => $res['status'], 'index' => $data['index']);
			}else{
				$response [] = array('response' => false,'id' => $res['id'], 'status' => $res['status'], 'index' => $data['index']);
			}
		}
		
		//var_dump($form_data);
		echo json_encode(array('response' => $response));
	}
	
	public function checkperiodAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$form_data = $this->_request->getPost();
		$cutoff = new CutOff();
		$where = "company_id = '".$this->companyId."' AND month = '".$form_data['month']."' 
						AND period_no = '".$form_data['period']."'";
		$row = $cutoff->fetchRow($where);
		$response = array();
		if(!empty($row)) {
			$response = array('response' => true);
		}else{
			$response = array('response' => false);
		}
		
		echo json_encode($response);
	}
}
?>