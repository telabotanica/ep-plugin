<?php

/*
 * This file is NOT part of Twig.
 *
 * 2017 Killian Stefanini
 *
 * Usefull when raw is not enough. (or unsecure)
 * Given an escaped string with something like htmlspecialchars, this filter
 * could save your life!
 */

class Twig_Extensions_Extension_Unescape extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            new Twig_SimpleFilter('unescape', 'unescape'),
        );
    }

    public function getName()
    {
        return 'unescape';
    }
}

function unescape($value)
{
    return html_entity_decode($value);
}
