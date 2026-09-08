<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ManagesOrderableResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Models\Program;
use App\Services\ActivityLogger;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProgramController extends Controller
{
    use ManagesOrderableResource;

    public function __construct(private MediaService $media)
    {
    }

    protected function modelClass(): string
    {
        return Program::class;
    }

    protected function resourceType(): string
    {
        return 'program';
    }

    protected function deleteOwnedFiles($record): void
    {
        $this->media->delete($record->image_path);
        $this->media->delete($record->icon_path);
    }

    public function publicIndex(Request $request): JsonResponse
    {
        $query = Program::where('is_active', true)->orderBy('display_order');

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        return response()->json(['success' => true, 'message' => 'Programs retrieved.', 'data' => $query->get()]);
    }

    public function publicShow(string $slug): JsonResponse
    {
        $program = Program::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return response()->json(['success' => true, 'message' => 'Program retrieved.', 'data' => $program]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = $request->boolean('trashed') ? Program::onlyTrashed() : Program::query();

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Programs retrieved.',
            'data' => $query->orderBy('display_order')->paginate(min((int) $request->get('per_page', 20), 100)),
        ]);
    }

    public function show(Program $program): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Program retrieved.', 'data' => $program]);
    }

    public function store(StoreProgramRequest $request): JsonResponse
    {
        $data = $request->safe()->except('image', 'icon');
        $data['slug'] = $data['slug'] ?? Str::slug($request->input('title'));
        $data['created_by'] = $request->user()->id;

        $imagePath = $request->hasFile('image') ? $this->media->store($request->file('image'), 'programs') : null;
        $iconPath = $request->hasFile('icon') ? $this->media->store($request->file('icon'), 'programs') : null;

        try {
            $program = DB::transaction(function () use ($data, $imagePath, $iconPath) {
                return Program::create([...$data, 'image_path' => $imagePath, 'icon_path' => $iconPath]);
            });
        } catch (\Throwable $e) {
            $this->media->delete($imagePath);
            $this->media->delete($iconPath);
            throw $e;
        }

        ActivityLogger::log($request, 'program_created', 'program', $program->id, 'Created program: '.$program->title);

        return response()->json(['success' => true, 'message' => 'Program created.', 'data' => $program], 201);
    }

    public function update(UpdateProgramRequest $request, Program $program): JsonResponse
    {
        $data = $request->safe()->except('image', 'icon');

        $newImagePath = $request->hasFile('image') ? $this->media->store($request->file('image'), 'programs') : null;
        $newIconPath = $request->hasFile('icon') ? $this->media->store($request->file('icon'), 'programs') : null;

        try {
            DB::transaction(function () use ($program, $data, $newImagePath, $newIconPath) {
                $program->update([
                    ...$data,
                    ...($newImagePath ? ['image_path' => $newImagePath] : []),
                    ...($newIconPath ? ['icon_path' => $newIconPath] : []),
                ]);
            });
        } catch (\Throwable $e) {
            $this->media->delete($newImagePath);
            $this->media->delete($newIconPath);
            throw $e;
        }

        if ($newImagePath) {
            $this->media->delete($program->getOriginal('image_path'));
        }
        if ($newIconPath) {
            $this->media->delete($program->getOriginal('icon_path'));
        }

        ActivityLogger::log($request, 'program_updated', 'program', $program->id, 'Updated program: '.$program->title);

        return response()->json(['success' => true, 'message' => 'Program updated.', 'data' => $program->fresh()]);
    }

    public function destroy(Request $request, Program $program): JsonResponse
    {
        // Soft delete only: existing admission applications keep their program_id intact.
        $program->delete();

        ActivityLogger::log($request, 'program_deleted', 'program', $program->id, 'Soft-deleted program: '.$program->title);

        return response()->json(['success' => true, 'message' => 'Program deleted.', 'data' => (object) []]);
    }

    public function feature(Request $request, Program $program): JsonResponse
    {
        $program->update(['is_featured' => true]);
        ActivityLogger::log($request, 'program_featured', 'program', $program->id, 'Featured program: '.$program->title);

        return response()->json(['success' => true, 'message' => 'Program featured.', 'data' => $program->fresh()]);
    }

    public function unfeature(Request $request, Program $program): JsonResponse
    {
        $program->update(['is_featured' => false]);
        ActivityLogger::log($request, 'program_unfeatured', 'program', $program->id, 'Unfeatured program: '.$program->title);

        return response()->json(['success' => true, 'message' => 'Program unfeatured.', 'data' => $program->fresh()]);
    }
}
