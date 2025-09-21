<?php

namespace App\Policies;

use App\Models\CaseReference;
use App\Models\User;
use App\Models\CaseParticipant;

class CaseReferencePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        // Can view any if user is a participant in at least one case
        return $user->caseParticipations()->active()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        // Can view if user is a participant in this case
        return $user->canAccessCase($caseReference);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        // Only referees can create new cases
        return $user->isRefereeInCase();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        // Only referees (or co_referee/chairman) in this case can update
        return $user->canEditCase($caseReference);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        // Only main referee or admin can delete
        return $user->canDeleteCase($caseReference);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage participants in the case.
     */
    public function manageParticipants(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->hasPermissionInCase($caseReference->id, 'manage_participants');
    }

    /**
     * Determine whether the user can view internal notes for the case.
     */
    public function viewInternalNotes(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->hasPermissionInCase($caseReference->id, 'access_internal_notes');
    }

    /**
     * Determine whether the user can access internal messages for the case.
     */
    public function accessInternalMessages(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->hasPermissionInCase($caseReference->id, 'access_internal_messages');
    }

    /**
     * Determine whether the user can upload documents for the case.
     */
    public function uploadDocuments(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->hasPermissionInCase($caseReference->id, 'upload_documents');
    }

    /**
     * Determine whether the user can view all documents for the case.
     */
    public function viewAllDocuments(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->hasPermissionInCase($caseReference->id, 'view_all_documents');
    }

    /**
     * Determine whether the user can set deadlines for the case.
     */
    public function setDeadlines(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->hasPermissionInCase($caseReference->id, 'set_deadlines');
    }

    /**
     * Determine whether the user can create calendar events for the case.
     */
    public function createCalendarEvents(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->hasPermissionInCase($caseReference->id, 'create_calendar_events');
    }

    /**
     * Determine whether the user can create decisions for the case.
     */
    public function createDecisions(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->hasPermissionInCase($caseReference->id, 'create_decisions');
    }

    /**
     * Determine whether the user can schedule hearings for the case.
     */
    public function scheduleHearings(User $user, CaseReference $caseReference): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->hasPermissionInCase($caseReference->id, 'schedule_hearings');
    }
}