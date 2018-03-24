<?php
/**
 * Общие классы используемые на форуме.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2014-2018 Flazy
 * @license http://www.gnu.org/licenses/gpl.html GPL версии 2 или выше
 * @package Flazy
 */


$js = array(
	'jquery'		=> '//code.jquery.com/jquery-2.1.1.min.js',
	'material'		=> '//cdnjs.cloudflare.com/ajax/libs/materialize/0.97.8/js/materialize.min.js',
	'flazy'			=> $base_url.'/style/default/js/flazy.js',
	'common'		=> $base_url.'/style/default/js/common.js',
	'tooltip'		=> $base_url.'/style/default/js/jquery.tooltip.js',
	'pstrength'		=> $base_url.'/style/default/js/jquery.pstrength.js',
	'cookies'		=> $base_url.'/style/default/js/jquery.cookie.js',
        'bb'                    => $base_url.'/resources/editor/js/bb.js',
    //admin js
        'admin_wysihtml5'          => $base_url.'/resources/admin/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.js',
);

$css = array(
        //Admin css
	'admin_bootstrap'	=> $base_url.'/resources/admin/bootstrap/css/bootstrap.min.css',
	'admin_common'		=> $base_url.'/resources/admin/dist/css/flazy.admin.min.css',
	'admin_wysihtml5'	=> $base_url.'/resources/admin/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css',
        //User css
);

($hook = get_hook('cls_fl_pre_class_js_helper')) ? eval($hook) : null;


/**
 * Добавление java-script файлов в <head> форума.
 * @author Copyright (C) 2009 hcs
 * @modified Copyright (C) 2014-2018 Flazy
 */
class forum_js
{
	var $file = array();
	var $code = array();
	
	function forum_js()
	{
	}

	/**
	 * Проверка названия скрипта на соответсвие в массиве $js.
	 * @param string ссылка или название локального js скрипта.
	 * @return string ссылка на js скрипт.
	 */
	function check_path($path)
	{
		global $js;

		return $path = !empty($js[$path]) ? $js[$path] : $path;
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
			$str .= '<script type="text/javascript" src="'.$file.'"></script>'."\n";
		foreach ($this->code as $code)
			$str .= '<script type="text/javascript">'."\n".$code."\n".'</script>'."\n";
		return $str;
	}
}

($hook = get_hook('cls_fl_js_helper_end')) ? eval($hook) : null;


($hook = get_hook('cls_fl_pre_class_css_helper')) ? eval($hook) : null;


class forum_css
{
	var $file = array();
	var $code = array();
	
	function forum_css()
	{
	}

	/**
	 * Проверка названия скрипта на соответсвие в массиве $js.
	 * @param string ссылка или название локального js скрипта.
	 * @return string ссылка на js скрипт.
	 */
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
			$str .= '<link rel="stylesheet" src="'.$file.'"  type="text/css"/>'."\n";
		foreach ($this->code as $code)
			$str .= '<style>'."\n".$code."\n".'</style>'."\n";
		return $str;
	}
}

($hook = get_hook('cls_fl_css_helper_end')) ? eval($hook) : null;

$forum_js = new forum_js();
$forum_css = new forum_css();
