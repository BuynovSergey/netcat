<?php

/**
 * @method $this inside(string|nc_content_changer_infoblock_finder $container_keyword_or_finder)
 * @method $this find_inside_by_source_keyword(string|nc_content_changer_infoblock_finder $container_keyword_or_finder, string $target_source_keyword)
 */
class nc_content_changer_infoblock_finder extends nc_content_changer_finder {

    const TABLE_NAME = 'Sub_Class';
    const TABLE_PRIMARY_KEY = 'Sub_Class_ID';

    public function apply_find_inside_by_source_keyword(nc_db_table $table, $container_keyword_or_finder, $target_source_keyword) {
        $this->apply_inside($table, $container_keyword_or_finder);
        $table->where('Source_Keyword', $target_source_keyword);
    }

    public function apply_inside(nc_db_table $table, $container_keyword_or_finder) {
        if (is_string($container_keyword_or_finder)) {
            $finder = self::select()->where('EnglishName', $container_keyword_or_finder);
        } else if ($container_keyword_or_finder instanceof self) {
            $finder = $container_keyword_or_finder;
        } else {
            throw new UnexpectedValueException('Expected string or ' . __CLASS__ . ', got ' . gettype($container_keyword_or_finder));
        }

        $parent_infoblock_ids = array_values($finder->get_table()->get_list('Sub_Class_ID', 'Sub_Class_ID'));

        if (!$parent_infoblock_ids) {
            $table->where(new nc_db_expression('0'));
        } else {
            $table->where_in_id($this->_get_children_infoblock_ids($parent_infoblock_ids));
        }
    }

    protected function _get_children_infoblock_ids(array $parent_ids) {
        if (empty($parent_ids)) {
            return array();
        }

        $children = array_values(
            nc_db()->get_col(
                "SELECT `Sub_Class_ID`
                   FROM `Sub_Class`
                  WHERE `Parent_Sub_Class_ID` IN (" . implode(', ', $parent_ids) . ")"
            )
        );

        return array_merge($children, $this->_get_children_infoblock_ids($children));
    }

}
