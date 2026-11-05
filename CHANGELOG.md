# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Abilities to search, get, create, update and comment on persons (`orbis-persons/search`, `orbis-persons/get`, `orbis-persons/create`, `orbis-persons/update` and `orbis-persons/comment`), also available as Orbis MCP server tools.
- Changes made through the abilities are logged as comments, with the AI provider and model in the comment meta.
- Person comments show the AI provider and model that wrote them.
- Person details are kept in revisions, shown in the revisions screen and restored with a revision.
- Separate fields for the first names, last names and last name at birth. The post title is the display name, composed of the first names and last names when left empty.
- Father and mother of a person, stored on the child as the post ID of the parent (`_orbis_person_father_post_id` and `_orbis_person_mother_post_id`). The person page and the abilities show the father, mother and children.

### Changed

- The birth day, month and year can now be set separately, each part is optional.
- The person details show the next birthday and the (possible) age.
- Existing birth dates are migrated to the separate day, month and year fields.
- The gender is a fixed choice, male or female, stored in the `_orbis_gender` post meta instead of a term of the `orbis_gender` taxonomy. Existing terms like "Man" and "Vrouw" are migrated, terms that cannot be mapped are kept but no longer shown.
- The gender in the abilities is `"male"`, `"female"` or `null` instead of a term object.
- The father search only shows men and persons without a gender, the mother search only women and persons without a gender. A woman cannot be saved as father and a man cannot be saved as mother.
- Email addresses and phone numbers come from Orbis Contacts (`_orbis_contact_email`, `_orbis_contact_phone` and `_orbis_contact_json`), with labels, and are shown in the admin list, the person page and the archive. Searching for a phone number like `06 12345678` finds `+31612345678`. The `{prefix}orbis_persons` table gets the first email address.
- The abilities return `email_addresses` and `phone_numbers` instead of `email`, `phone_number` and `mobile_number`.

### Removed

- The `orbis_gender` taxonomy.
- The email address, phone number and mobile number fields of the person details meta box, they are replaced by the "Contact Details" meta box of Orbis Contacts. The `_orbis_email`, `_orbis_phone_number` and `_orbis_mobile_number` post meta is no longer read or written.
- The `email`, `phone_number` and `mobile_number` input of `orbis-persons/create` and `orbis-persons/update`.

## [1.0.0]

### Added

- Initial release, based on the `orbis_person` post type from Orbis core.

[unreleased]: https://github.com/pronamic/wp-orbis-persons/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/pronamic/wp-orbis-persons/releases/tag/v1.0.0
