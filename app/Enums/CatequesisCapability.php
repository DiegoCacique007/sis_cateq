<?php

namespace App\Enums;

enum CatequesisCapability: string
{
    case ViewGroups = 'VIEW_GROUPS';
    case ViewGroupStudents = 'VIEW_GROUP_STUDENTS';
    case ViewAttendanceList = 'VIEW_ATTENDANCE_LIST';
    case ViewEvaluations = 'VIEW_EVALUATIONS';
    case ManageEvaluations = 'MANAGE_EVALUATIONS';
    case ViewBoletas = 'VIEW_BOLETAS';
    case ViewStudents = 'VIEW_STUDENTS';
    case ViewCommunities = 'VIEW_COMMUNITIES';
    case ViewCatechists = 'VIEW_CATECHISTS';
    case ViewTutors = 'VIEW_TUTORS';
    case ViewInscriptions = 'VIEW_INSCRIPTIONS';
    case ViewLevels = 'VIEW_LEVELS';
    case ManageStudents = 'MANAGE_STUDENTS';
    case ManageTutors = 'MANAGE_TUTORS';
    case ManageInscriptions = 'MANAGE_INSCRIPTIONS';
    case ManageAssignments = 'MANAGE_ASSIGNMENTS';
    case ManageCommunities = 'MANAGE_COMMUNITIES';
    case ManagePeriods = 'MANAGE_PERIODS';
    case ManageLevels = 'MANAGE_LEVELS';
    case ManageUnits = 'MANAGE_UNITS';
    case ManageRubrics = 'MANAGE_RUBRICS';
    case ManageUsers = 'MANAGE_USERS';
    case ManageGroups = 'MANAGE_GROUPS';
}
