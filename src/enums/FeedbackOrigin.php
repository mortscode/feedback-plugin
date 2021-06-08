<?php

namespace mortscode\feedback\enums;

/**
 * The FeedbackStatus class is an abstract class that defines all of the feedback origins that are available.
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author mortscode
 * @since 1.0.0
 */
abstract class FeedbackOrigin
{
    public const FRONTEND = 'origin_frontend';
    public const CONTROL_PANEL = 'origin_cp';
    public const IMPORT_DISQUS = 'origin_disqus';
}