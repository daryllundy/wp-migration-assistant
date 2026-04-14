<?php

declare(strict_types=1);

namespace WPMigration\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use WPMigration\Support\ByteSizeParser;

final class ByteSizeParserTest extends TestCase
{
    public function testParseSupportsHumanReadableSizes(): void
    {
        $this->assertSame(100 * 1024 * 1024, ByteSizeParser::parse('100MB'));
        $this->assertSame(1536, ByteSizeParser::parse('1.5KB'));
        $this->assertSame(42, ByteSizeParser::parse('42'));
    }
}
