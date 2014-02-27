<?php 

return array(

    'generate_id'   =>  false,

    'database_type_rules' => array(
        'string'    => 'word',
        'integer'   => 'randomNumber|1|10000',
        'bigint'    => 'randomNumber|1000|10000',
        'smallint'  => 'randomNumber|1|100',
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
        'age'           => 'randomNumber|20|90', 
        'address'       => 'address', 
        'city'          => 'city',
        'state'         => 'state', 
        'zip'           => 'postcode', 
        'street'        => 'streetName',
        'website'       => 'url', 
        'url'           => 'url',
        'ip'            => 'ipv4',
        'description'   => 'text'
    ),

);