## iCalendar Generator for laravel

This package contains an iCalendar file generator for laravel.
Created from some code I had lying around.

### Usage

```
$vcalendar = array(
    'prodid' => array('company' => 'My Company', 'product' => 'VCalendar attachment', 'language' => \App::getLocale()),
    'uid' => 'my@email.address',
    'organizer' => array('name' => 'my name', 'email' => 'my@email.address'),
    'location' => 'Grand Canyon',
    'subject' => 'Hiking',
    'description' => 'Going on a trip',
    'start_date' => '2017-03-10 09:00:00',
    'end_date' => '2017-03-10 19:00:00',
    'attendees' => array(
        array('name' => 'an invitee', 'email' => 'invitee@email.address'),
    )
);

//return file_path on temp directory
$file = VCalendar::generate($vcalendar);
```

## Installation

### Laravel 5.x:

After updating composer, add the ServiceProvider to the providers array in config/app.php

    Hansvn\Vcalendar\ServiceProvider::class,

Add this to your facades:

    'VCalendar' => Hansvn\Vcalendar\Facade::class
    
### Laravel 4.x:

After updating composer, add the ServiceProvider to the providers array in config/app.php

    'Hansvn\Vcalendar\L4ServiceProvider',

Add this to your facades:

    'VCalendar' => 'Hansvn\Vcalendar\L4Facade'

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
