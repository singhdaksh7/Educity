<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ManagesOrderableResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGalleryRequest;
use App\Http\Requests\UpdateGalleryRequest;
use App\Models\Gallery;
use App\Services\ActivityLogger;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GalleryController extends Controller
{
    use ManagesOrderableResource;

    public function __construct(private MediaService $media)
    {
    }

    protected function modelClass(): string
    {
        return Gallery::class;
    }

    protected function resourceType(): string
    {
        return 'gallery';
    }

    protected function deleteOwnedFiles($record): void
    {
        $this->media->delete($record->image_path);
    }

    public function publicIndex(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Gallery retrieved.',
            'data' => Gallery::where('is_active', true)->orderBy('display_order')->get(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = $request->boolean('trashed') ? Gallery::onlyTrashed() : Gallery::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Gallery items retrieved.',
            'data' => $query->orderBy('display_order')->paginate(min((int) $request->get('per_page', 20), 100)),
        ]);
    }

    public function show(Gallery $gallery): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Gallery item retrieved.', 'data' => $gallery]);
    }

    public function store(StoreGalleryRequest $request): JsonResponse
    {
        $data = $request->safe()->except('image');
        $data['created_by'] = $request->user()->id;

        $imagePath = $this->media->store($request->file('image'), 'gallery');

        try {
            $item = DB::transaction(fn () => Gallery::create([...$data, 'image_path' => $imagePath]));
        } catch (\Throwable $e) {
            $this->media->delete($imagePath);
            throw $e;
        }

        ActivityLogger::log($request, 'gallery_created', 'gallery', $item->id, 'Created gallery item: '.$item->title);

        return response()->json(['success' => true, 'message' => 'Gallery item created.', 'data' => $item], 201);
    }

    public function update(UpdateGalleryRequest $request, Gallery $gallery): JsonResponse
    {
        $data = $request->safe()->except('image');
        $newImagePath = $request->hasFile('image') ? $this->media->store($request->file('image'), 'gallery') : null;

        try {
            DB::transaction(function () use ($gallery, $data, $newImagePath) {
                $gallery->update([...$data, ...($newImagePath ? ['image_path' => $newImagePath] : [])]);
            });
        } catch (\Throwable $e) {
            $this->media->delete($newImagePath);
            throw $e;
        }

        if ($newImagePath) {
            $this->media->delete($gallery->getOriginal('image_path'));
        }

        ActivityLogger::log($request, 'gallery_updated', 'gallery', $gallery->id, 'Updated gallery item: '.$gallery->title);

        return response()->json(['success' => true, 'message' => 'Gallery item updated.', 'data' => $gallery->fresh()]);
    }

    public function destroy(Request $request, Gallery $gallery): JsonResponse
    {
        $gallery->delete();

        ActivityLogger::log($request, 'gallery_deleted', 'gallery', $gallery->id, 'Soft-deleted gallery item: '.$gallery->title);

        return response()->json(['success' => true, 'message' => 'Gallery item deleted.', 'data' => (object) []]);
    }
}
