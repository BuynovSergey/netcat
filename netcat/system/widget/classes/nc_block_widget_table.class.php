<?php



class nc_block_widget_table extends nc_db_table {

    protected $table = 'Block_Widget';

    // protected $primary_key = 'Block_Widget_ID';

    protected $fields = array(
        'Block_Widget_ID' => array('field' => 'hidden'),
        'Catalogue_ID'    => array('field' => 'hidden'),
        'Block_Key'       => array('field' => 'hidden'),
        'Priority'        => array('field' => 'hidden'),
        'Checked'         => array('field' => 'hidden'),
        'Widget_ID'       => array('field' => 'hidden'),
        'Widget_Class_ID' => array('field' => 'hidden'),
        'Widget_Settings' => array('field' => 'hidden'),
    );

    //-------------------------------------------------------------------------

    /**
     * @return $this
     */
    public function checked()
    {
        return $this->where('Checked', 1);
    }

    //-------------------------------------------------------------------------

    /**
     * @param $catalogue_id
     * @return $this
     */
    public function for_site($catalogue_id)
    {
        return $this->where('Catalogue_ID', (int)$catalogue_id);
    }

    //-------------------------------------------------------------------------

    /**
     * @param $block_key
     * @return $this
     */
    public function for_key($block_key)
    {
        return $this->where('Block_Key', $block_key);
    }

    //-------------------------------------------------------------------------

    /**
     * @return $this
     */
    public function order_by_priority()
    {
        return $this->order_by('Priority', 'DESC');
    }

    //-------------------------------------------------------------------------
}