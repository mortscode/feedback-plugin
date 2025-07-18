# Feedback plugin for Craft CMS 4.x

A comments and reviews plugin for Craft CMS 3.x

![Screenshot](resources/img/plugin-logo.png)

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require mortscode/feedback

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Feedback.

## Feedback Overview

The Feedback Plugin provides 2 types of feedback forms for your users: Reviews & Questions.
The idea is that sometimes you're looking for an actual Review from users, 
other times, you just want to provide a way for users to ask questions or leave a suggestion. 

Below you'll find the 2 forms aimed at these 2 types of users.

### Review Form

Required Fields:
- Name
- Email

Review-specific Fields:
- Rating

```html
<form class="js-feedback-form" role="form" method="post" accept-charset="UTF-8">
     {{ hiddenInput('action', 'feedback/feedback/save') }}
     {{ hiddenInput('entryId', entry.id) }}
     {{ hiddenInput('feedbackType', 'review') }}
     {{ hiddenInput('token', "", {class: 'js-token-input'}) }} <-- if using recaptcha
     {{ csrfInput() }}
     <input type="text" name="name" placeholder="name">
     {{ feedback is defined and feedback ? errorList(feedback.getErrors('name')) }}
     <input type="email" name="email" placeholder="email">
     <div>
         <input type="radio" id="rating-1" name="rating" value="1"><label for="rating-1">1</label>
         <input type="radio" id="rating-2" name="rating" value="2"><label for="rating-2">2</label>
         <input type="radio" id="rating-3" name="rating" value="3"><label for="rating-3">3</label>
         <input type="radio" id="rating-4" name="rating" value="4"><label for="rating-4">4</label>
         <input type="radio" id="rating-5" name="rating" value="5"><label for="rating-5">5</label>
     </div>
     <textarea name="comment" cols="30" rows="10"></textarea>
     <button type="submit">Submit Review</button>
 </form>
```

### Questions Form

Add the following form to enable users to ask questions (no ratings):

```html
<form class="js-feedback-form" role="form" method="post" accept-charset="UTF-8">
     {{ hiddenInput('action', 'feedback/feedback/save') }}
     {{ hiddenInput('entryId', entry.id) }}
     {{ hiddenInput('feedbackType', 'question') }}
     {{ hiddenInput('token', "", {class: 'js-token-input'}) }} <-- if using recaptcha
     {{ csrfInput() }}
     <input type="text" name="name" placeholder="name">
     <input type="email" name="email" placeholder="email">
     <textarea name="comment" cols="30" rows="10"></textarea>
     <button type="submit">Submit Question</button>
 </form>
```

## Configuring Feedback

-Insert text here-

## Using Feedback

### Available Variables

-   name
-   email
-   rating
-   comment
-   response

### Templating

Render reviews in your templates like so:

```html
{% set reviews = craft.reviews.getEntryReviews(entry.id) %}
<ol>
    {% for review in reviews %}
        <li>
            <h3>{{ review.name }}</h3>
            <p>{{ review.email }}</p>
            <p>Rating:
                {{ review.rating }}</p>
            <p>{{ review.comment }}</p>
            {% if review.response %}
                <p>{{ review.response }}</p>
            {% endif %}
        </li>
    {% endfor %}
</ol>
```

### ReCaptcha

1. Register your site with Google ReCaptcha [here](https://www.google.com/recaptcha/admin/create).
2. Add your new site key and secret key to the `Settings > Reviews` page in the Craft CP
3. Add the JS script to pages using the Reviews plugin. You can grab the key from the settings page with the `craft.reviews.getRecaptchaKey` helper.

```
<script src="https://www.google.com/recaptcha/api.js?render={{ craft.reviews.getRecaptchaKey }}"></script>
```

4. On submit, get the token from ReCaptcha and pass it into the form before the submit takes place.

```js
<script>
    var reviewForm = document.getElementById('reviews-form');

    reviewForm.addEventListener('submit', handleRecaptcha);

    function handleRecaptcha(e) {
        e.preventDefault();

        grecaptcha.ready(function() {
            grecaptcha.execute('{{ craft.reviews.getRecaptchaKey }}', {action: 'submit'}).then(function(token) {
                tokenizeForm(token);
            }).then(function() {
                reviewForm.submit();
            });
        });

        function tokenizeForm(token) {
            const tokenInput = document.querySelector('.js-token-input');
            console.log(tokenInput);
            tokenInput.value = token;
        }
    }

    reviewForm.addEventListener('submit', handleRecaptcha, false);
</script>
```

5. Add the hidden field to your forms:
```html
<form>
   [...]
   {{ hiddenInput('token', "", {class: 'js-token-input'}) }}
   [...]
</form>
```

## Feedback Roadmap

Some things to do, and ideas for potential features:

* Release it

Brought to you by [Scot Mortimer](mortscode.com)
