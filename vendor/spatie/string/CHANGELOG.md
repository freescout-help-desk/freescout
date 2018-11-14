# Changelog

All notable changes to `spatie/string` will be documented in this file

## 2.2.2 - 2017-11-08
- fix possesive output of `it`

## 2.2.1 - 2016-12-01
- fix error when using `possesive` on an empty string

## 2.2.0 - 2016-10-04
- add `replaceFirst`

## 2.1.0 - 2015-07-14
### Added
- Contains function (alias for find)

## 2.0.1 - 2015-09-22
### Bugfix
- Strings now have a more strict validation on instantiation. Trying to create a string from an array or an object that doesn't implement `__toString` now throws an exception.

## 2.0.0 - 2015-07-14
### Added
- PHP 7 compatibility

### Removed
- PHP 5.4 support

## 1.9.1 - 2015-06-26

### Changed
- Removed replace function & test (already provided by Underscore)

## 1.9.0 - 2015-06-26

### Added
- Add replace function

## 1.8.2 - 2015-06-24

### Bugfix
- Fixed ArrayAccess offset test

## 1.8.1 - 2015-06-12

### Bugfix
- Fixed underscore methods that use the string as a parameter

## 1.8.0 - 2015-06-12

### Added
- pop method
 
## 1.7.0 - 2015-06-09

### Added
-  segment methods
-  trim method
-  documentation improvements

## 1.6.0 - 2015-06-08

### Added
-  possessive method

## 1.5.0 - 2015-06-07

### Added
-  allow string manipulation via array offset

## 1.4.0 - 2015-06-07

### Added
-  integration with underscore

## 1.3.0 - 2015-06-05

### Added
-  prefix, suffix and concat methods

## 1.2.0 - 2015-06-05

### Added
-  replaceLast method

## 1.1.0 - 2015-06-05

### Added
- tease method

## 1.0.0 - 2015-06-05

### Added
- Everything, initial release
