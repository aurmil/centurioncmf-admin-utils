<?php

class Aurmil_Controller_CRUD extends Centurion_Controller_CRUD
{
    protected $_publishable = false;

    public function preDispatch()
    {
        $this->_helper->authCheck();
        $this->_helper->aclCheck();

        $this->_helper->layout->setLayout('admin');

        parent::preDispatch();

        if ($this instanceof Translation_Traits_Controller_CRUD_Interface) {
            if (isset($this->_filters['language_id'])) {
                unset($this->_filters['language_id']);
            }
        }
    }

    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);

        if ($this instanceof Translation_Traits_Controller_CRUD_Interface) {
            if (isset($this->_displays['language__name'])) {
                unset($this->_displays['language__name']);
            }
        }
    }

    public function init()
    {
        // $_formClassName

        $className = get_class($this);
        $module = substr($className, 0, strpos($className, '_'));
        $this->_formClassName = $module . '_Form_Model_'
                              . str_replace(
                                    array($module . '_Admin', 'Controller'),
                                    array('', ''),
                                    $className
                                );

        $model = $this->_getModel();
        $this->_sortable        = $model->isSortable();
        $this->_publishable     = $model->isPublishable();
        $this->_defaultOrder    = $model->getDefaultOrder();

        $meta = $model->getMeta();
        $placeholder = $this->view->placeholder('headling_1_content')->getValue();
        if (isset($meta['verboseName']) && empty($placeholder)) {
            $this->view->placeholder('headling_1_content')->set($this->view->translate('Manage ' . $meta['verbosePlural']));
        }
        $placeholder = $this->view->placeholder('headling_1_add_button')->getValue();
        if (isset($meta['verbosePlural']) && empty($placeholder)) {
            $this->view->placeholder('headling_1_add_button')->set($this->view->translate($meta['verboseName']));
        }

        parent::init();

        if ($this->_publishable) {
            $this->_displays['is_published'] = array(
                'label' => $this->view->translate('Is published?'),
                'type'  => self::COL_TYPE_ONOFF,
            );

            $this->_filters['is_published'] = array(
                'label'     => $this->view->translate('Is published?'),
                'type'      => self::FILTER_TYPE_RADIO,
                'behavior'  => self::FILTER_BEHAVIOR_EXACT,
                'data'      => array(
                    null    => $this->view->translate('All'),
                    0       => $this->view->translate('No'),
                    1       => $this->view->translate('Yes'),
                ),
            );

            // add "publish" and "unpublish" mass actions buttons
            $this->_toolbarActions = array_merge(array(
                'publish'   => $this->view->translate('Publish'),
                'unpublish' => $this->view->translate('Unpublish'),
            ), $this->_toolbarActions);
        }
    }

    public function publishAction($rowset = null)
    {
        if (!$this->_publishable) {
            return;
        }

        if (null === $rowset) {
            return;
        }

        foreach ($rowset as $row) {
            $row->is_published = 1;
            $row->save();
        }

        $this->getHelper('redirector')->gotoRoute(array_merge(array(
            'controller' => $this->_request->getControllerName(),
            'module' => $this->_request->getModuleName(),
            'action' => 'index',
        ), $this->_extraParam), null, true);
    }

    public function unpublishAction($rowset = null)
    {
        if (!$this->_publishable) {
            return;
        }

        if (null === $rowset) {
            return;
        }

        foreach ($rowset as $row) {
            $row->is_published = 0;
            $row->save();
        }

        $this->getHelper('redirector')->gotoRoute(array_merge(array(
            'controller' => $this->_request->getControllerName(),
            'module' => $this->_request->getModuleName(),
            'action' => 'index',
        ), $this->_extraParam), null, true);
    }

    public function getSelectFiltred()
    {
        $select = parent::getSelectFiltred();

        if ($this instanceof Translation_Traits_Controller_CRUD_Interface) {
            $select->where($select->getTable()->info(Zend_Db_Table::NAME) . '.original_id IS NULL');
        }

        return $select;
    }

    public function deleteAction($rowset = null)
    {
        if (null === $rowset) {
            $id = array($this->_getParam('id', null));
            $rowset = $this->_getModel()->find($id);
        }

        if ($this->_useTicket && !$this->view->ticket()->isValid()) {
            $this->view->errors[] = $this->view->translate('Invalid ticket');

            return $this->_forward('index', null, null, array('errors' => array()));
        }

        if (!count($rowset)) {
            throw new Zend_Controller_Action_Exception(sprintf('Object with type %s and id(s) %s not found',
                                                               get_class($this->_getModel()),
                                                               implode(', ', $id)),
                                                       404);
        }

        foreach ($rowset as $key => $row) {
            if ($row->isReadOnly()) {
                $row->setReadOnly(false);
            }

            // row datas may have been modified by a previous row removal
            $row->refresh();

            $this->_preDelete($row);
            $row->delete();
            $this->_postDelete($row);
        }

        $this->_cleanCache();

        if ($this->_hasParam('_next', false)) {
            $url = urldecode($this->_getParam('_next', null));

            return $this->_response->setRedirect($url);
        }

        $this->getHelper('redirector')->gotoRoute(array_merge(array(
            'controller' => $this->_request->getControllerName(),
            'module'     => $this->_request->getModuleName(),
            'action'         => 'index',
        ), $this->_extraParam), null, true);
    }
}
