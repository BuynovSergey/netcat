<?php

class nc_Lang extends nc_System {

    protected $language_code_description;

    public function __construct() {
        // load parent constructor
        parent::__construct();

        $this->language_code_description = array(
            'af'  => 'Afrikaans',
            'sq'  => 'Albanian',
            'ar'  => 'Arabic',
            'hy'  => 'Armenian',
            'as'  => 'Assamese',
            'az'  => 'Azeri',
            'eu'  => 'Basque',
            'be'  => 'Belarusian',
            'bn'  => 'Bengali',
            'bg'  => 'Bulgarian',
            'ca'  => 'Catalan',
            'zh'  => 'Chinese',
            'hr'  => 'Croatian',
            'cs'  => 'Chech',
            'da'  => 'Danish',
            'div' => 'Divehi',
            'nl'  => 'Dutch',
            'en'  => 'English',
            'et'  => 'Estonian',
            'fo'  => 'Faeroese',
            'fa'  => 'Farsi',
            'fi'  => 'Finnish',
            'fr'  => 'French',
            'mk'  => 'FYRO Macedonian',
            'gd'  => 'Gaelic',
            'ka'  => 'Georgian',
            'de'  => 'German',
            'el'  => 'Greek',
            'gu'  => 'Gujarati',
            'he'  => 'Hebrew',
            'hi'  => 'Hindi',
            'hu'  => 'Hungarian',
            'is'  => 'Icelandic',
            'id'  => 'Indonesian',
            'it'  => 'Italian',
            'ja'  => 'Japanese',
            'kn'  => 'Kannada',
            'kk'  => 'Kazakh',
            'kok' => 'Konkani',
            'ko'  => 'Korean',
            'kz'  => 'Kyrgyz',
            'lv'  => 'Latvian',
            'lt'  => 'Lithuanian',
            'ms'  => 'Malay',
            'ml'  => 'Malayalam',
            'mt'  => 'Maltese',
            'mr'  => 'Marathi',
            'mn'  => 'Mongolian',
            'ne'  => 'Nepali',
            'no'  => 'Norwegian',
            'or'  => 'Oriya',
            'pl'  => 'Polish',
            'pt'  => 'Portuguese',
            'pa'  => 'Punjabi',
            'rm'  => 'Rhaeto-Romanic',
            'ro'  => 'Romanian',
            'ru'  => 'Russian',
            'sa'  => 'Sanskrit',
            'sr'  => 'Serbian',
            'sk'  => 'Slovak',
            'ls'  => 'Slovenian',
            'sb'  => 'Sorbian',
            'es'  => 'Spanish',
            'sx'  => 'Sutu',
            'sw'  => 'Swahili',
            'sv'  => 'Swedish',
            'syr' => 'Syriac',
            'ta'  => 'Tamil',
            'tt'  => 'Tatar',
            'te'  => 'Telugu',
            'th'  => 'Thai',
            'ts'  => 'Tsonga',
            'tn'  => 'Tswana',
            'tr'  => 'Turkish',
            'uk'  => 'Ukrainian',
            'ur'  => 'Urdu',
            'uz'  => 'Uzbek',
            'vi'  => 'Vietnamese',
            'xh'  => 'Xhosa',
            'yi'  => 'Yiddish',
            'zu'  => 'Zulu'
        );
    }

    public function get_all() {
        return $this->language_code_description;
    }

    public function full_from_acronym($lang) {
        if ($lang && array_key_exists($lang, $this->language_code_description)) {
            return $this->language_code_description[$lang];
        }

        return false;
    }

    public function acronym_from_full($lang) {
        foreach ($this->language_code_description as $key => $value) {
            if ($value === $lang) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Метод определение языка
     *
     * Порядок определения:
     * - из текущего сайта, если он задан
     * - по переменной NEW_AUTH_LANG , пришедший из post'a
     * - из глобальной переменной AUTH_LANG
     * - по переменной PHP_AUTH_LANG, взятой из cookies
     * - из сессии пользователя
     * - по параметру ADMIN_LANGUAGE из конфигурационного файла
     * - первый попавшийся язык из директории lang
     *
     * @global string $AUTH_LANG
     * @param int $get_acronym вернуть акроним
     *
     * @return string язык, например "Russian"
     * @throws Exception
     */
    public function detect_lang($get_acronym = 0) {
        global $AUTH_LANG;

        $nc_core = nc_Core::get_object();

        if ($nc_core->inside_admin != 1) {
            $lang = $this->full_from_acronym($nc_core->catalogue->get_current('Language'));
            //}
            if ($lang && $this->_check_lang($lang)) {
                return $get_acronym ? $this->acronym_from_full($lang) : $lang;
            }
        }

        $lang = $nc_core->input->fetch_get_post('NEW_AUTH_LANG');
        if ($lang && $this->_check_lang($lang)) {
            return $get_acronym ? $this->acronym_from_full($lang) : $lang;
        }

        $lang = $AUTH_LANG;
        if ($lang && $this->_check_lang($lang)) {
            return $get_acronym ? $this->acronym_from_full($lang) : $lang;
        }

        $lang = $nc_core->input->fetch_cookie('PHP_AUTH_LANG');
        if ($lang && $this->_check_lang($lang)) {
            return $get_acronym ? $this->acronym_from_full($lang) : $lang;
        }

        if (!empty($_SESSION['User']['PHP_AUTH_LANG'])) {
            $lang = $_SESSION['User']['PHP_AUTH_LANG'];
            if ($lang && $this->_check_lang($lang)) {
                return $get_acronym ? $this->acronym_from_full($lang) : $lang;
            }
        }

        $lang = $nc_core->ADMIN_LANGUAGE;
        if ($lang && $this->_check_lang($lang)) {
            return $get_acronym ? $this->acronym_from_full($lang) : $lang;
        }

        if ($lang_folder = @opendir($nc_core->ADMIN_FOLDER . 'lang/')) {
            while (($lang_file = readdir($lang_folder)) !== false) {
                if (substr($lang_file, -3, 3) === 'php') {
                    $lang = str_replace('.php', '', $lang_file);
                    if ($lang && $this->_check_lang($lang)) {
                        return $get_acronym ? $this->acronym_from_full($lang) : $lang;
                    }
                }
            }
        }
        throw new Exception('Unable to determine current localization');
    }

    /**
     * Проверка языка на корректность
     *
     * @param string $lang язык
     * @return boolean язык можно использовать или нет
     */
    private function _check_lang($lang) {
        $nc_core = nc_Core::get_object();
        if (!preg_match("/^\w+$/", $lang)) {
            return false;
        }

        return file_exists($nc_core->ADMIN_FOLDER . 'lang/' . $lang . '.php');
    }

    /**
     * @param $word
     * @return array
     */
    public function get_ru_count_forms($word) {
        $nc_core = nc_core::get_object();
        $result = null;

        $morphy = $this->get_morphy('ru_ru');
        if ($morphy) {
            $analyzed_word = $word;
            if (!$nc_core->NC_UNICODE) {
                $analyzed_word = $nc_core->utf8->win2utf($analyzed_word);
            }
            $analyzed_word = $nc_core->utf8->uppercase($analyzed_word);

            $paradigms = $morphy->findWord($analyzed_word);
            if ($paradigms) {
                foreach ($paradigms as $paradigm) {
                    /** @var phpMorphy_WordDescriptor $paradigm */
                    /** @var phpMorphy_WordForm $word_form */
                    foreach ($paradigm->getFoundWordForm() as $word_form) {
                        if ($word_form->getPartOfSpeech() === 'С' && !$word_form->hasGrammems('АББР')) { // существительное, не сокращение
                            $f1 = $morphy->castFormByGramInfo($word_form->getWord(), 'С', array('ЕД', 'ИМ'), true); // "1 рубль"
                            $f2 = $morphy->castFormByGramInfo($word_form->getWord(), 'С', array('ЕД', 'РД'), true); // "2 рубля"
                            $f5 = $morphy->castFormByGramInfo($word_form->getWord(), 'С', array('МН', 'РД'), true); // "5 рублей"
                            $result = array($f1[0], $f2[0], $f5[0]);
                            break;
                        }
                    }
                }
            }
        }

        if ($result) {
            $result = array_map(array($nc_core->utf8, 'lowercase'), $result);
            if (!$nc_core->NC_UNICODE) {
                $result = $nc_core->utf8->utf2win($result);
            }
        } else {
            $result = array($word, $word, $word);
        }

        return $result;
    }

    /**
     * Склонение слова по числу.
     * @param int $number число, для которого выводим склонение
     * @param array $word_forms формы слова: единственное число ("1 рубль"),
     *    двойственное число ("2 рубля"), множественное число ("5 рублей")
     * @return string
     */
    public function get_ru_numerical_inclination($number, array $word_forms) {
        $number = abs((int)$number) % 100;
        if ($number > 10 && $number < 20) {
            return nc_array_value($word_forms, 2, $word_forms[0]);
        }
        $number %= 10;
        if ($number > 1 && $number < 5) {
            return nc_array_value($word_forms, 1, $word_forms[0]);
        }
        if ($number === 1) {
            return $word_forms[0];
        }

        return nc_array_value($word_forms, 2, $word_forms[0]);
    }

    /**
     * Единственное или множественное число (английский).
     * @param $number
     * @param array $word_forms формы слова: единственное число ("1 cent"),
     *    двойственное число ("2 cents")
     * @return string
     */
    public function get_en_numerical_inclination($number, array $word_forms) {
        return ((int)$number === 1) ? $word_forms[0] : $word_forms[1];
    }

    /**
     * Множественное число слова в зависимости от текущего языка
     * @param $number
     * @param array $word_forms
     * @throws Exception
     */
    public function get_numerical_inclination($number, array $word_forms) {
        $language = $this->detect_lang(1);
        $method = "get_{$language}_numerical_inclination";
        if (!method_exists($this, $method)) {
            $method = 'get_ru_numerical_inclination';
        }

        return $this->$method($number, $word_forms);
    }

    /**
     * @param $language
     * @return null|phpMorphy
     */
    protected function get_morphy($language) {
        $morphy_folder = nc_module_folder('search') . 'lib/3rdparty/phpmorphy';

        try {
            if (file_exists("$morphy_folder/src/common.php")) {
                include_once "$morphy_folder/src/common.php";
                $dict_path = "$morphy_folder/dicts";
                $options = array('storage' => PHPMORPHY_STORAGE_FILE, 'predict_by_suffix' => false, 'predict_by_db' => false);
                return new phpMorphy($dict_path, $language, $options);
            }
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
        }

        return null;
    }
}