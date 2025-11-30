<?php

namespace App\Http\Resources\Device;


use App\Http\Resources\DeviceResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DeviceCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = DeviceResource::class;

   
}
