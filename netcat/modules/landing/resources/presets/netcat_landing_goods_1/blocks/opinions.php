<?php

class nc_landing_preset_netcat_landing_goods_1_block_opinions extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_opinion';
    protected $default_component_template = 'slider_one_card';
    protected $default_infoblock_keyword = 'opinions';
    protected $default_infoblock_name = 'Отзывы';

    protected $ignore_user_objects = true;
    protected $ignore_user_infoblock_settings = array('header', 'text');

    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        $avatars = array(
            $this->get_image_path('user_blue.png'),
            $this->get_image_path('user_yellow.png'),
            $this->get_image_path('user_red.png'),
        );

        $result = array(
            array(
                'Text' => 'Понятие стиля и вкуса весьма условно. Дело не в том, что у одних вкус хороший, а у других нет, а в том, что именно подходит каждому отдельному человеку.',
                'AuthorName' => 'Келли Хоппен',
                'AuthorPosition' => 'Дизайнер, декоратор',
                'AuthorImage' => $avatars[0],
            ),
            array(
                'Text' => 'Быстро, дёшево и хорошо — из этих трёх вещей нужно всегда выбирать две. Если быстро и дёшево, это никогда не будет хорошо. Если это дёшево и хорошо, никогда не получится быстро. А если это хорошо и быстро, никогда не выйдет дёшево. Из трёх всё равно придётся всегда выбирать две.',
                'AuthorName' => 'Том Уэйтс',
                'AuthorPosition' => 'Музыкант',
                'AuthorImage' => $avatars[1],
            ),
            array(
                'Text' => 'Каждый прилично сделанный объект дизайна — от дома до лампы, ложки или зубной щётки — это не просто вещь, но прежде всего физическое воплощение нашей энергии, доказательство магических возможностей человека по превращению материалов в вещи, которые несут пользу, ценность и красоту.',
                'AuthorName' => 'Кевин Макклауд',
                'AuthorPosition' => 'Дизайнер, телеведущий',
                'AuthorImage' => $avatars[2],
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
        $item = $landing_data['item'];

        if ($item) {
            $text = 'Людям нравится ' . $item['Name'];
        }
        else {
            $text = 'Отзывы о товарах и услугах';
        }

        return array(
            'show_header' => 1,
            'header' => 'Отзывы',
            'show_text' => 1,
            'text' => $text,
        );
    }
    
}