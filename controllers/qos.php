<?php

/**
 * QoS controller.
 *
 * @category   apps
 * @package    qos
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mobile_demo/
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\network\Network as Network;
use \clearos\apps\qos\Qos as Qos_Lib;
use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * QoS controller.
 *
 * @category   apps
 * @package    qos
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mobile_demo/
 */

class Qos extends ClearOS_Controller
{
    /**
     * Index.
     */

    function index()
    {
        // Load dependencies
        //---------------

        $this->load->library('qos/Qos');
        $this->lang->load('qos');


        // Load data
        //----------

        $interfaces =
            $this->qos->get_interface_config();
        $pc_limit =
            $this->qos->get_priority_class_config(Qos_Lib::PRIORITY_CLASS_LIMIT);
        $pc_reserved  =
            $this->qos->get_priority_class_config(Qos_Lib::PRIORITY_CLASS_RESERVED);
        $priomark_rules = $this->qos->get_priomark_rules();

        // Load controllers
        //-----------------

        $controllers = array('qos/ifn');
        /* Advanced settings
        if (count($interfaces) && count($pc_limit))
            $controllers[] = 'qos/reserved';
        if (count($interfaces) && count($pc_reserved))
            $controllers[] = 'qos/limit';
        */
        if (count($interfaces)) {
            $controllers[] = 'qos/upstream';
            $controllers[] = 'qos/downstream';
        }

        $options['type'] = MY_Page::TYPE_WIDE_CONFIGURATION;
        $this->page->view_controllers($controllers, lang('qos_app_name'), $options);
    }
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
