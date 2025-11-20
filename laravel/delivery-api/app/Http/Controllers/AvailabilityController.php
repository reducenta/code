<?php

namespace App\Http\Controllers;

use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(private SlotService $slotService){}

    public function index(Request $request): JsonResponse
    {
        $data = $this->slotService->getAvailability();
        return response()->json($data);
    }
}
