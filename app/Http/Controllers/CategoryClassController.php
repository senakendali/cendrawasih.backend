<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoryClass;
use App\Models\AgeCategory;
use App\Models\TeamMember;
use App\Models\TournamentParticipant;

class CategoryClassController extends Controller
{
    // Get all category classes
    public function index(Request $request)
    {
        try {
            // Use pagination with a default of 10 items per page
            $categoryClasses = CategoryClass::with('ageCategory')->paginate($request->get('per_page', 10));
            return response()->json($categoryClasses, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to fetch data', 'message' => $e->getMessage()], 500);
        }
    }

    public function getClassOnTeamMember(Request $request)
{
    $ageCategoryId = $request->query('age_category_id');
    $tournamentId = $request->query('tournament_id');
    $matchCategoryId = $request->query('match_category_id');
    $filterFor = $request->query('filter_for'); // ✅ ambil filter_for

    if (!$tournamentId) {
        return response()->json(['error' => 'tournament_id is required'], 400);
    }

    $teamMembers = TeamMember::whereHas('tournamentParticipants', function ($q) use ($tournamentId) {
            $q->where('tournament_id', $tournamentId);
        })
        ->when($matchCategoryId, function ($q) use ($matchCategoryId) {
            $q->where('match_category_id', $matchCategoryId);
        })
        ->with(['categoryClass' => function ($q) use ($ageCategoryId) {
            if ($ageCategoryId) {
                $q->where('age_category_id', $ageCategoryId);
            }
        }, 'ageCategory'])
        ->get()
        ->filter(fn ($tm) => $tm->categoryClass !== null);

    $grouped = $teamMembers->groupBy('category_class_id');

    $result = $grouped->map(function ($members, $classId) {
        $class = $members->first()->categoryClass;
        $ageCategory = $class->ageCategory;

        return [
            'class_id' => $classId,
            'gender' => $class->gender,
            'class_name' => $class->name,
            'weight_min' => $class->weight_min,
            'weight_max' => $class->weight_max,
            'age_category_id' => $class->age_category_id,
            'age_category_name' => $ageCategory->name ?? '-',
            'team_member_count' => $members->count(),
        ];
    })->values();

    // ✅ Filter hanya kelas yang belum ada pool jika filter_for = drawing
    if ($filterFor === 'drawing') {
        $usedClassIds = \App\Models\Pool::where('tournament_id', $tournamentId)
            ->pluck('category_class_id')
            ->unique();

        $result = $result->reject(fn ($class) => $usedClassIds->contains($class['class_id']))->values();
    }

    return response()->json($result);
}


    public function getClassOnTeamMember___(Request $request)
    {
        $ageCategoryId = $request->query('age_category_id');
        $tournamentId = $request->query('tournament_id');
        $matchCategoryId = $request->query('match_category_id');

        if (!$tournamentId) {
            return response()->json(['error' => 'tournament_id is required'], 400);
        }

        $teamMembers = TeamMember::whereHas('tournamentParticipants', function ($q) use ($tournamentId) {
                $q->where('tournament_id', $tournamentId);
            })
            ->when($matchCategoryId, function ($q) use ($matchCategoryId) {
                $q->where('match_category_id', $matchCategoryId);
            })
            ->with(['categoryClass' => function ($q) use ($ageCategoryId) {
                if ($ageCategoryId) {
                    $q->where('age_category_id', $ageCategoryId);
                }
            }, 'ageCategory'])
            ->get()
            ->filter(fn ($tm) => $tm->categoryClass !== null); // ✅ pastikan hanya class yang cocok

        $grouped = $teamMembers->groupBy('category_class_id');

        $result = $grouped->map(function ($members, $classId) {
            $class = $members->first()->categoryClass;
            $ageCategory = $class->ageCategory;

            return [
                'class_id' => $classId,
                'gender' => $class->gender,
                'class_name' => $class->name,
                'weight_min' => $class->weight_min,
                'weight_max' => $class->weight_max,
                'age_category_id' => $class->age_category_id,
                'age_category_name' => $ageCategory->name ?? '-',
                'team_member_count' => $members->count(),
            ];
        })->values();

        return response()->json($result);
    }

    
    public function fetchByAgeCategory($ageCategoryId)
    {
        try {
            // Fetch CategoryClasses based on the age category
            $categoryClasses = CategoryClass::where('age_category_id', $ageCategoryId)->get();

            // Group by gender
            $groupedByGender = $categoryClasses->groupBy('gender'); // assuming 'gender' is a field in your table
            
            // Optionally, you can make sure to structure the response so male and female are guaranteed keys
            $result = [
                'male' => $groupedByGender->get('male', collect([])), // Return empty collection if 'male' does not exist
                'female' => $groupedByGender->get('female', collect([])), // Return empty collection if 'female' does not exist
            ];

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to fetch data', 'message' => $e->getMessage()], 500);
        }
    }

    public function fetchClass($ageCategoryId, Request $request)
    {
        try {
            // Get query parameters from the request
            $gender = $request->query('gender');
            $body_weight = $request->query('body_weight');
            $ageCategoryId = $request->query('age_category_id');
            
            // Start building the query with age_category_id
            $categoryClassesQuery = CategoryClass::where('age_category_id', $ageCategoryId);

            // Apply age category filter if it's provided
            if ($ageCategoryId) {
                $categoryClassesQuery->where('age_category_id', $ageCategoryId);
            }

            // Apply gender filter if it's provided
            if ($gender) {
                $categoryClassesQuery->where('gender', $gender);
            }

            // Apply weight range filter if it's provided
            if ($body_weight) {
                $categoryClassesQuery->where(function ($query) use ($body_weight) {
                    $query->where('weight_min', '<=', $body_weight)
                          ->where('weight_max', '>=', $body_weight);
                });
            }

            // Use pagination (for example, 10 results per page)
            $categoryClasses = $categoryClassesQuery->get();

            return response()->json($categoryClasses, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to fetch data', 'message' => $e->getMessage()], 500);
        }
    }


    // Get a single category class
    public function show($id)
    {
        try {
            $categoryClass = CategoryClass::findOrFail($id);
            return response()->json(['data' => $categoryClass], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Category Class not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    // Create a new category class
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'age_category_id' => 'required|exists:age_categories,id',
            'weight_min' => 'required|numeric|min:0',
            'weight_max' => 'required|numeric|min:0|gt:weight_min',
            'gender' => 'required|in:male,female',
        ]);

        try {
            $categoryClass = CategoryClass::create($request->all());
            return response()->json(['data' => $categoryClass], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create category class', 'message' => $e->getMessage()], 500);
        }
    }

    // Update a category class
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'age_category_id' => 'sometimes|exists:age_categories,id',
            'weight_min' => 'sometimes|numeric|min:0',
            'weight_max' => 'sometimes|numeric|min:0|gt:weight_min',
            'gender' => 'sometimes|in:male,female',
        ]);

        try {
            $categoryClass = CategoryClass::findOrFail($id);
            $categoryClass->update($request->all());
            return response()->json(['data' => $categoryClass], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Category Class not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update category class', 'message' => $e->getMessage()], 500);
        }
    }

    // Delete a category class
    public function destroy($id)
    {
        try {
            $categoryClass = CategoryClass::findOrFail($id);
            $categoryClass->delete();
            return response()->json(['message' => 'Category Class deleted successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Category Class not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete category class', 'message' => $e->getMessage()], 500);
        }
    }

    public function getByTournament(Request $request)
    {
        $ageCategoryId = $request->query('age_category_id');

        $query = CategoryClass::query();

        if ($ageCategoryId) {
            $query->where('age_category_id', $ageCategoryId);
        }

        return response()->json($query->get());
    }

}
