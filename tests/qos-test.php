#!/usr/clearos/sandbox/usr/bin/php
<?php

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = isset($_ENV['CLEAROS_BOOTSTRAP']) ?
	$_ENV['CLEAROS_BOOTSTRAP'] : '/usr/clearos/framework/shared';
require_once($bootstrap . '/bootstrap.php');

///////////////////////////////////////////////////////////////////////////////
// T E S T  R O U T I N E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\qos\Qos as Qos;

clearos_load_library('qos/Qos');

$qos = new Qos();

$ifn_config = $qos->get_interface_config();
var_dump($ifn_config);

$i = 0;
//for ($i = 0; $i < 4; $i++)
    $qos->set_interface_config('ppp' . $i, array(100, 200), array('auto', '10'));

/*
$ifn_config = $qos->get_interface_config();
var_dump($ifn_config);

$qos->delete_interface_config('ppp0');

$ifn_config = $qos->get_interface_config();
var_dump($ifn_config);
*/

/*
$pc_config = $qos->get_priority_class_config(Qos::PRIORITY_CLASS_RESERVED);
var_dump($pc_config);

$qos->set_priority_class_config(Qos::PRIORITY_CLASS_RESERVED,
    'ppp2',
    array(14, 14, 14, 14, 14, 15, 15),
    array(14, 14, 14, 14, 14, 15, 15)
);

$pc_config = $qos->get_priority_class_config(Qos::PRIORITY_CLASS_RESERVED);
var_dump($pc_config);

$qos->delete_interface_config('ppp2');

$ifn_config = $qos->get_interface_config();
var_dump($ifn_config);

$priomark_rules = $qos->get_priomark_rules(Qos::PRIOMARK_TYPE_IPV4);
var_dump($priomark_rules);
$priomark_rules = $qos->get_priomark_rules(Qos::PRIOMARK_TYPE_IPV4_CUSTOM);
var_dump($priomark_rules);
*/
$priomark_rules = $qos->get_priomark_rules();
var_dump($priomark_rules);

$qos->add_priomark_rule(
    'Test', 'ppp0', Qos::DIRECTION_DOWN, 5,
    'udp', '-', '-', '-', '-'
);

$qos->add_priomark_rule_custom(
    'Test', 'ppp0', Qos::DIRECTION_UP, 4,
    '-p udp'
);

$priomark_rules = $qos->get_priomark_rules();
var_dump($priomark_rules);

$qos->delete_interface_config('ppp0');

/*
$qos->add_priomark_rule_custom(
    $nickname, $ifn, $direction, $priority, $params
);
*/

//$qos->delete_priomark_rule(Qos::PRIOMARK_TYPE_IPV4_CUSTOM, 'Test');

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
