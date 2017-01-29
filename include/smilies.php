<?php
/**
 * Список смайлов форума.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2014-2017 Flazy.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM')) {
    die;
}

($hook = get_hook('ps_fl_smilies_start')) ? eval($hook) : null;

// Here you can add additional smilies if you like (please note that you must escape singlequote and backslash)
$smilies = array(
        ":)" => 'smile.svg',
        ":D" => 'happy-8.svg',
        ":d" => 'happy-8.svg',
        ":(" => 'sad-1.svg',
        ":'(" => 'crying-3.svg',
        ":P" => 'happy-6.svg',
        ":p" => 'happy-6.svg',
        "O:)" => 'angel.svg',
        "3:)" => 'evil.svg',
        ";)" => 'winking.svg',
        ":O" => 'shocked-2.svg',
        "-_-" => 'sceptic-5.svg',
        ">:O" => 'desperate-1.svg',
        ":*" => 'kiss-2.svg',
        "<3" => 'hearts.svg',
        "8-)" => 'nerd-3.svg',
        "8|" => 'smug-3.svg',
        "(^^^)" => 'sharks.svg',
        ":|]" => 'robot.svg',
        ">:(" => 'angry-3.svg',
        ":v" => 'pacman.svg',
        ":/" => 'sceptic-4.svg',
        ":afro" => 'afro-1.svg',
        ":agent" => 'agent.svg',
        ":alien" => 'alien.svg',
        ":arrogant" => 'arrogant.svg',
        ":baby" => 'baby-1.svg',
        ":bully" => 'bully.svg',
        ":burglar" => 'burglar.svg',
        ":businessman" => 'businessman.svg',
        ":asian" => 'asian.svg',
        ":creepy" => 'creepy.svg',
        ":dazed" => 'dazed-2.svg',
        ":dead" => 'dead.svg',
        ":detective" => 'detective.svg',
        ":geek" => 'geek.svg',
        ":gentleman" => 'gentleman.svg',
        ":girl" => 'girl.svg',
        "(happy)" => 'happy.svg',
        "(ninja)" => 'ninja.svg',
        "(pirate)" => 'pirate-1.svg',
        "(punk)" => 'punk.svg',
        ":money" => 'money.svg',
        "(rich)" => 'rich.svg',
        ":sad" => 'sad-1.svg',
        "(sad)" => 'sad-1.svg',
        "(batman)" => 'superhero.svg',
        "(deadpool)" => 'superhero-3.svg',
        "(daredevil)" => 'superhero-4.svg',
        "(vampire)" => 'vampire-1.svg',
        "(zombie)" => 'zombie.svg',
    
	);

($hook = get_hook('ps_fl_smilies_end')) ? eval($hook) : null;

define('FORUM_SMILIES_LOADED', 1);
