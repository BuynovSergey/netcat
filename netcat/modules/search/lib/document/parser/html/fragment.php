<?php

/* $Id: fragment.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 *
 */
class nc_search_document_parser_html_fragment {

    protected $dom;

    /**
     * @param string $content
     */
    public function __construct($content = "") {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        // «правильный» подход с «более корректным» XML, неймспейсами и блудницами не сработал
        // (корёжит кодировку при импорте узлов несмотря на explicit указание кодировки)
        $this->set_html($content);
    }

    /**
     * @param string $html
     */
    protected function set_html($html) {
        // Для корректного парсинга нужен заголовок с указанием кодировки:
        $meta = "<meta http-equiv='content-type' content='text/html; charset=utf-8' />";
        $html = preg_replace('/(<head\b[^>]*?>)/i', "$1$meta", $html, 1, $replace_count);
        if (!$replace_count) {
            $html = "<head>$meta</head><body>$html</body>";
        }
        $this->dom->loadHTML($html);
    }

    /**
     *
     * @param DOMNode $dest_node
     * @param DOMNodeList $imported_nodes
     */
    protected function append_nodes_to(DOMNode $dest_node, DOMNodeList $imported_nodes) {
        $dom = $this->dom;
        foreach ($imported_nodes as $node) {
            if ($node instanceof DOMAttr) {
                $dest_node->appendChild($dom->createTextNode($node->nodeValue."\n"));
            } else {
                $dest_node->appendChild($dom->importNode($node, true));
                $dest_node->appendChild($dom->createTextNode("\n")); // добавить WHITESPACE
            }
        }
    }

    /**
     *
     * @param self $fragment
     */
    public function append_fragment(self $fragment) {
        $this->append_nodes_to($this->dom->documentElement, $fragment->get_nodes());
    }

    /**
     *
     * @param DOMNodeList $nodes
     */
    public function append_nodes(DOMNodeList $nodes) {
        $this->append_nodes_to($this->dom->getElementsByTagName('body')->item(0), $nodes);
    }

    /**
     * Получить содержимое <body>
     * @return DOMNodeList
     */
    public function get_nodes() {
        return $this->dom->getElementsByTagName('body')->item(0)->childNodes;
    }

    /**
     *
     * @return string
     */
    public function get_html() {
        $html = $this->dom->saveHTML();
        return $html;
    }

    /**
     *
     * @return string
     */
    public function get_text() {
        $text = trim($this->dom->textContent);

        // убрать CR
        $text = strtr($text, "\r", "");
        // заменить последовательности из разных видов пробелов и символа табуляции на один обычный пробел
        $text = preg_replace('/[\t\p{Zs}]+/u', ' ', $text);
        // убрать пробелы в начале и конце строк
        $text = str_replace(array(" \n ", "\n ", " \n"), "\n", $text);
        // убрать лишние переводы строк
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return $text;
    }

    /**
     * @param DOMNodeList $nodes
     * @param boolean $remove
     * @param nc_search_document_parser_html_fragment|null $recipient
     * @return boolean success
     */
    protected function extract_nodes($nodes, self $recipient = null, $remove = false) {
        if (!($nodes instanceof DOMNodeList) || !$nodes->length) {
            return false;
        }
        if ($recipient) { $recipient->append_nodes($nodes); } // must be done before removing the nodes ↓
        if ($remove) {
            $nodes_to_remove = array(); // надо делать в два этапа, нельзя удалять внутри foreach
            foreach ($nodes as $node) {
                if ($node instanceof DOMElement) {
                    $nodes_to_remove[] = $node;
                }
                /* elseif ($node instanceof DOMAttr) { NOT IMPLEMENTED } */
            }
            foreach ($nodes_to_remove as $node) {
                $node->parentNode->removeChild($node);
            }
        }
        return true;
    }

    /**
     * @param string $xpath_query
     * @param nc_search_document_parser_html_fragment|null $recipient
     * @param boolean $remove
     * @return boolean success
     */
    public function extract_xpath($xpath_query, self $recipient = null, $remove = false) {
        return $this->extract_nodes($this->evaluate_xpath($xpath_query), $recipient, $remove);
    }

    /**
     * @param string $tag_name
     * @param nc_search_document_parser_html_fragment|null $recipient
     * @param boolean $remove
     * @return boolean success
     */
    public function extract_tag($tag_name, self $recipient = null, $remove = false) {
        return $this->extract_nodes($this->dom->getElementsByTagName($tag_name), $recipient, $remove);
    }

    /**
     * @param string $regexp
     * @param nc_search_document_parser_html_fragment|null $recipient
     * @param boolean $remove
     * @return boolean success
     */
    public function extract_regexp($regexp, self $recipient = null, $remove = false) {
        $html = $this->get_html();
        if (!preg_match_all($regexp . "u", $html, $matches)) {
            return false;
        }

        if ($recipient) {
            $recipient->append_fragment(new self(join("\n", $matches[1])));
        }
        if ($remove) {
            // it's easier to dismiss the old DOMDocument than to delete all nodes properly
            $this->set_html(str_replace($matches[0], "", $html));
        }
        return true;
    }

    /**
     *
     * @param string $query
     * @return DOMNodeList
     */
    public function evaluate_xpath($query) {
        $xpath = new DOMXPath($this->dom);
        return $xpath->evaluate($query);
    }

}
