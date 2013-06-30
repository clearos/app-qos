<?php

/**
 * Interface configuration view.
 *
 * @category   apps
 * @package    qos
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    GNU General Public License version 3 or later
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
    $headers = array(
        lang('network_interface'),
        lang('qos_upstream'),
        lang('qos_downstream'),
        lang('qos_rate_to_quantum'),
    );

    $rows = array();
    foreach ($interfaces as $direction => $config) {
        foreach ($config as $ifn => $settings) {
            $rows[$ifn][$direction]['speed'] = $settings['speed'];
            $rows[$ifn][$direction]['r2q'] = $settings['r2q'];
        }
    }

    $items = array();

    foreach ($rows as $ifn => $config) {
        $item['title'] = $ifn;
        $item['action'] = '';
        $item['anchors'] = button_set(array(
            anchor_edit("/app/qos/ifn/edit/$ifn"),
            anchor_delete("/app/qos/ifn/delete/$ifn")
        ));
        $r2q_up = ($config['up']['r2q'] == 'auto') ?
            lang('qos_auto') : $config['up']['r2q'];
        $r2q_down = ($config['down']['r2q'] == 'auto') ?
            lang('qos_auto') : $config['down']['r2q'];
        $item['details'] = array(
            $ifn,
            "<span id='" . $ifn . "_up'>{$config['up']['speed']}</span>",
            "<span id='" . $ifn . "_down'>{$config['down']['speed']}</span>",
            "<span id='" . $ifn . "_r2q_up'>$r2q_up / $r2q_down</span>",
        );
        $items[] = $item;
    }

    foreach ($external_interfaces as $ifn) {
        if (array_key_exists($ifn, $row)) continue;

        $item['title'] = $ifn;
        $item['action'] = '';
        $item['anchors'] = button_set(array(
            anchor_add("/app/qos/ifn/add/$ifn"),
        ));
        $item['details'] = array(
            $ifn,
            "<span id='" . $ifn . "_up'>-</span>",
            "<span id='" . $ifn . "_down'>-</span>",
            "<span id='" . $ifn . "_r2q_up'>-</span>",
        );
        $items[] = $item;
    }

    echo summary_table(
        lang('qos_interfaces_title'),
        array(),
        $headers,
        $items,
        array('id' => 'ifn_summary')
    );
}
else {
    echo form_open('qos/ifn',
        array('id' => 'ifn_form')
    );
    echo form_header(
        lang('qos_interface_edit_title'),
        array('id' => 'qos_ifn'));

    echo "<input type='hidden' name='ifn' value='$ifn'>\n";

    echo field_button_set(
        array( 
            form_submit_update('submit-form'),
            anchor_cancel('/app/qos')
    ));

    echo form_footer();
    echo form_close();
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
