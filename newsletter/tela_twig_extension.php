<?php

/*
 * This file is NOT part of Twig.
 *
 * 2017 Killian Stefanini
 *
 */

class Twig_Extensions_Extension_Tela extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            new Twig_SimpleFilter('addstyletolinks', 'addstyletolinks'),
            new Twig_SimpleFilter('linktotext', 'linktotext'),
            new Twig_SimpleFilter('unescape', 'unescape'),
        );
    }

    public function getName()
    {
        return 'tela';
    }
}

/**
 * Adds some style to href tag in given text
 *
 * @param      <type>  $text   The text
 *
 * @return     <type>  ( description_of_the_return_value )
 */
function addstyletolinks($text, $style)
{
    return preg_replace_callback(
        '|<a href="(.*)">(.*)</a>|',
        function($matches) use ($style) {
            return '<a href="' . $matches[1] . '" ' . $style . '>' . $matches[2] . '</a>';
        },
        $text
    );
}


/**
 * Convert html link to text equivalent
 * Given
 *     <a href="url">text</>
 * Become
 *     text (url)
 *
 * @param      <string>  $text   The text
 *
 * @return     <string>  Modified text
 */
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

/**
 * Usefull when raw is not enough. (or unsecure)
 * Given an escaped string with something like htmlspecialchars, this filter
 * could save your life!
 *
 * @param      <string>  $value  The value
 *
 * @return     <string>  ( description_of_the_return_value )
 */
function unescape($value)
{
    return html_entity_decode($value);
}
