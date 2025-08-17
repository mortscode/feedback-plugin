# Feedback Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 5.0.2 - 2025-08-16

### Updated

-   Updated to use ReCaptcha Enterprise
-   Added settings fields for RecaptchaSiteKey, RecaptchaProjectId, and RecaptchaApiKey

## 5.0.1 - 2025-08-07

### Fixed

-   Email verification for invalid email addresses
-   Issue where settings template wasn't rendering sections correctly

## 5.0.0 - 2025-07-23

### Updated

-   Passed phpstan for Craft 5
-   Passed Rector for Craft 5
-   Updated version to 5.0.0

## 4.0.24 - 2024-08-18

### Updated

-   TypeManager::prepareFieldDefinitions() has been updated to use craft\services\Gql::prepareFieldDefinition()
-   Applies to GQL interface and generator

## 4.0.23 - 2024-08-14

### Fixed

-   SetStatus functionality in Feedback index

### Updated

-   Feedback index sources
-   SetFeedbackStatus method now saves Element
-   Updated Servd Assets dev dependency
-   Fixed entry buttons for selected sources
-   Default table attributes

## 4.0.22 - 2024-08-13

### Updated

-   Additional typing for Craft 4.x

## 4.0.21 - 2024-03-19

### Fixed

-   Send email conditional

## 4.0.2 - 2024-03-18

### Added

-   User can add anonymous reviews
-   One anonymous review per day allowed
-   New hasRatedToday variable
-   Migrations for new Ratings feedback type

### Updated

-   Updated CP settings for Ratings

## 4.0.1 - 2024-03-11

### Updated

-   Updated composer.json with version number for packagist

## 4.0.0 - 2024-03-11

### Updated

-   Updated to run on Craft 4

## 1.0.30 - 2021-09-20

### Updated

-   Emails updated for grammatical errors

## 1.0.29 - 2021-09-20

### Updated

-   Emails don't show comment header if there is no comment

## 1.0.28 - 2021-09-20

### Added

-   Automatically approve reviews with no comment and more than 3 stars

## 1.0.27 - 2021-09-20

### Added

-   GraphQl arguments for `hasComment`

### Updated

-   All instances of `find()` on `FeedbackElement` converted away from ActiveRecord

## 1.0.26 - 2021-09-17

### Updated

-   Styled entry and feedback detail button containers
-   Updated email delivery conditional for non-frontend feedback
-   Cleaned up some lingering commented-out code

## 1.0.25 - 2021-09-17

### Removed

-   Removed servd cache bust from `AfterSave()` method on FeedbackElement
-   Removed commented out refs to field-based feedback stats

## 1.0.24 - 2021-09-17

### Fixed

-   Fixed issue where RecaptchaEnabled lightswitch wasn't working correctly

## 1.0.23 - 2021-09-17

### Added

-   Lightswitch to turn Recaptcha on/off
-   Cache purge with every saved element
-   New email for Approved feedback

## 1.0.22 - 2021-09-15

### Updated

-   Updated recaptcha check to return entire response
-   Updated filter options in feedback plugin aside menu

## 1.0.21 - 2021-09-09

### Fixed

-   Fixed issue where entryId was incorrect on the entry->createFeedback buttons

## 1.0.20 - 2021-09-09

### Added

-   Added "comment" to feedback element table
-   Updated icon svg to remove white bg

## 1.0.19 - 2021-09-08

### Fixed

-   Fixed issue where `getLocationByIp` wasn't using `isset()`

## 1.0.18 - 2021-09-03

### Added

-   Field in settings where a GQL token can be saved for feedback load more

## 1.0.17 - 2021-09-02

### Fixed

-   Fixed issue where feedback search indexes where hung up on emojis

## 1.0.16 - 2021-09-02

### Fixed

-   Fixed issue where disqus imported feedback wasn't converting Emojis to unicode

## 1.0.15 - 2021-09-02

### Removed

-   Removed entry review stats fields from plugin (`averageRating`, `totalRatings`, `totalPending`)

## 1.0.14 - 2021-09-01

### Added

-   `getEntryReviewStats` Service
-   returns a new `ReviewStatusModel` with `averageRating` and `totalRatings`

## 1.0.13 - 2021-08-26

### Added

-   GraphQL arguments for `feedbackStatus` and `feedbackType`

## 1.0.12 - 2021-08-24

### Added

-   GraphQL Endpoint for getting Feedback items via the Craft CMS GraphQL API

## 1.0.11 - 2021-08-18

### Fixed

-   Fixed issue where `getEntryFeedback()` service was using `.all()` which was limiting the use of `paginate` on the frontend templates.
-   Frontend templates can now use `.feedbackType('[feedback type]')` alongside the service.

## 1.0.10 - 2021-08-16

### Added

-   Link to recipe in feedback detail template

### Fixed

-   Issue where conditional wasn't firing on feedback detail emails

## 1.0.9 - 2021-08-16

### Added

-   Ability to convert a question to a review and vice versa
-   Questions converted to reviews are automatically given 5 stars

## 1.0.8 - 2021-08-05

### Fixed

-   Fixed issue where emojis were breaking feedback comments, names, and responses

## 1.0.7 - 2021-08-01

### Fixed

-   Issue where services were pulling from `FeedbackRecord` instead of `FeedbackElement`
-   Updated `FeedbackElementQuery` to include `$email`, `$ipAddress`, `$userAgent`

## 1.0.6 - 2021-07-01

### Added

-   JSON validation errors for submissions that break the rules

## 1.0.5 - 2021-06-14

### Added

-   Passing form errors back to template on failed validation
-   Updated README with usage instructions

## 1.0.4 - 2021-06-11

### Added

-   Pending source for CP
-   Responses have a table option
-   Added responses to Element Query

### Fixed

-   Save & update now use the same method
-   Saving via element save only

## 1.0.3 - 2021-06-08

### Added

-   Feedback Elements eager-load related Entries

### Fixed

-   Import Ratings updater
-   Cleaned up some comments and naming conventions

## 1.0.2 - 2021-06-08

### Added

-   Element Property for FeedbackOrigin (Frontend, CP, or Import from disqus)
-   Ratings values update much better

## 1.0.1 - 2021-06-04

### Added

-   Email integration
-   Sends for new feedback
-   Sends for response to feedback

## 1.0.0 - 2021-05-27

### Added

-   Initial release
