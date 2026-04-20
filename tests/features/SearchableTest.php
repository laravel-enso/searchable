<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use LaravelEnso\Permissions\Models\Permission;
use LaravelEnso\Searchable\Facades\Search;
use LaravelEnso\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
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

        $this->seed()
            ->actingAs(User::first());

        $this->testModel = $this->model();

        $this->setConfig();
    }

    #[Test]
    public function can_fetch_the_correct_searched_model()
    {
        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment(['param' => ['searchableTestModel' => $this->testModel->id]]);
    }

    #[Test]
    public function can_show_model_attributes()
    {
        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment([$this->testModel->name]);
    }

    #[Test]
    public function can_show_computed_model_attributes()
    {
        $this->setConfig($computed = true);

        $this->testModel->computedLabel;
        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment([$this->testModel->computedLabel]);
    }

    #[Test]
    public function can_fetch_routes_for_searched_model()
    {
        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment([self::SearchablePermission]);
    }

    #[Test]
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

    #[Test]
    public function can_fetch_the_correct_group()
    {
        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment([
                'group' => Search::all()->get('SearchableTestModel')['group'],
            ]);
    }

    #[Test]
    public function can_use_custom_route_params_for_search_results()
    {
        $this->setConfig(routeParam: ['searchableSlug' => 'slug']);

        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment(['param' => ['searchableSlug' => $this->testModel->slug]]);
    }

    #[Test]
    public function can_apply_registered_scopes_before_searching()
    {
        SearchableTestModel::create([
            'name'      => $this->testModel->name,
            'slug'      => 'inactive',
            'is_active' => false,
        ]);

        $this->setConfig(scopes: ['active']);

        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonMissing(['param' => ['searchableTestModel' => 2]]);
    }

    #[Test]
    public function can_use_the_registered_search_provider_branch()
    {
        $this->setConfig(searchProvider: true);

        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment(['param' => ['searchableTestModel' => $this->testModel->id]]);
    }

    private function model()
    {
        $this->createTestTable();

        Permission::factory()->create([
            'name'       => 'searchableModels.test',
            'is_default' => true,
        ])->roles()->attach(Auth::user()->role->id);

        return SearchableTestModel::create(['name' => 'searchable']);
    }

    private function createTestTable()
    {
        Schema::create('searchable_test_models', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        return $this;
    }

    private function setConfig($computed = false, ?array $routeParam = null, array $scopes = [], bool $searchProvider = false)
    {
        Search::register([
            SearchableTestModel::class => [
                'group'      => 'SearchableTestModel',
                'attributes' => ['name', 'computedLabel'],
                'label'      => $computed === false
                    ? 'name'
                    : 'computedLabel',
                'permissionGroup' => 'searchableModels',
                'permissions'     => ['test'],
                'routeParam'      => $routeParam,
                'scopes'          => $scopes,
                'searchProvider'  => $searchProvider,
            ],
        ]);
    }

    private function setDefaultRoute()
    {
        $defaultPermission = Permission::factory()->create([
            'name'       => 'searchableModels.'.self::DefaultPermission,
            'is_default' => true,
        ]);

        $defaultPermission->roles()->attach(Auth::user()->role->id);

        config(['enso.searchable.routes' => [self::DefaultPermission => 'test-icon']]);
        config(['enso.searchable.models.SearchableTestModel.permissions' => null]);
    }
}

class SearchableTestModel extends Model
{
    protected $fillable = ['name', 'slug', 'is_active'];

    protected $appends = ['computedLabel'];

    public function getComputedLabelAttribute()
    {
        return 'computedAttribute';
    }

    public function scopeActive($query)
    {
        return $query->whereIsActive(true);
    }

    public static function search($term)
    {
        return new SearchableTestSearchBuilder($term);
    }
}

class SearchableTestSearchBuilder
{
    public function __construct(private string $term)
    {
    }

    public function take($limit)
    {
        return $this;
    }

    public function get()
    {
        return SearchableTestModel::query()
            ->where('name', 'like', "%{$this->term}%")
            ->get();
    }
}
