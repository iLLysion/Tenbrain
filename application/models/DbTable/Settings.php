<?php
/**
 * Settings
 */

class Application_Model_DbTable_Settings extends Zend_Db_Table_Abstract
{
    /**
     * The default table name 
     */
    protected $_name = 'settings';
    
	public function getSetting($key) {
        $select = $this->_db->select()->from($this->_name, 'value')->where("`key` = ?",$key);
        $result = $this->getAdapter()->fetchOne($select);
        if ($result) {
            return $result;
        } else {
            return null;
        }
    }
}
