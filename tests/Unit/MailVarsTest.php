<?php

namespace Tests\Unit;

use App\Misc\Mail;
use Generator;
use Tests\TestCase;

class MailVarsTest extends TestCase
{
    /**
     * Retrieves an array of mocked data that can be passed to the {@see \App\Misc\Mail::replaceMailVars()} method's $data parameter.
     *
     * @return array
     */
    protected static function fakeMailData(): array
    {
        return [
            'conversation' => (object) [
                'subject'        => 'Re: Neque numquam velit consectetur!',
                'number'         => 123456,
                'customer_email' => 'customer.email@example.com',
            ],
            'mailbox' => new class
            {
                public $email = 'mailbox.email@example.com';
                public $name = 'Priority Support Requests';
                public function getMailFrom($fromUser): array
                {
                    return [
                        'address' => 'mailbox.email@example.com',
                        'name'    => 'Priority Support Requests',
                    ];
                }
            },
            'mailbox_from_name' => 'Priority Support Requests',
            'customer' => new class
            {
                public $last_name = 'Lebowski';
                public $company = 'Unemployed';
                public function getFullName(): string
                {
                    return 'Jeffrey Lebowski';
                }
                public function getFirstName(): string
                {
                    return 'Jeffrey';
                }
            },
            'user' => new class {
                public $phone = '(818) 123-456-7890';
                public $email = 'walter.sobchak@example.com';
                public $job_title = 'Proprietor';
                public $last_name = 'Sobchak';
                public function getFullName(): string
                {
                    return 'Walter Sobchak';
                }
                public function getFirstName(): string
                {
                    return 'Walter';
                }
                public function getPhotoUrl(): string
                {
                    return 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
                }
            },
        ];
    }

    /**
     * Returns a fake email text string containing all possible mail var codes.
     *
     * @return string
     */
    protected static function fakeMailText(): string
    {
        $text = [
            '<p>Hello {%customer.fullName%},</p>',
            '<p>Etincidunt magnam tempora adipisci amet ipsum consectetur. Tempora dolore sed neque velit ut sit.</p>',
            '<p>Subject: {%subject%}</p>',
            '<p>Conversation Number: {%conversation.number%}</p>',
            '<p>Customer Email: {%customer.email%}</p>',
            '<p>Mailbox FromName: {%mailbox.fromName%}</p>',
            '<p>Customer FirstName: {%customer.firstName%}</p>',
            '<p>Customer LastName: {%customer.lastName%}</p>',
            '<p>Customer Company: {%customer.company%}</p>',
            '<p>User FirstName: {%user.firstName%}</p>',
            '<p>User LastName: {%user.lastName%}</p>',
            '<p>Amet est adipisci ut. Numquam ipsum quaerat ipsum dolore velit porro non. Ut velit eius consectetur.</p>',
            '<br>---<br>',
            'Gratitude,<br>',
            '<a href="mailto:{%user.email%}">{%user.fullName%}</a><br>',
            '<a href="tel:{%user.phone%}">{%user.phone%}</a><br>',
            '{%user.jobTitle%} - {%mailbox.name%}<br>',
            '{%user.photoUrl%}<br>',
            '<a href="mailto:{%mailbox.email%}">{%mailbox.name%}</a><br>',
        ];

        return implode(PHP_EOL, $text);
    }

    /**
     * Tests the {@see \App\Misc\Mail::replaceMailVars()} method.
     *
     * @param array|string $expectedText       The expected result.
     * @param string       $inputText          The input text.
     * @param array        $data               An array of data to pass to the method.
     * @param bool         $escape             Whether to escape the result.
     * @param bool         $removeNonReplaced  Whether to remove non-replaced variables.
     *
     * @dataProvider providerReplaceMailVars
     */
    public function testReplaceMailVars(
        $expectedText,
        string $inputText,
        array $data = [],
        bool $escape = false,
        bool $removeNonReplaced = false
    ): void {
        $actual = Mail::replaceMailVars($inputText, $data, $escape, $removeNonReplaced);
        $this->assertEquals(
            is_array($expectedText) ? implode(PHP_EOL, $expectedText) : $expectedText,
            $actual
        );
    }

    /**
     * Data provider for {@see self::testReplaceMailVars()}.
     *
     * @return \Generator
     */
    public static function providerReplaceMailVars(): Generator
    {
        $noVars   = '<p>test</p>';
        $withVars = self::fakeMailText();
        $allData  = self::fakeMailData();

        yield 'no vars in string' => [
            $noVars,
            $noVars,
        ];

        yield 'no data' => [
            $withVars,
            $withVars,
        ];

        yield 'no data (remove_non_replaced)' => [
            [
                '<p>Hello ,</p>',
                '<p>Etincidunt magnam tempora adipisci amet ipsum consectetur. Tempora dolore sed neque velit ut sit.</p>',
                '<p>Subject: {%subject%}</p>', // Technically this is a bug but probably not in real situations, the regex doesn't accommodate merge codes without a dot.
                '<p>Conversation Number: </p>',
                '<p>Customer Email: </p>',
                '<p>Mailbox FromName: </p>',
                '<p>Customer FirstName: </p>',
                '<p>Customer LastName: </p>',
                '<p>Customer Company: </p>',
                '<p>User FirstName: </p>',
                '<p>User LastName: </p>',
                '<p>Amet est adipisci ut. Numquam ipsum quaerat ipsum dolore velit porro non. Ut velit eius consectetur.</p>',
                '<br>---<br>',
                'Gratitude,<br>',
                '<a href="mailto:"></a><br>',
                '<a href="tel:"></a><br>',
                ' - <br>',
                '<br>',
                '<a href="mailto:"></a><br>',
            ],
            $withVars,
            [],
            false,
            true,
        ];

        yield 'all data' => [
            [
                '<p>Hello Jeffrey Lebowski,</p>',
                '<p>Etincidunt magnam tempora adipisci amet ipsum consectetur. Tempora dolore sed neque velit ut sit.</p>',
                '<p>Subject: Re: Neque numquam velit consectetur!</p>',
                '<p>Conversation Number: 123456</p>',
                '<p>Customer Email: customer.email@example.com</p>',
                '<p>Mailbox FromName: Priority Support Requests</p>',
                '<p>Customer FirstName: Jeffrey</p>',
                '<p>Customer LastName: Lebowski</p>',
                '<p>Customer Company: Unemployed</p>',
                '<p>User FirstName: Walter</p>',
                '<p>User LastName: Sobchak</p>',
                '<p>Amet est adipisci ut. Numquam ipsum quaerat ipsum dolore velit porro non. Ut velit eius consectetur.</p>',
                '<br>---<br>',
                'Gratitude,<br>',
                '<a href="mailto:walter.sobchak@example.com">Walter Sobchak</a><br>',
                '<a href="tel:(818) 123-456-7890">(818) 123-456-7890</a><br>',
                'Proprietor - Priority Support Requests<br>',
                'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y<br>',
                '<a href="mailto:mailbox.email@example.com">Priority Support Requests</a><br>',
            ],
            $withVars,
            $allData,
        ];

        yield 'all data (escaped)' => [
            [
                '<p>Hello Jeffrey Lebowski,</p>',
                '<p>Etincidunt magnam tempora adipisci amet ipsum consectetur. Tempora dolore sed neque velit ut sit.</p>',
                '<p>Subject: Re: Neque numquam velit consectetur!</p>',
                '<p>Conversation Number: 123456</p>',
                '<p>Customer Email: customer.email@example.com</p>',
                '<p>Mailbox FromName: Priority Support Requests</p>',
                '<p>Customer FirstName: Jeffrey</p>',
                '<p>Customer LastName: Lebowski</p>',
                '<p>Customer Company: Unemployed</p>',
                '<p>User FirstName: Walter</p>',
                '<p>User LastName: Sobchak</p>',
                '<p>Amet est adipisci ut. Numquam ipsum quaerat ipsum dolore velit porro non. Ut velit eius consectetur.</p>',
                '<br>---<br>',
                'Gratitude,<br>',
                '<a href="mailto:walter.sobchak@example.com">Walter Sobchak</a><br>',
                '<a href="tel:(818) 123-456-7890">(818) 123-456-7890</a><br>',
                'Proprietor - Priority Support Requests<br>',
                'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&amp;f=y<br>',
                '<a href="mailto:mailbox.email@example.com">Priority Support Requests</a><br>',
            ],
            $withVars,
            $allData,
            true,
        ];

        yield 'partial data' => [
            [
                '<p>Hello Jeffrey Lebowski,</p>',
                '<p>Etincidunt magnam tempora adipisci amet ipsum consectetur. Tempora dolore sed neque velit ut sit.</p>',
                '<p>Subject: {%subject%}</p>',
                '<p>Conversation Number: {%conversation.number%}</p>',
                '<p>Customer Email: {%customer.email%}</p>',
                '<p>Mailbox FromName: {%mailbox.fromName%}</p>',
                '<p>Customer FirstName: Jeffrey</p>',
                '<p>Customer LastName: Lebowski</p>',
                '<p>Customer Company: Unemployed</p>',
                '<p>User FirstName: Walter</p>',
                '<p>User LastName: Sobchak</p>',
                '<p>Amet est adipisci ut. Numquam ipsum quaerat ipsum dolore velit porro non. Ut velit eius consectetur.</p>',
                '<br>---<br>',
                'Gratitude,<br>',
                '<a href="mailto:walter.sobchak@example.com">Walter Sobchak</a><br>',
                '<a href="tel:(818) 123-456-7890">(818) 123-456-7890</a><br>',
                'Proprietor - {%mailbox.name%}<br>',
                'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y<br>',
                '<a href="mailto:{%mailbox.email%}">{%mailbox.name%}</a><br>',
            ],
            $withVars,
            [
                'user'     => $allData['user'],
                'customer' => $allData['customer'],
            ],
        ];

        yield 'partial data (with removal)' => [
            [
                '<p>Hello ,</p>',
                '<p>Etincidunt magnam tempora adipisci amet ipsum consectetur. Tempora dolore sed neque velit ut sit.</p>',
                '<p>Subject: Re: Neque numquam velit consectetur!</p>',
                '<p>Conversation Number: 123456</p>',
                '<p>Customer Email: customer.email@example.com</p>',
                '<p>Mailbox FromName: Priority Support Requests</p>',
                '<p>Customer FirstName: </p>',
                '<p>Customer LastName: </p>',
                '<p>Customer Company: </p>',
                '<p>User FirstName: </p>',
                '<p>User LastName: </p>',
                '<p>Amet est adipisci ut. Numquam ipsum quaerat ipsum dolore velit porro non. Ut velit eius consectetur.</p>',
                '<br>---<br>',
                'Gratitude,<br>',
                '<a href="mailto:"></a><br>',
                '<a href="tel:"></a><br>',
                ' - Priority Support Requests<br>',
                '<br>',
                '<a href="mailto:mailbox.email@example.com">Priority Support Requests</a><br>',
            ],
            $withVars,
            [
                'conversation' => $allData['conversation'],
                'mailbox'     => $allData['mailbox'],
            ],
            false,
            true,
        ];
    }
}
