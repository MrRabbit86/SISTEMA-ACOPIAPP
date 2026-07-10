<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriaResource;
use App\Models\CategoriaMaterial;
use Illuminate\Http\JsonResponse;

class CategoriaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => CategoriaResource::collection(
                CategoriaMaterial::query()->orderBy('nombre')->get(),
            ),
        ]);
    }
}