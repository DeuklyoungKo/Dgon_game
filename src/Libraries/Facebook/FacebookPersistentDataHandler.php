<?php
namespace App\Libraries\Facebook;

use Facebook\PersistentData\PersistentDataInterface;
use Symfony\Component\HttpFoundation\Session\Session;


class FacebookPersistentDataHandler implements PersistentDataInterface
{

    private $seesion;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function get($key)
    {

        return $this->session->get('facebook.' . $key);
//        return Session::get('facebook.' . $key);
    }

    public function set($key, $value)
    {

        // set and get session attributes
        $this->session->set('facebook.' . $key, $value);

//        Session::put('facebook.' . $key, $value);
    }

}