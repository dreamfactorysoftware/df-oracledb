<?php

namespace DreamFactory\Core\OracleDb\Tests\Security;

use PHPUnit\Framework\TestCase;

/**
 * Security: OracleSchema must parameterize schema/owner lookups + quote
 * identifiers in DDL helpers.
 *
 * Phase 2 audit found 5 SQL interpolation sites + DDL identifier issues:
 *   - getTableConstraints (A.OWNER IN)
 *   - getTableNames (owner = '\$schema')
 *   - getViewNames (owner = '\$schema')
 *   - loadTableColumns nested-table lookup (owner / table_name)
 *   - dropColumns ALTER TABLE — identifiers concatenated raw
 */
class OracleSchemaHardeningTest extends TestCase
{
    private string $contents;

    protected function setUp(): void
    {
        $sourcePath = __DIR__ . '/../../src/Database/Schema/OracleSchema.php';
        $this->assertFileExists($sourcePath);
        $this->contents = file_get_contents($sourcePath);
    }

    public function testNoOwnerInterpolation(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            "/owner\s*=\s*'\\\$schema'/i",
            $this->contents,
            'No owner = \$schema interpolation must remain'
        );
        $this->assertDoesNotMatchRegularExpression(
            "/OWNER in\s*\(\s*'\{\\\$schema\}'\s*\)/",
            $this->contents,
            'No OWNER IN (\$schema) interpolation must remain'
        );
    }

    public function testNoNestedTableInterpolation(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            "/owner\s*=\s*'\\\$nestedTableOwner'/",
            $this->contents,
            'Nested-table lookup must not interpolate \$nestedTableOwner raw'
        );
    }

    public function testNamedBindingsUsed(): void
    {
        $this->assertMatchesRegularExpression(
            '/:schema\b/',
            $this->contents,
            ':schema named placeholder must appear'
        );
    }

    public function testDropColumnsQuotesIdentifiers(): void
    {
        $start = strpos($this->contents, 'function dropColumns');
        $this->assertNotFalse($start);
        $next = strpos($this->contents, "\n    /**", $start + 10);
        $body = substr($this->contents, $start, $next === false ? null : ($next - $start));

        $this->assertDoesNotMatchRegularExpression(
            '/ALTER TABLE \$table DROP COLUMN /',
            $body,
            'dropColumns must not interpolate raw \$table'
        );
        $this->assertMatchesRegularExpression(
            '/quoteColumnName\b/',
            $body,
            'dropColumns must quote column identifiers'
        );
    }
}
