<?php
/* Eden_Class */
if (!class_exists('Eden_Class')) {
    class Eden_Class
    {
        const DEBUG = 'DEBUG %s:';
        const INSTANCE = 0;
        private static $_instances = array();
        public static function i()
        {
            if (static::INSTANCE === 1) {
                return self::_getSingleton();
            }
            return self::_getMultiple();
        }
        public function __call($name, $args)
        {
            if (preg_match("/^[A-Z]/", $name)) {
                try {
                    return Eden_Route::i()->getClass($name, $args);
                }
                catch (Eden_Route_Error $e) {
                }
            }
            try {
                return Eden_Route::i()->getMethod()->call($this, $name, $args);
            }
            catch (Eden_Route_Error $e) {
                Eden_Error::i($e->getMessage())->trigger();
            }
        }
        public function __invoke()
        {
            if (func_num_args() == 0) {
                return $this;
            }
            $args = func_get_args();
            if (is_array($args[0])) {
                $args = $args[0];
            }
            $class = array_shift($args);
            if (strpos('Eden_', $class) !== 0) {
                $class = 'Eden_' . $class;
            }
            try {
                return Eden_Route::i()->getClass($class, $args);
            }
            catch (Eden_Route_Error $e) {
                Eden_Error::i($e->getMessage())->trigger();
            }
        }
        public function __toString()
        {
            return get_class($this);
        }
        public function callThis($method, array $args = array())
        {
            Eden_Error::i()->argument(1, 'string');
            return Eden_Route::i()->getMethod($this, $method, $args);
        }
        public function debug($variable = NULL, $next = NULL)
        {
            $class = get_class($this);
            if (is_null($variable)) {
                Eden_Debug::i()->output(sprintf(self::DEBUG, $class))->output($this);
                return $this;
            }
            if ($variable === true) {
                return Eden_Debug::i()->next($this, $next);
            }
            if (!is_string($variable)) {
                Eden_Debug::i()->output(Eden_Error::DEBUG_NOT_STRING);
                return $this;
            }
            if (isset($this->$variable)) {
                Eden_Debug::i()->output(sprintf(self::DEBUG, $class . '->' . $variable))->output($this->$variable);
                return $this;
            }
            $private = '_' . $variable;
            if (isset($this->$private)) {
                Eden_Debug::i()->output(sprintf(self::DEBUG, $class . '->' . $private))->output($this->$private);
                return $this;
            }
            Eden_Debug::i()->output(sprintf(Eden_Error::DEBUG_NOT_PROPERTY, $variable, $class));
            return $this;
        }
        public function each($callback)
        {
            Eden_Error::i()->argument(1, 'callable');
            return Eden_Loop::i()->iterate($this, $callback);
        }
        public function routeThis($route)
        {
            Eden_Error::i()->argument(1, 'string');
            if (func_num_args() == 1) {
                Eden_Route::i()->getClass()->route($route, $this);
                return $this;
            }
            Eden_Error::i()->argument(2, 'string', 'object');
            $args        = func_get_args();
            $source      = array_shift($args);
            $class       = array_shift($args);
            $destination = NULL;
            if (count($args)) {
                $destination = array_shift($args);
            }
            Eden_Route::i()->getMethod()->route($this, $source, $class, $destination);
            return $this;
        }
        public function when($isTrue, $lines = 0)
        {
            if ($isTrue) {
                return $this;
            }
            return Eden_When::i($this, $lines);
        }
        protected static function _getMultiple($class = NULL)
        {
            if (is_null($class) && function_exists('get_called_class')) {
                $class = get_called_class();
            }
            $class = Eden_Route::i()->getClass()->getRoute($class);
            return self::_getInstance($class);
        }
        protected static function _getSingleton($class = NULL)
        {
            if (is_null($class) && function_exists('get_called_class')) {
                $class = get_called_class();
            }
            $class = Eden_Route::i()->getClass()->getRoute($class);
            if (!isset(self::$_instances[$class])) {
                self::$_instances[$class] = self::_getInstance($class);
            }
            return self::$_instances[$class];
        }
        private static function _getInstance($class)
        {
            $trace = debug_backtrace();
            $args  = array();
            if (isset($trace[1]['args']) && count($trace[1]['args']) > 1) {
                $args = $trace[1]['args'];
                array_shift($args);
            } else if (isset($trace[2]['args']) && count($trace[2]['args']) > 0) {
                $args = $trace[2]['args'];
            }
            if (count($args) === 0 || !method_exists($class, '__construct')) {
                return new $class;
            }
            $reflect = new ReflectionClass($class);
            try {
                return $reflect->newInstanceArgs($args);
            }
            catch (Reflection_Exception $e) {
                Eden_Error::i()->setMessage(Eden_Error::REFLECTION_ERROR)->addVariable($class)->addVariable('new')->trigger();
            }
        }
    }
}
/* Eden_Error */
if (!class_exists('Eden_Error')) {
    class Eden_Error extends Exception
    {
        const REFLECTION_ERROR = 'Error creating Reflection Class: %s,Method: %s.';
        const INVALID_ARGUMENT = 'Argument %d in %s() was expecting %s,however %s was given.';
        const ARGUMENT = 'ARGUMENT';
        const LOGIC = 'LOGIC';
        const GENERAL = 'GENERAL';
        const CRITICAL = 'CRITICAL';
        const WARNING = 'WARNING';
        const ERROR = 'ERROR';
        const DEBUG = 'DEBUG';
        const INFORMATION = 'INFORMATION';
        const DEBUG_NOT_STRING = 'Debug was expecting a string';
        const DEBUG_NOT_PROPERTY = 'Debug: %s is not a property of %s';
        protected $_reporter = NULL;
        protected $_type = NULL;
        protected $_level = NULL;
        protected $_offset = 1;
        protected $_variables = array();
        protected $_trace = array();
        protected static $_argumentTest = true;
        public static function i($message = NULL, $code = 0)
        {
            $class = __CLASS__;
            return new $class($message, $code);
        }
        public function __construct($message = NULL, $code = 0)
        {
            $this->_type  = self::LOGIC;
            $this->_level = self::ERROR;
            parent::__construct($message, $code);
        }
        public function addVariable($variable)
        {
            $this->_variables[] = $variable;
            return $this;
        }
        public function argument($index, $types)
        {
            if (!self::$_argumentTest) {
                return $this;
            }
            $trace = debug_backtrace();
            $trace = $trace[1];
            $types = func_get_args();
            $index = array_shift($types) - 1;
            if ($index < 0) {
                $index = 0;
            }
            if ($index >= count($trace['args'])) {
                return $this;
            }
            $argument = $trace['args'][$index];
            foreach ($types as $i => $type) {
                if ($this->_isValid($type, $argument)) {
                    return $this;
                }
            }
            $method = $trace['function'];
            if (isset($trace['class'])) {
                $method = $trace['class'] . '->' . $method;
            }
            $type = $this->_getType($argument);
            $this->setMessage(self::INVALID_ARGUMENT)->addVariable($index + 1)->addVariable($method)->addVariable(implode(' or ', $types))->addVariable($type)->setTypeLogic()->setTraceOffset(1)->trigger();
        }
        public function getLevel()
        {
            return $this->_level;
        }
        public function getRawTrace()
        {
            return $this->_trace;
        }
        public function getReporter()
        {
            return $this->_reporter;
        }
        public function getTraceOffset()
        {
            return $this->_offset;
        }
        public function getType()
        {
            return $this->_type;
        }
        public function getVariables()
        {
            return $this->_variables;
        }
        public function noArgTest()
        {
            self::$_argumentTest = false;
            return $this;
        }
        public function setLevel($level)
        {
            $this->_level = $level;
            return $this;
        }
        public function setLevelDebug()
        {
            return $this->setLevel(self::DEBUG);
        }
        public function setLevelError()
        {
            return $this->setLevel(self::WARNING);
        }
        public function setLevelInformation()
        {
            return $this->setLevel(self::INFORMATION);
        }
        public function setLevelWarning()
        {
            return $this->setLevel(self::WARNING);
        }
        public function setMessage($message)
        {
            $this->message = $message;
            return $this;
        }
        public function setTraceOffset($offset)
        {
            $this->_offset = $offset;
            return $this;
        }
        public function setType($type)
        {
            $this->_type = $type;
            return $this;
        }
        public function setTypeArgument()
        {
            return $this->setType(self::ARGUMENT);
        }
        public function setTypeCritical()
        {
            return $this->setType(self::CRITICAL);
        }
        public function setTypeGeneral()
        {
            return $this->setType(self::GENERAL);
        }
        public function setTypeLogic()
        {
            return $this->setType(self::CRITICAL);
        }
        public function trigger()
        {
            $this->_trace    = debug_backtrace();
            $this->_reporter = get_class($this);
            if (isset($this->_trace[$this->_offset]['class'])) {
                $this->_reporter = $this->_trace[$this->_offset]['class'];
            }
            if (isset($this->_trace[$this->_offset]['file'])) {
                $this->file = $this->_trace[$this->_offset]['file'];
            }
            if (isset($this->_trace[$this->_offset]['line'])) {
                $this->line = $this->_trace[$this->_offset]['line'];
            }
            if (!empty($this->_variables)) {
                $this->message    = vsprintf($this->message, $this->_variables);
                $this->_variables = array();
            }
            throw $this;
        }
        public function vargument($method, $args, $index, $types)
        {
            if (!self::$_argumentTest) {
                return $this;
            }
            $trace  = debug_backtrace();
            $trace  = $trace[1];
            $types  = func_get_args();
            $method = array_shift($types);
            $args   = array_shift($types);
            $index  = array_shift($types) - 1;
            if ($index < 0) {
                $index = 0;
            }
            if ($index >= count($args)) {
                return $this;
            }
            $argument = $args[$index];
            foreach ($types as $i => $type) {
                if ($this->_isValid($type, $argument)) {
                    return $this;
                }
            }
            $method = $trace['class'] . '->' . $method;
            $type   = $this->_getType($argument);
            $this->setMessage(self::INVALID_ARGUMENT)->addVariable($index + 1)->addVariable($method)->addVariable(implode(' or ', $types))->addVariable($type)->setTypeLogic()->setTraceOffset(1)->trigger();
        }
        protected function _isCreditCard($value)
        {
            return preg_match('/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]' . '{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-' . '5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})$/', $value);
        }
        protected function _isEmail($value)
        {
            return preg_match('/^(?:(?:(?:[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]|\x5c(?=[@,"\[\]' . '\x5c\x00-\x20\x7f-\xff]))(?:[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]|(?<=\x5c)[@,"\[\]' . '\x5c\x00-\x20\x7f-\xff]|\x5c(?=[@,"\[\]\x5c\x00-\x20\x7f-\xff])|\.(?=[^\.])){1,62' . '}(?:[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]|(?<=\x5c)[@,"\[\]\x5c\x00-\x20\x7f-\xff])|' . '[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]{1,2})|"(?:[^"]|(?<=\x5c)"){1,62}")@(?:(?!.{64})' . '(?:[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.?|[a-zA-Z0-9]\.?)+\.(?:xn--[a-zA-Z0-9]' . '+|[a-zA-Z]{2,6})|\[(?:[0-1]?\d?\d|2[0-4]\d|25[0-5])(?:\.(?:[0-1]?\d?\d|2[0-4]\d|25' . '[0-5])){3}\])$/', $value);
        }
        protected function _isHex($value)
        {
            return preg_match("/^[0-9a-fA-F]{6}$/", $value);
        }
        protected function _isHtml($value)
        {
            return preg_match("/<\/?\w+((\s+(\w|\w[\w-]*\w)(\s*=\s*" . "(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)\/?>/i", $value);
        }
        protected function _isUrl($value)
        {
            return preg_match('/^(http|https|ftp):\/\/([A-Z0-9][A-Z0' . '-9_-]*(?:.[A-Z0-9][A-Z0-9_-]*)+):?(d+)?\/?/i', $value);
        }
        public function _alphaNum($value)
        {
            return preg_match('/^[a-zA-Z0-9]+$/', $value);
        }
        public function _alphaNumScore($value)
        {
            return preg_match('/^[a-zA-Z0-9_]+$/', $value);
        }
        public function _alphaNumHyphen($value)
        {
            return preg_match('/^[a-zA-Z0-9-]+$/', $value);
        }
        public function _alphaNumLine($value)
        {
            return preg_match('/^[a-zA-Z0-9-_]+$/', $value);
        }
        protected function _isValid($type, $data)
        {
            $type = $this->_getTypeName($type);
            switch ($type) {
                case 'number':
                    return is_numeric($data);
                case 'int':
                    return is_numeric($data) && strpos((string) $data, '.') === false;
                case 'float':
                    return is_numeric($data) && strpos((string) $data, '.') !== false;
                case 'file':
                    return is_string($data) && file_exists($data);
                case 'folder':
                    return is_string($data) && is_dir($data);
                case 'email':
                    return is_string($data) && $this->_isEmail($data);
                case 'url':
                    return is_string($data) && $this->_isUrl($data);
                case 'html':
                    return is_string($data) && $this->_isHtml($data);
                case 'cc':
                    return (is_string($data) || is_int($data)) && $this->_isCreditCard($data);
                case 'hex':
                    return is_string($data) && $this->_isHex($data);
                case 'alphanum':
                    return is_string($data) && $this->_alphaNum($data);
                case 'alphanumscore':
                    return is_string($data) && $this->_alphaNumScore($data);
                case 'alphanumhyphen':
                    return is_string($data) && $this->_alphaNumHyphen($data);
                case 'alphanumline':
                    return is_string($data) && $this->_alphaNumLine($data);
                default:
                    break;
            }
            $method = 'is_' . $type;
            if (function_exists($method)) {
                return $method($data);
            }
            if (class_exists($type)) {
                return $data instanceof $type;
            }
            return true;
        }
        private function _getType($data)
        {
            if (is_string($data)) {
                return "'" . $data . "'";
            }
            if (is_numeric($data)) {
                return $data;
            }
            if (is_array($data)) {
                return 'Array';
            }
            if (is_bool($data)) {
                return $data ? 'true' : 'false';
            }
            if (is_object($data)) {
                return get_class($data);
            }
            if (is_null($data)) {
                return 'null';
            }
            return 'unknown';
        }
        private function _getTypeName($data)
        {
            if (is_string($data)) {
                return $data;
            }
            if (is_numeric($data)) {
                return 'numeric';
            }
            if (is_array($data)) {
                return 'array';
            }
            if (is_bool($data)) {
                return 'bool';
            }
            if (is_object($data)) {
                return get_class($data);
            }
            if (is_null($data)) {
                return 'null';
            }
        }
    }
}
/* Eden_Event */
if (!class_exists('Eden_Event')) {
    class Eden_Event extends Eden_Class
    {
        protected $_observers = array();
        public static function i()
        {
            return self::_getSingleton(__CLASS__);
        }
        public function listen($event, $instance, $method = NULL, $important = false)
        {
            $error = Eden_Event_Error::i()->argument(1, 'string')->argument(2, 'object', 'string', 'callable')->argument(3, 'null', 'string', 'bool')->argument(4, 'bool');
            if (is_bool($method)) {
                $important = $method;
                $method    = NULL;
            }
            $id       = $this->_getId($instance, $method);
            $callable = $this->_getCallable($instance, $method);
            $observer = array(
                $event,
                $id,
                $callable
            );
            if ($important) {
                array_unshift($this->_observers, $observer);
                return $this;
            }
            $this->_observers[] = $observer;
            return $this;
        }
        public function trigger($event = NULL)
        {
            Eden_Event_Error::i()->argument(1, 'string', 'null');
            if (is_null($event)) {
                $trace = debug_backtrace();
                $event = $trace[1]['function'];
            }
            $args  = func_get_args();
            $event = array_shift($args);
            array_unshift($args, $this, $event);
            foreach ($this->_observers as $observer) {
                if ($event == $observer[0] && call_user_func_array($observer[2], $args) === false) {
                    break;
                }
            }
            return $this;
        }
        public function unlisten($event, $instance = NULL, $method = NULL)
        {
            Eden_Event_Error::i()->argument(1, 'string', 'null')->argument(2, 'object', 'string', 'null')->argument(3, 'string', 'null');
            if (is_null($event) && is_null($instance)) {
                $this->_observers = array();
                return $this;
            }
            $id = $this->_getId($instance, $method);
            if ($id === false) {
                return false;
            }
            foreach ($this->_observers as $i => $observer) {
                if (!is_null($event) && $event != $observer[0]) {
                    continue;
                }
                if ($id == $observer[1] && is_array($observer[2]) && $method != $observer[2][1]) {
                    continue;
                }
                if ($id != $observer[1]) {
                    continue;
                }
                unset($this->_observers[$i]);
            }
            return $this;
        }
        protected function _getCallable($instance, $method = NULL)
        {
            if (class_exists('Closure') && $instance instanceof Closure) {
                return $instance;
            }
            if (is_object($instance)) {
                return array(
                    $instance,
                    $method
                );
            }
            if (is_string($instance) && is_string($method)) {
                return $instance . '::' . $method;
            }
            if (is_string($instance)) {
                return $instance;
            }
            return NULL;
        }
        protected function _getId($instance, $method = NULL)
        {
            if (is_object($instance)) {
                return spl_object_hash($instance);
            }
            if (is_string($instance) && is_string($method)) {
                return $instance . '::' . $method;
            }
            if (is_string($instance)) {
                return $instance;
            }
            return false;
        }
    }
    class Eden_Event_Error extends Eden_Error
    {
        const NO_METHOD = 'Instance %s was passed but,no callable method was passed in listen().';
        public static function i($message = NULL, $code = 0)
        {
            $class = __CLASS__;
            return new $class($message, $code);
        }
    }
}
/* Eden_Error_Event */
if (!class_exists('Eden_Error_Event')) {
    class Eden_Error_Event extends Eden_Event
    {
        const PHP = 'PHP';
        const UNKNOWN = 'UNKNOWN';
        const WARNING = 'WARNING';
        const ERROR = 'ERROR';
        public static function i()
        {
            return self::_getSingleton(__CLASS__);
        }
        public function errorHandler($errno, $errstr, $errfile, $errline)
        {
            switch ($errno) {
                case E_NOTICE:
                case E_USER_NOTICE:
                case E_WARNING:
                case E_USER_WARNING:
                    $level = self::WARNING;
                    break;
                case E_ERROR:
                case E_USER_ERROR:
                default:
                    $level = self::ERROR;
                    break;
            }
            $type  = self::PHP;
            $trace = debug_backtrace();
            $class = self::UNKNOWN;
            if (count($trace) > 1) {
                $class = $trace[1]['function'] . '()';
                if (isset($trace[1]['class'])) {
                    $class = $trace[1]['class'] . '->' . $class;
                }
            }
            $this->trigger('error', $type, $level, $class, $errfile, $errline, $errstr, $trace, 1);
            return true;
        }
        public function exceptionHandler(Exception $e)
        {
            $type     = Eden_Error::LOGIC;
            $level    = Eden_Error::ERROR;
            $offset   = 1;
            $reporter = get_class($e);
            $trace    = $e->getTrace();
            $message  = $e->getMessage();
            if ($e instanceof Eden_Error) {
                $trace    = $e->getRawTrace();
                $type     = $e->getType();
                $level    = $e->getLevel();
                $offset   = $e->getTraceOffset();
                $reporter = $e->getReporter();
            }
            $this->trigger('exception', $type, $level, $reporter, $e->getFile(), $e->getLine(), $message, $trace, $offset);
        }
        public function releaseErrorHandler()
        {
            restore_error_handler();
            return $this;
        }
        public function releaseExceptionHandler()
        {
            restore_exception_handler();
            return $this;
        }
        public function setErrorHandler()
        {
            set_error_handler(array(
                $this,
                'errorHandler'
            ));
            return $this;
        }
        public function setExceptionHandler()
        {
            set_exception_handler(array(
                $this,
                'exceptionHandler'
            ));
            return $this;
        }
        public function setReporting($type)
        {
            error_reporting($type);
            return $this;
        }
    }
}
/* Eden_Route_Error */
if (!class_exists('Eden_Route_Error')) {
    class Eden_Route_Error extends Eden_Error
    {
        const CLASS_NOT_EXISTS = 'Invalid class call: %s->%s().Class does not exist.';
        const METHOD_NOT_EXISTS = 'Invalid class call: %s->%s().Method does not exist.';
        const STATIC_ERROR = 'Invalid class call: %s::%s().';
        const FUNCTION_ERROR = 'Invalid function run: %s().';
        public static function i($message = NULL, $code = 0)
        {
            $class = __CLASS__;
            return new $class($message, $code);
        }
    }
}
/* Eden_Route_Class */
if (!class_exists('Eden_Route_Class')) {
    class Eden_Route_Class extends Eden_Class
    {
        protected static $_instance = NULL;
        protected $_route = array();
        public static function i()
        {
            $class = __CLASS__;
            if (is_null(self::$_instance)) {
                self::$_instance = new $class();
            }
            return self::$_instance;
        }
        public function call($class)
        {
            Eden_Route_Error::i()->argument(1, 'string');
            $args  = func_get_args();
            $class = array_shift($args);
            return $this->callArray($class, $args);
        }
        public function callArray($class, array $args = array())
        {
            Eden_Route_Error::i()->argument(1, 'string');
            $route = $this->getRoute($class);
            if (is_object($route)) {
                return $route;
            }
            $reflect = new ReflectionClass($route);
            if (method_exists($route, 'i')) {
                $declared = $reflect->getMethod('i')->getDeclaringClass()->getName();
                return Eden_Route_Method::i()->callStatic($class, 'i', $args);
            }
            return $reflect->newInstanceArgs($args);
        }
        public function getRoute($route)
        {
            Eden_Route_Error::i()->argument(1, 'string');
            if ($this->isRoute($route)) {
                return $this->_route[strtolower($route)];
            }
            return $route;
        }
        public function getRoutes()
        {
            return $this->_route;
        }
        public function isRoute($route)
        {
            return isset($this->_route[strtolower($route)]);
        }
        public function release($route)
        {
            if ($this->isRoute($route)) {
                unset($this->_route[strtolower($route)]);
            }
            return $this;
        }
        public function route($route, $class)
        {
            Eden_Route_Error::i()->argument(1, 'string', 'object')->argument(2, 'string', 'object');
            if (is_object($route)) {
                $route = get_class($route);
            }
            if (is_string($class)) {
                $class = $this->getRoute($class);
            }
            $this->_route[strtolower($route)] = $class;
            return $this;
        }
    }
}
/* Eden_Route_Method */
if (!class_exists('Eden_Route_Method')) {
    class Eden_Route_Method extends Eden_Class
    {
        protected static $_instance = NULL;
        protected $_route = array();
        public static function i()
        {
            $class = __CLASS__;
            if (is_null(self::$_instance)) {
                self::$_instance = new $class();
            }
            return self::$_instance;
        }
        public function call($class, $method, array $args = array())
        {
            Eden_Route_Error::i()->argument(1, 'string', 'object')->argument(2, 'string');
            $instance = NULL;
            if (is_object($class)) {
                $instance = $class;
                $class    = get_class($class);
            }
            $classRoute    = Eden_Route_Class::i();
            $isClassRoute  = $classRoute->isRoute($class);
            $isMethodRoute = $this->isRoute($class, $method);
            list($class, $method) = $this->getRoute($class, $method);
            if (!is_object($class) && !class_exists($class)) {
                Eden_Route_Error::i()->setMessage(Eden_Route_Error::CLASS_NOT_EXISTS)->addVariable($class)->addVariable($method)->trigger();
            }
            if (!$isClassRoute && !$isMethodRoute && !method_exists($class, $method)) {
                Eden_Route_Error::i()->setMessage(Eden_Route_Error::METHOD_NOT_EXISTS)->addVariable($class)->addVariable($method)->trigger();
            }
            if ($isClassRoute || !$instance) {
                $instance = $classRoute->call($class);
            }
            return call_user_func_array(array(
                &$instance,
                $method
            ), $args);
        }
        public function callStatic($class, $method, array $args = array())
        {
            Eden_Route_Error::i()->argument(1, 'string', 'object')->argument(2, 'string');
            if (is_object($class)) {
                $class = get_class($class);
            }
            $isClassRoute  = Eden_Route_Class::i()->isRoute($class);
            $isMethodRoute = $this->isRoute($class, $method);
            list($class, $method) = $this->getRoute($class, $method);
            if (!is_object($class) && !class_exists($class)) {
                Eden_Route_Error::i()->setMessage(Eden_Route_Error::CLASS_NOT_EXISTS)->addVariable($class)->addVariable($method)->trigger();
            }
            if (!$isClassRoute && !$isMethodRoute && !method_exists($class, $method)) {
                Eden_Route_Error::i()->setMessage(Eden_Route_Error::METHOD_NOT_EXISTS)->addVariable($class)->addVariable($method)->trigger();
            }
            if (is_object($class)) {
                $class = get_class($class);
            }
            return call_user_func_array($class . '::' . $method, $args);
        }
        public function getRoute($class, $method)
        {
            Eden_Route_Error::i()->argument(1, 'string')->argument(2, 'string');
            if ($this->isRoute(NULL, $method)) {
                return $this->_route[NULL][strtolower($method)];
            }
            $class = Eden_Route_Class::i()->getRoute($class);
            if ($this->isRoute($class, $method)) {
                return $this->_route[strtolower($class)][strtolower($method)];
            }
            return array(
                $class,
                $method
            );
        }
        public function getRoutes()
        {
            return $this->_route;
        }
        public function isRoute($class, $method)
        {
            if (is_string($class)) {
                $class = strtolower($class);
            }
            return isset($this->_route[$class][strtolower($method)]);
        }
        public function release($class, $method)
        {
            if ($this->isRoute($class, $method)) {
                unset($this->_route[strtolower($class)][strtolower($method)]);
            }
            return $this;
        }
        public function route($source, $alias, $class, $method = NULL)
        {
            Eden_Route_Error::i()->argument(1, 'string', 'object', 'null')->argument(2, 'string')->argument(3, 'string', 'object')->argument(4, 'string');
            if (is_object($source)) {
                $source = get_class($source);
            }
            if (!is_string($method)) {
                $method = $alias;
            }
            $route = Eden_Route_Class::i();
            if (!is_null($source)) {
                $source = $route->getRoute($source);
                $source = strtolower($source);
            }
            if (is_string($class)) {
                $class = $route->getRoute($class);
            }
            $this->_route[$source][strtolower($alias)] = array(
                $class,
                $method
            );
            return $this;
        }
    }
}
/* Eden_Route_Function */
if (!class_exists('Eden_Route_Function')) {
    class Eden_Route_Function extends Eden_Class
    {
        protected static $_instance = NULL;
        protected $_route = array();
        public static function i()
        {
            $class = __CLASS__;
            if (is_null(self::$_instance)) {
                self::$_instance = new $class();
            }
            return self::$_instance;
        }
        public function call($function)
        {
            Eden_Route_Error::i()->argument(1, 'string');
            $args     = func_get_args();
            $function = array_shift($args);
            return $this->callArray($function, $args);
        }
        public function callArray($function, array $args = array())
        {
            Eden_Route_Error::i()->argument(1, 'string');
            $function = $this->getRoute($function);
            return call_user_func_array($function, $args);
        }
        public function getRoute($route)
        {
            Eden_Route_Error::i()->argument(1, 'string');
            if ($this->isRoute($route)) {
                return $this->_route[strtolower($route)];
            }
            return $route;
        }
        public function getRoutes()
        {
            return $this->_route;
        }
        public function isRoute($route)
        {
            return isset($this->_route[strtolower($route)]);
        }
        public function release($route)
        {
            if ($this->isRoute($route)) {
                unset($this->_route[strtolower($route)]);
            }
            return $this;
        }
        public function route($route, $function)
        {
            Eden_Route_Error::i()->argument(1, 'string')->argument(2, 'string');
            $function                         = $this->getRoute($function);
            $this->_route[strtolower($route)] = $function;
            return $this;
        }
    }
}
/* Eden_Route */
if (!class_exists('Eden_Route')) {
    class Eden_Route extends Eden_Class
    {
        protected static $_instance = NULL;
        public static function i()
        {
            $class = __CLASS__;
            if (is_null(self::$_instance)) {
                self::$_instance = new $class();
            }
            return self::$_instance;
        }
        public function getClass($class = NULL, array $args = array())
        {
            $route = Eden_Route_Class::i();
            if (is_null($class)) {
                return $route;
            }
            return $route->callArray($class, $args);
        }
        public function getFunction($function = NULL, array $args = array())
        {
            $route = Eden_Route_Function::i();
            if (is_null($function)) {
                return $route;
            }
            return $route->callArray($function, $args);
        }
        public function getMethod($class = NULL, $method = NULL, array $args = array())
        {
            $route = Eden_Route_Method::i();
            if (is_null($class) || is_null($method)) {
                return $route;
            }
            return $route->call($class, $method, $args);
        }
    }
}
/* Eden_When */
if (!class_exists('Eden_When')) {
    class Eden_When extends Eden_Class implements ArrayAccess, Iterator
    {
        protected $_scope = NULL;
        protected $_increment = 1;
        protected $_lines = 0;
        public static function i()
        {
            return self::_getMultiple(__CLASS__);
        }
        public function __construct($scope, $lines = 0)
        {
            $this->_scope = $scope;
            $this->_lines = $lines;
        }
        public function __call($name, $args)
        {
            if ($this->_lines > 0 && $this->_increment == $this->_lines) {
                return $this->_scope;
            }
            $this->_increment++;
            return $this;
        }
        public function current()
        {
            return $this->_scope->current();
        }
        public function key()
        {
            return $this->_scope->key();
        }
        public function next()
        {
            $this->_scope->next();
        }
        public function offsetExists($offset)
        {
            return $this->_scope->offsetExists($offset);
        }
        public function offsetGet($offset)
        {
            return $this->_scope->offsetGet($offset);
        }
        public function offsetSet($offset, $value)
        {
        }
        public function offsetUnset($offset)
        {
        }
        public function rewind()
        {
            $this->_scope->rewind();
        }
        public function valid()
        {
            return $this->_scope->valid();
        }
    }
}
/* Eden_Debug */
if (!class_exists('Eden_Debug')) {
    class Eden_Debug extends Eden_Class
    {
        protected $_scope = NULL;
        protected $_name = NULL;
        public static function i()
        {
            return self::_getSingleton(__CLASS__);
        }
        public function __call($name, $args)
        {
            if (is_null($this->_scope)) {
                return parent::__call($name, $args);
            }
            $results      = $this->_getResults($name, $args);
            $name         = $this->_name;
            $scope        = $this->_scope;
            $this->_name  = NULL;
            $this->_scope = NULL;
            if ($name) {
                $scope->debug($name);
                return $results;
            }
            $class = get_class($scope);
            $this->output(sprintf(self::DEBUG, $class . '->' . $name))->output($results);
            return $results;
        }
        public function next($scope, $name = NULL)
        {
            Eden_Error::i()->argument(1, 'object')->argument(2, 'string', 'null');
            $this->_scope = $scope;
            $this->_name  = $name;
            return $this;
        }
        public function output($variable)
        {
            if ($variable === true) {
                $variable = '*TRUE*';
            } else if ($variable === false) {
                $variable = '*FALSE*';
            } else if (is_null($variable)) {
                $variable = '*NULL*';
            }
            echo '<pre>' . print_r($variable, true) . '</pre>';
            return $this;
        }
        protected function _getResults($name, $args)
        {
            if (method_exists($this->_scope, $name)) {
                return call_user_func_array(array(
                    $this->_scope,
                    $name
                ), $args);
            }
            return $this->_scope->__call($name, $args);
        }
    }
}
/* Eden_Loop */
if (!class_exists('Eden_Loop')) {
    class Eden_Loop extends Eden_Class
    {
        protected $_scope = NULL;
        protected $_callback = NULL;
        public static function i()
        {
            return self::_getSingleton(__CLASS__);
        }
        public function __call($name, $args)
        {
            if (is_null($this->_scope)) {
                return parent::__call($name, $args);
            }
            $results  = $this->_getResults($name, $args);
            $loopable = is_scalar($results) ? array(
                $results
            ) : $results;
            foreach ($loopable as $key => $value) {
                if (call_user_func($this->_callback, $key, $value) === false) {
                    break;
                }
            }
            return $results;
        }
        public function iterate($scope, $callback)
        {
            Eden_Error::i()->argument(1, 'object')->argument(2, 'callable');
            $this->_scope    = $scope;
            $this->_callback = $callback;
            return $this;
        }
        protected function _getResults($name, $args)
        {
            if (method_exists($this->_scope, $name)) {
                return call_user_func_array(array(
                    $this->_scope,
                    $name
                ), $args);
            }
            return $this->_scope->__call($name, $args);
        }
    }
}
/* Eden_Loader */
if (!class_exists('Eden_Loader')) {
    class Eden_Loader extends Eden_Class
    {
        protected $_root = array();
        public static function i()
        {
            return self::_getSingleton(__CLASS__);
        }
        public function __construct($eden = true)
        {
            if ($eden) {
                $this->addRoot(realpath(dirname(__FILE__) . '/..'));
            }
        }
        public function __call($name, $args)
        {
            if (preg_match("/^[A-Z]/", $name)) {
                $this->load($name);
            }
            return parent::__call($name, $args);
        }
        public function addRoot($path)
        {
            array_unshift($this->_root, $path);
            return $this;
        }
        public function handler($class)
        {
            if (!is_string($class)) {
                return false;
            }
            $path = str_replace(array(
                '_',
                '\\'
            ), '/', $class);
            $path = '/' . strtolower($path);
            $path = str_replace('//', '/', $path);
            foreach ($this->_root as $root) {
                $file = $root . $path . '.php';
                if (file_exists($file) && require_once($file)) {
                    return true;
                }
            }
            return false;
        }
        public function load($class)
        {
            if (!class_exists($class)) {
                $this->handler($class);
            }
            return $this;
        }
    }
}
/* Eden_Type */
if (!class_exists('Eden_Type')) {
    class Eden_Type extends Eden_Class
    {
        public static function i($type = NULL)
        {
            if (func_num_args() > 1) {
                $type = func_get_args();
            }
            if (is_array($type)) {
                return Eden_Type_Array::i($type);
            }
            if (is_string($type)) {
                return Eden_Type_String::i($type);
            }
            return self::_getSingleton(__CLASS__);
        }
        public function getArray($array)
        {
            $args = func_get_args();
            if (count($args) > 1 || !is_array($array)) {
                $array = $args;
            }
            return Eden_Type_Array::i($array);
        }
        public function getString($string)
        {
            return Eden_Type_String::i($string);
        }
    }
}
/* Eden_Type_Abstract */
if (!class_exists('Eden_Type_Abstract')) {
    abstract class Eden_Type_Abstract extends Eden_Class
    {
        const PRE = 'pre';
        const POST = 'post';
        const REFERENCE = 'reference';
        protected $_data = NULL;
        protected $_original = NULL;
        public function __call($name, $args)
        {
            $type = $this->_getMethodType($name);
            if (!$type) {
                try {
                    return parent::__call($name, $args);
                }
                catch (Eden_Error $e) {
                    Eden_Type_Error::i($e->getMessage())->trigger();
                }
            }
            switch ($type) {
                case self::PRE:
                    array_unshift($args, $this->_data);
                    break;
                case self::POST:
                    array_push($args, $this->_data);
                    break;
                case self::REFERENCE:
                    call_user_func_array($name, array_merge(array(
                        &$this->_data
                    ), $args));
                    return $this;
            }
            $result = call_user_func_array($name, $args);
            if (is_string($result)) {
                if ($this instanceof Eden_Type_String) {
                    $this->_data = $result;
                    return $this;
                }
                return Eden_Type_String::i($result);
            }
            if (is_array($result)) {
                if ($this instanceof Eden_Type_Array) {
                    $this->_data = $result;
                    return $this;
                }
                return Eden_Type_Array::i($result);
            }
            return $result;
        }
        public function __construct($data)
        {
            $this->_original = $this->_data = $data;
        }
        public function get($modified = true)
        {
            Eden_Type_Error::i()->argument(1, 'bool');
            return $modified ? $this->_data : $this->_original;
        }
        public function revert()
        {
            $this->_data = $this->_original;
            return $this;
        }
        public function set($value)
        {
            $this->_data = $value;
            return $this;
        }
        abstract protected function _getMethodType(&$name);
    }
}
/* Eden_Type_Error */
if (!class_exists('Eden_Type_Error')) {
    class Eden_Type_Error extends Eden_Error
    {
        public static function i($message = NULL, $code = 0)
        {
            $class = __CLASS__;
            return new $class($message, $code);
        }
    }
}
/* Eden_Type_Array */
if (!class_exists('Eden_Type_Array')) {
    class Eden_Type_Array extends Eden_Type_Abstract implements ArrayAccess, Iterator, Serializable, Countable
    {
        protected $_data = array();
        protected $_original = array();
        protected static $_methods = array('array_change_key_case' => self::PRE, 'array_chunk' => self::PRE, 'array_combine' => self::PRE, 'array_count_datas' => self::PRE, 'array_diff_assoc' => self::PRE, 'array_diff_key' => self::PRE, 'array_diff_uassoc' => self::PRE, 'array_diff_ukey' => self::PRE, 'array_diff' => self::PRE, 'array_fill_keys' => self::PRE, 'array_filter' => self::PRE, 'array_flip' => self::PRE, 'array_intersect_assoc' => self::PRE, 'array_intersect_key' => self::PRE, 'array_intersect_uassoc' => self::PRE, 'array_intersect_ukey' => self::PRE, 'array_intersect' => self::PRE, 'array_keys' => self::PRE, 'array_merge_recursive' => self::PRE, 'array_merge' => self::PRE, 'array_pad' => self::PRE, 'array_reverse' => self::PRE, 'array_shift' => self::PRE, 'array_slice' => self::PRE, 'array_splice' => self::PRE, 'array_sum' => self::PRE, 'array_udiff_assoc' => self::PRE, 'array_udiff_uassoc' => self::PRE, 'array_udiff' => self::PRE, 'array_uintersect_assoc' => self::PRE, 'array_uintersect_uassoc' => self::PRE, 'array_uintersect' => self::PRE, 'array_unique' => self::PRE, 'array_datas' => self::PRE, 'count' => self::PRE, 'current' => self::PRE, 'each' => self::PRE, 'end' => self::PRE, 'extract' => self::PRE, 'key' => self::PRE, 'next' => self::PRE, 'prev' => self::PRE, 'sizeof' => self::PRE, 'array_fill' => self::POST, 'array_map' => self::POST, 'array_search' => self::POST, 'compact' => self::POST, 'implode' => self::POST, 'in_array' => self::POST, 'array_unshift' => self::REFERENCE, 'array_walk_recursive' => self::REFERENCE, 'array_walk' => self::REFERENCE, 'arsort' => self::REFERENCE, 'asort' => self::REFERENCE, 'krsort' => self::REFERENCE, 'ksort' => self::REFERENCE, 'natcasesort' => self::REFERENCE, 'natsort' => self::REFERENCE, 'reset' => self::REFERENCE, 'rsort' => self::REFERENCE, 'shuffle' => self::REFERENCE, 'sort' => self::REFERENCE, 'uasort' => self::REFERENCE, 'uksort' => self::REFERENCE, 'usort' => self::REFERENCE, 'array_push' => self::REFERENCE);
        public static function i()
        {
            return self::_getMultiple(__CLASS__);
        }
        public function __call($name, $args)
        {
            if (strpos($name, 'get') === 0) {
                $separator = '_';
                if (isset($args[0]) && is_scalar($args[0])) {
                    $separator = (string) $args[0];
                }
                $key = preg_replace("/([A-Z0-9])/", $separator . "$1", $name);
                $key = strtolower(substr($key, 3 + strlen($separator)));
                if (isset($this->_data[$key])) {
                    return $this->_data[$key];
                }
                return NULL;
            } else if (strpos($name, 'set') === 0) {
                $separator = '_';
                if (isset($args[1]) && is_scalar($args[1])) {
                    $separator = (string) $args[1];
                }
                $key = preg_replace("/([A-Z0-9])/", $separator . "$1", $name);
                $key = strtolower(substr($key, 3 + strlen($separator)));
                $this->__set($key, isset($args[0]) ? $args[0] : NULL);
                return $this;
            }
            try {
                return parent::__call($name, $args);
            }
            catch (Eden_Error $e) {
                Eden_Type_Error::i($e->getMessage())->trigger();
            }
        }
        public function __construct($data = array())
        {
            if (func_num_args() > 1 || !is_array($data)) {
                $data = func_get_args();
            }
            parent::__construct($data);
        }
        public function __set($name, $value)
        {
            $this->_data[$name] = $value;
        }
        public function __toString()
        {
            return json_encode($this->get());
        }
        public function copy($source, $destination)
        {
            $this->_data[$destination] = $this->_data[$source];
            return $this;
        }
        public function count()
        {
            return count($this->_data);
        }
        public function cut($key)
        {
            Eden_Type_Error::i()->argument(1, 'scalar');
            if (!isset($this->_data[$key])) {
                return $this;
            }
            unset($this->_data[$key]);
            $this->_data = array_values($this->_data);
            return $this;
        }
        public function current()
        {
            return current($this->_data);
        }
        public function each($callback)
        {
            Eden_Error::i()->argument(1, 'callable');
            foreach ($this->_data as $key => $value) {
                call_user_func($callback, $key, $value);
            }
            return $this;
        }
        public function isEmpty()
        {
            return empty($this->_data);
        }
        public function key()
        {
            return key($this->_data);
        }
        public function next()
        {
            next($this->_data);
        }
        public function offsetExists($offset)
        {
            return isset($this->_data[$offset]);
        }
        public function offsetGet($offset)
        {
            return isset($this->_data[$offset]) ? $this->_data[$offset] : NULL;
        }
        public function offsetSet($offset, $value)
        {
            if (is_null($offset)) {
                $this->_data[] = $value;
            } else {
                $this->_data[$offset] = $value;
            }
        }
        public function offsetUnset($offset)
        {
            unset($this->_data[$offset]);
        }
        public function paste($after, $value, $key = NULL)
        {
            Eden_Type_Error::i()->argument(1, 'scalar')->argument(3, 'scalar', 'null');
            $list = array();
            foreach ($this->_data as $i => $val) {
                $list[$i] = $val;
                if ($after != $i) {
                    continue;
                }
                if (!is_null($key)) {
                    $list[$key] = $value;
                    continue;
                }
                $list[] = $arrayValue;
            }
            if (is_null($key)) {
                $list = array_values($list);
            }
            $this->_data = $list;
            return $this;
        }
        public function rewind()
        {
            reset($this->_data);
        }
        public function serialize()
        {
            return json_encode($this->_data);
        }
        public function set($value)
        {
            Eden_Type_Error::i()->argument(1, 'array');
            $this->_data = $value;
            return $this;
        }
        public function unserialize($data)
        {
            $this->_data = json_decode($data, true);
            return $this;
        }
        public function valid()
        {
            return isset($this->_data[$this->key()]);
        }
        protected function _getMethodType(&$name)
        {
            if (isset(self::$_methods[$name])) {
                return self::$_methods[$name];
            }
            if (isset(self::$_methods['array_' . $name])) {
                $name = 'array_' . $name;
                return self::$_methods[$name];
            }
            $uncamel = strtolower(preg_replace("/([A-Z])/", "_$1", $name));
            if (isset(self::$_methods[$uncamel])) {
                $name = $uncamel;
                return self::$_methods[$name];
            }
            if (isset(self::$_methods['array_' . $uncamel])) {
                $name = 'array_' . $uncamel;
                return self::$_methods[$name];
            }
            return false;
        }
    }
}
/* Eden_Type_String */
if (!class_exists('Eden_Type_String')) {
    class Eden_Type_String extends Eden_Type_Abstract
    {
        protected static $_methods = array('addslashes' => self::PRE, 'bin2hex' => self::PRE, 'chunk_split' => self::PRE, 'convert_uudecode' => self::PRE, 'convert_uuencode' => self::PRE, 'crypt' => self::PRE, 'html_entity_decode' => self::PRE, 'htmlentities' => self::PRE, 'htmlspecialchars_decode' => self::PRE, 'htmlspecialchars' => self::PRE, 'lcfirst' => self::PRE, 'ltrim' => self::PRE, 'md5' => self::PRE, 'nl2br' => self::PRE, 'quoted_printable_decode' => self::PRE, 'quoted_printable_encode' => self::PRE, 'quotemeta' => self::PRE, 'rtrim' => self::PRE, 'sha1' => self::PRE, 'sprintf' => self::PRE, 'str_pad' => self::PRE, 'str_repeat' => self::PRE, 'str_rot13' => self::PRE, 'str_shuffle' => self::PRE, 'strip_tags' => self::PRE, 'stripcslashes' => self::PRE, 'stripslashes' => self::PRE, 'strpbrk' => self::PRE, 'stristr' => self::PRE, 'strrev' => self::PRE, 'strstr' => self::PRE, 'strtok' => self::PRE, 'strtolower' => self::PRE, 'strtoupper' => self::PRE, 'strtr' => self::PRE, 'substr_replace' => self::PRE, 'substr' => self::PRE, 'trim' => self::PRE, 'ucfirst' => self::PRE, 'ucwords' => self::PRE, 'vsprintf' => self::PRE, 'wordwrap' => self::PRE, 'count_chars' => self::PRE, 'hex2bin' => self::PRE, 'strlen' => self::PRE, 'strpos' => self::PRE, 'substr_compare' => self::PRE, 'substr_count' => self::PRE, 'str_ireplace' => self::POST, 'str_replace' => self::POST, 'preg_replace' => self::POST, 'explode' => self::POST);
        public static function i()
        {
            return self::_getMultiple(__CLASS__);
        }
        public function __construct($data)
        {
            Eden_Type_Error::i()->argument(1, 'scalar');
            $data = (string) $data;
            parent::__construct($data);
        }
        public function __toString()
        {
            return $this->_data;
        }
        public function camelize($prefix = '-')
        {
            Eden_Type_Error::i()->argument(1, 'string');
            $this->_data = str_replace($prefix, ' ', $this->_data);
            $this->_data = str_replace(' ', '', ucwords($this->_data));
            $this->_data = strtolower(substr($name, 0, 1)) . substr($name, 1);
            return $this;
        }
        public function dasherize()
        {
            $this->_data = preg_replace("/[^a-zA-Z0-9_-\s]/i", '', $this->_data);
            $this->_data = str_replace(' ', '-', trim($this->_data));
            $this->_data = preg_replace("/-+/i", '-', $this->_data);
            $this->_data = strtolower($this->_data);
            return $this;
        }
        public function titlize($prefix = '-')
        {
            Eden_Type_Error::i()->argument(1, 'string');
            $this->_data = ucwords(str_replace($prefix, ' ', $this->_data));
            return $this;
        }
        public function uncamelize($prefix = '-')
        {
            Eden_Type_Error::i()->argument(1, 'string');
            $this->_data = strtolower(preg_replace("/([A-Z])/", $prefix . "$1", $this->_data));
            return $this;
        }
        public function summarize($words)
        {
            Eden_Type_Error::i()->argument(1, 'int');
            $this->_data = explode(' ', strip_tags($this->_data), $words);
            array_pop($this->_data);
            $this->_data = implode(' ', $this->_data);
            return $this;
        }
        protected function _getMethodType(&$name)
        {
            if (isset(self::$_methods[$name])) {
                return self::$_methods[$name];
            }
            if (isset(self::$_methods['str_' . $name])) {
                $name = 'str_' . $name;
                return self::$_methods[$name];
            }
            $uncamel = strtolower(preg_replace("/([A-Z])/", "_$1", $name));
            if (isset(self::$_methods[$uncamel])) {
                $name = $uncamel;
                return self::$_methods[$name];
            }
            if (isset(self::$_methods['str_' . $uncamel])) {
                $name = 'str_' . $uncamel;
                return self::$_methods[$name];
            }
            return false;
        }
    }
}
/* Eden_Collection */
if (!class_exists('Eden_Collection')) {
    class Eden_Collection extends Eden_Class implements ArrayAccess, Iterator, Serializable, Countable
    {
        const FIRST = 'first';
        const LAST = 'last';
        const MODEL = 'Eden_Model';
        protected $_list = array();
        protected $_model = self::MODEL;
        public static function i()
        {
            return self::_getMultiple(__CLASS__);
        }
        public function __call($name, $args)
        {
            if (strpos($name, 'get') === 0) {
                $value = isset($args[0]) ? $args[0] : NULL;
                $list  = array();
                foreach ($this->_list as $i => $row) {
                    $list[] = $row->$name(isset($args[0]) ? $args[0] : NULL);
                }
                return $list;
            } else if (strpos($name, 'set') === 0) {
                $value     = isset($args[0]) ? $args[0] : NULL;
                $separator = isset($args[1]) ? $args[1] : NULL;
                foreach ($this->_list as $i => $row) {
                    $row->$name($value, $separator);
                }
                return $this;
            }
            $found = false;
            foreach ($this->_list as $i => $row) {
                if (!method_exists($row, $name)) {
                    continue;
                }
                $found = true;
                $row->callThis($name, $args);
            }
            if ($found) {
                return $this;
            }
            try {
                return parent::__call($name, $args);
            }
            catch (Eden_Error $e) {
                Eden_Collection_Error::i($e->getMessage())->trigger();
            }
        }
        public function __construct(array $data = array())
        {
            $this->set($data);
        }
        public function __set($name, $value)
        {
            foreach ($this->_list as $i => $row) {
                $row[$name] = $value;
            }
            return $this;
        }
        public function __toString()
        {
            return json_encode($this->get());
        }
        public function add($row = array())
        {
            Eden_Collection_Error::i()->argument(1, 'array', $this->_model);
            if (is_array($row)) {
                $model = $this->_model;
                $row   = $this->$model($row);
            }
            $this->_list[] = $row;
            return $this;
        }
        public function count()
        {
            return count($this->_list);
        }
        public function cut($index = self::LAST)
        {
            Eden_Collection_Error::i()->argument(1, 'string', 'int');
            if ($index == self::FIRST) {
                $index = 0;
            } else if ($index == self::LAST) {
                $index = count($this->_list) - 1;
            }
            if (isset($this->_list[$index])) {
                unset($this->_list[$index]);
            }
            $this->_list = array_values($this->_list);
            return $this;
        }
        public function each($callback)
        {
            Eden_Error::i()->argument(1, 'callable');
            foreach ($this->_list as $key => $value) {
                call_user_func($callback, $key, $value);
            }
            return $this;
        }
        public function current()
        {
            return current($this->_list);
        }
        public function get($modified = true)
        {
            Eden_Collection_Error::i()->argument(1, 'bool');
            $array = array();
            foreach ($this->_list as $i => $row) {
                $array[$i] = $row->get($modified);
            }
            return $array;
        }
        public function key()
        {
            return key($this->_list);
        }
        public function next()
        {
            next($this->_list);
        }
        public function offsetExists($offset)
        {
            return isset($this->_list[$offset]);
        }
        public function offsetGet($offset)
        {
            return isset($this->_list[$offset]) ? $this->_list[$offset] : NULL;
        }
        public function offsetSet($offset, $value)
        {
            Eden_Collection_Error::i()->argument(2, 'array', $this->_model);
            if (is_array($value)) {
                $model = $this->_model;
                $value = $this->$model($value);
            }
            if (is_null($offset)) {
                $this->_list[] = $value;
            } else {
                $this->_list[$offset] = $value;
            }
        }
        public function offsetUnset($offset)
        {
            $this->_list = Eden_Model::i($this->_list)->cut($offset)->get();
        }
        public function rewind()
        {
            reset($this->_list);
        }
        public function serialize()
        {
            return $this->__toString();
        }
        public function set(array $data = array())
        {
            foreach ($data as $row) {
                $this->add($row);
            }
            return $this;
        }
        public function setModel($model)
        {
            $error = Eden_Collection_Error::i()->argument(1, 'string');
            if (!is_subclass_of($model, 'Eden_Model')) {
                $error->setMessage(Eden_Collection_Error::NOT_SUB_MODEL)->addVariable($model)->trigger();
            }
            $this->_model = $model;
            return $this;
        }
        public function unserialize($data)
        {
            $this->_list = json_decode($data, true);
            return $this;
        }
        public function valid()
        {
            return isset($this->_list[key($this->_list)]);
        }
    }
    class Eden_Collection_Error extends Eden_Error
    {
        const NOT_COLLECTION = 'The data passed into __construct is not a collection.';
        const NOT_SUB_MODEL = 'Class %s is not a child of Eden_Model';
        public static function i($message = NULL, $code = 0)
        {
            $class = __CLASS__;
            return new $class($message, $code);
        }
    }
}
/* Eden_Curl */
if (!class_exists('Eden_Curl')) {
    class Eden_Curl extends Eden_Class implements ArrayAccess
    {
        const PUT = 'PUT';
        const DELETE = 'DELETE';
        const GET = 'GET';
        const POST = 'POST';
        protected $_options = array();
        protected $_meta = array();
        protected $_query = array();
        protected $_headers = array();
        protected static $_setBoolKeys = array('AutoReferer' => CURLOPT_AUTOREFERER, 'BinaryTransfer' => CURLOPT_BINARYTRANSFER, 'CookieSession' => CURLOPT_COOKIESESSION, 'CrlF' => CURLOPT_CRLF, 'DnsUseGlobalCache' => CURLOPT_DNS_USE_GLOBAL_CACHE, 'FailOnError' => CURLOPT_FAILONERROR, 'FileTime' => CURLOPT_FILETIME, 'FollowLocation' => CURLOPT_FOLLOWLOCATION, 'ForbidReuse' => CURLOPT_FORBID_REUSE, 'FreshConnect' => CURLOPT_FRESH_CONNECT, 'FtpUseEprt' => CURLOPT_FTP_USE_EPRT, 'FtpUseEpsv' => CURLOPT_FTP_USE_EPSV, 'FtpAppend' => CURLOPT_FTPAPPEND, 'FtpListOnly' => CURLOPT_FTPLISTONLY, 'Header' => CURLOPT_HEADER, 'HeaderOut' => CURLINFO_HEADER_OUT, 'HttpGet' => CURLOPT_HTTPGET, 'HttpProxyTunnel' => CURLOPT_HTTPPROXYTUNNEL, 'Netrc' => CURLOPT_NETRC, 'Nobody' => CURLOPT_NOBODY, 'NoProgress' => CURLOPT_NOPROGRESS, 'NoSignal' => CURLOPT_NOSIGNAL, 'Post' => CURLOPT_POST, 'Put' => CURLOPT_PUT, 'ReturnTransfer' => CURLOPT_RETURNTRANSFER, 'SslVerifyPeer' => CURLOPT_SSL_VERIFYPEER, 'TransferText' => CURLOPT_TRANSFERTEXT, 'UnrestrictedAuth' => CURLOPT_UNRESTRICTED_AUTH, 'Upload' => CURLOPT_UPLOAD, 'Verbose' => CURLOPT_VERBOSE);
        protected static $_setIntegerKeys = array('BufferSize' => CURLOPT_BUFFERSIZE, 'ClosePolicy' => CURLOPT_CLOSEPOLICY, 'ConnectTimeout' => CURLOPT_CONNECTTIMEOUT, 'ConnectTimeoutMs' => CURLOPT_CONNECTTIMEOUT_MS, 'DnsCacheTimeout' => CURLOPT_DNS_CACHE_TIMEOUT, 'FtpSslAuth' => CURLOPT_FTPSSLAUTH, 'HttpVersion' => CURLOPT_HTTP_VERSION, 'HttpAuth' => CURLOPT_HTTPAUTH, 'InFileSize' => CURLOPT_INFILESIZE, 'LowSpeedLimit' => CURLOPT_LOW_SPEED_LIMIT, 'LowSpeedTime' => CURLOPT_LOW_SPEED_TIME, 'MaxConnects' => CURLOPT_MAXCONNECTS, 'MaxRedirs' => CURLOPT_MAXREDIRS, 'Port' => CURLOPT_PORT, 'ProxyAuth' => CURLOPT_PROXYAUTH, 'ProxyPort' => CURLOPT_PROXYPORT, 'ProxyType' => CURLOPT_PROXYTYPE, 'ResumeFrom' => CURLOPT_RESUME_FROM, 'SslVerifyHost' => CURLOPT_SSL_VERIFYHOST, 'SslVersion' => CURLOPT_SSLVERSION, 'TimeCondition' => CURLOPT_TIMECONDITION, 'Timeout' => CURLOPT_TIMEOUT, 'TimeoutMs' => CURLOPT_TIMEOUT_MS, 'TimeValue' => CURLOPT_TIMEVALUE);
        protected static $_setStringKeys = array('CaInfo' => CURLOPT_CAINFO, 'CaPath' => CURLOPT_CAPATH, 'Cookie' => CURLOPT_COOKIE, 'CookieFile' => CURLOPT_COOKIEFILE, 'CookieJar' => CURLOPT_COOKIEJAR, 'CustomRequest' => CURLOPT_CUSTOMREQUEST, 'EgdSocket' => CURLOPT_EGDSOCKET, 'Encoding' => CURLOPT_ENCODING, 'FtpPort' => CURLOPT_FTPPORT, 'Interface' => CURLOPT_INTERFACE, 'Krb4Level' => CURLOPT_KRB4LEVEL, 'PostFields' => CURLOPT_POSTFIELDS, 'Proxy' => CURLOPT_PROXY, 'ProxyUserPwd' => CURLOPT_PROXYUSERPWD, 'RandomFile' => CURLOPT_RANDOM_FILE, 'Range' => CURLOPT_RANGE, 'Referer' => CURLOPT_REFERER, 'SslCipherList' => CURLOPT_SSL_CIPHER_LIST, 'SslCert' => CURLOPT_SSLCERT, 'SslCertPassword' => CURLOPT_SSLCERTPASSWD, 'SslCertType' => CURLOPT_SSLCERTTYPE, 'SslEngine' => CURLOPT_SSLENGINE, 'SslEngineDefault' => CURLOPT_SSLENGINE_DEFAULT, 'Sslkey' => CURLOPT_SSLKEY, 'SslKeyPasswd' => CURLOPT_SSLKEYPASSWD, 'SslKeyType' => CURLOPT_SSLKEYTYPE, 'Url' => CURLOPT_URL, 'UserAgent' => CURLOPT_USERAGENT, 'UserPwd' => CURLOPT_USERPWD);
        protected static $_setArrayKeys = array('Http200Aliases' => CURLOPT_HTTP200ALIASES, 'HttpHeader' => CURLOPT_HTTPHEADER, 'PostQuote' => CURLOPT_POSTQUOTE, 'Quote' => CURLOPT_QUOTE);
        protected static $_setFileKeys = array('File' => CURLOPT_FILE, 'InFile' => CURLOPT_INFILE, 'StdErr' => CURLOPT_STDERR, 'WriteHeader' => CURLOPT_WRITEHEADER);
        protected static $_setCallbackKeys = array('HeaderFunction' => CURLOPT_HEADERFUNCTION, 'ReadFunction' => CURLOPT_READFUNCTION, 'WriteFunction' => CURLOPT_WRITEFUNCTION);
        public static function i()
        {
            return self::_getMultiple(__CLASS__);
        }
        public function __call($name, $args)
        {
            if (strpos($name, 'set') === 0) {
                $method = substr($name, 3);
                if (isset(self::$_setBoolKeys[$method])) {
                    Eden_Curl_Error::i()->vargument($name, $args, 1, 'bool');
                    $key                  = self::$_setBoolKeys[$method];
                    $this->_options[$key] = $args[0];
                    return $this;
                }
                if (isset(self::$_setIntegerKeys[$method])) {
                    Eden_Curl_Error::i()->vargument($name, $args, 1, 'int');
                    $key                  = self::$_setIntegerKeys[$method];
                    $this->_options[$key] = $args[0];
                    return $this;
                }
                if (isset(self::$_setStringKeys[$method])) {
                    Eden_Curl_Error::i()->vargument($name, $args, 1, 'string');
                    $key                  = self::$_setStringKeys[$method];
                    $this->_options[$key] = $args[0];
                    return $this;
                }
                if (isset(self::$_setArrayKeys[$method])) {
                    Eden_Curl_Error::i()->vargument($name, $args, 1, 'array');
                    $key                  = self::$_setArrayKeys[$method];
                    $this->_options[$key] = $args[0];
                    return $this;
                }
                if (isset(self::$_setFileKeys[$method])) {
                    $key                  = self::$_setFileKeys[$method];
                    $this->_options[$key] = $args[0];
                    return $this;
                }
                if (isset(self::$_setCallbackKeys[$method])) {
                    Eden_Curl_Error::i()->vargument($name, $args, 1, 'array', 'string');
                    $key                  = self::$_setCallbackKeys[$method];
                    $this->_options[$key] = $args[0];
                    return $this;
                }
            }
            parent::__call($name, $args);
        }
        public function getDomDocumentResponse()
        {
            $this->_meta['response'] = $this->getResponse();
            $xml                     = new DOMDocument();
            $xml->loadXML($this->_meta['response']);
            return $xml;
        }
        public function getJsonResponse($assoc = true)
        {
            $this->_meta['response'] = $this->getResponse();
            Eden_Curl_Error::i()->argument(1, 'bool');
            return json_decode($this->_meta['response'], $assoc);
        }
        public function getMeta($key = NULL)
        {
            Eden_Curl_Error::i()->argument(1, 'string', 'null');
            if (isset($this->_meta[$key])) {
                return $this->_meta[$key];
            }
            return $this->_meta;
        }
        public function getQueryResponse()
        {
            $this->_meta['response'] = $this->getResponse();
            parse_str($this->_meta['response'], $response);
            return $response;
        }
        public function getResponse()
        {
            $curl = curl_init();
            $this->_addParameters()->_addHeaders();
            $this->_options[CURLOPT_RETURNTRANSFER] = true;
            curl_setopt_array($curl, $this->_options);
            $response    = curl_exec($curl);
            $this->_meta = array(
                'info' => curl_getinfo($curl, CURLINFO_HTTP_CODE),
                'error_message' => curl_errno($curl),
                'error_code' => curl_error($curl)
            );
            curl_close($curl);
            unset($curl);
            return $response;
        }
        public function getSimpleXmlResponse()
        {
            $this->_meta['response'] = $this->getResponse();
            return simplexml_load_string($this->_meta['response']);
        }
        public function offsetExists($offset)
        {
            return isset($this->_option[$offset]);
        }
        public function offsetGet($offset)
        {
            return isset($this->_option[$offset]) ? $this->_option[$offset] : NULL;
        }
        public function offsetSet($offset, $value)
        {
            if (!is_null($offset)) {
                if (in_array($offset, $this->_setBoolKeys)) {
                    $method = array_search($offset, $this->_setBoolKeys);
                    $this->_call('set' . $method, array(
                        $value
                    ));
                }
                if (in_array($offset, $this->_setIntegerKeys)) {
                    $method = array_search($offset, $this->_setIntegerKeys);
                    $this->_call('set' . $method, array(
                        $value
                    ));
                }
                if (in_array($offset, $this->_setStringKeys)) {
                    $method = array_search($offset, $this->_setStringKeys);
                    $this->_call('set' . $method, array(
                        $value
                    ));
                }
                if (in_array($offset, $this->_setArrayKeys)) {
                    $method = array_search($offset, $this->_setArrayKeys);
                    $this->_call('set' . $method, array(
                        $value
                    ));
                }
                if (in_array($offset, $this->_setFileKeys)) {
                    $method = array_search($offset, $this->_setFileKeys);
                    $this->_call('set' . $method, array(
                        $value
                    ));
                }
                if (in_array($offset, $this->_setCallbackKeys)) {
                    $method = array_search($offset, $this->_setCallbackKeys);
                    $this->_call('set' . $method, array(
                        $value
                    ));
                }
            }
        }
        public function offsetUnset($offset)
        {
            unset($this->_option[$offset]);
        }
        public function send()
        {
            $curl = curl_init();
            $this->_addParameters()->_addHeaders();
            curl_setopt_array($curl, $this->_options);
            curl_exec($curl);
            $this->_meta = array(
                'info' => curl_getinfo($curl, CURLINFO_HTTP_CODE),
                'error_message' => curl_errno($curl),
                'error_code' => curl_error($curl)
            );
            curl_close($curl);
            unset($curl);
            return $this;
        }
        public function setCustomGet()
        {
            $this->setCustomRequest(self::GET);
            return $this;
        }
        public function setCustomPost()
        {
            $this->setCustomRequest(self::POST);
            return $this;
        }
        public function setCustomPut()
        {
            $this->setCustomRequest(self::PUT);
            return $this;
        }
        public function setCustomDelete()
        {
            $this->setCustomRequest(self::DELETE);
            return $this;
        }
        public function setPostFields($fields)
        {
            Eden_Curl_Error::i()->argument(1, 'array', 'string');
            $this->_options[CURLOPT_POSTFIELDS] = $fields;
            return $this;
        }
        public function setHeaders($key, $value = NULL)
        {
            Eden_Curl_Error::i()->argument(1, 'array', 'string')->argument(2, 'scalar', 'null');
            if (is_array($key)) {
                $this->_headers = $key;
                return $this;
            }
            $this->_headers[] = $key . ': ' . $value;
            return $this;
        }
        public function setUrlParameter($key, $value = NULL)
        {
            Eden_Curl_Error::i()->argument(1, 'array', 'string')->argument(2, 'scalar');
            if (is_array($key)) {
                $this->_param = $key;
                return $this;
            }
            $this->_param[$key] = $value;
        }
        public function verifyHost($on = true)
        {
            Eden_Curl_Error::i()->argument(1, 'bool');
            $this->_options[CURLOPT_SSL_VERIFYHOST] = $on ? 1 : 2;
            return $this;
        }
        public function verifyPeer($on = true)
        {
            Eden_Curl_Error::i()->argument(1, 'bool');
            $this->_options[CURLOPT_SSL_VERIFYPEER] = $on;
            return $this;
        }
        protected function _addHeaders()
        {
            if (empty($this->_headers)) {
                return $this;
            }
            $this->_options[CURLOPT_HTTPHEADER] = $this->_headers;
            return $this;
        }
        protected function _addParameters()
        {
            if (empty($this->_params)) {
                return $this;
            }
            $params = http_build_query($this->_params);
            if ($this->_options[CURLOPT_POST]) {
                $this->_options[CURLOPT_POSTFIELDS] = $params;
                return $this;
            }
            if (strpos($this->_options[CURLOPT_URL], '?') === false) {
                $params = '?' . $params;
            } else if (substr($this->_options[CURLOPT_URL], -1, 1) != '?') {
                $params = '&' . $params;
            }
            $this->_options[CURLOPT_URL] .= $params;
            return $this;
        }
    }
    class Eden_Curl_Error extends Eden_Error
    {
        public static function i($message = NULL, $code = 0)
        {
            $class = __CLASS__;
            return new $class($message, $code);
        }
    }
}
/* Eden_Path */
if (!class_exists('Eden_Path')) {
    class Eden_Path extends Eden_Type_String implements ArrayAccess
    {
        public static function i()
        {
            return self::_getMultiple(__CLASS__);
        }
        public function __construct($path)
        {
            Eden_Path_Error::i()->argument(1, 'string');
            parent::__construct($this->_format($path));
        }
        public function __toString()
        {
            return $this->_data;
        }
        public function absolute($root = NULL)
        {
            Eden_Path_Error::i()->argument(1, 'string', 'null');
            if (is_dir($this->_data) || is_file($this->_data)) {
                return $this;
            }
            if (is_null($root)) {
                $root = $_SERVER['DOCUMENT_ROOT'];
            }
            $absolute = $this->_format($root) . $this->_data;
            if (is_dir($absolute) || is_file($absolute)) {
                $this->_data = $absolute;
                return $this;
            }
            Eden_Path_Error::i()->setMessage(Eden_Path_Error::FULL_PATH_NOT_FOUND)->addVariable($this->_data)->addVariable($absolute)->trigger();
        }
        public function append($path)
        {
            $error = Eden_Path_Error::i()->argument(1, 'string');
            $paths = func_get_args();
            foreach ($paths as $i => $path) {
                $error->argument($i + 1, $path, 'string');
                $this->_data .= $this->_format($path);
            }
            return $this;
        }
        public function getArray()
        {
            return explode('/', $this->_data);
        }
        public function offsetExists($offset)
        {
            return in_array($offset, $this->getArray());
        }
        public function offsetGet($offset)
        {
            $pathArray = $this->getArray();
            if ($offset == 'first') {
                $offset = 0;
            }
            if ($offset == 'last') {
                $offset = count($pathArray) - 1;
            }
            if (is_numeric($offset)) {
                return isset($pathArray[$offset]) ? $pathArray[$offset] : NULL;
            }
            return NULL;
        }
        public function offsetSet($offset, $value)
        {
            if (is_null($offset)) {
                $this->append($value);
            } else if ($offset == 'prepend') {
                $this->prepend($value);
            } else if ($offset == 'replace') {
                $this->replace($value);
            } else {
                $pathArray = $this->getArray();
                if ($offset > 0 && $offset < count($pathArray)) {
                    $pathArray[$offset] = $value;
                    $this->_data        = implode('/', $pathArray);
                }
            }
        }
        public function offsetUnset($offset)
        {
        }
        public function prepend($path)
        {
            $error = Eden_Path_Error::i()->argument(1, 'string');
            $paths = func_get_args();
            foreach ($paths as $i => $path) {
                $error->argument($i + 1, $path, 'string');
                $this->_data = $this->_format($path) . $this->_data;
            }
            return $this;
        }
        public function pop()
        {
            $pathArray   = $this->getArray();
            $path        = array_pop($pathArray);
            $this->_data = implode('/', $pathArray);
            return $path;
        }
        public function replace($path)
        {
            Eden_Path_Error::i()->argument(1, 'string');
            $pathArray = $this->getArray();
            array_pop($pathArray);
            $pathArray[] = $path;
            $this->_data = implode('/', $pathArray);
            return $this;
        }
        protected function _format($path)
        {
            $path = str_replace('\\', '/', $path);
            $path = str_replace('//', '/', $path);
            if (substr($path, -1, 1) == '/') {
                $path = substr($path, 0, -1);
            }
            if (substr($path, 0, 1) != '/' && !preg_match("/^[A-Za-z]+\:/", $path)) {
                $path = '/' . $path;
            }
            return $path;
        }
    }
    class Eden_Path_Error extends Eden_Error
    {
        const FULL_PATH_NOT_FOUND = 'The path %s or %s was not found.';
        public static function i($message = NULL, $code = 0)
        {
            $class = __CLASS__;
            return new $class($message, $code);
        }
    }
}
/* Eden_File */
if (!class_exists('Eden_File')) {
    class Eden_File extends Eden_Path
    {
        protected $_path = NULL;
        public static function i()
        {
            return self::_getMultiple(__CLASS__);
        }
        public function isFile()
        {
            return file_exists($this->_data);
        }
        public function getBase()
        {
            $pathInfo = pathinfo($this->_data);
            return $pathInfo['filename'];
        }
        public function getContent()
        {
            $this->absolute();
            if (!is_file($this->_data)) {
                Eden_File_Error::i()->setMessage(Eden_File_Error::PATH_IS_NOT_FILE)->addVariable($this->_data)->trigger();
            }
            return file_get_contents($this->_data);
        }
        public function getData()
        {
            $this->absolute();
            return include($this->_data);
        }
        public function getExtension()
        {
            $pathInfo = pathinfo($this->_data);
            if (!isset($pathInfo['extension'])) {
                return NULL;
            }
            return $pathInfo['extension'];
        }
        public function getFolder()
        {
            return dirname($this->_data);
        }
        public function getMime()
        {
            $this->absolute();
            if (function_exists('mime_content_type')) {
                return mime_content_type($this->_data);
            }
            if (function_exists('finfo_open')) {
                $resource = finfo_open(FILEINFO_MIME_TYPE);
                $mime     = finfo_file($resource, $this->_data);
                finfo_close($finfo);
                return $mime;
            }
            $extension = strtolower($this->getExtension());
            $types     = self::$_mimeTypes;
            if (isset($types[$extension])) {
                return $types[$extension];
            }
            return $types['class'];
        }
        public function getName()
        {
            return basename($this->_data);
        }
        public function getSize()
        {
            $this->absolute();
            return filesize($this->_data);
        }
        public function getTime()
        {
            $this->absolute();
            return filemtime($this->_data);
        }
        public function setContent($content)
        {
            Eden_File_Error::i()->argument(1, 'string');
            try {
                $this->absolute();
            }
            catch (Eden_Path_Error $e) {
                $this->touch();
            }
            file_put_contents($this->_data, $content);
            return $this;
        }
        public function setData($variable)
        {
            return $this->setContent(" //-->\nreturn " . var_export($variable, true) . ";");
        }
        public function remove()
        {
            $this->absolute();
            if (is_file($this->_data)) {
                unlink($this->_data);
                return $this;
            }
            return $this;
        }
        public function touch()
        {
            touch($this->_data);
            return $this;
        }
        protected static $_mimeTypes = array('ai' => 'application/postscript', 'aif' => 'audio/x-aiff', 'aifc' => 'audio/x-aiff', 'aiff' => 'audio/x-aiff', 'asc' => 'text/plain', 'atom' => 'application/atom+xml', 'au' => 'audio/basic', 'avi' => 'video/x-msvideo', 'bcpio' => 'application/x-bcpio', 'bin' => 'application/octet-stream', 'bmp' => 'image/bmp', 'cdf' => 'application/x-netcdf', 'cgm' => 'image/cgm', 'class' => 'application/octet-stream', 'cpio' => 'application/x-cpio', 'cpt' => 'application/mac-compactpro', 'csh' => 'application/x-csh', 'css' => 'text/css', 'dcr' => 'application/x-director', 'dif' => 'video/x-dv', 'dir' => 'application/x-director', 'djv' => 'image/vnd.djvu', 'djvu' => 'image/vnd.djvu', 'dll' => 'application/octet-stream', 'dmg' => 'application/octet-stream', 'dms' => 'application/octet-stream', 'doc' => 'application/msword', 'dtd' => 'application/xml-dtd', 'dv' => 'video/x-dv', 'dvi' => 'application/x-dvi', 'dxr' => 'application/x-director', 'eps' => 'application/postscript', 'etx' => 'text/x-setext', 'exe' => 'application/octet-stream', 'ez' => 'application/andrew-inset', 'gif' => 'image/gif', 'gram' => 'application/srgs', 'grxml' => 'application/srgs+xml', 'gtar' => 'application/x-gtar', 'hdf' => 'application/x-hdf', 'hqx' => 'application/mac-binhex40', 'htm' => 'text/html', 'html' => 'text/html', 'ice' => 'x-conference/x-cooltalk', 'ico' => 'image/x-icon', 'ics' => 'text/calendar', 'ief' => 'image/ief', 'ifb' => 'text/calendar', 'iges' => 'model/iges', 'igs' => 'model/iges', 'jnlp' => 'application/x-java-jnlp-file', 'jp2' => 'image/jp2', 'jpe' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg', 'js' => 'application/x-javascript', 'kar' => 'audio/midi', 'latex' => 'application/x-latex', 'lha' => 'application/octet-stream', 'lzh' => 'application/octet-stream', 'm3u' => 'audio/x-mpegurl', 'm4a' => 'audio/mp4a-latm', 'm4b' => 'audio/mp4a-latm', 'm4p' => 'audio/mp4a-latm', 'm4u' => 'video/vnd.mpegurl', 'm4v' => 'video/x-m4v', 'mac' => 'image/x-macpaint', 'man' => 'application/x-troff-man', 'mathml' => 'application/mathml+xml', 'me' => 'application/x-troff-me', 'mesh' => 'model/mesh', 'mid' => 'audio/midi', 'midi' => 'audio/midi', 'mif' => 'application/vnd.mif', 'mov' => 'video/quicktime', 'movie' => 'video/x-sgi-movie', 'mp2' => 'audio/mpeg', 'mp3' => 'audio/mpeg', 'mp4' => 'video/mp4', 'mpe' => 'video/mpeg', 'mpeg' => 'video/mpeg', 'mpg' => 'video/mpeg', 'mpga' => 'audio/mpeg', 'ms' => 'application/x-troff-ms', 'msh' => 'model/mesh', 'mxu' => 'video/vnd.mpegurl', 'nc' => 'application/x-netcdf', 'oda' => 'application/oda', 'ogg' => 'application/ogg', 'pbm' => 'image/x-portable-bitmap', 'pct' => 'image/pict', 'pdb' => 'chemical/x-pdb', 'pdf' => 'application/pdf', 'pgm' => 'image/x-portable-graymap', 'pgn' => 'application/x-chess-pgn', 'pic' => 'image/pict', 'pict' => 'image/pict', 'png' => 'image/png', 'pnm' => 'image/x-portable-anymap', 'pnt' => 'image/x-macpaint', 'pntg' => 'image/x-macpaint', 'ppm' => 'image/x-portable-pixmap', 'ppt' => 'application/vnd.ms-powerpoint', 'ps' => 'application/postscript', 'qt' => 'video/quicktime', 'qti' => 'image/x-quicktime', 'qtif' => 'image/x-quicktime', 'ra' => 'audio/x-pn-realaudio', 'ram' => 'audio/x-pn-realaudio', 'ras' => 'image/x-cmu-raster', 'rdf' => 'application/rdf+xml', 'rgb' => 'image/x-rgb', 'rm' => 'application/vnd.rn-realmedia', 'roff' => 'application/x-troff', 'rtf' => 'text/rtf', 'rtx' => 'text/richtext', 'sgm' => 'text/sgml', 'sgml' => 'text/sgml', 'sh' => 'application/x-sh', 'shar' => 'application/x-shar', 'silo' => 'model/mesh', 'sit' => 'application/x-stuffit', 'skd' => 'application/x-koan', 'skm' => 'application/x-koan', 'skp' => 'application/x-koan', 'skt' => 'application/x-koan', 'smi' => 'application/smil', 'smil' => 'application/smil', 'snd' => 'audio/basic', 'so' => 'application/octet-stream', 'spl' => 'application/x-futuresplash', 'src' => 'application/x-wais-source', 'sv4cpio' => 'application/x-sv4cpio', 'sv4crc' => 'application/x-sv4crc', 'svg' => 'image/svg+xml', 'swf' => 'application/x-shockwave-flash', 't' => 'application/x-troff', 'tar' => 'application/x-tar', 'tcl' => 'application/x-tcl', 'tex' => 'application/x-tex', 'texi' => 'application/x-texinfo', 'texinfo' => 'application/x-texinfo', 'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'tr' => 'application/x-troff', 'tsv' => 'text/tab-separated-values', 'txt' => 'text/plain', 'ustar' => 'application/x-ustar', 'vcd' => 'application/x-cdlink', 'vrml' => 'model/vrml', 'vxml' => 'application/voicexml+xml', 'wav' => 'audio/x-wav', 'wbmp' => 'image/vnd.wap.wbmp', 'wbmxl' => 'application/vnd.wap.wbxml', 'wml' => 'text/vnd.wap.wml', 'wmlc' => 'application/vnd.wap.wmlc', 'wmls' => 'text/vnd.wap.wmlscript', 'wmlsc' => 'application/vnd.wap.wmlscriptc', 'wrl' => 'model/vrml', 'xbm' => 'image/x-xbitmap', 'xht' => 'application/xhtml+xml', 'xhtml' => 'application/xhtml+xml', 'xls' => 'application/vnd.ms-excel', 'xml' => 'application/xml', 'xpm' => 'image/x-xpixmap', 'xsl' => 'application/xml', 'xslt' => 'application/xslt+xml', 'xul' => 'application/vnd.mozilla.xul+xml', 'xwd' => 'image/x-xwindowdump', 'xyz' => 'chemical/x-xyz', 'zip' => 'application/zip');
    }
    class Eden_File_Error extends Eden_Path_Error
    {
        const PATH_IS_NOT_FILE = 'Path %s is not a file in the system.';
        public static function i($message = NULL, $code = 0)
        {
            $class = __CLASS__;
            return new $class($message, $code);
        }
    }
}
/* Eden_Folder */
if (!class_exists('Eden_Folder')) {
    class Eden_Folder extends Eden_Path
    {
        public static function i()
        {
            return self::_getMultiple(__CLASS__);
        }
        public function create($chmod = 0755)
        {
            if (!is_int($chmod) || $chmod < 0 || $chmod > 777) {
                Eden_Folder_Error::i(Eden_Folder_Exception::CHMOD_IS_INVALID)->trigger();
            }
            if (!is_dir($this->_data)) {
                mkdir($this->_data, $chmod, true);
            }
            return $this;
        }
        public function getFiles($regex = NULL, $recursive = false)
        {
            $error = Eden_Folder_Error::i()->argument(1, 'string', 'null')->argument(2, 'bool');
            $this->absolute();
            $files = array();
            if ($handle = opendir($this->_data)) {
                while (false !== ($file = readdir($handle))) {
                    if (filetype($this->_data . '/' . $file) == 'file' && (!$regex || preg_match($regex, $file))) {
                        $files[] = Eden_File::i($this->_data . '/' . $file);
                    } else if ($recursive && $file != '.' && $file != '..' && filetype($this->_data . '/' . $file) == 'dir') {
                        $subfiles = self::i($this->_data . '/' . $file);
                        $files    = array_merge($files, $subfiles->getFiles($regex, $recursive));
                    }
                }
                closedir($handle);
            }
            return $files;
        }
        public function getFolders($regex = NULL, $recursive = false)
        {
            Eden_Folder_Error::i()->argument(1, 'string', 'null')->argument(2, 'bool');
            $this->absolute();
            $folders = array();
            if ($handle = opendir($this->_data)) {
                while (false !== ($folder = readdir($handle))) {
                    if ($folder != '.' && $folder != '..' && filetype($this->_data . '/' . $folder) == 'dir' && (!$regex || preg_match($regex, $folder))) {
                        $folders[] = Eden_Folder::i($this->_data . '/' . $folder);
                        if ($recursive) {
                            $subfolders = Eden_Folder::i($this->_data . '/' . $folder);
                            $folders    = array_merge($folders, $subfolders->getFolders($regex, $recursive));
                        }
                    }
                }
                closedir($handle);
            }
            return $folders;
        }
        public function getName()
        {
            $pathArray = $this->getArray();
            return array_pop($pathArray);
        }
        public function isFile()
        {
            return file_exists($this->_data);
        }
        public function isFolder($path = NULL)
        {
            Eden_Folder_Error::i()->argument(1, 'string', 'null');
            if (is_string($path)) {
                return is_dir($this->_data . '/' . $path);
            }
            return is_dir($this->_data);
        }
        public function remove()
        {
            $path = $this->absolute();
            if (is_dir($path)) {
                rmdir($path);
            }
            return $this;
        }
        public function removeFiles($regex = NULL)
        {
            Eden_Folder_Error::i()->argument(1, 'string', 'null');
            $files = $this->getFiles($regex);
            if (empty($files)) {
                return $this;
            }
            foreach ($files as $file) {
                $file->remove();
            }
            return $this;
        }
        public function removeFolders($regex = NULL)
        {
            Eden_Folder_Error::i()->argument(1, 'string', 'null');
            $this->absolute();
            $folders = $this->getFolders($regex);
            if (empty($folders)) {
                return $this;
            }
            foreach ($folders as $folder) {
                $folder->remove();
            }
            return $this;
        }
        public function truncate()
        {
            $this->removeFolders();
            $this->removeFiles();
            return $this;
        }
    }
    class Eden_Folder_Error extends Eden_Error
    {
        public static function i($message = NULL, $code = 0)
        {
            $class = __CLASS__;
            return new $class($message, $code);
        }
    }
}
/* Eden_Block */
if (!class_exists('Eden_Block')) {
    abstract class Eden_Block extends Eden_Class
    {
        protected static $_blockRoot = NULL;
        private static $_global = array();
        public function __toString()
        {
            try {
                return (string) $this->render();
            }
            catch (Exception $e) {
                Eden_Error_Event::i()->exceptionHandler($e);
            }
            return '';
        }
        abstract public function getTemplate();
        abstract public function getVariables();
        public function render()
        {
            return Eden_Template::i()->set($this->getVariables())->parsePhp($this->getTemplate());
        }
        public function setBlockRoot($root)
        {
            Eden_Error::i()->argument(1, 'folder');
            self::$_blockRoot = $root;
            return $this;
        }
        protected function _getGlobal($value)
        {
            if (in_array($value, self::$_global)) {
                return false;
            }
            self::$_global[] = $value;
            return $value;
        }
    }
}
/* Eden_Model */
if (!class_exists('Eden_Model')) {
    class Eden_Model extends Eden_Type_Array
    {
        public static function i()
        {
            return self::_getMultiple(__CLASS__);
        }
        protected function _getMethodType(&$name)
        {
            return false;
        }
    }
    class Eden_Model_Error extends Eden_Error
    {
        public static function i($message = NULL, $code = 0)
        {
            $class = __CLASS__;
            return new $class($message, $code);
        }
    }
}
/* Eden */
if (!class_exists('Eden')) {
    function eden()
    {
        $class = Eden::i();
        if (func_num_args() == 0) {
            return $class;
        }
        $args = func_get_args();
        return $class->__invoke($args);
    }
    class Eden extends Eden_Event
    {
        protected $_root = NULL;
        protected static $_active = NULL;
        public static function i()
        {
            return self::_getSingleton(__CLASS__);
        }
        public function __construct()
        {
            if (!self::$_active) {
                self::$_active = $this;
            }
            $this->_root = dirname(__FILE__);
        }
        public function __call($name, $args)
        {
            try {
                return parent::__call($name, $args);
            }
            catch (Eden_Route_Exception $e) {
                return parent::__call('Eden_' . $name, $args);
            }
        }
        public function setRoot($root)
        {
            Eden_Error::i()->argument(1, 'string');
            if (!class_exists('Eden_Path')) {
                Eden_Loader::i()->load('Eden_Path');
            }
            $this->_root = (string) Eden_Path::i($root);
            return $this;
        }
        public function getRoot()
        {
            return $this->_root;
        }
        public function getActiveApp()
        {
            return self::$_active;
        }
        public function setLoader()
        {
            if (!class_exists('Eden_Loader')) {
                require_once dirname(__FILE__) . '/eden/loader.php';
            }
            spl_autoload_register(array(
                Eden_Loader::i(),
                'handler'
            ));
            if (!class_exists('Eden_Path')) {
                Eden_Loader::i()->addRoot(dirname(__FILE__))->load('Eden_Path');
            }
            $paths = func_get_args();
            if (empty($paths)) {
                return $this;
            }
            $paths = array_unique($paths);
            foreach ($paths as $i => $path) {
                if (!is_string($path) && !is_null($path)) {
                    continue;
                }
                if ($path) {
                    $path = (string) Eden_Path::i($path);
                } else {
                    $path = $this->_root;
                }
                if (!is_dir($path)) {
                    $path = $this->_root . $path;
                }
                if (is_dir($path)) {
                    Eden_Loader::i()->addRoot($path);
                }
            }
            return $this;
        }
        public function routeClasses($routes)
        {
            Eden_Error::i()->argument(1, 'string', 'array', 'bool');
            $route = Eden_Route::i()->getClass();
            if ($routes === true) {
                $route->route('Cache', 'Eden_Cache')->route('Registry', 'Eden_Registry')->route('Model', 'Eden_Model')->route('Collection', 'Eden_Collection')->route('Cookie', 'Eden_Cookie')->route('Session', 'Eden_Session')->route('Template', 'Eden_Template')->route('Curl', 'Eden_Curl')->route('Event', 'Eden_Event')->route('Path', 'Eden_Path')->route('File', 'Eden_File')->route('Folder', 'Eden_Folder')->route('Image', 'Eden_Image')->route('Mysql', 'Eden_Mysql')->route('Type', 'Eden_Type');
                return $this;
            }
            if (is_string($routes)) {
                $routes = include($routes);
            }
            foreach ($routes as $alias => $class) {
                $route->route($alias, $class);
            }
            return $this;
        }
        public function routeMethods($routes)
        {
            Eden_Error::i()->argument(1, 'string', 'array', 'bool');
            $route = Eden_Route::i()->getMethod();
            if (is_bool($routes)) {
                $route->route(NULL, 'output', 'Eden_Debug');
                return $this;
            }
            if (is_string($routes)) {
                $routes = include($routes);
            }
            foreach ($routes as $method => $routePath) {
                if (is_string($routePath)) {
                    $routePath = array(
                        $routePath
                    );
                }
                if (is_array($routePath) && !empty($routePath)) {
                    if (count($routePath) == 1) {
                        $routePath[] = $method;
                    }
                    $route->route($method, $routePath[0], $routePath[1]);
                }
            }
            return $this;
        }
        public function startSession()
        {
            Eden_Session::i()->start();
            return $this;
        }
        public function setTimezone($zone)
        {
            Eden_Error::i()->argument(1, 'string');
            date_default_timezone_set($zone);
            return $this;
        }
    }
}
/* Eden_Mail */
if (!class_exists('Eden_Mail')) {
    class Eden_Mail extends Eden_Class
    {
        public static function i()
        {
            return self::_getSingleton(__CLASS__);
        }
        public function imap($host, $user, $pass, $port = NULL, $ssl = false, $tls = false)
        {
            Eden_Mail_Error::i()->argument(1, 'string')->argument(2, 'string')->argument(3, 'string')->argument(4, 'int', 'null')->argument(5, 'bool')->argument(6, 'bool');
            return Eden_Mail_Imap::i($host, $user, $pass, $port, $ssl, $tls);
        }
        public function pop3($host, $user, $pass, $port = NULL, $ssl = false, $tls = false)
        {
            Eden_Mail_Error::i()->argument(1, 'string')->argument(2, 'string')->argument(3, 'string')->argument(4, 'int', 'null')->argument(5, 'bool')->argument(6, 'bool');
            return Eden_Mail_Pop3::i($host, $user, $pass, $port, $ssl, $tls);
        }
        public function smtp($host, $user, $pass, $port = NULL, $ssl = false, $tls = false)
        {
            Eden_Mail_Error::i()->argument(1, 'string')->argument(2, 'string')->argument(3, 'string')->argument(4, 'int', 'null')->argument(5, 'bool')->argument(6, 'bool');
            return Eden_Mail_Smtp::i($host, $user, $pass, $port, $ssl, $tls);
        }
    }
}
/* Eden_Mail_Error */
if (!class_exists('Eden_Mail_Error')) {
    class Eden_Mail_Error extends Eden_Error
    {
        const SERVER_ERROR = 'Problem connecting to email server. Check server,port or ssl settings for your email server.';
        const LOGIN_ERROR = 'Your email provider has rejected your login information.Verify your email and/or password is correct. If you are using services like google, yahoo or hotmail etc, then allow less secure apps to access your email account.';
        const TLS_ERROR = 'Problem connecting to email sever with TLS on.';
        const SMTP_ADD_EMAIL = 'Adding %s to email failed.';
        const SMTP_DATA = 'Server did not allow data to be added.';
        public static function i($message = NULL, $code = 0)
        {
            $class = __CLASS__;
            return new $class($message, $code);
        }
    }
}
/* Eden_Mail_Imap */
if (!class_exists('Eden_Mail_Imap')) {
    class Eden_Mail_Imap extends Eden_Class
    {
        const TIMEOUT = 30;
        const NO_SUBJECT = '(no subject)';
        protected $_host = NULL;
        protected $_port = NULL;
        protected $_ssl = false;
        protected $_tls = false;
        protected $_username = NULL;
        protected $_password = NULL;
        protected $_tag = 0;
        protected $_total = 0;
        protected $_next = 0;
        protected $_buffer = NULL;
        protected $_socket = NULL;
        protected $_mailbox = NULL;
        protected $_mailboxes = array();
        public $headers=array();
        private $_debugging = false;
        public static function i()
        {
            return self::_getMultiple(__CLASS__);
        }
        public function __construct($host, $user, $pass, $port = NULL, $ssl = false, $tls = false)
        {
            Eden_Mail_Error::i()->argument(1, 'string')->argument(2, 'string')->argument(3, 'string')->argument(4, 'int', 'null')->argument(5, 'bool')->argument(6, 'bool');
            if (is_null($port)) {
                $port = $ssl ? 993 : 143;
            }
            $this->_host     = $host;
            $this->_username = $user;
            $this->_password = $pass;
            $this->_port     = $port;
            $this->_ssl      = $ssl;
            $this->_tls      = $tls;
        }
        public function connect($timeout = self::TIMEOUT, $test = false)
        {
            Eden_Mail_Error::i()->argument(1, 'int')->argument(2, 'bool');
            if ($this->_socket) {
                return $this;
            }
            $host = $this->_host;
            if ($this->_ssl) {
                $host = 'ssl://' . $host.':'.$this->_port;
            }
            $errno         = 0;
            $errstr        = '';
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            //$hostname = "tls://foo.localhost";
            $this->_socket = stream_socket_client($host, $errno, $errstr, ini_get("default_socket_timeout"), STREAM_CLIENT_CONNECT, $context);
           // $this->_socket = @fsockopen($host, $this->_port, $errno, $errstr, $timeout);
            if (!$this->_socket) {
                echo Eden_Mail_Error::SERVER_ERROR;
                exit();
            }
            if (strpos($this->_getLine(), '* OK') === false) {
                $this->disconnect();
                echo Eden_Mail_Error::SERVER_ERROR;
                exit();
            }
            if ($this->_tls) {
                $this->_send('STARTTLS');
                if (!stream_socket_enable_crypto($this->_socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    $this->disconnect();
                    echo Eden_Mail_Error::TLS_ERROR;
                exit();
                }
            }
            if ($test) {
                fclose($this->_socket);
                $this->_socket = NULL;
                return $this;
            }
            $result = $this->_call('LOGIN', $this->_escape($this->_username, $this->_password));
            if (!is_array($result) || strpos(implode(' ', $result), 'OK') === false) {
                $this->disconnect();
                echo Eden_Mail_Error::LOGIN_ERROR;
                exit();
               // Eden_Mail_Error::i(Eden_Mail_Error::LOGIN_ERROR)->trigger();
            }
            return $this;
        }
        public function disconnect()
        {
            if ($this->_socket) {
                $this->_send('LOGOUT');
                fclose($this->_socket);
                $this->_socket = NULL;
            }
            return $this;
        }
        public function getActiveMailbox()
        {
            return $this->_mailbox;
        }
        public function getEmails($start = 0, $range = 10, $body = false)
        {
            Eden_Mail_Error::i()->argument(1, 'int', 'array')->argument(2, 'int');
            if (!$this->_socket) {
                $this->connect();
            }
            if ($this->_total == 0) {
                return array();
            }
            if (is_array($start)) {
                $set = implode(',', $start);
            } else {
                $range = $range > 0 ? $range : 1;
                $start = $start >= 0 ? $start : 0;
                $max   = $this->_total - $start;
                if ($max < 1) {
                    $max = $this->_total;
                }
                $min = $max - $range + 1;
                if ($min < 1) {
                    $min = 1;
                }
                $set = $min . ':' . $max;
                if ($min == $max) {
                    $set = $min;
                }
            }
            $items = array(
                'UID',
                'FLAGS',
                'BODY[HEADER]'
            );
            if ($body) {
                $items = array(
                    'UID',
                    'FLAGS',
                    'BODY[]'
                );
            }
            $emails = $this->_getEmailResponse('FETCH', array(
                $set,
                $this->_getList($items)
            ));
            $emails = array_reverse($emails);
            return $emails;
        }
        public function getEmailTotal()
        {
            return $this->_total;
        }
        public function getNextUid()
        {
            return $this->_next;
        }
        public function getMailboxes()
        {
            if (!$this->_socket) {
                $this->connect();
            }
            $response  = $this->_call('LIST', $this->_escape('', '*'));
            $mailboxes = array();
            foreach ($response as $line) {
                if (strpos($line, 'Noselect') !== false || strpos($line, 'LIST') == false) {
                    continue;
                }
                $line = explode('"', $line);
                if (strpos(trim($line[0]), '*') !== 0) {
                    continue;
                }
                $mailboxes[] = $line[count($line) - 2];
            }
            return $mailboxes;
        }
        public function getUniqueEmails($uid, $body = false)
        {
            Eden_Mail_Error::i()->argument(1, 'int', 'string', 'array')->argument(2, 'bool');
            if (!$this->_socket) {
                $this->connect();
            }
            if ($this->_total == 0) {
                return array();
            }
            if (is_array($uid)) {
                $uid = implode(',', $uid);
            }
            $items = array(
                'UID',
                'FLAGS',
                'BODY[HEADER]'
            );
            if ($body) {
                $items = array(
                    'UID',
                    'FLAGS',
                    'BODY[]'
                );
            }
            $first = is_numeric($uid) ? true : false;
            
             $output=$this->_getEmailResponse('UID FETCH', array(
                $uid,
                $this->_getList($items)
            ), $first);
            return $output;
        }


        public function bodypeek($uid)
        {   if (!$this->_socket) {
                $this->connect();
            }
            $body=$this->_call('UID', array('FETCH',$uid,'(BODYSTRUCTURE)'));
           // explode('FILENAME')
            return $body;
             
        }
        public function getbodyifattachment($uid)
        {   
            if (!$this->_socket) {
                $this->connect();
            }
             //var_dump($this->_escape());
             $body=$this->_call('UID', array('FETCH',$uid,'(BODY.PEEK[1])'));
             return $body;
             $num_of_lines=count($body);
             $body_html="";
             $body_text="";
             $check=false;
             $set_plain=false;
             $set_html=false;
             //echo $line."\n";
                for($i=0;$i<$num_of_lines-2;$i++)
                {      
                    if(stripost($body[$i],"FETCH UID") || stripos($body[$i],"text/plain")!==false)
                    {  
                        $set_plain=true;
                        $set_html-false;
                        continue;
                    }
                    else if(stripos($body[$i],"text/html")!==false)
                    {    
                        $set_html=true;
                        $set_plain=false;
                        continue;
                    }

                    if($set_plain)
                    {   
                        $body_text.=$body[$i];
                    }

                    else if($set_html)
                    {
                        $body_html.=$body[$i];
                    }

                    
                    
                }
             return array("text/plain"=>$body_text,"text/html"=>$body_html);
        }

        public function getbodyifnoattachment($uid)
        {
            if (!$this->_socket) {
                $this->connect();
            }
             //var_dump($this->_escape());
              return $this->_call('UID', array('FETCH',$uid,'BODY[1]')); 
        }
        public function move($uid, $mailbox)
        {
            Eden_Mail_Error::i()->argument(1, 'int', 'string')->argument(2, 'string');
            if (!$this->_socket) {
                $this->connect();
            }
            if (!is_array($uid)) {
                $uid = array(
                    $uid
                );
            }
            $this->_call('UID COPY ' . implode(',', $uid) . ' ' . $mailbox);
            return $this->remove($uid);
        }
        public function remove($uid)
        {
            Eden_Mail_Error::i()->argument(1, 'int', 'string');
            if (!$this->_socket) {
                $this->connect();
            }
            if (!is_array($uid)) {
                $uid = array(
                    $uid
                );
            }
            $this->_call('UID STORE ' . implode(',', $uid) . ' FLAGS.SILENT \Deleted');
            return $this;
        }


        public function  mark_email_as_read($uid)
        {

        	Eden_Mail_Error::i()->argument(1, 'int', 'string');
            if (!$this->_socket) {
                $this->connect();
            }
            if (!is_array($uid)) {
                $uid = array(
                    $uid
                );
            }


            $this->_call('UID STORE ' . implode(',', $uid) . ' +FLAGS.SILENT \Seen');
            return $this;

        }





        public function  mark_email_as_unread($uid)
        {

        	Eden_Mail_Error::i()->argument(1, 'int', 'string');
            if (!$this->_socket) {
                $this->connect();
            }
            if (!is_array($uid)) {
                $uid = array(
                    $uid
                );
            }


            $this->_call('UID STORE ' . implode(',', $uid) . ' -FLAGS.SILENT \Seen');
            return $this;

        }

        public function mark_email_as_answered($uid)
        {


        }



        public function mark_email_as_unanswered($uid)
        {


        }


        public function mark_all_as_answered($uid)
        {

        }



        public function mark_all_as_unanswered($uid)
        {


        }



        public function uid_search( $filter, $start = 0, $range = 10, $or = false, $body = false)
        {
        	Eden_Mail_Error::i()->argument(2, 'int')->argument(3, 'int')->argument(4, 'bool')->argument(5, 'bool');
        	if (!$this->_socket) {
                $this->connect();
            }
            $search = $not = array();
            $response = $this->_call('UID SEARCH UID '.$filter[0]);
            $result   = array_pop($response);
            if (strpos($result, 'OK') !== false) {
                $uids = explode(' ', $response[0]);
                array_shift($uids);
                array_shift($uids);
                foreach ($uids as $i => $uid) {
                    if (in_array($uid, $not)) {
                        unset($uids[$i]);
                    }
                }
                if (empty($uids)) {
                    return array();
                }
                //$uids  = array_reverse($uids);
                $count = 0;
                foreach ($uids as $i => $id) {
                    if ($i < $start) {
                        unset($uids[$i]);
                        continue;
                    }
                    $count++;
                    if ($range != 0 && $count > $range) {
                        unset($uids[$i]);
                        continue;
                    }
                }
                return $this->getUniqueEmails($uids, $body);
            }
            return array();
        }


        public function search(array $filter, $start = 0, $range = 10, $or = false, $body = false)
        {
            Eden_Mail_Error::i()->argument(2, 'int')->argument(3, 'int')->argument(4, 'bool')->argument(5, 'bool');
            if (!$this->_socket) {
                $this->connect();
            }
            $search = $not = array();
            foreach ($filter as $where) {
                if (is_string($where)) {
                    $search[] = $where;
                    continue;
                }
                if ($where[0] == 'NOT') {
                    $not = $where[1];
                    continue;
                }
                $item = $where[0] . ' "' . $where[1] . '"';
                if (isset($where[2])) {
                    $item .= ' "' . $where[2] . '"';
                }
                $search[] = $item;
            }
            if ($or && count($search) > 1) {
                $query = NULL;
                while ($item = array_pop($search)) {
                    if (is_null($query)) {
                        $query = $item;
                    } else if (strpos($query, 'OR') !== 0) {
                        $query = 'OR (' . $query . ') (' . $item . ')';
                    } else {
                        $query = 'OR (' . $item . ') (' . $query . ')';
                    }
                }
                $search = $query;
            } else {
                $search = implode(' ', $search);
            }
            $response = $this->_call('UID SEARCH ' . $search);
            $result   = array_pop($response);
            if (strpos($result, 'OK') !== false) {
                $uids = explode(' ', $response[0]);
                array_shift($uids);
                array_shift($uids);
                foreach ($uids as $i => $uid) {
                    if (in_array($uid, $not)) {
                        unset($uids[$i]);
                    }
                }
                if (empty($uids)) {
                    return array();
                }
                $uids  = array_reverse($uids);
                $count = 0;
                foreach ($uids as $i => $id) {
                    if ($i < $start) {
                        unset($uids[$i]);
                        continue;
                    }
                    $count++;
                    if ($range != 0 && $count > $range) {
                        unset($uids[$i]);
                        continue;
                    }
                }
                return $this->getUniqueEmails($uids, $body);
            }
            return array();
        }
        public function searchTotal(array $filter, $or = false)
        {
            Eden_Mail_Error::i()->argument(2, 'bool');
            if (!$this->_socket) {
                $this->connect();
            }
            $search = array();
            foreach ($filter as $where) {
                $item = $where[0] . ' "' . $where[1] . '"';
                if (isset($where[2])) {
                    $item .= ' "' . $where[2] . '"';
                }
                $search[] = $item;
            }
            if ($or) {
                $search = 'OR (' . implode(') (', $search) . ')';
            } else {
                $search = implode(' ', $search);
            }
            $response = $this->_call('UID SEARCH ' . $search);
            $result   = array_pop($response);
            if (strpos($result, 'OK') !== false) {
                $uids = explode(' ', $response[0]);
                array_shift($uids);
                array_shift($uids);
                return count($uids);
            }
            return 0;
        }
        public function setActiveMailbox($mailbox)
        {
            Eden_Mail_Error::i()->argument(1, 'string');
            if (!$this->_socket) {
                $this->connect();
            }
            $response = $this->_call('SELECT', $this->_escape($mailbox));
            $result   = array_pop($response);
            foreach ($response as $line) {
                if (strpos($line, 'EXISTS') !== false) {
                    list($star, $this->_total, $type) = explode(' ', $line, 3);
                } else if (strpos($line, 'UIDNEXT') !== false) {
                    list($star, $ok, $next, $this->_next, $type) = explode(' ', $line, 5);
                    $this->_next = substr($this->_next, 0, -1);
                }
                if ($this->_total && $this->_next) {
                    break;
                }
            }
            if (strpos($result, 'OK') !== false) {
                $this->_mailbox = $mailbox;
                return $this;
            }
            return false;
        }


        public function setActiveMailboxExamine($mailbox)
        {
            Eden_Mail_Error::i()->argument(1, 'string');
            if (!$this->_socket) {
                $this->connect();
            }
            $response = $this->_call('EXAMINE', $this->_escape($mailbox));
            $result   = array_pop($response);
            foreach ($response as $line) {
                if (strpos($line, 'EXISTS') !== false) {
                    list($star, $this->_total, $type) = explode(' ', $line, 3);
                } else if (strpos($line, 'UIDNEXT') !== false) {
                    list($star, $ok, $next, $this->_next, $type) = explode(' ', $line, 5);
                    $this->_next = substr($this->_next, 0, -1);
                }
                if ($this->_total && $this->_next) {
                    break;
                }
            }
            if (strpos($result, 'OK') !== false) {
                $this->_mailbox = $mailbox;
                return $this;
            }
            return false;
        }




        protected function _call($command, $parameters = array())
        {
            if (!$this->_send($command, $parameters)) {
                return false;
            }
            return $this->_receive($this->_tag);
        }
        protected function _getLine()
        {
            $line = fgets($this->_socket);
            if ($line === false) {
                $this->disconnect();
            }
            $this->_debug('Receiving: ' . $line);
            return $line;
        }
        protected function _receive($sentTag)
        {
            $this->_buffer = array();
            $start         = time();
            while (time() < ($start + self::TIMEOUT)) {
                list($receivedTag, $line) = explode(' ', $this->_getLine(), 2);
                $this->_buffer[] = trim($receivedTag . ' ' . $line);
                if ($receivedTag == 'TAG' . $sentTag) {
                    return $this->_buffer;
                }
            }
            return NULL;
        }
        protected function _send($command, $parameters = array())
        {
            $this->_tag++;
            $line = 'TAG' . $this->_tag . ' ' . $command;
            if (!is_array($parameters)) {
                $parameters = array(
                    $parameters
                );
            }
            foreach ($parameters as $parameter) {
                if (is_array($parameter)) {
                    if (fputs($this->_socket, $line . ' ' . $parameter[0] . "\r\n") === false) {
                        return false;
                    }
                    if (strpos($this->_getLine(), '+ ') === false) {
                        return false;
                    }
                    $line = $parameter[1];
                } else {
                    $line .= ' ' . $parameter;
                }
            }
            $this->_debug('Sending: ' . $line);
            return fputs($this->_socket, $line . "\r\n");
        }
        private function _debug($string)
        {
            if ($this->_debugging) {
                $string = htmlspecialchars($string);
                echo '<pre>' . $string . '</pre>' . "\n";
            }
            return $this;
        }
        private function _escape($string)
        {
            if (func_num_args() < 2) {
                if (strpos($string, "\n") !== false) {
                    return array(
                        '{' . strlen($string) . '}',
                        $string
                    );
                } else {
                    return '"' . str_replace(array(
                        '\\',
                        '"'
                    ), array(
                        '\\\\',
                        '\\"'
                    ), $string) . '"';
                }
            }
            $result = array();
            foreach (func_get_args() as $string) {
                $result[] = $this->_escape($string);
            }
            return $result;
        }
        private function _getEmailFormat($email, $uniqueId = NULL, array $flags = array())
        {
            if (is_array($email)) {
                $email = implode("\n", $email);
            }
            $parts = preg_split("/\n\s*\n/", $email, 2);
            $head  = $parts[0];
            //var_dump($parts[0]);
            $body  = NULL;
            if (isset($parts[1]) && trim($parts[1]) != ')') {
                $body = $parts[1];
            }
            $lines = explode("\n", $head);
            $head  = array();
            foreach ($lines as $line) {
                if (trim($line) && preg_match("/^\s+/", $line)) {
                    $head[count($head) - 1] .= ' ' . trim($line);
                    continue;
                }
                $head[] = trim($line);
            }
            $head           = implode("\n", $head);
            $recipientsTo   = $recipientsCc = $recipientsBcc = $sender = array();
            $headers1       = imap_rfc822_parse_headers($head);
            $headers2       = $this->_getHeaders($head);
            

            $sender['name'] = NULL;
            if (isset($headers1->from[0]->personal)) {
                $sender['name'] = $headers1->from[0]->personal;
                if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($sender['name']))) {
                    $sender['name'] = str_replace('_', ' ', mb_decode_mimeheader($sender['name']));
                }
            }
            $sender['email'] = $headers1->from[0]->mailbox . '@' . $headers1->from[0]->host;
            if (isset($headers1->to)) {
                foreach ($headers1->to as $to) {
                    if (!isset($to->mailbox, $to->host)) {
                        continue;
                    }
                    $recipient = array(
                        'name' => NULL
                    );
                    if (isset($to->personal)) {
                        $recipient['name'] = $to->personal;
                        if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($recipient['name']))) {
                            $recipient['name'] = str_replace('_', ' ', mb_decode_mimeheader($recipient['name']));
                        }
                    }
                    $recipient['email'] = $to->mailbox . '@' . $to->host;
                    $recipientsTo[]     = $recipient;
                }
            }
            if (isset($headers1->cc)) {
                foreach ($headers1->cc as $cc) {
                    $recipient = array(
                        'name' => NULL
                    );
                    if (isset($cc->personal)) {
                        $recipient['name'] = $cc->personal;
                        if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($recipient['name']))) {
                            $recipient['name'] = str_replace('_', ' ', mb_decode_mimeheader($recipient['name']));
                        }
                    }
                    $recipient['email'] = $cc->mailbox . '@' . $cc->host;
                    $recipientsCc[]     = $recipient;
                }
            }
            if (isset($headers1->bcc)) {
                foreach ($headers1->bcc as $bcc) {
                    $recipient = array(
                        'name' => NULL
                    );
                    if (isset($bcc->personal)) {
                        $recipient['name'] = $bcc->personal;
                        if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($recipient['name']))) {
                            $recipient['name'] = str_replace('_', ' ', mb_decode_mimeheader($recipient['name']));
                        }
                    }
                    $recipient['email'] = $bcc->mailbox . '@' . $bcc->host;
                    $recipientsBcc[]    = $recipient;
                }
            }
            if (!isset($headers1->subject) || strlen(trim($headers1->subject)) === 0) {
                $headers1->subject = self::NO_SUBJECT;
            }
            $headers1->subject = str_replace(array(
                '<',
                '>'
            ), '', trim($headers1->subject));
            if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($headers1->subject))) {
                $headers1->subject = str_replace('_', ' ', mb_decode_mimeheader($headers1->subject));
            }
            $topic  = isset($headers2['thread-topic']) ? $headers2['thread-topic'] : $headers1->subject;
            $parent = isset($headers2['in-reply-to']) ? str_replace('"', '', $headers2['in-reply-to']) : NULL;
            $date   = isset($headers1->date) ? strtotime($headers1->date) : NULL;

            if (isset($headers2['message-id'])) {
                $messageId = str_replace('"', '', $headers2['message-id']);
            } else {
                $messageId = '<eden-no-id-' . md5(uniqid()) . '>';
            }
            //var_dump($headers2);
            $attachment = isset($headers2['content-type']) && strpos($headers2['content-type'], 'multipart/mixed') === 0;
            
            $format     = array(
                'id' => $messageId,
                'parent' => $parent,
                'topic' => $topic,
                'mailbox' => $this->_mailbox,
                'uid' => $uniqueId,
                'date' => $date,
                'subject' => str_replace('’', '\'', $headers1->subject),
                'from' => $sender,
                'flags' => $flags,
                'to' => $recipientsTo,
                'cc' => $recipientsCc,
                'bcc' => $recipientsBcc,
                'attachment' => $attachment
            );
            if (trim($body) && $body != ')') {
                $parts = $this->_getParts($email);
                if (empty($parts)) {
                    if(stripos($body,"text"))
                    $parts = array(
                        'text/plain' => $body
                    );
                }
                $body       = $parts;
                $attachment = array();
                if (isset($body['attachment'])) {
                    $attachment = $body['attachment'];
                    unset($body['attachment']);
                }
                $format['body']       = $body;
                $format['attachment'] = $attachment;
            }

           // var_dump($format);

            return $format;
        }
        private function _getEmailResponse($command, $parameters = array(), $first = false)
        {  
            if (!$this->_send($command, $parameters)) {
                return false;
            }
            $messageId = $uniqueId = $count = 0;
            $emails    = $email = array();
            $start     = time();
            while (time() < ($start + self::TIMEOUT)) {
                $line = str_replace("\n", '', $this->_getLine());
                if (strpos($line, 'FETCH') !== false && strpos($line, 'TAG' . $this->_tag) === false) {
                    if (!empty($email)) {
                        $emails[$uniqueId] = $this->_getEmailFormat($email, $uniqueId, $flags);
                        if ($first) {
                            return $emails[$uniqueId];
                        }
                        $email = array();
                    }
                    if (strpos($line, 'OK') !== false) {
                        continue;
                    }
                    $flags = array();
                    if (strpos($line, '\Answered') !== false) {
                        $flags[] = 'answered';
                    }
                    if (strpos($line, '\Flagged') !== false) {
                        $flags[] = 'flagged';
                    }
                    if (strpos($line, '\Deleted') !== false) {
                        $flags[] = 'deleted';
                    }
                    if (strpos($line, '\Seen') !== false) {
                        $flags[] = 'seen';
                    }
                    if (strpos($line, '\Draft') !== false) {
                        $flags[] = 'draft';
                    }
                    $findUid = explode(' ', $line);
                    foreach ($findUid as $i => $uid) {
                        if (is_numeric($uid)) {
                            $uniqueId = $uid;
                        }
                        if (strpos(strtolower($uid), 'uid') !== false) {
                            $uniqueId = $findUid[$i + 1];
                            break;
                        }
                    }
                    continue;
                }
                if (strpos($line, 'TAG' . $this->_tag) !== false) {
                    if (!empty($email) && strpos(trim($email[count($email) - 1]), ')') === 0) {
                        array_pop($email);
                    }
                    if (!empty($email)) {
                        $emails[$uniqueId] = $this->_getEmailFormat($email, $uniqueId, $flags);
                        if ($first) {
                            return $emails[$uniqueId];
                        }
                    }
                    break;
                }
                $email[] = $line;
            }
            return $emails;
        }
        private function _getHeaders($rawData)
        {   
            if (is_string($rawData)) {
                $rawData = explode("\n", $rawData);
                
            }
            $key     = NULL;
            $headers = array();
            
            foreach ($rawData as $line) {
                $line = trim($line);
               
                if (preg_match("/^([a-zA-Z0-9-]+):/i", $line, $matches)) {
                    $key = strtolower($matches[1]);   
                    if (isset($headers[$key])) {
                        if (!is_array($headers[$key])) {
                            $headers[$key] = array(
                                $headers[$key]
                            );
                        }
                        $headers[$key][] = trim(str_replace($matches[0], '', $line));
                        continue;
                    }
                    
                    $headers[$key] = trim(str_replace($matches[0], '', $line));
                    continue;
                }
               
                if (!is_null($key) && isset($headers[$key])) {
                    if (is_array($headers[$key])) {
                        $headers[$key][count($headers[$key]) - 1] .= ' ' . $line;
                        continue;
                    }
                    $headers[$key] .= ' ' . $line;
                }
            }

            array_push($this->headers,$headers);

            return $headers;
        }
        private function _getList($array)
        {
            $list = array();
            foreach ($array as $key => $value) {
                $list[] = !is_array($value) ? $value : $this->_getList($v);
            }
            return '(' . implode(' ', $list) . ')';
        }
        private function _getParts($content, array $parts = array("attachment"=>array()))
        {
            list($head, $body) = preg_split("/\n\s*\n/", $content, 2);
            
            $head = $this->_getHeaders($head);


            if (!isset($head['content-type'])) {


                return $parts;
            }
            if (is_array($head['content-type'])) {
                
                $type = array(
                    $head['content-type'][1]
                );
                if (strpos($type[0], ';') !== false) {
                    $type = explode(';', $type[0], 2);
                }

            } else {
               

               if(strpos($head['content-disposition'],"attachment")!==false )
                 {
                     $arr=explode(";",$head['content-type']);

                     $type[0]=$arr[0];
                     $type[1]=$arr[count($arr)-1];
                     $typer="attached";
                     $id=$head['x-attachment-id'];

                 }
                else if (strpos($head['content-disposition'],"inline")!==false ){
                    
                    $arr=explode(";",$head['content-type']);

                    $type[0]=$arr[0];
                    $type[1]=$arr[count($arr)-1];
                    $typer="inline";
                    $id=$head['x-attachment-id'];

                }
                else
                $type = explode(';', $head['content-type'], 2);

              
               
               
            }
            $extra = array();


            if (count($type) > 1) {
                $extra = explode(';', str_replace(array(
                    '"',
                    "'"
                ), '', trim($type[1])));
               
            }
            
            $type = trim($type[0]);
            foreach ($extra as $i => $attr) {
                $attr = explode('=', $attr, 2);
                if (count($attr) > 1) {
                    list($key, $value) = $attr;
                    $extra[$key] = $value;
                }
                unset($extra[$i]);
            }
            
            if (isset($extra['boundary'])) {
                $sections = explode('--' . str_replace(array(
                    '"',
                    "'"
                ), '', $extra['boundary']), $body);
                array_pop($sections);
                array_shift($sections);
                foreach ($sections as $section) {
                    $parts = $this->_getParts($section, $parts);
                }

            } else {
                if (isset($head['content-transfer-encoding'])) {
                    if (is_array($head['content-transfer-encoding'])) {
                        $head['content-transfer-encoding'] = array_pop($head['content-transfer-encoding']);
                    }
                    switch (strtolower($head['content-transfer-encoding'])) {
                        case 'binary':
                            $body = imap_binary($body);
                        case 'base64':
                            $body = base64_decode($body);
                            break;
                        case 'quoted-printable':
                            $body = quoted_printable_decode($body);
                            break;
                        case '7bit':
                            $body = mb_convert_encoding($body, 'UTF-8', 'ISO-2022-JP');
                            break;
                        default:
                            break;
                    }
                }
                if (isset($extra['name'])) { 
                    
                   array_push($parts['attachment'],array("name"=>[$extra['name']],"deposition"=>$typer,"attachmentid"=>$id,"typer"=>$type,"content" => $body));
                } else {
                    $parts[$type] = $body;
                }
            }
            
            
            return $parts;
        }
    }
    if (!function_exists('imap_rfc822_parse_headers')) {
        function _imap_rfc822_parse_headers_decode($from)
        {
            if (preg_match('#\<([^\>]*)#', html_entity_decode($from))) {
                preg_match('#([^<]*)\<([^\>]*)\>#', html_entity_decode($from), $From);
                $_from = array(
                    'personal' => trim($From[1]),
                    'email' => trim($From[2])
                );
            } else {
                $_from = array(
                    'personal' => '',
                    'email' => trim($from)
                );
            }
            unset($from);
            unset($From);
            preg_match('#([^\@]*)@(.*)#', $_from['email'], $from);
            if (empty($from[1])) {
                $from[1] = '';
            }
            if (empty($from[2])) {
                $from[2] = '';
            }
            $__from = array(
                'mailbox' => trim($from[1]),
                'host' => trim($from[2])
            );
            return (object) array_merge($_from, $__from);
        }
        function imap_rfc822_parse_headers($header)
        {
            $header      = htmlentities($header);
            $headers     = new stdClass();
            $tos         = $ccs = $bccs = array();
            $headers->to = $headers->cc = $headers->bcc = array();
            preg_match('#Message\-(ID|id|Id)\:([^\n]*)#', $header, $ID);
            $headers->ID = trim($ID[2]);
            unset($ID);
            preg_match('#\nTo\:([^\n]*)#', $header, $to);
            if (isset($to[1])) {
                $tos = array(
                    trim($to[1])
                );
                if (strpos($to[1], ',') !== false) {
                    explode(',', trim($to[1]));
                }
            }
            $headers->from = array(
                new stdClass()
            );
            preg_match('#\nFrom\:([^\n]*)#', $header, $from);
            $headers->from[0] = _imap_rfc822_parse_headers_decode(trim($from[1]));
            preg_match('#\nCc\:([^\n]*)#', $header, $cc);
            if (isset($cc[1])) {
                $ccs = array(
                    trim($cc[1])
                );
                if (strpos($cc[1], ',') !== false) {
                    explode(',', trim($cc[1]));
                }
            }
            preg_match('#\nBcc\:([^\n]*)#', $header, $bcc);
            if (isset($bcc[1])) {
                $bccs = array(
                    trim($bcc[1])
                );
                if (strpos($bcc[1], ',') !== false) {
                    explode(',', trim($bcc[1]));
                }
            }
            preg_match('#\nSubject\:([^\n]*)#', $header, $subject);
            $headers->subject = trim($subject[1]);
            unset($subject);
            preg_match('#\nDate\:([^\n]*)#', $header, $date);
            $date          = substr(trim($date[0]), 6);
            $date          = preg_replace('/\(.*\)/', '', $date);
            $headers->date = trim($date);
            unset($date);
            foreach ($ccs as $k => $cc) {
                $headers->cc[$k] = _imap_rfc822_parse_headers_decode(trim($cc));
            }
            foreach ($bccs as $k => $bcc) {
                $headers->bcc[$k] = _imap_rfc822_parse_headers_decode(trim($bcc));
            }
            foreach ($tos as $k => $to) {
                $headers->to[$k] = _imap_rfc822_parse_headers_decode(trim($to));
            }
            return $headers;
        }
    }
}
/* Eden_Mail_Pop3 */
if (!class_exists('Eden_Mail_Pop3')) {
    class Eden_Mail_Pop3 extends Eden_Class
    {
        const TIMEOUT = 30;
        const NO_SUBJECT = '(no subject)';
        protected $_host = NULL;
        protected $_port = NULL;
        protected $_ssl = false;
        protected $_tls = false;
        protected $_username = NULL;
        protected $_password = NULL;
        protected $_timestamp = NULL;
        protected $_socket = NULL;
        protected $_loggedin = false;
        private $_debugging = false;
        public static function i()
        {
            return self::_getMultiple(__CLASS__);
        }
        public function __construct($host, $user, $pass, $port = NULL, $ssl = false, $tls = false)
        {
            Eden_Mail_Error::i()->argument(1, 'string')->argument(2, 'string')->argument(3, 'string')->argument(4, 'int', 'null')->argument(5, 'bool')->argument(6, 'bool');
            if (is_null($port)) {
                $port = $ssl ? 995 : 110;
            }
            $this->_host     = $host;
            $this->_username = $user;
            $this->_password = $pass;
            $this->_port     = $port;
            $this->_ssl      = $ssl;
            $this->_tls      = $tls;
            $this->connect();
        }
        public function connect($test = false)
        {
            Eden_Mail_Error::i()->argument(1, 'bool');
            if ($this->_loggedin) {
                return $this;
            }
            $host = $this->_host;
            if ($this->_ssl) {
                $host = 'ssl://' . $host.':'.$this->_port;
            }
            $errno         = 0;
            $errstr        = '';
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            //$hostname = "tls://foo.localhost";
            $this->_socket = stream_socket_client($host, $errno, $errstr, ini_get("default_socket_timeout"), STREAM_CLIENT_CONNECT, $context);
           // $this->_socket = @fsockopen($host, $this->_port, $errno, $errstr, $timeout);
            if (!$this->_socket) {
                echo Eden_Mail_Error::SERVER_ERROR;
                exit();
            }
            $welcome = $this->_receive();
            strtok($welcome, '<');
            $this->_timestamp = strtok('>');
            if (!strpos($this->_timestamp, '@')) {
                $this->_timestamp = null;
            } else {
                $this->_timestamp = '<' . $this->_timestamp . '>';
            }
            if ($this->_tls) {
                $this->_call('STLS');
                if (!stream_socket_enable_crypto($this->_socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    $this->disconnect();
                    echo Eden_Mail_Error::TLS_ERROR;
                	exit();
                }
            }
            if ($test) {
                $this->disconnect();
                return $this;
            }
            if ($this->_timestamp) {
                try {
                    $this->_call('APOP ' . $this->_username . ' ' . md5($this->_timestamp . $this->_password));
                    return;
                }
                catch (Exception $e) {
                }
            }
            $this->_call('USER ' . $this->_username);
            $this->_call('PASS ' . $this->_password);
            $this->_loggedin = true;
            return $this;
        }
        public function disconnect()
        {
            if (!$this->_socket) {
                return;
            }
            try {
                $this->request('QUIT');
            }
            catch (Exception $e) {
            }
            fclose($this->_socket);
            $this->_socket = NULL;
        }
        public function getOne($start = 0, $range = 10)
        {
            Eden_Mail_Error::i()->argument(1, 'int')->argument(2, 'int');

                $emails[] = $this->_getEmailFormat($this->_call('RETR ' . $start, true));
            
            return $emails;
        }
        public function getEmails($start = 0, $range = 10)
        {
            Eden_Mail_Error::i()->argument(1, 'int')->argument(2, 'int');
            $total = $this->getEmailTotal();
            $total = $total['messages'];
            if ($total == 0) {
                return array();
            }
            if (!is_array($start)) {
                $range = $range > 0 ? $range : 1;
                $start = $start >= 0 ? $start : 0;
                $max   = $total - $start;
                if ($max < 1) {
                    $max = $total;
                }
                $min = $max - $range + 1;
                if ($min < 1) {
                    $min = 1;
                }
                $set = $min . ':' . $max;
                if ($min == $max) {
                    $set = $min;
                }
            }
            $emails = array();
            for ($i = $min; $i <= $max; $i++) {
                $emails[] = $this->_getEmailFormat($this->_call('RETR ' . $i, true));
            }
            return $emails;
        }
        public function getEmailTotal()
        {
            list($messages, $octets) = explode(' ', $this->_call('STAT'));
            return $messages;
        }
        public function remove($msgno)
        {
            Eden_Mail_Error::i()->argument(1, 'int', 'string');
            $this->_call("DELE $msgno");
            if (!$this->_loggedin || !$this->_socket) {
                return false;
            }
            if (!is_array($msgno)) {
                $msgno = array(
                    $msgno
                );
            }
            foreach ($msgno as $number) {
                $this->_call('DELE ' . $number);
            }
            return $this;
        }


        protected function _call($command, $multiline = false)
        {
            if (!$this->_send($command)) {
                return false;
            }
            return $this->_receive($multiline);
        }
        protected function _receive($multiline = false)
        {
            $result  = @fgets($this->_socket);
            $status  = $result = trim($result);
            $message = '';
            if (strpos($result, ' ')) {
                list($status, $message) = explode(' ', $result, 2);
            }
            if ($status != '+OK') {
                return false;
            }
            if ($multiline) {
                $message = '';
                $line    = fgets($this->_socket);
                while ($line && rtrim($line, "\r\n") != '.') {
                    if ($line[0] == '.') {
                        $line = substr($line, 1);
                    }
                    $this->_debug('Receiving: ' . $line);
                    $message .= $line;
                    $line = fgets($this->_socket);
                }
                ;
            }
            return $message;
        }
        protected function _send($command)
        {
            $this->_debug('Sending: ' . $command);
            return fputs($this->_socket, $command . "\r\n");
        }
        private function _debug($string)
        {
            if ($this->_debugging) {
                $string = htmlspecialchars($string);
                echo '<pre>' . $string . '</pre>' . "\n";
            }
            return $this;
        }
        private function _getEmailFormat($email, array $flags = array())
        {  
            if (is_array($email)) {
                $email = implode("\n", $email);
            }
       
            $parts = preg_split("/\n\s*\n/", $email, 2);
            $head  = $parts[0];
            $body  = NULL;
            if (isset($parts[1]) && trim($parts[1]) != ')') {
                $body = $parts[1];
            }
            $lines = explode("\n", $head);
            $head  = array();
            foreach ($lines as $line) {
                if (trim($line) && preg_match("/^\s+/", $line)) {
                    $head[count($head) - 1] .= ' ' . trim($line);
                    continue;
                }
                $head[] = trim($line);
            }
            $head           = implode("\n", $head);
            $recipientsTo   = $recipientsCc = $recipientsBcc = $sender = array();
            $headers1       = imap_rfc822_parse_headers($head);
            $headers2       = $this->_getHeaders($head);
            $sender['name'] = NULL;
            if (isset($headers1->from[0]->personal)) {
                $sender['name'] = $headers1->from[0]->personal;
                if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($sender['name']))) {
                    $sender['name'] = str_replace('_', ' ', mb_decode_mimeheader($sender['name']));
                }
            }
            $sender['email'] = $headers1->from[0]->mailbox . '@' . $headers1->from[0]->host;
            if (isset($headers1->to)) {
                foreach ($headers1->to as $to) {
                    if (!isset($to->mailbox, $to->host)) {
                        continue;
                    }
                    $recipient = array(
                        'name' => NULL
                    );
                    if (isset($to->personal)) {
                        $recipient['name'] = $to->personal;
                        if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($recipient['name']))) {
                            $recipient['name'] = str_replace('_', ' ', mb_decode_mimeheader($recipient['name']));
                        }
                    }
                    $recipient['email'] = $to->mailbox . '@' . $to->host;
                    $recipientsTo[]     = $recipient;
                }
            }
            if (isset($headers1->cc)) {
                foreach ($headers1->cc as $cc) {
                    $recipient = array(
                        'name' => NULL
                    );
                    if (isset($cc->personal)) {
                        $recipient['name'] = $cc->personal;
                        if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($recipient['name']))) {
                            $recipient['name'] = str_replace('_', ' ', mb_decode_mimeheader($recipient['name']));
                        }
                    }
                    $recipient['email'] = $cc->mailbox . '@' . $cc->host;
                    $recipientsCc[]     = $recipient;
                }
            }
            if (isset($headers1->bcc)) {
                foreach ($headers1->bcc as $bcc) {
                    $recipient = array(
                        'name' => NULL
                    );
                    if (isset($bcc->personal)) {
                        $recipient['name'] = $bcc->personal;
                        if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($recipient['name']))) {
                            $recipient['name'] = str_replace('_', ' ', mb_decode_mimeheader($recipient['name']));
                        }
                    }
                    $recipient['email'] = $bcc->mailbox . '@' . $bcc->host;
                    $recipientsBcc[]    = $recipient;
                }
            }
            if (!isset($headers1->subject) || strlen(trim($headers1->subject)) === 0) {
                $headers1->subject = self::NO_SUBJECT;
            }
            $headers1->subject = str_replace(array(
                '<',
                '>'
            ), '', trim($headers1->subject));
            if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($headers1->subject))) {
                $headers1->subject = str_replace('_', ' ', mb_decode_mimeheader($headers1->subject));
            }
            $topic  = isset($headers2['thread-topic']) ? $headers2['thread-topic'] : $headers1->subject;
            $parent = isset($headers2['in-reply-to']) ? str_replace('"', '', $headers2['in-reply-to']) : NULL;
            $date   = isset($headers1->date) ? strtotime($headers1->date) : NULL;
            if (isset($headers2['message-id'])) {
                $messageId = str_replace('"', '', $headers2['message-id']);
            } else {
                $messageId = '<eden-no-id-' . md5(uniqid()) . '>';
            }
        
            $attachment = isset($headers2['content-type']) && strpos($headers2['content-type'], 'multipart/mixed') === 0;
            $format     = array(
                'id' => $messageId,
                'parent' => $parent,
                'topic' => $topic,
                'mailbox' => 'INBOX',
                'date' => $date,
                'subject' => str_replace('’', '\'', $headers1->subject),
                'from' => $sender,
                'flags' => $flags,
                'to' => $recipientsTo,
                'cc' => $recipientsCc,
                'bcc' => $recipientsBcc,
                'attachment' => $attachment
            );
            if (trim($body) && $body != ')') {
                $parts = $this->_getParts($email);
                if (empty($parts)) {
                    $parts = array(
                        'text/plain' => $body
                    );
                }
                $body       = $parts;
                $attachment = array();
                if (isset($body['attachment'])) {
                    $attachment = $body['attachment'];
                    unset($body['attachment']);
                }
                $format['body']       = $body;
                $format['attachment'] = $attachment;
            }
           
            return $format;
        }
        private function _getHeaders($rawData)
        {
            if (is_string($rawData)) {
                $rawData = explode("\n", $rawData);
            }
            $key     = NULL;
            $headers = array();
            foreach ($rawData as $line) {
                $line = trim($line);
                if (preg_match("/^([a-zA-Z0-9-]+):/i", $line, $matches)) {
                    $key = strtolower($matches[1]);
                    if (isset($headers[$key])) {
                        if (!is_array($headers[$key])) {
                            $headers[$key] = array(
                                $headers[$key]
                            );
                        }
                        $headers[$key][] = trim(str_replace($matches[0], '', $line));
                        continue;
                    }
                    $headers[$key] = trim(str_replace($matches[0], '', $line));
                    continue;
                }
                if (!is_null($key) && isset($headers[$key])) {
                    if (is_array($headers[$key])) {
                        $headers[$key][count($headers[$key]) - 1] .= ' ' . $line;
                        continue;
                    }
                    $headers[$key] .= ' ' . $line;
                }
            }
            return $headers;
        }
        private function _getParts($content, array $parts = array("attachment"=>array()))
        {
            list($head, $body) = preg_split("/\n\s*\n/", $content, 2);
            
            $head = $this->_getHeaders($head);


            if (!isset($head['content-type'])) {


                return $parts;
            }
            if (is_array($head['content-type'])) {
                
                $type = array(
                    $head['content-type'][1]
                );
                if (strpos($type[0], ';') !== false) {
                    $type = explode(';', $type[0], 2);
                }

            } else {
               

               if(strpos($head['content-disposition'],"attachment")!==false )
                 {
                     $arr=explode(";",$head['content-type']);

                     $type[0]=$arr[0];
                     $type[1]=$arr[count($arr)-1];
                     $typer="attached";
                     $id=$head['x-attachment-id'];

                 }
                else if (strpos($head['content-disposition'],"inline")!==false ){
                    
                    $arr=explode(";",$head['content-type']);

                    $type[0]=$arr[0];
                    $type[1]=$arr[count($arr)-1];
                    $typer="inline";
                    $id=$head['x-attachment-id'];

                }
                else
                $type = explode(';', $head['content-type'], 2);

              
               
               
            }
            $extra = array();


            if (count($type) > 1) {
                $extra = explode(';', str_replace(array(
                    '"',
                    "'"
                ), '', trim($type[1])));
               
            }
            
            $type = trim($type[0]);
            foreach ($extra as $i => $attr) {
                $attr = explode('=', $attr, 2);
                if (count($attr) > 1) {
                    list($key, $value) = $attr;
                    $extra[$key] = $value;
                }
                unset($extra[$i]);
            }
            
            if (isset($extra['boundary'])) {
                $sections = explode('--' . str_replace(array(
                    '"',
                    "'"
                ), '', $extra['boundary']), $body);
                array_pop($sections);
                array_shift($sections);
                foreach ($sections as $section) {
                    $parts = $this->_getParts($section, $parts);
                }

            } else {
                if (isset($head['content-transfer-encoding'])) {
                    if (is_array($head['content-transfer-encoding'])) {
                        $head['content-transfer-encoding'] = array_pop($head['content-transfer-encoding']);
                    }
                    switch (strtolower($head['content-transfer-encoding'])) {
                        case 'binary':
                            $body = imap_binary($body);
                        case 'base64':
                            $body = base64_decode($body);
                            break;
                        case 'quoted-printable':
                            $body = quoted_printable_decode($body);
                            break;
                        case '7bit':
                            $body = mb_convert_encoding($body, 'UTF-8', 'ISO-2022-JP');
                            break;
                        default:
                            break;
                    }
                }
                if (isset($extra['name'])) { 
                    
                   array_push($parts['attachment'],array("name"=>[$extra['name']],"deposition"=>$typer,"attachmentid"=>$id,"typer"=>$type,"content" => $body));
                } else {
                    $parts[$type] = $body;
                }
            }
            
            
            return $parts;
        }
    }
}
/* Eden_Mail_Smtp */
if (!class_exists('Eden_Mail_Smtp')) {
    class Eden_Mail_Smtp extends Eden_Class
    {
        const TIMEOUT = 30;
        protected $_host = NULL;
        protected $_port = NULL;
        protected $_ssl = false;
        protected $_tls = false;
        protected $_username = NULL;
        protected $_password = NULL;
        protected $_socket = NULL;
        protected $_boundary = array();
        protected $_subject = NULL;
        protected $_body = array();
        protected $_to = array();
        protected $_cc = array();
        protected $_bcc = array();
        protected $_attachments = array();
        private $_debugging = false;
        private $headers=array();
        public static function i()
        {
            return self::_getMultiple(__CLASS__);
        }
        public function __construct($host, $user, $pass, $port = NULL, $ssl = false, $tls = false)
        {
            Eden_Mail_Error::i()->argument(1, 'string')->argument(2, 'string')->argument(3, 'string')->argument(4, 'int', 'null')->argument(5, 'bool')->argument(6, 'bool');
            if (is_null($port)) {
                $port = $ssl ? 465 : 25;
            }
            $this->_host       = $host;
            $this->_username   = $user;
            $this->_password   = $pass;
            $this->_port       = $port;
            $this->_ssl        = $ssl;
            $this->_tls        = $tls;
            $this->_boundary[] = md5(time() . '1');
            $this->_boundary[] = md5(time() . '2');
        }
        public function addAttachment($filename, $data, $mime = NULL)
        {
            Eden_Mail_Error::i()->argument(1, 'string')->argument(2, 'string')->argument(3, 'string', 'null');
            $this->_attachments[] = array(
                $filename,
                $data,
                $mime
            );
            return $this;
        }
        public function addBCC($email, $name = NULL)
        {
            Eden_Mail_Error::i()->argument(1, 'string')->argument(2, 'string', 'null');
            $this->_bcc[$email] = $name;
            return $this;
        }
        public function addCC($email, $name = NULL)
        {
            Eden_Mail_Error::i()->argument(1, 'string')->argument(2, 'string', 'null');
            $this->_cc[$email] = $name;
            return $this;
        }
        public function addTo($email, $name = NULL)
        {
            Eden_Mail_Error::i()->argument(1, 'string')->argument(2, 'string', 'null');
            $this->_to[$email] = $name;
            return $this;
        }
        public function connect($timeout = self::TIMEOUT, $test = false)
        {
            Eden_Mail_Error::i()->argument(1, 'int')->argument(2, 'bool');
            $host = $this->_host;
            if ($this->_ssl) {
                $host = 'ssl://' . $host;
            } else {
                $host = 'tcp://' . $host;
            }
            $errno         = 0;
            $errstr        = '';
            $this->_socket = @stream_socket_client($host . ':' . $this->_port, $errno, $errstr, $timeout);
            if (!$this->_socket || strlen($errstr) > 0 || $errno > 0) {
                    echo Eden_Mail_Error::SERVER_ERROR;
                exit();
            }
            $this->_receive();
            if (!$this->_call('EHLO ' . $_SERVER['HTTP_HOST'], 250) && !$this->_call('HELO ' . $_SERVER['HTTP_HOST'], 250)) {
                $this->disconnect();
                    echo Eden_Mail_Error::SERVER_ERROR;
                exit();
            }
            ;
            if ($this->_tls && !$this->_call('STARTTLS', 220, 250)) {
                if (!stream_socket_enable_crypto($this->_socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    $this->disconnect();
                        echo Eden_Mail_Error::TLS_ERROR;
                exit();
                }
                if (!$this->_call('EHLO ' . $_SERVER['HTTP_HOST'], 250) && !$this->_call('HELO ' . $_SERVER['HTTP_HOST'], 250)) {
                    $this->disconnect();
                    echo Eden_Mail_Error::SERVER_ERROR;
                exit();
                }
            }
            if ($test) {
                $this->disconnect();
                return $this;
            }
            if (!$this->_call('AUTH LOGIN', 250, 334)) {
                $this->disconnect();
                echo Eden_Mail_Error::LOGIN_ERROR;
                exit();
            }
            if (!$this->_call(base64_encode($this->_username), 334)) {
                $this->disconnect();
                echo Eden_Mail_Error::LOGIN_ERROR;
                exit();
            }
            if (!$this->_call(base64_encode($this->_password), 235, 334)) {
                $this->disconnect();
                echo Eden_Mail_Error::LOGIN_ERROR;
                exit();
            }
            return $this;
        }
        public function disconnect()
        {
            if ($this->_socket) {
                $this->_send('QUIT');
                fclose($this->_socket);
                $this->_socket = NULL;
            }
            return $this;
        }
        public function reply($messageId, $topic = NULL, array $headers = array())
        {
            Eden_Mail_Error::i()->argument(1, 'string')->argument(2, 'string', 'null');
            $headers['In-Reply-To'] = $messageId;
            if ($topic) {
                $headers['Thread-Topic'] = $topic;
            }
            return $this->send($headers);
        }
        public function reset()
        {
            $this->_subject     = NULL;
            $this->_body        = array();
            $this->_to          = array();
            $this->_cc          = array();
            $this->_bcc         = array();
            $this->_attachments = array();
            $this->disconnect();
            return $this;
        }
        public function send(array $headers = array())
        {
            if (!$this->_socket) {
                $this->connect();
            }
            $headers = $this->_getHeaders($headers);
            $body    = $this->_getBody();
            if (!$this->_call('MAIL FROM:<' . $this->_username . '>', 250, 251)) {
                $this->disconnect();
                Eden_Mail_Error::i()->setMessage(Eden_Mail_Error::SMTP_ADD_EMAIL)->addVariable($this->_username)->trigger();
            }
            foreach ($this->_to as $email => $name) {
                if (!$this->_call('RCPT TO:<' . $email . '>', 250, 251)) {
                    $this->disconnect();
                    Eden_Mail_Error::i()->setMessage(Eden_Mail_Error::SMTP_ADD_EMAIL)->addVariable($email)->trigger();
                }
            }
            foreach ($this->_cc as $email => $name) {
                if (!$this->_call('RCPT TO:<' . $email . '>', 250, 251)) {
                    $this->disconnect();
                    Eden_Mail_Error::i()->setMessage(Eden_Mail_Error::SMTP_ADD_EMAIL)->addVariable($email)->trigger();
                }
            }
            foreach ($this->_bcc as $email => $name) {
                if (!$this->_call('RCPT TO:<' . $email . '>', 250, 251)) {
                    $this->disconnect();
                    Eden_Mail_Error::i()->setMessage(Eden_Mail_Error::SMTP_ADD_EMAIL)->addVariable($email)->trigger();
                }
            }
            if (!$this->_call('DATA', 354)) {
                $this->disconnect();
                Eden_Mail_Error::i(Eden_Mail_Error::SMTP_DATA)->trigger();
            }
            foreach ($headers as $name => $value) {
                $this->_send($name . ': ' . $value);
            }
            foreach ($body as $line) {
                if (strpos($line, '.') === 0) {
                    $line = '.' . $line;
                }
                $this->_send($line);
            }
            if (!$this->_call("\r\n.\r\n", 250)) {
                $this->disconnect();
                Eden_Mail_Error::i(Eden_Mail_Error::SMTP_DATA)->trigger();
            }
            $this->_send('RSET');
            return $headers;
        }
        public function setBody($body, $html = false)
        {
            Eden_Mail_Error::i()->argument(1, 'string')->argument(2, 'bool');
            if ($html) {
                $this->_body['text/html'] = $body;
                $body                     = strip_tags($body);
            }
            
            $this->_body['text/plain'] = $body;
            return $this;
        }
        public function setSubject($subject)
        {
            Eden_Mail_Error::i()->argument(1, 'string');
            $this->_subject = $subject;
            return $this;
        }
        protected function _addAttachmentBody(array $body)
        {
            foreach ($this->_attachments as $attachment) {
                list($name, $data, $mime) = $attachment;
                $mime   = $mime ? $mime : Eden_File::i($name)->getMime();
                $data   = base64_encode($data);
                $count  = ceil(strlen($data) / 998);
                $body[] = '--' . $this->_boundary[1];
                $body[] = 'Content-type: ' . $mime . ';name="' . $name . '"';
                $body[] = 'Content-disposition: attachment;filename="' . $name . '"';
                $body[] = 'Content-transfer-encoding: base64';
                $body[] = NULL;
                for ($i = 0; $i < $count; $i++) {
                    $body[] = substr($data, ($i * 998), 998);
                }
                $body[] = NULL;
                $body[] = NULL;
            }
            $body[] = '--' . $this->_boundary[1] . '--';
            return $body;
        }
        protected function _call($command, $code = NULL)
        {
            if (!$this->_send($command)) {
                return false;
            }
            $receive = $this->_receive();
            $args    = func_get_args();
            if (count($args) > 1) {
                for ($i = 1; $i < count($args); $i++) {
                    if (strpos($receive, (string) $args[$i]) === 0) {
                        return true;
                    }
                }
                return false;
            }
            return $receive;
        }
        protected function _getAlternativeAttachmentBody()
        {
            $alternative = $this->_getAlternativeBody();
            $body        = array();
            $body[]      = 'Content-Type: multipart/mixed;boundary="' . $this->_boundary[1] . '"';
            $body[]      = NULL;
            $body[]      = '--' . $this->_boundary[1];
            foreach ($alternative as $line) {
                $body[] = $line;
            }
            return $this->_addAttachmentBody($body);
        }
        protected function _getAlternativeBody()
        {
            $plain  = $this->_getPlainBody();
            $html   = $this->_getHtmlBody();
            $body   = array();
            $body[] = 'Content-Type: multipart/alternative;boundary="' . $this->_boundary[0] . '"';
            $body[] = NULL;
            $body[] = '--' . $this->_boundary[0];
            foreach ($plain as $line) {
                $body[] = $line;
            }
            $body[] = '--' . $this->_boundary[0];
            foreach ($html as $line) {
                $body[] = $line;
            }
            $body[] = '--' . $this->_boundary[0] . '--';
            $body[] = NULL;
            $body[] = NULL;
            return $body;
        }
        protected function _getBody()
        {
            $type = 'Plain';
            if (count($this->_body) > 1) {
                $type = 'Alternative';
            } else if (isset($this->_body['text/html'])) {
                $type = 'Html';
            }
            $method = '_get%sBody';
            if (!empty($this->_attachments)) {
                $method = '_get%sAttachmentBody';
            }
            $method = sprintf($method, $type);
            return $this->$method();
        }
        protected function _getHeaders(array $customHeaders = array())
        {   
            $timestamp = $this->_getTimestamp();
            $subject   = trim($this->_subject);
            $subject   = str_replace(array(
                "\n",
                "\r"
            ), '', $subject);
            $to        = $cc = $bcc = array();
            foreach ($this->_to as $email => $name) {
                $to[] = trim($name . ' <' . $email . '>');
            }
            foreach ($this->_cc as $email => $name) {
                $cc[] = trim($name . ' <' . $email . '>');
            }
            foreach ($this->_bcc as $email => $name) {
                $bcc[] = trim($name . ' <' . $email . '>');
            }
            list($account, $suffix) = explode('@', $this->_username);
            $headers = array(
                'Date' => $timestamp,
                'Subject' => $subject,
                'From' => '<' . $this->_username . '>',
                'To' => implode(',', $to)
            );
            if (!empty($cc)) {
                $headers['Cc'] = implode(',', $cc);
            }
            if (!empty($bcc)) {
                $headers['Bcc'] = implode(',', $bcc);
            }
            $headers['Message-ID']   = '<' . md5(uniqid(time())) . '.eden@' . $suffix . '>';
            $headers['Thread-Topic'] = $this->_subject;
            $headers['Reply-To']     = '<' . $this->_username . '>';
            foreach ($customHeaders as $key => $value) {
                $headers[$key] = $value;
            }
            return $headers;
        }
        protected function _getHtmlAttachmentBody()
        {
            $html   = $this->_getHtmlBody();
            $body   = array();
            $body[] = 'Content-Type: multipart/mixed;boundary="' . $this->_boundary[1] . '"';
            $body[] = NULL;
            $body[] = '--' . $this->_boundary[1];
            foreach ($html as $line) {
                $body[] = $line;
            }
            return $this->_addAttachmentBody($body);
        }
        protected function _getHtmlBody()
        {
            $charset = $this->_isUtf8($this->_body['text/html']) ? 'utf-8' : 'US-ASCII';
            $html    = str_replace("\r", '', trim($this->_body['text/html']));
            $encoded = explode("\n", $this->_quotedPrintableEncode($html));
            $body    = array();
            $body[]  = 'Content-Type: text/html;charset=' . $charset;
            $body[]  = 'Content-Transfer-Encoding: quoted-printable' . "\n";
            foreach ($encoded as $line) {
                $body[] = $line;
            }
            $body[] = NULL;
            $body[] = NULL;
            return $body;
        }
        protected function _getPlainAttachmentBody()
        {
            $plain  = $this->_getPlainBody();
            $body   = array();
            $body[] = 'Content-Type: multipart/mixed;boundary="' . $this->_boundary[1] . '"';
            $body[] = NULL;
            $body[] = '--' . $this->_boundary[1];
            foreach ($plain as $line) {
                $body[] = $line;
            }
            return $this->_addAttachmentBody($body);
        }
        protected function _getPlainBody()
        {
            $charset = $this->_isUtf8($this->_body['text/plain']) ? 'utf-8' : 'US-ASCII';
            $plane   = str_replace("\r", '', trim($this->_body['text/plain']));
            $count   = ceil(strlen($plane) / 998);
            $body    = array();
            $body[]  = 'Content-Type: text/plain;charset=' . $charset;
            $body[]  = 'Content-Transfer-Encoding: 7bit';
            $body[]  = NULL;
            for ($i = 0; $i < $count; $i++) {
                $body[] = substr($plane, ($i * 998), 998);
            }
            $body[] = NULL;
            $body[] = NULL;
            return $body;
        }
        protected function _receive()
        {
            $data = '';
            $now  = time();
            while ($str = fgets($this->_socket, 1024)) {
                $data .= $str;
                if (substr($str, 3, 1) == ' ' || time() > ($now + self::TIMEOUT)) {
                    break;
                }
            }
            $this->_debug('Receiving: ' . $data);
            return $data;
        }
        protected function _send($command)
        {
            $this->_debug('Sending: ' . $command);
            return fwrite($this->_socket, $command . "\r\n");
        }
        private function _debug($string)
        {
            if ($this->_debugging) {
                $string = htmlspecialchars($string);
                echo '<pre>' . $string . '</pre>' . "\n";
            }
            return $this;
        }
        private function _getTimestamp()
        {
            $zone = date('Z');
            $sign = ($zone < 0) ? '-' : '+';
            $zone = abs($zone);
            $zone = (int) ($zone / 3600) * 100 + ($zone % 3600) / 60;
            return sprintf("%s %s%04d", date('D,j M Y H:i:s'), $sign, $zone);
        }
        private function _isUtf8($string)
        {
            $regex = array(
                '[\xC2-\xDF][\x80-\xBF]',
                '\xE0[\xA0-\xBF][\x80-\xBF]',
                '[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}',
                '\xED[\x80-\x9F][\x80-\xBF]',
                '\xF0[\x90-\xBF][\x80-\xBF]{2}',
                '[\xF1-\xF3][\x80-\xBF]{3}',
                '\xF4[\x80-\x8F][\x80-\xBF]{2}'
            );
            $count = ceil(strlen($string) / 5000);
            for ($i = 0; $i < $count; $i++) {
                if (preg_match('%(?:' . implode('|', $regex) . ')+%xs', substr($string, ($i * 5000), 5000))) {
                    return false;
                }
            }
            return true;
        }
        private function _quotedPrintableEncode($input, $line_max = 250)
        {
            $hex            = array(
                '0',
                '1',
                '2',
                '3',
                '4',
                '5',
                '6',
                '7',
                '8',
                '9',
                'A',
                'B',
                'C',
                'D',
                'E',
                'F'
            );
            $lines          = preg_split("/(?:\r\n|\r|\n)/", $input);
            $linebreak      = "=0D=0A=\r\n";
            $line_max       = $line_max - strlen($linebreak);
            $escape         = "=";
            $output         = "";
            $cur_conv_line  = "";
            $length         = 0;
            $whitespace_pos = 0;
            $addtl_chars    = 0;
            for ($j = 0; $j < count($lines); $j++) {
                $line   = $lines[$j];
                $linlen = strlen($line);
                for ($i = 0; $i < $linlen; $i++) {
                    $c   = substr($line, $i, 1);
                    $dec = ord($c);
                    $length++;
                    if ($dec == 32) {
                        if (($i == ($linlen - 1))) {
                            $c = "=20";
                            $length += 2;
                        }
                        $addtl_chars    = 0;
                        $whitespace_pos = $i;
                    } elseif (($dec == 61) || ($dec < 32) || ($dec > 126)) {
                        $h2 = floor($dec / 16);
                        $h1 = floor($dec % 16);
                        $c  = $escape . $hex["$h2"] . $hex["$h1"];
                        $length += 2;
                        $addtl_chars += 2;
                    }
                    if ($length >= $line_max) {
                        $cur_conv_line .= $c;
                        $whitesp_diff = $i - $whitespace_pos + $addtl_chars;
                        if (($i + $addtl_chars) > $whitesp_diff) {
                            $output .= substr($cur_conv_line, 0, (strlen($cur_conv_line) - $whitesp_diff)) . $linebreak;
                            $i = $i - $whitesp_diff + $addtl_chars;
                        } else {
                            $output .= $cur_conv_line . $linebreak;
                        }
                        $cur_conv_line  = "";
                        $length         = 0;
                        $whitespace_pos = 0;
                    } else {
                        $cur_conv_line .= $c;
                    }
                }
                $length         = 0;
                $whitespace_pos = 0;
                $output .= $cur_conv_line;
                $cur_conv_line = "";
                if ($j <= count($lines) - 1) {
                    $output .= $linebreak;
                }
            }
            return trim($output);
        }
    }
}
