<?php 

namespace Hansvn\Vcalendar\Facades;
use Illuminate\Support\Facades\Facade;

class L4Facade extends Facade {

    protected static function getFacadeAccessor() {
        return 'Hansvn\Vcalendar\Vcalendar';
    }
    
}