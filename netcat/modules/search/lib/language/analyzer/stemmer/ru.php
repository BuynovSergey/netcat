<?php
/**
 *
 */
class nc_search_language_analyzer_stemmer_ru extends nc_search_language_analyzer_stemmer {

    // обработка терминов производится в UTF8, дополнительная конвертация не требуется
    const PERFECTIVEGROUND = '/((ИВ|ИВШИ|ИВШИСЬ|ЫВ|ЫВШИ|ЫВШИСЬ)|((?<=[АЯ])(В|ВШИ|ВШИСЬ)))$/u';
    const REFLEXIVE = '/(С[ЯЬ])$/u';
    const ADJECTIVE = '/(ЕЕ|ИЕ|ЫЕ|ОЕ|ИМИ|ЫМИ|ЕЙ|ИЙ|ЫЙ|ОЙ|ЕМ|ИМ|ЫМ|ОМ|ЕГО|ОГО|ЕМУ|ОМУ|ИХ|ЫХ|УЮ|ЮЮ|АЯ|ЯЯ|ОЮ|ЕЮ)$/u';
    const PARTICIPLE = '/((ИВШ|ЫВШ|УЮЩ)|((?<=[АЯ])(ЕМ|НН|ВШ|ЮЩ|Щ)))$/u';
    const VERB = '/((ИЛА|ЫЛА|ЕНА|ЕЙТЕ|УЙТЕ|ИТЕ|ИЛИ|ЫЛИ|ЕЙ|УЙ|ИЛ|ЫЛ|ИМ|ЫМ|ЕН|ИЛО|ЫЛО|ЕНО|ЯТ|УЕТ|УЮТ|ИТ|ЫТ|ЕНЫ|ИТЬ|ЫТЬ|ИШЬ|УЮ|Ю)|((?<=[АЯ])(ЛА|НА|ЕТЕ|ЙТЕ|ЛИ|Й|Л|ЕМ|Н|ЛО|НО|ЕТ|ЮТ|НЫ|ТЬ|ЕШЬ|ННО)))$/u';
    const NOUN = '/(А|ЕВ|ОВ|ИЕ|ЬЕ|Е|ИЯМИ|ЯМИ|АМИ|ЕИ|ИИ|И|ИЕЙ|ЕЙ|ОЙ|ИЙ|Й|ИЯМ|ЯМ|ИЕМ|ЕМ|АМ|ОМ|О|У|АХ|ИЯХ|ЯХ|Ы|Ь|ИЮ|ЬЮ|Ю|ИЯ|ЬЯ|Я)$/u';
    const RVRE = '/^(.*?[АЕИОУЫЭЮЯ])(.*)$/u';
    const DERIVATIONAL = '/[^АЕИОУЫЭЮЯ][АЕИОУЫЭЮЯ]+[^АЕИОУЫЭЮЯ]+[АЕИОУЫЭЮЯ].*(?<=О)СТЬ?$/u';

    /**
     * @param string $term
     * @return string term after the stemming
     */
    public function stem($term) {
        $matches = array();
        if (preg_match_all(self::RVRE, $term, $matches)) {
           $start = $matches[1][0];
           $rv = $matches[2][0];
        }

        if (empty($rv)) {
           return $term;
        }

        //Step 1
        if (preg_match(self::PERFECTIVEGROUND, $rv)) {
           $rv = preg_replace(self::PERFECTIVEGROUND, '', $rv);
        }
        else {
           $rv = preg_replace(self::REFLEXIVE, '', $rv);
           if (preg_match(self::ADJECTIVE, $rv)) {
              $rv = preg_replace(self::ADJECTIVE, '', $rv);
              $rv = preg_replace(self::PARTICIPLE, '', $rv);
           }
           else {
              if (!preg_match(self::VERB, $rv)) {
                 $rv = preg_replace(self::NOUN, '', $rv);
              }
              else {
                 $rv = preg_replace(self::VERB, '', $rv);
              }
           }
        }

        //Step 2
        $rv = preg_replace('/И$/u', '', $rv);

        //Step 3
        if (preg_match(self::DERIVATIONAL, $rv)) {
           $rv = preg_replace('/ОСТЬ?$/u', '', $rv);
        }

        //Step 4
        if (preg_match('/Ь$/u', $rv)) {
           $rv = preg_replace('/Ь$/u', '', $rv);
        }
        else {
           $rv = preg_replace('/ЕЙШЕ?/u', '', $rv);
           $rv = preg_replace('/НН$/u', 'Н', $rv);
        }

        return $start . $rv;
    }

}