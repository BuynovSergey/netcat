<?php

class nc_tpl_stylesheet_rule {

    /** @var array  */
    protected $selectors;
    /** @var  string|nc_tpl_stylesheet_rule */
    protected $declarations;

    /**
     * @param array $selectors
     * @param $declarations
     */
    public function __construct(array $selectors, $declarations) {
        $this->selectors = $selectors;
        $this->declarations = $declarations;
    }

    /**
     * @param $string
     * @return string
     */
    protected function normalize_whitespace($string) {
        return trim(preg_replace('/\s+/', ' ', $string));
    }

    /**
     * @param string $declaration
     * @param string $url_prefix
     * @return string
     */
    protected function add_url_prefix($declaration, $url_prefix) {
        if (!stripos($declaration, 'url')) {
            return $declaration;
        }

        $declaration = preg_replace(
            '#\burl\s*\(\s*([\'"]?)(?!data:)([^/\'"].+?)\1\s*\)#is',
            "url($1$url_prefix$2$1)",
            $declaration
        );

        return $declaration;
    }

    /**
     * @param string $block_class
     * @param string $url_prefix
     * @return array
     */
    public function transform($block_class, $url_prefix) {
        $selectors = array();
        $add_selectors_to_output = true;
        $container_breakpoints = array();
        $list_object_breakpoints = array();
        foreach ($this->selectors as $selector) {
            if ($selector[0] === '@') {
                if (strpos($selector, 'nc-container') === 1) {
                    $container_rule = $this->parse_container_rule($selector);
                    $block_class .= $container_rule['selector_refinement'];
                    $container_breakpoints += $container_rule['breakpoints'];
                    $add_selectors_to_output = false;
                } else if (strpos($selector, 'nc-list-object') === 1) {
                    $container_rule = $this->parse_container_rule($selector);
                    $block_class .= ' .tpl-block-list-objects > *' . $container_rule['selector_refinement'];
                    $list_object_breakpoints += $container_rule['breakpoints'];
                    $add_selectors_to_output = false;
                }
                $selectors[] = $this->add_url_prefix($selector, $url_prefix);
            } else if (strpos($selector, '&') !== false) {
                $selectors[] = str_replace('&', ".$block_class", $selector);
            } else {
                $selectors[] = ".$block_class $selector";
            }
        }
        $selectors = $this->normalize_whitespace(join(', ', $selectors));

        if ($this->declarations instanceof nc_tpl_stylesheet) {
            // Вложенные блоки @nc- некорректны, поэтому брейкпоинты нам не нужны:
            $declarations = nc_array_value($this->declarations->transform($block_class, $url_prefix), 'declarations');
            if ($add_selectors_to_output && $declarations) {
                $declarations = "$selectors {\n$declarations}\n";
            }
        } else {
            $declarations = $this->declarations;
            $declarations = $this->add_url_prefix($declarations, $url_prefix);
            $declarations = $this->normalize_whitespace($declarations);
            if ($declarations) {
                $declarations = "$selectors { $declarations }\n";
            }
        }

        return array(
            'declarations' => $declarations,
            'container_breakpoints' => $container_breakpoints,
            'list_object_breakpoints' => $list_object_breakpoints,
        );
    }

    /**
     * Разбор определения правила вида "@nc-container (min-width: 123px)"
     * @param string $rule
     * @return array
     */
    protected function parse_container_rule($rule) {
        $block_selector_refinement = '';
        $breakpoints = array();

        if (preg_match_all('/(min|max)-width\s*:\s*(\d*(?:\.\d+)?)px/', $rule, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $breakpoint = $match[2];
                if ($match[1] === 'min') {
                    $attribute = 'b1';
                } else {
                    if (strpos($breakpoint, '.9') !== false) {
                        // в nc_element_queries верхняя граница не включительно, а до указанного значения
                        $breakpoint = round($breakpoint);
                    }
                    $attribute = 'b2';
                }
                $block_selector_refinement .= "[data-nc-$attribute~=\"$breakpoint\"]";
                $breakpoints[(string)$breakpoint] = $breakpoint;
            }
        }

        return array('selector_refinement' => $block_selector_refinement, 'breakpoints' => $breakpoints);
    }

    /**
     * @return string
     */
    public function __toString() {
        $selectors = $this->normalize_whitespace(join(', ', $this->selectors));
        $declarations = $this->normalize_whitespace($this->declarations);
        return $declarations ? "$selectors { $declarations } " : '';
    }

}