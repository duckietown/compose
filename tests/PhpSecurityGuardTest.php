<?php

use PHPUnit\Framework\TestCase;

class PhpSecurityGuardTest extends TestCase
{
  private string $directory;

  protected function setUp(): void
  {
    $this->directory = sys_get_temp_dir() . '/compose-php-security-' . bin2hex(random_bytes(8));
    self::assertTrue(mkdir($this->directory));
    $fpm = <<<'SH'
#!/bin/sh
printf '%s\n' "$TEST_FPM_OUTPUT"
exit "$TEST_FPM_STATUS"
SH;
    foreach (['7.4', '8.3', '8.4', '8.5'] as $branch) {
      $this->command('php-fpm' . $branch, $fpm);
    }
    $this->command('php', "#!/bin/sh\nprintf 'PHP 99.0.0 (cli)\\n'\n");
    $this->command('dpkg-query', <<<'SH'
#!/bin/sh
case "$3" in
  *-fpm) printf '%s\n' "$TEST_FPM_PACKAGE"; exit "$TEST_FPM_QUERY_STATUS" ;;
  *-common) printf '%s\n' "$TEST_COMMON_PACKAGE"; exit "$TEST_COMMON_QUERY_STATUS" ;;
  *) exit 2 ;;
esac
SH
    );
  }

  protected function tearDown(): void
  {
    foreach (scandir($this->directory) as $file) {
      if ($file !== '.' && $file !== '..') {
        unlink($this->directory . '/' . $file);
      }
    }
    rmdir($this->directory);
  }

  /** @dataProvider patchedVersions */
  public function testAcceptsPatchedVersions(string $branch, string $version, string $package): void
  {
    [$status, $output] = $this->runGuard($branch, [
      'TEST_FPM_OUTPUT' => "PHP $version (fpm-fcgi)",
      'TEST_FPM_PACKAGE' => "installed $package",
      'TEST_COMMON_PACKAGE' => "installed $package",
    ]);

    self::assertSame(0, $status, $output);
    self::assertStringContainsString("PHP2Shell security check passed: PHP-FPM $version", $output);
  }

  public function testAcceptsPackagedFocalBinaryVersion(): void
  {
    foreach (['7.4.3-4ubuntu2.29+esm5', '7.4.3-4ubuntu2.29+duckietown1.php2shell.712e60f9'] as $package) {
      [$status, $output] = $this->runGuard('7.4', [
        'TEST_FPM_OUTPUT' => "PHP $package (fpm-fcgi)",
        'TEST_FPM_PACKAGE' => "installed $package",
        'TEST_COMMON_PACKAGE' => "installed $package",
      ]);
      self::assertSame(0, $status, $output);
      self::assertStringContainsString("PHP2Shell security check passed: PHP-FPM 7.4.3 ($package)", $output);
    }
  }

  public static function patchedVersions(): array
  {
    return [
      '7.4 Focal ESM floor' => ['7.4', '7.4.3', '7.4.3-4ubuntu2.29+esm5'],
      '7.4 newer Focal ESM revision' => ['7.4', '7.4.3', '7.4.3-4ubuntu2.29+esm6'],
      '7.4 newer Focal package revision' => ['7.4', '7.4.3', '7.4.3-4ubuntu2.30+esm1'],
      '7.4 pinned Focal source build' => ['7.4', '7.4.3', '7.4.3-4ubuntu2.29+duckietown1.php2shell.712e60f9'],
      '7.4 Jammy backport' => ['7.4', '7.4.33', '1:7.4.33-31+ubuntu22.04.1+deb.sury.org+1'],
      '7.4 Noble backport' => ['7.4', '7.4.33', '1:7.4.33-31+ubuntu24.04.1+deb.sury.org+1'],
      '7.4 newer backport revision' => ['7.4', '7.4.33', '1:7.4.33-32+ubuntu24.04.1+deb.sury.org+1'],
      '8.3 floor' => ['8.3', '8.3.36', '8.3.36-1'],
      '8.3 newer' => ['8.3', '8.3.37', '8.3.37-1'],
      '8.4 floor' => ['8.4', '8.4.27', '8.4.27-1'],
      '8.4 newer' => ['8.4', '8.4.28', '8.4.28-1'],
      '8.5 floor' => ['8.5', '8.5.12', '8.5.12-1'],
      '8.5 newer' => ['8.5', '8.5.13', '8.5.13-1'],
      'Debian epoch and PPA revision' => ['8.3', '8.3.36', '2:8.3.36-1+ubuntu22.04.1+deb.sury.org+1'],
      'Debian revision' => ['8.3', '8.3.36', '8.3.36-1~deb12u1'],
    ];
  }

  /** @dataProvider unsafeVersions */
  public function testRejectsUnsafeOrUnverifiableVersions(string $branch, array $environment, string $message): void
  {
    [$status, $output] = $this->runGuard($branch, $environment);

    self::assertSame(1, $status, $output);
    self::assertStringContainsString('PHP2Shell security check failed:', $output);
    self::assertStringContainsString($message, $output);
    self::assertStringNotContainsString('security check passed', $output);
  }

  public static function unsafeVersions(): array
  {
    return [
      '7.4 Focal binary revision mismatch' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.3-4ubuntu2.29 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 7.4.3-4ubuntu2.29+duckietown1.php2shell.712e60f9',
      ], 'does not match FPM'],
      '7.4 Focal ESM binary revision mismatch' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.3-4ubuntu2.29+esm4 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 7.4.3-4ubuntu2.29+esm5',
      ], 'does not match FPM'],
      '7.4 unreviewed custom patch' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.3 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 7.4.3-4ubuntu2.29+duckietown1.php2shell.ffffffff',
      ], 'Unreviewed PHP 7.4 package'],
      '7.4 unreviewed custom revision' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.3 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 7.4.3-4ubuntu2.29+duckietown2.php2shell.712e60f9',
      ], 'Unreviewed PHP 7.4 package'],
      '7.4 mixed source and archive packages' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.3 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 7.4.3-4ubuntu2.29+duckietown1.php2shell.712e60f9',
        'TEST_COMMON_PACKAGE' => 'installed 7.4.3-4ubuntu2.29',
      ], 'Unreviewed PHP 7.4 package'],
      '7.4 unpatched Focal archive' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.3 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 7.4.3-4ubuntu2.29',
      ], 'Unreviewed PHP 7.4 package'],
      '7.4 old Focal ESM revision' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.3 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 7.4.3-4ubuntu2.29+esm4',
      ], 'below the reviewed ESM revision'],
      '7.4 older Focal package revision' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.3 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 7.4.3-4ubuntu2.28+esm99',
      ], 'below the reviewed ESM revision'],
      '7.4 unreviewed ESM suffix' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.3 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 7.4.3-4ubuntu2.29+esm5+other',
      ], 'Unreviewed PHP 7.4 package'],
      '7.4 epoch cannot hide old ESM revision' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.3 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 99:7.4.3-4ubuntu2.29+esm4',
      ], 'Unreviewed PHP 7.4 package'],
      '7.4 old ESM common package' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.3 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 7.4.3-4ubuntu2.29+esm5',
        'TEST_COMMON_PACKAGE' => 'installed 7.4.3-4ubuntu2.29+esm4',
      ], 'below the reviewed ESM revision'],
      '7.4 mismatched ESM revisions' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.3 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 7.4.3-4ubuntu2.29+esm5',
        'TEST_COMMON_PACKAGE' => 'installed 7.4.3-4ubuntu2.29+esm6',
      ], 'package revisions do not match'],
      '7.4 old PPA revision' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.33 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 1:7.4.33-30+ubuntu22.04.1+deb.sury.org+1',
        'TEST_COMMON_PACKAGE' => 'installed 1:7.4.33-30+ubuntu22.04.1+deb.sury.org+1',
      ], 'below the reviewed backport revision'],
      '7.4 epoch cannot hide old revision' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.33 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 99:7.4.33-30+ubuntu22.04.1+deb.sury.org+1',
      ], 'Unreviewed PHP 7.4 package'],
      '7.4 unknown vendor backport' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.33 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 1:7.4.33-31+other-vendor',
      ], 'Unreviewed PHP 7.4 package'],
      '7.4 old common package' => ['7.4', [
        'TEST_FPM_OUTPUT' => 'PHP 7.4.33 (fpm-fcgi)',
        'TEST_FPM_PACKAGE' => 'installed 1:7.4.33-31+ubuntu22.04.1+deb.sury.org+1',
        'TEST_COMMON_PACKAGE' => 'installed 1:7.4.33-30+ubuntu22.04.1+deb.sury.org+1',
      ], 'below the reviewed backport revision'],
      '8.3 below floor, despite patched CLI' => ['8.3', ['TEST_FPM_OUTPUT' => 'PHP 8.3.35 (fpm-fcgi)'], 'below 8.3.36'],
      '8.4 below floor' => ['8.4', ['TEST_FPM_OUTPUT' => 'PHP 8.4.26 (fpm-fcgi)'], 'below 8.4.27'],
      '8.5 below floor' => ['8.5', ['TEST_FPM_OUTPUT' => 'PHP 8.5.11 (fpm-fcgi)'], 'below 8.5.12'],
      '8.2 is incompatible with the application' => ['8.2', [], 'Unsupported PHP branch'],
      'unreviewed future branch' => ['8.6', [], 'Unsupported PHP branch'],
      'malformed branch' => ['8.3.36', [], 'Unsupported PHP branch'],
      'prerelease binary' => ['8.3', ['TEST_FPM_OUTPUT' => 'PHP 8.3.36RC1 (fpm-fcgi)'], 'Expected a stable'],
      'wrong binary branch' => ['8.3', ['TEST_FPM_OUTPUT' => 'PHP 8.4.27 (fpm-fcgi)'], 'Expected a stable'],
      'CLI is not FPM' => ['8.3', ['TEST_FPM_OUTPUT' => 'PHP 8.3.36 (cli)'], 'Expected a stable'],
      'malformed binary output' => ['8.3', ['TEST_FPM_OUTPUT' => 'unknown'], 'Expected a stable'],
      'failed FPM inspection' => ['8.3', ['TEST_FPM_STATUS' => '1'], 'Cannot inspect php-fpm8.3'],
      'missing FPM binary' => ['8.3', ['TEST_FPM_STATUS' => '127'], 'Cannot inspect php-fpm8.3'],
      'failed FPM package inspection' => ['8.3', ['TEST_FPM_QUERY_STATUS' => '1'], 'Cannot inspect php8.3-fpm'],
      'failed common package inspection' => ['8.3', ['TEST_COMMON_QUERY_STATUS' => '1'], 'Cannot inspect php8.3-common'],
      'unconfigured FPM package' => ['8.3', ['TEST_FPM_PACKAGE' => 'unpacked 8.3.36-1'], 'not fully installed'],
      'removed common package' => ['8.3', ['TEST_COMMON_PACKAGE' => 'config-files 8.3.36-1'], 'not fully installed'],
      'missing package metadata' => ['8.3', ['TEST_FPM_PACKAGE' => ''], 'not fully installed'],
      'missing package version' => ['8.3', ['TEST_FPM_PACKAGE' => 'installed'], 'Invalid stable package version'],
      'malformed package version' => ['8.3', ['TEST_FPM_PACKAGE' => 'installed unknown'], 'Invalid stable package version'],
      'prerelease package' => ['8.3', ['TEST_FPM_PACKAGE' => 'installed 8.3.36~RC1-1'], 'Invalid stable package version'],
      'epoch cannot hide old FPM package' => ['8.3', ['TEST_FPM_PACKAGE' => 'installed 99:8.3.35-1'], 'does not match FPM'],
      'old common package' => ['8.3', ['TEST_COMMON_PACKAGE' => 'installed 8.3.35-1'], 'does not match FPM'],
      'mismatched package revisions' => ['8.3', ['TEST_COMMON_PACKAGE' => 'installed 8.3.36-2'], 'package revisions do not match'],
    ];
  }

  public function testBuildAndStartupEnforceTheGuard(): void
  {
    $root = dirname(__DIR__);
    $dockerfile = file_get_contents($root . '/Dockerfile');
    $copy = strpos($dockerfile, 'COPY --chmod=755 assets/check-php-security.sh /usr/local/bin/check-php-security');
    $install = strpos($dockerfile, 'RUN add-apt-repository');
    self::assertNotFalse($copy);
    self::assertNotFalse($install);
    self::assertLessThan($install, $copy);
    $installationLayer = substr($dockerfile, $install, strpos($dockerfile, "\n\n", $install) - $install);
    self::assertStringContainsString('apt-get update', $installationLayer);
    self::assertStringContainsString('php${PHP_VERSION}-common', $installationLayer);
    self::assertStringContainsString('&& check-php-security "${PHP_VERSION}"', $installationLayer);

    $entrypoint = file_get_contents($root . '/assets/entrypoint.sh');
    $check = strpos($entrypoint, 'check-php-security "${PHP_VERSION:-8.3}"');
    $start = strpos($entrypoint, 'sudo -E /etc/init.d/php${PHP_VERSION:-8.3}-fpm start');
    self::assertNotFalse($check);
    self::assertNotFalse($start);
    self::assertLessThan($start, $check);
  }

  public function testDebugImageChecksItsInheritedRuntime(): void
  {
    $dockerfile = file_get_contents(dirname(__DIR__) . '/Dockerfile.debug');
    $copy = strpos($dockerfile, 'COPY --chmod=755 assets/check-php-security.sh /usr/local/bin/check-php-security');
    $check = strpos($dockerfile, 'RUN check-php-security "${PHP_VERSION:-8.3}"');
    $install = strpos($dockerfile, 'RUN apt-get update');

    self::assertNotFalse($copy);
    self::assertNotFalse($check);
    self::assertNotFalse($install);
    self::assertLessThan($check, $copy);
    self::assertLessThan($install, $check);
    self::assertStringContainsString('COPY assets/entrypoint.sh /entrypoint.sh', $dockerfile);
    self::assertStringContainsString('ENTRYPOINT ["/entrypoint.sh"]', $dockerfile);
    self::assertStringContainsString('ARG BASE_VERSION=debug-base', $dockerfile);
    self::assertStringContainsString('FROM ${BASE_ORGANIZATION}/compose:', $dockerfile);
    self::assertStringContainsString('php${PHP_VERSION}-xdebug', $dockerfile);
    self::assertStringNotContainsString('xdebug-2.4.1', $dockerfile);
    self::assertStringContainsString('phpenmod -v "${PHP_VERSION}" compose-xdebug', $dockerfile);

    $makefile = file_get_contents(dirname(__DIR__) . '/Makefile');
    self::assertStringContainsString('$(MAKE) build VERSION=debug-base TAG=debug-base EXTRA_TAG= COMPOSE_VERSION=${COMPOSE_VERSION}', $makefile);
    self::assertStringContainsString('BASE_VERSION=debug-base', $makefile);
    self::assertMatchesRegularExpression('/^\s*PULL=\s*\\\\$/m', $makefile);
  }

  public function testStandaloneImageLoadsPhpSettingsAndInstallsSudo(): void
  {
    $root = dirname(__DIR__);
    $dockerfile = file_get_contents($root . '/Dockerfile');
    self::assertContains('sudo', file($root . '/dependencies-apt.txt', FILE_IGNORE_NEW_LINES));
    self::assertContains('curl', file($root . '/dependencies-apt.txt', FILE_IGNORE_NEW_LINES));
    self::assertStringContainsString('/etc/php/${PHP_VERSION}/mods-available/apcu.ini', $dockerfile);
    self::assertStringContainsString('/etc/php/${PHP_VERSION}/mods-available/compose-log-errors.ini', $dockerfile);
    self::assertStringContainsString('phpenmod -v "${PHP_VERSION}" apcu compose-log-errors', $dockerfile);
  }

  public function testImageMitigatesPhpUploadProgressRequests(): void
  {
    $root = dirname(__DIR__);
    $dockerfile = file_get_contents($root . '/Dockerfile');
    self::assertStringContainsString("session.upload_progress.enabled = 0", $dockerfile);
    self::assertStringContainsString('/etc/php/${PHP_VERSION}/fpm/conf.d/99-compose-security.ini', $dockerfile);
    self::assertMatchesRegularExpression('/^\s*client_max_body_size 8m;/m', file_get_contents($root . '/assets/etc/nginx/default'));
  }

  public function testComposerChecksTheActualRuntimeAfterDependencyResolution(): void
  {
    $manifest = json_decode(
        file_get_contents(dirname(__DIR__) . '/public_html/system/classes/google_api_php_client/composer.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    self::assertSame('@composer check-platform-reqs', $manifest['scripts']['post-install-cmd']);
    self::assertSame('@composer check-platform-reqs', $manifest['scripts']['post-update-cmd']);
  }

  public function testImageChecksBundledApplicationPlatformAfterCheckout(): void
  {
    $dockerfile = file_get_contents(dirname(__DIR__) . '/Dockerfile');
    $checkout = strpos($dockerfile, 'checkout "${COMPOSE_VERSION}"');
    $check = strpos($dockerfile, 'check-platform-reqs --no-dev');
    self::assertNotFalse($checkout);
    self::assertNotFalse($check);
    self::assertLessThan($check, $checkout);
    self::assertStringContainsString('composer --working-dir="${COMPOSE_DIR}/public_html/system/classes/google_api_php_client"', $dockerfile);
  }

  public function testRegressionTestsStayOutsideTheWebRoot(): void
  {
    $root = dirname(__DIR__);
    self::assertDirectoryDoesNotExist($root . '/public_html/system/classes/google_api_php_client/tests');
    self::assertFileDoesNotExist($root . '/public_html/system/classes/google_api_php_client/phpunit.xml.dist');
    self::assertFileExists($root . '/tests/JwtVerificationTest.php');
    self::assertFileExists($root . '/phpunit.xml.dist');
  }

  private function command(string $name, string $script): void
  {
    $path = $this->directory . '/' . $name;
    self::assertNotFalse(file_put_contents($path, $script . "\n"));
    self::assertTrue(chmod($path, 0755));
  }

  private function runGuard(string $branch, array $overrides): array
  {
    $environment = array_merge(getenv(), [
      'PATH' => $this->directory . ':' . getenv('PATH'),
      'TEST_FPM_OUTPUT' => 'PHP 8.3.36 (fpm-fcgi)',
      'TEST_FPM_STATUS' => '0',
      'TEST_FPM_PACKAGE' => 'installed 8.3.36-1',
      'TEST_COMMON_PACKAGE' => 'installed 8.3.36-1',
      'TEST_FPM_QUERY_STATUS' => '0',
      'TEST_COMMON_QUERY_STATUS' => '0',
    ], $overrides);
    $process = proc_open(
      ['bash', dirname(__DIR__) . '/assets/check-php-security.sh', $branch],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        null,
        $environment
    );
    if ($process === false) {
      throw new RuntimeException('Could not run the PHP security guard');
    }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $output];
  }
}
