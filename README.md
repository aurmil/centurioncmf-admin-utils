# Centurion CMF - Admin Utils library

## Overview
This library is an add-on to [Centurion CMF](https://github.com/centurion-project/Centurion).

It is focused on administration side and does basic things that Centurion should do but does not.

Its aim is to save you some time while making your application's back office.

### Avoid repetitive or unnecessary code
Centralizes code, automatically detects and defines stuff. So you do not have to worry about.

Eg:

* controller "preDispatch" method
* setting title and add button labels in list view
* attributes like $_formClassName, $_modelClassName or $_exclude

### Form view
Adds input file for fields refering to Centurion media table and for n-m relationships with this table (= media galleries).

Adds information block with creation / modification dates.

### Multilingual datas
To get a better overview of your datas, list view only displays original rows while translations remain hidden. In consequence, language column and filter are not displayed anymore.

In form view, when displaying a select field listing datas read from a multilingual table (for a foreing key field), only original rows are listed.

Added two usefull methods when listing datas in front office:

* Get a rowset of translation rows in a given language, from a given rowset of original rows.
* Merge these two kinds of rowsets: for each row, original row is considered as the basis and for each field (within a given list), if translation exists, it is merged into this base.

### Sortable datas
Introduces "order context" concept into Centurion. It means that sortable datas are ordered within a context which is defined by a set of fields (that can be empty). Eg: in a tree structure, an node is ordered in the context of its parent node.

When inserting a new row, automatically sets its rank (it is put at the end of its context).

When removing a row, automatically updates rank of rows that was within its context.

Fixes a bug that messed up ranks with mass delete (bash/delete action) on sortable datas.

### Publishable datas
In list view, adds a column with a flipswitch button to display the current publication status of each row and to allow to change this status. Also add two toolbar items (mass publish/unpublish actions) and a filter.

When editing a row, automatically sets publication date if needed.

In form view, adds an information block with publication date.

## Compatibility
Tested on Centurion CMF 0.4

## Notes
* Free and open source
* Code and DB must comply with some rules listed below

## Installation
Just download the "library" folder and paste it into the root directory of your Centurion application. It will be merged with the existing "library" folder.

Then, edit "application/configs/application.ini" file, find lines mentioning "autoloaderNamespaces" and, below them, add the following one:

```ini
autoloaderNamespaces[] = "Aurmil_"
```

## DB rules
DB tables must have a primary key, composed by only one single field.

DB fields must be named as follows:

* "title", "name" or "label" for main label field
* "created_at" and "updated_at" for creation/change dates (timestamps)
* "order" for sortable datas
* "is_published" and "published_at" (timestamp) for publishable datas

## Usage
### Table row class
Do not forget to:

* extend "Aurmil_Db_Table_Row_Abstract"
* implement "Translation_Traits_Model_DbTable_Row_Interface" for multilingual datas

No code is needed.

### Table class
This is the most important class so pay attention to it!

Do not forget to:

* extend "Aurmil_Db_Table_Abstract"
* implement "Translation_Traits_Model_DbTable_Interface" for multilingual datas
* define "$_orderContext" for sortable datas (no need to set "language_id" and "original_id" for multilingual datas)

```php
protected $_orderContext = array('type');
```

* define "$_defaultOrder" for non sortable datas, not required though
* define "$_referenceMap" or "$_manyDependentTables" when using medias

```php
protected $_referenceMap = array(
    'media' => array(
        'columns'       => 'media_id', // current table field name
        'refColumns'    => 'id',
        'refTableClass' => 'Media_Model_DbTable_File',
        'onDelete'      => self::SET_NULL,
        'onUpdate'      => self::CASCADE
    ),
);

protected $_manyDependentTables = array(
    'slideshow' => array( // relashionship name
        'refTableClass'     => 'Media_Model_DbTable_File',
        'intersectionTable' => 'Mymodule_Model_DbTable_Slideshow', // n-m table class name
        'columns'           => array(
            'local'         => 'mymodel_id', // current table primary key field name
            'foreign'       => 'media_id'
        )
    )
);
```

* define "$_meta" for interface labels

```php
protected $_meta = array(
    'verboseName'   => 'project',
    'verbosePlural' => 'projects'
);
```

* overload "getTranslationSpec()" to manage your specific fields (fields dedicated to sortable and publishable datas are already managed)

```php
public function getTranslationSpec()
{
    $spec = parent::getTranslationSpec();

    $spec[Translation_Traits_Model_DbTable::TRANSLATED_FIELDS] = array_merge(
        $spec[Translation_Traits_Model_DbTable::TRANSLATED_FIELDS],
        array('title', 'subtitle', 'description',
              'link_text', 'link_url', 'link_target')
    );

    $spec[Translation_Traits_Model_DbTable::SET_NULL_FIELDS] = array_merge(
        $spec[Translation_Traits_Model_DbTable::SET_NULL_FIELDS],
        array('type')
    );

    return $spec;
}
```

### Form class
Do not forget to:

* extend "Aurmil_Form_Model_Abstract"
* implement "Translation_Traits_Form_Model_Interface" for multilingual datas

You may specify fields labels in "__construct" method.

### Controller class
Do not forget to:

* extend "Aurmil_Controller_CRUD"
* implement "Translation_Traits_Controller_CRUD_Interface" for multilingual datas

You may specify list columns and filters in "init" method.

## Changelog
### 1.0
* Initial release
