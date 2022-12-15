<?php

namespace Javoscript\MacroableModels;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MacroableModels
{
    private $macros = [];

    public function getAllMacros()
    {
        return $this->macros;
    }

    public function addMacro(String $model, String $name, \Closure $closure)
    {
        $this->checkModelSubclass($model);

        if (! isset($this->macros[$name])) $this->macros[$name] = [];
        $this->macros[$name][$model] = $closure;
        $this->syncMacros($name);
    }

    public function removeMacro($model, String $name)
    {
        $this->checkModelSubclass($model);

        if (isset($this->macros[$name]) && isset($this->macros[$name][$model])) {
            unset($this->macros[$name][$model]);
            if (count($this->macros[$name]) == 0) {
                unset($this->macros[$name]);
            } else {
                $this->syncMacros($name);
            }
            return true;
        }

        return false;
    }

    public function modelHasMacro($model, $name)
    {
        $this->checkModelSubclass($model);
        return (isset($this->macros[$name]) && isset($this->macros[$name][$model]));
    }

    public function modelsThatImplement($name)
    {
        if (! isset($this->macros[$name])) return [];
        return array_keys($this->macros[$name]);
    }

    public function macrosForModel($model)
    {
        $this->checkModelSubclass($model);

        $macros = [];

        foreach($this->macros as $macro => $models) {
            if (in_array($model, array_keys($models))) {
                $params = (new \ReflectionFunction($this->macros[$macro][$model]))->getParameters();
                $macros[$macro] = [
                    'name' => $macro,
                    'parameters' => $params,
                ];
            }
        }

         return $macros;
    }

    private function syncMacros($name)
    {
        $models = $this->macros[$name];
        Builder::macro($name, function(...$args) use ($name, $models){
            $class = get_class($this->getModel());

            if (! isset($models[$class])) {
                throw new \BadMethodCallException("Call to undefined method {$class}::{$name}()");
            }

            $closure = \Closure::bind($models[$class], $this->getModel());
            return call_user_func($closure, ...$args);
        });
    }

    private function checkModelSubclass(String $model)
    {
        if (! is_subclass_of($model, Model::class)) {
            throw new \InvalidArgumentException('$model must be a subclass of Illuminate\\Database\\Eloquent\\Model');
        }
    }
}
