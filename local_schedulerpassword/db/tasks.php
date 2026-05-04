<?php
/**
 * @package    local_schedulerpassword
*/
defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'local_schedulerpassword\task\resetPassword',
        'minute' => '0',
        'hour' => '0',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    )
);
