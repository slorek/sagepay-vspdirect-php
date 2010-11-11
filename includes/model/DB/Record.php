<?php
class DB_Record {
  private $_data = array();
  private $_db = null;
  private $_new = false;
  
  function __construct($db = null, $data = null) {
    if (!empty($db)) {
      $this->_db = $db;
    }
    
    if (is_array($data)) {
      $this->_data = $data;

      foreach ($this->_data as $key => $value) {
        if (is_array($value)) {
          $this->_data = null;
          break;

        } else {
          $this->$key = $value;
        }
      }
    } else {
      $this->_new = true;

      if (!empty($this->_db)) {
        foreach ($this->_db->getFields() as $field) {
          if ($field != $this->_db->id) {
            $this->$field = null;
          }
        }
      } else {
        return false;
      }
    }

    if (empty($this->_data) || empty($this->_db)) {
      return false;

    } else {
      return $this;
    }
  }
  
  function save() {
    $save = array();

    foreach ($this->_db->getFields() as $field) {
      if (isset($this->$field) && $field != $this->_db->id) {
        $this->_data[$field] = $this->$field;
        $save[$field] = $this->$field;
      }
    }
    
    $id = $this->_db->id;

    if ($this->_new) {
      $r = $this->_db->create($save);
      $this->$id = $this->_db->lastId();
      $this->_new = false;
      return $r;

    } else {
      return $this->_db->update($save, $this->$id);
    }
  }
  
  function isNew() {
    return $this->_new;
  }

}
?>