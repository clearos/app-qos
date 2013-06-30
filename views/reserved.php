<?php

/**
 * Reserved bandwidth view.
 *
 * @category   apps
 * @package    qos
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/qos/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('qos');
$this->lang->load('network');

///////////////////////////////////////////////////////////////////////////////
// Form or summary table
///////////////////////////////////////////////////////////////////////////////

if ($read_only) {
    $headers = array(lang('network_interface'));
    for ($i = 0; $i < $priority_classes; $i++)
        $headers[] = $i + 1;

    $rows = array();
    foreach ($pc_config as $direction => $entries) {
        foreach ($entries as $ifn => $values)
            $rows[$ifn][$direction] = $values;
    }

    $items = array();
    foreach ($rows as $ifn => $row) {
        $item['title'] = $ifn;
        $item['action'] = '';
        $item['anchors'] = button_set(array(
            anchor_edit("/app/qos/reserved/edit/$ifn"),
            anchor_delete("/app/qos/reserved/delete/$ifn")
        ));
        $item['details'] = array($ifn);
        for ($i = 0; $i < $priority_classes; $i++) {
            $item['details'][] =
                "<div id='" . $ifn . $i . "'>{$row['up'][$i]}%</div>" .
                "<div id='" . $ifn . $i . "'>{$row['down'][$i]}%</div>";
        }
        $items[] = $item;
    }

    echo summary_table(
        lang('qos_priority_class') . ': ' .
        lang('qos_class_reserved_title'),
        array(),
        $headers,
        $items,
        array('id' => 'pcreserved_summary')
    );
}
else {
    require_once('slider_array.inc.php');

    echo form_open('qos/reserved',
        array('id' => 'reserved_form')
    );
    echo form_header(
        lang('qos_priority_class') . ': ' .
        lang('qos_class_reserved_title') . ': ' . $ifn,
        array('id' => 'qos_pcreserved'));

    echo form_banner(form_slider_array('pcupreserved', lang('qos_upstream'), 0,
        $priority_classes, $default_values_up));
    echo form_banner(form_slider_array('pcdownreserved', lang('qos_downstream'), 0,
        $priority_classes, $default_values_down));

    echo field_button_set(
        array( 
            form_submit_update('submit-form'),
            anchor_cancel('/app/qos')
    ));

    echo form_footer();
    echo form_close();
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
