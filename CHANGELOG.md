# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## 0.0.10 – 2020-12-26
### Fix
- casing mistake
[#6](https://github.com/nextcloud/integration_dropbox/issues/6) @johnnyasantoss
[#7](https://github.com/nextcloud/integration_dropbox/issues/7) @johnnyasantoss

## 0.0.8 – 2020-12-22
### Changed
- warn if storage is empty

### Fixed
- avoid counting files, too slow

## 0.0.7 – 2020-12-16
### Fixed
- issue with unlimited quota

## 0.0.5 – 2020-11-11
### Fixed
- don't close already closed resource when downloading

## 0.0.4 – 2020-11-08
### Changed
- no more temp files, directly download to target file (in a stream)

## 0.0.3 – 2020-11-05
### Fixed
- remove timeout when downloading files
- reduce disk usage by deleting temp files after having copied them in NC storage

## 0.0.2 – 2020-10-24
### Fixed
- get storage info directly after successfull login

## 0.0.1 – 2020-10-23
### Added
* the app
