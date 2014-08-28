<?php 

return array(

    'generate_id'   =>  false,

    'database_type_rules' => array(
        'string'    => 'word',
        'integer'   => 'numberBetween|1|10000',
        'bigint'    => 'numberBetween|1000|10000',
        'smallint'  => 'numberBetween|1|100',
        'decimal'   => 'randomFloat|2|0|1000',
        'float'     => 'randomFloat|2|0|1000',
        'boolean'   => 'boolean',
        'date'      => 'date',
        'time'      => 'unixTime',
        'datetime'  => 'date|Y-m-d H:i:s',
        'text'      => 'text',
    ),

    'special_field_rules' => array(
        'username'      => 'username',
        'name'          => 'name', 
        'first_name'    => 'firstName',
        'last_name'     => 'lastName',
        'email'         => 'email', 
        'phone'         => 'phoneNumber',
        'age'           => 'numberBetween|20|90', 
        'address'       => 'address', 
        'city'          => 'city',
        'state'         => 'state', 
        'zip'           => 'postcode', 
        'street'        => 'streetName',
        'website'       => 'url', 
        'url'           => 'url',
        'ip'            => 'ipv4',
        'description'   => 'text',
        'latitude'      => 'randomFloat|6|-90|90',
        'longitude'     => 'randomFloat|6|-180|180',
        'uuid'          => 'uuid',
    ),

);
