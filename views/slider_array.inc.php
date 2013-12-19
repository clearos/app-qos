<?php

///////////////////////////////////////////////////////////////////////////////
// Custom slider array widget
// TODO: This outputs HTML directly and should be moved in to the
// framework / theme engine.
///////////////////////////////////////////////////////////////////////////////

function form_slider_array($id, $title, $mode, $sliders, $defaults = array(), $units = '%')
{
    $widget = "<center><h1>$title</h1><table><tr><tr>\n";

    for ($i = 0; $i < $sliders; $i++) {
        $slider = $i + 1;
        $widget .= "<td><center>";
        if ($mode == 1)
            $widget .= "<div class='slider_label'>$slider</div>";
        else {
            $widget .= "<label style='display: block' for='${$id}{$i}_lock' />";
            $widget .= "$slider</label><input type='checkbox' id='{$id}{$i}_lock' />";
        }
    
        $widget .= "<div id='${id}$i' class='slider'>";
        $widget .= "</div><input type='text' name='{$id}{$i}_amount' id='{$id}{$i}_amount' class='slider_input' />$units";
        $widget .= "</center></td>\n";
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
    $widget .= "<script type='text/javascript'>create_slider_array('$id', $mode, $sliders);</script>\n";

    if (count($defaults) == $sliders) {
        $widget .= "<script type='text/javascript'>\n";
        for ($i = 0; $i < $sliders; $i++)
            $widget .= "set_default_value('$id', $i, $defaults[$i]);\n";
        $widget .= "reset('$id');\n";
        $widget .= "</script>\n";
    }

    return $widget;
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
