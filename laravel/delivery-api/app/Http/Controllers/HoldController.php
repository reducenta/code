<?php

namespace App\Http\Controllers;

use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HoldController extends Controller
{
    public function __construct(private SlotService $slotService){}

    public function store(Request $request, $slot_id): JsonResponse
    {
        $key = $request->header('Idempotency-Key');
        if(!$key){
            return response()->json(['message' => 'Idempotency-Key header required'], Response::HTTP_BAD_REQUEST);
        }

        [$payload, $status] = $this->slotService->createHold($slot_id, $key);

        return response()->json($status, $payload);

    }

    public function confirm($hold_id): JsonResponse
    {
        [$payload, $status] = $this->slotService->confirmHold($hold_id);
        return response()->json($status, $payload);
    }

    public function cancel($hold_id): JsonResponse
    {
        [$payload, $status] = $this->slotService->cancelHold($hold_id);
        return response()->json($status, $payload);
    }

}
