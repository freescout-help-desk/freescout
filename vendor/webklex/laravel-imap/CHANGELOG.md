# Changelog

All notable changes to `webklex/laravel-imap` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [UNRELEASED]
### Fixed
- NaN

### Added
- NaN

### Affected Classes
- NaN

## [1.2.7] - 2018-08-06
### Fixed
- Broken non-latin characters in subjects and attachments  #133

### Added
- Required php extensions added to composer.json

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Attachment::class](src/IMAP/Attachment.php)

## [1.2.6] - 2018-08-04
### Fixed
- Message subjects and attachment  names will now be decoded with a guessed encoding #97 #107

### Added
- Expunge option added to critical imap operations

### Affected Classes
- [Folder::class](src/IMAP/Folder.php)
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)
- [Attachment::class](src/IMAP/Attachment.php)

## [1.2.5] - 2018-07-30
### Fixed
- Fixing undefined index error if associative config array isn't properly filled #131

### Affected Classes
- [LaravelServiceProvider::class](src/IMAP/Providers/LaravelServiceProvider.php)

## [1.2.4] - 2018-07-26
### Fixed
- fetch_flags default set to true on all methods
- Missing fetch_flags attribute added

### Added
- Folder::query() aliases added
- Priority fetching added

### Affected Classes
- [Folder::class](src/IMAP/Folder.php)
- [Message::class](src/IMAP/Message.php)
- [Query::class](src/IMAP/Query/Query.php)

## [1.2.3] - 2018-07-23
### Fixed
- Config loading fixed and moved to a custom solution
- Set Encryption type correctly #128
- Moving a message takes now a uid #127

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)

## [1.2.2] - 2018-07-22
### Fixed
- Don't set the charset if it isn't used - prevent strange outlook mail server errors #100
- Protocol option added -minor Fix #126

### Added
- Query extended with markAsRead() and leaveUnread() methods

### Affected Classes
- [Query::class](src/IMAP/Query/Query.php)
- [Client::class](src/IMAP/Client.php)

## [1.2.1] - 2018-07-22
### Added
- WhereQuery aliases for all where methods added

### Affected Classes
- [WhereQuery::class](src/IMAP/Query/WhereQuery.php)

## [1.2.0] - 2018-07-22
### Fixed
- Charset error fixed #109
- Potential imap_close() error fixed #118
- Plain text attachments have a content type of other/plain of text/plain #119
- Carbon Exception Parse Data #45 

### Added
- Protocol option added #124
- Message collection key option added
- Message collection sorting option added
- Search Query functionality added
- Flag collection added
- Search methods updated

### Affected Classes
- [Folder::class](src/IMAP/Folder.php)
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)
- [Attachment::class](src/IMAP/Attachment.php)
- [Query::class](src/IMAP/Query/Query.php) [WhereQuery::class](src/IMAP/Query/WhereQuery.php)

## [1.1.1] - 2018-05-04
### Fixed
- Force to add a space between criteria in search query, otherwise no messages are fetched. Thanks to @cent89

### Added
- Attachment::getMimeType() and Attachment::getExtension() added

### Affected Classes
- [Folder::class](src/IMAP/Folder.php)
- [Attachment::class](src/IMAP/Attachment.php)

## [1.1.0] - 2018-04-24
### Fixed
- Client::createFolder($name) fixed #91
- Versions will now follow basic **Semantic Versioning** guidelines (MAJOR.MINOR.PATCH) 

### Added
- Connection validation added
- Client::renameFolder($old_name, $new_name) and Client::deleteFolder($name) methods added #91
- Find the folder containing a message #92
- Change all incoming encodings to iconv() supported ones #94

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)

## [1.0.5.9] - 2018-04-15
### Added
- Handle Carbon instances in message search criteria #82
- $message->getRawBody() throws Exception #88
- Request: add getReferences method to Message class #83

### Affected Classes
- [Folder::class](src/IMAP/Folder.php)
- [Message::class](src/IMAP/Message.php)

## [1.0.5.8] - 2018-04-08
### Added
- Specify provider name when publishing the config #80
- Enable package discovery #81

## [1.0.5.7] - 2018-04-04
### Fixed
- Added option for optional attachment download #76
- Added option for optional body download
- Renamed "fetch" parameters
- hasAttachment() method added

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Folder::class](src/IMAP/Folder.php)
- [Client::class](src/IMAP/Client.php)

## [1.0.5.6] - 2018-04-03
### Fixed
- More explicit date validation statements
- Resolving getMessage is not returning the body of the message #75

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Folder::class](src/IMAP/Folder.php)

## [1.0.5.5] - 2018-03-28
### Fixed
- New validation rule for a new invalid date format added (Exception Parse Data #45) 
- Default config keys are now fixed (Confusing default configuration values #66)

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Client::class](src/IMAP/Client.php)

## [1.0.5.4] - 2018-03-27
### Fixed
- Clear error stack before imap_close #72

### Affected Classes
- [Client::class](src/IMAP/Client.php)

## [1.0.5.3] - 2018-03-18
### Added
- FolderCollection::class added
- Comments updated

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [Folder::class](src/IMAP/Folder.php)
- [FolderCollection::class](src/IMAP/Support/FolderCollection.php)

## [1.0.5.2] - 2018-03-18
### Added
- Attachment::save() method added
- Unnecessary methods declared deprecated

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)
- [Folder::class](src/IMAP/Folder.php)
- [Attachment::class](src/IMAP/Attachment.php)

## [1.0.5.1] - 2018-03-16
### Added
- Message collection moved to Support
- Attachment collection added
- Attachment class added

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Folder::class](src/IMAP/Folder.php)
- [Attachment::class](src/IMAP/Attachment.php)
- [MessageCollection::class](src/IMAP/Support/MessageCollection.php)
- [AttachmentCollection::class](src/IMAP/Support/AttachmentCollection.php)

## [1.0.5.0] - 2018-03-16
### Added
- Message search method added
- Basic pagination added
- Prevent automatic body parsing (will be default within the next major version (2.x))
- Unified MessageCollection::class added
- Several small improvements and docs added
- Implementation of the "get raw body" pull request [#59](https://github.com/Webklex/laravel-imap/pull/59)
- Get a single message by uid

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Client::class](src/IMAP/Client.php)
- [Folder::class](src/IMAP/Folder.php)
- [MessageCollection::class](src/IMAP/Support/MessageCollection.php)
- [MessageSearchValidationException::class](src/IMAP/Exceptions/MessageSearchValidationException.php)

## [1.0.4.2] - 2018-03-15
### Added
- Support message delivery status [#47](https://github.com/Webklex/laravel-imap/pull/47)

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.4.1] - 2018-02-14
### Added
- Enable support to get In-Reply-To property from Message header. [#56](https://github.com/Webklex/laravel-imap/pull/56)

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.4.0] - 2018-01-28
### Added
- Set and unset flags added `$oMessage->setFlag(['Seen', 'Spam']) or $oMessage->unsetFlag('Spam')`
- Get raw header string `$oMessage->getHeader()`
- Get additional header information `$oMessage->getHeaderInfo()`

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.3.11] - 2018-01-01
### Added
- New experimental function added [#48 How can I specify a single folder?](https://github.com/Webklex/laravel-imap/issues/48)

### Affected Classes
- [Client::class](src/IMAP/Client.php)

## [1.0.3.10] - 2018-01-01
### Fixed
- Ignore inconvertible chars in order to prevent sudden code exists

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.3.9] - 2017-12-03
### Fixed
- #45 DateTime::__construct(): Failed to parse time string (...)

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.3.8] - 2017-11-24
### Fixed
- #41 imap_expunge(): supplied resource is not a valid imap resource
- #40 mb_convert_encoding(): Illegal character encoding specified

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.3.7] - 2017-11-05
### Fixed
- Fix assignment ```msgno``` to ```uid``` regardless of ```fetch_options``` is set in config 
- Disposition is checked in case of malformed mail attachments

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.3.6] - 2017-10-24
### Added
- A method to get only unread messages from email folders to [Client::class](src/IMAP/client.php)

## [1.0.3.5] - 2017-10-18
### Fixed
- Messageset issue resolved [#31](https://github.com/Webklex/laravel-imap/issues/31)

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Client::class](src/IMAP/Client.php)

## [1.0.3.4] - 2017-10-04
### Fixed
- E-mails parsed without a content type of multipart present no body [#27](https://github.com/Webklex/laravel-imap/pull/27)
- Do not resolve uid to msgno if using FT_UID [#25](https://github.com/Webklex/laravel-imap/pull/25)

### Affected Classes
- [Message::class](src/IMAP/Message.php)


## [1.0.3.3] - 2017-09-22
### Fixed
- General code style and documentation

### Added
- several getter methods added to [Message::class](src/IMAP/Message.php)

### Affected Classes
- All

## [1.0.3.2] - 2017-09-07
### Fixed
- Fix implode error in Client.php, beacause imap_errors() can return FALSE instead of an array

### Added
- FT_UID changed to $this->options which references to `imap.options.fetch`

### Affected Classes
- [Message::class](src/IMAP/Message.php)
- [Client::class](src/IMAP/Client.php)

## [1.0.3.1] - 2017-09-05
### Added
- getConnection method added
- Using a bit more fail save uid / msgNo by calling imap_msgno()

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)

## [1.0.3.0] - 2017-09-01
### Changes
- Carbon dependency removed

## [1.0.2.12] - 2017-08-27
### Added
- Fixing text attachment issue - overwrite mail body (thx to radicalloop)

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.2.11] - 2017-08-25
### Added
- Attachment disposition (special thanks to radicalloop)
- Missing method added to README.md

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.2.10] - 2017-08-11
### Added
- $fetch_option setter added

### Affected Classes
- [Message::class](src/IMAP/Message.php)

## [1.0.2.9] - 2017-07-12
### Added
- Merged configuration
- New config parameter added
- "Known issues" added to README.md
- Typo fixed

### Affected Classes
- [Client::class](src/IMAP/Client.php)
- [LaravelServiceProvider::class](src/IMAP/Providers/LaravelServiceProvider.php)

## [1.0.2.8] - 2017-06-25
### Added
- Message attribute is now case insensitive
- Readme file extended
- Changelog typo fixed

### Affected Classes
- [Message::class](src/IMAP/Message.php)


## [1.0.2.7] - 2017-04-23
### Added
- imap_fetchheader(): Bad message number - merged
- Changed the default options in imap_fetchbody function - merged
- Attachment handling fixed (Plain text files are no longer ignored)
- Optional config parameter added.
- Readme file extended

### Changes 
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)
- [Folder::class](src/IMAP/Folder.php)

## [1.0.2.3] - 2017-03-09
### Added
- Code commented
- A whole bunch of functions and features added. To many to mention all of them ;)
- Readme file extended

### Changes 
- [Client::class](src/IMAP/Client.php)
- [Message::class](src/IMAP/Message.php)
- [Folder::class](src/IMAP/Folder.php)

## 0.0.1 - 2017-03-04
### Added
- new laravel-imap package
