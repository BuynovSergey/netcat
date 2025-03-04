<?php

/**
 * Класс, обеспечивающий базовую функциональность для работы с фрагментами страниц,
 * которые могут быть загружены отдельно от страниц (врезки, инфоблоки, области).
 *
 * Оборачивает содержимое фрагмента в комментарии, которые обозначают границы и
 * параметры фрагмента для скрипта в браузере (nc_partial_load.js).
 *
 * Не следует путать с template_partial (врезками макетов). Здесь под 'partial' без
 * уточнения 'template' понимается любое «частичное» содержимое (aka фрагмент).
 */
abstract class nc_partial {

    // ··· ↓↓↓ следующие свойства должны быть переопределены в классах-наследниках! ↓↓↓ ···
    /** @var int счётчик вложенных фрагментов с отложенной загрузкой (используется в ID комментария) */
    static protected $partial_last_sequence_number = 0;
    /** @var int счётчик вложенных вызовов (фрагмент внутри фрагмента) */
    static protected $partial_nesting_level = 0;
    /** @var string префикс комментария (должен быть определён в классе-наследнике */
    protected $partial_comment_id_prefix = '';
    // ··· ↑↑↑ (конец списка свойств, которые должны быть переопределены) ↑↑↑ ···

    /** @var array параметры фрагмента (передаются внутрь соответствующего шаблона) */
    protected $data = array();

    // --- параметры асинхронной загрузки ---

    /** @var bool фрагмент запрошен через partial.php */
    protected $is_async_partial_request = false;

    /** @var bool использовать отложенную загрузку */
    protected $defer = false;

    /** @var string что выводится вместо partial, когда $defer = true */
    protected $stub = '';

    /** @var bool хранение в браузере в localStorage */
    protected $store_in_browser = false;

    /** @var bool всегда перезагружать partial, даже если он есть в localStorage */
    protected $always_reload = false;

    /** @var bool добавлять разметку (комментарии) для асинхронной загрузки,
     *            даже если нет параметров defer и т. п. */
    protected $always_add_async_markup_when_allowed = false;


    /**
     * Включить необходимую разметку (комментарии) для асинхронной загрузки,
     * даже если настройками фрагмента загрузка не предусматривается
     * и не указаны параметры, определяющие необходимость такой разметки
     * (defer, always_reload и т. п.)
     *
     * Для врезки может не иметь эффекта, если в настройках врезки запрещена
     * асинхронная загрузка (из соображений безопасности — изначально у врезок
     * отсутствовала возможность асинхронной загрузки).
     *
     * @param bool $enable_async_loading
     * @return $this
     */
    public function enable_async_loading($enable_async_loading = true) {
        $this->always_add_async_markup_when_allowed = $enable_async_loading;
        return $this;
    }

    /**
     * Устанавливает (по умолчанию — включает) отложенную загрузку врезки
     *
     * @param bool $defer
     * @return $this
     */
    public function defer($defer = true) {
        $this->defer = (bool)$defer;
        return $this;
    }

    /**
     * Устанавливает «заглушку», когда включена отложенная загрузка
     *
     * @param string $stub
     * @return $this
     */
    public function set_stub($stub) {
        $this->stub = $stub;
        return $this;
    }

    /**
     * Синоним для метода set_stub()
     *
     * @param $stub
     * @return $this
     */
    public function stub($stub) {
        return $this->set_stub($stub);
    }

    /**
     * Включает (по умолчанию и когда аргумент равен true) или выключает (если аргумент равен false)
     * хранение результата, загруженного отдельно от страницы, в браузере в sessionStorage
     *
     * @param bool $store_in_browser
     * @return $this
     */
    public function store_in_browser($store_in_browser = true) {
        $this->store_in_browser = (bool)$store_in_browser;
        return $this;
    }

    /**
     * Переключает (по умолчанию включает) режим принудительного обновления partial
     * при отложенной загрузке после загрузки страницы
     *
     * @param bool $always_reload
     * @return $this
     */
    public function always_reload($always_reload = true) {
        $this->always_reload = (bool)$always_reload;
        return $this;
    }

    /**
     * Метод, формирующий контент фрагмента
     *
     * @return string
     */
    abstract public function get_content();

    /**
     * Разрешена ли асинхронная загрузка фрагмента (через /netcat/partial.php)?
     * (Может быть запрещена для врезок в их настройках.)
     *
     * @return boolean
     */
    public function is_async_loading_allowed() {
        return true;
    }

    /**
     * Загрузка произведена через partial.php?
     * Не является частью публичного API
     * @param $is_async_partial_request
     * @return $this
     */
    public function is_async_partial_request($is_async_partial_request) {
        $this->is_async_partial_request = $is_async_partial_request;
        return $this;
    }

    /**
     * Решает, надо ли добавить разметку (комментарии) для асинхронной загрузки
     * фрагмента
     *
     * @return bool
     */
    protected function should_add_async_markup() {
        // проверка запрета асинхронной загрузки (используется у врезок)
        if (!$this->is_async_loading_allowed()) {
            return false;
        }

        // при запросе через partial.php для первого уровня (но не для вложенных фрагментов)
        // разметка не добавляется, т. к. она уже есть на странице
        $is_nested = static::$partial_nesting_level > 1;
        if ($this->is_async_partial_request && !$is_nested) {
            return false;
        }

        // в режиме редактирования всегда используется (для обновления блоков)
        if (nc_core::get_object()->admin_mode) {
            return true;
        }

        // установлены свойства, указывающие на необходимость поддержки асинхронной загрузки?
        return $this->always_add_async_markup_when_allowed ||
               $this->defer ||
               $this->store_in_browser ||
               $this->always_reload;
    }


    /**
     * Возвращает параметр src для загрузки этого фрагмента через partial.php
     *
     * @return string
     */
    abstract protected function get_src();

    /**
     * @return string
     */
    public function make() {
        ++static::$partial_nesting_level;

        if ($this->defer) {
            $result = $this->stub;
        } else {
            // Содержимое фрагмента получаем до определения $this->should_add_async_markup():
            // это позволяет (например) шаблону компонента включить собственную асинхронную
            // загрузку из кода этого компонента
            $result = $this->get_content();
        }

        // оборачивание в HTML-комментарий для работы nc_partial_load()
        if ($this->should_add_async_markup()) {
            nc_core::get_object()->page->require_asset_once('nc_partial_load', array('embed' => true));
            $fragment_id =
                (static::$partial_nesting_level > 1 ? 'I' : '') .
                $this->partial_comment_id_prefix .
                (++static::$partial_last_sequence_number);
            $comment_data = array(
                'src' => (string)$this->get_src(), // JS полагается на то, что src — строка
                'data' => $this->data ?: new stdClass(),  // JS полагается на то, что data — всегда объект
                'defer' => $this->defer,
                'reload' => $this->always_reload,
                'store' => $this->store_in_browser,
            );
            if (!empty($this->template_id)) {
                $comment_data['template'] = $this->template_id;
            }
            $comment_json = strtr(nc_array_json($comment_data), array('<' => '\x3C', '>' => '\x3E'));
            $result = "<!-- nc_partial $fragment_id $comment_json -->" .
                      $result .
                      "<!-- /nc_partial $fragment_id -->";
        }

        --static::$partial_nesting_level;
        return $result;
    }

    /**
     * Синоним $this->make()
     * @return string
     */
    public function __toString() {
        // __toString() не может бросать исключений
        try {
            return (string)$this->make();
        } catch (Error $e) { // Throwable нет в PHP 5.x, делаем отдельные catch
            $this->trigger_uncaught_throwable_error($e);
        } catch (Exception $e) {
            $this->trigger_uncaught_throwable_error($e);
        }
        return '';
    }

    /**
     * Преобразует Exception или Error в E_USER_WARNING
     * @param Throwable $e
     */
    protected function trigger_uncaught_throwable_error($e) {
        $message = get_class($e) .
            ": {$e->getMessage()}, thrown from {$e->getFile()}:{$e->getLine()}\n" .
            $e->getTraceAsString();
        trigger_error($message, E_USER_WARNING);
    }

    // --- Работа со свойством $data (данные, передаваемые в шаблон фрагмента) ---

    /**
     * Переопределяет $this->data
     * @param array $data
     * @return $this
     */
    protected function set_data(array $data) {
        $this->data = $data;
        return $this;
    }

    // --- Методы, пришедшие из предыдущих версий nc_tpl_template_partial ---
    // (для однотипности API теперь работают для всех типов фрагментов)

    /**
     * Присвоение переменной шаблона
     * @param  string $key  Название переменной
     * @param  mixed $value Значение переменой
     * @return $this
     */
    public function with($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function value($name, $default = null) {
        return isset($this->data[$name]) ? $this->data[$name] : $default;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value) {
        $this->with($name, $value);
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name) {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

}