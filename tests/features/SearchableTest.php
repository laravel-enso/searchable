<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use LaravelEnso\Core\Models\User;
use LaravelEnso\Permissions\Models\Permission;
use LaravelEnso\Searchable\Facades\Search;
use Tests\TestCase;

class SearchableTest extends TestCase
{
    use RefreshDatabase;

    private const SearchablePermission = 'searchableModels.test';
    private const DefaultPermission = 'defaultPermission';

    private $testModel;

    protected function setUp(): void
    {
        parent::setUp();

        // $this->withoutExceptionHandling();

        $this->seed()
            ->actingAs(User::first());

        $this->testModel = $this->model();

        $this->setConfig();
    }

    /** @test */
    public function can_fetch_the_correct_searched_model()
    {
        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment(['param' => ['searchableTestModel' => $this->testModel->id]]);
    }

    /** @test */
    public function can_show_model_attributes()
    {
        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment([$this->testModel->name]);
    }

    /** @test */
    public function can_show_computed_model_attributes()
    {
        $this->setConfig($computed = true);

        $this->testModel->computedLabel;
        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment([$this->testModel->computedLabel]);
    }

    /** @test */
    public function can_fetch_routes_for_searched_model()
    {
        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment([self::SearchablePermission]);
    }

    /** @test */
    public function can_fetch_default_routes_for_searched_model()
    {
        $this->setDefaultRoute();

        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment([[
                'group' => 'SearchableTestModel',
                'label' => 'searchable',
                'param' => [
                    'searchableTestModel' => 1,
                ],
                'routes' => [[
                    'icon' => null,
                    'name' => 'searchableModels.test',
                ]],
            ]]);
    }

    /** @test */
    public function can_fetch_the_correct_group()
    {
        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment([
                'group' => Search::all()->get('SearchableTestModel')['group'],
            ]);
    }

    private function model()
    {
        $this->createTestTable();

        Permission::factory()->create([
            'name' => 'searchableModels.test',
            'is_default' => true,
        ])->roles()->attach(Auth::user()->role->id);

        return SearchableTestModel::create(['name' => 'searchable']);
    }

    private function createTestTable()
    {
        Schema::create('searchable_test_models', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        return $this;
    }

    private function setConfig($computed = false)
    {
        Search::register([
            SearchableTestModel::class => [
                'group' => 'SearchableTestModel',
                'attributes' => ['name', 'computedLabel'],
                'label' => $computed === false
                    ? 'name'
                    : 'computedLabel',
                'permissionGroup' => 'searchableModels',
                'permissions' => ['test'],
            ],
        ]);
    }

    private function setDefaultRoute()
    {
        $defaultPermission = Permission::factory()->create([
            'name' => 'searchableModels.'.self::DefaultPermission,
            'is_default' => true,
        ]);

        $defaultPermission->roles()->attach(Auth::user()->role->id);

        config(['enso.searchable.routes' => [self::DefaultPermission => 'test-icon']]);
        config(['enso.searchable.models.SearchableTestModel.permissions' => null]);
    }
}

class SearchableTestModel extends Model
{
    protected $fillable = ['name'];

    protected $appends = ['computedLabel'];

    public function getComputedLabelAttribute()
    {
        return 'computedAttribute';
    }
}
