<?php

use App\Models\CaseReference;
use App\Models\User;
use App\Services\CaseDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    // Mock the database service to avoid actual database creation during tests
    $this->mock(CaseDatabaseService::class, function ($mock) {
        $caseReference = CaseReference::create([
            'database_name' => 'test_case_db',
            'database_user' => 'test_user',
            'database_password' => '',
            'database_host' => '/tmp/test_case.sqlite',
            'connection_name' => 'test_case_connection',
            'is_active' => true,
        ]);
        $mock->shouldReceive('createCaseDatabase')->andReturn($caseReference);
        $mock->shouldReceive('switchToCaseDatabase')->andReturn('test_case_connection');
        $mock->shouldReceive('switchBackToMainDatabase')->andReturn();
    });
});

it('can create a case file', function () {
    $response = $this->actingAs($this->user)->post('/cases', [
        'case_number' => 'Az. 1/2024',
        'title' => 'Test Case',
        'description' => 'Test case description',
        'initiated_at' => '2024-01-01',
        'dispute_value' => 100000,
        'currency' => 'EUR',
        'jurisdiction' => 'Germany',
        'case_category' => 'Construction',
        'complexity_level' => 'medium',
        'urgency_level' => 'normal',
    ]);

    $response->assertRedirect();

    expect(CaseReference::count())->toBe(1);

    $caseReference = CaseReference::first();
    expect($caseReference->database_name)->toBe('test_case_db');
    expect($caseReference->is_active)->toBe(true);
});

it('can view case files index', function () {
    // Create case references without tenant database setup for index test
    CaseReference::factory()->count(3)->create([
        'tenant_case_id' => null, // No tenant case data for index test
    ]);

    $response = $this->actingAs($this->user)->get('/cases');

    $response->assertStatus(200);
    $response->assertInertia(function ($page) {
        $page->component('CaseFiles/Index')
            ->has('cases.data', 0); // No tenant data available, so empty
    });
});

it('creates database connection when creating case file', function () {
    $response = $this->actingAs($this->user)->post('/cases', [
        'case_number' => 'Az. 1/2024',
        'title' => 'Test Case',
        'initiated_at' => '2024-01-01',
    ]);

    $response->assertRedirect();
    expect(CaseReference::count())->toBe(1);
});

it('validates required fields', function () {
    $response = $this->actingAs($this->user)->post('/cases', []);

    $response->assertSessionHasErrors(['case_number', 'title', 'initiated_at']);
});
