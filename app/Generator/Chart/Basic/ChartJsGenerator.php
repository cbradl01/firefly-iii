<?php

/**
 * ChartJsGenerator.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Generator\Chart\Basic;

use FireflyIII\Support\ChartColour;

/**
 * Class ChartJsGenerator.
 */
class ChartJsGenerator implements GeneratorInterface
{
    /**
     * Expects data as:.
     *
     * key => [value => x, 'currency_symbol' => 'x']
     * 
     * Automatically groups small slices into "Others" category to improve readability.
     * Configuration: firefly.charts.pie_chart.max_slices and firefly.charts.pie_chart.min_percentage
     */
    public function multiCurrencyPieChart(array $data): array
    {
        $chartData = [
            'datasets' => [
                0 => [],
            ],
            'labels'   => [],
        ];

        // Configuration for pie chart grouping
        $maxSlices = config('firefly.charts.pie_chart.max_slices', 7);
        $minPercentage = config('firefly.charts.pie_chart.min_percentage', 5);

        $amounts   = array_column($data, 'amount');
        $next      = next($amounts);
        $sortFlag  = SORT_ASC;
        if (!is_bool($next) && 1 === bccomp((string) $next, '0')) {
            $sortFlag = SORT_DESC;
        }
        array_multisort($amounts, $sortFlag, $data);
        unset($next, $sortFlag, $amounts);

        // Calculate total amount for percentage calculations
        $totalAmount = '0';
        foreach ($data as $valueArray) {
            $totalAmount = bcadd($totalAmount, (string) $valueArray['amount']);
        }

        // Group data into top slices and others
        $processedData = [];
        $othersData = [
            'amount' => '0',
            'currency_symbol' => '',
            'currency_code' => '',
            'count' => 0
        ];
        $index = 0;

        foreach ($data as $key => $valueArray) {
            $amount = (string) $valueArray['amount'];
            $percentage = $totalAmount > 0 ? bcmul(bcdiv($amount, $totalAmount, 4), '100', 2) : '0';
            
            // Include as individual slice if:
            // 1. We haven't reached maxSlices yet, OR
            // 2. The percentage is above minPercentage
            if ($index < $maxSlices || bccomp($percentage, (string) $minPercentage) >= 0) {
                $processedData[$key] = $valueArray;
            } else {
                // Group into "others"
                $othersData['amount'] = bcadd($othersData['amount'], $amount);
                if (empty($othersData['currency_symbol'])) {
                    $othersData['currency_symbol'] = $valueArray['currency_symbol'];
                    $othersData['currency_code'] = $valueArray['currency_code'];
                }
                $othersData['count']++;
            }
            $index++;
        }

        // Add "others" group if there are grouped items
        if ($othersData['count'] > 0) {
            $othersLabel = $othersData['count'] === 1 
                ? 'Other' 
                : sprintf('Others (%d items)', $othersData['count']);
            $processedData[$othersLabel] = [
                'amount' => $othersData['amount'],
                'currency_symbol' => $othersData['currency_symbol'],
                'currency_code' => $othersData['currency_code']
            ];
        }

        $index = 0;
        foreach ($processedData as $key => $valueArray) {
            // make larger than 0
            $chartData['datasets'][0]['data'][]            = app('steam')->positive((string) $valueArray['amount']);
            $chartData['datasets'][0]['backgroundColor'][] = ChartColour::getColour($index);
            $chartData['datasets'][0]['currency_symbol'][] = $valueArray['currency_symbol'];
            $chartData['labels'][]                         = $key;
            ++$index;
        }

        return $chartData;
    }

    /**
     * Will generate a Chart JS compatible array from the given input. Expects this format.
     *
     * Will take labels for all from first set.
     *
     * 0: [
     *    'label' => 'label of set',
     *    'type' => bar or line, optional
     *    'yAxisID' => ID of yAxis, optional, will not be included when unused.
     *    'fill' => if to fill a line? optional, will not be included when unused.
     *    'currency_symbol' => 'x',
     *    'backgroundColor' => 'x',
     *    'entries' =>
     *        [
     *         'label-of-entry' => 'value'
     *        ]
     *    ]
     * 1: [
     *    'label' => 'label of another set',
     *    'type' => bar or line, optional
     *    'yAxisID' => ID of yAxis, optional, will not be included when unused.
     *    'fill' => if to fill a line? optional, will not be included when unused.
     *    'entries' =>
     *        [
     *         'label-of-entry' => 'value'
     *        ]
     *    ]
     *
     *  // it's five.
     */
    public function multiSet(array $data): array
    {
        reset($data);
        $first     = current($data);
        if (!is_array($first)) {
            return [];
        }
        $labels    = is_array($first['entries']) ? array_keys($first['entries']) : [];

        $chartData = [
            'count'    => count($data),
            'labels'   => $labels, // take ALL labels from the first set.
            'datasets' => [],
        ];
        unset($first, $labels);

        foreach ($data as $set) {
            $currentSet              = [
                'label' => $set['label'] ?? '(no label)',
                'type'  => $set['type'] ?? 'line',
                'data'  => array_values($set['entries']),
            ];
            if (array_key_exists('yAxisID', $set)) {
                $currentSet['yAxisID'] = $set['yAxisID'];
            }
            if (array_key_exists('fill', $set)) {
                $currentSet['fill'] = $set['fill'];
            }
            if (array_key_exists('currency_symbol', $set)) {
                $currentSet['currency_symbol'] = $set['currency_symbol'];
            }
            if (array_key_exists('backgroundColor', $set)) {
                $currentSet['backgroundColor'] = $set['backgroundColor'];
            }
            $chartData['datasets'][] = $currentSet;
        }

        return $chartData;
    }

    /**
     * Expects data as:.
     *
     * key => value
     * 
     * Automatically groups small slices into "Others" category to improve readability.
     * Configuration: firefly.charts.pie_chart.max_slices and firefly.charts.pie_chart.min_percentage
     */
    public function pieChart(array $data): array
    {
        $chartData = [
            'datasets' => [
                0 => [],
            ],
            'labels'   => [],
        ];

        // Configuration for pie chart grouping
        $maxSlices = config('firefly.charts.pie_chart.max_slices', 7);
        $minPercentage = config('firefly.charts.pie_chart.min_percentage', 5);

        // sort by value, keep keys.
        // different sort when values are positive and when they're negative.
        asort($data);
        $next      = next($data);
        if (!is_bool($next) && 1 === bccomp((string) $next, '0')) {
            // next is positive, sort other way around.
            arsort($data);
        }
        unset($next);

        // Calculate total amount for percentage calculations
        $totalAmount = '0';
        foreach ($data as $value) {
            $totalAmount = bcadd($totalAmount, (string) $value);
        }

        // Group data into top slices and others
        $processedData = [];
        $othersAmount = '0';
        $othersCount = 0;
        $index = 0;

        foreach ($data as $key => $value) {
            $amount = (string) $value;
            $percentage = $totalAmount > 0 ? bcmul(bcdiv($amount, $totalAmount, 4), '100', 2) : '0';
            
            // Include as individual slice if:
            // 1. We haven't reached maxSlices yet, OR
            // 2. The percentage is above minPercentage
            if ($index < $maxSlices || bccomp($percentage, (string) $minPercentage) >= 0) {
                $processedData[$key] = $value;
            } else {
                // Group into "others"
                $othersAmount = bcadd($othersAmount, $amount);
                $othersCount++;
            }
            $index++;
        }

        // Add "others" group if there are grouped items
        if ($othersCount > 0) {
            $othersLabel = $othersCount === 1 
                ? 'Other' 
                : sprintf('Others (%d items)', $othersCount);
            $processedData[$othersLabel] = $othersAmount;
        }

        $index = 0;
        foreach ($processedData as $key => $value) {
            // make larger than 0
            $chartData['datasets'][0]['data'][]            = app('steam')->positive((string) $value);
            $chartData['datasets'][0]['backgroundColor'][] = ChartColour::getColour($index);

            $chartData['labels'][]                         = $key;
            ++$index;
        }

        return $chartData;
    }

    /**
     * Will generate a (ChartJS) compatible array from the given input. Expects this format:.
     *
     * 'label-of-entry' => value
     */
    public function singleSet(string $setLabel, array $data): array
    {
        return [
            'count'    => 1,
            'labels'   => array_keys($data), // take ALL labels from the first set.
            'datasets' => [
                [
                    'label' => $setLabel,
                    'data'  => array_values($data),
                ],
            ],
        ];
    }
}
