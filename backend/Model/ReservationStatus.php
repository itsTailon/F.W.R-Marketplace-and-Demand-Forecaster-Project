<?php
namespace TTE\App\Model;

enum ReservationStatus: string {
    case Active = "active";
    case Completed = "completed";
    case NoShow = "no-show";
    case Cancelled = "cancelled";
}
