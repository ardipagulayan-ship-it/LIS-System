<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sigma Metric Thresholds
    |--------------------------------------------------------------------------
    |
    | These thresholds define the quality levels for Sigma metrics.
    | These values are based on Six Sigma quality standards.
    |
    */
    'thresholds' => [
        'excellent' => env('SIGMA_THRESHOLD_EXCELLENT', 6.0),
        'good' => env('SIGMA_THRESHOLD_GOOD', 4.0),
        'critical' => env('SIGMA_THRESHOLD_CRITICAL', 4.0), // Values below this trigger alerts
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Calculation Period
    |--------------------------------------------------------------------------
    |
    | Default number of days to look back when calculating Sigma metrics
    | from rolling QC data.
    |
    */
    'default_period_days' => env('SIGMA_DEFAULT_PERIOD_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Trend Chart Period
    |--------------------------------------------------------------------------
    |
    | Default number of days to display in the trend chart.
    |
    */
    'default_trend_days' => env('SIGMA_DEFAULT_TREND_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Minimum QC Data Points
    |--------------------------------------------------------------------------
    |
    | Minimum number of QC data points required for a valid Sigma calculation.
    |
    */
    'min_data_points' => env('SIGMA_MIN_DATA_POINTS', 2),

    /*
    |--------------------------------------------------------------------------
    | High Sigma Value
    |--------------------------------------------------------------------------
    |
    | When standard deviation is zero (perfect QC), return this high value.
    |
    */
    'high_sigma_value' => env('SIGMA_HIGH_VALUE', 99.99),
];



