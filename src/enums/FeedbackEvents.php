<?php

namespace mortscode\feedback\enums;

/**
 * The FeedbackEvents class is an abstract class that defines all of the review types that are available.
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author mortscode
 * @since 1.0.0
 */
abstract class FeedbackEvents
{
    public const NewResponse = 'newResponse';
    public const NewApproval = 'newApproval';
    public const ResponseAndApproved = 'responseAndApproved';
}