<?php

require_once 'ApplicationSettings.php';

class WithinValidDaysValidate extends Zend_Validate_Abstract {
    const STRING = 'string';
    protected $_messageTemplates = array(self::STRING => "Date selected beyond allowed valid number of days.");
    public $companyId;
    public $applicationType;
    public function isValid($value) {
        $this->_setValue($value);
        $todayDate = date('Y-m-d');
        $applicationsSettings = new ApplicationSettings();
        $daysValid = $applicationsSettings->getDaysValid($this->companyId, $this->applicationType);
        $dayDifference = $this->getNumDaysDifference($value, $todayDate);
        $this->_messageTemplates = array(self::STRING => "Date must be within {$daysValid} day(s) of current date.");
        if ($dayDifference <= $daysValid) {
            return true;
        } else {
            $this->_error();
            return false;
        }
    }
    public function setCompanyId($companyId) {
        $this->companyId = $companyId;
    }
    public function setApplicationType($applicationType) {
        $this->applicationType = $applicationType;
    }
    private function getNumDaysDifference($inputtedDate, $todayDate) {
        $inputtedDateUnixTime = strtotime($inputtedDate);
        $todayDateUnixTime = strtotime($todayDate);
        $dateDifference = $todayDateUnixTime - $inputtedDateUnixTime;
        return round($dateDifference / 86400);
    }
}
