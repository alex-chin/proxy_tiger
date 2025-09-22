<?php

namespace App\Http\Controllers;

use App\Services\TigerSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exceptions\SmsServiceException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class SmsController extends Controller
{
    protected $smsService;

    public function __construct(TigerSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function getNumber(Request $request): JsonResponse
    {
        return $this->respondWithJson($request, $this->rulesForGetNumber(), function (array $data) {
            return $this->smsService->getNumber(
                $data['country'] ?? null,
                $data['service'] ?? null
            );
        });
    }

    public function getSms(Request $request): JsonResponse
    {
        return $this->respondWithJson($request, $this->rulesForActivation(), function (array $data) {
            return $this->smsService->getSms($data['activation']);
        });
    }

    public function cancelNumber(Request $request): JsonResponse
    {
        return $this->respondWithJson($request, $this->rulesForActivation(), function (array $data) {
            return $this->smsService->cancelNumber($data['activation']);
        });
    }

    public function getStatus(Request $request): JsonResponse
    {
        return $this->respondWithJson($request, $this->rulesForActivation(), function (array $data) {
            return $this->smsService->getStatus($data['activation']);
        });
    }

    private function rulesForGetNumber(): array
    {
        return [
            'country' => [
                'sometimes', 'string', 'size:2',
                Rule::in(config('tiger_test.allowed_countries')),
            ],
            'service' => [
                'sometimes', 'string', 'size:2',
                Rule::in(config('tiger_test.allowed_services')),
            ],
        ];
    }

    private function rulesForActivation(): array
    {
        return [
            'activation' => 'required|string',
        ];
    }

    private function respondWithJson(Request $request, array $rules, callable $action): JsonResponse
    {
        try {
            $data = $request->validate($rules);
            $result = $action($data);
            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (SmsServiceException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
