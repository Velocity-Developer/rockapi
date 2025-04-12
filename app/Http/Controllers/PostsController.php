<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;

class PostsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $count = $request->input('count', 20);
        $title_search = $request->input('title');

        $Posts = Post::with('author:id,name,avatar')
            ->when($title_search, function ($query) use ($title_search) {
                $query->where('title', 'like', '%' . $title_search . '%');
            })
            ->orderBy('date', 'desc')
            ->paginate($count);

        return response()->json($Posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'     => 'required|min:4|string',
            'content'   => 'required|min:4',
            'date'      => 'nullable|date',
            'featured_image'    => 'nullable|image|mimes:jpeg,png,webp,jpg,gif,svg|max:2048',
            'status'    => 'required|string',
        ]);

        //if date is null, set date to now
        if (!$request->input('date')) {
            $date = now();
        } else {
            $date = $request->input('date');
        }

        //create post
        $post = Post::create([
            'title'     => $request->title,
            'content'   => $request->content,
            'date'      => $date,
            'status'    => $request->status,
        ]);

        //author
        $post->author()->associate(auth()->user());
        $post->save();

        if ($request->hasFile('featured_image')) {

            //delete old image
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }

            $file = $request->file('featured_image');
            $path = $file->store('posts/' . date('Y/m'), 'public');
            $post->update([
                'featured_image' => $path
            ]);
        }

        return response()->json($post);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //get post
        $post = Post::with('author:id,name,avatar')
            ->where('id', $id)
            ->first();

        return response()->json($post);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title'     => 'required|min:4|string',
            'content'   => 'required|min:4',
            'date'      => 'nullable|date',
            'featured_image'    => 'nullable|image|mimes:jpeg,png,jpg,webp,gif,svg|max:2048',
            'status'    => 'required|string',
        ]);

        //if date is null, set date to now
        if (!$request->input('date')) {
            $date = now();
        } else {
            $date = $request->input('date');
        }

        //update post
        $post = Post::find($id);
        $post->update([
            'title'     => $request->title,
            'content'   => $request->content,
            'date'      => $date,
            'status'    => $request->status,
        ]);

        if ($request->hasFile('featured_image')) {

            //delete old image
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }

            $file = $request->file('featured_image');
            $path = $file->store('posts/' . date('Y/m'), 'public');
            $post->update([
                'featured_image' => $path
            ]);
        }

        return response()->json($post);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //get post
        $post = Post::find($id);

        //delete featured image
        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }

        //delete post
        $post->delete();
    }
}
