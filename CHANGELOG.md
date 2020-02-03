# Release Notes

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Since `0.7.1` this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.5] - 2020-02-03

### Fixed

* Fixed issue with system routes when a base path is defined.

## [1.0.4] - 2020-01-25

### Fixed

* Fixed assertions in admin views.

## [1.0.3] - 2020-01-25

### Security

* Fixed XSS vulnerability in canonical/alternate tags. ([#71])

## [1.0.2] - 2019-10-28

### Added

* Low memory option. ([#45])

### Deprecated

* Constants starting with `MULTPLE_` are now deprecated. They have a matching `MULTIPLE_` prefixed constant.

### Fixed

* Fixed constants starting with `MULTPLE_`, changed to `MULTIPLE`.

## [1.0.1] - 2019-10-25

### Fixed

* Fixed issue with regex used in domain replacement. ([#59])

## [1.0.0] - 2019-10-21

### Added

* Locked out instructions to readme file. ([#61])
* API to programmatically change the domains list. ([#56])

### Fixed

* Don't add canonical link if settings are `false`. ([#58])

## [0.11.2] - 2019-04-19

### Added

* FAQ about removal of `hreflang` tags. ([#51])

### Fixed

* Bug in domain replacement when it contains a slash (the regex delimiter).
* Fixed issue in the domain replacement regex. ([#55])

## [0.11.1] - 2019-04-10

### Fixed

* Improved URI validation when there is a domain's base restriction. ([#50])

## [0.11.0] - 2019-04-09

### Added

* Add [CHANGELOG.md] file. ([#44])
* Added option to enable canonical tags. ([#42])
* Added `%%multiple_domain%%` advanced variable for Yoast.

### Changed

* Moved WordPress admin features to a separate class.
* Renamed hreflang related methods.
* Inline documentation review.
* Minor refactoring.

### Fixed

* Fixed issue with domain replacement.

## [0.10.3] - 2019-03-12

### Added

* Added public method to retrieve a given (or current) domain base path: `getDomainBase($domain = null)`.

### Changed

* Minor code refactoring.

## [0.10.2] - 2019-03-11

### Added

* Added FAQ about plugin settings and domains.

### Fixed

* Fix minor notice message when loading the non-mapped original domain.

## [0.10.1] - 2019-03-11

### Fixed

* Fix bug introduced in 0.10.0 with setups where the original domain is not present in the plugin settings.

## [0.10.0] - 2019-03-08

### Changed

* Moved HTML to view files.

### Fixed

* Don't add SSL when accessing via a Tor domain name. ([#31])

## [0.9.0] - 2019-02-09

### Added

* Added a class to `<body>` tag containing the domain name (e.g. `multipled-domain-name-tld`) to allow front-end customizations.

### Fixed

* Fixed bug in backward compatibility logic.

## [0.8.7] - 2019-02-07

### Changed

* Loading Multiple Domain before other plugins to fix issue with paths.
* Refactored `initAttributes` method.

### Fixed

* Missing locales on language list (this issue was reopened and now it's fixed) ([#38])

## [0.8.6] - 2019-02-01

### Removed

* Rolling back changes introduced in [0.8.4] and [0.8.5] regarding to avoid URL changes in the WP admin. ([#39])

## [0.8.5] - 2019-01-18

### Added

* Add `[multiple_domain]` shortcode to show the current language.

### Fixed

* Fixed an issue introduced in 0.8.4 that breaks the admin URLs.
* Missing locales on language list ([#38])

## [0.8.4] - 2019-01-16

### Changed

* Using singleton pattern for main plugin class.
* Avoiding URL changes in the admin panel.

### Fixed

* Wrong host in URLs returned by the JSON API. ([#36])

## [0.8.3] - 2019-01-12

### Fixed

* `hreflang` tag error. ([#34])

## [0.8.2] - 2018-12-19

### Fixed

* Image URLs not being re-written properly via Tor. ([#32])

## [0.8.1] - 2018-10-09

### Fixed

* Undefined index when using wp-cli. ([#23])

## [0.8.0] - 2018-09-07

### Added

* Added `MULTIPLE_DOMAIN_DOMAIN_LANG` constant for theme/plugin customization. ([#20])

### Changed

* Moved `MultipleDomain` class to its own file.

### Fixed

* No 'Access-Control-Allow-Origin' header is present on the requested resource. ([#21])
* Attempt to fix #22. ([#22])

### Security

* Remove `filter_input` from plugin. ([#14])

## [0.7.1] - 2018-05-25

### Fixed

* Make the plugin compatible with PHP 5.4 again.

## [0.7] - 2018-05-24

Notice that version `0.6` wasn't tagged as a release, then `0.7` also includes its changes.

### Changed

* Code review/refactoring.

### Fixed

* Added activation hook to fix empty settings bug.
* Redirect to original domain if SSL/https ([#11]).

## [0.5] - 2018-01-04

### Added

* `http`/`https` for alternate link.

## [0.4] - 2017-11-28

### Added

* Added Reflang links to head for SEO purpose.  
e.g.
```html
<link rel="alternate" hreflang="x-default" href="https://example.com/">
<link rel="alternate" hreflang="de-DE" href="https://de.example.com/">
```

### Fixed

* Fixed resolving host name to boolean.

## [0.3] - 2016-08-21

### Added

* `MULTIPLE_DOMAIN_ORIGINAL_DOMAIN` constant to hold the original WP home domain.
* Allowing developers to create custom URL restriction logic through `multiple_domain_redirect` action.

### Changed

* Improved settings interface.

### Fixed

* Fixed bug when removing the port from current domain.

## [0.2] - 2016-07-28

### Added

* `MULTIPLE_DOMAIN_DOMAIN` constant for theme/plugin customization.

### Changed

* Improved port verification.
* And, last but not least, code refactoring.

## [0.1] - 2016-07-28

### Added

* Basic multiple domain setup.
* Option base path for each domain.


[Unreleased]: https://github.com/straube/multiple-domain/compare/v1.0.5...HEAD
[1.0.5]: https://github.com/straube/multiple-domain/compare/v1.0.4...v1.0.5
[1.0.4]: https://github.com/straube/multiple-domain/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/straube/multiple-domain/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/straube/multiple-domain/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/straube/multiple-domain/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/straube/multiple-domain/compare/v0.11.2...v1.0.0
[0.11.2]: https://github.com/straube/multiple-domain/compare/v0.11.1...v0.11.2
[0.11.1]: https://github.com/straube/multiple-domain/compare/v0.11.0...v0.11.1
[0.11.0]: https://github.com/straube/multiple-domain/compare/v0.10.3...v0.11.0
[0.10.3]: https://github.com/straube/multiple-domain/compare/v0.10.2...v0.10.3
[0.10.2]: https://github.com/straube/multiple-domain/compare/v0.10.1...v0.10.2
[0.10.1]: https://github.com/straube/multiple-domain/compare/v0.10.0...v0.10.1
[0.10.0]: https://github.com/straube/multiple-domain/compare/v0.9.0...v0.10.0
[0.9.0]: https://github.com/straube/multiple-domain/compare/v0.8.7...v0.9.0
[0.8.7]: https://github.com/straube/multiple-domain/compare/v0.8.6...v0.8.7
[0.8.6]: https://github.com/straube/multiple-domain/compare/v0.8.5...v0.8.6
[0.8.5]: https://github.com/straube/multiple-domain/compare/v0.8.4...v0.8.5
[0.8.4]: https://github.com/straube/multiple-domain/compare/v0.8.3...v0.8.4
[0.8.3]: https://github.com/straube/multiple-domain/compare/v0.8.2...v0.8.3
[0.8.2]: https://github.com/straube/multiple-domain/compare/v0.8.1...v0.8.2
[0.8.1]: https://github.com/straube/multiple-domain/compare/v0.8.0...v0.8.1
[0.8.0]: https://github.com/straube/multiple-domain/compare/v0.7.1...v0.8.0
[0.7.1]: https://github.com/straube/multiple-domain/compare/v0.7...v0.7.1
[0.7]: https://github.com/straube/multiple-domain/compare/v0.5...v0.7
[0.5]: https://github.com/straube/multiple-domain/compare/v0.4...v0.5
[0.4]: https://github.com/straube/multiple-domain/compare/v0.3...v0.4
[0.3]: https://github.com/straube/multiple-domain/compare/v0.2...v0.3
[0.2]: https://github.com/straube/multiple-domain/compare/v0.1...v0.2
[0.1]: https://github.com/straube/multiple-domain/releases/tag/v0.1

[#71]: https://github.com/straube/multiple-domain/issues/71
[#61]: https://github.com/straube/multiple-domain/issues/61
[#59]: https://github.com/straube/multiple-domain/issues/59
[#58]: https://github.com/straube/multiple-domain/issues/58
[#56]: https://github.com/straube/multiple-domain/issues/56
[#55]: https://github.com/straube/multiple-domain/issues/55
[#51]: https://github.com/straube/multiple-domain/issues/51
[#50]: https://github.com/straube/multiple-domain/issues/50
[#45]: https://github.com/straube/multiple-domain/issues/45
[#44]: https://github.com/straube/multiple-domain/issues/44
[#42]: https://github.com/straube/multiple-domain/issues/42
[#39]: https://github.com/straube/multiple-domain/issues/39
[#38]: https://github.com/straube/multiple-domain/issues/38
[#36]: https://github.com/straube/multiple-domain/issues/36
[#34]: https://github.com/straube/multiple-domain/issues/34
[#32]: https://github.com/straube/multiple-domain/issues/32
[#31]: https://github.com/straube/multiple-domain/issues/31
[#23]: https://github.com/straube/multiple-domain/issues/23
[#22]: https://github.com/straube/multiple-domain/issues/22
[#21]: https://github.com/straube/multiple-domain/issues/21
[#20]: https://github.com/straube/multiple-domain/issues/20
[#14]: https://github.com/straube/multiple-domain/issues/14
[#11]: https://github.com/straube/multiple-domain/issues/11
