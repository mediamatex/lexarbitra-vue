<?php

use App\Models\CaseFile;
use App\Models\User;
use App\Services\CaseDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
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

    expect(CaseFile::count())->toBe(1);

    $caseFile = CaseFile::first();
    expect($caseFile->case_number)->toBe('Az. 1/2024');
    expect($caseFile->title)->toBe('Test Case');
});

it('can view case files index', function () {
    CaseFile::factory()->count(3)->create();

    $response = $this->actingAs($this->user)->get('/cases');

    $response->assertStatus(200);
    $response->assertInertia(function ($page) {
        $page->component('CaseFiles/Index')
            ->has('cases.data', 3);
    });
});

it('creates database connection when creating case file', function () {
    $this->mock(CaseDatabaseService::class, function ($mock) {
        $mock->shouldReceive('createCaseDatabase')->once();
    });

    $response = $this->actingAs($this->user)->post('/cases', [
        'case_number' => 'Az. 1/2024',
        'title' => 'Test Case',
        'initiated_at' => '2024-01-01',
    ]);

    $response->assertRedirect();
    expect(CaseFile::count())->toBe(1);
});

it('validates required fields', function () {
    $response = $this->actingAs($this->user)->post('/cases', []);

    $response->assertSessionHasErrors(['case_number', 'title', 'initiated_at']);
});

it('ensures case number is unique', function () {
    CaseFile::factory()->create(['case_number' => 'Az. 1/2024']);

    $response = $this->actingAs($this->user)->post('/cases', [
        'case_number' => 'Az. 1/2024',
        'title' => 'Test Case',
        'initiated_at' => '2024-01-01',
    ]);

    $response->assertSessionHasErrors(['case_number']);
});
