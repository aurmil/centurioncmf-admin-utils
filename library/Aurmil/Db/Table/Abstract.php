<?php

abstract class Aurmil_Db_Table_Abstract extends Centurion_Db_Table
{
    protected $_defaultOrder = null;

    protected $_orderContext = array();

    public function isSortable()
    {
        return (in_array('order', $this->info(self::COLS)));
    }

    public function isPublishable()
    {
        return (in_array('is_published', $this->info(self::COLS)));
    }

    public function getDefaultOrder()
    {
        if ($this->isSortable()) {
            return 'order';
        } else {
            return $this->_defaultOrder;
        }
    }

    public function getOrderContext()
    {
        return $this->_orderContext;
    }

    public function buildTranslationOptions($nullable = false)
    {
        if ($this instanceof Translation_Traits_Model_DbTable_Interface) {
            $select = $this->select()
                ->where('original_id IS NULL')
                ->order($this->getDefaultOrder());
            $rowset = $this->fetchAll($select);

            // @see Centurion_Form_Model_Abstract::_buildOptions()
            $options = (true === $nullable) ? array(null => '') : array();
            foreach ($rowset as $related) {
                $options[$related->id] = (string) $related;
            }

            return $options;
        } else {
            throw new Exception(get_class($this) . ' does not implement translation trait');
        }
    }

    public function getTranslationSpec()
    {
        if ($this instanceof Translation_Traits_Model_DbTable_Interface) {
            $spec = array(
                Translation_Traits_Model_DbTable::TRANSLATED_FIELDS => array(
                    //Put here the columns of your table which need to be translated (set to null when you translate the row, you have to translate the value in the admin form)
                ),
                Translation_Traits_Model_DbTable::DUPLICATED_FIELDS => array(
                    //Put here the columns of your table which need to be duplicated (in this case the translated row uses the of the orignal column and you can change it in the admin form)
                ),
                Translation_Traits_Model_DbTable::SET_NULL_FIELDS => array(
                    //Put here te columns of your table which need to be set to null (in this case the translated row uses the value of the original column but you can change the value in the admin form)
                ),
            );

            if ($this->isPublishable()) {
                $spec[Translation_Traits_Model_DbTable::TRANSLATED_FIELDS][] = 'is_published';
                $spec[Translation_Traits_Model_DbTable::TRANSLATED_FIELDS][] = 'published_at';
            }

            if ($this->isSortable()) {
                $spec[Translation_Traits_Model_DbTable::SET_NULL_FIELDS][] = 'order';
            }

            return $spec;
        } else {
            throw new Exception(get_class($this) . ' does not implement translation trait');
        }
    }

    public function getTranslations($rowset, Translation_Model_DbTable_Row_Language $language)
    {
        if ($this instanceof Translation_Traits_Model_DbTable_Interface) {
            $pk = $this->info(Zend_Db_Table::PRIMARY);
            $pk = reset($pk);

            $ids = array();
            foreach ($rowset as $row) {
                $ids[] = $row->{$pk};
            }

            return $this->all(array(
                'language_id = ?' => $language->id,
                'original_id IN (' . implode(',', $ids) . ')',
            ));
        } else {
            throw new Exception(get_class($this) . ' does not implement translation trait');
        }
    }

    public function mergeTranslations($rowset, $rowsetTrad, array $fields)
    {
        if ($this instanceof Translation_Traits_Model_DbTable_Interface) {
            $pk = $this->info(Zend_Db_Table::PRIMARY);
            $pk = reset($pk);

            foreach ($rowset as $row) {
                foreach ($rowsetTrad as $rowTrad) {
                    if ($rowTrad->original_id == $row->{$pk}) {
                        foreach ($fields as $field) {
                            if ('' != $rowTrad->{$field}) {
                                $row->{$field} = $rowTrad->{$field};
                            }
                        }

                        break;
                    }
                }
            }

            return $rowset;
        } else {
            throw new Exception(get_class($this) . ' does not implement translation trait');
        }
    }
}
