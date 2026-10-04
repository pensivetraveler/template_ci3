<?php
if( !function_exists('split_log_lines') ){
    function split_log_lines($text): array
    {
        $text = preg_replace(
            '/\A(?:\xEF\xBB\xBF)?\s*<\?php\s+defined\([\'"]BASEPATH[\'"]\)\s+OR\s+exit\([\'"]No direct script access allowed[\'"]\);\s*\?>\s*/u',
            '',
            $text
        );

        $text = trim($text);

        if ($text === '') {
            return [];
        }

        /**
         * 새 로그 시작 직전의 줄바꿈에서만 split
         *
         * 예:
         * ERROR - 2026-06-30 02:31:20 -->
         * DEBUG - 2026-06-30 02:31:24 -->
         * INFO  - 2026-06-30 02:31:24 -->
         */
        $pattern = '/\R(?=[A-Z]+\s*-\s*\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\s*-->)/u';
//        $pattern = '/\R(?=(?:ERROR|DEBUG|INFO)\s*-\s*\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\s*-->)/u';

        $rows = preg_split($pattern, $text);

        $rows = array_map(function ($row) {
            $row = trim($row);

            /**
             * split 이후 row 내부에 남아 있는 줄바꿈 제거
             *
             * before:
             * UPDATE ...
             * WHERE ...
             *
             * after:
             * UPDATE ... WHERE ...
             */
            $row = preg_replace('/[ \t]*\R+[ \t]*/u', ' ', $row);

            return trim($row);
        }, $rows);

        $rows = array_filter($rows, function ($row) {
            return $row !== '';
        });

        return array_values($rows);
    }
}

if( !function_exists('parse_log_line') ){
    function parse_log_line($line): array
    {
        preg_match('/^(ERROR|DEBUG|INFO)\s+-\s+([0-9\-]+\s+[0-9:]+)\s+-->\s+(.*)$/', $line, $matches);

        if (!$matches) {
            return [
                'level' => null,
                'datetime' => null,
                'message' => $line,
            ];
        }

        return [
            'level' => $matches[1],
            'datetime' => $matches[2],
            'message' => $matches[3],
        ];
    }
}
