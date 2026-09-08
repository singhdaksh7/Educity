<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ManagesOrderableResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTestimonialRequest;
use App\Http\Requests\UpdateTestimonialRequest;
use App\Models\Testimonial;
use App\Services\ActivityLogger;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestimonialController extends Controller
{
    use ManagesOrderableResource;

    public function __construct(private MediaService $media)
    {
    }

    protected function modelClass(): string
    {
        return Testimonial::class;
    }

    protected function resourceType(): string
    {
        return 'testimonial';
    }

    protected function deleteOwnedFiles($record): void
    {
        $this->media->delete($record->image_path);
    }

    public function publicIndex(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Testimonials retrieved.',
            'data' => Testimonial::where('is_active', true)->orderBy('display_order')->get(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = $request->boolean('trashed') ? Testimonial::onlyTrashed() : Testimonial::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Testimonials retrieved.',
            'data' => $query->orderBy('display_order')->paginate(min((int) $request->get('per_page', 20), 100)),
        ]);
    }

    public function show(Testimonial $testimonial): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Testimonial retrieved.', 'data' => $testimonial]);
    }

    public function store(StoreTestimonialRequest $request): JsonResponse
    {
        $data = $request->safe()->except('image');
        $data['created_by'] = $request->user()->id;

        $imagePath = $request->hasFile('image') ? $this->media->store($request->file('image'), 'testimonials') : null;

        try {
            $testimonial = DB::transaction(fn () => Testimonial::create([...$data, 'image_path' => $imagePath]));
        } catch (\Throwable $e) {
            $this->media->delete($imagePath);
            throw $e;
        }

        ActivityLogger::log($request, 'testimonial_created', 'testimonial', $testimonial->id, 'Created testimonial: '.$testimonial->student_name);

        return response()->json(['success' => true, 'message' => 'Testimonial created.', 'data' => $testimonial], 201);
    }

    public function update(UpdateTestimonialRequest $request, Testimonial $testimonial): JsonResponse
    {
        $data = $request->safe()->except('image');
        $newImagePath = $request->hasFile('image') ? $this->media->store($request->file('image'), 'testimonials') : null;

        try {
            DB::transaction(function () use ($testimonial, $data, $newImagePath) {
                $testimonial->update([...$data, ...($newImagePath ? ['image_path' => $newImagePath] : [])]);
            });
        } catch (\Throwable $e) {
            $this->media->delete($newImagePath);
            throw $e;
        }

        if ($newImagePath) {
            $this->media->delete($testimonial->getOriginal('image_path'));
        }

        ActivityLogger::log($request, 'testimonial_updated', 'testimonial', $testimonial->id, 'Updated testimonial: '.$testimonial->student_name);

        return response()->json(['success' => true, 'message' => 'Testimonial updated.', 'data' => $testimonial->fresh()]);
    }

    public function destroy(Request $request, Testimonial $testimonial): JsonResponse
    {
        $testimonial->delete();

        ActivityLogger::log($request, 'testimonial_deleted', 'testimonial', $testimonial->id, 'Soft-deleted testimonial: '.$testimonial->student_name);

        return response()->json(['success' => true, 'message' => 'Testimonial deleted.', 'data' => (object) []]);
    }

    public function feature(Request $request, Testimonial $testimonial): JsonResponse
    {
        $testimonial->update(['is_featured' => true]);
        ActivityLogger::log($request, 'testimonial_featured', 'testimonial', $testimonial->id, 'Featured testimonial');

        return response()->json(['success' => true, 'message' => 'Testimonial featured.', 'data' => $testimonial->fresh()]);
    }

    public function unfeature(Request $request, Testimonial $testimonial): JsonResponse
    {
        $testimonial->update(['is_featured' => false]);
        ActivityLogger::log($request, 'testimonial_unfeatured', 'testimonial', $testimonial->id, 'Unfeatured testimonial');

        return response()->json(['success' => true, 'message' => 'Testimonial unfeatured.', 'data' => $testimonial->fresh()]);
    }
}
