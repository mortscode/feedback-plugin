# Feedback Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

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
