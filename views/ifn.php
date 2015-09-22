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
$this->lang->load('base');
$this->lang->load('network');

///////////////////////////////////////////////////////////////////////////////
// Form or summary table
///////////////////////////////////////////////////////////////////////////////

if ($form_type == 'add' && $warning == TRUE) {
    echo infobox_warning(
        lang('base_warning'), '<p>' .
        lang('qos_interface_speed_not_set') . '</p>' .
        '<p align="center">' . anchor_ok('/app/network') . '&nbsp;' .
        anchor_cancel('/app/qos') . '</p>'
    );
    return;
}

if ($form_type == 'view') {
    $headers = array(
        lang('network_interface'),
        lang('qos_rate_to_quantum'),
    );

    $rows = array();
    foreach ($interfaces as $direction => $config) {
        foreach ($config as $ifn => $settings) {
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
            lang('base_automatic') : $config['up']['r2q'];
        $r2q_down = ($config['down']['r2q'] == 'auto') ?
            lang('base_automatic') : $config['down']['r2q'];
        $item['details'] = array(
            $ifn,
            $r2q_up . '/' . $r2q_down
        );
        $items[] = $item;
    }

    foreach ($external_interfaces as $ifn) {
        if (array_key_exists($ifn, $rows)) continue;

        $item['title'] = $ifn;
        $item['action'] = '';
        $item['anchors'] = button_set(array(
            anchor_add("/app/qos/ifn/add/$ifn"),
        ));
        $item['details'] = array($ifn, '');
        $items[] = $item;
    }

    $header_buttons = array();
    if ($engine_status === TRUE) {
        $header_buttons[] = anchor_custom(
            '/app/qos/ifn/disable', lang('qos_engine_disable'));
    }
    else {
        if (count($rows) > 0) {
            $header_buttons[] = anchor_custom(
                '/app/qos/ifn/enable', lang('qos_engine_enable'));
        }
    }

    echo summary_table(
        lang('network_external_network_interfaces'),
        $header_buttons,
        $headers,
        $items,
        array('id' => 'ifn_summary')
    );
}
else {
    echo form_open("qos/ifn/$form_type/$ifn", array('id' => 'ifn_form'));
    // Interface hidden field
    echo "<input type='hidden' name='ifn' value='$ifn'>\n";

    if ($form_type == 'add') 
        $form_title = lang('qos_interface_add_title');
    else
        $form_title = lang('qos_interface_edit_title');

    echo form_header($form_title, array('id' => 'qos_ifn'));

    // Upstream
    echo fieldset_header(lang('network_upstream'));
    echo field_checkbox('r2q_auto_up', $r2q_auto_up, lang('qos_rate_to_quantum_auto') . '?', FALSE);
    echo field_input('r2q_up', $r2q_up, lang('qos_rate_to_quantum'), FALSE);
    echo fieldset_footer();

    // Downstream
    echo fieldset_header(lang('network_downstream'));
    echo field_checkbox('r2q_auto_down', $r2q_auto_down, lang('qos_rate_to_quantum_auto') . '?', FALSE);
    echo field_input('r2q_down', $r2q_down, lang('qos_rate_to_quantum'), FALSE);
    echo fieldset_footer();

    echo field_button_set(
        array( 
            ($form_type == 'add') ?
                form_submit_add('submit-form') : form_submit_update('submit-form'),
            anchor_cancel('/app/qos')
    ));

    echo form_footer();
    echo form_close();
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
