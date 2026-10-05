<?php

namespace App\Enums;

enum ChatbotIntent: string
{
    case HelpSystem = 'HELP_SYSTEM';
    case ViewMyGroups = 'VIEW_MY_GROUPS';
    case ViewGroupStudents = 'VIEW_GROUP_STUDENTS';
    case ViewAttendanceList = 'VIEW_ATTENDANCE_LIST';
    case ViewEvaluations = 'VIEW_EVALUATIONS';
    case ViewBoleta = 'VIEW_BOLETA';
    case HowToRecordEvaluations = 'HOW_TO_RECORD_EVALUATIONS';
    case HowToRegisterStudent = 'HOW_TO_REGISTER_STUDENT';
    case HowToRegisterTutor = 'HOW_TO_REGISTER_TUTOR';
    case HowToRegisterInscription = 'HOW_TO_REGISTER_INSCRIPTION';
    case HowToAssignGroup = 'HOW_TO_ASSIGN_GROUP';
    case HowToManageCommunities = 'HOW_TO_MANAGE_COMMUNITIES';
    case HowToManagePeriods = 'HOW_TO_MANAGE_PERIODS';
    case HowToManageLevels = 'HOW_TO_MANAGE_LEVELS';
    case HowToManageUnits = 'HOW_TO_MANAGE_UNITS';
    case HowToManageRubrics = 'HOW_TO_MANAGE_RUBRICS';
    case HowToManageUsers = 'HOW_TO_MANAGE_USERS';
    case ModifyStudent = 'MODIFY_STUDENT';
    case ModifyAdministrativeData = 'MODIFY_ADMINISTRATIVE_DATA';
    case Unknown = 'UNKNOWN';
}
