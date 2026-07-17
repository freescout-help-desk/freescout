<?php

namespace Modules\TestEmailGuard\Services;

/**
 * Anonymises email addresses for the test environment (ARMS-16).
 *
 * The transform folds the original domain into the local part so that
 * distinct addresses stay distinct after anonymisation:
 *
 *     tanti.omar@gmail.com  →  tanti.omar+gmail.com@example.com
 *
 * A flat "replace the domain" would merge john@gmail.com and john@yahoo.com
 * into one customer record; folding keeps the mapping unique. example.com is
 * IANA-reserved (RFC 2606) so an anonymised address can never deliver.
 *
 * Two output modes:
 *  - anonymize()         → local+domain@example.com — for data at rest
 *                          (seeded/imported customer records).
 *  - rewriteRecipient()  → send-time target. Same as anonymize() unless a
 *                          sink mailbox is configured (TEST_EMAIL_GUARD_SINK),
 *                          in which case rewritten mail is delivered into
 *                          the sink so it can be inspected instead of
 *                          bouncing. Default sink mode is "plain" (the bare
 *                          sink address; originals kept in display names
 *                          and X-TestEmailGuard-Original-To — see sinkMode()); "plus" mode
 *                          plus-addresses per customer:
 *                          armssink+tanti.omar+gmail.com@threls.onmicrosoft.com
 */
class EmailAnonymizer
{
    const SAFE_DOMAIN = 'example.com';

    /**
     * RFC 5321 limit for the local part of an address.
     */
    const LOCAL_PART_MAX = 64;

    const HASH_LENGTH = 10;

    /**
     * Domains whose recipients receive real mail, untouched.
     * Comparison is by exact domain, not suffix — mail to a lookalike
     * subdomain must not slip through the guard.
     *
     * Read via the module config (Config/config.php, merged by the service
     * provider) with a direct env fallback for contexts where the provider
     * is not registered. Both are null-safe: a missing value falls back to
     * the built-in ARMS/Threls defaults, so a cached config that cannot see
     * .env still leaves the guard guarding.
     */
    public static function allowedDomains()
    {
        $env = config('testemailguard.allow_domains') ?: env('TEST_EMAIL_GUARD_ALLOW_DOMAINS');

        $domains = $env ? explode(',', $env) : ['arms.com.mt', 'threls.com'];

        return array_values(array_filter(array_map(function ($domain) {
            return strtolower(trim($domain));
        }, $domains)));
    }

    /**
     * Optional sink mailbox for send-time rewrites (a real mailbox the
     * team controls). Empty/invalid → example.com mode (sends will bounce).
     */
    public static function sink()
    {
        $sink = config('testemailguard.sink') ?: env('TEST_EMAIL_GUARD_SINK');

        if (!$sink || strpos($sink, '@') === false) {
            return null;
        }

        return strtolower(trim($sink));
    }

    /**
     * How rewritten mail is addressed into the sink.
     *
     * "plain" (default): the bare sink address — cannot fail to resolve on
     * any mail host; the original recipient is carried in the display name
     * and an X-TestEmailGuard-Original-To header (see the service provider).
     *
     * "plus": plus-addressed (sink_local+original_local+domain@sink_domain)
     * so each customer gets a distinct sink address — requires the sink's
     * host to accept plus addressing, which Exchange tenants sometimes
     * refuse (that lesson cost an afternoon; see the README probe note).
     */
    public static function sinkMode()
    {
        $mode = config('testemailguard.sink_mode') ?: env('TEST_EMAIL_GUARD_SINK_MODE');

        return strtolower(trim($mode ?? '')) === 'plus' ? 'plus' : 'plain';
    }

    public static function isAllowed($email)
    {
        $domain = self::domainOf($email);

        return $domain !== null && in_array($domain, self::allowedDomains());
    }

    /**
     * Anonymise an address for storage: local+domain@example.com.
     * Allow-listed and already-anonymised addresses pass through unchanged,
     * so the transform is idempotent and safe to re-run.
     */
    public static function anonymize($email)
    {
        $email = mb_strtolower(trim($email ?? ''), 'UTF-8');
        $domain = self::domainOf($email);

        if ($domain === null || $domain === self::SAFE_DOMAIN || self::isAllowed($email)) {
            return $email;
        }

        $local = substr($email, 0, strrpos($email, '@'));

        return self::capLocal($local.'+'.$domain, $email).'@'.self::SAFE_DOMAIN;
    }

    /**
     * Send-time rewrite target for a non-allow-listed recipient.
     */
    public static function rewriteRecipient($email)
    {
        $sink = self::sink();

        if (!$sink) {
            return self::anonymize($email);
        }

        $email = mb_strtolower(trim($email ?? ''), 'UTF-8');
        $domain = self::domainOf($email);

        if ($domain === null || self::isAllowed($email)) {
            return $email;
        }

        // Plain mode: every rewritten recipient becomes the bare sink
        // address (nothing a mail host can fail to resolve). Who the mail
        // was for is preserved by the provider in the display name and an
        // X-TestEmailGuard-Original-To header, and the subject's [#ticket] links back to
        // the conversation.
        if (self::sinkMode() === 'plain') {
            return $sink;
        }

        $sink_local = substr($sink, 0, strrpos($sink, '@'));
        $sink_domain = substr($sink, strrpos($sink, '@') + 1);

        // Already sunk — keep idempotent.
        if ($domain === $sink_domain && strpos($email, $sink_local.'+') === 0) {
            return $email;
        }

        $local = substr($email, 0, strrpos($email, '@'));

        // An already-anonymised address carries its folded domain in the
        // local part — don't fold example.com in on top of it.
        $folded = ($domain === self::SAFE_DOMAIN) ? $local : $local.'+'.$domain;

        return self::capLocal($sink_local.'+'.$folded, $email).'@'.$sink_domain;
    }

    /**
     * Recover the original address from an anonymised one by splitting the
     * folded local part on its last "+" (unambiguous — domains cannot
     * contain "+"). Returns null when the input is not an anonymised
     * address, or when the hash fallback was used (the one case that is
     * not parseable — see isReversible()).
     */
    public static function reverse($anonymized)
    {
        $anonymized = mb_strtolower(trim($anonymized ?? ''), 'UTF-8');

        if (self::domainOf($anonymized) !== self::SAFE_DOMAIN) {
            return null;
        }

        $local = substr($anonymized, 0, strrpos($anonymized, '@'));
        $pos = strrpos($local, '+');
        if ($pos === false) {
            return null;
        }

        $original_domain = substr($local, $pos + 1);

        // A hash-fallback suffix is hex with no dot — not a real domain.
        if (strpos($original_domain, '.') === false) {
            return null;
        }

        return substr($local, 0, $pos).'@'.$original_domain;
    }

    /**
     * Whether anonymize() output can be reversed back to this address.
     * False when the folded local part would exceed the RFC limit (i.e.
     * the original address itself is longer than 64 characters) and the
     * transform must drop to the hash fallback, or when the domain has no
     * dot (user@localhost) — a folded dotless domain is indistinguishable
     * from a hash-fallback suffix, so reverse() refuses to parse it.
     */
    public static function isReversible($email)
    {
        $email = mb_strtolower(trim($email ?? ''), 'UTF-8');
        $domain = self::domainOf($email);

        // Addresses anonymize() leaves untouched are trivially "reversible".
        if ($domain === null || $domain === self::SAFE_DOMAIN || self::isAllowed($email)) {
            return true;
        }

        // The folded local part (local+domain) has exactly the original
        // address's length — the "@" becomes the "+".
        return strlen($email) <= self::LOCAL_PART_MAX
            && strpos($domain, '.') !== false;
    }

    /**
     * Keep the local part within the RFC limit; past it, drop to a stable
     * short-hash suffix (uniqueness preserved, reversibility knowingly not).
     *
     * The hash covers the input as given, so for a >64-char original the
     * sink form of the raw address differs from the sink form of its stored
     * anonymised address (whose own hash suffix is all that survives of the
     * original). Unavoidable — a hash-fallback form cannot be reversed to
     * recover the original — and harmless in practice: each path stays
     * unique per customer, and the runbook (scrub before testing) means all
     * sends see the stored form.
     */
    protected static function capLocal($local, $original)
    {
        if (strlen($local) <= self::LOCAL_PART_MAX) {
            return $local;
        }

        $hash = substr(sha1($original), 0, self::HASH_LENGTH);

        // The RFC limit is octets, so the budget is measured in bytes —
        // mb_strcut trims to the byte budget without splitting a UTF-8
        // character in half (local parts can be internationalised).
        return mb_strcut($local, 0, self::LOCAL_PART_MAX - self::HASH_LENGTH - 1, 'UTF-8').'+'.$hash;
    }

    protected static function domainOf($email)
    {
        $email = $email ?? '';
        $pos = strrpos($email, '@');

        if ($pos === false || $pos === 0 || $pos === strlen($email) - 1) {
            return null;
        }

        return strtolower(substr($email, $pos + 1));
    }
}
