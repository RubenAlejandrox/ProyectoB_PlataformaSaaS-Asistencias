<?php

use App\Models\Classroom;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('attendance.{classroomId}', function ($user, string $classroomId) {
    $classroom = Classroom::withoutGlobalScopes()->find($classroomId);

    if (!$classroom) {
        return false;
    }

    if ($user->hasRole('Teacher')) {
        return (string) $classroom->teacher_id === (string) $user->id;
    }

    if ($user->hasRole('Student')) {
        return Enrollment::withoutGlobalScopes()
            ->where('classroom_id', $classroomId)
            ->where('student_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    return false;
});

Broadcast::channel('progress.{studentId}', function ($user, string $studentId) {
    return (string) $user->id === (string) $studentId;
});
