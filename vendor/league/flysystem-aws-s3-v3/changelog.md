# Changelog

## 1.0.30 - 2022-07-02

* upgrade to list objects v2

## 1.0.29 - 2020-10-08

* copies now switch to multipart copy for large files.

## 1.0.28 - 2020-08-22

* __Allow streamed read by default.__<br/>
  This change prevents the stream from being seekable (func
  calls like rewind have no effect). Need to seek through the stream?
  Check out the docs to see how to disable streaming read: https://flysystem.thephpleague.com/v1/docs/adapter/aws-s3-v3/#streamed-reads 

## 1.0.27 - 2020-08-22

* Revert always streaming reads (degraded functionality).

## 1.0.26 - 2020-08-18

* Always stream reads (#211)

## 1.0.25 - 2020-06-02

* Use `S3Client::encodeKey` for key encoding.

## 1.0.24 - 2020-02-23

* Depend on S3ClientInterface rather than the concrete client.

## 1.0.23 - 2019-06-05

* Prevent content type detection for directory creation.
* Use `rawurlencode` instead of `urlencode` to treat url encoding in a spec compliant way.

## 1.0.22 - 2019-01-31

* Invert type check where string/resource difference is determined for ContentLength option.

## 1.0.21 - 2018-10-08

* Catch multipart upload errors.

## 1.0.20 - 2018-09-25

* Fixed prefix handling for uploads (writes and updates).

## 1.0.19 - 2018-03-27

* Added ETAG to response mapping.

## 1.0.18 - 2017-06-30

### Fixed

* Allow metadata to be returned through the getMetadata method.

## 1.0.17 - 2017-06-30

### Fixed

* Allow passing options to methods that don't accept options.

## 1.0.16 - 2017-06-08

### Improved

* Allow the `Tagging` meta option.

## 1.0.15 - 2017-04-28

### Improved

* Indicate this adapter can overwrite files.

## 1.0.14 - 2017-01-02

### Improved

* Now also detect mimetypes of streams.

## 1.0.13 - 2016-06-21

### Fixed

* Uploading a remote stream no longer results in an unexpected exception.

## 1.0.12 - 2016-06-06

### Improved

* Responses are now streamed instead of downloaded fully.

## 1.0.11 - 2016-05-03

### Fixed

* [::has] A regression introduced in 1.0.10 is addressed.

## 1.0.10 - 2016-04-19

### Fixed

* [::has] The `has` method now also respects implicit directories.

## 1.0.9 - 2015-11-19

### Fixed

* [#49] Large listings only returned the last page of the listing.

## 1.0.8 - 2015-11-06

### Improved

* Non-recursive listings now retrieve a shallow listing for better performance.

## 1.0.7 - 2015-11-06

### Fixed

* The `copy` operation now `urlencode`'s the `CopySource` to allow characters like `+`.

## 1.0.6 - 2015-09-25

### Fixed

* The `has` operation now respects path prefix, bug introduced in 1.0.5.

## 1.0.5 - 2015-09-22

### Fixed

* `has` calls now use `doesObjectExist` rather than retrieving metadata.

## 1.0.4 - 2015-07-06

### Fixed

* Fixed delete return value.

## 1.0.3 - 2015-06-16

### Fixed

* Use an iterator for contents listing to break through the 1000 objects limit.

## 1.0.2 - 2015-06-06

### Fixed

* Exception due to misconfiguration no longer causes a fatal error but are properly rethrown.

## 1.0.1 - 2015-05-31

### Fixed

* Stable release depending in the first v3 release of the AWS SDK.
