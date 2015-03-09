<?php

/**
 * Global fields values array.
 */
$_FIELDS = array();

/**
 * Global fields associated errors array.
 */
$_ERRORS = array();

/**
 * Form fields errors functionality.
 *
 * @category Asymptix PHP Framework
 * @author Dmytro Zarezenko <dmytro.zarezenko@gmail.com>
 * @copyright (c) 2009 - 2015, Dmytro Zarezenko
 * @license http://opensource.org/licenses/MIT
 */
class Errors {

    /**
     * Display error of script execution.
     *
     * @param string $errorMessage Message of the error.
     */
    public static function displayError($errorMessage, $fieldName = null) {
        if (!is_null($fieldName)) {
            return ('<label for="' . $fieldName . '" class="form-error">' . $errorMessage . '</label>');
        } else {
            return ('<span class="label label-danger pull-right form-error">' . $errorMessage . '</span>');
        }
    }

    /**
     * Display error for field if it's exist.
     *
     * @global array $_ERRORS List of fields errors.
     * @param string $fieldName Name of the field.
     */
    public static function displayErrorFor($fieldName) {
        global $_ERRORS;

        if (self::isSetErrorFor($fieldName)) {
            return self::displayError($_ERRORS[$fieldName], $fieldName);
        }
        return "";
    }

    /**
     * Returns error message by field name if exists. Throws Exception if error
     * for this field doesn't exist.
     *
     * @global array $_ERRORS Global list of fields errors.
     * @param string $fieldName Name of the field.
     *
     * @return string Error message.
     * @throws Exception If error for this field doesn't exist.
     */
    public static function getError($fieldName) {
        global $_ERRORS;

        if (self::isSetErrorFor($fieldName)) {
            return $_ERRORS[$fieldName];
        }
        //throw new Exception("No error exists for field '" . $fieldName . "'");
        return "";
    }

    /**
     * Test if error for field is exist.
     *
     * @global array $_ERRORS Global list of fields errors.
     * @param string $fieldName Name of the field.
     * @return boolean
     */
    public static function isSetErrorFor($fieldName) {
        global $_ERRORS;

        return isset($_ERRORS[$fieldName]);
    }

    public static function isErrorsExist() {
        global $_ERRORS;

        return (isset($_ERRORS['_common']) && !empty($_ERRORS['_common']));
    }

    public static function getErrors() {
        global $_ERRORS;

        return (isset($_ERRORS['_common'])?$_ERRORS['_common']:array());
    }

    /**
     * Save error message text for a field in global errors list.
     *
     * @global array $_ERRORS Global list of fields errors.
     * @param string $fieldName Name of the field.
     * @param string $errorMessageText Text of the error message.
     */
    public static function saveErrorFor($fieldName, $errorMessageText) {
        global $_ERRORS;

        $_ERRORS[$fieldName] = $errorMessageText;
    }

    /**
     * Save error message text for a field in global errors list.
     *
     * @global array $_ERRORS Global list of fields errors.
     * @param string $errorMessageText Text of the error message.
     */
    public static function saveError($errorMessageText) {
        global $_ERRORS;

        if (!isset($_ERRORS['_common'])) {
            $_ERRORS['_common'] = array();
        }
        $_ERRORS['_common'][] = $errorMessageText;
    }

}

?>