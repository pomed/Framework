<?php

/**
 * Settings module. Gets system settings and configuration from the database.
 *
 * @category Asymptix PHP Framework
 * @author Dmytro Zarezenko <dmytro.zarezenko@gmail.com>
 * @copyright (c) 2009 - 2015, Dmytro Zarezenko
 *
 * @git https://github.com/dzarezenko/Asymptix-PHP-Framework.git
 * @license http://opensource.org/licenses/MIT
 */

require_once("db/Settings.php");

use Asymptix\DB\DBSelector;

$dbSelector = new DBSelector(new Settings());
$settings = $dbSelector->selectDBObjects();

$_SETTINGS = array();
foreach ($settings as $setting) {
    $_SETTINGS[$setting->id] = $setting->value;
}

?>