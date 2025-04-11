<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Sql_parser
{
    function __construct()
    {
        log_message('Debug', 'SQL Parser Libarary is loaded.');
    }

    function parsing($sql) {
        // 파일을 읽어와 SQL 문을 배열로 나누기
        $lines = preg_split('/;\s*/', $sql);

        // 테이블 생성 관련 쿼리 저장용 배열
        $tables = [];
        $dropTableStatements = [];
        $createTableStatements = [];
        $alterStatementsPK = [];
        $alterStatementsFK = [];
        $uniqueStatements = [];
        $autoIncrementStatements = [];

        foreach ($lines as $line) {
            // CREATE TABLE 문 찾기
            if (preg_match("/DROP TABLE\s+IF EXISTS\s+`([^`]+)`/i", $line, $matches)) {
                $tableName = $matches[1];
                if(!array_key_exists($tableName, $tables)) $tables[$tableName] = [];
                $dropTableStatements[$tableName] = $line;
            }

            if (preg_match('/CREATE TABLE\s+`([^`]+)`\s*\((.*)\)/si', $line, $matches)) {
                $tableName = $matches[1];
                $columns = $matches[2];

                // 컬럼 추출
                if(preg_match_all(
                    "/`([^`]+)`\s+([A-Z]+(?:\(\d+\))?(?:\s+Unsigned)?(?:\s+NOT NULL)?(?:\s+DEFAULT\s+[^ ]+)?(?:\s+COMMENT\s+'[^']*')?)/i",
                    $columns,
                    $matchesColumn
                )){
                    foreach ($matchesColumn[1] as $index => $column) {
                        $tables[$tableName][$column] = trim($matchesColumn[2][$index]);
                    }
                }

                // 정규식으로 COMMENT 'auto_increment' 를 해당 컬럼명으로 변경
                $pattern = '/`([a-zA-Z0-9_]+)`\s+([^\n]+)\s+COMMENT\s+\'auto_increment\'/';
                $replacement = '`$1` $2 COMMENT \'$1\'';
                // 변경된 SQL 구문
                $modifiedSql = preg_replace($pattern, $replacement, $line);

                // AUTO_INCREMENT 필드 찾기
                if (preg_match_all('/`([^`]+)`\s+BIGINT\(11\)\s+Unsigned\s+NOT NULL\s+COMMENT\s+\'auto_increment\'/', $columns, $columnMatches)) {
                    $autoIncrementStatements[$tableName] = "ALTER TABLE `$tableName` MODIFY COLUMN `{$columnMatches[1][0]}` BIGINT(11) UNSIGNED NOT NULL AUTO_INCREMENT;";
                }

                if(strpos($modifiedSql, 'ENGINE=') === false) {
                    $modifiedSql .= ' ENGINE=INNODB ';
                }

                if(strpos($modifiedSql, 'DEFAULT CHARSET=') === false) {
                    $modifiedSql .= ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ';
                }

                $createTableStatements[$tableName] = $modifiedSql;
            }

            // Collect ALTER TABLE statements for multiple primary key columns
            if (preg_match('/ALTER TABLE `([^`]+)` ADD CONSTRAINT `([^`]+)` PRIMARY KEY \(\s*([^()]+?)\s*\)/', $line, $matches)) {
                $tableName = $matches[1];
                $columns = array_map('trim', explode(',', $matches[3])); // 쉼표로 분리하고 공백 제거
                foreach ($columns as $column) {
                    $column = preg_replace('/`/', '', $column);
                    $temp = $tables[$tableName][$column]??'';
                    if ((strpos($temp, 'VARCHAR') !== false || strpos($temp, 'CHAR') !== false) && count($columns) > 1) {
                        $uniqueStatements[$tableName][] = "CREATE UNIQUE INDEX {$tableName}_{$column}_uindex on {$tableName} (`{$column}`)";
                    }
                }
                $alterStatementsPK[$tableName] = $line;
            }

            // Collect ALTER TABLE foreign key statements
            if (preg_match('/ALTER TABLE `([^`]+)` ADD CONSTRAINT `([^`]+)` FOREIGN KEY \(\s*`([^`]+)`\s*\)\s*REFERENCES `([^`]+)` \(\s*`([^`]+)`\s*\)/', $line, $matches)) {
                $tableName = $matches[1];             // tbl_article
                // Store or process the extracted values
                $alterStatementsFK[$tableName] = $line;
            }
        }

        $statements = [];
        foreach (array_keys($tables) as $table) {
            if(array_key_exists($table, $dropTableStatements)) $statements[] = $dropTableStatements[$table];
            if(array_key_exists($table, $createTableStatements)) $statements[] = $createTableStatements[$table];
        }
        foreach (array_keys($tables) as $table) {
            if(array_key_exists($table, $alterStatementsPK)) $statements[] = $alterStatementsPK[$table];
            if(array_key_exists($table, $uniqueStatements)) $statements[] = implode(";\n", $uniqueStatements[$table]);
        }
        foreach (array_keys($tables) as $table) {
            if(array_key_exists($table, $alterStatementsFK)) $statements[] = $alterStatementsFK[$table];
        }
        foreach (array_keys($tables) as $table) {
            if(array_key_exists($table, $autoIncrementStatements)) $statements[] = $autoIncrementStatements[$table];
        }

        // SQL 문 다시 문자열로 변환
        return implode(";\n", $statements);
    }
}
