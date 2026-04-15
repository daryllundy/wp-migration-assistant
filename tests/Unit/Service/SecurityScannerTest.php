<?php

declare(strict_types=1);

namespace WPMigration\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use WPMigration\Service\SecurityScanner;

final class SecurityScannerTest extends TestCase
{
    public function testSecurityMalwareAndVulnerabilityScansUseDistinctHeuristics(): void
    {
        $root = sys_get_temp_dir() . '/wp-migration-scan-' . bin2hex(random_bytes(4));
        mkdir($root . '/wp-content/plugins/risky-plugin', 0755, true);
        mkdir($root . '/wp-includes', 0755, true);

        file_put_contents($root . '/wp-content/plugins/risky-plugin/security.php', "<?php\nshell_exec('id');\n");
        file_put_contents($root . '/wp-content/plugins/risky-plugin/malware.php', "<?php\nbase64_decode('" . str_repeat('A', 220) . "');\n");
        file_put_contents($root . '/wp-content/plugins/risky-plugin/upload.php', "<?php\n\$p = \$_GET['p']; include(\$p); move_uploaded_file(\$_FILES['f']['tmp_name'], '/tmp/x');\n");
        file_put_contents($root . '/wp-includes/version.php', "<?php\n\$wp_version = '6.2';\n");

        $scanner = new SecurityScanner();

        try {
            $security = $scanner->scanSecurity($root);
            $malware = $scanner->scanMalware($root);
            $vulnerabilities = $scanner->scanVulnerabilities($root);

            $this->assertSame('security', $security['scanner']);
            $this->assertSame('malware', $malware['scanner']);
            $this->assertSame('vulnerability', $vulnerabilities['scanner']);
            $this->assertNotEmpty($security['issues']);
            $this->assertNotEmpty($malware['issues']);
            $this->assertNotEmpty($vulnerabilities['issues']);
        } finally {
            unlink($root . '/wp-content/plugins/risky-plugin/security.php');
            unlink($root . '/wp-content/plugins/risky-plugin/malware.php');
            unlink($root . '/wp-content/plugins/risky-plugin/upload.php');
            unlink($root . '/wp-includes/version.php');
            rmdir($root . '/wp-content/plugins/risky-plugin');
            rmdir($root . '/wp-content/plugins');
            rmdir($root . '/wp-content');
            rmdir($root . '/wp-includes');
            rmdir($root);
        }
    }
}
