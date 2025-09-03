<?php
namespace Underscore\Methods;

/**
 * Methods to manage objects.
 */
class ObjectMethods extends CollectionMethods
{
    /**
     * Get all methods from an object.
     */
    public static function methods($object)
    {
        return get_class_methods(get_class($object));
    }

    /**
     * Unpack an object's properties.
     */
    public static function unpack($object, $attribute = null)
    {
        $object = (array) $object;
        $object = $attribute
            ? ArraysMethods::get($object, $attribute)
            : ArraysMethods::first($object);

        return (object) $object;
    }
}
