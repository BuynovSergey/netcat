<?php

class nc_tpl_color_generator {

    protected $lightness_modifiers = array(
        'darkest' => -0.3,
        'darker' => -0.2,
        'dark' => -0.1,
        'light' => 0.1,
        'lighter' => 0.2,
        'lightest' => 0.3,
    );

    public function generate_colors_modifications_css(array $colors) {
        $result = '';
        foreach ($colors as $css_variable_name => $hex) {
            if ($hex) {
                $result .= $this->generate_color_modifications_css($css_variable_name, $hex);
            }
        }
        return $result;
    }

    protected function generate_color_modifications_css($css_variable_name, $hex) {
        $colors = "$css_variable_name: $hex;\n";

        $hsl = $this->rgb2hsl(sscanf($hex, '#%02x%02x%02x'));
        foreach ($this->lightness_modifiers as $modifier_name => $modifier_value) {
            $modified_color = $this->hsl2rgb($this->modify_hsl_lightness($hsl, $modifier_value));
            $modified_color = "rgb(" . join(',', $modified_color) . ")";
            $colors .= "$css_variable_name-$modifier_name: $modified_color;\n";
        }

        return $colors;
    }

    public function generate_swapped_colors_css(array $colors) {
        $modifiers = array(
            '',
            '-darkest',
            '-darker',
            '-dark',
            '-light',
            '-lighter',
            '-lightest',
        );

        $css = array();

        foreach ($colors as $to => $from) {
            foreach ($modifiers as $modifier) {
                $css[] = "$to$modifier: var($from$modifier);";
            }
        }

        return implode("\n", $css);
    }

    protected function modify_hsl_lightness(array $hsl, $difference) {
        $hsl[2] = max(0, min(255, $hsl[2] + $difference));
        return $hsl;
    }

    protected function rgb2hsl($rgb) {
        list($r, $g, $b) = $rgb;
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);

        $l = ($max + $min) / 2;
        $d = $max - $min;
        $h = 0;

        if ($d == 0) {
            $h = $s = 0; // achromatic
        } else {
            $s = $d / (1 - abs(2 * $l - 1));

            switch ($max) {
                case $r:
                    $h = 60 * fmod((($g - $b) / $d), 6);
                    if ($b > $g) {
                        $h += 360;
                    }
                    break;
                case $g:
                    $h = 60 * (($b - $r) / $d + 2);
                    break;
                case $b:
                    $h = 60 * (($r - $g) / $d + 4);
                    break;
            }
        }

        return array(round($h, 2), round($s, 2), round($l, 2));
    }

    protected function hsl2rgb(array $hsl) {
        list ($h, $s, $l) = $hsl;
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod(($h / 60), 2) - 1));
        $m = $l - ($c / 2);

        if ($h < 60) {
            $r = $c;
            $g = $x;
            $b = 0;
        } else if ($h < 120) {
            $r = $x;
            $g = $c;
            $b = 0;
        } else if ($h < 180) {
            $r = 0;
            $g = $c;
            $b = $x;
        } else if ($h < 240) {
            $r = 0;
            $g = $x;
            $b = $c;
        } else if ($h < 300) {
            $r = $x;
            $g = 0;
            $b = $c;
        } else {
            $r = $c;
            $g = 0;
            $b = $x;
        }

        $r = min(255, ($r + $m) * 255);
        $g = min(255, ($g + $m) * 255);
        $b = min(255, ($b + $m) * 255);

        return array(floor($r), floor($g), floor($b));
    }

}