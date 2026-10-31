<?php

namespace Tests\Feature;

use App\Exceptions\Handler;
use Symfony\Component\HttpFoundation\Exception\ConflictingHeadersException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Tests\TestCase;

class MalformedRequestTest extends TestCase
{
    /**
     * Invalid HTTP method override (sent by vulnerability scanners)
     * must return "400 Bad Request" instead of "500 Server Error".
     */
    public function testInvalidMethodOverrideReturnsBadRequest()
    {
        $response = $this->call('POST', '/login', ['_method' => '__construct']);

        $response->assertStatus(400);
    }

    /**
     * Malformed requests must not be written to the log.
     */
    public function testMalformedRequestIsNotReported()
    {
        $handler = $this->app->make(Handler::class);

        $this->assertFalse($handler->shouldReport(new SuspiciousOperationException('Invalid Host "bad host".')));
        $this->assertFalse($handler->shouldReport(new SuspiciousOperationException('Invalid method override "__CONSTRUCT".')));
        $this->assertFalse($handler->shouldReport(new ConflictingHeadersException('The request has both a trusted "FORWARDED" header and a trusted "X_FORWARDED_FOR" header, conflicting with each other.')));
        $this->assertTrue($handler->shouldReport(new \Exception('Any other error')));
    }
}
