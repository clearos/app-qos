<?php

/**
 * QoS class.
 *
 * @category    apps
 * @package     qos
 * @subpackage  libraries
 * @author      ClearFoundation <developer@clearfoundation.com>
 * @copyright   2013 ClearFoundation
 * @license     GNU Lesser General Public License version 3 or later
 * @link        http://www.clearfoundation.com/docs/developer/apps/qos/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\qos;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ?
    getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';

require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('qos');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\base\Webconfig as Webconfig;
use \clearos\apps\firewall\Firewall as Firewall;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('base/Webconfig');
clearos_load_library('firewall/Firewall');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\qos\Prioclass_Limit_Underflow_Exception as Prioclass_Limit_Underflow_Exception;
use \Exception as Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('qos/Prioclass_Limit_Underflow_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * QoS class.
 *
 * @category    apps
 * @package     qos
 * @subpackage  libraries
 * @author      ClearFoundation <developer@clearfoundation.com>
 * @copyright   2013 ClearFoundation
 * @license     GNU Lesser General Public License version 3 or later
 * @link        http://www.clearfoundation.com/docs/developer/apps/qos/
 */

class Qos extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/clearos/qos.conf';
    const FILE_BANDWIDTH_CONFIG = '/etc/clearos/bandwidth.conf';

    const PRIORITY_CLASSES = 7;
    const PRIORITY_CLASS_RESERVED = 1;
    const PRIORITY_CLASS_LIMIT = 2;

    const DIRECTION_UP = 0;
    const DIRECTION_DOWN = 1;

    const PRIOMARK_TYPE_ALL = -1;
    const PRIOMARK_TYPE_IPV4 = 1;
    const PRIOMARK_TYPE_IPV6 = 2;
    const PRIOMARK_TYPE_IPV4_CUSTOM = 3;
    const PRIOMARK_TYPE_IPV6_CUSTOM = 4;

    const PRIOMARK_ENABLED = 1;
    const PRIOMARK_DISABLED = 0;

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    private $interface_fields;

    private $priomark_fields;
    private $priomark_fields_custom;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * QoS constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->interface_fields = array(
            'interface', 'speed', 'r2q'
        );

        $this->priomark_fields = array(
            'nickname', 'interface', 'enabled', 'direction',
            'priority', 'protocol', 'saddr', 'sport',
            'daddr', 'dport'
        );

        $this->priomark_fields_custom = array(
            'nickname', 'interface', 'enabled', 'direction',
            'priority', 'params'
        );
    }

    /**
     * Get configuration value by key.
     *
     * @access  private
     * @param   string $key configuration key to lookup
     * @return  string value for key
     * @throws  File_No_Match_Exception
     */

    private function _get_config_value($key)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);
        if (! $file->exists())
            throw new File_No_Match_Exception(self::FILE_CONFIG, $key);

        return trim($file->lookup_value("/^$key\s*=\s*/"), " \t\n\r\0\x0B\"'");
    }

    /**
     * Get QoS engine status.
     *
     * This method returns the QoS engine status (enabled or disabled).
     *
     * @return  boolean TRUE if enabled
     */

    public function get_engine_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $enabled = $this->_get_config_value('QOS_ENABLE');
            if ($enabled == 'on') return TRUE;
        }
        catch (File_No_Match_Exception $e) {
        }

        return FALSE;
    }

    /**
     * Enable/disable QoS engine.
     *
     * This method enables or disables the QoS engine.
     *
     * @param   boolean $enable TRUE to enable the QoS engine
     */

    public function enable_engine($enable = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $value = NULL;
        try {
            $value = $this->_get_config_value('QOS_ENABLE');
            if ($enable && $value == 'on') return;
            if ($enable === FALSE && $value == 'off') return;
        }
        catch (File_No_Match_Exception $e) {
        }

        $value = ($enable === TRUE) ? 'on' : 'off';

        $file = new File(self::FILE_CONFIG);
        if (! $file->exists()) return;
        $file->replace_lines("/^QOS_ENABLE.*$/", "QOS_ENABLE=\"$value\"\n", 1);

        $fw_restart = TRUE;

        if ($enable === TRUE) {
            try {
                $file = new File(self::FILE_BANDWIDTH_CONFIG);
                $value = $this->_get_config_value('BANDWIDTH_QOS');

                if ($value == 'on' && $file->exists()) {
                    $file->replace_lines(
                        '/^BANDWIDTH_QOS.*$/', "BANDWIDTH_QOS=\"off\"\n", 1);
                    $fw_restart = FALSE;
                }
            }
            catch (File_No_Match_Exception $e) {
            }
            catch (File_Not_Found_Exception $e) {
            }
        }

        if ($fw_restart) $this->firewall_restart();
    }

    /**
     * Restart the firewall.
     *
     * This method restart the firewall.
     */

    public function firewall_restart()
    {
        clearos_profile(__METHOD__, __LINE__);

        $firewall = new Firewall();
        $firewall->restart();
    }

    /**
     * Get external interface configuration.
     *
     * This method returns the configuration values for an external interface
     * assigned using the QOS_[UP|DOWN]STREAM keywords.
     * 
     * The returned array will contain zero or more entries keyed by interface
     * name containing the following fields:
     *
     *   interface  The external interface name
     *   speed      The connection speed in kbits per second
     *   r2q        The desired rate-to-quantum value which may be set to: auto
     *
     * @return  array interface configuration as an associative array
     */

    public function get_interface_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ifn_config = array();

        try {
            $directions = array('up', 'down');
            foreach ($directions as $direction) {
                $values = explode(' ', $this->_get_config_value('QOS_' .
                    strtoupper($direction) . 'STREAM'));
                foreach ($values as $value) {
                    if (strlen(trim($value)) == 0) continue;
                    $config = explode(':', $value, count($this->interface_fields));
                    if (count($config) != count($this->interface_fields)) continue;
                    foreach ($this->interface_fields as $i => $key)
                        $ifn[$key] = $config[$i];
                    $ifn_config[$direction][$ifn['interface']] = $ifn;
                }
            }
        }
        catch (File_No_Match_Exception $e) {
            return $ifn_config;
        }

        return $ifn_config;
    }

    /**
     * Set external interface configuration values.
     *
     * This method sets configuration values for an external interface assigned
     * to the QOS_[UP|DOWN]STREAM keywords.
     *
     * The $speed and $r2q paramters are arrays which contain two values, the
     * first (index 0) is the upstream value and the second (index 1) is the
     * corresponding downstream value.
     *
     * @param   string $ifn external interface name
     * @param   array $speed external interface connection speed in kbits/s
     * @param   array $r2q desired rate-to-quantum value or 'auto'
     * @throws  File_No_Match_Exception
     */

    public function set_interface_config($ifn, $speed, $r2q = array('auto', 'auto'))
    {
        clearos_profile(__METHOD__, __LINE__);

        $ifn_config = $this->get_interface_config();

        $directions = array('up' => 0, 'down' => 1);
        foreach ($directions as $direction => $index) {
            $ifn_config[$direction][$ifn]['speed'] = $speed[$index];
            $ifn_config[$direction][$ifn]['r2q'] = $r2q[$index];
        
            $this->_save_interface_config($ifn_config);
        }
    }

    /**
     * Delete an external interface configuration.
     *
     * This method removes an external interface configuration from the
     * following keywords: QOS_[UP|DOWN]STREAM, QOS_[UP|DOWN]STREAM_BWRES, and
     * qOS_[UP|DOWN]STREAM_BWLIMIT.  In addition, any PRIOMARK rules that
     * explictly set to this interface are set to a disabled state.
     *
     * @param   string $ifn external interface to delete from configuration
     * @throws  File_No_Match_Exception
     */

    public function delete_interface_config($ifn)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ifn_config = $this->get_interface_config();

        $directions = array('up', 'down');
        foreach ($directions as $direction) {
            if (array_key_exists($direction, $ifn_config) &&
                array_key_exists($ifn, $ifn_config[$direction]))
                unset($ifn_config[$direction][$ifn]);
        }

        $this->delete_priority_class(self::PRIORITY_CLASS_RESERVED, $ifn);
        $this->delete_priority_class(self::PRIORITY_CLASS_LIMIT, $ifn);

        $disabled = 0;
        $priomark_rules = $this->get_priomark_rules();
        foreach ($priomark_rules as $type => $rules) {
            foreach ($rules as $nickname => $rule) {
                if ($rule['interface'] != $ifn) continue;
                if ($rule['enabled'] == self::PRIOMARK_DISABLED) continue;
                $priomark_rules[$type][$nickname]['enabled'] = self::PRIOMARK_DISABLED;
                $disabled++;
            }
            if ($disabled > 0) {
                $this->_save_priomark_rules($type, $priomark_rules);
                $disabled = 0;
            }
        }

        $this->_save_interface_config($ifn_config);
    }

    /**
     * Save external interface configuration array.
     *
     * converts an external interface array in to the configuration file format.
     *
     * @access  private
     * @param   array $config external interface configuration array to save.
     */

    private function _save_interface_config($config)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);
        if (! $file->exists()) return;

        $directions = array('up', 'down');
        foreach ($directions as $direction) {
            $key = 'QOS_' . strtoupper($direction) . 'STREAM';
            $value = '';
            if (array_key_exists($direction, $config)) {
                ksort($config[$direction]);
                foreach ($config[$direction] as $ifn => $params)
                    $value .= " $ifn:{$params['speed']}:{$params['r2q']}";
            }
            $value = trim($value);
            $file->replace_lines("/^$key.*$/", "$key=\"$value\"\n", 1);
        }
    }

    /**
     * Get priority class settings (as percentages).
     *
     * Returns an associative array containing the priority class settings for
     * all configured external interfaces.  The $type parameter is used to
     * select the type of configuration which can be either:
     *
     *   Qos::PRIORITY_CLASS_RESERVED   For priority class reservation values
     *   Qos::PRIORITY_CLASS_LIMIT      For priority class limit values
     *
     * If a non-empty associative array is returned, it will contain the
     * following fields:
     *
     *   interface  External interface name
     *   <array>    An array of Qos::PRIORITY_CLASSES percentages.
     * 
     * @param   int $type priority class configuration type
     * @return  array of priority class settings
     */

    public function get_priority_class_config($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        $pc_config = array();

        try {
            $directions = array('up', 'down');
            foreach ($directions as $direction) {
                $key = 'QOS_' . strtoupper($direction) . 'STREAM_';
                switch ($type) {
                case self::PRIORITY_CLASS_RESERVED:
                    $key .= 'BWRES';
                    break;

                case self::PRIORITY_CLASS_LIMIT:
                    $key .= 'BWLIMIT';
                    break;

                default:
                    return $pc_config;
                }
                $values = explode(' ', $this->_get_config_value($key));
                foreach ($values as $value) {
                    if (strlen(trim($value)) == 0) continue;
                    $config = explode(':', $value, self::PRIORITY_CLASSES + 1);
                    if (count($config) != self::PRIORITY_CLASSES + 1) continue;
                    for ($i = 1; $i < self::PRIORITY_CLASSES + 1; $i++)
                        $pc_config[$direction][$config[0]][] = $config[$i];
                }
            }
        }
        catch (File_No_Match_Exception $e) {
            return $pc_config;
        }

        return $pc_config;
    }

    /**
     * Set priority class parameters.
     *
     * @throws  Prioclass_Limit_Underflow_Exception
     *
     */

    public function set_priority_class_config($type, $ifn, $values_up, $values_down)
    {
        clearos_profile(__METHOD__, __LINE__);

        $pc_config = $this->get_priority_class_config($type);
        $pc_config['up'][$ifn] = $values_up;
        $pc_config['down'][$ifn] = $values_down;

        if ($type == self::PRIORITY_CLASS_LIMIT) {
            $pc_reserved = $this->get_priority_class_config(self::PRIORITY_CLASS_RESERVED);

            $directions = array('up', 'down');
            foreach ($directions as $direction) {
                $key = '*';
                if (array_key_exists($ifn, $pc_reserved[$direction])) $key = $ifn;
                else if (! array_key_exists($key, $pc_reserved[$direction])) continue;
                for ($prio = 0; $prio < self::PRIORITY_CLASSES; $prio++) {
                    if ($pc_config[$direction][$ifn][$prio] >= 
                        $pc_reserved[$direction][$key][$prio]) continue;
                    throw new Prioclass_Limit_Underflow_Exception($prio);
                }
            }
        }

        $this->_save_priority_class_config($type, $pc_config);
    }

    /**
     * Save priority class settings.
     *
     */

    private function _save_priority_class_config($type, $config)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);
        if (! $file->exists()) return;

        $directions = array('up', 'down');
        foreach ($directions as $direction) {
            $key = 'QOS_' . strtoupper($direction) . 'STREAM_';
            switch ($type) {
            case self::PRIORITY_CLASS_RESERVED:
                $key .= 'BWRES';
                break;

            case self::PRIORITY_CLASS_LIMIT:
                $key .= 'BWLIMIT';
                break;

            default:
                return;
            }
            $value = '';
            if (array_key_exists($direction, $config)) {
                ksort($config[$direction]);
                foreach ($config[$direction] as $ifn => $params) {
                    $value .= " $ifn:";
                    foreach ($params as $percent) $value .= "$percent:";
                    $value = rtrim($value, ':');
                }
            }
            $value = trim($value);
            $file->replace_lines("/^$key.*$/", "$key=\"$value\"\n", 1);
        }
    }

    /**
     * Delete priority class reserved bandwidth settings for an interface.
     *
     */

    function delete_priority_class($type, $ifn)
    {
        clearos_profile(__METHOD__, __LINE__);

        $pc_config = $this->get_priority_class_config($type);

        $directions = array('up', 'down');
        foreach ($directions as $direction) {
            if (array_key_exists($direction, $pc_config) &&
                array_key_exists($ifn, $pc_config[$direction]))
                unset($pc_config[$direction][$ifn]);
        }

        $this->_save_priority_class_config($type, $pc_config);
    }

    /**
     * Get all priority mark rules by type.
     *
     */

    public function get_priomark_rules($type = self::PRIOMARK_TYPE_ALL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $priomark_rules = array();
        $priomark_types = array();

        if ($type == self::PRIOMARK_TYPE_ALL) {
            $priomark_types = array(
                self::PRIOMARK_TYPE_IPV4, self::PRIOMARK_TYPE_IPV6,
                self::PRIOMARK_TYPE_IPV4_CUSTOM, self::PRIOMARK_TYPE_IPV6_CUSTOM,
            );
        }
        else $priomark_types = array($type);

        foreach ($priomark_types as $priomark_type) {
            $priomark_rules[$priomark_type] =
                $this->_get_priomark_rules($priomark_type);
        }

        return $priomark_rules;
    }

    /**
     * Get all priority mark rules by type.
     *
     */

    private function _get_priomark_rules($type)
    {
        clearos_profile(__METHOD__, __LINE__);

        $priomark_rules = array();
        $file = new File(self::FILE_CONFIG);

        try {
            $key = 'QOS_PRIOMARK';
            switch ($type) {
            case self::PRIOMARK_TYPE_IPV4:
                $key .= '4';
                break;

            case self::PRIOMARK_TYPE_IPV6:
                $key .= '6';
                break;

            case self::PRIOMARK_TYPE_IPV4_CUSTOM:
                $key .= '4_CUSTOM';
                break;

            case self::PRIOMARK_TYPE_IPV6_CUSTOM:
                $key .= '6_CUSTOM';
                break;

            default:
                return $priomark_rules;
            }

            $contents = $file->get_contents();

            if (preg_match("/$key=\"([^\"]*)\"/", $contents, $parts) &&
                strlen($parts[1])) {

                $delim = ' ';

                switch ($type) {
                case self::PRIOMARK_TYPE_IPV4:
                case self::PRIOMARK_TYPE_IPV6:
                    $rules = trim(str_replace(array("\n", "\\", "\t"), ' ', $parts[1]));
                    break;

                case self::PRIOMARK_TYPE_IPV4_CUSTOM:
                case self::PRIOMARK_TYPE_IPV6_CUSTOM:
                    $delim = "\n";
                    $rules = trim(str_replace(array("\\", "\t"), ' ', $parts[1]));
                    break;
                }

                while (strstr($rules, '  '))
                    $rules = str_replace('  ', ' ', $rules);

                if ( !strlen($rules)) return $priomark_rules;

                foreach (explode($delim, $rules) as $rule) {

                    $fields = array();
                    switch ($type) {
                    case self::PRIOMARK_TYPE_IPV4:
                    case self::PRIOMARK_TYPE_IPV6:
                        $config = explode('|', $rule, count($this->priomark_fields));
                        if (count($config) != count($this->priomark_fields)) continue;
                        $fields = $this->priomark_fields;
                        break;

                    case self::PRIOMARK_TYPE_IPV4_CUSTOM:
                    case self::PRIOMARK_TYPE_IPV6_CUSTOM:
                        $config = explode('|', $rule,
                            count($this->priomark_fields_custom));
                        if (count($config) != count($this->priomark_fields_custom))
                            continue;
                        $fields = $this->priomark_fields_custom;
                        break;
                    }

                    $priomark_rule = array();
                    foreach ($fields as $i => $key)
                        $priomark_rule[$key] = trim($config[$i]);
                    if (! count($priomark_rule)) continue;
                    $priomark_rules[$priomark_rule['nickname']] = $priomark_rule;
                }
            }
        }
        catch (File_No_Match_Exception $e) {
            return $priomark_rules;
        }

        return $priomark_rules;
    }

    /**
     * add a priority mark rule.
     *
     */

    public function add_priomark_rule($type,
        $nickname, $ifn, $direction, $priority,
        $protocol, $saddr, $sport, $daddr, $dport)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_add_priomark_rule($type,
            $nickname, $ifn, $direction, $priority, NULL,
            $protocol, $saddr, $sport, $daddr, $dport);
    }

    /**
     * add a custom priority mark rule.
     *
     */

    public function add_priomark_rule_custom($type,
        $nickname, $ifn, $direction, $priority, $params)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_add_priomark_rule($type,
            $nickname, $ifn, $direction, $priority, $params);
    }

    /**
     * add a priority mark rule.
     *
     */

    private function _add_priomark_rule(
        $type, $nickname, $ifn, $direction, $priority, $params,
        $protocol = NULL, $saddr = NULL, $sport = NULL, $daddr = NULL, $dport = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $priomark_rules = $this->get_priomark_rules();
        if (array_key_exists($nickname, $priomark_rules[$type])) return;

        $priomark_rule = array();
        switch ($type) {
        case self::PRIOMARK_TYPE_IPV4:
        case self::PRIOMARK_TYPE_IPV6:
            $priomark_rule = array(
                'nickname' => $nickname, 'interface' => $ifn,
                'enabled' => self::PRIOMARK_ENABLED, 'direction' => $direction,
                'priority' => $priority, 'protocol' => $protocol,
                'saddr' => $saddr, 'sport' => $sport,
                'daddr' => $daddr, 'dport' => $dport,
            );
            break;

        case self::PRIOMARK_TYPE_IPV4_CUSTOM:
        case self::PRIOMARK_TYPE_IPV6_CUSTOM:
            $priomark_rule = array(
                'nickname' => $nickname, 'interface' => $ifn,
                'enabled' => self::PRIOMARK_ENABLED, 'direction' => $direction,
                'priority' => $priority, 'params' => $params,
            );
            break;
        }

        $priomark_rules[$type][$nickname] = $priomark_rule;
        $this->_save_priomark_rules($type, $priomark_rules);
    }

    /**
     * Enable/disable a priority mark rule.
     *
     */

    public function enable_priomark_rule($type, $nickname, $direction,
        $enable = self::PRIOMARK_ENABLED)
    {
        clearos_profile(__METHOD__, __LINE__);

        $priomark_rules = $this->get_priomark_rules($type);
        if (! array_key_exists($nickname, $priomark_rules[$type])) return;
        if ($priomark_rules[$type][$nickname]['enabled'] == $enable) return;;
        $priomark_rules[$type][$nickname]['enabled'] = $enable;
        $this->_save_priomark_rules($type, $priomark_rules);
    }

    /**
     * Delete a priority mark rule.
     *
     */

    public function delete_priomark_rule($type, $nickname)
    {
        clearos_profile(__METHOD__, __LINE__);

        $priomark_rules = $this->get_priomark_rules($type);
        if (! array_key_exists($nickname, $priomark_rules[$type])) return;
        unset($priomark_rules[$type][$nickname]);
        $this->_save_priomark_rules($type, $priomark_rules);
    }

    /**
     * Save priority mark rules.
     *
     */

    private function _save_priomark_rules($type, $priomark_rules)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! array_key_exists($type, $priomark_rules)) return;
        ksort($priomark_rules[$type]);

        $key = NULL;

        switch ($type) {
        case self::PRIOMARK_TYPE_IPV4:
            $key .= 'QOS_PRIOMARK4';
            break;

        case self::PRIOMARK_TYPE_IPV6:
            $key .= 'QOS_PRIOMARK6';
            break;

        case self::PRIOMARK_TYPE_IPV4_CUSTOM:
            $key .= 'QOS_PRIOMARK4_CUSTOM';
            break;

        case self::PRIOMARK_TYPE_IPV6_CUSTOM:
            $key .= 'QOS_PRIOMARK6_CUSTOM';
            break;

        default:
            return;
        }

        $value = '';
        $fields = array();
        $rules = $priomark_rules[$type];
        
        foreach ($rules as $rule) {
            switch ($type) {
            case self::PRIOMARK_TYPE_IPV4:
            case self::PRIOMARK_TYPE_IPV6:
                $fields = $this->priomark_fields;
                break;

            case self::PRIOMARK_TYPE_IPV4_CUSTOM:
            case self::PRIOMARK_TYPE_IPV6_CUSTOM:
                $fields = $this->priomark_fields_custom;
                break;
            }

            $line = '';
            foreach ($fields as $field) $line .= "{$rule[$field]}|";
            $line = trim($line, '|');

            switch ($type) {
            case self::PRIOMARK_TYPE_IPV4:
            case self::PRIOMARK_TYPE_IPV6:
                $value .= "    $line \\\n";
                break;

            case self::PRIOMARK_TYPE_IPV4_CUSTOM:
            case self::PRIOMARK_TYPE_IPV6_CUSTOM:
                $value .= "    $line \n";
                break;
            }
        }

        try {
            $file = new File(self::FILE_CONFIG);

            $contents = preg_replace(
                "/$key=\"([^\"]*)\"/si", "$key=\"\\\n$value\"",
                $file->get_contents()
            );

            $temp = new File('qos', FALSE, TRUE);
            $temp->add_lines("$contents\n");

            $file->replace($temp->get_filename());
        }
        catch (Exception $e) {
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * validates an array of priority class reservation values.
     *
     * @param   array $values reservation values
     * @return  string which is empty if valid
     */

    public static function validate_reservation_values($values)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_array($values))
            return lang('qos_invalid_reservation_value');
        if (count($values) != self::PRIORITY_CLASSES)
            return lang('qos_invalid_reservation_value');
        $total = 0;
        foreach ($values as $value) $total += $value;
        if ($total != 100)
            return lang('qos_invalid_reservation_value');
        return '';
    }

    /**
     * validates an array of priority class bandwidth limits.
     *
     * @param   array $values bandwidth limits
     * @return  string which is empty if valid
     */

    public static function validate_bandwidth_limits($values)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_array($values))
            return lang('qos_invalid_limit_value');
        if (count($values) != self::PRIORITY_CLASSES)
            return lang('qos_invalid_limit_value');
        foreach ($values as $value) {
            if ($value < 1 || $value > 100)
                return lang('qos_invalid_limit_value');
        }
        return '';
    }

    public static function validate_nickname($nickname)
    {
        if (preg_match('/^[A-z0-9_]+$/', $nickname)) return '';
        return lang('qos_invalid_nickname');
    }

    public static function validate_address($address)
    {
        if ($address == '-' || inet_pton($address) !== FALSE) return '';
        return lang('qos_invalid_address');
    }

    public static function validate_port($port)
    {
        if ($port == '-' || preg_match('/^[0-9]+$/', $port)) return '';
        return lang('qos_invalid_port');
    }

    public static function validate_speed($speed)
    {
        if (preg_match('/^[0-9]+$/', $speed) && $speed > 0) return '';
        return lang('qos_invalid_speed');
    }

    public static function validate_r2q($r2q)
    {
        if (preg_match('/^[0-9]+$/', $r2q) && $r2q > 0) return '';
        return lang('qos_invalid_r2q');
    }
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
