<?php

class phpMorphy_GrammemsProvider_ru_RU extends phpMorphy_GrammemsProvider_ForFactory {

    static protected $self_encoding = 'windows-1251';
    static protected $instances = array();
    static protected $grammems_map = array(
            '���' => array('��', '��', '��'),
            '��������������' => array('��', '��'),
            '�����' => array('��', '��'),
            '�����' => array('��', '��', '��', '��', '��', '��', '��', '2'),
            '�����' => array('���', '���'),
            '�����' => array('���', '���', '���'),
            '������������� �����' => array('���'),
            '����' => array('1�', '2�', '3�'),
            '���������' => array('��'),
            '������������� �����' => array('�����'),
            '������������ �������' => array('����'),
            '���' => array('��', '��'),
            '������������' => array('��', '��'),
            '��������� ������' => array('����'),
    );

    function getSelfEncoding() {
        return 'windows-1251';
    }

    function getGrammemsMap() {
        return self::$grammems_map;
    }

    static function instance(phpMorphy $morphy) {
        $key = $morphy->getEncoding();

        if (!isset(self::$instances[$key])) {
            $class = __CLASS__;
            self::$instances[$key] = new $class($key);
        }

        return self::$instances[$key];
    }

}
