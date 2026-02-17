<?php
namespace TTE\App\Model;

enum BundleStatus: string {
    case Available = "available";
    case Reserved = "reserved";
    case Collected = "collected";
    case Cancelled = "cancelled";
    case Expired = "expired";
}
