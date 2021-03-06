<?php

/**
 * Session module and manipulations with User DBObject in session.
 *
 * @category Asymptix PHP Framework
 * @author Dmytro Zarezenko <dmytro.zarezenko@gmail.com>
 * @copyright (c) 2009 - 2015, Dmytro Zarezenko
 *
 * @git https://github.com/Asymptix/Framework
 * @license http://opensource.org/licenses/MIT
 */

use Asymptix\db\DBSelector;
use db\access\User;

$_USER = null;
if (isset($_SESSION['user'])) {
    $_USER = unserialize($_SESSION['user']);

    $userSelector = new DBSelector(new User());
    $_USER = $userSelector->selectDBObjectById($_USER->id);

    $user = clone($_USER);
    $user->password = null;
    //TODO: clear other secured data from session

    $_SESSION['user'] = serialize($user);

    unset($user);
}