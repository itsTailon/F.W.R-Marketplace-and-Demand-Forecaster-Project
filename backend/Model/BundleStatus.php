<?php
namespace TTE\App\Model;

enum BundleStatus: string {
    case Available = "available";
    case Reserved = "reserved";
    case Expired = "expired";
    case Collected = "collected";
    case Cancelled = "cancelled";
}
