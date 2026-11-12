<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class TimeController extends Controller
{
    public function __construct(private readonly TimeService $time) {}

    public function now(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'timezone' => ['sometimes', 'string', 'timezone:all'],
        ]);

        return response()->json($this->time->now($validated['timezone'] ?? 'UTC'));
    }

    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'time' => ['required', 'string', 'max:64'],
            'from_tz' => ['required', 'string', 'timezone:all'],
            'to_tz' => ['required', 'string', 'timezone:all'],
        ]);

        return $this->respond('time', fn () => $this->time->convert(
            $validated['time'], $validated['from_tz'], $validated['to_tz'],
        ));
    }

    public function difference(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'string', 'max:64'],
            'end' => ['required', 'string', 'max:64'],
            'unit' => ['sometimes', 'string', Rule::in(TimeService::UNITS)],
        ]);

        return $this->respond('start', fn () => $this->time->difference(
            $validated['start'], $validated['end'], $validated['unit'] ?? null,
        ));
    }

    public function timezones(): JsonResponse
    {
        $timezones = $this->time->timezones();

        return response()->json(['count' => count($timezones), 'timezones' => $timezones]);
    }

    /**
     * A timestamp the service cannot parse is the caller's error, so it is
     * reported in the same 422 shape as any other invalid parameter.
     *
     * @param  callable(): array<string, mixed>  $callback
     */
    private function respond(string $field, callable $callback): JsonResponse
    {
        try {
            return response()->json($callback());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([$field => $e->getMessage()]);
        }
    }
}
