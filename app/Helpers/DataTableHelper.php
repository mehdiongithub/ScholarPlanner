<?php

namespace App\Helpers;

use PDO;

class DataTableHelper {
    public static function process(
        PDO $db,
        string $baseTable,
        array $columns,
        array $searchableColumns,
        array $columnMapping,
        array $joins = [],
        string $customWhere = '',
        array $customParams = [],
        ?callable $rowFormatter = null
    ): array {
        $draw = (int)($_GET['draw'] ?? 1);
        $start = (int)($_GET['start'] ?? 0);
        $length = (int)($_GET['length'] ?? 25);
        $searchValue = trim($_GET['search']['value'] ?? '');
        
        $whereClauses = [];
        $params = $customParams;
        
        if ($customWhere !== '') {
            $whereClauses[] = $customWhere;
        }
        
        // Handle search
        if ($searchValue !== '') {
            $searchTerms = [];
            foreach ($searchableColumns as $col) {
                $paramName = 'search_' . str_replace('.', '_', $col);
                $searchTerms[] = "$col LIKE :$paramName";
                $params[$paramName] = '%' . $searchValue . '%';
            }
            if (!empty($searchTerms)) {
                $whereClauses[] = '(' . implode(' OR ', $searchTerms) . ')';
            }
        }
        
        $whereSql = '';
        if (!empty($whereClauses)) {
            $whereSql = ' WHERE ' . implode(' AND ', $whereClauses);
        }
        
        $joinSql = implode(' ', $joins);
        
        // Count total records in database
        $totalCountQuery = "SELECT COUNT(*) FROM $baseTable $joinSql";
        if ($customWhere !== '') {
            $totalCountQuery .= " WHERE $customWhere";
        }
        $stmtTotal = $db->prepare($totalCountQuery);
        $totalParams = $customParams;
        $stmtTotal->execute($totalParams);
        $recordsTotal = (int)$stmtTotal->fetchColumn();
        
        // Count filtered records
        $filteredCountQuery = "SELECT COUNT(*) FROM $baseTable $joinSql $whereSql";
        $stmtFiltered = $db->prepare($filteredCountQuery);
        $stmtFiltered->execute($params);
        $recordsFiltered = (int)$stmtFiltered->fetchColumn();
        
        // Sorting
        $orderSql = '';
        $orderColumnIdx = (int)($_GET['order'][0]['column'] ?? -1);
        $orderDir = strtoupper($_GET['order'][0]['dir'] ?? 'DESC');
        if (!in_array($orderDir, ['ASC', 'DESC'])) {
            $orderDir = 'DESC';
        }
        
        if ($orderColumnIdx >= 0 && isset($_GET['columns'][$orderColumnIdx]['data'])) {
            $colName = $_GET['columns'][$orderColumnIdx]['data'];
            if (isset($columnMapping[$colName])) {
                $orderSql = ' ORDER BY ' . $columnMapping[$colName] . ' ' . $orderDir;
            }
        }
        
        if ($orderSql === '' && !empty($columnMapping)) {
            $firstCol = reset($columnMapping);
            $orderSql = " ORDER BY $firstCol DESC";
        }
        
        // Pagination
        $limitSql = " LIMIT :limit OFFSET :offset";
        
        // Fetch rows
        $selectCols = [];
        foreach ($columns as $alias => $realName) {
            if (is_numeric($alias)) {
                $selectCols[] = $realName;
            } else {
                $selectCols[] = "$realName AS $alias";
            }
        }
        $selectSql = implode(', ', $selectCols);
        
        $dataQuery = "SELECT $selectSql FROM $baseTable $joinSql $whereSql $orderSql $limitSql";
        
        $stmtData = $db->prepare($dataQuery);
        foreach ($params as $key => $val) {
            $stmtData->bindValue(':' . $key, $val);
        }
        $stmtData->bindValue(':limit', $length, PDO::PARAM_INT);
        $stmtData->bindValue(':offset', $start, PDO::PARAM_INT);
        
        $stmtData->execute();
        $rows = $stmtData->fetchAll(PDO::FETCH_ASSOC);
        
        $data = [];
        foreach ($rows as $row) {
            if ($rowFormatter !== null) {
                $data[] = $rowFormatter($row);
            } else {
                $data[] = $row;
            }
        }
        
        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }
}
