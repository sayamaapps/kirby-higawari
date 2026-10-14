<?php

namespace Higawari;

use DateTimeZone;
use Kirby\Cms\App;

/**
 * Reads `sayamaapps.higawari.*` options. `null` counts as "not set". Both
 * spellings work, flat ('sayamaapps.higawari.active' => true) and nested
 * ('sayamaapps' => ['higawari' => ['active' => true]]), because Kirby's
 * option() finds both.
 *
 * A broken value is reported once per message and the default is used, except
 * with Kirby's `debug` option on: then it throws, so a developer sees it on the
 * page rather than in the log.
 *
 * Defaults are given here and at the call sites, never in an `options` block
 * of the plugin (Kirby would file them under sayamaapps.higawari.* anyway,
 * but the README documents one place to look).
 */
final class Options
{
    public const PREFIX = 'sayamaapps.higawari.';

    public const DEFAULT_DAYS = 1;
    public const MAX_DAYS = 366;
    public const DEFAULT_CURRENCY = '€';

    /** @var array<string, true> */
    private static array $reported = [];

    public static function get(App $kirby, string $key, mixed $default = null): mixed
    {
        return $kirby->option(self::PREFIX . $key) ?? $default;
    }

    /** Master switch: only a real `true` switches the menu on. */
    public static function active(App $kirby): bool
    {
        return self::get($kirby, 'active', false) === true;
    }

    /** `sayamaapps.higawari.days`: how many days a menu is shown, counted from its date. Default 1 (the day itself). */
    public static function days(App $kirby): int
    {
        $value = self::get($kirby, 'days', self::DEFAULT_DAYS);
        if (is_int($value) && $value >= 1 && $value <= self::MAX_DAYS) {
            return $value;
        }
        self::invalid($kirby, 'Higawari: sayamaapps.higawari.days must be a whole number from 1 to ' . self::MAX_DAYS . ' — using ' . self::DEFAULT_DAYS . '.');
        return self::DEFAULT_DAYS;
    }

    /** `sayamaapps.higawari.timezone`, default Europe/Berlin. Any zone PHP accepts. */
    public static function timezone(App $kirby): string
    {
        $zone = self::get($kirby, 'timezone', Menu::DEFAULT_TIMEZONE);
        if (is_string($zone)) {
            try {
                new DateTimeZone($zone);
                return $zone;
            } catch (\Exception) {
                // Reported below.
            }
        }
        self::invalid($kirby, 'Higawari: unknown sayamaapps.higawari.timezone ' . var_export($zone, true) . ' — using ' . Menu::DEFAULT_TIMEZONE . '.');
        return Menu::DEFAULT_TIMEZONE;
    }

    /** `sayamaapps.higawari.currency`: the symbol after a price. '' shows bare numbers ("7,50"). */
    public static function currency(App $kirby): string
    {
        $value = self::get($kirby, 'currency', self::DEFAULT_CURRENCY);
        if (is_string($value)) {
            return trim($value);
        }
        self::invalid($kirby, 'Higawari: sayamaapps.higawari.currency must be a string — using ' . self::DEFAULT_CURRENCY . '.');
        return self::DEFAULT_CURRENCY;
    }

    /** `sayamaapps.higawari.emptyText`: shown instead of the menu when none is valid. Null (default) shows nothing. */
    public static function emptyText(App $kirby): ?string
    {
        return self::text($kirby, 'emptyText');
    }

    /** `sayamaapps.higawari.note`: a line under the dishes, e.g. about prices or allergens. Null shows nothing. */
    public static function note(App $kirby): ?string
    {
        return self::text($kirby, 'note');
    }

    /**
     * Language of the plugin's text: `sayamaapps.higawari.language`, otherwise
     * the current language of a multi-language site, its default language, the
     * first two letters of Kirby's `locale`, and English last.
     */
    public static function language(App $kirby): string
    {
        $candidates = [self::get($kirby, 'language')];
        try {
            $candidates[] = $kirby->language()?->code();
            $candidates[] = $kirby->defaultLanguage()?->code();
        } catch (\Throwable) {
            // No languages configured.
        }
        $locale = $kirby->option('locale');
        if (is_array($locale)) {
            $locale = $locale[LC_ALL] ?? reset($locale);
        }
        $candidates[] = is_string($locale) ? substr($locale, 0, 2) : null;

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array(strtolower($candidate), I18n::LANGUAGES, true)) {
                return strtolower($candidate);
            }
        }
        return 'en';
    }

    /** Sets I18n's language for this request. Called before any text is produced. */
    public static function applyLanguage(App $kirby): void
    {
        I18n::setLanguage(self::language($kirby));
    }

    /** A trimmed, non-empty string, else null. Not an error when unset. */
    private static function text(App $kirby, string $key): ?string
    {
        $value = self::get($kirby, $key);
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * A broken option: with Kirby's `debug` option on it throws, otherwise it is
     * reported once per process and the caller uses the default.
     */
    private static function invalid(App $kirby, string $message): void
    {
        if ($kirby->option('debug') === true) {
            throw new \InvalidArgumentException($message);
        }
        if (!isset(self::$reported[$message])) {
            self::$reported[$message] = true;
            error_log($message);
        }
    }
}
