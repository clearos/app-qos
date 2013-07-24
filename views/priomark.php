<?php

/**
 * Priomark rules view.
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

use \clearos\apps\qos\Qos as Qos_Lib;
use \Exception as Exception;

$this->lang->load('base');
$this->lang->load('firewall');
$this->lang->load('network');
$this->lang->load('qos');

$controller = ($direction == Qos_Lib::DIRECTION_UP) ? 'upstream' : 'downstream';

///////////////////////////////////////////////////////////////////////////////
// Form or summary table
///////////////////////////////////////////////////////////////////////////////

if ($form_type == 'view') {
    $headers = array(
        lang('qos_name'),
        lang('qos_priority'),
        lang('network_interface'),
        lang('network_protocol'),
        lang('qos_source') . ' / ' . lang('qos_destination'),
    );

    $items = array();
    $items_disabled = 0;

    foreach ($priomark_rules as $type => $rules) {

        if ($type != $priomark_type) continue;

        foreach ($rules as $nickname => $config) {

            if ($config['direction'] != $direction) continue;

            $key = urlencode(base64_encode($nickname));

            $state = 'enable';
            if ($config['enabled'] == Qos_Lib::PRIOMARK_ENABLED)
                $state = 'disable';
            else
                $items_disabled++;
            $state_anchor = 'anchor_' . $state;

            $ifn = lang('qos_all');
            if ($config['interface'] != '*') $ifn = $config['interface'];

            $protocol = lang('qos_any');
            if (substr($config['protocol'], 0, 1) == '!') {
                $protocol = lang('qos_not') . ' ';
                $protocol .= strtoupper(substr($config['protocol'], 1));
            }
            else if (strlen($config['protocol'])) {
                if ($config['protocol'] == '-')
                    $protocol = lang('qos_all');
                else
                    $protocol = strtoupper($config['protocol']);
            }

            $saddr = lang('qos_any');
            if ($config['saddr'] != '-') $saddr = $config['saddr'];
            $daddr = lang('qos_any');
            if ($config['daddr'] != '-') $daddr = $config['daddr'];
            $sport = lang('qos_all');
            if ($config['sport'] != '-') $sport = $config['sport'];
            $dport = lang('qos_all');
            if ($config['dport'] != '-') $dport = $config['dport'];

            $source = "$saddr : $sport";
            $destination = "$daddr : $dport";

            $item['title'] = $nickname;
            $item['action'] = '';
            $item['anchors'] = button_set(array(
                anchor_edit("/app/qos/$controller/edit/$key"),
                $state_anchor("/app/qos/$controller/$state/$key"),
                anchor_delete("/app/qos/$controller/delete/$key")
            ));
            $item['details'] = array(
                $nickname,
                "<span id='{$key}_priority'>" . ($config['priority'] + 1) . '</span>',
                "<span id='{$key}_interface'>$ifn</span>",
                "<span id='{$key}_protocol'>$protocol</span>",
                "<span id='{$key}_address_port'>$source / $destination</span>",
            );
            $items[] = $item;
        }
    }

    $header_buttons = array();
    if ($items_disabled > 0) {
        $header_buttons[] = anchor_custom(
            "/app/qos/$controller/enable_all", lang('qos_enable_all'));
    }
    $header_buttons[] = anchor_custom(
        "/app/qos/$controller/add", lang('base_add'));

    echo summary_table(
        lang(($direction == Qos_Lib::DIRECTION_UP) ?
            'qos_priomark_upstream_rules' : 'qos_priomark_downstream_rules'),
        $header_buttons,
        $headers,
        $items,
        array('id' => ($direction == Qos_Lib::DIRECTION_UP) ?
            'priomark_upstream_rules' : 'priomark_downstream_rules',
            'grouping' => TRUE)
    );
}
else {
    $title_lang = '';

    $read_only = FALSE;

    $interface = lang('qos_all');
    $interfaces = array('*' => lang('qos_all'));
    foreach ($avail_interfaces as $ifn)
        $interfaces[$ifn] = $ifn;

    $config = NULL;
    if (isset($nickname) &&
        array_key_exists($priomark_type, $priomark_rules) &&
        array_key_exists($nickname, $priomark_rules[$priomark_type]) &&
        $priomark_rules[$priomark_type][$nickname]['direction'] == $direction)
        $config = $priomark_rules[$priomark_type][$nickname];

    if ($form_type == 'add') {
        $title_lang = ($direction == Qos_Lib::DIRECTION_UP) ?
            'qos_add_priomark_upstream_rule' : 'qos_add_priomark_downstream_rule';
    }
    else {
        $read_only = TRUE;
        $title_lang = ($direction == Qos_Lib::DIRECTION_UP) ?
            'qos_edit_priomark_upstream_rule' : 'qos_edit_priomark_downstream_rule';

        if ($config != NULL) {
            if ($config['interface'] != '*') {
                $interface = $config['interface'];
                if (! array_key_exists($interfaces, $interface))
                    $interfaces[$interface] = $interface;
            }
        }
    }

    $protocols = array('-' => lang('qos_any'),
        'tcp' => 'TCP', '!tcp' => lang('qos_not') . ' TCP',
        'udp' => 'UDP', '!udp' => lang('qos_not') . ' UDP');

    $enabled = TRUE;
    $priority = 4;
    $protocol = '-';
    $saddr = '';
    $sport = '';
    $daddr = '';
    $dport = '';

    if ($config != NULL) {
        $enabled = $config['enabled'];
        $priority = $config['priority'];
        $protocol = $config['protocol'];
        if ($protocol != '-' && !array_key_exists($protocol, $protocols))
            $protocols[$protocol] = strtoupper($protocol);
        if ($config['saddr'] != '-')
            $saddr = $config['saddr'];
        if ($config['sport'] != '-')
            $sport = $config['sport'];
        if ($config['daddr'] != '-')
            $daddr = $config['daddr'];
        if ($config['dport'] != '-')
            $dport = $config['dport'];
    }

    $priority_classes = array();
    for ($i = 0; $i < Qos_Lib::PRIORITY_CLASSES; $i++) {
        if ($i == 0)
            $priority_classes[$i] = sprintf('%d - %s', $i + 1, lang('base_highest'));
        else if ($i == Qos_Lib::PRIORITY_CLASSES - 1)
            $priority_classes[$i] = sprintf('%d - %s', $i + 1, lang('base_lowest'));
        else
            $priority_classes[$i] = $i + 1;
    }

    echo form_open("qos/$controller/$form_type",
        array('id' => "priomark_{$controller}_form")
    );
    echo form_header(
        lang($title_lang),
        array('id' => "qos_priomark_$controller"));

    // Nickname
    echo field_input('nickname', $nickname, lang('firewall_nickname'), $read_only);

    // External interface
    echo field_dropdown('interface',
        $interfaces, $interface, lang('network_interface'), FALSE);

    // Enabled?
    echo field_checkbox('enabled', $enabled, lang('base_enabled') . '?', FALSE);

    // Priority class
    echo field_dropdown('priority',
        $priority_classes, $priority, lang('qos_priority'), FALSE);

    // Protocol
    echo field_dropdown('protocol',
        $protocols, $protocol, lang('network_protocol'), FALSE);

    // Source Address
    echo field_input('saddr', $saddr, lang('firewall_source_address'), FALSE);

    // Source Port
    echo field_input('sport', $sport, lang('network_source_port'), FALSE);

    // Destination Address
    echo field_input('daddr', $daddr, lang('firewall_destination_address'), FALSE);

    // Destination Port
    echo field_input('dport', $dport, lang('network_destination_port'), FALSE);

    echo field_button_set(
        array( 
            ($form_type == 'add') ?
                form_submit_add('submit-add') : form_submit_update('submit-edit'),
            anchor_cancel('/app/qos')
    ));

    echo form_footer();
    echo form_close();
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
