<?php
/**
 * Based on the Porter stemmer implementation by Richard Heyes; (c) 2005, Richard Heyes
 * License and source: http://tartarus.org/martin/PorterStemmer/php.txt
 */
class nc_search_language_analyzer_stemmer_en extends nc_search_language_analyzer_stemmer {

    /**
     * @param string $term
     * @return string
     */
    public function stem($term) {
        if (strlen($term) <= 2) {
            return $term;
        }

        $term = self::step1ab($term);
        $term = self::step1c($term);
        $term = self::step2($term);
        $term = self::step3($term);
        $term = self::step4($term);
        $term = self::step5($term);

        return $term;
    }

    // -------------------------------------------------------------------------

    /**
     * Regex for matching a consonant
     * @var string
     */
    private static $regex_consonant = '(?:[BCDFGHJKLMNPQRSTVWXZ]|(?<=[AEIOU])Y|^Y)';


    /**
     * Regex for matching a vowel
     * @var string
     */
    private static $regex_vowel = '(?:[AEIOU]|(?<![AEIOU])Y)';


    /**
    * Step 1
    */
    private static function step1ab($word) {
        // Part a
        if (substr($word, -1) == 'S') {
               self::replace($word, 'SSES', 'SS')
            OR self::replace($word, 'IES', 'I')
            OR self::replace($word, 'SS', 'SS')
            OR self::replace($word, 'S', '');
        }

        // Part b
        if (substr($word, -2, 1) != 'E' OR !self::replace($word, 'EED', 'EE', 0)) { // First rule
            $v = self::$regex_vowel;

            // ing and ed
            if (preg_match("#$v+#", substr($word, 0, -3)) && self::replace($word, 'ING', '') OR
                preg_match("#$v+#", substr($word, 0, -2)) && self::replace($word, 'ED', '')) { // Note use of && and OR, for precedence reasons

                // If one of above two test successful
                if (    !self::replace($word, 'AT', 'ATE')
                    AND !self::replace($word, 'BL', 'BLE')
                    AND !self::replace($word, 'IZ', 'IZE')) {

                    // Double consonant ending
                    if (    self::doubleConsonant($word)
                        AND substr($word, -2) != 'LL'
                        AND substr($word, -2) != 'SS'
                        AND substr($word, -2) != 'ZZ') {

                        $word = substr($word, 0, -1);

                    } else if (self::m($word) == 1 AND self::cvc($word)) {
                        $word .= 'E';
                    }
                }
            }
        }

        return $word;
    }


    /**
     * Step 1c
     *
     * @param string $word Word to stem
     * @return string
     */
    private static function step1c($word)
    {
        $v = self::$regex_vowel;

        if (substr($word, -1) == 'Y' && preg_match("#$v+#", substr($word, 0, -1))) {
            self::replace($word, 'Y', 'I');
        }

        return $word;
    }


    /**
     * Step 2
     *
     * @param string $word Word to stem
     * @return string
     */
    private static function step2($word)
    {
        switch (substr($word, -2, 1)) {
            case 'A':
                   self::replace($word, 'ATIONAL', 'ATE', 0)
                OR self::replace($word, 'TIONAL', 'TION', 0);
                break;

            case 'C':
                   self::replace($word, 'ENCI', 'ENCE', 0)
                OR self::replace($word, 'ANCI', 'ANCE', 0);
                break;

            case 'E':
                self::replace($word, 'IZER', 'IZE', 0);
                break;

            case 'G':
                self::replace($word, 'LOGI', 'LOG', 0);
                break;

            case 'L':
                   self::replace($word, 'ENTLI', 'ENT', 0)
                OR self::replace($word, 'OUSLI', 'OUS', 0)
                OR self::replace($word, 'ALLI', 'AL', 0)
                OR self::replace($word, 'BLI', 'BLE', 0)
                OR self::replace($word, 'ELI', 'E', 0);
                break;

            case 'O':
                   self::replace($word, 'IZATION', 'IZE', 0)
                OR self::replace($word, 'ATION', 'ATE', 0)
                OR self::replace($word, 'ATOR', 'ATE', 0);
                break;

            case 'S':
                   self::replace($word, 'IVENESS', 'IVE', 0)
                OR self::replace($word, 'FULNESS', 'FUL', 0)
                OR self::replace($word, 'OUSNESS', 'OUS', 0)
                OR self::replace($word, 'ALISM', 'AL', 0);
                break;

            case 'T':
                   self::replace($word, 'BILITI', 'BLE', 0)
                OR self::replace($word, 'ALITI', 'AL', 0)
                OR self::replace($word, 'IVITI', 'IVE', 0);
                break;
        }

        return $word;
    }


    /**
     * Step 3
     *
     * @param string $word String to stem
     * @return string
     */
    private static function step3($word)
    {
        switch (substr($word, -2, 1)) {
            case 'A':
                self::replace($word, 'ICAL', 'IC', 0);
                break;

            case 'S':
                self::replace($word, 'NESS', '', 0);
                break;

            case 'T':
                   self::replace($word, 'ICATE', 'IC', 0)
                OR self::replace($word, 'ICITI', 'IC', 0);
                break;

            case 'U':
                self::replace($word, 'FUL', '', 0);
                break;

            case 'V':
                self::replace($word, 'ATIVE', '', 0);
                break;

            case 'Z':
                self::replace($word, 'ALIZE', 'AL', 0);
                break;
        }

        return $word;
    }


    /**
     * Step 4
     *
     * @param string $word Word to stem
     * @return string
     */
    private static function step4($word)
    {
        switch (substr($word, -2, 1)) {
            case 'A':
                self::replace($word, 'AL', '', 1);
                break;

            case 'C':
                   self::replace($word, 'ANCE', '', 1)
                OR self::replace($word, 'ENCE', '', 1);
                break;

            case 'E':
                self::replace($word, 'ER', '', 1);
                break;

            case 'I':
                self::replace($word, 'IC', '', 1);
                break;

            case 'L':
                   self::replace($word, 'ABLE', '', 1)
                OR self::replace($word, 'IBLE', '', 1);
                break;

            case 'N':
                   self::replace($word, 'ANT', '', 1)
                OR self::replace($word, 'EMENT', '', 1)
                OR self::replace($word, 'MENT', '', 1)
                OR self::replace($word, 'ENT', '', 1);
                break;

            case 'O':
                if (substr($word, -4) == 'TION' OR substr($word, -4) == 'SION') {
                   self::replace($word, 'ION', '', 1);
                } else {
                    self::replace($word, 'OU', '', 1);
                }
                break;

            case 'S':
                self::replace($word, 'ISM', '', 1);
                break;

            case 'T':
                   self::replace($word, 'ATE', '', 1)
                OR self::replace($word, 'ITI', '', 1);
                break;

            case 'U':
                self::replace($word, 'OUS', '', 1);
                break;

            case 'V':
                self::replace($word, 'IVE', '', 1);
                break;

            case 'Z':
                self::replace($word, 'IZE', '', 1);
                break;
        }

        return $word;
    }


    /**
     * Step 5
     *
     * @param string $word Word to stem
     * @return string
     */
    private static function step5($word)
    {
        // Part a
        if (substr($word, -1) == 'E') {
            if (self::m(substr($word, 0, -1)) > 1) {
                self::replace($word, 'E', '');

            } else if (self::m(substr($word, 0, -1)) == 1) {

                if (!self::cvc(substr($word, 0, -1))) {
                    self::replace($word, 'E', '');
                }
            }
        }

        // Part b
        if (self::m($word) > 1 AND self::doubleConsonant($word) AND substr($word, -1) == 'L') {
            $word = substr($word, 0, -1);
        }

        return $word;
    }


    /**
     * Replaces the first string with the second, at the end of the string. If third
     * arg is given, then the preceding string must match that m count at least.
     *
     * @param  string $str   String to check
     * @param  string $check Ending to check for
     * @param  string $repl  Replacement string
     * @param  int    $m     Optional minimum number of m() to meet
     * @return bool          Whether the $check string was at the end
     *                       of the $str string. True does not necessarily mean
     *                       that it was replaced.
     */
    private static function replace(&$str, $check, $repl, $m = null)
    {
        $len = 0 - strlen($check);

        if (substr($str, $len) == $check) {
            $substr = substr($str, 0, $len);
            if (is_null($m) OR self::m($substr) > $m) {
                $str = $substr . $repl;
            }

            return true;
        }

        return false;
    }


    /**
     * What, you mean it's not obvious from the name?
     *
     * m() measures the number of consonant sequences in $str. if c is
     * a consonant sequence and v a vowel sequence, and <..> indicates arbitrary
     * presence,
     *
     * <c><v>       gives 0
     * <c>vc<v>     gives 1
     * <c>vcvc<v>   gives 2
     * <c>vcvcvc<v> gives 3
     *
     * @param  string $str The string to return the m count for
     * @return int         The m count
     */
    private static function m($str)
    {
        $c = self::$regex_consonant;
        $v = self::$regex_vowel;

        $str = preg_replace("#^$c+#", '', $str);
        $str = preg_replace("#$v+$#", '', $str);

        preg_match_all("#($v+$c+)#", $str, $matches);

        return count($matches[1]);
    }


    /**
     * Returns true/false as to whether the given string contains two
     * of the same consonant next to each other at the end of the string.
     *
     * @param  string $str String to check
     * @return bool        Result
     */
    private static function doubleConsonant($str)
    {
        $c = self::$regex_consonant;
        return preg_match("#$c{2}$#", $str, $matches) AND $matches[0][0] == $matches[0][1];
    }


    /**
     * Checks for ending CVC sequence where second C is not W, X or Y
     *
     * @param  string $str String to check
     * @return bool        Result
     */
    private static function cvc($str)
    {
        $c = self::$regex_consonant;
        $v = self::$regex_vowel;

        return     preg_match("#($c$v$c)$#", $str, $matches)
               AND strlen($matches[1]) == 3
               AND $matches[1]{2} != 'W'
               AND $matches[1]{2} != 'X'
               AND $matches[1]{2} != 'Y';
    }
}