<?php
declare(strict_types=1);

namespace DKulyk\Presentation;

/**
 * Class Presenter
 *
 * @package DKulyk\Presentation
 */
class Presenter
{
    /**
     * Get presenter instance.
     *
     * @return Presenter
     */
    public static function instance(): Presenter
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new Presenter();
        }

        return $instance;
    }

    /**
     * @var array
     */
    protected $aliases = [];

    /**
     * @var callable[][]
     */
    protected $resolvers = [];

    /**
     * @var ClassPresenter[]
     */
    protected $presenters = [];

    /**
     * Add presenter resolver.
     *
     * @param string   $class
     * @param callable $callback
     * @param string[] $aliases
     *
     * @return Presenter
     */
    public function addResolver(string $class, callable $callback, string ...$aliases): Presenter
    {
        if (array_key_exists($class, $this->resolvers)) {
            $this->resolvers[$class][] = $callback;
        } else {
            $this->resolvers[$class] = [$callback];
        }

        $this->aliases($class, ...$aliases);

        return $this;
    }

    /**
     * Add aliases.
     *
     * @param string   $class
     * @param string[] $aliases
     *
     * @return Presenter
     */
    public function aliases(string $class, string ...$aliases): Presenter
    {
        foreach ($aliases as $alias) {
            $this->aliases[$alias] = $class;
        }

        return $this;
    }

    /**
     * @param string        $class
     * @param callable|null $callback
     *
     * @return ClassPresenter
     */
    public function presenter(string $class, callable $callback = null): ClassPresenter
    {
        if (!array_key_exists($class, $this->presenters)) {
            $this->presenters[$class] = new ClassPresenter($this, $class);
        }
        if ($callback !== null) {
            $this->presenters[$class]->using($callback);
        }

        return $this->presenters[$class];
    }

    /**
     * @param string $class
     *
     * @return ClassPresenter|null
     */
    public function resolve(string $class)
    {
        //resolve alias.
        if (array_key_exists($class, $this->aliases)) {
            $class = $this->aliases[$class];
        }

        if (array_key_exists($class, $this->presenters)) {
            return $this->presenters[$class];
        }

        if (array_key_exists($class, $this->resolvers)) {
            $presenter = $this->presenter($class);
            foreach ($this->resolvers[$class] as $callable) {
                call_user_func($callable, $presenter, $this);
            }
            return $presenter;
        }
        return null;
    }

    /**
     * @param mixed $object
     * @param array|string|null $with
     * @param mixed $default
     *
     * @return mixed
     */
    public function present($object, $with = null, $default = null)
    {
        if (is_array($object)) {
            return array_map([$this, 'present'], $object);
        }

        if (is_scalar($object)) {
            return $object;
        }

        if (is_object($object)) {
            $presenter = $this->resolve(get_class($object));
            if ($presenter !== null) {
                return $presenter->present($object, is_string($with) ? explode(',', $with) : (array)$with, $default);
            }
        }

        return $object;
    }
}
