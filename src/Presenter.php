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
     *
     * @return Presenter
     */
    public function addResolver(string $class, callable $callback): Presenter
    {
        if (array_key_exists($class, $this->resolvers)) {
            $this->resolvers[$class][] = $callback;
        } else {
            $this->resolvers[$class] = [$callback];
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
     * @param array $with
     * @param mixed $default
     *
     * @return mixed
     */
    public function present($object, array $with = [], $default = null)
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
                return $presenter->present($object, $with, $default);
            }
        }

        return $object;
    }
}
