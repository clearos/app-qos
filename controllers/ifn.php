<?php

/**
 * External interfaces controller
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

use \clearos\apps\qos\Qos as Qos;
use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * External interfaces controller.
 *
 * @category   apps
 * @package    qos
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mobile_demo/
 */

class Ifn extends ClearOS_Controller
{
    /**
     * Read-only index.
     */

    function index()
    {
        $this->view();
    }

    /**
     * Read-only view.
     */

    function view()
    {
        $this->_view_edit('view');
    }
    
    /**
     * Edit view.
     */

    function edit($ifn)
    {
        $this->_view_edit('edit', $ifn);
    }

    /**
     * External interfaces controller
     *
     * @param string $form_type form type
     *
     * @return view
     */

    function _view_edit($form_type, $ifn = NULL)
    {
        // Load dependencies
        //------------------

        $this->load->library('qos/Qos');
        $this->load->library('network/Iface_Manager');
        $this->lang->load('qos');

        // Handle form submit
        //-------------------
        if ($this->input->post('submit-form')) {
            try {
                redirect('/qos/qos');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load data 
        //----------
        $ifn_config = $this->qos->get_interface_config();
        $ifn_external = $this->iface_manager->get_external_interfaces();

        // Load views
        //-----------

        $data = array();
        $data['ifn'] = $ifn;
        $data['read_only'] = ($form_type == 'edit') ? FALSE : TRUE;
        $data['interfaces'] = $ifn_config;
        $data['interfaces'] = $ifn_config;
        $data['external_interfaces'] = $ifn_external;

        if ($data['read_only']) {
        }
        else {
        }

        $this->page->view_form('qos/ifn', $data, lang('qos_app_name'));
    }
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
