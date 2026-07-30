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
            '[fd00:ec2::254]' => '', // The AWS Metadata
            'fd00:ec2::254' => '', // The AWS Metadata
            '169.254.169.254' => '', // The AWS Metadata
            '[::ffff:169.254.169.254]' => '', // The AWS Metadata
            '::ffff:169.254.169.254' => '', // The AWS Metadata
            '0000:0000:0000:0000:0000:ffff:a9fe:a9fe' => '', // The AWS Metadata 
            '0x00000000000000000000ffffa9fea9fe' => '', // The AWS Metadata
            '[::1]' => '', // IPv6 loopback
            '::1' => '', // IPv6 loopback
            '[::ffff:127.0.0.1]' => '', // Special IPv6 address
            '::ffff:127.0.0.1' => '', // Special IPv6 address
            '0x00000000000000000000ffff7f000001' => '', // Special IPv6 address

            // Allowed.
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334' => 'https://[2001:0db8:85a3:0000:0000:8a2e:0370:7334]',
            'example.org' => 'https://example.org',
        ];
        foreach ($test_hosts as $host => $result) {
            $this->assertEquals($result, \Helper::checkUrlIpAndHost('https://'.$host));
        }
    }
}