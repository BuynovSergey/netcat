<?php

/**
 * Класс для реализации поля типа "Цвет"
 */
class nc_a2f_field_color extends nc_a2f_field {

    protected $has_default = 1;
    protected $can_inherit_values = true;

    //  возможные значения (значение => описание)
    protected $values = array(
        'transparent' => '',
        '#ffffff' => '#ffffff',
        '#e5e5e5' => '#e5e5e5',
        '#cccccc' => '#cccccc',
        '#999999' => '#999999',
        '#666666' => '#666666',
        '#333333' => '#333333',
        '#616b7e' => '#616b7e',
        '#59090a' => '#59090a',
        '#15224d' => '#15224d',
        '#2872bf' => '#2872bf',
        '#f89515' => '#f89515',
        '#ec7669' => '#ec7669',
        '#774f8f' => '#774f8f',
        '#76872b' => '#76872b',
        '#e9004a' => '#e9004a',
        '#ff5633' => '#ff5633',
        '#fac437' => '#fac437',
        '#33bd4e' => '#33bd4e',
        '#3caff1' => '#3caff1',
        '#51eec3' => '#51eec3',
        '#f0e9da' => '#f0e9da',
        '#f0fcff' => '#f0fcff',
        '#f9fffd' => '#f9fffd',
        '#feeced' => '#feeced',
        '#fffbf5' => '#fffbf5',
        '#f2dffc' => '#f2dffc',
        '#f9ad81' => '#f9ad81',
        '#fff200' => '#fff200',
        '#c69c6e' => '#c69c6e',
        '#f49ac2' => '#f49ac2',
        '#3bb878' => '#3bb878',
    );

    /**
     * @param bool $html
     * @return string
     */
    public function render_value_field($html = true) {
        // текущее значение
        $current_value = $this->get_value_for_input();

        $ret = "<div id='nc-field-color-control-" . $this->name . "' class='nc-field-color-container'>";
        $ret .= "<a href='#' class='nc-field-color-box " . ($current_value == "transparent" ? "nc-field-color-transparent" : "") . "' style='" . ($current_value != "transparent" ? "background: {$current_value};" : "") . "'>";
        $ret .= "<input type='hidden' name='" . $this->get_field_name() . "' value='{$current_value}' />";
        $ret .= "</a>";
        $ret .= "<div class='nc-field-color-popup'>";

        foreach ((array)$this->values as $k => $v) {
            $ret .= "<a href='#' data-value='{$k}' class='" . ($k == $current_value ? "nc-field-color-selected" : "") . " " . ($k == "transparent" ? "nc-field-color-transparent" : "") . "' style='" . ($k != "transparent" ? "background: {$k};" : "") . "'><span>&#xe64c;</span></a>";
        }
        $ret .= "</div>";
        $ret .= "</div>";
        $ret .= "<script>
    (function($) {
        var colorFieldContainer = $('#nc-field-color-control-" . $this->name . "');
        colorFieldContainer.find('.nc-field-color-box').on('click', function (e) {
            e.preventDefault();
            $(this).parent().find('.nc-field-color-popup').fadeToggle();
        });
        
        colorFieldContainer.find('.nc-field-color-box INPUT').change(function() {
            var value = this.value;
            if (value == 'transparent') {
                colorFieldContainer.find('.nc-field-color-box').css({
                    background: ''
                }).addClass('nc-field-color-transparent');
            } else {
                colorFieldContainer.find('.nc-field-color-box').css({
                    background: value
                }).removeClass('nc-field-color-transparent');
            }            
        });
        
        colorFieldContainer.find('.nc-field-color-popup A').on('click', function (e) {
            e.preventDefault();
            var colorBlock = $(this);
            colorBlock.addClass('nc-field-color-selected').siblings().removeClass('nc-field-color-selected');
            var value = colorBlock.attr('data-value');
            colorFieldContainer.find('.nc-field-color-box INPUT').val(value).change();
            colorFieldContainer.find('.nc-field-color-popup').fadeToggle();
        });
    })(\$nc);
</script>";

        if ($html) {
            $ret = "<div class='ncf_value'>" . $ret . "</div>\n";
        }

        return $ret;
    }

    /**
     *
     */
    protected function get_displayed_default_value() {
        return $this->values[$this->default_value];
    }

}