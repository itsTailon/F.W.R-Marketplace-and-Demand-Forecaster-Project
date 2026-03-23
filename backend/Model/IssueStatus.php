<?php
namespace TTE\App\Model;

enum IssueStatus: string {
    case Ongoing = "ongoing";
    case Resolved = "resolved";
}
