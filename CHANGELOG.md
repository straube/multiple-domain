# Release Notes

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Since `0.7.1` this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

* Add [CHANGELOG.md] file. (#44)

## [0.10.3]

### Added

* Added public method to retrieve a given (or current) domain base path: `getDomainBase($domain = null)`.

### Changed

* Minor code refactoring.

## [0.10.2]

### Added

* Added FAQ about plugin settings and domains.

### Fixed

* Fix minor notice message when loading the non-mapped original domain.

## [0.10.1]

### Fixed

* Fix bug introduced in 0.10.0 with setups where the original domain is not present in the plugin settings.

## [0.10.0]

### Changed

* Moved HTML to view files.

### Fixed

* Don't add SSL when accessing via a Tor domain name. (#31)

## [0.9.0]

### Added

* Added a class to `<body>` tag containing the domain name (e.g. `multipled-domain-name-tld`) to allow front-end customizations.

### Fixed

* Fixed bug in backward compatibility logic.

## [0.8.7]

### Changed

* Loading Multiple Domain before other plugins to fix issue with paths.
* Refactored `initAttributes` method.

### Fixed

* Missing locales on language list (this issue was reopened and now it's fixed) (#38)

## [0.8.6]

### Removed

* Rolling back changes introduced in [0.8.4] and [0.8.5] regarding to avoid URL changes in the WP admin. (#39)

## [0.8.5]

### Added

* Add `[multiple_domain]` shortcode to show the current language.

### Fixed

* Fixed an issue introduced in 0.8.4 that breaks the admin URLs.
* Missing locales on language list (#38)

## [0.8.4]

### Changed

* Using singleton pattern for main plugin class.
* Avoiding URL changes in the admin panel.

### Fixed

* Wrong host in URLs returned by the JSON API. (#36)

## [0.8.3]

### Fixed

* `hreflang` tag error. (#34)

## [0.8.2]

### Fixed

* Image URLs not being re-written properly via Tor. (#32)

## [0.8.1]

### Fixed

* Undefined index when using wp-cli. (#23)

## [0.8.0]

### Added

* Added `MULTPLE_DOMAIN_DOMAIN_LANG` constant for theme/plugin customization. (#20)

### Changed

* Moved `MultipleDomain` class to its own file.

### Fixed

* No 'Access-Control-Allow-Origin' header is present on the requested resource. (#21)
* Attempt to fix #22. (#22)

### Security

* Remove `filter_input` from plugin. (#14)

## [0.7.1]

### Fixed

* Make the plugin compatible with PHP 5.4 again.

## [0.7]

### Changed

* Code review/refactoring.

### Fixed

* Added activation hook to fix empty settings bug.

## [0.6]

### Fixed

* Redirect to original domain if SSL/https (#11).

## [0.5]

### Added

* `http`/`https` for alternate link.

## [0.4]

### Added

* Added Reflang links to head for SEO purpose.  
e.g.
```html
<link rel="alternate" hreflang="x-default" href="https://example.com/">
<link rel="alternate" hreflang="de-DE" href="https://de.example.com/">
```

### Fixed

* Fixed resolving host name to boolean.

## [0.3]

### Added

* `MULTPLE_DOMAIN_ORIGINAL_DOMAIN` constant to hold the original WP home domain.
* Allowing developers to create custom URL restriction logic through `multiple_domain_redirect` action.

### Changed

* Improved settings interface.

### Fixed

* Fixed bug when removing the port from current domain.

## [0.2]

### Added

* `MULTPLE_DOMAIN_DOMAIN` constant for theme/plugin customization.

### Changed

* Improved port verification.
* And, last but not least, code refactoring.

## [0.1]

### Added

* Basic multiple domain setup.
* Option base path for each domain.
