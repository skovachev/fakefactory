<?php 

class DummyFakerClass extends Skovachev\Fakefactory\Faker
{
    public static $fakerClass = 'FooFaker';

    public function getFooFakeValue($f)
    {
        return 'bar';
    }
}