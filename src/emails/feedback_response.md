{% set entry = craft.entries.id(feedback.entryId).one() %}
{% set emailHeaderUrl = craft.feedback.getEmailHeaderUrl %}

{% if emailHeaderUrl %}
<div with="500" style="width:100%; max-width:500px;">
    <img src="{{ emailHeaderUrl }}" alt="brand header image" style="width:100%;height:auto;">
</div>
{% endif %}

### Hey {{ feedback.name ? feedback.name | split(' ')[0] : 'friend' }},

There is a new response to your {{ feedback.feedbackType }} on our [{{ entry.title }}]({{ entry.url }}) page:

<div style="background-color:#F7F6F2;color:#222222;padding:18px">
{% if feedback.rating %}
{% set remainingStars = 5 - feedback.rating %}
<p><span style="font-weight: bold">Rating:</span> 
{% for i in 1..feedback.rating %}&#9733;{% endfor %}
{% if remainingStars > 0%}{% for i in 1..remainingStars %}&#9734;{% endfor %}{% endif %}</p>
{% endif %}
{% if feedback.comment %}
<p><span style="font-weight: bold">Comment:</span></br>
{{ feedback.comment }}</p>
{% endif %}
{% if feedback.response %}
<p><span style="font-weight: bold">Response:</span></br>
{{ feedback.response }}</p>
</div>
{% endif %}

{% if feedback.feedbackStatus == 'approved' %}
Check out your {{ feedback.feedbackType }} on [The Modern Proper]({{ entry.url }}#feedback).
{% else %}
Your comment is pending, so you won't see it on the site.
{% endif %}

---

Cheers,

**The Modern Proper**