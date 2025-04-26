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
    protected static function fakeMailText(bool $withFb = false): string
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
            '<img src="{%user.photoUrl%}"><br>',
            '<a href="mailto:{%mailbox.email%}">{%mailbox.name%}</a><br>',
        ];

        $text = implode(PHP_EOL, $text);

        if ($withFb) {
            $fallbacks = [
                '{%subject%}'             => 'Re: Neque numquam velit consectetur!',
                '{%conversation.number%}' => '0001234',
                '{%customer.email%}'      => '', // No fallback, remove it.
                '{%mailbox.email%}'       => 'noreply@example.com',
                '{%mailbox.name%}'        => 'No Reply',
                '{%mailbox.fromName%}'    => 'Support Team',
                '{%customer.fullName%}'   => 'there',
                '{%customer.firstName%}'  => 'buddy',
                '{%customer.lastName%}'   => '', // No fallback, remove it.
                '{%customer.company%}'    => 'A Business Company Ltd.',
                '{%user.fullName%}'       => 'Your Team',
                '{%user.firstName%}'      => 'friend',
                '{%user.phone%}'          => '(123) 456-7890',
                '{%user.email%}'          => 'noreply@example.com',
                '{%user.jobTitle%}'       => 'Support Engineer',
                '{%user.lastName%}'       => '', // No fallback, remove it.
                '{%user.photoUrl%}'       => 'https://place.dog/200/200',
            ];

            foreach ($fallbacks as $var => $fallback) {
                $baseVar = str_replace(['{%', '%}'], '', $var);
                $text = str_replace($var, "{%{$baseVar},fallback={$fallback}%}", $text);
            }
        }

        return $text;
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
        $noVars            = '<p>test</p>';
        $textNoFallback    = self::fakeMailText();
        $textWithFallbacks = self::fakeMailText(true);
        $allData           = self::fakeMailData();

        yield 'no vars in string' => [
            $noVars,
            $noVars,
        ];

        yield 'no data' => [
            $textNoFallback,
            $textNoFallback,
        ];

        yield 'invalid variables' => [
            '<p>{%invalid.mergeVar%}</p>',
            '<p>{%invalid.mergeVar%}</p>',
        ];

        yield 'no data (remove_non_replaced)' => [
            [
                '<p>Hello ,</p>',
                '<p>Etincidunt magnam tempora adipisci amet ipsum consectetur. Tempora dolore sed neque velit ut sit.</p>',
                '<p>Subject: </p>',
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
                '<img src=""><br>',
                '<a href="mailto:"></a><br>',
            ],
            $textNoFallback,
            [],
            false,
            true,
        ];

        $expectedWithAllData = [
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
            '<img src="https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y"><br>',
            '<a href="mailto:mailbox.email@example.com">Priority Support Requests</a><br>',
        ];
        yield 'all data' => [$expectedWithAllData, $textNoFallback, $allData];

        yield 'all data with fallbacks' => [$expectedWithAllData, $textWithFallbacks, $allData];

        yield 'show all fallbacks' => [
            [
                '<p>Hello there,</p>',
                '<p>Etincidunt magnam tempora adipisci amet ipsum consectetur. Tempora dolore sed neque velit ut sit.</p>',
                '<p>Subject: Re: Neque numquam velit consectetur!</p>',
                '<p>Conversation Number: 0001234</p>',
                '<p>Customer Email: </p>',
                '<p>Mailbox FromName: Support Team</p>',
                '<p>Customer FirstName: buddy</p>',
                '<p>Customer LastName: </p>',
                '<p>Customer Company: A Business Company Ltd.</p>',
                '<p>User FirstName: friend</p>',
                '<p>User LastName: </p>',
                '<p>Amet est adipisci ut. Numquam ipsum quaerat ipsum dolore velit porro non. Ut velit eius consectetur.</p>',
                '<br>---<br>',
                'Gratitude,<br>',
                '<a href="mailto:noreply@example.com">Your Team</a><br>',
                '<a href="tel:(123) 456-7890">(123) 456-7890</a><br>',
                'Support Engineer - No Reply<br>',
                '<img src="https://place.dog/200/200"><br>',
                '<a href="mailto:noreply@example.com">No Reply</a><br>',
            ],
            $textWithFallbacks
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
                '<img src="https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&amp;f=y"><br>',
                '<a href="mailto:mailbox.email@example.com">Priority Support Requests</a><br>',
            ],
            $textNoFallback,
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
                '<img src="https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y"><br>',
                '<a href="mailto:{%mailbox.email%}">{%mailbox.name%}</a><br>',
            ],
            $textNoFallback,
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
                '<img src=""><br>',
                '<a href="mailto:mailbox.email@example.com">Priority Support Requests</a><br>',
            ],
            $textNoFallback,
            [
                'conversation' => $allData['conversation'],
                'mailbox'     => $allData['mailbox'],
            ],
            false,
            true,
        ];

        yield 'partial data with fallbacks' => [
            [
                '<p>Hello there,</p>',
                '<p>Etincidunt magnam tempora adipisci amet ipsum consectetur. Tempora dolore sed neque velit ut sit.</p>',
                '<p>Subject: Re: Neque numquam velit consectetur!</p>',
                '<p>Conversation Number: 123456</p>',
                '<p>Customer Email: customer.email@example.com</p>',
                '<p>Mailbox FromName: Priority Support Requests</p>',
                '<p>Customer FirstName: buddy</p>',
                '<p>Customer LastName: </p>',
                '<p>Customer Company: A Business Company Ltd.</p>',
                '<p>User FirstName: friend</p>',
                '<p>User LastName: </p>',
                '<p>Amet est adipisci ut. Numquam ipsum quaerat ipsum dolore velit porro non. Ut velit eius consectetur.</p>',
                '<br>---<br>',
                'Gratitude,<br>',
                '<a href="mailto:noreply@example.com">Your Team</a><br>',
                '<a href="tel:(123) 456-7890">(123) 456-7890</a><br>',
                'Support Engineer - Priority Support Requests<br>',
                '<img src="https://place.dog/200/200"><br>',
                '<a href="mailto:mailbox.email@example.com">Priority Support Requests</a><br>',
            ],
            $textWithFallbacks,
            [
                'conversation' => $allData['conversation'],
                'mailbox'     => $allData['mailbox'],
            ],
        ];
    }

    /**
     * Tests {@see \App\Misc\Mail::replaceMailVars()} with custom mail vars added via variables.
     */
    public function testReplaceMailVarsCustomVars(): void
    {
        // No data so no replacement.
        $this->assertEquals('{%custom.currentYear%}', Mail::replaceMailVars('{%custom.currentYear%}', [], false, false));

        // Use fallback if no replacement value is available.
        $this->assertEquals('2021', Mail::replaceMailVars('{%custom.currentYear,fallback=2021%}', [], false, false));

        $addReplacements = function($vars) {
            $vars['{%custom.currentYear%}'] = '2025';
            return $vars;
        };
        \Eventy::addFilter('mail_vars.replace', $addReplacements);

        // No fallback but data is present.
        $this->assertEquals('2025', Mail::replaceMailVars('{%custom.currentYear%}', [], false, false));

        // Data is present so no fallback is used.
        $this->assertEquals('2025', Mail::replaceMailVars('{%custom.currentYear,fallback=2021%}', [], false, false));

        \Eventy::removeFilter('mail_vars.replace', $addReplacements);
    }
}
