<?php

namespace App\Repositories;

interface CategoryInterface
{
    public function all();
    public function find($id);
    public function store(array $attributes);
    public function update($id, array $attributes);
    public function destroy($id);
    public function search($search);
}
