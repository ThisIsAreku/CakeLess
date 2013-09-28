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
		'lessjs_url' => '//cdnjs.cloudflare.com/ajax/libs/less.js/1.3.3/less.min.js',
		'formatter' => 'compressed'
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
				//echo $target;
				$this->auto_compile_less($source, $target);
			}

			$url = $this->assetUrl($path, $options + $moreOpts);
			$options = array_diff_key($options, array('fullBase' => null));
		}


		if((Configure::read('debug') > 0)||$this->settings['force_debug']) {
			$this->Html->script($this->settings['lessjs_url'], array('inline' => false));
			echo $this->Html->css($url, $rel, $options);
		}else{
			echo $this->Html->css($url, $rel, $options);
		}
	}

	public function auto_compile_less($lessFilename, $cssFilename) {
		$this->log("Lessc: Initialized ".lessc::$VERSION, 'activite');
		$this->log("Lessc: checking ".$lessFilename, 'activite');
		// Check if cache & output folders are writable and the less file exists.
		if (!is_writable(dirname($cssFilename))) {
			$this->log("Lessc: failed, not writable", 'activite');
			trigger_error(__d('cake_dev', '"%s" directory is NOT writable.', CACHE.'less'), E_USER_NOTICE);
			return;
		}
		if (!file_exists($lessFilename)) {
			trigger_error(__d('cake_dev', 'File: "%s" not found.', $lessFilename), E_USER_NOTICE);
			return;
		}
		try {
			if($this->Less->checkedCompile($lessFilename, $cssFilename))
				$this->log("Lessc: success ".$cssFilename, 'activite');
			else
				$this->log("Lessc: not required ".$cssFilename, 'activite');

		} catch (exception $e) {
			$this->log("Lessc: Fatal error: ".$e->getMessage(), 'activite');
		}

		// Load the cache
		/*$cache = file_exists($cssFilename) ? filemtime($cssFilename) : 0;
		$file = filemtime($lessFilename);

		if ($file > $cache) {
			$this->log("Lessc: ".$lessFilename, 'activite');
			$new_cache = $this->Less->compileFile($lessFilename);
			$cssFile = new File($cssFilename, true);
			if ($cssFile->write($new_cache) === false) {
				if (!is_writable(dirname($cssFilename))) {
					$this->log("Lessc: failed, not writable", 'activite');
					//trigger_error(__d('cake_dev', '"%s" directory is NOT writable.', dirname($cssFilename)), E_USER_NOTICE);
					return;
				}
				$this->log("Lessc: failed", 'activite');
				//trigger_error(__d('cake_dev', 'Failed to write "%s"', $cssFilename), E_USER_NOTICE);
				return;
			}
			$this->log("Lessc: success ".$cssFilename, 'activite');
		}*/
	}

	public function beforeLayout($layoutFile) {
		$lessgen = LESS.'gen';
		$lessFile = $lessgen.DS.'custom.less';
		if (!is_writable($lessgen)) {
			trigger_error(__d('cake_dev', '"%s" directory is NOT writable.', $lessgen), E_USER_NOTICE);
			return;
		}
		$this->log("Lessc: generating custom.less", 'activite');
		$lessVars = Configure::read('Less');
		if(!is_array($lessVars))
			return;

		$content = '';
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
