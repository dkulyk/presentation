<?php
declare(strict_types=1);

namespace DKulyk\Presentation;

/**
 * Class ClassPresenter
 *
 * @package DKulyk\Presentation
 */
class ClassPresenter
{
    /**
     * @var callable[]
     */
    protected $uses = [];

    /**
     * @var callable[][]
     */
    protected $with = [];

    /**
     * @var Presenter
     */
    protected $presenter;

    /**
     * @var string
     */
    protected $class;

    /**
     * ClassPresenter constructor.
     *
     * @param Presenter $presenter
     * @param string    $class
     */
    public function __construct(Presenter $presenter, string $class)
    {
        $this->presenter = $presenter;
        $this->class = $class;
    }

    /**
     * @param callable $callable
     *
     * @return ClassPresenter
     */
    public function using(callable $callable): ClassPresenter
    {
        $this->uses[] = $callable;

        return $this;
    }

    /**
     * @param mixed $object
     * @param array $with
     * @param mixed $result
     *
     * @return mixed
     * @internal param mixed $data
     */
    public function present($object, array $with = [], $result = null)
    {
        if (count($this->uses) === 0) {
            return $object;
        }
        $result = $this->call($this->uses, $object, $result, $with);

        foreach ($this->normalizeWith($with) as $name => $with2) {
            if (array_key_exists($name, $this->with)) {
                $result = $this->call($this->with[$name], $object, $result, $with2);
            }
        }
        return $result;
    }

    /**
     * @param array $callable
     * @param mixed $object
     * @param mixed $result
     * @param array $with
     *
     * @return mixed
     */
    protected function call(array $callable, $object, $result, array $with)
    {
        return array_reduce($callable, function ($result, callable $callable) use ($object, $with) {
            return call_user_func($callable, $object, $result, $with, $this->presenter);
        }, $result);
    }

    /**
     * @param array $with
     *
     * @return array
     */
    protected function normalizeWith(array $with): array
    {
        return array_reduce($with, function (array $result, string $with) {
            $args = explode('.', $with, 2);
            if (!array_key_exists($args[0], $result)) {
                $result[$args[0]] = [];
            }
            if (count($args) > 1) {
                $result[$args[0]][] = $args[1];
            }

            return $result;
        }, []);
    }

    /**
     * @param string   $with
     * @param callable $callback
     *
     * @return ClassPresenter
     */
    public function with(string $with, callable $callback): ClassPresenter
    {
        if (array_key_exists($with, $this->with)) {
            $this->with[$with][] = $callback;
        } else {
            $this->with[$with] = [$callback];
        }

        return $this;
    }
}
