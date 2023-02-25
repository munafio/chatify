# Change log

All notable changes to this project will be documented in this file.

## v1.6.0 (2023-02-xx)

### Added

- Emoji's support.
- Css variables.
- Notification sounds.

### Enhancements

- Using UUIDs instead of random IDs on table primary column #243.
- UI/UX changes and enhancements.

### Fixed

- Fetching messages multiple times at once on send/fetch requests.
- Migrations duplicate class name.
- Prevent chat for invalid user ids #246
- Fix responsiveness when going to chat with specific ID #247.
- App URL should be changed when click the `back to contacts` button on small screens.
- Internet connection UI.
- Prevent Users from updating each others statuses #254

## v1.5.6 (2023-01-26)

### Fixed

- Keyboard overlaping on input issue on mobile #202.
- Security issue and code enhancements #240.

## v1.5.5 (2023-01-21)

### Fixed

- message delete event channel #238.

## v1.5.4 (2022-12-05)

### Fixed

- Channels auth secutiy issue #29

## v1.5.3 (2022-12-04)

### Fixed

- Channels Secutiy issue #29

## v1.5.2 (2022-07-08)

### Fixed

- MessageCard & fetchMessage methods@`ChatifyMessenger.php` fallback.

## v1.5.1 (2022-06-09)

### Fixed

- Sync the `sending a message form`'s allowed files/images with the `config` file (Update sendForm.blade.php [#190](https://github.com/munafio/chatify/pull/190))

## v1.5.0 (2022-06-08)

### Added

- Page/Document visibility Support which improves (seen) feature #183

### Fixed

- fix: case insensitive file upload extension check #182

## v1.4.0 (2022-05-02)

### Added

- [Gravatar](https:://gravatar.com) support (optional, can be changed at config/chatify.php).
- Delete Message by ID.
- Laravel's Storage disk now supported and can be changed from the config.

### Changed

- File upload (user avatar & attachments) `allowed files` and `max size` now can be changed from one place which is (config/chatify.php).

### Fixed

- Bugs and UI/UX design fixes/improvements.

## v1.3.4 (2022-02-04)

### Fixed

- Fixed Installing errors on the migrations step. #163

## v1.3.3 (2022-01-10)

### Fixed

- Fixed file upload size limit error message rephrase #160.

### Changed

- Files max upload size changed & added to the config to be customizable.
- Changed `Messenger colors` logic to be more flexible and customizable.
- Migration files renamed, file date automatically will be changed to the publish/install date.

## v1.3.2 (2022-01-07)

### Fixed

- Fixed CSS issue in FF with the contact list #157.
- Correct misspelt of `updateContactItem` method (typo error) #159.

## v1.3.1 (2021-12-23)

### Fixed

- Fixed migration's rollback, (ch\_) prefix added.

## v1.3.0 (2021-11-30)

### Fixed

- UI/Ux fixes & improvements.
- Backend fixes & improvements.

### Added

- Messages, Contacts, and Search pagination.
- API routes.

## v1.2.5 (2021-08-18)

### Fixed

- Fixed a security issue on uploaded file-name, which is vulnerable with XSS.

## v1.2.4 (2021-07-15)

### Fixed

- README updates.
- Install Command fixes & improvements.
- Contact list visible onLoad.
- Settings’ modal responsive design.

### Added

- UPGRADE.md added.
- Publish command added.
- Package.json additions & modifications.

## v1.2.3 - (2021-06-19)

### Fixed

- XSS issue on inputs.
- UI/UX fixes & improvements.
- Send message fixes (UI & backend).
- Update Profile Settings (upload file & error handling ….).
- Shared photos not working issue.
- Typo error fixes (Your `contatc` list is empty).
- Rolling back migrations added.
- Get Last message `orderBy` query duplication.

## v1.2.2 - (2021-06-01)

### Fixed

- Migrate to database command removed.
- Publishable asset `assets` avatar config issue.
- Pusher encryption key option removed.
- Settings button on click not working issue.

## v1.2.1 - (2021-05-30)

### Fixed

- Publishable asset `assets`.

## v1.2.0 - (2021-05-30)

### FIxed

- Security issues.
- UI/UX issues.
- Route [home] not defiend.
- `$msg->attachment` issue #9.
- Delete conversation issue #89.

### Added

- Console commands.
- `Models` added to assets to be published.
- Laravel 8+ support.

### Changed

- Project structure.
- composer updated `pusher/pusher-php-server` to v^7.0.
- Models & Migrations' tables names changed (added `ch` prefix to avoid duplication) solves issue #68.
  - Models changed to (`ChMessage`, `ChFavorite`)
  - Migrations' tables names (`ch_messages`, `ch_favorites`)
- Configuration file `config/chatify.php`.

## v1.0.1 - (2020-09-30)

### FIxed

- Security issues.

### Added

- Routes' controllers namespace included in the configuration.

## v1.0.0 - (2019-12-30)

- First release
