<?php

namespace Tests\Unit;

use App\Conversation;
use Tests\TestCase;

/**
 * Covers the parts of the SortableCustomFields module (ARMS-33) that need
 * neither a database nor the (paid, not-installed-here) Custom Fields
 * module: the slug helper the sort-injection fix and CSS class naming both
 * depend on, and the HTML-escaping fix for custom-field name/value output.
 * See tests/Feature/SortableCustomFieldsTest.php for the DB-backed coverage
 * (the sort filter itself, and th_before_conv_number's order normalization).
 */
class SortableCustomFieldsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The module is not autoloaded while inactive — load directly.
        require_once __DIR__.'/../../Modules/SortableCustomFields/Providers/SortableCustomFieldsServiceProvider.php';
    }

    protected function bootModule()
    {
        (new \Modules\SortableCustomFields\Providers\SortableCustomFieldsServiceProvider(app()))->boot();
    }

    protected function provider()
    {
        return \Modules\SortableCustomFields\Providers\SortableCustomFieldsServiceProvider::class;
    }

    /**
     * The sort filter and the CSS-class builder both trust createSlug()'s
     * output to be a safe raw-SQL-identifier / CSS-class charset. If this
     * ever stopped holding, the SQL-injection fix in the sort filter (which
     * interpolates the slug into an alias without further escaping) would
     * regress.
     */
    public function test_slug_output_is_always_a_safe_identifier_charset()
    {
        $maliciousInputs = [
            "Priority'; DROP TABLE conversations; --",
            '<script>alert(1)</script>',
            'Multi Recipient ID',
            "O'Brien Priority",
            'għażiż priority', // Maltese, multibyte
            'name`with`backticks',
            'a"b\'c;d--e',
        ];

        foreach ($maliciousInputs as $input) {
            $underscoreSlug = $this->provider()::createSlug($input, '_');
            $hyphenSlug = $this->provider()::createSlug($input, '-');

            $this->assertMatchesRegularExpression('/^[a-z0-9_]*$/', $underscoreSlug, "underscore slug of: $input");
            $this->assertMatchesRegularExpression('/^[a-z0-9-]*$/', $hyphenSlug, "hyphen slug of: $input");
        }
    }

    public function test_slug_matches_expected_values()
    {
        $this->assertSame('priority', $this->provider()::createSlug('Priority', '_'));
        $this->assertSame('multi_recipient_id', $this->provider()::createSlug('Multi Recipient ID', '_'));
        $this->assertSame('multi-recipient-id', $this->provider()::createSlug('Multi Recipient ID', '-'));
    }

    /**
     * The stored-XSS fix: a custom field's value is echoed via e() in
     * conversations_table.td_before_conv_number. A mailbox admin controls
     * custom field values through ticket data, not through markup — a
     * value containing HTML must never render as markup for every agent
     * viewing the list.
     */
    public function test_td_before_conv_number_escapes_malicious_field_value()
    {
        $this->bootModule();

        $conversation = new Conversation();
        $conversation->custom_fields = [
            $this->fakeCustomField('Priority', '<img src=x onerror=alert(1)>'),
        ];

        $html = $this->captureAction('conversations_table.td_before_conv_number', $conversation);

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    /**
     * row_class only ever echoes the slugified class name (safe charset by
     * construction) — confirms a malicious value can't inject an extra
     * class/attribute into the row via this hook either.
     */
    public function test_row_class_only_emits_safe_slug_classes()
    {
        $this->bootModule();

        $conversation = new Conversation();
        $conversation->custom_fields = [
            $this->fakeCustomField('Priority', '"><script>alert(1)</script>'),
        ];

        $html = $this->captureAction('conversations_table.row_class', $conversation);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertMatchesRegularExpression('/^\s*cf_priority_[a-z0-9_]*\s*$/', $html);
    }

    protected function fakeCustomField($name, $text)
    {
        return new class($name, $text) {
            public $name;
            private $text;

            public function __construct($name, $text)
            {
                $this->name = $name;
                $this->text = $text;
            }

            public function getAsText()
            {
                return $this->text;
            }
        };
    }

    protected function captureAction($hook, ...$args)
    {
        ob_start();
        \Eventy::action($hook, ...$args);

        return ob_get_clean();
    }
}
