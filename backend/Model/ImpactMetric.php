<?php

namespace TTE\App\Model;

enum ImpactMetric {

    /**
     * Estimate of total CO2(kg) saved by a customer, based on the all-time number of bundles that they have collected.
     */
    case CO2_Saved;

    /**
     * Total (all-time) number of bundles collected by a customer.
     */
    case Bundles_Collected;
}
