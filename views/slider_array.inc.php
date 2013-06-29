<?php

///////////////////////////////////////////////////////////////////////////////
// Custom slider array widget
// TODO: This outputs HTML directly and should be moved in to the
// framework / theme engine.
///////////////////////////////////////////////////////////////////////////////

function form_slider_array($id, $title, $mode = 0, $sliders = 7, $defaults = array())
{
    $colspan = $sliders + 1;

    $widget = '';
    $widget .= "<center><table><tr><th colspan='$colspan'>$title</th></tr><tr>\n";

    for ($i = 0; $i < $sliders; $i++) {
        $slider = $i + 1;
    $widget .= "<td><center>";
    if ($mode == 1) {
        $widget .= "<div class='slider_label'>$slider</div>";
    }
    else {
        $widget .= "<label style='display: block' for='${$id}{$i}_lock' />$slider</label><input type='checkbox' id='{$id}{$i}_lock' />";
    }
    $widget .= "<div id='${id}$i' class='slider'></div><input type='text' id='${id}{$i}_amount' class='slider_input' /></center></td>\n";
    }

    $widget .= '<td>';
    switch ($mode) {
    case 0:
        $widget .= "<div class='slider_button'>" .
            anchor_javascript("${id}_ramp", lang('qos_ramp'), 'high') . '</div>';
        $widget .= "<div class='slider_button'>" .
            anchor_javascript("${id}_equalize", lang('qos_equalize'), 'high') . '</div>';
    default:
        $widget .= "<div class='slider_button'>" .
            anchor_javascript("${id}_reset", lang('qos_reset'), 'high') . '</div>';
    }
    $widget .= "</td></tr></table></center>\n";
    $widget .= "<script>create_slider_array('$id', $mode, $sliders);</script>\n";

    if (count($defaults) == $sliders) {
        $widget .= "<script>\n";
        for ($i = 0; $i < $sliders; $i++)
            $widget .= "set_default_value('$id', $i, $defaults[$i]);\n";
        $widget .= "reset('$id');\n";
        $widget .= "</script>\n";
    }

    return $widget;
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
