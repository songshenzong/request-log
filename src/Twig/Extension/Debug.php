<?php namespace Songshenzong\Log\Twig\Extension;

use Illuminate\Foundation\Application;
use Twig_Environment;
use Twig_Extension;
use Twig_SimpleFunction;
use const false;

/**
 * Access Laravels auth class in your Twig templates.
 */
class Debug extends Twig_Extension
{
    /**
     * @var \Songshenzong\Log\LaravelDebugbar
     */
    protected $debugbar;

    /**
     * Create a new auth extension.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        if ($app->bound('songshenzongLog')) {
            $this->debugbar = $app['songshenzongLog'];
        } else {
            $this->debugbar = null;
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Laravel_Debugbar_Debug';
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction(
                'debug',
                [$this, 'debug'],
                ['needs_context' => true, 'needs_environment' => true]
            ),
        ];
    }

    /**
     * Based on Twig_Extension_Debug / twig_var_dump
     * (c) 2011 Fabien Potencier
     *
     * @param Twig_Environment $env
     * @param                  $context
     *
     * @return bool
     * @throws \Songshenzong\Log\DebugBarException
     */
    public function debug(Twig_Environment $env, $context)
    {
        if (!$env->isDebug() || !$this->debugbar) {
            return;
        }

        $count = func_num_args();
        if (2 === $count) {
            $data = [];
            foreach ($context as $key => $value) {
                if (is_object($value)) {
                    if (method_exists($value, 'toArray')) {
                        $data[$key] = $value->toArray();
                    } else {
                        $data[$key] = "Object (" . get_class($value) . ")";
                    }
                } else {
                    $data[$key] = $value;
                }
            }
            $this->debugbar->addMessage($data);
        } else {
            for ($i = 2; $i < $count; $i++) {
                $this->debugbar->addMessage(func_get_arg($i));
            }
        }

        return false;
    }
}
