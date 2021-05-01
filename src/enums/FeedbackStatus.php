<?php

namespace mortscode\feedback\enums;

/**
 * The FeedbackStatus class is an abstract class that defines all of the review status states that are available.
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author mortscode
 * @since 1.0.0
 */
abstract class FeedbackStatus
{
    public const Approved = 'approved';
    public const Pending = 'pending';
    public const Spam = 'spam';
}