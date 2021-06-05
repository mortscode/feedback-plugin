{% set entry = craft.entries.id(feedback.entryId).one() %}
{% set emailHeaderUrl = craft.feedback.getEmailHeaderUrl %}

{% if emailHeaderUrl %}
<div with="500" style="width:100%; max-width:500px;">
    <img src="{{ emailHeaderUrl }}" alt="brand header image" style="width:100%;height:auto;">
</div>
{% endif %}

### Hey {{ feedback.name ? feedback.name | split(' ')[0] : 'friend' }},

Thanks for your {{ feedback.feedbackType }} on our [{{ entry.title }}]({{ entry.url }}) page:

<div style="background-color:#F7F6F2;color:#222222;padding:18px">
{% if feedback.rating %}
{% set remainingStars = 5 - feedback.rating %}
<p><span style="font-weight: bold">Rating:</span> 
{% for i in 1..feedback.rating %}&#9733;{% endfor %}
{% if remainingStars > 0%}{% for i in 1..remainingStars %}&#9734;{% endfor %}{% endif %}</p>
<p><span style="font-weight: bold">Comment:</span></br>
{{ feedback.comment }}</p>
{% else %}
<p>{{ feedback.comment }}</p>
{% endif %}
</div>

Your {{ feedback.feedbackType }} is currently pending. We'll take a look and get back to you soon!

---

Cheers,

**The Modern Proper**