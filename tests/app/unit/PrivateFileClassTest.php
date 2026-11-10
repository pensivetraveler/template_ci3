<?php
use PHPUnit\Framework\TestCase;

class PrivateFileClassTest extends TestCase
{
    /** @dataProvider privatePaths */
    public function testPrivatePaths(string $path, string $class): void
    {
        $this->assertSame($class, private_file_class($path));
    }

    public function privatePaths(): array
    {
        return [
            ['application/uploads/articles/2026/a.png', 'articles'],
            ['/var/www/html/application/uploads/boards/2026/a.png', 'boards'],
            ['s3://bucket/application/uploads/articles/a.png', 'articles'],
            ['public/application/uploads/articles/a.png', 'articles'],
            ['application\\uploads\\articles\\a.png', 'articles'],
        ];
    }

    public function testPublicPathsAreNotPrivate(): void
    {
        foreach (['public/uploads/articles/a.png', 'https://cdn.example.com/a.png', '', 'application/config/a.php'] as $path) {
            $this->assertNull(private_file_class($path), $path);
        }
    }
}
