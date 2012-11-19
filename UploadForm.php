<?php
class UploadForm extends Zend_Form {
    public function __construct($name, $action = '') {
        parent::__construct();
        $this->setName($name);
		$actionA = array();
		if($action != '')
			$actionA = array('action' => $action);
        $this->setAttribs(array($actionA, 'enctype' => 'multipart/form-data'));
        $file = new Zend_Form_Element_File('file');
	$file->setLabel(' ')
            ->setAttribs(array('size'=>70))
            ->addValidator('Size', true, '3MB')
            ->addValidator('Extension', true, 'otm,csv,txt');
        $submit = new Zend_Form_Element_Submit('upload');
        $submit->setLabel('Upload')
            ->setAttrib('class', 'classname_assign')
            ->setAttrib('onclick', 'displayProgress();');
        $this->addElements(array($file, $submit));
    }
}
?>