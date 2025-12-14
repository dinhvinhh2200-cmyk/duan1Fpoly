<?php
/*
 * TABLE MODEL
 */
class TableModel {
    private $db;

    public function __construct() {
        $this->db = new Database(); 
    }

    public function getTables() {
        $sql = "SELECT * FROM tables ORDER BY is_deleted ASC, table_id ASC";
        $this->db->query($sql);
        return $this->db->resultSet();
    }
    public function getActiveTables() {
        $sql = "SELECT * FROM tables WHERE is_deleted = 0 ORDER BY table_id ASC";
        
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    // --- [MỚI] TỰ ĐỘNG THÊM BÀN ---
    public function addAutoTable() {
        // Bước 1: Tạo một dòng mới với tên tạm
        $this->db->query("INSERT INTO tables (table_name, status) VALUES ('Creating...', 'empty')");
        
        if ($this->db->execute()) {
            // Bước 2: Lấy ID vừa sinh ra
            $this->db->query("SELECT LAST_INSERT_ID() as id");
            $row = $this->db->single();
            $newId = $row->id;

            // Bước 3: Cập nhật tên bàn theo format "Bàn Số + ID"
            $newName = "Bàn Số " . $newId;
            $this->db->query("UPDATE tables SET table_name = :name WHERE table_id = :id");
            $this->db->bind(':name', $newName);
            $this->db->bind(':id', $newId);
            
            return $this->db->execute();
        }
        return false;
    }

    public function deleteTable($id) {
        $sql = "UPDATE tables SET is_deleted = 1, status = 'empty' WHERE table_id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function restoreTable($id) {
        $sql = "UPDATE tables SET is_deleted = 0, status = 'empty' WHERE table_id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getTableById($id) {
        $sql = "SELECT * FROM tables WHERE table_id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
}