<?php

namespace App\Services;

use App\Models\Hold;
use App\Models\Slot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SlotService
{

    private const HOLD_TTL_MINUTES = 5;

    private const AVAILABILITY_CACHE_KEY = 'slots:availability:v1';

    private const AVAILABILITY_TTL_SECONDS_MIN = 5;
    private const AVAILABILITY_TTL_SECONDS_MAX = 15;

    public function getAvailability(): array
    {
        $ttl = random_int(self::AVAILABILITY_TTL_SECONDS_MIN, self::AVAILABILITY_TTL_SECONDS_MAX);

        $cached = Cache::get(self::AVAILABILITY_CACHE_KEY);
        if($cached !== null){
            return $cached;
        }

        return Cache::lock(self::AVAILABILITY_CACHE_KEY, 10)->block(3, function() use ($ttl){

            $cachedInside = Cache::get(self::AVAILABILITY_CACHE_KEY);
            if($cachedInside !== null){
                return $cachedInside;
            }

            $now = now();

            $activeHeldThreshold = $now->clone()->subMinutes(self::HOLD_TTL_MINUTES)->toDateTimeString();

            $slots = Slot::query()
                ->select(['id as slot_id', 'capacity', 'remaining'])
                ->orderBy('id')
                ->get();

            $activeHolds = Hold::query()
                ->selectRaw('slot_id, COUNT(*) as cnt')
                ->where('status', Hold::STATUS_HELD)
                ->where('created_at', '>=', $activeHeldThreshold)
                ->groupBy('slot_id')
                ->pluck('cnt', 'slot_id');

            $result = $slots->map(function($s) use ($activeHolds){
                $helds = (int)($activeHolds[$s->slot_id] ?? 0);
                $effective = max($s->remaining - $helds, 0);
                return [
                    'slot_id' => (int)$s->slot_id,
                    'capacity' => (int)$s->capacity,
                    'remaining' => (int)$effective
                ];
            })->values()->all();

            Cache::put(self::AVAILABILITY_CACHE_KEY, $result, $ttl);

            return $result;

        });
    }

    public function createHold(int $slot_id, string $idempotencyKey): array
    {
        $existing = Hold::where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            return [
                Response::HTTP_OK,
                [
                    'id' => $existing->id,
                    'slot_id' => $existing->slot_id,
                    'status' => $existing->status,
                    'created_at' => $existing->created_at,
                ]
            ];
        }


        try{

            $hold = DB::transaction(function () use ($slot_id, $idempotencyKey) {

                $slot = Slot::lockForUpdate()->find($slot_id);
                if (!$slot) {
                    abort(Response::HTTP_NOT_FOUND, 'Slot not found');
                }

                $activeHeldThreshold = now()->subMinutes(self::HOLD_TTL_MINUTES)->toDateTimeString();

                $activeHolds = Hold::where('slot_id', $slot_id)
                    ->where('status', Hold::STATUS_HELD)
                    ->where('created_at', '>=', $activeHeldThreshold)
                    ->count();

                $effectiveRemaining = max($slot->remaining - $activeHolds, 0);

                if($effectiveRemaining <= 0) {
                    abort(Response::HTTP_CONFLICT, 'Capacity exhausted');
                }

                return Hold::create([
                    'slot_id' => $slot_id,
                    'status' => Hold::STATUS_HELD,
                    'idempotency_key' => $idempotencyKey,
                ]);

            });

            Cache::forget(self::AVAILABILITY_CACHE_KEY);

            return [
                Response::HTTP_CREATED,
                [
                    'id' => $hold->id,
                    'slot_id' => $hold->slot_id,
                    'status' => $hold->status,
                    'created_at' => $hold->created_at,
                ]
            ];

        }catch (NotFoundHttpException $e){
            return [Response::HTTP_NOT_FOUND, ['message' => 'Slot not found']];
        }catch (HttpException $e){
            return [Response::HTTP_CONFLICT, ['message' => 'Capacity exhausted']];
        }

    }

    public function confirmHold($hold_id): array
    {
        $ttlThreshold = now()->clone()->subMinutes(self::HOLD_TTL_MINUTES)->toDateTimeString();

        try{

            DB::transaction(function () use ($ttlThreshold, $hold_id) {

                $hold = Hold::lockForUpdate()->find($hold_id);

                if(!$hold){
                    abort(Response::HTTP_NOT_FOUND, 'Hold not found');
                }

                if($hold->status == Hold::STATUS_CONFIRMED){
                    return;
                }

                if($hold->created_at->lt($ttlThreshold)){
                    abort(Response::HTTP_CONFLICT, 'Hold expired');
                }

                $updated = DB::update(
                    'UPDATE slots SET remaining = remaining - 1 WHERE id = ? AND remaining > 0',
                    [$hold->slot_id]
                );

                if($updated === 0){
                    abort(Response::HTTP_CONFLICT, 'Capacity exhausted');
                }

                $hold->status = Hold::STATUS_CONFIRMED;
                $hold->confirmed_at = now();
                $hold->save();

            });

            Cache::forget(self::AVAILABILITY_CACHE_KEY);

            $hold = Hold::find($hold_id);

            return [
                Response::HTTP_OK,
                [
                    'id' => $hold->id,
                    'slot_id' => $hold->slot_id,
                    'status' => $hold->status,
                    'confirmed_at' => $hold->confirmed_at,
                ]
            ];

        }catch (NotFoundHttpException $e){
            return [Response::HTTP_NOT_FOUND, ['message' => 'Hold not found']];
        }catch (HttpException $e){
            return [Response::HTTP_CONFLICT, ['message' => $e->getMessage()]];
        }

    }

    public function cancelHold($hold_id): array
    {

        try{

            DB::transaction(function () use ($hold_id) {
                $hold = Hold::lockForUpdate()->find($hold_id);

                if(!$hold){
                    abort(Response::HTTP_NOT_FOUND, 'Hold not found');
                }

                if($hold->status === Hold::STATUS_CANCELLED){
                    return;
                }

                if($hold->status === Hold::STATUS_CONFIRMED){
                    DB::update(
                        'UPDATE slots SET remaining = remaining + 1 WHERE id = ? AND remaining < capacity',
                        [$hold->slot_id]
                    );
                }

                $hold->status = Hold::STATUS_CANCELLED;
                $hold->cancelled_at = now();
                $hold->save();
            });

            Cache::forget(self::AVAILABILITY_CACHE_KEY);

            $hold = Hold::find($hold_id);

            return [
                Response::HTTP_OK,
                [
                    'id' => $hold->id,
                    'slot_id' => $hold->slot_id,
                    'status' => $hold->status,
                    'cancelled_at' => $hold->cancelled_at,
                ]
            ];

        }catch (NotFoundHttpException $e){
            return [Response::HTTP_NOT_FOUND, ['message' => 'Hold not found']];
        }

    }









}