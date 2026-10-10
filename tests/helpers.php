<?php
/**
 * Shared test helpers. Every test file is a standalone script:
 *
 *   require_once __DIR__ . '/helpers.php';
 *
 * Kirby-free tests require the classes they need themselves (src/I18n.php
 * first). Kirby tests boot a throwaway site through higawari_kirby(). Each
 * file calls expect()/holds() as often as needed, then finish('Suite name'),
 * which prints every failure and exits 1, or prints the count and exits 0.
 */

$GLOBALS['higawariChecks'] = 0;
$GLOBALS['higawariFailures'] = [];

/** Strict comparison ($expected === $actual). */
function expect(mixed $expected, mixed $actual, string $what): void
{
    $GLOBALS['higawariChecks']++;
    if ($expected !== $actual) {
        $GLOBALS['higawariFailures'][] = sprintf(
            "  ✗ %s\n      expected: %s\n      actual:   %s",
            $what,
            var_export($expected, true),
            var_export($actual, true)
        );
    }
}

function holds(bool $ok, string $what): void
{
    $GLOBALS['higawariChecks']++;
    if (!$ok) {
        $GLOBALS['higawariFailures'][] = '  ✗ ' . $what;
    }
}

/** The callable must throw an instance of $class. */
function throws(string $class, callable $fn, string $what): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        holds($e instanceof $class, $what . ': threw ' . $e::class . ' instead of ' . $class);
        return;
    }
    holds(false, $what . ': nothing thrown, expected ' . $class);
}

/** A moment in Europe/Berlin (or another zone): at('2026-10-10 12:00'). */
function at(string $when, string $zone = 'Europe/Berlin'): DateTimeImmutable
{
    return new DateTimeImmutable($when, new DateTimeZone($zone));
}

/** Prints the result and exits: 0 when every check passed, 1 otherwise. */
function finish(string $suite): never
{
    $failures = $GLOBALS['higawariFailures'];
    $checks = $GLOBALS['higawariChecks'];
    if ($failures !== []) {
        fwrite(STDERR, "$suite: " . count($failures) . " of $checks checks failed\n" . implode("\n", $failures) . "\n");
        exit(1);
    }
    echo "$suite: all $checks checks passed\n";
    exit(0);
}

/* --------------------------------------------------------------------------
 * Kirby fixtures (need vendor/: composer install).
 * -------------------------------------------------------------------------- */

const HIGAWARI_HOME_BLUEPRINT = "title: Startseite\ntabs:\n  tageskarte:\n    extends: higawari/tabs/daily-menu\n";

/**
 * A throwaway site with the package symlinked as site/plugins/higawari, a
 * home page with $fields (structure values are YAML-encoded the way the Panel
 * stores them) and a home blueprint with the Tageskarte tab. Removed after $run.
 */
function higawari_with_site(array $fields, callable $run): void
{
    $base = dirname(__DIR__);
    if (!is_file($base . '/vendor/autoload.php')) {
        fwrite(STDERR, "Composer dependencies missing; run composer install first\n");
        exit(1);
    }
    require_once $base . '/vendor/autoload.php';

    $tmp = sys_get_temp_dir() . '/higawari-kirby-' . bin2hex(random_bytes(6));
    foreach (['public', 'content/home', 'site/plugins', 'site/templates', 'site/blueprints/pages', 'storage'] as $dir) {
        mkdir($tmp . '/' . $dir, 0700, true);
    }
    symlink($base, $tmp . '/site/plugins/higawari');
    file_put_contents($tmp . '/site/templates/default.php', 'Fixture');
    file_put_contents($tmp . '/site/blueprints/pages/home.yml', HIGAWARI_HOME_BLUEPRINT);
    $blocks = ['Title: Home'];
    foreach ($fields as $name => $value) {
        $blocks[] = ucfirst($name) . ': ' . (is_array($value) ? "\n\n" . Kirby\Data\Yaml::encode($value) : (string) $value);
    }
    file_put_contents($tmp . '/content/home/home.txt', implode("\n\n----\n\n", $blocks) . "\n");
    try {
        $run($tmp);
    } finally {
        higawari_remove_tree($tmp);
    }
}

/** A fresh Kirby on a higawari_with_site() directory. Options are passed as they are. */
function higawari_kirby(string $tmp, array $options = []): Kirby\Cms\App
{
    Kirby\Template\Snippet::$cache = [];
    return new Kirby\Cms\App([
        'roots' => [
            'base' => $tmp,
            'index' => $tmp . '/public',
            'content' => $tmp . '/content',
            'site' => $tmp . '/site',
            'storage' => $tmp . '/storage',
        ],
        'options' => ['url' => 'http://example.invalid'] + $options,
    ]);
}

function higawari_remove_tree(string $path): void
{
    if (!is_dir($path) || is_link($path)) {
        return;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $file) {
        $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($path);
}
