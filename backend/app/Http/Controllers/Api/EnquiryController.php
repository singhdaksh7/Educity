<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnquiryRequest;
use App\Http\Requests\UpdateEnquiryRequest;
use App\Mail\EnquiryConfirmationMail;
use App\Mail\NewEnquiryAdminMail;
use App\Models\Enquiry;
use App\Services\ActivityLogger;
use App\Services\CsvExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnquiryController extends Controller
{
    private const SORTABLE = ['created_at', 'name', 'status', 'contacted_at'];

    public function store(StoreEnquiryRequest $request): JsonResponse
    {
        // Honeypot field: real visitors never fill this hidden input.
        if ($request->filled('website')) {
            return response()->json(['success' => false, 'message' => 'Invalid submission.', 'errors' => (object) []], 422);
        }

        $enquiry = Enquiry::create([
            ...$request->safe()->except('website'),
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip()) : null,
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        try {
            if ($enquiry->email) {
                Mail::to($enquiry->email)->send(new EnquiryConfirmationMail($enquiry));
            }
            if (config('mail.admin_notification_email')) {
                Mail::to(config('mail.admin_notification_email'))->send(new NewEnquiryAdminMail($enquiry));
            }
        } catch (\Throwable $e) {
            Log::warning('Enquiry notification email failed', ['enquiry_id' => $enquiry->id, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Your enquiry has been received. We will get back to you shortly.',
            'data' => ['id' => $enquiry->id],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->filtered($request);

        return response()->json([
            'success' => true,
            'message' => 'Enquiries retrieved.',
            'data' => $query->paginate(min((int) $request->get('per_page', 20), 100)),
        ]);
    }

    public function show(Enquiry $enquiry): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Enquiry retrieved.', 'data' => $enquiry->load('assignedTo:id,name,email', 'user:id,name,email')]);
    }

    public function update(UpdateEnquiryRequest $request, Enquiry $enquiry): JsonResponse
    {
        $data = $request->validated();

        if (($data['status'] ?? null) === 'contacted' && ! $enquiry->contacted_at) {
            $data['contacted_at'] = now();
        }

        $enquiry->update($data);

        ActivityLogger::log($request, 'enquiry_updated', 'enquiry', $enquiry->id, 'Updated enquiry', $data);

        return response()->json(['success' => true, 'message' => 'Enquiry updated.', 'data' => $enquiry->fresh()]);
    }

    public function destroy(Request $request, Enquiry $enquiry): JsonResponse
    {
        $enquiry->delete();

        ActivityLogger::log($request, 'enquiry_deleted', 'enquiry', $enquiry->id, 'Soft-deleted enquiry');

        return response()->json(['success' => true, 'message' => 'Enquiry deleted.', 'data' => (object) []]);
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $enquiry = Enquiry::onlyTrashed()->findOrFail($id);
        $enquiry->restore();

        ActivityLogger::log($request, 'enquiry_restored', 'enquiry', $enquiry->id, 'Restored enquiry');

        return response()->json(['success' => true, 'message' => 'Enquiry restored.', 'data' => $enquiry]);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $enquiry = Enquiry::onlyTrashed()->findOrFail($id);
        $enquiry->forceDelete();

        ActivityLogger::log($request, 'enquiry_permanently_deleted', 'enquiry', $id, 'Permanently deleted enquiry');

        return response()->json(['success' => true, 'message' => 'Enquiry permanently deleted.', 'data' => (object) []]);
    }

    public function export(Request $request, CsvExportService $csv): StreamedResponse
    {
        $query = $this->filtered($request);

        ActivityLogger::log($request, 'enquiries_exported', 'enquiry', null, 'Exported enquiries CSV', $request->only('search', 'status', 'assigned_to', 'from', 'to'));

        return $csv->stream(
            $query,
            'enquiries-'.now()->format('Y-m-d-His').'.csv',
            ['ID', 'Name', 'Email', 'Phone', 'Subject', 'Status', 'Assigned To', 'Source', 'Contacted At', 'Created At'],
            fn (Enquiry $e) => [
                $e->id, $e->name, $e->email, $e->phone, $e->subject, $e->status,
                $e->assignedTo?->name, $e->source, $e->contacted_at, $e->created_at,
            ]
        );
    }

    private function filtered(Request $request)
    {
        $query = $request->boolean('trashed') ? Enquiry::onlyTrashed() : Enquiry::query();
        $query->with(['assignedTo:id,name', 'user:id,name,email']);

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->get('assigned_to'));
        }

        if ($request->filled('student_id')) {
            $query->where('user_id', $request->get('student_id'));
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
