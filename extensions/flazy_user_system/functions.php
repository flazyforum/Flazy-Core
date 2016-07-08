<?php
/**
 * Общие функции.
 *
 * @copyright Copyright (C) 2015 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL версии 2 или выше
 * @package flazy_user_system
 */

if (!defined('FORUM'))
	die;

// Определим браузер
function user_browser($ua)
{
	if (strpos($ua, 'arora') !== false) return 'Arora';
	else if (strpos($ua, 'avant browser') !== false) return 'AvantBrowser';
	else if (strpos($ua, 'aweb') !== false) return 'AWeb';
	else if (strpos($ua, 'camino') !== false) return 'Camino';
	else if (strpos($ua, 'chrome') !== false) return 'Chrome';
	else if (strpos($ua, 'cometbird') !== false) return 'Cometbird';
	else if (strpos($ua, 'dillo') !== false) return 'Dillo';
	else if (strpos($ua, 'elinks') !== false) return 'ELinks';
	else if (strpos($ua, 'epiphany') !== false) return 'Epiphany';
	else if (strpos($ua, 'fennec') !== false) return 'Fennec';
	else if (strpos($ua, 'firebird') !== false) return 'Firebird';
	else if (strpos($ua, 'firefox') !== false) return 'Firefox';
	else if (strpos($ua, 'flock') !== false) return 'Flock';
	else if (strpos($ua, 'galeon') !== false) return 'Galeon';
	else if (strpos($ua, 'hotjava') !== false) return 'HotJava';
	else if (strpos($ua, 'ibrowse') !== false) return 'IBrowse';
	else if (strpos($ua, 'icab') !== false) return 'iCab';
	else if (strpos($ua, 'iceweasel') !== false) return 'Iceweasel';
	else if (strpos($ua, 'iron') !== false) return 'Iron';
	else if (strpos($ua, 'konqueror') !== false) return 'Konqueror';
	else if (strpos($ua, 'maxthon') !== false || strpos($ua, 'myie') !== false) return 'Maxthon';
	else if (strpos($ua, 'minefield') !== false) return 'Minefield';
	else if (strpos($ua, 'msie8.0') !== false) return 'MSIE8';
	else if (strpos($ua, 'msie7.0') !== false) return 'MSIE7';
	else if (strpos($ua, 'msie') !== false) return 'MSIE';
	else if (strpos($ua, 'netscape') !== false) return 'Netscape';
	else if (strpos($ua, 'netsurf') !== false) return 'NetSurf';
	else if (strpos($ua, 'opera') !== false) return 'Opera';
	else if (strpos($ua, 'phaseout') !== false) return 'PhaseOut';
	else if (strpos($ua, 'safari') !== false) return 'Safari';
	else if (strpos($ua, 'seamonkey') !== false) return 'SeaMonkey';
	else if (strpos($ua, 'shiretoko') !== false) return 'Shiretoko';
	else if (strpos($ua, 'slimbrowser') !== false) return 'SlimBrowser';
	else if (strpos($ua, 'stainless') !== false)return 'Stainless';
	else if (strpos($ua, 'sunrise') !== false) return 'Sunrise';
	else if (strpos($ua, 'wyzo') !== false) return 'Wyzo';
	// Семейство Mozilla
	else if (strpos($ua, 'mozilla') !== false && strpos($ua, 'rv:') !== false) return 'Mozilla';
	// Семейство WebKit
	else if (strpos($ua, 'webkit') !== false) return 'WebKit';
}

// Определим ОС
function user_os($ua)
{
	if (strpos($ua, 'amiga') !== false) return 'Amiga';
	else if (strpos($ua, 'beos') !== false) return 'BeOS';
	else if (strpos($ua, 'freebsd') !== false) return 'FreeBSD';
	else if (strpos($ua, 'hp-ux') !== false) return 'HP-UX';
	else if (strpos($ua, 'linux') !== false)
	{
		if (strpos($ua, 'arch') !== false) return 'Arch';
		else if (strpos($ua, 'ark') !== false) return 'Ark';
		else if (strpos($ua, 'centos') !== false || strpos($ua, 'cent os') !== false) return 'CentOS';
		else if (strpos($ua, 'debian') !== false) return 'Debian';
		else if (strpos($ua, 'fedora') !== false) return 'Fedora';
		else if (strpos($ua, 'freespire') !== false) return 'Freespire';
		else if (strpos($ua, 'gentoo') !== false) return 'Gentoo';
		else if (strpos($ua, 'kanotix') !== false) return 'Kanotix';
		else if (strpos($ua, 'kateos') !== false) return 'KateOS';
		else if (strpos($ua, 'knoppix') !== false) return 'Knoppix';
		else if (strpos($ua, 'kubuntu') !== false) return 'Kubuntu';
		else if (strpos($ua, 'linspire') !== false) return 'Linspire';
		else if (strpos($ua, 'mandriva') !== false || strpos($ua, 'mandrake') !== false) return 'Mandriva';
		else if (strpos($ua, 'redhat') !== false) return 'RedHat';
		else if (strpos($ua, 'slackware') !== false) return 'Slackware';
		else if (strpos($ua, 'slax') !== false) return 'Slax';
		else if (strpos($ua, 'suse') !== false) return 'Suse';
		else if (strpos($ua, 'xubuntu') !== false) return 'Xubuntu';
		else if (strpos($ua, 'ubuntu') !== false) return 'Ubuntu';
		else if (strpos($ua, 'xandros') !== false) return 'Xandros';
		else return 'Linux';
	}
	else if (strpos($ua, 'macosx') !== false || strpos($ua, 'macos') !== false || strpos($ua, 'macosx') !== false || strpos($ua, 'macintosh') !== false || strpos($ua, 'os=mac') !== false || strpos($ua, 'mac_osx') !== false) return 'MacOSX';
	else if (strpos($ua, 'macppc') !== false || strpos($ua, 'mac_ppc') !== false || strpos($ua, 'cpu=ppc;') !== false && strpos($ua, 'os=mac') !== false || strpos($ua, 'macintosh; ppc') !== false || strpos($ua, 'macintosh;') !== false && strpos($ua, 'ppc') !== false || strpos($ua, 'mac_powerpc') !== false) return 'MacPPC';
	else if (strpos($ua, 'netbsd') !== false) return 'NetBSD';
	else if (strpos($ua, 'os/2') !== false) return 'OS/2';
	else if (strpos($ua, 'avantgo') !== false) return 'Palm';
	else if (strpos($ua, 'sunos') !== false || strpos($ua, 'solaris') !== false) return 'SunOS';
	else if (strpos($ua, 'symbian') !== false) return 'SymbianOS';
	else if (strpos($ua, 'unix') !== false) return 'Unix';
	else if (strpos($ua, 'win') !== false)
	{
		if (strpos($ua, 'windowsnt6.1') !== false || strpos($ua, 'winnt6.1') !== false) return 'WindowsSeven';
		else if (strpos($ua, 'windowsnt6.0') !== false || strpos($ua, 'winnt6.0') !== false) return 'WindowsVista';
		else if (strpos($ua, 'winnt5.0') !== false || strpos($ua, 'windowsnt5.0') !== false || strpos($ua, 'winnt5.1') !== false || strpos($ua, 'windowsnt5.1') !== false || strpos($ua, 'windowsxp5.1') !== false || strpos($ua, 'winnt5.2') !== false || strpos($ua, 'windowsnt5.2') !== false || strpos($ua, 'windowsxp') !== false || strpos($ua, 'winxp') !== false || strpos($ua, 'cygwin_nt-5.1') !== false || strpos($ua, 'windows2000') !== false || strpos($ua, 'win2000') !== false) return 'WindowsXP';
		else if (strpos($ua, 'windows') !== false || strpos($ua, 'win') !== false) return 'Windows';
		else return 'Windows';
	}
	else if (strpos($ua, 'macintosh') !== false || strpos($ua, 'mac') !== false) return 'Macintosh';
	else if (strpos($ua, 'sun') !== false) return 'Sun';
	// Мобильные системы
	else if (strpos($ua, 'smartphone') !== false || strpos($ua, 'iemobile') !== false || strpos($ua, 'j2me') !== false || strpos($ua, 'iphone') !== false || strpos($ua, 'nintendo') !== false) return 'Mobile';
}

function user_system($ua)
{
	global $base_url, $ext_info, $lang_user_system;

	$ua = strtolower($ua);
	$os = user_os($ua);
	$browser = user_browser($ua);
	$ua_os = $ua_browser = '';

	if (!empty($os))
		$ua_os = '<img class="popup" src="'.$base_url.'/'.$ext_info['path'].'/img/os/'.strtolower($os).'.png" title="'.$lang_user_system['OS'].' - '.$os.'" alt=""/>';
	if (!empty($browser))
		$ua_browser = '<img class="popup" src="'.$base_url.'/'.$ext_info['path'].'/img/browser/'.strtolower($browser).'.png" title="'.$lang_user_system['Browser'].' - '.$browser.'" alt=""/>';


	return $ua_browser.' '.$ua_os;
}