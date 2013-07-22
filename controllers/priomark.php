<?php

/**
 * Priomark rules controller
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

use \clearos\apps\qos\Qos as Qos_Lib;
use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Priomark rules controller.
 *
 * @category   apps
 * @package    qos
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mobile_demo/
 */

class Priomark extends ClearOS_Controller
{
    protected $type;
    protected $direction;

    /**
     * Constructor
     */

    function __construct($type, $direction)
    {
        // Load dependencies
        //------------------

        $this->load->library('qos/Qos');

        // Set class variables
        //--------------------

        switch ($type) {
        case Qos_Lib::PRIOMARK_TYPE_IPV4:
            $this->type = $type;
            break;

        default:
            // TODO: Throw exception...
        }

        switch ($direction) {
        case Qos_Lib::DIRECTION_UP:
        case Qos_Lib::DIRECTION_DOWN:
            $this->direction = $direction;
            break;

        default:
            // TODO: Throw exception...
        }

        // Construct parent
        //-----------------

        parent::__construct();
    }

    /**
     * Read-only index.
     */

    function index()
    {
        $this->_view_edit('view');
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

    function edit($key)
    {
        $this->_view_edit('edit', $key);
    }

    /**
     * Add view.
     */

    function add()
    {
        $this->_view_edit('add');
    }

    /**
     * Delete view.
     */

    function delete($key)
    {
        // Delete priomark rule
        //---------------------

        try {
            $nickname = base64_decode(urldecode($key));
            $this->qos->delete_priomark_rule($this->type, $nickname);

            redirect('/qos/qos');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Priomark rule controller
     *
     * @param string $form_type form type
     * @param string $type priomark rule type
     * @param string $key priomark key
     *
     * @return view
     */

    function _view_edit($form_type, $key = NULL)
    {
        // Load dependencies
        //------------------

        $this->load->library('network/Iface_Manager');

        // Load dependencies
        //------------------

        $this->lang->load('qos');

        // Load data 
        //----------

        $priomark_rules = $this->qos->get_priomark_rules($this->type);
        $ifn_external = $this->iface_manager->get_external_interfaces();

        // Set validation rules
        //---------------------
/*
        $this->form_validation->set_policy(
            'identifier', 'ether_wake/Ether_Wake',
            'validate_ident', TRUE
        );
        $this->form_validation->set_policy(
            'password', 'ether_wake/Ether_Wake',
            'validate_password', FALSE
        );

        $form_ok = $this->form_validation->run();
*/
        $form_ok = TRUE;

        // Handle form submit
        //-------------------

        if ($this->input->post('submit-form') && ($form_ok === TRUE)) {
            try {

                redirect('/qos/qos');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view
        //----------

        $data = array(
            'form_type' => $form_type,
            'direction' => $this->direction,
            'priomark_rules' => $priomark_rules,
            'priomark_type' => $this->type,
        );

        if ($form_type == 'edit') {
            $nickname = base64_decode(urldecode($key));
            $data['nickname'] = $nickname;
        }

        if ($form_type == 'edit' || $form_type == 'add')
            $data['external_interfaces'] = $ifn_external;

        $this->page->view_form('qos/priomark', $data, lang('qos_app_name'));
    }
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
