<?php

class Database {
    private $pdo;
    private $dbPath;

    public function __construct($path) {
        $this->dbPath = $path;
        try {
            $this->pdo = new PDO("sqlite:" . $path);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }

    public function Execute($sql) {
        try {
            return $this->pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("Ошибка выполнения SQL: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }

    public function Fetch($sql) {
        try {
            $statement = $this->pdo->query($sql);
            return $statement ? $statement->fetch(PDO::FETCH_ASSOC) : false;
        } catch (PDOException $e) {
            error_log("Ошибка выполнения SQL и выборки: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }

    public function Create($table, $data) {
        if (empty($data)) {
            return false;
        }

        $fields = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Ошибка создания записи в таблице {$table}: " . $e->getMessage() . " SQL: " . $sql . " Data: " . print_r($data, true));
            return false;
        }
    }

    public function Read($table, $id) {
        $sql = "SELECT * FROM {$table} WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Ошибка чтения записи из таблицы {$table} с ID {$id}: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }

    public function Update($table, $id, $data) {
        if (empty($data)) {
            return false;
        }

        $setClauses = [];
        foreach (array_keys($data) as $field) {
            $setClauses[] = "{$field} = :{$field}";
        }
        $setClause = implode(", ", $setClauses);

        $sql = "UPDATE {$table} SET {$setClause} WHERE id = :id";
        $data['id'] = $id;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Ошибка обновления записи в таблице {$table} с ID {$id}: " . $e->getMessage() . " SQL: " . $sql . " Data: " . print_r($data, true));
            return false;
        }
    }

    public function Delete($table, $id) {
        $sql = "DELETE FROM {$table} WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Ошибка удаления записи из таблицы {$table} с ID {$id}: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }

    public function Count($table) {
        $sql = "SELECT COUNT(*) FROM {$table}";
        $result = $this->Fetch($sql);
        return $result ? intval($result['COUNT(*)']) : 0;
    }
}

?>