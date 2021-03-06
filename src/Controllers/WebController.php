<?php

namespace Songshenzong\Log\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Response;

/**
 * Class WebController
 *
 * @package Songshenzong\Log\Controllers
 */
class WebController extends BaseController
{

    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    public $app;


    /**
     * CurrentController constructor.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }


    /**
     * Index Page
     */
    public function index()
    {
        $file_path = __DIR__ . '/index.html';
        $file      = file_get_contents($file_path);
        return new Response($file, 200);
    }

    /**
     * Login Page
     */
    public function login()
    {
        $file_path = __DIR__ . '/Login.html';
        $file      = file_get_contents($file_path);
        return new Response($file, 200);
    }
}
