<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Learn\Academy\LibraryBook;
use App\Models\Learn\Academy\LibraryBorrowing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LibraryController extends Controller
{
    // ==================== BOOKS ====================

    public function index(Academy $academy, Request $request): JsonResponse
    {
        $query = LibraryBook::where('academy_id', $academy->id);

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('author', 'like', "%{$request->search}%")
                  ->orWhere('isbn', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $books = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $books,
        ]);
    }

    public function store(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'author' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'published_year' => 'nullable|integer',
            'category' => 'nullable|string|max:100',
            'total_copies' => 'required|integer|min:1',
            'location' => 'nullable|string|max:100',
        ]);

        $validated['academy_id'] = $academy->id;
        $validated['available_copies'] = $validated['total_copies'];

        $book = LibraryBook::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Book added to catalog',
            'data' => $book,
        ], 201);
    }

    // ==================== BORROWING ====================

    public function borrow(Request $request, Academy $academy): JsonResponse
    {
        $validated = $request->validate([
            'library_book_id' => 'required|exists:library_books,id',
            'user_id' => 'required|exists:users,id',
            'due_at' => 'required|date|after:today',
        ]);

        $book = LibraryBook::findOrFail($validated['library_book_id']);

        if ($book->available_copies <= 0) {
            return response()->json(['success' => false, 'message' => 'No copies available'], 422);
        }

        $borrowing = LibraryBorrowing::create([
            'academy_id' => $academy->id,
            'library_book_id' => $book->id,
            'user_id' => $validated['user_id'],
            'borrowed_at' => now(),
            'due_at' => $validated['due_at'],
            'status' => 'borrowed',
            'handled_by' => Auth::id() ?? 1,
        ]);

        $book->decrement('available_copies');

        return response()->json([
            'success' => true,
            'message' => 'Book borrowed successfully',
            'data' => $borrowing,
        ]);
    }

    public function returnBook(Request $request, Academy $academy, LibraryBorrowing $borrowing): JsonResponse
    {
        if ($borrowing->status !== 'borrowed' && $borrowing->status !== 'overdue') {
            return response()->json(['success' => false, 'message' => 'Invalid borrowing status'], 422);
        }

        $borrowing->update([
            'returned_at' => now(),
            'status' => 'returned',
        ]);

        $borrowing->book->increment('available_copies');

        return response()->json([
            'success' => true,
            'message' => 'Book returned successfully',
        ]);
    }
}
