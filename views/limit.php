<?php

/**
 * Bandwidth class limit view.
 *
 * @category   apps
 * @package    qos
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/base/
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

$this->lang->load('base');
$this->lang->load('qos');

require_once('slider_array.inc.php');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
    $buttons = array( 
        form_submit_update('submit-form'),
        anchor_cancel('/app/qos')
    );
} else {
    $read_only = TRUE;
    $buttons = array( 
        anchor_edit('/app/qos/limit/edit')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form 
///////////////////////////////////////////////////////////////////////////////

echo form_open('qos/limit',
    array('id' => 'limit_form')
);
echo form_header(
    lang('qos_priority_class') . ': ' .
    lang('qos_class_limit_title'), array('id' => 'qos'));

if ($read_only == FALSE) {
    $defaults = array();
    echo form_banner(form_slider_array('pcuplimit', 'Upstream', 1,
        $priority_classes, $default_values));
    echo form_banner(form_slider_array('pcdownlimit', 'Downstream', 1,
        $priority_classes, $default_values));
}

echo field_button_set($buttons);

echo form_footer();
echo form_close();

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
