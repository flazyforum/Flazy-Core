<?php
/**
 * Общие классы используемые на форуме.
 *
 * @copyright Copyright (C) 2008-2015 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2013-2015 Flazy.us
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */
 
 ($hook = get_hook('cls_fl_css_helper_start')) ? eval($hook) : null;
$css = array(
	/* Forum CSS*/
	
	/* Admin CSS*/
	
	//Dashboard
	
	//Settings
	
	//Users
	
	//Management
	
	//Extensions
);
($hook = get_hook('cls_fl_pre_class_css_helper')) ? eval($hook) : null;
 class forum_css{
	var $file = array();
	var $code = array();
	
	function forum_css()
	{
	}
	
	function check_path($path)
	{
		global $css;

		return $path = !empty($css[$path]) ? $css[$path] : $path;
	}

	function file($paths)
	{
		if (!is_array($paths))
		{
			if (!in_array($paths, $this->file))
				$this->file[] = $this->check_path($paths);
		}
		else
		{
			foreach ($paths as $path_num => $path)
				if (!in_array($paths, $this->file))
					$this->file[] = $this->check_path($path);
		}
	}

	function code($code)
	{
		if (!in_array($code, $this->code))
			$this->code[] = $code;
	}

	function out()
	{
		$str = '';
		foreach ($this->file as $file)
			$str .= '<link rel="stylesheet" type="text/css" href="'.$file.'">'."\n";
		foreach ($this->code as $code)
			$str .= '<style>'."\n".$code."\n".'</style>'."\n";
		return $str;
	}
}
($hook = get_hook('cls_fl_css_helper_end')) ? eval($hook) : null;
$forum_css = new forum_css();