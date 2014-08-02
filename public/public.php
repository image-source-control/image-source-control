<?php
//avoid direct calls to this file
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists('ISC_Public')) {

    /**
     * handles all admin functionalities
     *
     * @since 1.7
     * @todo move frontend-only functions from general class here
     */
    class ISC_Public extends ISC_Class {

    }
}