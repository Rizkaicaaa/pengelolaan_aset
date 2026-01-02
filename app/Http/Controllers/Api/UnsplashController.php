<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UnsplashController extends Controller
{
    private $baseUrl = 'https://api.unsplash.com';

    /**
     * FUNGSI 1: LIST (Cari Gambar)
     */
    public function index(Request $request)
    {
        $query = $request->query('query', 'office');
        $apiKey = env('UNSPLASH_ACCESS_KEY');

        // Tembak API Unsplash
        $response = Http::get("{$this->baseUrl}/search/photos", [
            'client_id' => $apiKey,
            'query' => $query,
            'per_page' => 12, // Ambil 12 gambar
            'orientation' => 'landscape' // Biar rapi (opsional)
        ]);

        if ($response->failed()) {
            return response()->json(['message' => 'Gagal koneksi ke Unsplash'], 500);
        }

        $data = $response->json();

        // Rapikan data untuk dikirim ke React
        $images = collect($data['results'])->map(function ($item) {
            return [
                'id' => $item['id'],
                'description' => $item['alt_description'] ?? 'Aset',
                'thumbnail' => $item['urls']['small'],
                'full_image' => $item['urls']['regular'],
                'photographer' => $item['user']['name']
            ];
        });

        return response()->json(['data' => $images]);
    }

    /**
     * FUNGSI 2: DETAIL 
     */
    public function show($id)
    {
        $apiKey = env('UNSPLASH_ACCESS_KEY');
        
        $response = Http::get("{$this->baseUrl}/photos/{{$id}}", [
            'client_id' => $apiKey
        ]);

        if ($response->failed()) {
            return response()->json(['message' => 'Gambar tidak ditemukan'], 404);
        }

        return response()->json(['data' => $response->json()]);
    }
}