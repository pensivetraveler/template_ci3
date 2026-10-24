<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 리소스 설정 형식 변환 (분할형 ↔ 통합형)
 *
 * module/cli resource_to_merged·resource_to_split 이 쓴다. 웹 요청에서는 로드하지 않는다.
 * - 분할형 → 통합형: 분할형 파일에서 해당 엔티티의 설정 문장·항목을 지우고 resources/{클래스}.php 를 만든다
 * - 통합형 → 분할형: resources/{클래스}.php 의 섹션을 분할형 파일 끝에 설정 문장으로 붙이고 파일을 지운다
 * 메뉴 트리({flag}_nav_config.php)는 두 형식 모두 같은 곳에 있으므로 옮기지 않는다 (통합형 nav 는 예외적으로 안내만).
 *
 * 분할형 파일 편집은 PHP 토큰 단위로 한다: `$config['키'] = …;` 문장과,
 * `$config['route_config'] = [ '클래스' => […], … ];` 안의 항목을 찾아 지운다.
 */
class Resource_converter
{
    /** 엔티티 설정 종류 → 분할형 파일 이름의 종류 부분 ({flag}_{이것}_config.php) */
    public const KIND_FILES = ['route' => 'route', 'form' => 'form', 'list' => 'list', 'filter' => 'filter', 'view' => 'view', 'excel' => 'excel'];

    /* --------------------------------------------------------------- */
    /* PHP 배열 내보내기                                                 */
    /* --------------------------------------------------------------- */

    /**
     * 설정 값을 `[ ... ]` 형식의 PHP 코드로 만든다 (들여쓰기 4칸, 문자열은 작은따옴표).
     */
    public function exportValue($value, int $level = 0): string
    {
        if(is_array($value)) {
            if(!count($value)) return '[]';
            $indent = str_repeat('    ', $level + 1);
            $isList = array_keys($value) === range(0, count($value) - 1);
            $lines = [];
            foreach ($value as $key => $item) {
                $prefix = $isList ? '' : var_export($key, true).' => ';
                $lines[] = $indent.$prefix.$this->exportValue($item, $level + 1).',';
            }
            return "[\n".implode("\n", $lines)."\n".str_repeat('    ', $level).']';
        }
        if(is_bool($value)) return $value ? 'true' : 'false';
        if(is_null($value)) return 'null';
        return var_export($value, true);
    }

    /* --------------------------------------------------------------- */
    /* 분할형 파일 편집 (토큰 단위)                                       */
    /* --------------------------------------------------------------- */

    /**
     * 토큰 목록 [id, text, start, end] (start·end 는 바이트 위치)
     */
    protected function tokens(string $code): array
    {
        $result = [];
        $pos = 0;
        foreach (token_get_all($code) as $token) {
            $text = is_array($token) ? $token[1] : $token;
            $result[] = ['id' => is_array($token) ? $token[0] : null, 'text' => $text, 'start' => $pos, 'end' => $pos + strlen($text)];
            $pos += strlen($text);
        }
        return $result;
    }

    protected function isSpace(array $token): bool
    {
        return in_array($token['id'], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
    }

    protected function unquote(string $text): ?string
    {
        if(strlen($text) >= 2 && ($text[0] === "'" || $text[0] === '"') && substr($text, -1) === $text[0]) {
            return stripcslashes(substr($text, 1, -1));
        }
        return null;
    }

    /**
     * 최상위 `$config['a']['b'] = 값;` 문장 목록.
     * 각 항목: keys(['a','b']), start(줄 시작), end(문장 끝 줄바꿈 다음), valueStart·valueEnd(토큰 번호)
     */
    public function findConfigStatements(string $code): array
    {
        $tokens = $this->tokens($code);
        $count = count($tokens);
        $statements = [];
        $depth = 0;

        for($i = 0; $i < $count; $i++) {
            $t = $tokens[$i];
            if(in_array($t['text'], ['[', '(', '{'], true)) { $depth++; continue; }
            if(in_array($t['text'], [']', ')', '}'], true)) { $depth--; continue; }
            if($depth !== 0 || $t['id'] !== T_VARIABLE || $t['text'] !== '$config') continue;

            // $config ['a'] ['b'] … =
            $keys = [];
            $j = $i + 1;
            while($j + 2 < $count && $tokens[$j]['text'] === '[' && $tokens[$j + 1]['id'] === T_CONSTANT_ENCAPSED_STRING && $tokens[$j + 2]['text'] === ']') {
                $keys[] = $this->unquote($tokens[$j + 1]['text']);
                $j += 3;
            }
            while($j < $count && $this->isSpace($tokens[$j])) $j++;
            if(!count($keys) || $j >= $count || $tokens[$j]['text'] !== '=') continue;

            $valueStart = $j + 1;
            $d = 0;
            for($k = $valueStart; $k < $count; $k++) {
                $text = $tokens[$k]['text'];
                if(in_array($text, ['[', '(', '{'], true)) $d++;
                elseif(in_array($text, [']', ')', '}'], true)) $d--;
                elseif($text === ';' && $d === 0) break;
            }
            if($k >= $count) continue;

            $start = $this->lineStart($code, $t['start']);
            $end = $this->afterLine($code, $tokens[$k]['end']);
            $statements[] = ['keys' => $keys, 'start' => $start, 'end' => $end, 'valueStart' => $valueStart, 'valueEnd' => $k];
            $i = $k;
        }

        return $statements;
    }

    /**
     * 문장 값(배열 리터럴) 바로 안쪽에서 `'키' => 값,` 항목의 범위 [start, end] (줄 단위). 없으면 null.
     */
    public function findArrayElement(string $code, array $statement, string $elementKey): ?array
    {
        $tokens = $this->tokens($code);
        $depth = 0;
        $elementStart = null;

        for($i = $statement['valueStart']; $i < $statement['valueEnd']; $i++) {
            $t = $tokens[$i];
            if(in_array($t['text'], ['[', '(', '{'], true)) { $depth++; continue; }
            if(in_array($t['text'], [']', ')', '}'], true)) {
                $depth--;
                if($depth === 0 && $elementStart !== null) return [$elementStart, $this->lineStart($code, $t['start'])];
                continue;
            }
            if($depth !== 1) continue;

            if($elementStart !== null && $t['text'] === ',') {
                return [$elementStart, $this->afterLine($code, $t['end'])];
            }
            if($elementStart === null && $t['id'] === T_CONSTANT_ENCAPSED_STRING && $this->unquote($t['text']) === $elementKey) {
                $j = $i + 1;
                while($this->isSpace($tokens[$j])) $j++;
                if($tokens[$j]['id'] === T_DOUBLE_ARROW) $elementStart = $this->lineStart($code, $t['start']);
            }
        }
        return null;
    }

    protected function lineStart(string $code, int $pos): int
    {
        $nl = strrpos(substr($code, 0, $pos), "\n");
        return $nl === false ? 0 : $nl + 1;
    }

    protected function afterLine(string $code, int $pos): int
    {
        $nl = strpos($code, "\n", $pos);
        return $nl === false ? strlen($code) : $nl + 1;
    }

    /**
     * 분할형 파일 내용에서 엔티티 설정을 지운다.
     *   $configKeys : 지울 최상위 키 (예: form_projects_config)
     *   $routeKey   : route_config 안에서 지울 클래스 키 ('' 이면 안 지움)
     * 돌려주는 값: [바뀐 내용, 지운 항목 설명 목록]
     */
    public function removeEntity(string $code, array $configKeys, string $routeKey = ''): array
    {
        $ranges = [];
        $removed = [];

        foreach ($this->findConfigStatements($code) as $statement) {
            $keys = $statement['keys'];
            if(count($keys) === 1 && in_array($keys[0], $configKeys, true)) {
                $ranges[] = [$statement['start'], $statement['end']];
                $removed[] = "\$config['{$keys[0]}']";
            }
            if($routeKey !== '' && $keys[0] === 'route_config') {
                if(count($keys) === 2 && $keys[1] === $routeKey) {
                    $ranges[] = [$statement['start'], $statement['end']];
                    $removed[] = "\$config['route_config']['{$routeKey}']";
                }elseif(count($keys) === 1 && ($range = $this->findArrayElement($code, $statement, $routeKey))) {
                    $ranges[] = $range;
                    $removed[] = "route_config['{$routeKey}'] 항목";
                }
            }
        }

        // resource_to_split 가 붙인 표시 주석이 바로 위에 있으면 같이 지운다
        foreach ($ranges as $idx => [$start, $end]) {
            $prevStart = $this->lineStart($code, max(0, $start - 1));
            $prevLine = substr($code, $prevStart, $start - $prevStart);
            if($start > 0 && preg_match('#^\s*//.*resource_to_split#u', $prevLine)) $ranges[$idx][0] = $prevStart;
        }

        usort($ranges, function ($a, $b) { return $b[0] <=> $a[0]; });
        foreach ($ranges as [$start, $end]) $code = substr($code, 0, $start).substr($code, $end);

        return [$code, $removed];
    }

    /**
     * 분할형 파일 끝에 설정 문장을 붙인다.
     *   $statements : ['$config[\'form_x_config\']' => 값, …] 형태가 아니라 [[키 목록], 값] 목록
     */
    public function appendStatements(string $code, array $statements, string $comment): string
    {
        $code = rtrim($code)."\n\n// {$comment}\n";
        foreach ($statements as [$keys, $value]) {
            $target = '$config'.implode('', array_map(function ($key) { return '['.var_export($key, true).']'; }, $keys));
            $code .= $target.' = '.$this->exportValue($value).";\n";
        }
        return $code;
    }

    /**
     * 통합형 파일 내용
     */
    public function mergedFileContent(string $class, array $data): string
    {
        return "<?php\ndefined('BASEPATH') OR exit('No direct script access allowed');\n\n"
            ."// 통합형 리소스 설정: {$class} (형식은 docs/builder/resources.md)\n"
            ."return ".$this->exportValue($data).";\n";
    }

    /**
     * 새 분할형 파일 내용 (해당 종류 파일이 없을 때)
     */
    public function newSplitFileContent(): string
    {
        return "<?php\ndefined('BASEPATH') OR exit('No direct script access allowed');\n";
    }
}
