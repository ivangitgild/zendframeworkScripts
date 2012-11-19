<?php
class CSVParser {
    var $filePath;
 
    public function __construct($filePath) {
        $this->filePath = $filePath;
    }
	
    public function parse() {
        $path = $this->filePath;
        $file = fopen($path, 'r');
        $contents = fread($file, filesize($path));
        fclose($file);
        $array = explode("\n", $contents);
        $cleanArray = array();
        foreach ($array as $key => $value) {
            if (!empty($value)) {
                $cleanArray[] = trim($value);
            }
        }
		
		$final = array();
		foreach($cleanArray as $key => $i) {
			$csd = explode(",", $i);
			foreach($csd as $k => $d) {
				$csd[$k] = preg_replace('/(\s)*$/','',preg_replace('/^(\s)*/','',$d));
			}
			$final[] = $csd;
		}
		
        return $final;
    }
}
?>