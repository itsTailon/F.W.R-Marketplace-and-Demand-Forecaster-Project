<?php
namespace TTE\App\Model;

enum SellerRegistrationRequestStatus: string {
    case Pending = "pending";
    case Closed = "closed";

}
