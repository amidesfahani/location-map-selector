<?php

namespace Amidesfahani\LocationMapSelector;

use Laravel\Nova\Fields\Field;

class LocationMapSelector extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'location-map-selector';

    protected $image;

    public function image($image)
    {
        $this->image = $image;

        return $this;
    }

    public function getImage($parameters = [])
    {
        return call_user_func($this->image, $parameters);
    }

    public function parent($attribute)
    {
        $this->withMeta(['parentAttribute' => $attribute]);
        return $this;
    }
}
