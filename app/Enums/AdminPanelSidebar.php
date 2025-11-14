<?php

namespace App\Enums;

enum AdminPanelSidebar: int
{
    case DEPARTMENTS = 1;
    case CLIENTS = 2;
    case PROJECTS = 3;
    case TASKS = 4;
    case CALENDAR = 5;
    case REPORTS = 6;
    case USERS = 7;
    case ARCHIVED_USERS = 8;
    case ROLES = 9;
    case SALES = 10;
    case SETTINGS = 11;
    case ACTIVITY_LOGS = 12;
    case EVENTS = 13;
}
