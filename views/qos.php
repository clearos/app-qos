<?php

/**
 * Warning view.
 *
 * @category   ClearOS
 * @package    ibVPN
 * @subpackage Views
 * @author     Darryl Sokoloski <dsokoloski@clearfoundation.com>
 * @copyright  2013 Darryl Sokoloski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
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

///////////////////////////////////////////////////////////////////////////////
// Form 
///////////////////////////////////////////////////////////////////////////////

echo "<table><tr>\n";

for ($i = 0; $i < 7; $i++) {
echo "<td>
<center>
<input type='checkbox' id='bucket{$i}_lock' />
<div id='bucket$i' class='bucket'></div>
<input type='text' id='bucket{$i}_amount' class='bucket_input' />
</center>
</td>\n";
}

echo '<td>';
echo "<div class='bucket_button'>" . anchor_javascript('bucket_ramp', lang('qos_ramp'), 'high') . '</div>';
echo "<div class='bucket_button'>" . anchor_javascript('bucket_equalize', lang('qos_equalize'), 'high') . '</div>';
echo "<div class='bucket_button'>" . anchor_javascript('bucket_reset', lang('qos_reset'), 'high') . '</div>';
echo "</td></tr></table>\n";
