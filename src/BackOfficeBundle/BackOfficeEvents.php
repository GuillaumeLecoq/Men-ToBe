<?php

namespace BackOfficeBundle;

/**
 * Contains all events thrown in the BackOfficeBundle
 */
final class BackOfficeEvents
{
    /*
    * Add Event when article is created in order to follow this actity on timeline (into profile of specific user)
    */
    const POST_ARTICLE = 'backofficebundle.article_event';
    const POST_MESSAGE = 'backofficebundle.message_event';

}