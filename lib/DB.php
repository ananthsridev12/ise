<?php
class DB {
    private static $pdo = null;

    public static function get() {
        if (self::$pdo) return self::$pdo;
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return self::$pdo;
    }

    public static function query($sql, $params = []) {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll($sql, $params = []) {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne($sql, $params = []) {
        $row = self::query($sql, $params)->fetch();
        return $row ?: null;
    }

    public static function insert($table, $data) {
        $cols = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        self::query("INSERT INTO `$table` ($cols) VALUES ($placeholders)", array_values($data));
        return (int) self::get()->lastInsertId();
    }

    public static function update($table, $data, $where, $whereParams = []) {
        $set = implode(',', array_map(function($k) { return "`$k`=?"; }, array_keys($data)));
        self::query("UPDATE `$table` SET $set WHERE $where", array_merge(array_values($data), $whereParams));
    }
}
