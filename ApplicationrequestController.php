<?php
class ApplicationrequestController extends Zend_Controller_Action {
	var $employeeId;
	var $companyId;
	var $baseUrl = '';
	var $warn = false;
	var $warn_message = 'CutOff Date is near';
	public function init() {
		$sessionVariables = new Zend_Session_Namespace('userSessionVariables');
        $this->employeeId = $sessionVariables->employeeId;
		$this->companyId = $sessionVariables->companyId;
		$fc = Zend_Controller_Front::getInstance();
		$this->baseUrl = $fc->getBaseUrl();
		$approver = new Approvers();
		$this->warn = $approver->doWarnByCutOff($this->companyId);
	}
	public function indexAction() {
		$this->_redirect('applicationrequest/attendanceadjustments');
	}
	
	public function attendanceadjustmentsAction() {
        $this->view->placeholder('title')->set('Attendance Adjustments');
        $this->view->placeholder('head_title')->set('Application Requests');
		$form = new ApproveAttendanceAdjustmentForm();
        $employeeSelect = new EmployeeSelect('e_empno');
        $employeeSelect->setLabel(' ')
            ->setRequired(true)
            ->addValidator(new Zend_Validate_NotEmpty());
		$approvers = new Approvers();
        $employees = new Employees();
        
        if (!$employees->isSupervisor($this->employeeId)) {
            $this->_redirect('error/supervisorsonly/');
        }
        $attendanceAdjustmentApplications = new AttendanceAdjustmentApplications();
		$attendanceadjustmentsAction = new AttendanceAdjustmentActions();
		$filter_form = new ApplicationAttendanceAdjustmentFormHistory();
        $where = $approvers->getAllByApprovers2($this->employeeId, $this->companyId);
        $pending_where = $where;
		$isPagination = true;
		if($this->_request->isPost() && $this->_request->getPost('submit_button')!='') {
			$filters = $this->_request->getPost();
			$where .= FilterHelperHistorical::combineFilters($filters);
			$where = str_replace('status','a.status',$where);
			$where = str_replace('in_out','a.in_out',$where);
			$where = str_replace('date','a.date',$where);
            //$where = str_replace('e_empno','a.e_empno',$where);
			$isPagination = false;
            $employeeSelect->setValue(array($filters['e_empno']));
		}
      
        $order = "date_created DESC";
		if($where != '') {
			//$applications = $attendanceAdjustmentApplications->fetchAll($where, $order);
			
			//$applications = $attendanceAdjustmentApplications->getAll($where);
			// var_dump($applications); die();
			/* Return Data only if the current approver is at the right level */
			// if(!$approvers->isOr($this->companyId, Approvers::ATTENDANCEADJUSTMENT)) {
				// $pending_applications = $approvers->checkLevels($pending_applications, $this->companyId, $this->employeeId);
				// $applications = $approvers->checkLevels($applications, $this->companyId, $this->employeeId);
			// }
			$pending_applications = $attendanceAdjustmentApplications->getAll($pending_where, 'pending');
			$applications = $attendanceAdjustmentApplications->getAll($where, 'pending');
			/* Return Data only if the current approver is at the right level */
			if(!$approvers->isOr($this->companyId, Approvers::ATTENDANCEADJUSTMENT)) {
				$pending_applications = $attendanceAdjustmentApplications->checkLevels($pending_applications, $this->employeeId);
				$applications = $attendanceAdjustmentApplications->checkLevels($applications, $this->employeeId);
			}
			
			$applications = $attendanceadjustmentsAction->checkApprovedBy($applications, $this->employeeId);
			$pending_applications = $attendanceadjustmentsAction->checkApprovedBy($pending_applications, $this->employeeId);
			//$applications = $approvers->checkApprovedBy($applications, $this->employeeId, 'attendance_queue_new');
			
			if ( isset($filters['e_empno']) && !empty($filters['e_empno']) ) {
				$applications = FilterHelperHistorical::search_emp_array($filters['e_empno'], $applications);
			}
		}else{
			$applications = array();
		}
		
		if($this->warn) {
			$this->view->warn = $this->warn_message;
		}
        $this->view->companyId = $this->companyId;
        $this->view->pending_applications = $pending_applications;
      	$this->view->applications = $applications;
			
        $this->view->form_historical = $filter_form;
		$this->view->isPaginated = $isPagination;
        $this->view->postData = $filters;
        $this->view->form = $form;
        $this->view->employee = $employeeSelect;
	}
	
	public function shiftsAction() {
		$this->view->placeholder('title')->set('Change Shifts');
        $this->view->placeholder('head_title')->set('Application Requests');
        $employees = new Employees();
		$approvers = new Approvers();
		$employeeSelect = new EmployeeSelect('e_empno');
        $employeeSelect->setLabel(' ')
            ->setRequired(true)
            ->addValidator(new Zend_Validate_NotEmpty());
        if (!$employees->isSupervisor($this->employeeId)) {
            $this->_redirect('error/supervisorsonly/');
        }
		
		$changeShiftApplications = new ChangeShiftApplications();
		$filter_form = new ChangeShiftFormHistorical();
        $where = $approvers->getAllByApprovers2($this->employeeId, $this->companyId);
		$pending_where = $where;
		$isPagination = true;
		if($this->_request->isPost()) {
			$filters = $this->_request->getPost();
			$where .= FilterHelperHistorical::combineFilters($filters);
			$isPagination = false;
			$employeeSelect->setValue(array($filters['e_empno']));
		}
        $order = "date_created DESC";
		if($where != '') {
			//$applications = $changeShiftApplications->fetchAll($where, $order);
			$applications = $changeShiftApplications->getAll($where, 'pending');
			//echo $applications;
			if(!$approvers->isOr($this->companyId, 2)) {
				$applications = $approvers->checkLevels($applications, $this->companyId, $this->employeeId);
			}
			
			$applications = $approvers->checkApprovedBy($applications, $this->employeeId, 'change_shift_queue_new');
		}else{
			$applications = array();
		}
		if($this->warn) {
			$this->view->warn = $this->warn_message;
		}
        $this->view->companyId = $this->companyId;
        $this->view->applications = $applications;
		$this->view->form_historical = $filter_form;
       	$this->view->isPaginated = $isPagination;
		$this->view->employee = $employeeSelect;
	}
	
	public function restdaysAction() {
		$this->view->placeholder('title')->set('Change Rest Days');
        $this->view->placeholder('head_title')->set('Application Requests');
		$employees = new Employees();
		$approvers = new Approvers();
        if (!$employees->isSupervisor($this->employeeId)) {
            $this->_redirect('error/supervisorsonly/');
        }
		
		$changeRestDaysApplication = new ChangeRestDayApplications();
		//$filter_form = new ChangeShiftFormHistorical();
		$isPagination = false;
        $where = $approvers->getAllByApprovers2($this->employeeId, $this->companyId);
		$pending_where = $where;
		if($this->_request->isPost()) {
			$filters = $this->_request->getPost();
			$where .= FilterHelperHistorical::combineFilters($filters);
			$where = str_replace('status','a.status',$where);
			$where = str_replace('date','a.date_created',$where);
			$isPagination = false;
		}
        $order = "date_created DESC";
		if($where != '') {
			//$applications = $changeShiftApplications->fetchAll($where, $order);
			$pending_applications = $changeRestDaysApplication->getAll($where, 'pending');
			$applications = $changeRestDaysApplication->getAll($where);
			if(!$approvers->isOr($this->companyId, Approvers::CHANGERESTDAY)) {
				$pending_applications = $approvers->checkLevels($pending_applications, $this->companyId, $this->employeeId);
				$applications = $approvers->checkLevels($applications, $this->companyId, $this->employeeId);
			}
			
			$pending_applications = $approvers->checkApprovedBy($pending_applications, $this->employeeId, 'change_rest_day_queue_new');
			$applications = $approvers->checkApprovedBy($applications, $this->employeeId, 'change_rest_day_queue_new');
        }else{
			$applications = array();
		}
		if($this->warn) {
			$this->view->warn = $this->warn_message;
		}
        $this->view->companyId = $this->companyId;
        $this->view->pending_applications = $pending_applications;
		$this->view->applications = $applications;
		$this->view->postData = $filters;
        
	}
	
	public function leavesAction() {
		$this->view->placeholder('title')->set('Leaves');
        $this->view->placeholder('head_title')->set('Application Requests');
        $form = new LeaveApproveForm();
        $employees = new Employees();
		$approvers = new Approvers();
		$leaveApplications = new LeaveApplications();
		$filter_form = new LeaveApplicationFormHistory();
		
        if (!$employees->isSupervisor($this->employeeId)) {
            $this->_redirect('error/supervisorsonly/');
        }
		$this->view->form = $form;
		
        $where = $approvers->getAllByApprovers2($this->employeeId, $this->companyId);
		
		$isPagination = true;
		if($this->_request->isPost() && $this->_request->getPost('submit_button')!='') {
			$filters = $this->_request->getPost();
			$where .= FilterHelperHistorical::combineFilters($filters);
			$where = str_replace("date","date_created",$where); 
			$isPagination = false;
		}
		
        $order = "date_created DESC";
		if($where != '') {
        //$applications = $attendanceAdjustmentApplications->fetchAll($where, $order);
		$applications = $leaveApplications->getAll($where, 'pending');
		//var_dump($applications); die();
		/* Return Data only if the current approver is at the right level */
		if(!$approvers->isOr($this->companyId, Approvers::LEAVES)) {
			$applications = $approvers->checkLevels($applications, $this->companyId, $this->employeeId);
		}
		
		$applications = $approvers->checkApprovedBy($applications, $this->employeeId, 'leaves_queue_new');
		}else{
			$applications = array();
		}
		if($this->warn) {
			$this->view->warn = $this->warn_message;
		}
        $this->view->companyId = $this->companyId;
      	$this->view->userLeaves = $applications;
        $this->view->form_historical = $filter_form;
	}
	
	public function overtimeAction() {
		$this->view->placeholder('title')->set('Overtime');
        $this->view->placeholder('head_title')->set('Application Requests');
		//$form = new OvertimeApproveForm();
        $employees = new Employees();
		$approvers = new Approvers();
		$overtimeApplications = new OvertimeApplications();
		$filter_form = new OvertimeApplicationFormHistory();
		
        if (!$employees->isSupervisor($this->employeeId)) {
            $this->_redirect('error/supervisorsonly/');
        }
		$this->view->form = $form;
		
        $where = $approvers->getAllByApprovers2($this->employeeId, $this->companyId);
		
		$isPagination = true;
		if($this->_request->isPost() && $this->_request->getPost('submit_button')!='') {
			$filters = $this->_request->getPost();
			$where .= FilterHelperHistorical::combineFilters($filters);
			$where = str_replace("date","date_created",$where); 
			$isPagination = false;
		}
		if($where != '') {
			$order = "date_created DESC";
			//$applications = $attendanceAdjustmentApplications->fetchAll($where, $order);
			$applications = $overtimeApplications->getAll($where, $order);
			
			/* Return Data only if the current approver is at the right level */
			if(!$approvers->isOr($this->companyId, Approvers::OVERTIME)) {
				$applications = $approvers->checkLevels($applications, $this->companyId, $this->employeeId);
			}
		
			$applications = $approvers->checkApprovedBy($applications, $this->employeeId, 'overtime_queue_new');
		}else{
			$applications = array();
		}
		if($this->warn) {
			$this->view->warn = $this->warn_message;
		}
        $this->view->companyId = $this->companyId;
      	$this->view->applications = $applications;
        $this->view->form_historical = $filter_form;
		
       // print_r($this);
		//die();
	}
	
	/* Action Controller For Ajax Notifications in Application Requests */
	public function ajaxgetnotifsAction() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$sessionVariables = new Zend_Session_Namespace('userSessionVariables');
        $employeeId = $sessionVariables->employeeId;
		$companyId = $sessionVariables->companyId;
		$attendanceadjustments = new AttendanceAdjustmentApplications();
		$attendanceadjustmentsAction = new AttendanceAdjustmentActions();
		$approvers = new Approvers();
		$shifts = new ChangeShiftApplications();
		$rests = new ChangeRestDayApplications();
		$leaves = new LeaveApplications();
		$overtime = new OvertimeApplications();
		$order = "date_created DESC";
		//$where = $approvers->getAllByApprovers($where, $employeeId, $companyId);
		$where = $approvers->getAllByApprovers2($this->employeeId, $this->companyId);
		if($where != '') {
			/* For Attendance Adjustments Applications */
			$applications = $attendanceadjustments->getAll($where, 'pending');
			/* Return Data only if the current approver is at the right level */
			if(!$approvers->isOr($this->companyId, Approvers::ATTENDANCEADJUSTMENT)) {
				$applications = $attendanceadjustments->checkLevels($applications, $this->employeeId);
			}
			
			$applications = $attendanceadjustmentsAction->checkApprovedBy($applications, $this->employeeId);
			$att = $applications;
			//var_dump($att);
			/* For Change Shifts */
			
			$applications = $shifts->getAll($where, 'pending');
			
			/* Return Data only if the current approver is at the right level */
			if(!$approvers->isOr($this->companyId, Approvers::CHANGESHIFT)) {
				$applications = $approvers->checkLevels($applications, $this->companyId, $this->employeeId);
			}
			$applications = $approvers->checkApprovedBy($applications, $this->employeeId, 'change_shift_queue_new');
			$shift_res = $applications;
			
			/* For Rest Days Applications */
			$applications = $rests->getAll($where,'pending');
			/* Return Data only if the current approver is at the right level */
			if(!$approvers->isOr($this->companyId, Approvers::CHANGERESTDAY)) {
				$applications = $approvers->checkLevels($applications, $this->companyId, $this->employeeId);
			}
			$applications = $approvers->checkApprovedBy($applications, $this->employeeId, 'change_rest_day_queue_new');
			$restdays = $applications;
			
			/* For Leave Applications */
			$applications = $leaves->getAll($where,'pending');
			/* Return Data only if the current approver is at the right level */
			if(!$approvers->isOr($this->companyId,Approvers::LEAVES)) {
				$applications = $approvers->checkLevels($applications, $this->companyId, $this->employeeId);
			}
			$applications = $approvers->checkApprovedBy($applications, $this->employeeId, 'leaves_queue_new');
			$leave = $applications;
			
			/* For Overtime Applications */
			$applications = $overtime->getAll($where, 'pending');
			/* Return Data only if the current approver is at the right level */
			if(!$approvers->isOr($this->companyId,Approvers::OVERTIME)) {
				$applications = $approvers->checkLevels($applications, $this->companyId, $this->employeeId);
			}
			$applications = $approvers->checkApprovedBy($applications, $this->employeeId, 'overtime_queue_new');
			$ot = $applications;
		
			$att = (count($att) > 0)? count($att):false;
			$shift = (count($shift_res) > 0)? count($shift_res):false;
			$rest_res = (count($restdays) > 0)? count($restdays):false;
			$leave_res = (count($leave) > 0)? count($leave):false;
			$ot_res = (count($ot) > 0)? count($ot):false;
		}
		$response = ($att || $shift || $rest_res || $leave_res || $ot_res)? true:false;
		echo json_encode(array('response' => $response, 'attendanceadjustments' => $att, 
			'shifts' => $shift, 'restdays' => $rest_res, 'leaves' => $leave_res, 'overtime' => $ot_res ));
	}
}
?>