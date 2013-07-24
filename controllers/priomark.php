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

use \clearos\apps\qos\Invalid_Direction_Exception as Invalid_Direction_Exception;
use \clearos\apps\qos\Invalid_Priomark_Type_Exception as Invalid_Priomark_Type_Exception;
use \clearos\apps\qos\Priomark_Exists_Exception as Priomark_Exists_Exception;
use \clearos\apps\qos\Priomark_Invalid_Interface_Exception as Priomark_Invalid_Interface_Exception;
use \clearos\apps\qos\Priomark_Not_Found_Exception as Priomark_Not_Found_Exception;
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
    protected $type = -1;
    protected $direction = -1;
    protected $priomark_rules = NULL;
    protected $ifn_external = NULL;
    protected $interfaces = NULL;

    /**
     * Constructor
     */

    function __construct($type, $direction)
    {
        // Load dependencies
        //------------------

        $this->load->library('qos/Qos');
        $this->load->library('network/Iface_Manager');

        try {
            // Set class variables
            //--------------------

            $this->priomark_rules = $this->qos->get_priomark_rules($this->type);
            $this->ifn_external = $this->iface_manager->get_external_interfaces();
            $this->interfaces = $this->qos->get_interface_config();

            switch ($type) {
            case Qos_Lib::PRIOMARK_TYPE_IPV4:
                $this->type = $type;
                break;

            default:
                clearos_load_library('qos/Invalid_Priomark_Type_Exception');
                throw new Invalid_Priomark_Type_Exception($type);
            }

            switch ($direction) {
            case Qos_Lib::DIRECTION_UP:
            case Qos_Lib::DIRECTION_DOWN:
                $this->direction = $direction;
                break;

            default:
                clearos_load_library('qos/Invalid_Direction_Exception');
                throw new Invalid_Direction_Exception($direction);
            }

        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
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

            $this->qos->firewall_restart();

            redirect('/qos/qos');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Enable rule.
     */

    function enable($key)
    {
        // Enable priomark rule
        //---------------------

        try {
            $nickname = base64_decode(urldecode($key));
            $rule = $this->_priomark_lookup($nickname);
            if ($rule == NULL) {
                clearos_load_library('qos/Priomark_Not_Found_Exception');
                throw new Priomark_Not_Found_Exception($nickname);
            }
            if (! $this->_is_valid_interface($rule['interface'])) {
                clearos_load_library('qos/Priomark_Invalid_Interface_Exception');
                throw new Priomark_Invalid_Interface_Exception($rule['interface']);
            }
            $this->qos->enable_priomark_rule(
                $this->type, $nickname, $this->direction,
                Qos_Lib::PRIOMARK_ENABLED
            );

            $this->qos->firewall_restart();

            redirect('/qos/qos');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Enable all disabled rules.
     */

    function enable_all()
    {
        // Enable all disabled priomark rules
        //-----------------------------------

        try {
            foreach ($this->priomark_rules as $priomark_type => $rules) {
                if ($priomark_type != $this->type) continue;
                foreach ($rules as $nickname => $rule) {
                    if ($rule['direction'] != $this->direction) continue;
                    if ($rule['enabled'] === TRUE) continue;
                    if (! $this->_is_valid_interface($rule['interface'])) continue;

                    $this->qos->enable_priomark_rule($this->type,
                        $nickname, $this->direction,
                        Qos_Lib::PRIOMARK_ENABLED
                    );
                }
            }

            $this->qos->firewall_restart();

            redirect('/qos/qos');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Disable rule.
     */

    function disable($key)
    {
        // Disable priomark rule
        //----------------------

        try {
            $nickname = base64_decode(urldecode($key));
            $this->qos->enable_priomark_rule(
                $this->type, $nickname, $this->direction,
                Qos_Lib::PRIOMARK_DISABLED
            );

            $this->qos->firewall_restart();

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

    protected function _view_edit($form_type, $key = NULL)
    {
        // Set validation rules
        //---------------------

        $this->form_validation->set_policy(
            'nickname', 'qos/Qos',
            'validate_nickname', TRUE
        );
        $this->form_validation->set_policy(
            'saddr', 'qos/Qos',
            'validate_address', FALSE
        );
        $this->form_validation->set_policy(
            'sport', 'qos/Qos',
            'validate_port', FALSE
        );
        $this->form_validation->set_policy(
            'daddr', 'qos/Qos',
            'validate_address', FALSE
        );
        $this->form_validation->set_policy(
            'dport', 'qos/Qos',
            'validate_port', FALSE
        );

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($form_ok &&
            ($this->input->post('submit-add') || $this->input->post('submit-edit'))) {
            try {
                if ($this->input->post('submit-add')) {
                    if (array_key_exists($this->input->post('nickname'),
                        $this->priomark_rules[$this->type])) {
                        clearos_load_library('qos/Priomark_Exists_Exception');
                        throw new Priomark_Exists_Exception(
                            $this->input->post('nickname'));
                    }
                }
                else if ($this->input->post('submit-edit')) {
                    if (! array_key_exists($this->input->post('nickname'),
                        $this->priomark_rules[$this->type])) {
                        clearos_load_library('qos/Priomark_Not_Found_Exception');
                        throw new Priomark_Not_Found_Exception(
                            $this->input->post('nickname'));
                    }
                    $this->qos->delete_priomark_rule(
                        $this->type, $this->input->post('nickname'));
                }

                $saddr = $this->input->post('saddr');
                if (! strlen($saddr)) $saddr = '-';
                $sport = $this->input->post('sport');
                if (! strlen($sport)) $sport = '-';
                $daddr = $this->input->post('daddr');
                if (! strlen($daddr)) $daddr = '-';
                $dport = $this->input->post('dport');
                if (! strlen($dport)) $dport = '-';

                $this->qos->add_priomark_rule(
                    $this->type, $this->input->post('nickname'),
                    $this->input->post('interface'), $this->direction,
                    $this->input->post('priority'), $this->input->post('protocol'),
                    $saddr, $sport, $daddr, $dport
                );

                if ($this->input->post('enabled') === FALSE ||
                    ! $this->_is_valid_interface($this->input->post('interface'))) {
                    $this->qos->enable_priomark_rule(
                        $this->type, $this->input->post('nickname'),
                        $this->direction, Qos_Lib::PRIOMARK_DISABLED
                    );
                }

                $this->qos->firewall_restart();

                redirect('/qos/qos');
            }
            catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view
        //----------

        $data = array(
            'form_type' => $form_type,
            'direction' => $this->direction,
            'priomark_rules' => $this->priomark_rules,
            'priomark_type' => $this->type,
        );

        if ($form_type == 'edit') {
            $nickname = base64_decode(urldecode($key));
            $data['nickname'] = $nickname;
        }

        if ($form_type == 'edit' || $form_type == 'add') {
            $data['avail_interfaces'] = array();
            foreach ($this->ifn_external as $ifn) {
                if (! $this->_is_valid_interface($ifn)) continue;
                $data['avail_interfaces'][] = $ifn;
            }
        }

        $this->page->view_form('qos/priomark', $data, lang('qos_app_name'));
    }

    /**
     * Determine if a priomark rule's interface argument is valid.
     *
     * Valid interfaces are '*', or a configured external interface.
     *
     * @param string $interface external interface to validate
     *
     * @return boolean
     */

    protected function _is_valid_interface($interface)
    {
        if ($interface == '*') return TRUE;
        if (! array_key_exists($interface,
            $this->interfaces[($this->direction == Qos_Lib::DIRECTION_UP) ?
                'up' : 'down'])) return FALSE;
        if (in_array($interface, $this->ifn_external)) return TRUE;
        return FALSE;
    }

    /**
     * Return a priomark rule by nickname.
     *
     * @param string $nickname priomark rule nickname
     *
     * @return array
     */

    protected function _priomark_lookup($nickname)
    {
        if (! array_key_exists($nickname, $this->priomark_rules[$this->type]))
            return NULL;
        $rule = $this->priomark_rules[$this->type][$nickname];
        if ($rule['direction'] != $this->direction) return NULL;
        return $rule;
    }
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
