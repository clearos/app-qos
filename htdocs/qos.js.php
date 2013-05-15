<?php
/**
 * Qos javascript helper.
 *
 * @category   ClearOS
 * @package    Qos
 * @subpackage Javascript
 * @author     Darryl Sokoloski <dsokoloski@clearfoundation.com>
 * @copyright  2013 Darryl Sokoloski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
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
var buckets = 7;
var last_bucket = 0;
var last_delta = 0;
var values = [15, 15, 14, 14, 14, 14, 14];
var locks = [false, false, false, false, false, false, false];

function init_state()
{
    last_delta = 0;
    last_bucket = 0;
    values = [15, 15, 14, 14, 14, 14, 14];
}

function update_widgets()
{
    for (i = 0; i < buckets; i++) {
        $('#bucket' + i).slider('value', values[i]);
        $('#bucket' + i + '_amount').val(values[i]);
    }
}

function distribute(bucket, delta)
{
    var b = -1;

    if (delta != 1 && delta != -1) return false;
    if (bucket < 0 || bucket >= buckets) return false;

    if (delta > 0) {
        if (values[bucket] == 100) return false;
        if (delta != last_delta) last_bucket = buckets - 1;
        for (i = 0; i < buckets; i++) {
            if (bucket != last_bucket &&
                values[last_bucket] > 1 &&
                locks[last_bucket] == false) {
                b = last_bucket;
                break;
            }

            if (--last_bucket < 0) last_bucket = buckets - 1;
        }

        if (b != -1)
            if (--last_bucket < 0) last_bucket = buckets - 1;
    }
    else if (delta < 0) {
        if (values[bucket] == 1) return false;
        if (delta != last_delta) last_bucket = 0;
        for (i = 0; i < buckets; i++) {
            if (bucket != last_bucket &&
                values[last_bucket] < 100 &&
                locks[last_bucket] == false) {
                b = last_bucket;
                break;
            }

            if (++last_bucket == buckets) last_bucket = 0;
        }

        if (b != -1)        
            if (++last_bucket == buckets) last_bucket = 0;
    }

    last_delta = delta;

    if (b == -1) {
        console.log("Search exhausted, can't solve.");
        return false;
    }

    values[bucket] += delta;
    values[b] += -delta;

    update_widgets();

    return true;
}

$(document).ready(function() {
	$("#bucket_reset").click(function() {
        init_state();
        //values = values_conf;
        update_widgets();
	});

	$("#bucket_equalize").click(function() {
        init_state();
        values = [15, 15, 14, 14, 14, 14, 14];
        update_widgets();
	});

	$("#bucket_ramp").click(function() {
        init_state();
        values = [50, 25, 12, 6, 4, 2, 1];
        update_widgets();
	});
});

$(function() {
    for (var i = 0; i < buckets; i++) {
        var bucket = '#bucket' + i;
        var amount = bucket + '_amount';
        var lock = bucket + '_lock';

        $(bucket).slider({
            orientation: 'vertical',
            range: 'min',
            min: 1,
            max: 100,
            value: values[i],
            slide: function(event, ui) {
                var bucket = 0;
                var delta = 0;
                var diff = 0;
                var rx = /^.*(\d+).*$/;
                bucket = this.id.replace(rx, "$1");
                if (ui.value > $('#' + this.id).slider('value'))
                    delta = 1;
                else
                    delta = -1;
                diff = ui.value - $('#' + this.id).slider('value');
                for (i = 0; i < Math.abs(diff); i++)
                    if (! distribute(bucket, delta)) break;
                return false;
            }
        });

        $(amount).val(
            $(bucket).slider('value')
        );
/*
        $(amount).keyup(function() {
            console.log($(this).val());
        });

        $(amount).keydown(function(event) {
            var isnumeric = /^\d+$/;
            if (event.which == 13) {
                event.preventDefault();
            }
            else if (!isnumeric.test($(this).val())) {
                console.log("Not numeric.");
                event.preventDefault();
                return false;
            }
        });
*/
        $(lock).click(function() {
            var lock_check = $(this);
            var rx = /^.*(\d+).*$/;
            var bucket = this.id.replace(rx, "$1");
            locks[bucket] = lock_check.is(':checked');
        });
    }
});

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4 syntax=javascript
