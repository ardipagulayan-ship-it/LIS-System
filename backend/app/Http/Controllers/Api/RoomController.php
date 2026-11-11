<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

     public function index(Request $request)
     {
         $query = Room::with('ward')->orderBy('id');
     
         // Optional filtering by ward_id
         if ($request->has('ward_id')) {
             $query->where('ward_id', $request->input('ward_id'));
         }
     
         // Pagination support
         $perPage = $request->input('per_page', 15);
         $rooms = $query->paginate($perPage);
     
         return response()->json($rooms);
     }

     /**
     * Display the specified resource.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $query = Room::with('ward')->orderBy('id');
     
        // Optional filtering by ward_id
        if ($request->has('ward_id')) {
            $query->where('ward_id', $request->input('ward_id'));
        }
    
        // Pagination support
        //$perPage = $request->input('per_page', 15);
        $rooms = $query->get();
    
        return response()->json($rooms);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ward_id' => 'required|exists:wards,id',
        ]);

        $room = Room::create($validated);
        return response()->json($room, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'ward_id' => 'sometimes|required|exists:wards,id',
        ]);

        $room->update($validated);
        return response()->json($room);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        $room->delete();
        return response()->json(null, 204);
    }
}
