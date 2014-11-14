<?php

abstract class Aurmil_Form_Model_Abstract extends Centurion_Form_Model
{
    public function __construct($options, Centurion_Db_Table_Row_Abstract $instance = null)
    {
        // $_modelClassName

        $className = get_class($this);
        $module = substr($className, 0, strpos($className, '_'));

        $this->_modelClassName = $module . '_Model_DbTable_' . str_replace($module . '_Form_Model_', '', $className);

        // common

        $model  = $this->getModel();
        $pk     = $model->info(Zend_Db_Table::PRIMARY);
        $cols   = $model->info(Zend_Db_Table::COLS);
        $refMap = $model->info(Zend_Db_Table::REFERENCE_MAP);
        $depTab = $model->info(Centurion_Db_Table::MANY_DEPENDENT_TABLES);

        // $_exclude

        $this->_exclude[] = reset($pk);
        if ($model->isSortable()) {
            $this->_exclude[] = 'order';
        }
        if ($model->isPublishable() && in_array('published_at', $cols)) {
            $this->_exclude[] = 'published_at';
        }
        if (in_array('created_at', $cols)) {
            $this->_exclude[] = 'created_at';
        }
        if (in_array('updated_at', $cols)) {
            $this->_exclude[] = 'updated_at';
        }
        if (is_array($refMap) && !empty($refMap)) {
            foreach ($refMap as $as => $ref) {
                if ('Media_Model_DbTable_File' == $ref['refTableClass']) {
                    $this->_exclude = array_merge(
                        $this->_exclude,
                        (array) $ref['columns']
                    );
                }
            }
        }

        // $_elementLabels

        if ($model->isPublishable()) {
            $this->_elementLabels['is_published'] = $this->_translate('Is published?');
        }

        if (is_array($depTab) && !empty($depTab)) {
            foreach ($depTab as $as => $ref) {
                if ('Media_Model_DbTable_File' == $ref['refTableClass']) {
                    // only the key matters, the label is not used
                    $this->_elementLabels[$as] = $as;
                }
            }
        }

        // parent

        parent::__construct($options, $instance);
    }

    public function init()
    {
        // parent

        parent::init();

        // common

        $model  = $this->getModel();
        $refMap = $model->info(Zend_Db_Table::REFERENCE_MAP);
        $upload = false;

        // medias

        if (is_array($refMap) && !empty($refMap)) {
            foreach ($refMap as $as => $ref) {
                if ('Media_Model_DbTable_File' == $ref['refTableClass']) {
                    $upload = true;

                    // add media upload field
                    $this->addReferenceSubForm(
                        new Media_Form_Model_Admin_File(array('name' => $as)),
                        $as
                    );
                }
            }
        }

        if ($upload) {
            $this->setAttrib('enctype', self::ENCTYPE_MULTIPART);
        }
    }

    public function setInstance(Centurion_Db_Table_Row_Abstract $instance = null)
    {
        if (null !== $instance) {
            // common

            $model  = $this->getModel();
            $cols   = $model->info(Zend_Db_Table::COLS);

            // created_at

            if (in_array('created_at', $cols) && null !== $instance->created_at) {
                $this->addElement('info', 'created_at', array(
                    'label' => $this->_translate('Created at')
                ));
            }

            // updated_at

            if (in_array('updated_at', $cols) && null !== $instance->updated_at) {
                $this->addElement('info', 'updated_at', array(
                    'label' => $this->_translate('Updated at')
                ));
            }

            // published_at

            if ($model->isPublishable()
                && (1 == $instance->is_published)
                && in_array('published_at', $cols)
                && (null !== $instance->published_at)
            ) {
                $this->addElement('info', 'published_at', array(
                    'label' => $this->_translate('Published at')
                ));
            }
        }

        return parent::setInstance($instance);
    }

    protected function _buildOptions($table, $key, $nullable = false)
    {
        if ($this instanceof Translation_Traits_Form_Model_Interface
            && method_exists($table, 'buildTranslationOptions')
        ) {
            return $table->buildTranslationOptions($nullable);
        }

        return parent::_buildOptions($table, $key, $nullable);
    }
}
