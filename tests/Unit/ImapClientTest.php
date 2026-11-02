<?php

namespace Tests\Unit;

use Tests\TestCase;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Connection\Protocols\ImapProtocol;

class ImapClientTest extends TestCase
{
    /**
     * When the server rejects SELECT, no folder is selected anymore,
     * so the next openFolder() has to send SELECT again.
     *
     * https://github.com/freescout-help-desk/freescout/issues/5786
     */
    public function testFolderIsSelectedAgainAfterRejectedSelect()
    {
        list($client_stream, $server_stream) = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        // Responses of the server, read by the client one after another.
        fwrite($server_stream,
            "TAG1 NO [UNAVAILABLE] Temporary failure\r\n"
            ."* 3 EXISTS\r\n"
            ."TAG2 OK [READ-WRITE] SELECT completed\r\n"
        );

        $protocol = new class extends ImapProtocol {
            public function setStream($stream)
            {
                $this->stream = $stream;
            }

            public function connected(): bool
            {
                return (bool)$this->stream;
            }

            public function logout(): bool
            {
                $this->stream = null;
                return true;
            }
        };
        $protocol->setStream($client_stream);

        $client = (new ClientManager(config('imap')))->account('default');
        $property = new \ReflectionProperty($client, 'connection');
        $property->setAccessible(true);
        $property->setValue($client, $protocol);

        \Log::shouldReceive('error')
            ->once()
            ->with('IMAP folder could not be selected. Folder: INBOX; server response: NO [UNAVAILABLE] Temporary failure');

        $this->assertFalse($client->openFolder('INBOX'));
        $this->assertSame('NO [UNAVAILABLE] Temporary failure', $client->getLastError());

        $this->assertSame(['exists' => 3], $client->openFolder('INBOX'));
        $this->assertSame('', $client->getLastError());

        // The folder is selected now, so there is no need to select it again.
        $this->assertTrue($client->openFolder('INBOX'));

        stream_set_blocking($server_stream, false);
        $this->assertSame(
            "TAG1 SELECT \"INBOX\"\r\nTAG2 SELECT \"INBOX\"\r\n",
            fread($server_stream, 8192)
        );
    }
}
