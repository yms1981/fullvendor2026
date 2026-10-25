<?php

namespace Tests;

use Carbon\Carbon;
use Database\Seeders\Test\DatabaseSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Arr;
use Tests\TestCase as BaseTestCase;


abstract class ModelBaseTestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected $model;
    protected $seeder = DatabaseSeeder::class;
    protected $expectedColumns = [];

    public function testFetch()
    {
        $this->createModelInstance(); // ensure at least one instance of $model present
        $items = $this->model::all();
        $this->assertNotNull($items);
        $this->assertInstanceOf(Collection::class,$items);
        $item = $items->first();
        $this->assertNotNull($item);
        $this->assertInstanceOf($this->model, $item);
    }

    /**
     * adds a model
     * @return Model
     */
    public function createModelInstance($attributes=[]) {
        return $this->model::factory()->create($attributes);
    }

    /**
     * Testea una relación BelongsTo
     *
     * @param $relation
     * @param $relatedModel
     * @return void
     */
    function assertBelongsTo($relation, $relatedModel)
    {
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(1, $relation->count());
        $this->assertInstanceOf($relatedModel, $relation->sole());
    }

    /**
     * Asserts the relation is HasOne and the relation's logic is correct
     *
     * @param HasOne $relation
     * @param $relatedModel
     * @param boolean $strict if not strict allows 0
     * @return void
     */
    function assertHasOne($relation, $relatedModel,$strict=true)
    {
        $this->assertInstanceOf(HasOne::class, $relation);
        $count = $relation->count();
        if($strict){
            $this->assertEquals(1, $count); //accepting only 1
            $this->assertInstanceOf($relatedModel, $relation->sole());
        }
        else {
            $this->assertLessThanOrEqual(1, $count); //accepting 0 and 1
            if($count>0)
                $this->assertInstanceOf($relatedModel, $relation->sole());
        }

    }

    /**
     * Asserts the relation is HasOne and the relation's logic is correct
     *
     * @param HasOneThrough $relation
     * @param $relatedModel
     * @param boolean $strict if not strict allows 0
     * @return void
     */
    function assertHasOneThrough($relation, $relatedModel,$strict=true)
    {
        $this->assertInstanceOf(HasOneThrough::class, $relation);
        $count = $relation->count();
        if($strict){
            $this->assertEquals(1, $count); //accepting only 1
            $this->assertInstanceOf($relatedModel, $relation->sole());
        }
        else {
            $this->assertLessThanOrEqual(1, $count); //accepting 0 and 1
            if($count>0)
                $this->assertInstanceOf($relatedModel, $relation->sole());
        }

    }

    /**
     * Testea una relación HasMany
     *
     * @param HasMany $relation
     * @param $relatedModel
     * @return void
     */
    function assertHasMany($relation, $relatedModel)
    {
        $this->assertInstanceOf(HasMany::class, $relation);
        foreach($relation->get() as $elem)
        {
            $this->assertInstanceOf($relatedModel, $elem);
        }
    }

    /**
     * Testea una relación HasManyThrough
     *
     * @param HasManyThrough $relation
     * @param $relatedModel
     * @return void
     */
    function assertHasManyThrough($relation, $relatedModel)
    {
        $this->assertInstanceOf(HasManyThrough::class, $relation);
        foreach($relation->get() as $elem)
        {
            $this->assertInstanceOf($relatedModel, $elem);
        }
    }

    /**
     * Testea una relación BelongsToMany
     *
     * @param $relation
     * @param $relatedModel
     * @return void
     */
    function assertBelongsToMany($relation, $relatedModel)
    {
        $this->assertInstanceOf(BelongsToMany::class, $relation);
        foreach($relation->get() as $elem)
        {
            $this->assertInstanceOf($relatedModel, $elem);
        }
    }


    /**
     * @param Model $item
     * @param string $propertyName
     * @param int|float|double $propertyNewValue
     * @return void
     */
    protected static function assertModifiesNumberProperty($item,$propertyName,$propertyNewValue=0) {
        $origValue = $item->$propertyName;
        $item->$propertyName = $propertyNewValue;
        $item->save();    $item->refresh();
        self::assertNotSame($origValue,$item->$propertyName);
        self::assertEquals($propertyNewValue,$item->$propertyName);
    }

    /**
     * @param Model $item
     * @param string $propertyName
     * @param string $propertyNewValue
     * @return void
     */
    protected static function assertModifiesStringProperty($item,$propertyName,$propertyNewValue='Some value') {
        $origValue = $item->$propertyName;
        $item->$propertyName = $propertyNewValue;
        $item->save();    $item->refresh();
        self::assertNotEquals($origValue,$item->$propertyName);
        self::assertEquals($propertyNewValue,$item->$propertyName);
    }

    /**
     * @param Model $item
     * @param string $propertyName
     * @param $format
     * @param Carbon|null $afterDate
     * @return void
     */
    protected static function assertModifiesDateProperty($item, $propertyName, $format = "Y-m-d", $afterDate=null)
    {
        $origValue = $item->$propertyName;
        if(!isset($afterDate))
            $afterDate = Carbon::now();
        $randomDate = $afterDate->addDays(rand(1, 55));

        $item->$propertyName = $randomDate;
        $item->save();    $item->refresh();
        self::assertNotEquals($origValue, $item->$propertyName);
        self::assertEquals($randomDate->format($format), $item->$propertyName->format($format));
    }

    protected static function assertModifyEnumProperty($item,$propertyName,$enumValues) {
        $orig=$item->$propertyName;
        $differentEnumValues = Arr::where($enumValues,function($value,$key) use ($orig) {
            return  $value!=$orig;
        });
        $item->$propertyName= Arr::random($differentEnumValues);
        $item->save(); $item->refresh();
        self::assertNotEquals($orig, $item->$propertyName);
    }

    public function testAdd()
    {
        $m = $this->createModelInstance();
        self::assertInstanceOf($this->model,$m);
        $m2 = $this->model::where($this->getIdCondArray($m))->sole();
        self::assertEquals($this->getIdCondArray($m),$this->getIdCondArray($m2));
    }

    /**
     * Defines the condition for the queryBuilder to find a model of self::$model class as an array of conditions
     * @param Model $item
     * @return array[]
     */
    protected function getIdCondArray($item) {
        $key = $item->getKeyName();
        $idCondArr = [];
        if(is_string($key))
            return [[$key, $item->$key]];

        foreach ($key as $partialKey) {
            $idCondArr[] = [$partialKey,$item->$partialKey];
        }
        return $idCondArr;
    }

    /**
     * Deletes a given model by using getIdCondArray to get the builder
     * this is useful when the model doesn't have a primary 'id' key
     * @param $item
     * @return mixed
     */
    protected function deleteModel($item) {
        return $this->model::query()->where($this->getIdCondArray($item))->delete();
    }

    /**
     * @param Model $item
     * @return void
     */
    protected function removeRelationsForModelDeletion($item) {

    }

    /**
     * Simple add and delete of a model
     * @return void
     */
    public function testDelete() {
        $m = $this->createModelInstance();
        self::assertNotNull($m);
        $this->removeRelationsForModelDeletion($m);
        self::assertEquals(true, $this->deleteModel($m));
        $m = $this->model::query()->where($this->getIdCondArray($m))->first();
        self::assertNull($m);
    }


}
