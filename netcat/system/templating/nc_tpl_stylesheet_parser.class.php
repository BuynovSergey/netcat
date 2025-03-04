<?php

/**
 * Класс для простого разбора CSS. Делит CSS на селекторы и объявления.
 * Используется для добавления префиксов к селекторам и обновления
 * путей в стилях компонентов
 */
class nc_tpl_stylesheet_parser {

    /** @var  string  исходная строка */
    protected $css_string;

    /** @var  int  позиция, на которой находится обработка исходной строки.
     *             Методы get_* сдвигают этот указатель.
     */
    protected $position;

    /** @var  int  длина исходной строки */
    protected $length;

    /**
     * @param $css_string
     */
    public function __construct($css_string) {
        if (substr($css_string, 0, 3) == "\xEF\xBB\xBF") {
            // UTF-8 BOM
            $css_string = substr($css_string, 3);
        }
        $this->css_string = trim($css_string);
        $this->position = 0;
        $this->length = strlen($this->css_string);
    }

    /**
     * Генерирует nc_tpl_stylesheet из исходной строки
     * @return nc_tpl_stylesheet
     */
    public function parse() {
        $parsed_stylesheet = new nc_tpl_stylesheet();
        while ($this->position < $this->length) {
            $char = $this->css_string[$this->position];
            if (trim($char) === '') {
                $this->position++;
                continue;
            }
            else if ($char == '/' && $this->get_comment() !== false) {
                continue; // comments are not added to the output
            }
            else if ($char == '@') {
                $rule = $this->get_at_rule();
                $parsed_stylesheet->add_rule($rule);
            }
            else {
                $rule = $this->get_rule();
                $parsed_stylesheet->add_rule($rule);
            }
        }
        return $parsed_stylesheet;
    }

    /**
     * Возвращает правило — селектор + объявления
     * @return nc_tpl_stylesheet_rule
     */
    protected function get_rule() {
        $selectors = $this->get_selectors();
        $declaration = $this->get_block();
        $rule = new nc_tpl_stylesheet_rule($selectors, $declaration);
        return $rule;
    }

    /**
     * Возвращает @-правило. Для @-правил со вложенными блоками (media queries)
     * declaration у правила будет тоже правилом.
     * @return nc_tpl_stylesheet_rule
     */
    protected function get_at_rule() {
        $directive = '';
        $block = '';

        while ($this->position < $this->length) {
            $char = $this->css_string[$this->position];

            if ($char == '"' || $char == "'") {
                $directive .= $this->get_quoted_string($char);
                continue;
            }
            else if ($char == '{') {
                if (preg_match('/^@(?:media|supports|document|nc-container|nc-list-object)\b/', $directive)) {
                    $block = $this->get_nested_block();
                }
                else {
                    $block = $this->get_block();
                }
                break;
            }
            else if ($char == '/' && $this->get_comment() !== false) {
                $this->position++;
                continue; // comments are not added to the output
            }
            else {
                $this->position++;
                $directive .= $char;
            }

            if ($char == ';') {
                break;
            }
        }

        return new nc_tpl_stylesheet_rule(array($directive), $block);
    }

    /**
     * Возвращает вложенный блок (используется в @-правилах — media queries)
     */
    protected function get_nested_block() {
        if ($this->css_string[$this->position] != '{') {
            return '';
        }
        $initial_position = $this->position;
        $this->position++;

        $block = new nc_tpl_stylesheet();
        while ($this->position < $this->length) {
            $char = $this->css_string[$this->position];

            if (trim($char) === '') {
                $this->position++;
                continue;
            }
            else if ($char == '/' && $this->get_comment() !== false) {
                continue; // comments are not added to the output
            }
            else if ($char == '}') {
                $this->position++;
                return $block;
            }
            else {
                $rule = $this->get_rule();
                $block->add_rule($rule);
            }
        }

        $this->position = $initial_position + 1;
        return '{';
    }

    /**
     * Возвращает массив с селекторами
     * @return array
     */
    protected function get_selectors() {
        $selectors = array('');
        $n = 0;
        while ($this->position < $this->length) {
            $char = $this->css_string[$this->position];

            if ($char == '{') {
                break;
            }

            if ($char == '"' || $char == "'") {
                $selectors[$n] .= $this->get_quoted_string($char);
                continue;
            }
            else if ($char == ',') {
                $this->position++;
                $n++;
                $selectors[$n] = '';
                continue; // do not add to $selectors, i.e. skip the ','
            }
            else if ($char == '/' && $this->get_comment() !== false) {
                continue; // comments are not added to the output
            }
            else {
                $selectors[$n] .= $char;
            }

            $this->position++;
        }

        $selectors = array_map('trim', $selectors);

        return $selectors;
    }

    /**
     * Возвращает блок объявлений (без {}).
     * @return string
     */
    protected function get_block() {
        if ($this->position >= $this->length || $this->css_string[$this->position] != '{') {
            return '';
        }

        $num_curly_braces = 0;
        $result = '';

        while ($this->position < $this->length) {
            $char = $this->css_string[$this->position];
            if ($char == '"' || $char == "'") {
                $result .= $this->get_quoted_string($char);
                continue;
            }
            else if ($char == '/' && $this->get_comment() !== false) {
                continue; // comments are not added to the output
            }
            else {
                $this->position++;

                if ($char == '{') {
                    // count curly braces to process non-modified nested rules correctly (e.g., @keyframes)
                    $num_curly_braces++;
                    if ($num_curly_braces == 1) {
                        continue; // first '{' is not added to the output
                    }
                }
                else if ($char == '}') {
                    $num_curly_braces--;
                    if (!$num_curly_braces) {
                        return $result; // all curly braces are closed, return block contents (without last '}')
                    }
                }

                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * Возвращает строку
     * @param string $quote
     * @return string
     */
    protected function get_quoted_string($quote) {
        $escaped = false;
        $result = $quote;
        $this->position++;
        $initial_position = $this->position;

        while ($this->position < $this->length) {
            $char = $this->css_string[$this->position];
            $this->position++;
            $result .= $char;
            if (!$escaped && $char == $quote) {
                return $result;
            }
            $escaped = ($char == '\\');
        }

        // wtf, no ending quote???
        $this->position = $initial_position;
        return $quote;
    }

    /**
     * Возвращает комментарий или false, если указатель не в начале комментария
     * @return bool|string
     */
    protected function get_comment() {
        if ($this->css_string[$this->position] == '/' && $this->position + 1 < $this->length && $this->css_string[$this->position + 1] == '*') {
            // ok, it is a comment
            $result = '';
            $star = false;
            while ($this->position < $this->length) {
                $char = $this->css_string[$this->position];
                $this->position++;
                $result .= $char;
                if ($star && $char == '/') {
                    return $result;
                }
                $star = ($char == '*');
            }
        }

        // not a comment!
        return false;
    }

}