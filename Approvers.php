<?php
/**
 *  Class Model Approvers
 *  for approvers
 *  @author: prog82
 */
 
class Approvers extends ApproverSettings {
    protected $_name = 'approvers';
	
	/**
     *  Creates New Approver
     *  @access public
     *  @params: string e_empno (employee id), 
	 *  @param company id
     *  @return bool
     */
	public function createNew($e_empno, $companyId) {
		$res = false;
		$e_empno = trim($e_empno);
		$db = Zend_Registry::get('db');
		$old = $db->fetchRow("SELECT * FROM user_approvers WHERE e_empno='{$e_empno}'");
		if(!empty($old)) {
			return false;
		}else{
			//$new = $this->createRow();
			//$new->e_empno = $e_empno;
			//$new->save();
			$new = $db->insert('user_approvers', array('id' => '', 'e_empno' => $e_empno, 'company_id' => $companyId));
			return true;
		}
	}
	
    public function getLevel2($employeeId, $Supervisor) {
        $where = "e_empno = '{$employeeId}' AND level_1 = '{$Supervisor}'";
        $level2 = $this->fetchRow($where);
        return $level2->level_2;
    }

    public function getApprovers($employeeId) {
        $where = "e_empno = '{$employeeId}'";
        return $this->fetchRow($where);
    }

    public function isApprover($employeeId) {
        // $isApprover= false;
        // $where = "level_1 = '{$employeeId}' or level_2 = '{$employeeId}'";
        // $rowSet = $this->fetchAll($where);
        // $numRows = $rowSet->count();
        // if ($numRows > 0) {
            // $isApprover = true;
        // }
        // return $isApprover;
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('user_approvers');
		$sql = $sql->where("e_empno = '{$employeeId}'");
		$row = $this->_db->fetchRow($sql);
		
		if(!empty($row)) {
			return true;
		}else{
			return false;
		}
    }
	
	public function deleteApprovers($data) {
		$where = '';
		$i = 0;
		$db = Zend_Registry::get('db');
		if(!empty($data)) {
			foreach($data as $id) {
				$where .="id ='{$id}'";
				if($i==(count($data)-1))
					break;
				else{
					$where .=" OR ";
					$i++;
				}
			}
			//$this->delete($where);
			$db->query("Delete from `user_approvers` WHERE $where");
		}
	}
	
	public function doesUserHasApprover($employeeId){	
		if($this->checkExist2($employeeId)) {
			return true;
		}else{
			return false;
		}
	}
	/**
     *  Get the approver details
     *  @access public
	 *  @param company id
     *  @return result array
     */
	public function getApproversDetails($companyId) {
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from(array('a' => 'user_approvers'), array( 'e_empno' => 'a.e_empno','ap_id' => 'a.id','date_added' => 'a.date_added'));
		$sql = $sql->joinLeft(array('b' => 'empmas'), 'b.e_empno = a.e_empno', 
					array('name'=>'CONCAT(b.lastname,\', \',b.firstname)'));
		$sql = $sql->order('name ASC');
		
		return $this->_db->query($sql)->fetchAll();
	}
	
	/**
     *  Get details of employee who are not yet approvers
     *  @access public
     *  @return result array
     */
	public function getNotApproversDetails() {
		 $db = Zend_Registry::get('db');
		 $sql = $db->select();
		 $sql = $sql->from('empmas', array('empno' => 'e_empno' ,'real_name' => 'CONCAT(lastname,\' ,\',firstname)'));
		 $sql = $sql->where('e_empno NOT IN (SELECT e_empno FROM `user_approvers`) 
							AND e_empno NOT IN (SELECT username as e_empno FROM `users` 
							where user_type = \'adm\')');
		 $sql = $sql->order('real_name ASC');
		 return $db->query($sql)->fetchAll();
	}
	public function unset_arr(&$level, $key) {
		unset($level[$key]);
	}
	
	/**
     *  get all employees with their corresponding approvers and approver level
     *  @access public
	 *  @param company id
     *  @return result array
     */
	public function getEmployeesApproversAssigned($companyId) {
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from(array('a' => 'empmas') ,array('empno' => 'a.e_empno', 'emp_name' => 'CONCAT(a.lastname,\',\',a.firstname)'));
		$sql = $sql ->joinLeft(array('b' => 'approver_user_level'), 'b.e_empno = a.e_empno', 
									array('level' => 'b.level' , 'app_empno' => 'b.approver' , 
											'name' => '(SELECT CONCAT(lastname,\',\',firstname) FROM empmas WHERE e_empno = b.approver)')
								);
		$sql = $sql->where("a.company_id = '".$companyId."'");
		
		return $this->_db->fetchAll($sql);
	}
	
	/**
     *  Assign approvers to employees
     *  @access public
     *  @params: postdata -- formdata submitted
	 *  @param company id
     *  @return none
     */
	public function assignApprovers2($postData, $companyId) {
		$approver = trim($postData['approvers']);
		$level = trim($postData['approver_level']);
		foreach($postData['_assign'] as $assign) {
			$assign = trim($assign);
			if($assign == $approver)
				continue;
				
			/*if($this->checkExist2($assign)) {
				if($this->approverExist($assign, $approver)){
					$message = 'Some Approvers already exist. No need to overwrite';
				}else if($this->levelExist($assign, $level)) {
					$this->_db->update('approver_user_level', array('approver' => $approver), 
								"e_empno = '{$assign}' AND level = '{$level}'"
							);
				}else{
					 $this->_db->insert('approver_user_level',array('e_empno' => $assign, 
							'approver' => $approver, 'company_id' => trim($companyId), 
							'level' => $level));
				}
			}else{
				 $this->_db->insert('approver_user_level',array('e_empno' => $assign, 
							'approver' => $approver, 'company_id' => trim($companyId), 
							'level' => $level));
			}*/
			if($this->checkExist($assign)) {
				if(!$this->isMaxApproverCounts($assign)) {
					if(!$this->approverExist($assign, $approver)) {
						 $this->_db->insert('approver_user_level',array('e_empno' => $assign, 
							'approver' => $approver, 'company_id' => trim($companyId), 
							'level' => $level));
					}
				}
			}else{
				 $this->_db->insert('approver_user_level',array('e_empno' => $assign, 
							'approver' => $approver, 'company_id' => trim($companyId), 
							'level' => $level));
			}
		}
	}
	
	/**
     *  Assign approvers to employee via upload
     *  @access public
     *  @params: string e_empno (employee id), 
	 *  @param: string approver -- approvers employee id
	 *  @param: int / string level -- level for the approver
	 *  @param company id
     *  @return none
     */
	public function assignApproversUpload($e_empno, $approver, $level, $companyId) {
			
			/*if($this->checkExist2($e_empno)) {
				if(!$this->approverExist($e_empno, $approver) && $this->levelExist($e_empno, $level)) {
					$this->_db->update('approver_user_level', array('approver' => $approver), 
								"e_empno = '{$assign}' AND level = '{$level}'"
							);
				}else{
					 $this->_db->insert('approver_user_level',array('e_empno' => $e_empno, 
							'approver' => $approver, 'company_id' => trim($companyId), 
							'level' => $level));
				}
			}else{
				 $this->_db->insert('approver_user_level',array('e_empno' => $e_empno, 
							'approver' => $approver, 'company_id' => trim($companyId), 
							'level' => $level));
			}*/
			if($this->checkExist($e_empno)) {
				if(!$this->isMaxApproverCounts($e_empno)) {
					if(!$this->approverExist($e_empno, $approver)) {
						 $this->_db->insert('approver_user_level',array('e_empno' => $e_empno, 
							'approver' => $approver, 'company_id' => trim($companyId), 
							'level' => $level));
					}
				}
			}else{
				 $this->_db->insert('approver_user_level',array('e_empno' => $e_empno, 
							'approver' => $approver, 'company_id' => trim($companyId), 
							'level' => $level));
			}
		
	}
	
	/**
     * Checks if assigned employee reached the maximum number of approvers
     *  @access public
     *  @params: string $assign -- employeeid
     *  @return bool
     */
	public function isMaxApproverCounts($assign) {
		$sessionVariables = new Zend_Session_Namespace('userSessionVariables');
		$companyId = $sessionVariables->companyId;
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approver_user_level');
		$sql = $sql->where("e_empno = '{$assign}'");
		$row = $this->_db->fetchRow($sql);
		$num_approvers = (int)$this->getNumApprovers($companyId);
		
		if(count($row) == $num_approvers ){
			return true;
		}else{
			return false;
		}
	}
	
	/**
     * Checks if approver exist
     *  @access public
     *  @params: string assign -- employeeid, 
	 *  @param approver -- approver employee id
     *  @return bool
     */
	public function approverExist($assign, $approver) {
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approver_user_level');
		$sql = $sql->where("e_empno = '{$assign}' AND approver = '{$approver}'");
		$row = $this->_db->fetchRow($sql);
		if(!empty($row)) {
			return true;
		}else{
			return false;
		}
	}
	
	/**
     * Checks if level exist in the approver_user_level table for the employeeid
     *  @access public
     *  @params: string assign -- employeeid, 
	 *  @param level -- level number
     *  @return bool
     */
	public function levelExist($assign, $level) {
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approver_user_level');
		$sql = $sql->where("e_empno = '{$assign}' AND level = '{$level}'");
		$row = $this->_db->fetchAll($sql);
		if(!empty($row)) {
			return true;
		}else{
			return false;
		}
	}
	
	public function checkExist2($employee) {
		//$db = Zend_Registry::get('db');
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approver_user_level');
		$sql = $sql->where("e_empno = '{$employee}'");
		$row = $this->_db->fetchRow($sql);
		if(!empty($row)){
			return true;
		}else{
			return false;
		}
	}
	
	public function assignApprovers($postData) {
		$message = '';
		foreach($postData['_assign'] as $assign) {
			$assign = trim($assign);
			if($assign == trim($postData['approvers']))
				continue;
			
			if($this->checkExist($assign)) {
			  $level = json_decode($this->getLevels($assign));
			  $new_level = array($postData['approver_level'] => $postData['approvers']);
			  if($this->isApproverExist($assign, $postData)) {
				$message = 'Some Approvers already exist. No need to overwrite';
			  }else{
				foreach($level as $key => $val) {
					if($key == $postData['approver_level']) {
						unset($level->{$key});
						break;
					}
				}
				$level = (array)$level;
				$level = $new_level + $level;
			  }
			  $this->_db->update('approvers_2',array('level' => json_encode($level)), "e_empno = '$assign'");
			}else{
			  $level = json_encode(array($postData['approver_level'] => $postData['approvers']));
			  $this->_db->insert('approvers_2',array('id' => '','e_empno' => $assign, 'level' => $level));
			}
		}
		
		return $message;
	}
	
	public function isApproverExist2($employee, $level) {
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approvers_2');
		//$sql = $sql->where("approver = '{$
	}
	public function isApproverExist($employee, $level) {
		$levels = (array)json_decode($this->getLevels($employee));
		if(array_search($level['approvers'] , $levels)!=''){
			return true;
		}
		return false;
	}
	
	public function checkExist($employee) {
		//$db = Zend_Registry::get('db');
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approvers_2');
		$sql = $sql->where("e_empno = '{$employee}'");
		$row = $this->_db->fetchRow($sql);
		if(!empty($row)){
			return true;
		}else{
			return false;
		}
	}
	
	
	
	public function getLevels($employee) {
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approvers_2');
		$sql = $sql->where("e_empno = '{$employee}'");
		$row = $this->_db->fetchRow($sql);
		
		return $row['level'];
	}
	
	public function getLevel($approver, $employee) {
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approver_user_level');
		$sql = $sql->where("e_empno = '".$employee."' AND approver = '".$approver."'");
		$row = $this->_db->fetchRow($sql);
		
		return $row['level'];
	}
	
	/**
	  *	Get the number of counts of approver by level
	  *
	  *
	 **/
	public function getCountByLevel($employeeId, $level) {
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approver_user_level',array('count' => 'count(id)'));
		$sql = $sql->where("e_empno = '".$employeeId."' AND level = '".$level."'");
		$row = $this->_db->fetchRow($sql);
		
		return $row['count'];
	}
	public function getApproverByLevel($employeeId, $level) {
		
	}
	public function getApproversAllLevel($employeeId) {
		
		$sessionVariables = new Zend_Session_Namespace('userSessionVariables');
		$companyId = $sessionVariables->companyId;
		$current = $this->getCurrentSettings($companyId);
		$approver_num = $current['approvers_num'];
		$where = "e_empno = '{$employeeId}'";
		if($current['allow_main_approver'] == '1')
			$where .=" OR level = '0' ";
	
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approver_user_level');
		$sql = $sql->where($where);
		$row = $this->_db->fetchAll($sql,$approver_num);
		
		//$approvers = json_decode($row['level']);
		return $row;
	}
	public function getApproversByLevel($employeeId, $level) {
		
		$sessionVariables = new Zend_Session_Namespace('userSessionVariables');
		$companyId = $sessionVariables->companyId;
		$current = $this->getCurrentSettings($companyId);
		$where = "e_empno = '{$employeeId}' AND level = '$level'";
		if($current['allow_main_approver'] == '1')
			$where .=" OR level = '0' ";
	
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approver_user_level');
		$sql = $sql->where($where);
		$row = $this->_db->fetchAll($sql);
		
		//$approvers = json_decode($row['level']);
		
		return $row;
	}
	public function checkLevels($data, $companyId, $employeeId, $level, $count_by_level) {
		$result = array();
		foreach($data as $item) {
		
			if($this->isLevelMatched( $item['approved_count'],$item['e_empno'], $employeeId)){
				$result[] = $item;
			}
		}
		
		return $result;
	}
	
	public function isLevelMatched($count, $e_empno, $approver) {
		$count = $count + 1;
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approver_user_level');
		$sql = $sql->where("e_empno = '$e_empno' AND approver = '$approver' AND level = '$count'");
		$row = $this->_db->fetchRow($sql);
		
		if(!empty($row)) {
			return true;
		}else{
			return false;
		}
	}
	/* Check if current approver already approved the application 
	 *  and return the validated application data
	*/
	public function checkApprovedBy($data, $employeeId, $table) {
		$result = array();
		foreach($data as $item) {
			if($this->isCheckedBy($item['id'], $employeeId, $table) == false) {
				$result[] = $item;
			}
		}
		
		return $result;
	}
	/* Check if approver approved/disapproved the application in attendance queue */
	public function isCheckedBy($application_id, $employeeId, $table) {
		$employeeId = trim($employeeId);
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from($table);
		$sql = $sql->where("application_id = '{$application_id}' AND 
							( FIND_IN_SET('$employeeId',approved_by_set) > 0 
							OR FIND_IN_SET('$employeeId',disapproved_by_set) > 0)");
		
		$row = $this->_db->fetchAll($sql);
		
		if(!empty($row)) {
			return true;
		}else{
			return false;
		}
	}
	
	public function getAllByApprovers2($employeeId, $companyId) {
		$current = $this->getCurrentSettings($companyId);
		$approver_num = $current['approvers_num'];
		//echo $approver_num;
		$where1 = "company_id = '{$companyId}' AND approver = '{$employeeId}'";
		//$start = ($current['allow_main_approver'] == '1')? 0:1;
		if($current['allow_main_approver'] == '1') {
			$where1 .=" AND ( level >= 0 AND level <= $approver_num) ";
		}else{
			$where1 .=" AND ( level > 0 AND level <= $approver_num) ";
		}
		//echo $where1;
		
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approver_user_level');
		$sql = $sql->where($where1);
		$row = $this->_db->fetchAll($sql);
		//$order = "date_created DESC";
		//$where .= " status = 'pending' AND ";
		$where = "";
		if(count($row) > 0) {
			$where = "(";
			$j = 0;
			//$row = array_unique($row);
			$new_r = array();
			foreach($row as $e) {
				$new_r[] = $e['e_empno'];
			}
			
			$row = array_unique($new_r);
			foreach($row as $e) {
				//$where .=" e_empno = '".$e['e_empno']."' ";
				$where .=" e_empno = '".$e."' ";
				if($j < count($row)-1){
					$where .= " OR ";
				}
				$j++;	
			}
			$where .=')';
       }
		return $where;
	}
	
	public function getAllByApprovers($where, $employeeId, $companyId) {
		$db = Zend_Registry::get('db');
		
		$sql = $db->select();
		//$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from('approvers_2');
		$sql = $sql->where("company_id = '$companyId'");
		$users = $db->query($sql)->fetchAll();
		$current = $this->getCurrentSettings($companyId);
		$approver_num = $current['approvers_num'];
		$start = ($current['allow_main_approver'] == '1')? 0:1;
		$employees = array();
		foreach($users as $user) {
			$levels = json_decode($user['level'],true);
			for($i=$start;$i<=$approver_num;$i++) {
			  if($employeeId == $levels["$i"]){
				array_push($employees, $user['e_empno']);
				break;
			  }
			}
		}
		$order = "date_created DESC";
		$where .= " status = 'pending' AND ";
		$j = 0;
		foreach($employees as $e) {
			$where .=" e_empno = '{$e}' ";
			if($j < count($employees)-1){
				$where .= " OR ";
			}
			$j++;	
		}
		
		return $where;
	}
	/**
     *  check if current date is number of days before the current cut off set
     *  @access public
     *  @params: companyid, 
     *  @return bool
     */
	public function doWarnByCutOff($companyId) {
		$settings = $this->getCurrentSettings($companyId);
		$warn = (int)$settings['warn_cutoff'];
		$days = (int)$settings['warn_time_days'];
		
		if($warn) {
			$cutoffdates = new CutOff();
			
			$where = "(NOW() BETWEEN period_start AND period_end) 
								AND abs(datediff(period_end, NOW())) <= $days";
			$row = $cutoffdates->fetchRow($where);
			
			if(count($row) > 0) {
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	/**
     *  get all employees to be assigned which still has slot for approvers
     *  @access public
     *  @params: companyid, 
     *  @return array
     */
	 public function listEmployeesToBeAssigned($companyId) {
		$max = (int) $this->getNumApprovers($companyId);
		if($this->isMainApproverAllowed($companyId)) {
			$max = $max + 1;
		}
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from(array("a" => "empmas"), array("fullname" => "CONCAT(a.lastname,' , ',a.firstname)", 
									"e_empno" => "REPLACE(a.e_empno,' ','')"));
		$sql = $sql->where(" (SELECT count(id) FROM approver_user_level WHERE e_empno = a.e_empno) < $max 
											AND company_id = '".$companyId."' ");
		$sql = $sql->order("a.lastname ASC");
		$results = $this->_db->fetchAll($sql);
		
		return $results;
		
	 }
	 
	 /**
     *  get all filtered employees to be assigned
     *  @access public
     *  @params: $companyid, 
	 *  @param: $level => what particular approver level
	 *  @param: $approver => approver emp number
     *  @return array
     */
	 public function filterListEmployeesToBeAssigned($companyId,$level, $approver) {
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from("approver_user_level", array("e_empno" => "REPLACE(e_empno,' ','')"));
		$sql = $sql->where("approver = '".$approver."'");
		$results = $this->_db->fetchAll($sql);
		$resultWMax = self::getEmployeeWithMaxApprover($companyId);
		if($this->isMainApproverAllowed($companyId) && (int)$level > 0) {
			$results = $results + $resultWMax;
		}
		return $results;
		
	 }
	 
	 public function getEmployeeWithMaxApprover($companyId) {
		$max = (int) $this->getNumApprovers($companyId);
		
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from(array("a" => "empmas"), array("e_empno" => "REPLACE(a.e_empno,' ','')"));
		$sql = $sql->where(" (SELECT count(id) FROM approver_user_level WHERE e_empno = a.e_empno AND level > 0) = $max 
											AND company_id = '".$companyId."' ");
		$sql = $sql->order("a.lastname ASC");
		$results = $this->_db->fetchAll($sql);
		
		return $results;
	 }
	 
	 public function getAllApproversEmail($companyId) {
		$sql = $this->select();
		$sql = $sql->setIntegrityCheck(false);
		$sql = $sql->from(array("a" => "user_approvers"), array("e_empno" => "a.e_empno"));
		$sql = $sql->joinLeft(array("b" => "empmas"), "b.e_empno = a.e_empno", 
								array("email" => "b.email", "fullname" => "CONCAT(b.lastname,' , ',b.firstname)" ));
		$sql = $sql->where("a.company_id = '".$companyId."'");
		
		$result = $this->_db->fetchAll($sql);
		return $result;
	}
	
}
?>