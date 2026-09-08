<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationRequest;
use App\Mail\AdmissionAcknowledgementMail;
use App\Mail\NewAdmissionApplicationAdminMail;
use App\Models\AdmissionApplication;
use App\Models\Program;
use App\Services\ActivityLogger;
use App\Services\CsvExportService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdmissionApplicationController extends Controller
{
    private const SORTABLE = ['created_at', 'full_name', 'status', 'reviewed_at'];

    public function store(StoreApplicationRequest $request): JsonResponse
    {
        // Honeypot field: real visitors never fill this hidden input.
        if ($request->filled('website')) {
            return response()->json(['success' => false, 'message' => 'Invalid submission.', 'errors' => (object) []], 422);
        }

        if (! Program::whereKey($request->input('program_id'))->where('is_active', true)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Selected program is unavailable.',
                'errors' => ['program_id' => ['Selected program is unavailable.']],
            ], 422);
        }

        $application = $this->createWithUniqueNumber($request->safe()->except('website'));

        try {
            Mail::to($application->email)->send(new AdmissionAcknowledgementMail($application));
            if (config('mail.admin_notification_email')) {
                Mail::to(config('mail.admin_notification_email'))->send(new NewAdmissionApplicationAdminMail($application));
            }
        } catch (\Throwable $e) {
            Log::warning('Application notification email failed', ['application_id' => $application->id, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Your application has been received.',
            'data' => ['application_number' => $application->application_number],
        ], 201);
    }

    /**
     * Persist the application, retrying on the rare chance the generated
     * number collides with one written by a concurrent request — the
     * database unique constraint is the real safety net.
     */
    private function createWithUniqueNumber(array $data): AdmissionApplication
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $number = 'EDU-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

            try {
                return AdmissionApplication::create([...$data, 'application_number' => $number]);
            } catch (QueryException $e) {
                if (! str_contains($e->getMessage(), 'application_number')) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Unable to generate a unique application number.');
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Applications retrieved.',
            'data' => $this->filtered($request)->paginate(min((int) $request->get('per_page', 20), 100)),
        ]);
    }

    public function show(AdmissionApplication $admissionApplication): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Application retrieved.',
            'data' => $admissionApplication->load('program:id,title', 'reviewer:id,name'),
        ]);
    }

    public function update(UpdateApplicationRequest $request, AdmissionApplication $admissionApplication): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['status'])) {
            $data['reviewed_by'] = $request->user()->id;
            $data['reviewed_at'] = now();
        }

        $admissionApplication->update($data);

        ActivityLogger::log($request, 'application_updated', 'admission_application', $admissionApplication->id, 'Updated application', $data);

        return response()->json(['success' => true, 'message' => 'Application updated.', 'data' => $admissionApplication->fresh()]);
    }

    public function destroy(Request $request, AdmissionApplication $admissionApplication): JsonResponse
    {
        $admissionApplication->delete();

        ActivityLogger::log($request, 'application_deleted', 'admission_application', $admissionApplication->id, 'Soft-deleted application');

        return response()->json(['success' => true, 'message' => 'Application deleted.', 'data' => (object) []]);
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $application = AdmissionApplication::onlyTrashed()->findOrFail($id);
        $application->restore();

        ActivityLogger::log($request, 'application_restored', 'admission_application', $application->id, 'Restored application');

        return response()->json(['success' => true, 'message' => 'Application restored.', 'data' => $application]);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $application = AdmissionApplication::onlyTrashed()->findOrFail($id);
        $application->forceDelete();

        ActivityLogger::log($request, 'application_permanently_deleted', 'admission_application', $id, 'Permanently deleted application');

        return response()->json(['success' => true, 'message' => 'Application permanently deleted.', 'data' => (object) []]);
    }

    public function export(Request $request, CsvExportService $csv): StreamedResponse
    {
        $query = $this->filtered($request);

        ActivityLogger::log($request, 'applications_exported', 'admission_application', null, 'Exported applications CSV', $request->only('search', 'status', 'program_id', 'from', 'to'));

        return $csv->stream(
            $query,
            'admission-applications-'.now()->format('Y-m-d-His').'.csv',
            ['Application Number', 'Full Name', 'Email', 'Phone', 'Programme', 'Status', 'Reviewed At', 'Created At'],
            fn (AdmissionApplication $a) => [
                $a->application_number, $a->full_name, $a->email, $a->phone,
                $a->program?->title, $a->status, $a->reviewed_at, $a->created_at,
            ]
        );
    }

    private function filtered(Request $request)
    {
        $query = $request->boolean('trashed') ? AdmissionApplication::onlyTrashed() : AdmissionApplication::query();
        $query->with('program:id,title');

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('application_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->get('program_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->get('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->get('to'));
        }

        $sort = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'created_at';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction);
    }
}
