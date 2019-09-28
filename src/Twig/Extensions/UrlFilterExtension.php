<?php

namespace App\Twig\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class UrlFilterExtension
 * @package App\Twig\Extensions
 */
class UrlFilterExtension extends AbstractExtension
{
    /**
     * @return array|TwigFilter[]
     */
    public function getFilters()
    {
        return array(
            'url_filter' => new TwigFilter('url_filter',[$this, 'transform'])
        );
    }

    /**
     * @param $url
     * @return string
     */
    public function transform($url)
    {
        if(!strpos($url, 'http://')) {
            $url = 'http://' . $url;
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'urlfilter_extension';
    }
}