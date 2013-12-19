<?php
/**
 * qos javascript helper.
 *
 * @category   apps
 * @package    qos
 * @subpackage javascript
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
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');

?>
var state = [];

function init_state(id, mode, sliders)
{
    state[id] = [];
    state[id]['last_delta'] = 0;
    state[id]['last_slider'] = 0;
    state[id]['mode'] = 0;
    state[id]['sliders'] = sliders;

    state[id]['values'] = [];
    state[id]['defaults'] = [];
    state[id]['locks'] = [];

    for (i = 0; i < sliders; i++) {
        state[id]['values'][i] = 0;
        state[id]['defaults'][i] = 0;
        state[id]['locks'][i] = false;
    }

    if (state[id]['mode'] == 0) {
        // TODO - Causing JS to bomb out.
        //equalize(id);
    } else {
        for (i = 0; i < sliders; i++) state[id]['values'][i] = 100;
    }
}

function update_sliders(id)
{
    for (i = 0; i < state[id]['sliders']; i++) {
        $('#' + id + '' + i).slider('value', state[id]['values'][i]);
        $('#' + id + '' + i + '_amount').val(state[id]['values'][i]);
        $('#' + id + '' + i + '_lock').attr('checked', state[id]['locks'][i]);
    }
}

function set_default_value(id, slider, value)
{
    state[id]['defaults'][slider] = value;
}

function reset(id)
{
    for (i = 0; i < state[id]['sliders']; i++) {
        if (state[id]['defaults'][i] == 0) continue;
        state[id]['values'][i] = state[id]['defaults'][i];
    }

    update_sliders(id);
}

function distribute(id, slider, delta)
{
    var b = -1;

    if (delta != 1 && delta != -1) return false;
    if (slider < 0 || slider >= state[id]['sliders']) return false;

    if (delta > 0) {
        if (state[id]['values'][slider] == 100) return false;
        if (delta != state[id]['last_delta'])
            state[id]['last_slider'] = state[id]['sliders'] - 1;
        for (i = 0; i < state[id]['sliders']; i++) {
            if (slider != state[id]['last_slider'] &&
                state[id]['values'][state[id]['last_slider']] > 1 &&
                state[id]['locks'][state[id]['last_slider']] == false) {
                b = state[id]['last_slider'];
                break;
            }

            if (--state[id]['last_slider'] < 0)
                state[id]['last_slider'] = state[id]['sliders'] - 1;
        }

        if (b != -1) {
            if (--state[id]['last_slider'] < 0)
                state[id]['last_slider'] = state[id]['sliders'] - 1;
        }
    }
    else if (delta < 0) {
        if (state[id]['values'][slider] == 1) return false;
        if (delta != state[id]['last_delta']) state[id]['last_slider'] = 0;
        for (i = 0; i < state[id]['sliders']; i++) {
            if (slider != state[id]['last_slider'] &&
                state[id]['values'][state[id]['last_slider']] < 100 &&
                state[id]['locks'][state[id]['last_slider']] == false) {
                b = state[id]['last_slider'];
                break;
            }

            if (++state[id]['last_slider'] == state[id]['sliders'])
                state[id]['last_slider'] = 0;
        }

        if (b != -1) {
            if (++state[id]['last_slider'] == state[id]['sliders'])
                state[id]['last_slider'] = 0;
        }
    }

    state[id]['last_delta'] = delta;

    if (b == -1) {
        console.log(id + ": Search exhausted, can't solve.");
        return false;
    }

    state[id]['values'][slider] += delta;
    state[id]['values'][b] += -delta;

    update_sliders(id);

    return true;
}

function equalize(id)
{
    var slider = 0;
    var total = 100;
    var locked = 0;

    for (var i = 0; i < state[id]['sliders']; i++) {
        if (state[id]['locks'][i]) {
            total -= state[id]['values'][i];
            locked++;
            continue;
        }
        state[id]['values'][i] = 0;
    }

    if (locked == state[id]['sliders']) return;

    for (var i = total; i > 0; ) {
        if (state[id]['locks'][slider] == false) {
            state[id]['values'][slider]++;
            i--;
        }
        if (++slider == state[id]['sliders']) slider = 0;
    }

    update_sliders(id);
}

function ramp(id)
{
    var slider = 0;
    var total = 100;
    var locked = 0;

    for (var i = 0; i < state[id]['sliders']; i++) {
        if (state[id]['locks'][i]) {
            total -= state[id]['values'][i];
            locked++;
            continue;
        }
        total -= 1;
        state[id]['values'][i] = 1;
    }

    if (locked == state[id]['sliders']) return;

    for (var i = total; i > 0; ) {
        if (state[id]['locks'][slider] == false) {
            var points = Math.abs(slider - state[id]['sliders']);
            if (points > i) points = i;
            state[id]['values'][slider] += points;
            i -= points;
        }
        if (++slider == state[id]['sliders']) slider = 0;
    }

    update_sliders(id);
}

function create_slider_array(id, mode, sliders)
{
    init_state(id, mode, sliders);

    for (var i = 0; i < sliders; i++) {
        var name = '#' + id + '' + i;
        var amount = name + '_amount';
        var lock = name + '_lock';

        switch (mode) {
        case 0:
            $(name).slider({
                orientation: 'vertical',
                range: 'min',
                min: 1,
                max: 100,
                value: state[id]['values'][i],
                slide: function(event, ui) {
                    var slider = 0;
                    var delta = 0;
                    var diff = 0;
                    var rx = /^.*(\d+).*$/;
                    slider = this.id.replace(rx, "$1");
                    if (ui.value > $('#' + this.id).slider('value'))
                        delta = 1;
                    else
                        delta = -1;
                    diff = ui.value - $('#' + this.id).slider('value');
                    for (i = 0; i < Math.abs(diff); i++)
                        if (! distribute(id, slider, delta)) break;
                    return false;
                }
            });

            $(lock).click(function() {
                var lock_check = $(this);
                var rx = /^.*(\d+).*$/;
                var slider = this.id.replace(rx, '$1');
                state[id]['locks'][slider] = lock_check.is(':checked');
            });

            break;

        case 1:
            $(name).slider({
                orientation: 'vertical',
                range: 'min',
                min: 1,
                max: 100,
                value: state[id]['values'][i],
                slide: function(event, ui) {
                    var slider = 0;
                    var rx = /^.*(\d+).*$/;
                    var slider = this.id.replace(rx, "$1");
                    state[id]['values'][slider] = ui.value;
                    update_sliders(id);
                    return false;
                }
            });
            break;
        }

        $(amount).val($(name).slider('value'));
    }

    switch (mode) {
    case 0:
        $('#' + id + '_equalize').click(function() {
            var rx_id = /^([A-z]+)_.*$/;
            var id = this.id.replace(rx_id, "$1");
            equalize(id);
        });

        $('#' + id + '_ramp').click(function() {
            var rx_id = /^([A-z]+)_.*$/;
            var id = this.id.replace(rx_id, "$1");
            ramp(id);
        });

    default:
        $('#' + id + '_reset').click(function() {
            var rx_id = /^([A-z]+)_.*$/;
            var id = this.id.replace(rx_id, "$1");
            reset(id);
        });
    }
}

$(document).ready(function() {
    if ($('#r2q_auto_up').is(':checked'))
        $("#r2q_up").attr("disabled", "disabled");
    else
        $("#r2q_up").removeAttr("disabled");

    if ($('#r2q_auto_down').is(':checked'))
        $("#r2q_down").attr("disabled", "disabled");
    else
        $("#r2q_down").removeAttr("disabled");

    $('#r2q_auto_up').change(function() {
        if ($(this).is(':checked'))
            $("#r2q_up").attr("disabled", "disabled");
        else
            $("#r2q_up").removeAttr("disabled");
    });

    $('#r2q_auto_down').change(function() {
        if ($(this).is(':checked'))
            $("#r2q_down").attr("disabled", "disabled");
        else
            $("#r2q_down").removeAttr("disabled");
    });
});

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4 syntax=javascript
