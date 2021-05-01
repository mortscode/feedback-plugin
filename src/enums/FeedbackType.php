<?php

namespace mortscode\feedback\enums;

/**
 * The FeedbackType class is an abstract class that defines all of the review types that are available.
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author mortscode
 * @since 1.0.0
 */
abstract class FeedbackType
{
    public const Review = 'review';
    public const Question = 'question';
}