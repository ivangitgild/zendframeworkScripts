<?php
class PasswordValidate extends Zend_Validate_Abstract {
    const STRING = 'string';
    protected $_messageTemplates = array(self::STRING => "The new password did not pass the standards.");
    public function isValid($value) {
        $this->_setValue($value);
        if (preg_match('/^(?=[-_a-zA-Z0-9]*?[A-Z])(?=[-_a-zA-Z0-9]*?[a-z])(?=[-_a-zA-Z0-9]*?[0-9])[-_a-zA-Z0-9]{8,}$/', $value)) {
            return true;
        } else {
            $this->_error();
            return false;
        }
    }
}
