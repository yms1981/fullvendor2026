<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryGroup;
use App\Models\CategorySubgroup;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * CRUD de grupos/subgrupos de categoría (con foto opcional).
 */
class CategoryGroupController extends Controller
{
    protected function resolveCompanyId(Request $request): ?int
    {
        $companyId = $request->input('company_id') ?? $request->input('company');
        if ($companyId) {
            return (int) $companyId;
        }

        $user = $request->user();
        if (!$user) {
            return null;
        }

        $userCompany = $user->companiesForRole('manager')->first();
        if (!$userCompany) {
            $userCompany = $user->companies()->first();
        }

        return $userCompany?->company_id ? (int) $userCompany->company_id : null;
    }

    protected function findCategoryForCompany(int $categoryId, int $companyId): ?Category
    {
        return Category::query()
            ->where('cat_id', $categoryId)
            ->where('company_id', $companyId)
            ->first();
    }

    protected function storeUploadedImage(Request $request, int $companyId): ?string
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        $imageFile = $request->file('image');
        $image = new Image();
        $image->company_id = $companyId;
        $image->name = Str::uuid() . '.' . Image::STORAGE_EXTENSION;
        $image->disk = Image::defaultDisk();
        $image->type = Image::STORAGE_MIME;
        $image->mime_type = Image::STORAGE_MIME;
        $image->extension = Image::STORAGE_EXTENSION;
        $image->original_name = $imageFile->getClientOriginalName();
        $image->putContent($imageFile->getContent(), $companyId, 'category');

        return $image->uuid;
    }

    protected function deleteImageIfAny(?string $uuid): void
    {
        if (!$uuid) {
            return;
        }
        $old = Image::query()->where('uuid', $uuid)->first();
        if ($old) {
            try {
                $old->delete();
            } catch (\Throwable $e) {
                // No bloquear el flujo si falla el borrado físico.
            }
        }
    }

    public function listByCategory(Request $request, int $categoryId)
    {
        $companyId = $this->resolveCompanyId($request);
        if (!$companyId) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $category = $this->findCategoryForCompany($categoryId, $companyId);
        if (!$category) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $groups = CategoryGroup::query()
            ->where('company_id', $companyId)
            ->where('category_id', $categoryId)
            ->with([
                'imageRecord:uuid,name,path,extension,mime_type,disk,company_id',
                'subgroups' => function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                        ->with('imageRecord:uuid,name,path,extension,mime_type,disk,company_id');
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('group_name')
            ->get();

        return response()->json(['data' => $groups]);
    }

    public function storeByCategory(Request $request, int $categoryId)
    {
        $companyId = $this->resolveCompanyId($request);
        if (!$companyId) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $category = $this->findCategoryForCompany($categoryId, $companyId);
        if (!$category) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if ((int) ($category->category_default ?? 0) === 1) {
            return response()->json(['message' => 'Default category cannot have groups'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) $request->input('group_name', ''));
        if ($name === '') {
            return response()->json(['message' => 'group_name required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $imageUuid = null;
        try {
            $imageUuid = $this->storeUploadedImage($request, $companyId);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $group = CategoryGroup::create([
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'group_name' => $name,
            'group_status' => (int) ($request->input('group_status', 1)),
            'sort_order' => (int) ($request->input('sort_order', 0)),
            'image' => $imageUuid,
        ]);

        $group->load([
            'imageRecord:uuid,name,path,extension,mime_type,disk,company_id',
            'subgroups',
        ]);

        return response()->json(['data' => $group], Response::HTTP_CREATED);
    }

    public function updateGroup(Request $request, int $groupId)
    {
        $companyId = $this->resolveCompanyId($request);
        if (!$companyId) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $group = CategoryGroup::query()
            ->where('group_id', $groupId)
            ->where('company_id', $companyId)
            ->first();

        if (!$group) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if ($request->has('group_name')) {
            $name = trim((string) $request->input('group_name'));
            if ($name === '') {
                return response()->json(['message' => 'group_name required'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $group->group_name = $name;
        }
        if ($request->has('group_status')) {
            $group->group_status = (int) $request->input('group_status');
        }
        if ($request->has('sort_order')) {
            $group->sort_order = (int) $request->input('sort_order');
        }

        if ($request->hasFile('image')) {
            try {
                $newUuid = $this->storeUploadedImage($request, $companyId);
                if ($newUuid) {
                    $oldUuid = $group->image;
                    $group->image = $newUuid;
                    $this->deleteImageIfAny($oldUuid);
                }
            } catch (\Throwable $e) {
                return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $group->save();
        $group->load([
            'imageRecord:uuid,name,path,extension,mime_type,disk,company_id',
            'subgroups.imageRecord:uuid,name,path,extension,mime_type,disk,company_id',
        ]);

        return response()->json(['data' => $group]);
    }

    public function destroyGroup(Request $request, int $groupId)
    {
        $companyId = $this->resolveCompanyId($request);
        if (!$companyId) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $group = CategoryGroup::query()
            ->where('group_id', $groupId)
            ->where('company_id', $companyId)
            ->with('subgroups')
            ->first();

        if (!$group) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        foreach ($group->subgroups as $subgroup) {
            $this->deleteImageIfAny($subgroup->image);
            $subgroup->delete();
        }
        $this->deleteImageIfAny($group->image);
        $group->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function storeSubgroup(Request $request, int $groupId)
    {
        $companyId = $this->resolveCompanyId($request);
        if (!$companyId) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $group = CategoryGroup::query()
            ->where('group_id', $groupId)
            ->where('company_id', $companyId)
            ->first();

        if (!$group) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $name = trim((string) $request->input('subgroup_name', ''));
        if ($name === '') {
            return response()->json(['message' => 'subgroup_name required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $imageUuid = null;
        try {
            $imageUuid = $this->storeUploadedImage($request, $companyId);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $subgroup = CategorySubgroup::create([
            'company_id' => $companyId,
            'group_id' => $groupId,
            'subgroup_name' => $name,
            'subgroup_status' => (int) ($request->input('subgroup_status', 1)),
            'sort_order' => (int) ($request->input('sort_order', 0)),
            'image' => $imageUuid,
        ]);

        $subgroup->load('imageRecord:uuid,name,path,extension,mime_type,disk,company_id');

        return response()->json(['data' => $subgroup], Response::HTTP_CREATED);
    }

    public function updateSubgroup(Request $request, int $subgroupId)
    {
        $companyId = $this->resolveCompanyId($request);
        if (!$companyId) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $subgroup = CategorySubgroup::query()
            ->where('subgroup_id', $subgroupId)
            ->where('company_id', $companyId)
            ->first();

        if (!$subgroup) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if ($request->has('subgroup_name')) {
            $name = trim((string) $request->input('subgroup_name'));
            if ($name === '') {
                return response()->json(['message' => 'subgroup_name required'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $subgroup->subgroup_name = $name;
        }
        if ($request->has('subgroup_status')) {
            $subgroup->subgroup_status = (int) $request->input('subgroup_status');
        }
        if ($request->has('sort_order')) {
            $subgroup->sort_order = (int) $request->input('sort_order');
        }

        if ($request->hasFile('image')) {
            try {
                $newUuid = $this->storeUploadedImage($request, $companyId);
                if ($newUuid) {
                    $oldUuid = $subgroup->image;
                    $subgroup->image = $newUuid;
                    $this->deleteImageIfAny($oldUuid);
                }
            } catch (\Throwable $e) {
                return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $subgroup->save();
        $subgroup->load('imageRecord:uuid,name,path,extension,mime_type,disk,company_id');

        return response()->json(['data' => $subgroup]);
    }

    public function destroySubgroup(Request $request, int $subgroupId)
    {
        $companyId = $this->resolveCompanyId($request);
        if (!$companyId) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $subgroup = CategorySubgroup::query()
            ->where('subgroup_id', $subgroupId)
            ->where('company_id', $companyId)
            ->first();

        if (!$subgroup) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->deleteImageIfAny($subgroup->image);
        $subgroup->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
