<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user    = User::factory()->create(['monthly_budget' => 50000, 'currency' => 'KES']);
        $token         = JWTAuth::fromUser($this->user);
        $this->headers = ['Authorization' => "Bearer {$token}"];
    }

    public function test_daily_report_returns_expected_structure(): void
    {
        // Seed today's data
        Task::factory()->create([
            'user_id'      => $this->user->id,
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        Transaction::factory()->create([
            'user_id'  => $this->user->id,
            'type'     => 'expense',
            'amount'   => 1200,
            'date'     => today(),
        ]);

        $response = $this->getJson('/api/reports/daily', $this->headers);

        $response->assertOk()
                 ->assertJsonStructure([
                     'data' => [
                         'date',
                         'currency',
                         'tasks'    => ['completed_today', 'pending_today', 'overdue'],
                         'finances' => ['today', 'this_month'],
                         'summary_text',
                     ],
                 ]);

        $this->assertEquals(1, $response->json('data.tasks.completed_today.count'));
        $this->assertEquals(1200, $response->json('data.finances.today.expenses'));
    }

    public function test_daily_report_summary_text_contains_expected_fields(): void
    {
        $response = $this->getJson('/api/reports/daily', $this->headers);

        $summaryText = $response->json('data.summary_text');
        $this->assertStringContainsString('Daily Report', $summaryText);
        $this->assertStringContainsString('Tasks completed', $summaryText);
        $this->assertStringContainsString('Expenses today', $summaryText);
        $this->assertStringContainsString('Remaining budget', $summaryText);
    }

    public function test_weekly_report_returns_7_day_breakdown(): void
    {
        $response = $this->getJson('/api/reports/weekly', $this->headers);

        $response->assertOk()
                 ->assertJsonStructure([
                     'data' => ['period', 'tasks', 'finances', 'daily_breakdown'],
                 ]);

        $this->assertCount(7, $response->json('data.daily_breakdown'));
    }

    public function test_monthly_report_returns_category_breakdown(): void
    {
        Transaction::factory()->create([
            'user_id'  => $this->user->id,
            'type'     => 'expense',
            'category' => 'food',
            'amount'   => 3000,
            'date'     => today(),
        ]);

        $response = $this->getJson('/api/reports/monthly', $this->headers);

        $response->assertOk()
                 ->assertJsonStructure([
                     'data' => [
                         'finances' => ['income', 'expenses', 'category_breakdown'],
                         'tasks'    => ['completed', 'created', 'completion_rate'],
                     ],
                 ]);
    }

    public function test_dashboard_aggregates_finance_and_task_data(): void
    {
        $response = $this->getJson('/api/dashboard', $this->headers);

        $response->assertOk()
                 ->assertJsonStructure([
                     'data' => [
                         'user',
                         'finance' => ['current_month', 'budget_alert', 'history_6_months'],
                         'tasks'   => ['stats', 'priorities', 'upcoming'],
                     ],
                 ]);
    }
}
