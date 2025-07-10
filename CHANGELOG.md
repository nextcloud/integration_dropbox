<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [4.0.3] - 2025-07-10

### Fixed

- Fixed build

## [4.0.2] - 2025-07-10

### Fixed

- Fixed build

## [4.0.1] - 2025-07-10

### Fixed

- Fixed build

## [4.0.0] - 2025-07-10

### Breaking changes

- Drop support for Nextcloud < 30

### New

- Add support for Nextcloud 32

### Changed

- Use outline-style icons where possible

### Fixed

- Fix(l10n): Update translations from Transifex

## [3.1.0] - 2024-11-11

### New

- Add support for Nextcloud 31

### Fixed

- Fix(l10n): Update translations from Transifex


## [3.0.3] - 2024-11-11

### Fixed

* Fixed build

## [3.0.2] - 2024-11-11

### Fixed

* fix: Safer settings
* Fix(l10n): Update translations from Transifex

## [3.0.1] - 2024-09-19

### Fixed

 - fix: add password confirmation in admin settings
 - fix: remove default oauth app client/secret
 - fix: update npm pkgs, adjust to new stylelint config
 - fix: update composer deps, update psalm baseline

## [3.0.0] - 2024-08-01

### breaking changes

- Drop support for Nextcloud < 28

### New

- Add support for Nextcloud 30

## [2.2.0] - 2024-03-06

### Changed
 - Add support for nc 29

### Fixed
 - fix(DropboxStorageAPIService): Don't allow shared folder as target folder 
 - fix(downloadFile): Switch to /download endpoint to avoid temporary_link endpoint 
 - Fix(l10n): Update translations from Transifex

## [2.1.0] - 2023-05-31

### Changed

- Added support for nextcloud 28

### Fixed

- Fix(l10n): Update translations from Transifex

## [2.0.1] - 2023-05-31

### Fixed

 - Fix ImportDropboxJob
 - Fix sign-in if username is empty + Give more feedback in UI
 - Update translations from Transifex

## [2.0.0] - 2023-05-19

### Breaking changes

 - Drop support for Nextcloud pre 26
 - Now supported: Nextcloud 26 and Nextcloud 27


## 1.0.6 – 2023-01-06
### Changed
- improve admin settings style

### Fixed
- fix app being unable to start importing after a job has been brutaly stopped

## 1.0.5 – 2022-08-24
### Changed
- use material icons
- ready for NC 25
- bump js libs
- adjust to new eslint config

### Fixed
- npm scripts

## 1.0.3 – 2021-09-02
### Fixed
- handle all crashes in import job
- fix file import with SSE enabled, get temp link and use it on the fly

## 1.0.2 – 2021-06-28
### Changed
- refactor backend code @vitormattos
- bump js libs
- get rid of all deprecated stuff
- bump min NC version to 22
- cleanup backend code

## 1.0.1 – 2021-04-19
### Added
- setting to choose output dir

### Changed
- bump js libs

### Fixed
- potential mess with concurrent import jobs

## 1.0.0 – 2021-03-19
### Changed
- bump js libs

## 0.0.18 – 2021-02-12
### Changed
- bump js libs
- bump max NC version

### Fixed
- import nc dialogs style

## 0.0.17 – 2021-01-18
### Fixed
- catch ForbiddenException (for .htaccess files for example)
[#3](https://github.com/nextcloud/integration_dropbox/issues/3) @agentff6600

## 0.0.16 – 2021-01-10
### Fixed
- allow float file sizes
- catch lockedException
[#3](https://github.com/nextcloud/integration_dropbox/issues/3) @agentff6600

## 0.0.15 – 2021-01-07
### Fixed
- fix wrong method name when refreshing token
- fix userID type in controller
[#3](https://github.com/nextcloud/integration_dropbox/issues/3) @agentff6600

## 0.0.14 – 2021-01-07
### Fixed
- skip files that can't be created or written
[#3](https://github.com/nextcloud/integration_dropbox/issues/3) @agentff6600

## 0.0.13 – 2021-01-01
### Changed
- bump js libs

### Fixed
- remove useless browser detection (crashing on RPi with chrome anyway)
[#4](https://github.com/nextcloud/integration_dropbox/issues/4) @janesser

## 0.0.12 – 2020-12-26
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
