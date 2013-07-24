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

use \clearos\apps\qos\Qos as Qos_Lib;
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
        $ifn = NULL;
        if ($this->input->post('submit-form'))
            $ifn = $this->input->post('ifn');
        $this->_view_edit('view', $ifn);
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
     * Add view.
     */

    function add($ifn)
    {
        $this->_view_edit('add', $ifn);
    }

    /**
     * Enable QoS Engine.
     */

    function enable()
    {
        // Load dependencies
        //------------------

        $this->load->library('qos/Qos');

        // Enable QoS Engine
        //------------------

        try {
            $this->qos->enable_engine();

            redirect('/qos/qos');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Disable QoS Engine.
     */

    function disable()
    {
        // Load dependencies
        //------------------

        $this->load->library('qos/Qos');

        // Enable QoS Engine
        //------------------

        try {
            $this->qos->enable_engine(FALSE);

            redirect('/qos/qos');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Delete view.
     */

    function delete($ifn)
    {
        // Load dependencies
        //------------------

        $this->load->library('qos/Qos');

        // Delete interface configuration
        //-------------------------------

        try {
            $this->qos->delete_interface_config($ifn);

            $this->qos->firewall_restart();

            redirect('/qos/qos');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
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

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy(
            'speed_up', 'qos/Qos',
            'validate_speed', TRUE
        );
        $this->form_validation->set_policy(
            'speed_down', 'qos/Qos',
            'validate_speed', TRUE
        );
        if ($this->input->post('submit-form')) {
            if ($this->input->post('r2q_auto_up') != 'on') {
                $this->form_validation->set_policy(
                    'r2q_up', 'qos/Qos',
                    'validate_r2q', TRUE
                );
            }
            if ($this->input->post('r2q_auto_down') != 'on') {
                $this->form_validation->set_policy(
                    'r2q_down', 'qos/Qos',
                    'validate_r2q', TRUE
                );
            }
        }

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit-form') && ($form_ok === TRUE)) {
            try {
                $this->qos->set_interface_config(
                    $this->input->post('ifn'),
                    array(
                        $this->input->post('speed_up'),
                        $this->input->post('speed_down')
                    ),
                    array(
                        ($this->input->post('r2q_auto_up') == 'on') ?
                            'auto' : $this->input->post('r2q_up'),
                        ($this->input->post('r2q_auto_down') == 'on') ?
                            'auto' : $this->input->post('r2q_down')
                    )
                );
        
                $types = array(
                    Qos_Lib::PRIORITY_CLASS_RESERVED, Qos_Lib::PRIORITY_CLASS_LIMIT);

                foreach ($types as $type) {
                    $pc_config = $this->qos->get_priority_class_config($type);
                    if (! array_key_exists('up', $pc_config) ||
                        ! array_key_exists('down', $pc_config) ||
                        ! array_key_exists('*', $pc_config['up']) ||
                        ! array_key_exists('*', $pc_config['down'])) {

                        $values = array();
                        for ($i = 0; $i < Qos_Lib::PRIORITY_CLASSES; $i++)
                            $values[] = 0;

                        switch ($type) {
                        case Qos_Lib::PRIORITY_CLASS_RESERVED:
                            $i = 0;
                            for ($total = 100; $total > 0; $total--) {
                                $values[$i++] += 1;
                                if ($i == Qos_Lib::PRIORITY_CLASSES) $i = 0;
                            }
                            break;

                        case Qos_Lib::PRIORITY_CLASS_LIMIT:
                            for ($i = 0; $i < Qos_Lib::PRIORITY_CLASSES; $i++)
                                $values[$i] = 100;
                            break;
                        }

                        $this->qos->set_priority_class_config(
                            $type, '*', $values, $values);
                    }
                }

                $this->qos->firewall_restart();

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
        $data['form_type'] = $form_type;
        $data['interfaces'] = $ifn_config;
        $data['external_interfaces'] = $ifn_external;
        $data['engine_status'] = $this->qos->get_engine_status();

        if ($form_type == 'add') {
            $data['r2q_auto_up'] = TRUE;
            $data['r2q_auto_down'] = TRUE;
        }
        else if ($form_type == 'edit') {
            if (array_key_exists('up', $ifn_config) &&
                array_key_exists($ifn, $ifn_config['up'])) {
                $data['speed_up'] = $ifn_config['up'][$ifn]['speed'];
                $data['r2q_auto_up'] =
                    ($ifn_config['up'][$ifn]['r2q'] == 'auto') ?
                        TRUE : FALSE;
                $data['r2q_up'] =
                    ($ifn_config['up'][$ifn]['r2q'] == 'auto') ?
                        '' : $ifn_config['up'][$ifn]['r2q'];
            }
            if (array_key_exists('down', $ifn_config) &&
                array_key_exists($ifn, $ifn_config['down'])) {
                $data['speed_down'] = $ifn_config['down'][$ifn]['speed'];
                $data['r2q_auto_down'] =
                    ($ifn_config['down'][$ifn]['r2q'] == 'auto') ?
                        TRUE : FALSE;
                $data['r2q_down'] =
                    ($ifn_config['down'][$ifn]['r2q'] == 'auto') ?
                        '' : $ifn_config['down'][$ifn]['r2q'];
            }
        }

        $this->page->view_form('qos/ifn', $data, lang('qos_app_name'));
    }
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
