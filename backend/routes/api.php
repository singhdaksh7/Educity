<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Api\AdmissionApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EnquiryController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\Student\ApplicationController as StudentApplicationController;
use App\Http\Controllers\Api\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Api\Student\EnquiryController as StudentEnquiryController;
use App\Http\Controllers\Api\Student\StudentAuthController;
use App\Http\Controllers\Api\TestimonialController;
use Illuminate\Support\Facades\Route;

Route::get('health', static fn () => response()->json([
    'success' => true,
    'data' => ['status' => 'ok'],
]));

Route::prefix('v1')->group(function () {

    /*
    |----------------------------------------------------------------------
    | Public endpoints
    |----------------------------------------------------------------------
    */
    Route::middleware('throttle:public-form')->group(function () {
        Route::post('enquiries', [EnquiryController::class, 'store']);
        Route::post('admission-applications', [AdmissionApplicationController::class, 'store']);
    });

    Route::get('programs', [ProgramController::class, 'publicIndex']);
    Route::get('programs/{slug}', [ProgramController::class, 'publicShow']);
    Route::get('testimonials', [TestimonialController::class, 'publicIndex']);
    Route::get('gallery', [GalleryController::class, 'publicIndex']);
    Route::get('site-content', [SiteSettingController::class, 'publicContent']);

    /*
    |----------------------------------------------------------------------
    | Student authentication + self-service
    |----------------------------------------------------------------------
    */
    Route::prefix('student')->group(function () {
        Route::middleware('throttle:student-register')->post('auth/register', [StudentAuthController::class, 'register']);
        Route::middleware('throttle:student-login')->post('auth/login', [StudentAuthController::class, 'login']);
        Route::middleware('throttle:forgot-password')->post('auth/forgot-password', [StudentAuthController::class, 'forgotPassword']);
        Route::post('auth/reset-password', [StudentAuthController::class, 'resetPassword']);

        Route::middleware(['auth:sanctum', 'student.active'])->group(function () {
            Route::get('auth/me', [StudentAuthController::class, 'me']);
            Route::post('auth/logout', [StudentAuthController::class, 'logout']);
            Route::patch('auth/profile', [StudentAuthController::class, 'updateProfile']);
            Route::patch('auth/password', [StudentAuthController::class, 'updatePassword']);

            Route::get('dashboard', [StudentDashboardController::class, 'index']);

            Route::prefix('applications')->group(function () {
                Route::get('/', [StudentApplicationController::class, 'index']);
                Route::post('/', [StudentApplicationController::class, 'store']);
                Route::get('{application}', [StudentApplicationController::class, 'show'])->whereNumber('application');
                Route::patch('{application}/withdraw', [StudentApplicationController::class, 'withdraw'])->whereNumber('application');
            });

            Route::prefix('enquiries')->group(function () {
                Route::get('/', [StudentEnquiryController::class, 'index']);
                Route::post('/', [StudentEnquiryController::class, 'store']);
                Route::get('{enquiry}', [StudentEnquiryController::class, 'show'])->whereNumber('enquiry');
            });
        });
    });

    /*
    |----------------------------------------------------------------------
    | Admin authentication
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {
        Route::middleware('throttle:admin-login')->post('auth/login', [AuthController::class, 'login']);
        Route::middleware('throttle:forgot-password')->post('auth/forgot-password', [AuthController::class, 'forgot']);
        Route::post('auth/reset-password', [AuthController::class, 'reset']);

        Route::middleware(['auth:sanctum', 'admin.active'])->group(function () {
            Route::get('auth/me', [AuthController::class, 'me']);
            Route::post('auth/logout', [AuthController::class, 'logout']);
            Route::patch('auth/password', [AuthController::class, 'password']);

            /*
            |------------------------------------------------------------
            | Dashboard
            |------------------------------------------------------------
            */
            Route::middleware('can:dashboard.view')->get('dashboard', [DashboardController::class, 'index']);

            /*
            |------------------------------------------------------------
            | Enquiries
            |------------------------------------------------------------
            */
            Route::middleware('can:enquiries.manage')->prefix('enquiries')->group(function () {
                Route::get('/', [EnquiryController::class, 'index']);
                Route::get('export', [EnquiryController::class, 'export']);
                Route::get('{enquiry}', [EnquiryController::class, 'show']);
                Route::patch('{enquiry}', [EnquiryController::class, 'update']);
                Route::delete('{enquiry}', [EnquiryController::class, 'destroy']);
                Route::post('{id}/restore', [EnquiryController::class, 'restore'])->whereNumber('id');
                Route::middleware('can:records.forceDelete')->delete('{id}/force', [EnquiryController::class, 'forceDelete'])->whereNumber('id');
            });

            /*
            |------------------------------------------------------------
            | Admission applications
            |------------------------------------------------------------
            */
            Route::middleware('can:applications.manage')->prefix('admission-applications')->group(function () {
                Route::get('/', [AdmissionApplicationController::class, 'index']);
                Route::get('export', [AdmissionApplicationController::class, 'export']);
                Route::get('{admissionApplication}', [AdmissionApplicationController::class, 'show']);
                Route::patch('{admissionApplication}', [AdmissionApplicationController::class, 'update']);
                Route::delete('{admissionApplication}', [AdmissionApplicationController::class, 'destroy']);
                Route::post('{id}/restore', [AdmissionApplicationController::class, 'restore'])->whereNumber('id');
                Route::middleware('can:records.forceDelete')->delete('{id}/force', [AdmissionApplicationController::class, 'forceDelete'])->whereNumber('id');
            });

            /*
            |------------------------------------------------------------
            | Programs
            |------------------------------------------------------------
            */
            Route::middleware('can:programs.manage')->prefix('programs')->group(function () {
                Route::get('/', [ProgramController::class, 'index']);
                Route::post('/', [ProgramController::class, 'store']);
                Route::patch('reorder', [ProgramController::class, 'reorder']);
                Route::get('{program}', [ProgramController::class, 'show']);
                Route::patch('{program}', [ProgramController::class, 'update']);
                Route::delete('{program}', [ProgramController::class, 'destroy']);
                Route::patch('{program}/activate', [ProgramController::class, 'activate']);
                Route::patch('{program}/deactivate', [ProgramController::class, 'deactivate']);
                Route::patch('{program}/feature', [ProgramController::class, 'feature']);
                Route::patch('{program}/unfeature', [ProgramController::class, 'unfeature']);
                Route::post('{id}/restore', [ProgramController::class, 'restore'])->whereNumber('id');
                Route::middleware('can:records.forceDelete')->delete('{id}/force', [ProgramController::class, 'forceDelete'])->whereNumber('id');
            });

            /*
            |------------------------------------------------------------
            | Testimonials
            |------------------------------------------------------------
            */
            Route::middleware('can:testimonials.manage')->prefix('testimonials')->group(function () {
                Route::get('/', [TestimonialController::class, 'index']);
                Route::post('/', [TestimonialController::class, 'store']);
                Route::patch('reorder', [TestimonialController::class, 'reorder']);
                Route::get('{testimonial}', [TestimonialController::class, 'show']);
                Route::patch('{testimonial}', [TestimonialController::class, 'update']);
                Route::delete('{testimonial}', [TestimonialController::class, 'destroy']);
                Route::patch('{testimonial}/activate', [TestimonialController::class, 'activate']);
                Route::patch('{testimonial}/deactivate', [TestimonialController::class, 'deactivate']);
                Route::patch('{testimonial}/feature', [TestimonialController::class, 'feature']);
                Route::patch('{testimonial}/unfeature', [TestimonialController::class, 'unfeature']);
                Route::post('{id}/restore', [TestimonialController::class, 'restore'])->whereNumber('id');
                Route::middleware('can:records.forceDelete')->delete('{id}/force', [TestimonialController::class, 'forceDelete'])->whereNumber('id');
            });

            /*
            |------------------------------------------------------------
            | Gallery
            |------------------------------------------------------------
            */
            Route::middleware('can:gallery.manage')->prefix('gallery')->group(function () {
                Route::get('/', [GalleryController::class, 'index']);
                Route::post('/', [GalleryController::class, 'store']);
                Route::patch('reorder', [GalleryController::class, 'reorder']);
                Route::get('{gallery}', [GalleryController::class, 'show']);
                Route::patch('{gallery}', [GalleryController::class, 'update']);
                Route::delete('{gallery}', [GalleryController::class, 'destroy']);
                Route::patch('{gallery}/activate', [GalleryController::class, 'activate']);
                Route::patch('{gallery}/deactivate', [GalleryController::class, 'deactivate']);
                Route::post('{id}/restore', [GalleryController::class, 'restore'])->whereNumber('id');
                Route::middleware('can:records.forceDelete')->delete('{id}/force', [GalleryController::class, 'forceDelete'])->whereNumber('id');
            });

            /*
            |------------------------------------------------------------
            | Students
            |------------------------------------------------------------
            */
            Route::middleware('can:students.manage')->prefix('students')->group(function () {
                Route::get('/', [AdminStudentController::class, 'index']);
                Route::get('{student}', [AdminStudentController::class, 'show'])->whereNumber('student');
                Route::patch('{student}', [AdminStudentController::class, 'update'])->whereNumber('student');
                Route::patch('{student}/activate', [AdminStudentController::class, 'activate'])->whereNumber('student');
                Route::patch('{student}/deactivate', [AdminStudentController::class, 'deactivate'])->whereNumber('student');
            });

            /*
            |------------------------------------------------------------
            | Site settings
            |------------------------------------------------------------
            */
            Route::get('site-settings', [SiteSettingController::class, 'index']);
            Route::patch('site-settings', [SiteSettingController::class, 'update']);

            /*
            |------------------------------------------------------------
            | Activity logs (super_admin only)
            |------------------------------------------------------------
            */
            Route::middleware('can:logs.view')->get('activity-logs', [ActivityLogController::class, 'index']);
        });
    });
});
