<?php

/**
 * Область поиска, индексации
 *
 * Строка, описывающая область поиска или переиндексации:
 *  - URL (заканчивающийся на слэш) или subНОМЕР - все страницы, относящиеся к
 *    этому разделу, не включая подразделы
 *  - URL без указания имени сайта, заканчивающийся на слэш, при индексации будет
 *    применён ко всем сайтам
 *  - URL может содержать имя сайта, в этом случае должен быть указан и протокол,
 *    например "http://mysite.ru/news/"
 *  - однако имя сайта указывается всегда без "http://"
 *  - то же со звездочкой на конце - весь раздел и все подразделы
 *  - URL (любой) или номер с точкой на конце - только эта страница
 *  - имя домена или siteНОМЕР - весь сайт
 *  - минус перед любой из предыдущих конструкций - исключить из области эту подобласть
 * Области перечисляются через запятую или whitespace.
 */
class nc_search_area extends nc_search_data {

    protected $properties = array(
        'id' => null,
        'name' => null,
        'sites' => null, // self; нужно для проверки того, находится ли ссылка на сайтах, соответствующей данной области
        'area_string' => null,
        'https' => false,
        'rule_id' => null,
    );

    /* @var nc_search_area_part[] */
    protected $parts = array();

    /**
     * Свойства класса, которые сохраняются при сериализации
     * @var array
     */
    protected $serialized_object_properties = array('properties', 'parts');

    /**
     * @param string|int|array $area_string строка, описывающая область поиска
     *    ^\d$:  load *rule* with that ID from the database
     *     subX., subX, subX*, -subX etc; siteX etc; allsites
     *     /path, /path/*, /path., site.com;
     *   array of nc_search_area_part: set area parts
     * @param integer $site_id (для резолвинга неполных путей)
     * @throws nc_search_exception
     * @throws nc_record_exception
     */
    public function __construct($area_string = null, $site_id = null) {
        // Если на входе одно число, рассматривать его как ID *правила*
        if (is_int($area_string) || ctype_digit($area_string)) {
            $rule = new nc_search_rule;
            $rule->load((int)$area_string);

            $this->set('rule_id', $rule->get_id())
                 ->set('https', $rule->get_site_scheme() === 'https')
                 ->set_area($rule->get_area_string(), $rule->get('site_id'));
        }
        elseif (is_string($area_string)) {
            $this->set_area($area_string, $site_id);
        }
        elseif (is_array($area_string)) {
            $this->parts = $area_string;
        }
        else { // if ($area_string instanceof self) { // actually that's an error!
            // we could gracefully copy another area properties, but we won't!
            throw new nc_search_exception("Wrong \$area_string parameter");
        }
    }

    /**
     *
     * @param string $area_string строка, описывающая область поиска
     * @param integer $site_id ID сайта, для резолвинга неполных путей
     * @return nc_search_area
     * @throws nc_search_exception
     * @throws nc_record_exception
     */
    public function set_area($area_string, $site_id = null) {
        $this->set('area_string', $area_string);

        $site_areas = ($site_id
                            ? array(new nc_search_area_site(array(
                                  'ids'   => array($site_id),
                                  'https' => nc_Core::get_object()->catalogue->get_scheme_by_id($site_id) === 'https'
                              )))
                            : null);
        $ambiguous_subs = array();

        $parts = preg_split("/(?:\s*,?\s+|,)/u", trim($area_string));

        foreach ($parts as $part_string) {
            $part_object = $this->parse($part_string);
            $this->parts[] = $part_object;
            if ($part_object instanceof nc_search_area_sub && !$part_object->has_site()) {
                if ($site_areas) {
                    $part_object->set_sites($site_areas);
                } else {
                    $ambiguous_subs[] = $part_object;
                }
            }
        }

        // URLы для подразделов могут не содержать имени сайта. Необходимо установить
        // для каждого из подразделов сайт(ы), которые должны быть заданы на входе
        if ($ambiguous_subs) {
            $site_areas = array();
            foreach ($this->parts as $part) {
                if ($part instanceof nc_search_area_site) {
                    $site_areas[] = $part;
                }
            }
            foreach ($ambiguous_subs as $sub) {
                $sub->set_sites($site_areas);
            }
        }

        if (!$site_areas) {
            $site_areas = array(new nc_search_area_allsites(array('https' => $this->get('https'))));
        }

        $this->set('sites', new self($site_areas));

        return $this;
    }

    /**
     * Преобразовать текстовое описание области в объект nc_search_area_part
     * @param string $original_string
     * @return nc_search_area_part
     * @throws nc_record_exception
     */
    protected function parse($original_string) {
        $string = $original_string;
        $params = array('https' => $this->get('https'));

        // начинается на «-»?
        if ($string[0] === '-') { // исключить подобласть
            $params['is_excluded'] = true;
            $string = substr($string, 1); // убрать из строки
        }

        if (strtolower($string) === 'allsites') {
            return new nc_search_area_allsites($params); // ----- RETURN ------
        }

        // звездочка и точка на конце
        $last_char = substr($string, -1);
        if ($last_char === '*') { // включить всех потомков в область
            $params['include_descendants'] = true;
            $string = substr($string, 0, -1); // убрать из строки
        } elseif ($last_char === '.') {
            $params['include_children'] = false;
            $string = substr($string, 0, -1); // убрать из строки
        }

        // siteX, subX
        if (preg_match("/^(site|sub)(\d+)$/", $string, $matches)) {
            $params['ids'] = array($matches[2]);
            $class_name = "nc_search_area_$matches[1]";
            return new $class_name($params); // ----- RETURN ------
        }

        // осталась непонятная строка. это нам пытаются сказать куда пойти
        $params['url'] = $string;
        if (strpos($string, '/') !== false) { // путь: есть слэши
            if (strpos(strtolower($string), 'https://') === 0) {
                $params['https'] = true;
            }
            if (substr($string, -1) === '/') { // sub?
                return new nc_search_area_sub($params); // ----- RETURN ------
            } else { // assert that this is a page
                return new nc_search_area_page($params); // ----- RETURN ------
            }
        }

        // фигня какая-то, видимо имя домена
        return new nc_search_area_site($params);
    }

    /**
     *
     * @param string $url
     * @return boolean
     */
    public function includes($url) {
        $result = false;
        foreach ($this->parts as $part) {
            // предполагается, что части перечислены в иерархически правильном порядке
            if ($part->matches($url)) {
                $result = !$part->is_excluded();
            }
        }
        return $result;
    }

    /**
     * @return string
     */
    public function to_string() {
        $res = array();
        foreach ($this->parts as $part) {
            $res[] = $part->to_string();
        }
        return implode(' ', $res);
    }

    /**
     * 
     * @return string
     */
    public function __toString() {
        return (string) $this->to_string();
    }

    /**
     * Используется для получения списка полных URLов (используется индексатором если
     * не задан начальный URL)
     * @return array|string
     */
    public function get_urls() {
        if (!$this->parts) {
            return '';
        }
        $urls = array();
        foreach ($this->parts as $part) {
            if ($part->is_excluded()) {
                continue;
            }
            $urls = array_merge($urls, $part->get_urls());
        }
        return array_unique($urls);
    }

    /**
     * @throws nc_search_exception
     */
    protected function make_query_condition($part_method, $and_op = ' AND ', $or_op = ' OR ', $not_op = ' NOT ') {
        if (!count($this->parts)) {
            throw new nc_search_exception('Wrong area: no parts');
        }

        // Область siteX обрабатывается особо: она может использоваться для задания сайта,
        // к которым относятся пути в запросе (например: "site1 /forum/*").
        // В запросы "site_id" не добавляется (так как соединение частей производится через "OR")
        // кроме особых случаев:
        //  — все перечисленные области указывают на сайты ("site1", "site1 site2")
        //  — поиск по всему сайту с исключением области без указания дополнительных областей
        //    ("site1 -sub2*", "site1 -/news/* -/about/*", но не "site1 /about/* -/about/history/*")
        // Чтобы сайт правильно учитывался, siteX должен идти первым ("site1 /news/" означает
        // «раздел /news/ на сайте 1», а "/news/ site1" — «раздел /news/ на любых сайтах и весь сайт 1»).

        $chunks = array();
        $i = 0;
        foreach ($this->parts as $part) {
            if ($part instanceof nc_search_area_site) {
                $site = $part;
                $i++;
                $chunks[$i]['site'] = $site;
            }
            else if ($part->is_excluded()) {
                $chunks[$i]['excluded'][] = $part;
            } else {
                $chunks[$i]['included'][] = $part;
            }
        }

        $query = array();
        foreach ($chunks as $chunk) {
            $chunk_query = null;
            $has_site = !empty($chunk['site']);
            $has_included = !empty($chunk['included']);
            $has_excluded = !empty($chunk['excluded']);

            if ($has_site && !$has_included && !$has_excluded) {
                // только сайт без дополнительных областей: добавить его в запрос
                $chunk_query = $chunk['site']->$part_method();
            } else if ($has_included) {
                $chunk_query = $this->make_query_condition_part($chunk['included'], $part_method, $or_op);
            }

            if ($has_excluded) {
                if (strpos($chunk_query, $or_op) !== false) {
                    $chunk_query = "($chunk_query)";
                }

                $site_condition = '';
                if ($has_included) {
                    $chunk_query .= $and_op;
                } else if ($has_site) {
                    $site_condition = $chunk['site']->$part_method() . $and_op;
                }

                $excluded_condition = $this->make_query_condition_part($chunk['excluded'], $part_method, $and_op . $not_op);
                $excluded_condition = str_replace($or_op, ")$and_op$not_op(", $excluded_condition);

                $chunk_query .= $site_condition . $not_op . $excluded_condition;
            }

            if ($chunk_query) {
                if (count($chunks) > 1) {
                    $chunk_query = "($chunk_query)";
                }
                $query[] = $chunk_query;
            }
        }

        $query = join($or_op, $query);
        if (strpos($query, $or_op) !== false) {
            $query = "($query)";
        }

        return $query;
    }

    /**
     * @param array $parts
     * @param string $part_method
     * @param string $join_op
     * @return string
     */
    protected function make_query_condition_part(array $parts, $part_method, $join_op) {
        $query = array();
        foreach ($parts as $part) {
            $query[] = $part->$part_method();
        }
        $query = join($join_op, $query);
        return $query;
    }

    /**
     * @return string
     * @throws nc_search_exception
     */
    public function get_sql_condition() {
        return $this->make_query_condition('get_sql_condition');
    }

    /**
     * @param bool $boolean
     * @return string
     * @throws nc_search_exception
     */
    public function get_field_condition($boolean = false) {
        if ($boolean) {
            return $this->make_query_condition('get_field_condition');
        } else {
            return $this->make_query_condition('get_field_condition', ' +', ' OR ', ' -');
        }
    }

    /**
     * @param bool $excluded
     * @return array
     */
    public function get_description($excluded = false) {
        $result = array();
        foreach ($this->parts as $part) {
            if ($excluded && !$part->is_excluded()) {
                continue;
            }
            if (!$excluded && $part->is_excluded()) {
                continue;
            }
            $result[] = $part->get_description();
        }
        return $result;
    }

}
