<?php

namespace Tests\Unit;

use App\Thread;
use Tests\TestCase;

class ThreadBodyLinkifyTest extends TestCase
{
    private function makeThread(string $body): Thread
    {
        $thread = new Thread();
        $thread->body = $body;

        return $thread;
    }

    public function test_get_clean_body_does_not_linkify_plain_urls()
    {
        $thread = $this->makeThread('<p>See https://example.com for details.</p>');

        $clean = $thread->getCleanBody();

        $this->assertStringNotContainsString('<a', $clean);
    }

    public function test_get_body_with_formated_links_linkifies_plain_urls()
    {
        $thread = $this->makeThread('<p>See https://example.com for details.</p>');

        $formatted = $thread->getBodyWithFormatedLinks();

        $this->assertStringContainsString('<a', $formatted);
        $this->assertStringContainsString('href="https://example.com"', $formatted);
    }

    public function test_get_body_with_formated_links_preserves_existing_anchor_tags()
    {
        $thread = $this->makeThread('<p>Click <a href="https://example.com">here</a>.</p>');

        $formatted = $thread->getBodyWithFormatedLinks();

        $this->assertStringContainsString('href="https://example.com"', $formatted);
        $this->assertSame(1, substr_count($formatted, '<a '));
    }

    /**
     * The customer-facing reply email must render the thread body through
     * getBodyWithFormatedLinks() (clean + linkify), not getCleanBody() alone,
     * or plain-text URLs the agent types arrive as non-clickable text in the
     * customer's inbox even though they render as links in the web UI.
     */
    public function test_customer_reply_template_linkifies_the_thread_body()
    {
        $view = file_get_contents(resource_path('views/emails/customer/reply_fancy.blade.php'));

        $this->assertStringContainsString('$thread->getBodyWithFormatedLinks()', $view);
        $this->assertStringNotContainsString('$thread->getCleanBody()', $view);
    }
}
