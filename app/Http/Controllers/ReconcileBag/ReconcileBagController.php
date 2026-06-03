<?php

namespace App\Http\Controllers\ReconcileBag;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReconcileBagRequest;
use App\Models\CollectedBag;
use App\Services\ReconcileBagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReconcileBagController extends Controller
{
    public function __invoke(ReconcileBagRequest $request, CollectedBag $collectedBag, ReconcileBagService $service): JsonResponse
    {
        return $service->packagesAmountComparison($request->validated(), $collectedBag);
    }
}
