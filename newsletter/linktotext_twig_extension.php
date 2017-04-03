<?php

/*
 * This file is NOT part of Twig.
 *
 * 2017 Killian Stefanini
 *
 * Convert html link to text equivalent
 * Given
 *     <a href="url">text</>
 * Become
 *     text (url)
 */

class Twig_Extensions_Extension_LinkToText extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            new Twig_SimpleFilter('linktotext', 'linktotext'),
        );
    }

    public function getName()
    {
        return 'linktotext';
    }
}

function linktotext($text)
{
    return preg_replace_callback(
        '|<a href="(.*)">(.*)</a>|',
        function($matches) {
            return $matches[2] . ' (' . $matches[1] . ')';
        },
        $text
    );
}
