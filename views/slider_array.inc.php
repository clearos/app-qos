<?php

///////////////////////////////////////////////////////////////////////////////
// Custom slider array widget
// TODO: This outputs HTML directly and should be moved in to the
// framework / theme engine.
///////////////////////////////////////////////////////////////////////////////

function form_slider_array($linked = TRUE)
{
    ob_flush();

    echo "<center><table><tr>\n";

    for ($i = 0; $i < 7; $i++) {
        $bucket = $i + 1;
    echo "
        <td>
        <center>
    ";
    if ($linked == FALSE) {
        echo "
        <div class='bucket_label'>$bucket</div>
        ";
    }
    else {
        echo "
        <label style='display: block' for='bucket{$i}_lock' />$bucket</label>
        <input type='checkbox' id='bucket{$i}_lock' />
        ";
    }
    echo "
        <div id='bucket$i' class='bucket'></div>
        <input type='text' id='bucket{$i}_amount' class='bucket_input' />
        </center>
        </td>\n";
    }

    echo '<td>';
    if ($linked == TRUE) {
        echo "<div class='bucket_button'>" .
            anchor_javascript('bucket_ramp', lang('qos_ramp'), 'high') . '</div>';
        echo "<div class='bucket_button'>" .
            anchor_javascript('bucket_equalize', lang('qos_equalize'), 'high') . '</div>';
    }
    echo "<div class='bucket_button'>" . anchor_javascript('bucket_reset', lang('qos_reset'), 'high') . '</div>';
    echo "</td></tr></table></center>\n";

    return form_banner(ob_get_clean());
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
