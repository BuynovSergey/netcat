<?php

class nc_tpl_stylesheet {

    /** @var nc_tpl_stylesheet_rule[] */
    protected $rules = array();

    /**
     * @param $path
     * @return self
     */
    static public function from_file($path) {
        $parser = new nc_tpl_stylesheet_parser(file_get_contents($path));
        return $parser->parse();
    }

    /**
     * @param nc_tpl_stylesheet_rule $rule
     */
    public function add_rule(nc_tpl_stylesheet_rule $rule) {
        $this->rules[] = $rule;
    }

    /**
     * @param string $block_class
     * @param string $url_prefix
     * @return array
     */
    public function transform($block_class, $url_prefix) {
        $result = array(
            'declarations' => '',
            'container_breakpoints' => array(),
            'list_object_breakpoints' => array(),
        );
        foreach ($this->rules as $rule) {
            $rules = $rule->transform($block_class, $url_prefix);
            $result['declarations'] .= $rules['declarations'];
            $result['container_breakpoints'] += $rules['container_breakpoints'];
            $result['list_object_breakpoints'] += $rules['list_object_breakpoints'];
        }
        return $result;
    }

    public function __toString() {
        $result = '';
        foreach ($this->rules as $rule) {
            $result .= $rule->__toString();
        }
        return $result;
    }

}