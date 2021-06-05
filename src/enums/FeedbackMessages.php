<?php

namespace mortscode\feedback\enums;

/**
 * The FeedbackStatus class is an abstract class that defines all of the review status states that are available.
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author mortscode
 * @since 1.0.0
 */
abstract class FeedbackMessages
{
    public const MESSAGE_NEW_FEEDBACK = 'new_feedback';
    public const MESSAGE_FEEDBACK_RESPONSE = 'feedback_response';
}