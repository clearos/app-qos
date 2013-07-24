<?php

/**
 * Bandwidth priority class view.
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

if ($form_type == 'view') {
    $headers = array(lang('qos_direction'), lang('network_interface'));
    for ($i = 0; $i < $priority_classes; $i++)
        $headers[] = $i + 1;

    $rows = array();
    foreach ($pc_config as $direction => $entries) {
        foreach ($entries as $ifn => $values)
            $rows[$ifn][$direction] = $values;
    }

    $directions = array('up', 'down');
    $items = array();
    foreach ($directions as $direction) {

        $dir_lang = ($direction == 'up') ?
            lang('qos_upstream') : lang('qos_downstream');

        foreach ($rows as $ifn => $row) {
            $key = ($ifn == '*') ? 'all' : $ifn;
            $key_lang = ($ifn == '*') ? lang('base_all') : $ifn;

            $item['title'] = $key_lang;
            $item['action'] = '';

            $buttons = array(
                anchor_edit("/app/qos/$type_name/edit/$key")
            );
            if ($ifn != '*') {
                $buttons[] =
                    anchor_delete("/app/qos/$type_name/delete/$key");
            }
            $item['anchors'] = button_set($buttons);

            $item['details'] = array(
                $dir_lang,
                "<span id=\'$key\'>{$key_lang}</span>");

            for ($i = 0; $i < $priority_classes; $i++) {
                $item['details'][] =
                    '<span id=\'' . $key . $i . "'>{$row[$direction][$i]}%</span>";
            }
            $items[] = $item;
        }
    }

    $header_buttons = array();
    if (count($available_external_interfaces)) {
        $header_buttons[] = anchor_custom(
            "/app/qos/$type_name/add", lang('base_add'));
    }

    echo summary_table(
        lang("qos_class_{$type_name}_title"),
        $header_buttons,
        $headers,
        $items,
        array('id' => "pc{$type_name}_summary", 'grouping' => TRUE)
    );
}
else if ($form_type == 'add' && $ifn == NULL) {
    if (count($available_external_interfaces) == 0)
        redirect('/qos/qos');
    if (count($available_external_interfaces) == 1) {
        reset($available_external_interfaces);
        $ifn = current($available_external_interfaces);
        redirect("/qos/{$type_name}/add/$ifn");
    }

    echo form_open("qos/$type_name/add",
        array('id' => "{$type_name}_form")
    );
    echo form_header(
        lang("qos_class_{$type_name}_title"),
        array('id' => "qos_pc$type_name"));

    $interfaces = array();
    foreach ($available_external_interfaces as $ifn) 
        $interfaces[$ifn] = $ifn;
    echo field_simple_dropdown('ifn',
        $interfaces, '',
        lang('network_interface'), FALSE);

    echo field_button_set(
        array( 
            form_submit_next('submit-form-ifn-select'),
            anchor_cancel('/app/qos')
        )
    );

    echo form_footer();
    echo form_close();
}
else {
    require_once('slider_array.inc.php');

    echo form_open("qos/$type_name",
        array('id' => "{$type_name}_form")
    );
    echo form_header(
        lang("qos_class_{$type_name}_title"),
        array('id' => "qos_pc$type_name"));

    $key_lang = ($ifn == 'all') ? lang('base_all') : $ifn;
    $mode = ($type_name == 'limit') ? 1 : 0;

    $upstream_lang = lang('qos_upstream') . " - $key_lang";
    $downstream_lang = lang('qos_downstream') . " - $key_lang";

    echo form_banner(form_slider_array("pcup$type_name",
        $upstream_lang, $mode,
        $priority_classes, $default_values_up));
    echo form_banner(form_slider_array("pcdown$type_name",
        $downstream_lang, $mode,
        $priority_classes, $default_values_down));

    echo "<input type='hidden' name='ifn' value='$ifn'>\n";

    echo field_button_set(
        array( 
            ($form_type == 'add') ?
                form_submit_add('submit-form') :
                form_submit_update('submit-form'),
            anchor_cancel('/app/qos')
    ));

    echo form_footer();
    echo form_close();
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
