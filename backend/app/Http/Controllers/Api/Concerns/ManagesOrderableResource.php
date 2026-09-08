<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Http\Requests\ReorderRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Shared restore / force-delete / reorder / toggle behaviour for the
 * Program, Testimonial and Gallery admin controllers.
 */
trait ManagesOrderableResource
{
    abstract protected function modelClass(): string;

    abstract protected function resourceType(): string;

    public function restore(Request $request, int $id): JsonResponse
    {
        $model = $this->modelClass();
        $record = $model::onlyTrashed()->findOrFail($id);
        $record->restore();

        ActivityLogger::log($request, $this->resourceType().'_restored', $this->resourceType(), $record->id, 'Restored '.$this->resourceType());

        return response()->json(['success' => true, 'message' => 'Restored successfully.', 'data' => $record]);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $model = $this->modelClass();
        $record = $model::onlyTrashed()->findOrFail($id);
        $this->deleteOwnedFiles($record);
        $record->forceDelete();

        ActivityLogger::log($request, $this->resourceType().'_permanently_deleted', $this->resourceType(), $id, 'Permanently deleted '.$this->resourceType());

        return response()->json(['success' => true, 'message' => 'Permanently deleted.', 'data' => (object) []]);
    }

    public function reorder(ReorderRequest $request): JsonResponse
    {
        $model = $this->modelClass();
        $items = $request->validated()['items'];

        DB::transaction(function () use ($model, $items) {
            foreach ($items as $item) {
                $model::whereKey($item['id'])->update(['display_order' => $item['display_order']]);
            }
        });

        ActivityLogger::log($request, $this->resourceType().'_reordered', $this->resourceType(), null, 'Reordered '.$this->resourceType());

        return response()->json(['success' => true, 'message' => 'Reordered successfully.', 'data' => (object) []]);
    }

    public function activate(Request $request, int $id): JsonResponse
    {
        $model = $this->modelClass();
        $record = $model::findOrFail($id);
        $record->update(['is_active' => true]);
        ActivityLogger::log($request, $this->resourceType().'_activated', $this->resourceType(), $record->id, 'Activated '.$this->resourceType());

        return response()->json(['success' => true, 'message' => 'Activated.', 'data' => $record->fresh()]);
    }

    public function deactivate(Request $request, int $id): JsonResponse
    {
        $model = $this->modelClass();
        $record = $model::findOrFail($id);
        $record->update(['is_active' => false]);
        ActivityLogger::log($request, $this->resourceType().'_deactivated', $this->resourceType(), $record->id, 'Deactivated '.$this->resourceType());

        return response()->json(['success' => true, 'message' => 'Deactivated.', 'data' => $record->fresh()]);
    }

    protected function deleteOwnedFiles($record): void
    {
        //
    }
}
