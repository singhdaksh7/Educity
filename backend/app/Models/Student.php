<?php

namespace App\Models;

/**
 * Same `users` table as User, scoped to role=student. Used as the Sanctum /
 * password-broker provider for the student auth flows so admin and student
 * accounts can never be looked up, authenticated, or reset through the
 * wrong flow even if an email happens to collide across a future import.
 */
class Student extends User
{
    // Eloquent infers the table name per-class (not inherited), so without
    // this a Student query would hit a non-existent "students" table.
    protected $table = 'users';

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('student', function ($query) {
            $query->where('role', 'student');
        });
    }
}
