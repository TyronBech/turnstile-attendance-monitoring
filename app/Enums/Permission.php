<?php

namespace App\Enums;

enum Permission: string
{
    case ViewDashboard = 'view_dashboard';
    case ViewMonitoring = 'view_monitoring';
    case ViewUsers = 'view_users';
    case CreateUser = 'create_user';
    case EditUser = 'edit_user';
    case DeleteUser = 'delete_user';
    case ImportUsers = 'import_users';
    case ViewReports = 'view_reports';
    case ExportReports = 'export_reports';
    case ManageRolesAndPermissions = 'manage_roles_and_permissions';
    case AccessSettings = 'access_settings';
    case ViewAdmins = 'view_admins';
    case CreateAdmins = 'create_admins';
    case EditAdmins = 'edit_admins';
    case DeleteAdmins = 'delete_admins';
}
