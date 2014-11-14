<?php

abstract class Aurmil_Db_Table_Row_Abstract extends Centurion_Db_Table_Row
{
    public function __toString()
    {
        $cols = $this->getTable()->info(Zend_Db_Table::COLS);
        $fields = array('title', 'name', 'label');

        foreach ($fields as $field) {
            if (in_array($field, $cols)) {
                return $this->{$field};
            }
        }

        return parent::__toString();
    }

    protected function _insert()
    {
        if (($this->getTable()->isSortable() && !$this->order)
            && ((($this instanceof Translation_Traits_Model_DbTable_Row_Interface)
                && (null == $this->original_id))
                || !($this instanceof Translation_Traits_Model_DbTable_Row_Interface))
        ) {
            $pk = $this->getTable()->info(Zend_Db_Table::PRIMARY);
            $filter = array();

            if ($this instanceof Translation_Traits_Model_DbTable_Row_Interface) {
                $filter['original_id'] = new Zend_Db_Expr('original_id IS NULL');
            }

            $orderContext = $this->getTable()->getOrderContext();
            if (is_array($orderContext) && !empty($orderContext)) {
                foreach ($orderContext as $field) {
                    $filter[$field] = $this->{$field};
                }
            }

            // set item order
            $this->order = $this->getTable()->getCounts(reset($pk), $filter);
        }

        parent::_insert();
    }

    protected function _postDelete()
    {
        $table = $this->getTable();

        if ($table->isSortable()
            && (!($this instanceof Translation_Traits_Model_DbTable_Row_Interface)
                || ($this instanceof Translation_Traits_Model_DbTable_Row_Interface
                && null == $this->original_id))
        ) {
            $tableName = $table->info(Zend_Db_Table::NAME);
            $where = array($tableName . '.order > ' . $this->order);

            $orderContext = $this->getTable()->getOrderContext();
            if (is_array($orderContext) && !empty($orderContext)) {
                foreach ($orderContext as $field) {
                    $where[] = $table->getAdapter()->quoteInto("$field = ?",
                                                               $this->{$field});
                }
            }

            // re-order items that were after removed item
            $table->update(
                array('order' => new Zend_Db_Expr($tableName . '.order - 1')),
                $where
            );
        }

        parent::_postDelete();
    }

    protected function _preSave()
    {
        // if (row is published) and (row is new or (row is not new and was not previously published)) and there is a field called "published_at"
        if ((1 == $this->_data['is_published'])
            && ($this->isNew() || (!$this->isNew() && (0 == $this->_cleanData['is_published'])))
            && in_array('published_at', $this->getTable()->info(Zend_Db_Table::COLS))
        ) {
            // update published_at field
            $this->_data['published_at'] = Zend_Date::now()->toString(Centurion_Date::MYSQL_DATETIME);
            $this->_modifiedFields['published_at'] = true;
        }

        parent::_preSave();
    }
}
