<?php

use Faker\Factory;
use Tests\TestCase;
use LaravelEnso\Core\app\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelEnso\PermissionManager\app\Models\Permission;

class SearchableTest extends TestCase
{
    use  RefreshDatabase;

    private $testModel;

    const SearchablePermission = 'searchableModels.test';
    const DefaultPermission = 'defaultPermission';

    protected function setUp()
    {
        parent::setUp();

        // $this->withoutExceptionHandling();

        $this->seed()
            ->actingAs(User::first());

        $this->testModel = $this->model();

        $this->setConfig();
    }

    /** @test */
    public function can_access_search_index()
    {
        $this->get('core.search.index', [], false)
            ->assertStatus(200);
    }

    /** @test */
    public function can_bring_the_correct_searched_model()
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
    public function can_bring_routes_for_searched_model()
    {
        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment([self::SearchablePermission]);
    }

    /** @test */
    public function can_bring_default_routes_for_searched_model()
    {
        $defaultPermission = $this->setDefaultRoute();

        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment(['routes' => [['icon' => 'test-icon', 'name' => $defaultPermission->name]]]);
    }

    /** @test */
    public function can_bring_the_correct_group()
    {
        $this->get(route('core.search.index', ['query' => $this->testModel->name], false))
            ->assertStatus(200)
            ->assertJsonFragment(['group' => config('enso.searchable.models.SearchableTestModel.group')
            ]);
    }

    private function model()
    {
        $this->createTestTable();

        factory(Permission::class)->create([
            'name' => 'searchableModels.test',
            'is_default' => true,
        ])->roles()->attach(auth()->user()->role->id);

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
        config(['enso.searchable.models' => [
                SearchableTestModel::class => [
                    'group' => 'SearchableTestModel',
                    'attributes' => ['name', 'computedLabel'],
                    'label' => $computed === false
                        ? 'name'
                        : 'computedLabel',
                    'permissionGroup' => 'searchableModels',
                    'permissions' => ['test'],
                ]
            ]
        ]);
    }

    private function setDefaultRoute()
    {
        $defaultPermission = factory(Permission::class)->create([
                'name' => 'searchableModels.'.self::DefaultPermission,
                'is_default' => true,
        ]);

        $defaultPermission->roles()->attach(auth()->user()->role->id);

        config(['enso.searchable.routes' => [self::DefaultPermission => 'test-icon']]);
        config(['enso.searchable.models.SearchableTestModel.permissions' => null]);

        return $defaultPermission;
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
