<?php defined('SYSPATH') or die('No direct access allowed.');

return array (
  'sha_hash' => getenv('BEANS_SHA_HASH'),
  'sha_salt' => getenv('BEANS_SHA_SALT'),
  'cookie_salt' => getenv('BEANS_COOKIE_SALT'),
  'modules' =>
  array (
    'encrypt' =>
    array (
      'default' =>
      array (
        'key' => getenv('BEANS_ENCRYPT_KEY'),
        'cipher' => 'rijndael-128',
        'mode' => 'nofb',
      ),
    ),
    'database' =>
    array (
      'default' =>
      array (
        'type' => 'mysql',
        'connection' =>
        array (
          'hostname' => getenv('BEANS_DB_HOST'),
          'database' => getenv('BEANS_DB_NAME'),
          'username' => getenv('BEANS_DB_USERNAME'),
          'password' => getenv('BEANS_DB_PASSWORD'),
          'persistent' => false,
        ),
        'table_prefix' => '',
        'charset' => 'utf8',
        'caching' => true,
        'profiling' => false,
      ),
    ),
    'email' =>
    array (
      'driver' => 'smtp',
      'options' =>
      array (
        'hostname' => getenv('BEANS_EMAIL_HOST'),
        'port' => getenv('BEANS_EMAIL_PORT'),
        'username' => getenv('BEANS_EMAIL_USERNAME'),
        'password' => getenv('BEANS_EMAIL_PASSWORD'),
        'encryption' => getenv('BEANS_EMAIL_ENCRYPTION'),
      ),
    ),
  ),
);
