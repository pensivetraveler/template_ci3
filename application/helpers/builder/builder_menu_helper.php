<?php

function make_base_auth_from_method(array $method): string
{
    // list category method 만 base_auth 계산 대상
    if (($method['category'] ?? '') !== 'list') {
        return '000000';
    }

    // C: 등록
    $c = !empty($method['buttons']['add']) ? '1' : '0';

    // R: 상세 조회
    $r = !empty($method['actions']['view']) ? '1' : '0';

    // U: 수정
    $u = !empty($method['actions']['edit']) ? '1' : '0';

    // D: 삭제
    $d = !empty($method['actions']['delete']) ? '1' : '0';

    // E: Export
    $exports = $method['exports'] ?? [];
    $e = (
        !empty($exports['print']) ||
        !empty($exports['csv']) ||
        !empty($exports['excel']) ||
        !empty($exports['pdf']) ||
        !empty($exports['copy'])
    ) ? '1' : '0';

    // I: Import
    // 현재 구조상 buttons.excel 은 Export 가 아니라 CSV/Excel Import 화면 진입 버튼
    $i = !empty($method['buttons']['excel']) ? '1' : '0';

    return $c . $r . $u . $d . $e . $i;
}

function merge_auth(string $a, string $b): string
{
    $a = normalize_auth_string($a);
    $b = normalize_auth_string($b);

    $result = '';

    for ($i = 0; $i < 6; $i++) {
        $result .= ($a[$i] === '1' || $b[$i] === '1') ? '1' : '0';
    }

    return $result;
}

function make_base_auth_from_route(array $route): string
{
    $base_auth = BASE_AUTH_CHAR;

    foreach (($route['methods'] ?? []) as $method) {
        $method_auth = make_base_auth_from_method($method);
        $base_auth = merge_auth($base_auth, $method_auth);
    }

    return $base_auth;
}

function normalize_menu_auth(string $base_auth, ?string $menu_auth): string
{
    $base_auth = normalize_auth_string($base_auth);
    $menu_auth = normalize_auth_string($menu_auth ?: BASE_AUTH_CHAR);

    $result = '';

    for ($i = 0; $i < 6; $i++) {
        $result .= ($base_auth[$i] === '1' && $menu_auth[$i] === '1') ? '1' : '0';
    }

    return $result;
}

function normalize_auth_string(?string $auth): string
{
    $auth = $auth ?: BASE_AUTH_CHAR;

    // 0과 1만 남긴다.
    $auth = preg_replace('/[^01]/', '', $auth);

    return str_pad(substr($auth, 0, 6), 6, '0');
}

function get_auth_bit(string $auth, string $key): bool
{
    $auth = normalize_auth_string($auth);

    $map = [
        'create' => 0,
        'read'   => 1,
        'update' => 2,
        'delete' => 3,
        'export' => 4,
        'import' => 5,
    ];

    if (!isset($map[$key])) {
        return false;
    }

    return $auth[$map[$key]] === '1';
}

function make_effective_auth(string $base_auth, ?string $menu_auth): string
{
    return normalize_menu_auth($base_auth, $menu_auth);
}

function make_default_menu_auth(string $base_auth): string
{
    return normalize_auth_string($base_auth);
}

function auth_to_array(string $auth): array
{
    $auth = normalize_auth_string($auth);

    return [
        'create' => $auth[0] === '1',
        'read'   => $auth[1] === '1',
        'update' => $auth[2] === '1',
        'delete' => $auth[3] === '1',
        'export' => $auth[4] === '1',
        'import' => $auth[5] === '1',
    ];
}

function build_menu_auth_from_post(array $auth): string
{
    return
        (!empty($auth['create']) ? '1' : '0') .
        (!empty($auth['read'])   ? '1' : '0') .
        (!empty($auth['update']) ? '1' : '0') .
        (!empty($auth['delete']) ? '1' : '0') .
        (!empty($auth['export']) ? '1' : '0') .
        (!empty($auth['import']) ? '1' : '0');
}
