<?php
/**
 * 시험 DB 접속. DB 이름이 template_test 가 아니면 멈춘다 (개발·운영 DB 를 건드리지 않게).
 */
class TestDb
{
    const NAME = 'template_test';
    private static ?mysqli $conn = null;

    public static function conn(): mysqli
    {
        if (self::$conn) return self::$conn;

        $env = (new Symfony\Component\Dotenv\Dotenv())->parse(file_get_contents(FCPATH.'.env.development'));
        if (getenv('TEST_DB_NAME') !== self::NAME) throw new RuntimeException('TEST_DB_NAME 이 '.self::NAME.' 가 아닙니다.');

        self::$conn = new mysqli($env['DB_HOST'], $env['DB_USERNAME'], $env['DB_PASSWORD'], self::NAME, (int)($env['DB_PORT'] ?: 3306));
        self::$conn->set_charset('utf8mb4');
        return self::$conn;
    }

    public static function exec(string $sql): void
    {
        if (!self::conn()->query($sql)) throw new RuntimeException(self::conn()->error.' : '.$sql);
    }

    public static function value(string $sql)
    {
        $row = self::conn()->query($sql)->fetch_row();
        return $row ? $row[0] : null;
    }

    public static function row(string $sql): ?array
    {
        return self::conn()->query($sql)->fetch_assoc();
    }

    public static function esc(string $value): string
    {
        return self::conn()->real_escape_string($value);
    }
}
