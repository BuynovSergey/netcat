<?php

class nc_landing_preset_netcat_landing_goods_3_block_opinions extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_opinion';
    protected $default_component_template = 'slider_cards_on_background';
    protected $default_infoblock_keyword = 'opinions';
    protected $default_infoblock_name = 'Отзывы';

    protected $ignore_user_objects = true;
    protected $ignore_user_infoblock_settings = array('header', 'text');

    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        $avatars = array(
            $this->get_image_path('user_red.png'),
            $this->get_image_path('user_blue.png'),
            $this->get_image_path('user_yellow.png'),
        );

        $result = array(
            array(
                'Text' => 'Теперь нам нужны только ховерборды и странное чувство вкуса и готово. Верните мне мой 2015-й!',
                'AuthorName' => 'Кира Царёвa',
                'AuthorPosition' => 'Дизайнер одежды',
                'AuthorImage' => $this->get_image_path('avatar1.png'),
            ),
            array(
                'Text' => 'Да! Тирании длинных кусочков материи в моей обуви пришёл конец! Больше никаких усилий — хватит завязывать шнурки самостоятельно! Самозавязывающиеся шнурки — то, чего мы все так ждали!',
                'AuthorName' => 'Стас Хромцов',
                'AuthorPosition' => 'Специалист по новым медиа, блоггер',
                'AuthorImage' => $this->get_image_path('avatar2.png'),
            ),
            array(
                'Text' => 'О, эти кроссовки гораздо удобнее тех, что были на съёмках! Хорошо, что по контракту с Nike я получу две пары бесплатно.',
                'AuthorName' => 'Майкл Джей Фокс',
                'AuthorPosition' => 'Актёр и режиссёр',
                'AuthorImage' => $this->get_image_path('avatar3.png'),
            ),
        );

        if ($item && $item['Sub_Class_ID']) {
            $comments = nc_db()->get_results(
                "SELECT * 
                   FROM `Comments_Text`
                  WHERE `Sub_Class_ID` = $item[Sub_Class_ID]
                    AND `Message_ID` = $item[Message_ID]
                    AND `Parent_Comment_ID` = 0",
                ARRAY_A
            );

            if ($comments) {
                $nc_core = nc_core::get_object();
                $result = array();
                foreach ($comments as $comment) {
                    $avatar = $avatars[rand(0, count($avatars)-1)];
                    if ($comment['User_ID']) {
                        $user_avatar_file_info = $nc_core->file_info->get_file_info('User', $comment['User_ID'], 'ForumAvatar', false);
                        if ($user_avatar_file_info['url']) {
                            $avatar = $user_avatar_file_info['url'];
                        }
                    }

                    $result[] = array(
                        'Text' => $comment['Comment'],
                        'AuthorName' => $comment['Guest_Name'],
                        'AuthorPosition' => '',
                        'AuthorImage' => $avatar,
                    );
                }
            }
        }

        return $result;
    }


    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array(
            'show_header' => 0,
            'header' => 'Отзывы',
            'show_text' => 0,
            'text' => '',
            'background_color' => '#e5e5e5',
            'padding_top' => 45,
            'padding_bottom' => 135,
        );
    }
    
}