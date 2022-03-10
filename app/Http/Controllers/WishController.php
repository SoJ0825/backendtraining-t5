<?php

namespace App\Http\Controllers;

use App\Models\Wish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $wishQuery = Wish::query()
            ->where('user_id', $user['id']);
        if ($request->has('status')) {
            $wishQuery->where('status', $request['status']);
        }

        return response()->json($wishQuery->paginate());
    }

    public function store(Request $request)
    {
        $request->validate([
            'message' => ['required', 'string'],
        ]);
        $user = $request->user();

        $newWish = new Wish();
        $newWish['message'] = $request['message'];
        $newWish['user_id'] = $user['id'];
        $newWish['status'] = Wish::STATUS_DRAFT;
        $newWish->save();

        return response()->json($newWish);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $wish = Wish::query()
            ->where('id', $id)
            ->where('user_id', $user['id'])
            ->firstOrFail();

        return response()->json($wish);
    }

    public function update(Request $request, int $id)
    {
        $request->validate(['message' => ['required', 'string']]);
        $user = $request->user();

        $wish = Wish::query()
            ->where('id', $id)
            ->where('user_id', $user['id'])
            ->firstOrFail();
        foreach ($request->input() as $key => $value) {
            $wish[$key] = $value;
        }
        $wish->save();

        return response()->json($wish);
    }

    public function delete(Request $request, int $id)
    {
        $user = $request->user();

        $wish = Wish::query()
            ->where('id', $id)
            ->where('user_id', $user['id'])
            ->firstOrFail();
        $wish->delete();

        return response()->json();
    }

    public function submit(Request $request, int $id)
    {
        $user = $request->user();

        $wish = Wish::query()
            ->where('id', $id)
            ->where('user_id', $user['id'])
            ->firstOrFail();
        $wish['status'] = Wish::STATUS_SUBMITTED;
        $wish->save();

        return response()->json($wish);
    }
}
