<?php

namespace Holloway;

use Illuminate\Support\Collection;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\FactoryBuilder as EloquentFactoryBuilder;
use InvalidArgumentException;

class FactoryBuilder extends EloquentFactoryBuilder
{
    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * Create an new builder instance.
     *
     * @param  Mapper  $mapper
     * @param  string  $class
     * @param  string  $name
     * @param  array   $definitions
     * @param  Faker   $faker
     * @return void
     */
    public function __construct(Mapper $mapper, $class, $name, array $definitions, array $states, Faker $faker)
    {
        $this->mapper = $mapper;

        parent::__construct($class, $name, $definitions, $states, $faker);
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function create(array $attributes = [])
    {
        $results = $this->make($attributes);

        $this->mapper->store($results);

        return $results;
    }

    /**
     * Create a collection of models.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function make(array $attributes = [])
    {
        if ($this->amount === null) {
            return $this->makeInstance($attributes);
        } else {
            $results = [];

            for ($i = 0; $i < $this->amount; $i++) {
                $results[] = $this->makeInstance($attributes);
            }

            return new Collection($results);
        }
    }

    /**
     * Make an instance of the model with the given attributes.
     *
     * @param  array  $attributes
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    protected function makeInstance(array $attributes = [])
    {
        if (! isset($this->definitions[$this->class][$this->name])) {
            throw new InvalidArgumentException("Unable to locate factory with name [{$this->name}] [{$this->class}].");
        }

        $record = (object) $this->getRawAttributes($attributes);

        $relations = array_filter((array) $record, function($attribute) {
            return $this->mapper->hasRelationship($attribute);
        }, ARRAY_FILTER_USE_KEY);

        return $this->mapper->hydrate($record, collect($relations));
    }

    /**
     * Expand all attributes to their underlying values.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function expandAttributes(array $attributes)
    {
        foreach ($attributes as &$attribute) {
            if (is_callable($attribute) && ! is_string($attribute)) {
                $attribute = $attribute($attributes);
            }

            if ($attribute instanceof static) {
                $attribute = $attribute->create()->getKey();
            }
        }

        return $attributes;
    }
}