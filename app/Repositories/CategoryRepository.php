<?php

namespace App\Repositories;

use App\Models\Category;
use Request;

class CategoryRepository implements CategoryInterface
{
    public function all()
    {
        $data = Category::with('courses', 'courses.videos');

        return $data->get();
    }
    public function find($id)
    {
        return Category::find($id);
    }
    public function store(array $attributes)
    {
        return Category::create($attributes);
    }
    public function update($id, array $attributes)
    {
        return Category::find($id)->update(Request::all());
    }
    public function destroy($id)
    {
        return Category::find($id)->delete();
    }
    public function search($search)
    {
        return Category::where('name', 'LIKE', '%'.$search.'%')->get();
    }
}
