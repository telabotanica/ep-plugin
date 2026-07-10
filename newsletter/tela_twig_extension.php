<?php

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Twig_Extensions_Extension_Tela extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('addstyletolinks', 'addstyletolinks'),
            new TwigFilter('linktotext', 'linktotext'),
            new TwigFilter('unescape', 'unescape'),
        ];
    }

    public function getName()
    {
        return 'tela';
    }
}

function addstyletolinks($text, $style)
{
    return preg_replace_callback(
        '|<a href="(.*)">(.*)</a>|',
        function ($matches) use ($style) {
            return '<a href="' . $matches[1] . '" ' . $style . '>' . $matches[2] . '</a>';
        },
        $text
    );
}

function linktotext($text)
{
    return preg_replace_callback(
        '|<a href="(.*)">(.*)</a>|',
        function ($matches) {
            return $matches[2] . ' (' . $matches[1] . ')';
        },
        $text
    );
}

function unescape($value)
{
    return html_entity_decode($value);
}
