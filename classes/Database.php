<?php

class Database {
    private static $conn = null;
    public static $echo_query = false;

    // Connessione al db
    private static function getConnection() {        
        if (self::$conn === null) {
            $host = $_ENV['DB_HOST'];
            $dbname = $_ENV['DB_NAME'];
            $username = $_ENV['DB_USER'];
            $password = $_ENV['DB_PASSWORD'];

            self::$conn = new mysqli($host, $username, $password, $dbname);

            if (self::$conn->connect_error) {
                die("Connessione fallita: " . self::$conn->connect_error);
            }

        }

        return self::$conn;
    }

    // Chiusura connessione al db
    private static function closeConnection() {
        if (self::$conn !== null) {
            self::$conn->close();
            self::$conn = null;
        }
    }

    // ****************** SELECT QUERY ****************** 
    public static function select(
        $table,
        $params = []
    ) {
        $conn = self::getConnection();

        $sql = sprintf("SELECT %s FROM %s ", $params["columns"] ?? '*', $table);

        if (!empty($params["joins"])) {
            foreach($params["joins"] as $join) {
                $sql .= sprintf(" %s JOIN %s", $join['type'], $join['sql']);
            }
        }
        
        if ($params['where'] ?? false) {
            $sql .= sprintf(" WHERE %s", $params['where']);
        }

        if (!empty($params["groupBy"])) {
            $sql .= sprintf(" GROUP BY %s", $params["groupBy"]);
        }

        if ($params['orderBy'] ?? false) {
            $sql .= sprintf(" ORDER BY %s %s", $params['orderBy'], $params["orderDirection"] ?? 'ASC');
        }


        if (($params["limit"] ?? false) != '-1') {
            $sql .= sprintf(" LIMIT %d", $params["limit"] ?? 10);
        }

        if (!empty($params["offset"])) {
            $sql .= sprintf(" OFFSET %d", $params["offset"]);
        }
        if (self::$echo_query)
            echo $sql;
        $stmt = $conn->prepare($sql);
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();
        return $rows;
    }

    // ******************  INSERT QUERY ******************
    public static function insert(
        $table,
        $dataArray = []
    ) {
        $conn = self::getConnection();

        if (!$dataArray) {
            return false;
        }
        $validKeys = array_keys($dataArray[0]);
        $values = [];
        foreach ($dataArray as $dataSet) {
            if (array_diff(array_keys($dataSet), $validKeys) || array_diff($validKeys, array_keys($dataSet))) {
                return false;
            }
            $values[] = implode(',', array_map(function ($item) {return "'" . addslashes($item) . "'";}, $dataSet));
            
        }

        $sql = sprintf("INSERT INTO %s (%s) VALUES %s", 
            $table, 
            implode(", ", array_keys($dataArray[0])),
            implode(',', array_map(function ($item) {return "($item)";}, $values))
        );        
        if (self::$echo_query)
            echo $sql;
        $result = $conn->query($sql);
        if ($result) {
            return $conn->affected_rows;
        } else {
            return false;
        }
    }

    // ****************** UPDATE QUERY ****************** 
    public static function update(
        $table,
        $data,
        $params
    ) {
        $conn = self::getConnection();

        if (!$params) {
            return false;
        }
        $values = implode(", ", 
            array_map(function ($item, $key) {
                if (is_numeric($item)) {
                    return "$key = $item"; 
                } else if ($item === NULL) {
                    return "$key = null"; 
                } else {
                    return "$key = '" .addslashes($item) . "'";} 
                }, $data, array_keys($data)));
        
        if (!$values) return false;

        
        $sql = sprintf("UPDATE %s SET %s %s",
            $table,
            $values,
            $params['where'] ? " WHERE " . $params['where'] : '');
           
        
        if (self::$echo_query)
            echo $sql;
        if ($conn->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    // ****************** DELETE QUERY ****************** 

    public static function delete(
        $table,
        $where
    ) {
        $conn = self::getConnection();

        if (!$where) {
            return false;
        }

        $sql = sprintf("DELETE FROM %s WHERE %s", $table, $where);
        
        if (self::$echo_query)
            echo $sql;
        if ($conn->query($sql)) {
            return true;
        } else {
            return false;
        }
    }
}
