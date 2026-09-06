<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Database;

use App\Shared\Database\SqlScript;
use PHPUnit\Framework\TestCase;

final class SqlScriptTest extends TestCase
{
    public function testTriggerAndQuotedSemicolonsRemainSingleStatements(): void
    {
        $sql = "-- comment ;\nCREATE TABLE example (name VARCHAR(40));\nDELIMITER $$\nCREATE TRIGGER t AFTER INSERT ON example FOR EACH ROW BEGIN SET @a = 'a;b'; SET @b = 'it\\'s'; END$$\nDELIMITER ;\nINSERT INTO example VALUES ('a; b');\n";
        $statements = SqlScript::statements($sql);
        self::assertCount(3, $statements);
        self::assertStringContainsString("SET @a = 'a;b';", $statements[1]);
        self::assertStringContainsString("VALUES ('a; b')", $statements[2]);
    }

    public function testEmptyScriptHasNoStatements(): void
    {
        self::assertSame([], SqlScript::statements("-- comment\n/* note */"));
    }
}
