<?php

require_once(TOOLKIT . '/class.manager.php');

Class RecipientgroupManager extends Manager{

	public function __getHandleFromFilename($filename){
		$result = sscanf($filename, 'group.%[^.].php');
		return $result[0];
	}

	public function __getClassName($handle){
		return sprintf('recipientgroup%s', ucfirst($handle));
	}
	
	public function __getClassPath($handle){
		if(is_file(WORKSPACE . "/newsletter-recipients/group.$handle.php")) return WORKSPACE . '/newsletter-recipients';
		else{
			$extensions = Symphony::ExtensionManager()->listInstalledHandles();

			if(is_array($extensions) && !empty($extensions)){
				foreach($extensions as $e){
					if(is_file(EXTENSIONS . "/$e/newsletter-recipients/group.$handle.php")) return EXTENSIONS . "/$e/newsletter-recipients";
				}
			}
		}

		return false;
	}
	
	public function __getDriverPath($handle){
		return $this->__getClassPath($handle) . "/group.$handle.php";
	}

	public function listAll(){
		$result = array();

		$structure = General::listStructure(WORKSPACE . '/newsletter-recipients', '/group.[\\w-]+.php/', false, 'ASC', WORKSPACE . '/newsletter-recipients');

		if(is_array($structure['filelist']) && !empty($structure['filelist'])){
			foreach($structure['filelist'] as $f){
				$f = self::__getHandleFromFilename($f);

				if($about = $this->about($f)){

					$classname = $this->__getClassName($f);
					$path = $this->__getDriverPath($f);

					$can_parse = false;
					$type = null;

					if(method_exists($classname,'allowEditorToParse')) {
						$can_parse = call_user_func(array($classname, 'allowEditorToParse'));
					}

					if(method_exists($classname,'getSource')) {
						$type = call_user_func(array($classname, 'getSource'));
					}

					$about['can_parse'] = $can_parse;
					$about['type'] = $type;
					$result[$f] = $about;
				}
			}
		}

		$extensions = Symphony::ExtensionManager()->listInstalledHandles();

		if(is_array($extensions) && !empty($extensions)){
			foreach($extensions as $e){
				if(!is_dir(EXTENSIONS . "/$e/newsletter-recipients")) continue;

				$tmp = General::listStructure(EXTENSIONS . "/$e/newsletter-recipients", '/group.[\\w-]+.php/', false, 'ASC', EXTENSIONS . "/$e/newsletter-recipients");

				if(is_array($tmp['filelist']) && !empty($tmp['filelist'])){
					foreach($tmp['filelist'] as $f){
						$f = self::__getHandleFromFilename($f);

						if($about = $this->about($f)){
							$about['can_parse'] = false;
							$about['type'] = null;
							$result[$f] = $about;
						}
					}
				}
			}
		}

		ksort($result);
		return $result;
	}

	public function &create($handle){
		$env =  array(
			'today' => DateTimeObj::get('Y-m-d'),
			'current-time' => DateTimeObj::get('H:i'),
			'this-year' => DateTimeObj::get('Y'),
			'this-month' => DateTimeObj::get('m'),
			'this-day' => DateTimeObj::get('d'),
			'timezone' => DateTimeObj::get('P'),
			'website-name' => Symphony::Configuration()->get('sitename', 'general'),
			'root' => URL,
			'workspace' => URL . '/workspace',
			'upload-limit' => min($upload_size_php, $upload_size_sym),
			'symphony-version' => Symphony::Configuration()->get('version', 'symphony'),
		);

		$classname = $this->__getClassName($handle);
		$path = $this->__getDriverPath($handle);

		if(!is_file($path)){
			throw new Exception(
				__(
					'Could not find Recipient Group <code>%s</code>. If the Recipient Group was provided by an Extension, ensure that it is installed, and enabled.',
					array($handle)
				)
			);
		}

		if(!class_exists($classname)) require_once($path);

		return new $classname($this->_Parent, $env, $process_params);

	}
}