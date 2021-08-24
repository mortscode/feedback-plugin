# Feedback Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 1.0.12 - 2021-08-24
### Added
- GraphQL Endpoint for getting Feedback items via the Craft CMS GraphQL API

## 1.0.11 - 2021-08-18
### Fixed
- Fixed issue where `getEntryFeedback()` service was using `.all()` which was limiting the use of `paginate` on the frontend templates.
- Frontend templates can now use `.feedbackType('[feedback type]')` alongside the service.

## 1.0.10 - 2021-08-16
### Added
- Link to recipe in feedback detail template
### Fixed
- Issue where conditional wasn't firing on feedback detail emails

## 1.0.9 - 2021-08-16
### Added
- Ability to convert a question to a review and vice versa
- Questions converted to reviews are automatically given 5 stars

## 1.0.8 - 2021-08-05
### Fixed
- Fixed issue where emojis were breaking feedback comments, names, and responses

## 1.0.7 - 2021-08-01
### Fixed
- Issue where services were pulling from `FeedbackRecord` instead of `FeedbackElement`
- Updated `FeedbackElementQuery` to include `$email`, `$ipAddress`, `$userAgent`

## 1.0.6 - 2021-07-01
### Added
- JSON validation errors for submissions that break the rules

## 1.0.5 - 2021-06-14
### Added
- Passing form errors back to template on failed validation
- Updated README with usage instructions

## 1.0.4 - 2021-06-11
### Added
- Pending source for CP
- Responses have a table option
- Added responses to Element Query

### Fixed
- Save & update now use the same method
- Saving via element save only

## 1.0.3 - 2021-06-08
### Added
- Feedback Elements eager-load related Entries
### Fixed
- Import Ratings updater
- Cleaned up some comments and naming conventions

## 1.0.2 - 2021-06-08
### Added
- Element Property for FeedbackOrigin (Frontend, CP, or Import from disqus)
- Ratings values update much better

## 1.0.1 - 2021-06-04
### Added
- Email integration
- Sends for new feedback
- Sends for response to feedback

## 1.0.0 - 2021-05-27
### Added
- Initial release
