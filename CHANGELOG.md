# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Abilities to search, get, create, update and comment on persons (`orbis-persons/search`, `orbis-persons/get`, `orbis-persons/create`, `orbis-persons/update` and `orbis-persons/comment`), also available as Orbis MCP server tools.
- Changes made through the abilities are logged as comments, with the AI provider and model in the comment meta.
- Person comments show the AI provider and model that wrote them.

### Changed

- The birth day, month and year can now be set separately, each part is optional.
- The person details show the next birthday and the (possible) age.
- Existing birth dates are migrated to the separate day, month and year fields.

## [1.0.0]

### Added

- Initial release, based on the `orbis_person` post type from Orbis core.

[unreleased]: https://github.com/pronamic/wp-orbis-persons/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/pronamic/wp-orbis-persons/releases/tag/v1.0.0
