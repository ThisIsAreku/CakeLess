<?php
App::uses('AppHelper', 'View/Helper');

App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::uses('Component', 'Controller');
if(!App::import('Vendor', 'Less.Lessc',
  array(
    'file' => 'lessphp' . DS . 'lessc.inc.php'
  )
)){
	trigger_error('Class not found', E_USER_ERROR);
	return;
}
class LessHelper extends AppHelper {

	public $helpers = array('Html');
	public $Less;
	public $settings = array(
		'force_debug' => false,
		'lessjs_url' => '//cdnjs.cloudflare.com/ajax/libs/less.js/1.5.0/less.min.js',
		'formatter' => 'compressed',
		'custom_less_dir' => 'gen'
		);

	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings );

		if (!defined('LESS')) {
			define('LESS', WWW_ROOT . 'less' . DS);
		}
		if (!defined('LESS_URL')) {
			define('LESS_URL', 'less/');
		}
		$this->Less = new lessc;
		$this->Less->setFormatter($this->settings['formatter']);
	}
	
	public function css($path, $options = array()) {
		$moreOpts = array();
		if((Configure::read('debug') > 0)||$this->settings['force_debug']) {
			$rel = 'stylesheet/less';
			$moreOpts = array('pathPrefix' => LESS_URL, 'ext' => '.less', 'fullBase' => true);
		}else{
			$rel = 'stylesheet';
			$moreOpts = array('pathPrefix' => CSS_URL, 'ext' => '.css', 'fullBase' => false);
		}

		if (is_array($path)) {
			$out = '';
			foreach ($path as $i) {
				$out .= "\n\t" . $this->css($i, $options);
			}
			if (empty($options['block'])) {
				return $out . "\n";
			}
			return;
		}

		if (strpos($path, '//') !== false) {
			$url = $path;
		} else {
			if(Configure::read('debug') == 0) {
				$source = LESS.$path.'.less';
				$target = CSS.$path.'.css';

				$this->auto_compile_less($source, $target);
			}

			$options = array_diff_key($options, array('fullBase' => null));
		}


		if((Configure::read('debug') > 0)||$this->settings['force_debug']) {
			$this->Html->script($this->settings['lessjs_url'], array('inline' => false));
			echo $this->Html->css($path, $rel, $options + $moreOpts);
		}else{
			echo $this->Html->css($path, $rel, $options + $moreOpts);
		}
	}

	public function auto_compile_less($lessFilename, $cssFilename) {
		// Check if cache & output folders are writable and the less file exists.
		if (!file_exists(dirname($cssFilename))) {
			$this->log("Lessc: creating dir for ".$cssFilename, 'activite');
			mkdir(dirname($cssFilename), 0777, true);
		}
		if (!is_writable(dirname($cssFilename))) {
			$this->log("Lessc: failed, not writable: ".$cssFilename, 'activite');
			trigger_error(__d('cake_dev', '"%s" directory is NOT writable.', $cssFilename), E_USER_NOTICE);
			return;
		}
		if (!file_exists($lessFilename)) {
			$this->log("Lessc: failed, file not found: ".$lessFilename, 'activite');
			trigger_error(__d('cake_dev', 'File: "%s" not found.', $lessFilename), E_USER_NOTICE);
			return;
		}
		try {
			if($this->Less->checkedCompile($lessFilename, $cssFilename))
				$this->log("Lessc: compiled ".$cssFilename, 'activite');

		} catch (exception $e) {
			$this->log("Lessc: Fatal error: ".$e->getMessage(), 'activite');
		}
	}

	public function beforeLayout($layoutFile) {
		$lessDir = LESS.$this->settings['custom_less_dir'];
		$lessFile = $lessDir.DS.'custom.less';
		if (!is_writable($lessDir)) {
			trigger_error(__d('cake_dev', '"%s" is NOT writable.', $lessDir), E_USER_NOTICE);
			return;
		}
		$lessVars = Configure::read('Less');
		/*if(!is_array($lessVars))
			return;*/

		$content = '// LESS generated variables from Configure'.PHP_EOL.'// Generated on '.date('Y-m-d H:i:s').PHP_EOL;
		if(is_array($lessVars))
			foreach($lessVars as $key => $value){
				$content .= '@'.$key.': '.$value.';'.PHP_EOL;
			}
		$lessFileW = new File($lessFile, true);
		if ($lessFileW->write($content) === false) {
			if (!is_writable(dirname($lessFile))) {
				trigger_error(__d('cake_dev', '"%s" directory is NOT writable.', dirname($lessFile)), E_USER_NOTICE);
			}
			trigger_error(__d('cake_dev', 'Failed to write "%s"', $lessFile), E_USER_NOTICE);
		}
	}

}
