<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Tests\TestCase;

class ApiTest extends TestCase
{
    public function test_time_defaults_to_utc(): void
    {
        CarbonImmutable::setTestNow('2026-11-13T00:00:00Z');

        $this->getJson('/api/v1/time')
            ->assertOk()
            ->assertJson(['timezone' => 'UTC', 'datetime' => '2026-11-13T00:00:00+00:00', 'day_of_week' => 'Friday']);
    }

    public function test_time_in_a_timezone(): void
    {
        CarbonImmutable::setTestNow('2026-11-13T00:00:00Z');

        $this->getJson('/api/v1/time?timezone=Australia/Sydney')
            ->assertOk()
            ->assertJson(['datetime' => '2026-11-13T11:00:00+11:00', 'utc_offset' => '+11:00', 'is_dst' => true]);
    }

    public function test_unknown_timezone_is_a_422(): void
    {
        $this->getJson('/api/v1/time?timezone=Mars/Olympus')
            ->assertStatus(422)
            ->assertJsonValidationErrors('timezone');
    }

    public function test_convert(): void
    {
        $this->getJson('/api/v1/convert?'.http_build_query([
            'time' => '2026-07-17 09:30:00', 'from_tz' => 'Australia/Sydney', 'to_tz' => 'America/New_York',
        ]))
            ->assertOk()
            ->assertJsonPath('source.datetime', '2026-07-17T09:30:00+10:00')
            ->assertJsonPath('converted.datetime', '2026-07-16T19:30:00-04:00')
            ->assertJsonPath('offset_difference', '-14 hours');
    }

    public function test_convert_requires_its_parameters_and_a_parseable_time(): void
    {
        $this->getJson('/api/v1/convert')->assertStatus(422)->assertJsonValidationErrors(['time', 'from_tz', 'to_tz']);

        $this->getJson('/api/v1/convert?time=not-a-time&from_tz=UTC&to_tz=UTC')
            ->assertStatus(422)
            ->assertJsonValidationErrors('time');
    }

    public function test_difference(): void
    {
        $this->getJson('/api/v1/difference?'.http_build_query([
            'start' => '2026-01-01T00:00:00Z', 'end' => '2026-03-15T12:00:00Z', 'unit' => 'hours',
        ]))
            ->assertOk()
            ->assertJson(['total_seconds' => 6350400, 'hours' => 1764, 'human' => '2 months 2 weeks 12 hours']);

        $this->getJson('/api/v1/difference?start=2026-01-01&end=2026-01-02&unit=fortnights')
            ->assertStatus(422)
            ->assertJsonValidationErrors('unit');
    }

    public function test_timezones(): void
    {
        $response = $this->getJson('/api/v1/timezones')->assertOk();

        $this->assertContains('Australia/Sydney', $response->json('timezones'));
        $this->assertSame(count($response->json('timezones')), $response->json('count'));
    }

    public function test_the_api_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/time')->assertOk();
        }

        $this->getJson('/api/v1/time')->assertStatus(429);
    }

    public function test_docs_page_shows_the_connection_details(): void
    {
        $this->get('/docs')->assertOk()->assertSee(url('/mcp'))->assertSee(url('/api/v1'));
    }

    public function test_mcp_endpoint_still_lists_and_runs_its_tools(): void
    {
        $headers = ['Accept' => 'application/json, text/event-stream'];

        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], $headers)
            ->assertOk()
            ->assertSee('get_current_time')->assertSee('convert_time')->assertSee('time_difference');

        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => [
            'name' => 'convert_time',
            'arguments' => ['time' => '2026-07-17 09:30:00', 'from_tz' => 'Australia/Sydney', 'to_tz' => 'America/New_York'],
        ]], $headers)->assertOk()->assertSee('2026-07-16T19:30:00-04:00');
    }

    public function test_timezone_search_matches_spaces_and_case(): void
    {
        $this->getJson('/api/v1/timezones?search=new%20york')
            ->assertOk()
            ->assertExactJson(['count' => 1, 'timezones' => ['America/New_York']]);

        $this->getJson('/api/v1/timezones?search=zzz')->assertOk()->assertJson(['count' => 0]);
    }

    public function test_mcp_list_timezones_tool_and_read_only_hints(): void
    {
        $headers = ['Accept' => 'application/json, text/event-stream'];

        $tools = collect($this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], $headers)
            ->assertOk()->json('result.tools'))->keyBy('name');

        $this->assertCount(4, $tools);
        foreach ($tools as $name => $tool) {
            $this->assertTrue($tool['annotations']['readOnlyHint'] ?? false, "$name should be read-only");
        }

        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => [
            'name' => 'list_timezones', 'arguments' => ['search' => 'sydney'],
        ]], $headers)->assertOk()->assertSee('Australia\\/Sydney', false);
    }
}
