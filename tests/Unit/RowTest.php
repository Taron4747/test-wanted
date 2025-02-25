<?
namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Row;
use Carbon\Carbon;

class RowTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_can_store_row()
    {
        $row = Row::create([
            'id' => 1,
            'name' => 'Test Name',
            'date' => Carbon::now()->format('Y-m-d'),
        ]);

        $this->assertDatabaseHas('rows', ['id' => 1]);
    }

    public function test_rows_index_page_displays_rows()
    {
        Row::create([
            'id' => 1,
            'name' => 'John Doe',
            'date' => Carbon::now()->format('Y-m-d'),
        ]);
        $response = $this->withoutMiddleware()->get('/rows');
        $response->assertStatus(200);
        $response->assertSee('John Doe');
    }
}
