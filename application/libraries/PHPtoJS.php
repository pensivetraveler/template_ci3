<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class PHPtoJS
{

    /**
     * The namespace to nest JS vars under.
     *
     * @var string
     */
    protected $namespace;

    /**
     * variables to the views.
     *
     * @var jsToview
     */
    protected $jsToView;

    /**
     * All transformable types.
     *
     * @var array
     */
    protected $types = [
        'String',
        'Array',
        'Object',
        'Numeric',
        'Boolean',
        'Null'
    ];

    /**
     * Create a new JS transformer instance.
     *
     * @param string     $namespace
     */
    function __construct($namespace = 'window')
    {
        $this->namespace = is_array($namespace) && !empty($namespace['namespace']) ? $namespace['namespace'] : $namespace;
    }

    /**
     * Store the given array of variables to the jsToview.
     *
     * @param array $variables
     */
    public function put(array $variables)
    {
        // First, we have to translate the variables
        // to something JS-friendly.
        $this->jsToView = $this->buildJavaScriptSyntax($variables);
    }

    /**
     * returns the javascript variables.
     *
     * @return string
     */
    public function getJsVars()
    {
        return '<script type="text/javascript"'.csp_nonce_attr().'>'.$this->jsToView.'</script>';
    }

    /**
     * Translate the array of PHP vars to
     * the expected JavaScript syntax.
     *
     * @param  array $vars
     * @return array
     */
    public function buildJavaScriptSyntax(array $vars)
    {
        $js = $this->buildNamespaceDeclaration();

        foreach ($vars as $key => $value) {
            $js .= $this->buildVariableInitialization($key, $value);
        }

        return $js;
    }

    /**
     * Create the namespace that all
     * vars will be nested under.
     *
     * @return string
     */
    protected function buildNamespaceDeclaration()
    {
        return "window.{$this->namespace} = window.{$this->namespace} || {};";
    }

    /**
     * Translate a single PHP var to JS.
     *
     * @param  string $key
     * @param  string $value
     * @return string
     */
    protected function buildVariableInitialization($key, $value)
    {
        return "{$this->namespace}.{$key} = {$this->optimizeValueForJavaScript($value)};";
    }

    /**
     * Format a value for JavaScript.
     *
     * @param  string $value
     * @throws \Exception
     * @return mixed
     */
    protected function optimizeValueForJavaScript($value)
    {
        // For every transformable type, let's see if
        // it needs to be converted for JS-used.
        foreach ($this->types as $transformer) {
            $js = $this->{"transform{$transformer}"}($value);

            if (!is_null($js)) {
                return $js;
            }
        }
    }

    /**
     * Transform a string.
     *
     * @param  string $value
     * @return string
     */
    protected function transformString($value) {
        if (is_string($value)) {
            return "'{$this->escape($value)}'";
        }
    }

    /**
     * Transform an array.
     *
     * @param  array  $value
     * @return string
     */
    protected function transformArray($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_HEX_TAG);
        }
    }

    /**
     * Transform a numeric value.
     *
     * @param  mixed $value
     * @return mixed
     */
    protected function transformNumeric($value)
    {
        if (is_numeric($value)) {
            return $value;
        }
    }

    /**
     * Transform a boolean.
     *
     * @param  boolean $value
     * @return string
     */
    protected function transformBoolean($value)
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
    }

    /**
     * Transform an object.
     *
     * @param  object $value
     * @return string
     * @throws \Exception
     */
    protected function transformObject($value)
    {
        if (is_object($value)) {
            // If a toJson() method exists, we'll assume that
            // the object can cast itself automatically.
            if (method_exists($value, 'toJson')) {
                return $value;
            }

            // Otherwise, if the object doesn't even have
            // a toString method, we can't proceed.
            if (!method_exists($value, '__toString')) {
                throw new Exception('The provided object needs a __toString() method.');
            }

            return "'{$this->escape((string)$value)}'";
        }
    }

    /**
     * Transform "null."
     *
     * @param  mixed $value
     * @return string
     */
    protected function transformNull($value)
    {
        if (is_null($value)) {
            return 'null';
        }
    }

    /**
     * Escape any single quotes.
     *
     * @param  string $value
     * @return string
     */
    protected function escape($value)
    {
        // <script> 안에 그대로 출력되므로 태그·줄바꿈이 문자열을 벗어나지 않게 한다
        return str_replace(
            ["\\", "'", "<", ">", "\r", "\n", "\u{2028}", "\u{2029}"],
            ["\\\\", "\'", "\\x3C", "\\x3E", "\\r", "\\n", "\\u2028", "\\u2029"],
            $value
        );
    }

    /**
     * js 로 보내줄 변수리스트를 초기화하지 말고 append
     *
     * @param array $variables
     * @return void
     */
    public function append(array $variables)
    {
        $this->jsToView = $this->appendJavaScriptSyntax($variables);
    }


    /**
     * 변수를 추가하는 경우 사용하는 메서드
     *
     * @param array $vars
     * @return jsToview|string
     */
    public function appendJavaScriptSyntax(array $vars)
    {
        $js = $this->jsToView;

        foreach ($vars as $key => $value) {
            $js .= $this->buildVariableInitialization($key, $value);
        }

        return $js;
    }
}