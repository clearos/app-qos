<?php

/**
 * Prioclass limit underflow exception class.
 *
 * @category   Apps
 * @package    QoS
 * @subpackage Exceptions
 * @author     Darryl Sokoloski <dsokoloski@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/base/
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

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Prioclass limit underflow exception class.
 *
 * @category   Apps
 * @package    QoS
 * @subpackage Exceptions
 * @author     Darryl Sokoloski <dsokoloski@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/base/
 */

class Prioclass_Limit_Underflow_Exception extends Engine_Exception
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    private $prioclass;

    /**
     * Prioclass_Invalid_Interface_Exception constructor.
     *
     * @param string $prioclass priority class
     * @param int    $code      error code
     */

    public function __construct($prioclass, $code = CLEAROS_ERROR)
    {
        $this->prioclass = $prioclass;
        parent::__construct(
            lang('qos_prioclass_limit_underflow') . ($prioclass + 1), $code);
    }

    /**
     * Return priority class.
     *
     * @returns int $prioclass priority class
     */

    public function getPrioclass()
    {
        return $this->prioclass;
    }
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
