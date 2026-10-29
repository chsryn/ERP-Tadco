<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiChatRequest;
use App\Services\Ai\AiAssistantService;
use Illuminate\Http\JsonResponse;

class AiChatController extends Controller
{
    public function __invoke(AiChatRequest $request, AiAssistantService $assistant): JsonResponse
    {
        $result = $assistant->answer($request->user(), $request->validated('prompt'));

        return response()->json($result);
    }
}
