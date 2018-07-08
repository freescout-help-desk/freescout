## v1.3.1 (2016-05-18)

- Normalizer fixes from @gitlost
- fix tests for php 5.5.35/5.6.21/7.0.6

## v1.3.0 (2015-12-15)

- add shim for mb_convert_variables
- marked all shims as @internal
- test on appveyor
- a few fixes in iconv and mbstring shims
- cleanup refacto for preparing v2 based on symfony-polyfill

## v1.2.6 (2015-12-15)

- fix compat with symfony-polyfill

## v1.2.5 (2015-10-14)

- handle the third argument of mb_convert_encoding() being an array
- add license files

## v1.2.4 (2015-06-29)

- trigger silenced deprecation notices when shims are in use
- fix mb_strrpos() shim registration
- fix .gitattributes

## v1.2.3 (2015-06-25)

- fix mb_strrpos shim with negative offset
- sync tests with latest PHP/HHVM behaviors
- remove PHP7/HHVM from allowed failures
- move to PSR-1+2+4
- mv class/ src/

## v1.2.2 (2015-04-26)

- Fix ucwords to be functionally the same as in-built PHP version
- Fix iconv_set_encoding deprecation notice in PHP 5.6.0
- remove legacy test for HHVM/PHP7
- mb_parse_str() should have no return value

## v1.2.1 (2015-01-28)

- fix double declaration in mbstring shim

## v1.2.0 (2015-01-12)

- add u::strwidth() to get the width of a string when printed on a terminal
- add more mbstring shims
- add a note about https://bugs.php.net/65358
- fail properly when COM is not loaded
- fallback on stat() when lstat() fails

## v1.2.0-beta (2014-08-05)

- add best-fit mappings for UTF-8 to Code Page approximations
- add portable Unicode filesystem access under Windows and other OSes

## v1.1.31 (2015-12-15)

- fix compat with symfony-polyfill

## v1.1.30 (2015-06-29)

- fix mb_strrpos shim with negative offset

## v1.1.29 (2015-04-26)

- fix ucwords to be functionally the same as in-built PHP version
- fix iconv_set_encoding deprecation notice in PHP 5.6.0
- remove legacy test for HHVM/PHP7

## v1.1.28 (2015-01-12)

- fix mbstring shim for html-entities

## v1.1.27 (2015-01-11)

- update to Unicode 7.0
- fix iconv shim compat layer

## v1.1.26 (2014-11-08)

- tweak composer.json

## v1.1.25 (2014-08-05)

- update travis matrix
- add composer branch alias

## v1.1.24 (2014-06-17)

- update tests for latest HHVM fixes
- move legacy GRAPHEME_CLUSTER_RX version to Intl shim

## v1.1.23 (2014-05-22)

- enable tests for PHP 5.6
- remove HHVM from allowed failures

## v1.1.22 (2014-05-06)

- fix #19: don't call ini_set() when not required and gain compat with PHP5.6

## v1.1.21 (2014-03-26)

- fix #18 u::wordwrap() now relies on native behavior

## v1.1.20 (2014-03-01)
## v1.1.19 (2014-03-01)

- fix mb_regex_encoding() being disabled on some hosting providers

## v1.1.18 (2014-02-02)

- require PCRE>=7.3, the first that correctly checks UTF-8 validity
- enable HHVM on Travis CI

## v1.1.17 (2014-01-02)

- enable Travis CI and SensioLabsInsight
- add shims for mb_check_encoding, mb_detect_encoding, mb_detect_order,
  mb_language and mb_encoding_aliases
- mbstring shim fix: alias UTF8 to UTF-8
- more tests

## v1.1.16 (2013-12-06)

- fix $_FILES bootup filtering
- fix mbstring shim behavior with invalid utf8 strings

## v1.1.15 (2013-11-23)

- u::toAscii() is now locale sensitive and allows a substitution character
- use LSB for more extension openness
- handle null for mb_substr() shim length as in PHP 5.4.8
- fix casts to string
- fix mbstring MB_CASE_TITLE shim on edge case
- small optimizations
- add a changelog

## v1.1.14 (2013-11-04)

- set default_charset to UTF-8 at bootup
- remove bootup PCRE warning
- fix iconv internal_encoding shim
- fix bootup dependencies
- add tests for normalizers consts
- readme update

## v1.1.13 (2013-10-11)

- new u::filter(): normalizes to UTF-8 NFC, converting from CP-1252 when needed
- new u::json_decode(), u::filter_input() and u::filter_input_array() for NFC safeness
- reference Unicode 6.3
- more tests
- readme update

## v1.1.12 (2013-10-03)

- new Patchwork\TurkishUtf8 class extends Patchwork\Utf8 with Turkish specifics
- expose Patchwork\Utf8\Bootup::filterString() for UF-8 NFC strings normalization
- normalize inputs EOL to work around https://bugs.php.net/65732
- update composer.json

## v1.1.11 (2013-08-19)

- updates related to PHP bugs 52211 and 61860
- fixes and tests for iconv shim
- fixes and tests for mbstring shim

## v1.1.10 (2013-08-13)

- update .gitattributes export-ignore
- fixes and tests for intl::grapheme_extract() shim
- fixes and tests for iconv shim
- fixes and tests for mbstring shim

## v1.1.9 (2013-08-04)

- know that PHP bug 61860 has been fixed in 5.5.1
- fix intl::grapheme_strlen() shim on edge case
- fix case sensitive encoding checks for mbstring shim
- some more fixes, tests and optimizations

## v1.1.8 since v1.1.0 (2013-05-24)

- filter leading combining chars in inputs for NFC safeness
- fixes, tests and optimizations
- readme update

## v1.1.0 (2013-04-18)

- PSR-0 autoloading and explicit bootup configuration is now required

## v1.0.6 since v1.0.0 (2013-04-22)

- add extra characters for ASCII transliterations
- move bootup stages in namespaced functions for greater modularity
- NFC normalization for autoglobal inputs
- better setlocale() initialization
- fix fatal error caused by multiple bootup inclusion
- fix bootup

## v1.0.0 (2012-10-15)

- first official release of a work started in 2007
- Apache v2.0 / GPL v2.0 dual-licensed
- PHP portability implementations for mbstring, iconv, intl grapheme_*() and utf8_encode/decode()
- Unicode compliant and portable Normalizer
- grapheme clusters aware UTF-8 handling string functions replica
- PHP runtime environment configuration for UTF-8
- extra functions for UTF-8 validity checks, transliterations and case folding
- covered by unit tests
