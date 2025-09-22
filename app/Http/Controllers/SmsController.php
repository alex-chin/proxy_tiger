<?php

namespace App\Http\Controllers;

use App\Services\TigerSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exceptions\SmsServiceException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Services\SmsActions\SmsActionInterface;
use App\Services\SmsActions\GetNumberAction;
use App\Services\SmsActions\GetSmsAction;
use App\Services\SmsActions\CancelNumberAction;
use App\Services\SmsActions\GetStatusAction;

class SmsController extends Controller
{
    protected $smsService;

    public function __construct(TigerSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function getNumber(Request $request): JsonResponse
    {
        return $this->respondWithJson($request, $this->rulesForGetNumber(), new GetNumberAction($this->smsService));
    }

    public function getSms(Request $request): JsonResponse
    {
        return $this->respondWithJson($request, $this->rulesForActivation(), new GetSmsAction($this->smsService));
    }

    public function cancelNumber(Request $request): JsonResponse
    {
        return $this->respondWithJson($request, $this->rulesForActivation(), new CancelNumberAction($this->smsService));
    }

    public function getStatus(Request $request): JsonResponse
    {
        return $this->respondWithJson($request, $this->rulesForActivation(), new GetStatusAction($this->smsService));
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

    private function respondWithJson(Request $request, array $rules, SmsActionInterface $action): JsonResponse
    {
        try {
            $data = $request->validate($rules);
            $result = $action->execute($data);
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
