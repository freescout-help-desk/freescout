<?php
namespace Tests\Unit;

use Tests\TestCase;

class SsrfProtectionsTest extends TestCase
{
    public function testCheckUrlIpAndHost(): void
    {
        $test_hosts = [
            // Disallowed.
            '127.0.0.1' => '',
            '0x7f000001' => '',
            '[fd00:ec2::254]' => '', // The AWS Instance Metadata Service (IMDS) IPv6 address
            '[::1]' => '', // IPv6 loopback
            // Allowed.
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334' => 'https://2001:0db8:85a3:0000:0000:8a2e:0370:7334',
        ];
        foreach ($test_hosts as $host => $result) {
            $this->assertEquals($result, \Helper::checkUrlIpAndHost('https://'.$host));
        }
    }
}