<?php

namespace YourVendor\AIRotationManager\Facades;

use Illuminate\Support\Facades\Facade;

class AIRotation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ai-rotation';
    }
}
