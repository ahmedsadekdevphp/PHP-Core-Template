<?php

namespace Core;

use Core\Database;
use PDO;

class QueryBuilder
{

    private $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    /**
     * Retrieves a single record from the specified table based on given conditions.
     *
     * @param string $table The name of the database table to query.
     * @param array $conditions An associative array of column-value pairs for filtering results.
     *                          Each key-value pair represents a column name and its expected value.
     * @param array $columns The columns to select from the table (default is all columns '*').
     * @return array|null Returns an associative array of the fetched row, or null if no matching row is found.
     */
    public function find($table, $conditions, $columns = ['*'])
    {
        $sql = "SELECT " . implode(", ", $columns) . " FROM {$table}";
        $sql .= " WHERE ";
        $conditionClauses = [];
        foreach ($conditions as $column => $value) {
            $conditionClauses[] = "{$column} = :{$column}";
        }
        $sql .= implode(" AND ", $conditionClauses);

        $sql .= " LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        foreach ($conditions as $column => $value) {
            $stmt->bindValue(":{$column}", $value);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    /**
     * Searches for records in a specified table where a given column matches a specified value pattern.
     *
     * @param string $columns The columns to select in the query (e.g., '*' or 'id, name').
     * @param string $tableName The name of the database table to search in.
     * @param string $column The column to apply the search criteria on.
     * @param string $value The value pattern to search for within the specified column.
     *                      A partial match is performed using SQL `LIKE` with wildcards.
     * @return array Returns an array of associative arrays representing the matching records.
     */
    public function searchByKey($columns, $tableName, $column, $value)
    {
        $query = "SELECT $columns FROM $tableName WHERE $column LIKE :value";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':value', '%' . $value . '%', PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Deletes a record from a specified table based on a given ID.
     *
     * @param string $tableName The name of the database table to delete the record from.
     * @param int $id The ID of the record to delete.
     * @return bool Returns true if the record was successfully deleted, or false if the deletion failed.
     */
    public function deleteRecord($tableName, $id)
    {
        $query = "DELETE FROM " . $tableName . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }


    /**
     * Inserts a new record into a specified table with provided data.
     *
     * @param string $table The name of the database table to insert the record into.
     * @param array $data An associative array of column-value pairs to insert.
     * The keys represent column names, and values represent the corresponding values to insert.
     * @return bool Returns true if the record was successfully inserted, or false if the insertion failed.
     */
    public function insert($table, $data)
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));

        $query = "INSERT INTO " . $table . " ($columns) VALUES ($placeholders)";
        $stmt = $this->conn->prepare($query);
        foreach ($data as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":$key", $value, $paramType);
        }
        return $stmt->execute();
    }

    /**
     * Retrieves all records from a specified table, with optional filtering and column selection.
     *
     * @param string $tableName The name of the database table to retrieve records from.
     * @param string|array $columns The columns to select in the query (default is '*', selecting all columns).
     * @param array|null $conditions Optional associative array of column-value pairs for filtering results.
     *  Each key-value pair represents a column and the value to match.
     * @return array Returns an array of associative arrays representing the fetched records.
     */
    public function getAll($tableName, $columns = '*', $conditions = null)
    {
        $query = "SELECT $columns FROM $tableName";
        $whereClause = '';
        if ($conditions) {
            $whereClause = ' WHERE ';
            $conditionParts = [];
            foreach ($conditions as $column => $value) {
                $conditionParts[] = "$column = :where_$column";
            }
            $whereClause .= implode(' AND ', $conditionParts);
        }

        $query .= $whereClause;
        $stmt = $this->conn->prepare($query);
        if ($conditions) {
            foreach ($conditions as $column => $value) {
                $stmt->bindValue(":where_$column", $value, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Updates specified fields in a table for records that match optional conditions.
     *
     * @param string $tableName The name of the database table to update.
     * @param array $fields An associative array of columns and their new values for the update.
     *                      Each key represents a column name, and the corresponding value is the new value to set.
     * @param array|null $conditions Optional associative array of column-value pairs for filtering records to update.
     *                               Each key-value pair represents a column and the value to match for the condition.
     * @return bool Returns true if the update was successful, or false if it failed.
     */
    public function updateFields($tableName, $fields, $conditions = null)
    {
        $setClause = [];
        foreach ($fields as $column => $value) {
            $setClause[] = "$column = :$column";
        }
        $setClause = implode(', ', $setClause);

        $whereClause = '';
        if ($conditions) {
            $whereClause = ' WHERE ';
            $conditionParts = [];
            foreach ($conditions as $column => $value) {
                $conditionParts[] = "$column = :where_$column";
            }
            $whereClause .= implode(' AND ', $conditionParts);
        }

        // Final SQL query
        $query = "UPDATE $tableName SET $setClause" . $whereClause;
        $stmt = $this->conn->prepare($query);

        // Bind the field values
        foreach ($fields as $column => $value) {
            $stmt->bindValue(":$column", $value, PDO::PARAM_STR);
        }

        // Bind the condition values if any
        if ($conditions) {
            foreach ($conditions as $column => $value) {
                $stmt->bindValue(":where_$column", $value, PDO::PARAM_STR);
            }
        }
        return $stmt->execute();
    }


    public function countRows($table)
    {
        $query = "SELECT COUNT(*) as total FROM " . $table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ)->total;
    }
    /**
     * Retrieve the total count of records in a specified table with optional filters.
     *
     * This function executes a `COUNT` SQL query on a given table to get the total
     * number of records, applying specified filters as conditions. Each filter 
     * field and value is appended to the query dynamically, and values are bound 
     * securely to prevent SQL injection.
     *
     * @param string $table The name of the database table.
     * @param array $filters Optional associative array of filters (column => value)
     *                       to be applied to the count query.
     * 
     * @return int The total count of records in the table matching the filters.
     */
    public function getTotalCount($table, $filters = [])
    {
        $totalQuery = "SELECT COUNT(*) as total FROM " . $table . " WHERE 1=1";
        $totalStmt = $this->conn->prepare($totalQuery);
        $params = [];

        // Append filters dynamically
        foreach ($filters as $field => $value) {
            $totalQuery .= " AND $field = :$field"; // Add  filters
            $params[":$field"] = $value; // Store value for binding

        }
        // Prepare the statement
        $totalStmt = $this->conn->prepare($totalQuery);

        // Bind parameters
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $totalStmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $totalStmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        $totalStmt->execute();
        $total = $totalStmt->fetch(PDO::FETCH_OBJ)->total;
        return $total;
    }


    /**
     * Retrieve paginated records from a specified table with optional filters and sorting.
     *
     * This function executes a `SELECT` query on a given table, applying filters, sorting,
     * and pagination. It calculates the offset based on the requested page number and 
     * results per page, retrieves the records, and provides pagination metadata.
     *
     * @param string $table The name of the database table.
     * @param int $page The current page number (default is 1).
     * @param int $resultsPerPage The number of results per page (default is 10).
     * @param string $columns The columns to retrieve, defaults to '*' (all columns).
     * @param array $filters Optional associative array of filters (column => value)
     *                       to be applied to the query.
     * @param string|null $sortBy Optional column name to sort by.
     * 
     * @return array An associative array containing pagination data and the results:
     *               - 'pagination': metadata about the current pagination state
     *               - 'result': an array of retrieved records as objects.
     */
    public function paginate($table, $page = 1, $resultsPerPage = 10, $columns = '*', $filters = [], $sortBy = null)
    {
        // Calculate the offset for the query
        $offset = ($page - 1) * $resultsPerPage;

        $query = "SELECT " . $columns . " FROM " . $table . " WHERE 1=1";
        $params = [];
        foreach ($filters as $field => $value) {
            $query .= " AND $field = :$field";
            $params[":$field"] = $value; // Bind value
        }
        if ($sortBy) {
            $query .= " ORDER BY " . $sortBy;
        }
        $query .= " LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);

        // Bind parameters
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':limit', $resultsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        // Fetch all results
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Get the total count of records
        $total = $this->getTotalCount($table, $filters);
        return [
            'pagination' => [
                'current_page' => $page,
                'per_page' => $resultsPerPage,
                'total' => $total,
                'total_pages' => ceil($total / $resultsPerPage)
            ],
            'result' => $results,
        ];
    }
}
