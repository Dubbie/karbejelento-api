<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\PaginationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PaginationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_filter_by_not_equal(): void
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Doe']);

        // Simulate an HTTP request with a 'neq' filter
        $request = new Request(['filter' => ['name:neq:John Doe']]);

        $result = PaginationService::paginate(User::query(), $request, [
            'filterableFields' => ['name']
        ]);

        $this->assertEquals(1, $result['meta']['totalItems']);
        $this->assertEquals('Jane Doe', $result['data'][0]->name);
    }

    public function test_it_can_filter_by_in_operator(): void
    {
        User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'manager']);
        User::factory()->create(['role' => 'customer']);

        // Simulate a request with an 'in' filter for multiple roles
        $request = new Request(['filter' => ['role:in:admin,manager']]);

        $result = PaginationService::paginate(User::query(), $request, [
            'filterableFields' => ['role']
        ]);

        $this->assertEquals(2, $result['meta']['totalItems']);
    }

    public function test_it_ignores_invalid_sort_fields(): void
    {
        User::factory()->create(['name' => 'Zebra']);
        User::factory()->create(['name' => 'Apple']);

        // Attempt to sort by an invalid 'id' field
        $request = new Request(['sort' => 'id:asc']);

        $result = PaginationService::paginate(User::query(), $request, [
            'sortableFields' => ['name'] // 'id' is not in the whitelist
        ]);

        // It should fall back to the default sort (created_at desc), but for this test,
        // we can just assert that it DID NOT sort by name asc.
        $this->assertEquals('Zebra', $result['data'][0]->name);
    }
}
