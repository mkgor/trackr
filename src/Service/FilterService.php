<?php

namespace App\Service;

use App\Service\Entity\Dates;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FilterService
 * @package Service
 */
class FilterService
{
    /**
     * Gets the p_sort param from query and handles it.
     *
     * @param Request $request
     * @param string  $defaultSort
     *
     * @return string
     */
    public function getSort(Request $request, $defaultSort = 'desc')
    {
        $sort = $defaultSort;

        /**
         * If p_sort parameter is specified and have valid value - we use it.
         */
        if($request->query->has('p_sort')) {
            if($request->query->get('p_sort') == 'desc' || $request->query->get('p_sort') == 'asc') {
                $sort = $request->query->get('p_sort');
            }
        }

        return $sort;
    }

    /**
     * @param $min_date
     * @param $max_date
     * @param $parameterName
     *
     * @return Dates
     */
    public function handleMinMaxDates($min_date, $max_date, $parameterName)
    {
        $dates = new Dates();

        if(isset($min_date[0][$parameterName])) {
            $dates->setMinDate($min_date[0][$parameterName]->format('Y-m-d H:i:s'));
        }

        if(isset($max_date[0][$parameterName])) {
            $dates->setMaxDate($max_date[0][$parameterName]->format('Y-m-d H:i:s'));
        }

        return $dates;
    }
}