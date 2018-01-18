<?php

Yaf_Loader::import( SMARTY_DIR."/Smarty.class.php");
Yaf_Loader::import( SMARTY_SYSPLUGINS_DIR ."smarty_internal_templatecompilerbase.php");
Yaf_Loader::import( SMARTY_SYSPLUGINS_DIR . "smarty_internal_templatelexer.php");
Yaf_Loader::import( SMARTY_SYSPLUGINS_DIR . "smarty_internal_templateparser.php");
Yaf_Loader::import( SMARTY_SYSPLUGINS_DIR . "smarty_internal_compilebase.php");
Yaf_Loader::import( SMARTY_SYSPLUGINS_DIR . "smarty_internal_write_file.php");
class Comm_SmartyAdapter implements Yaf_View_Interface
{
    /**
     * Smarty object
     * @var Smarty
     */
    public $_smarty;

    /**
     * Constructor
     *
     * @param string $tmplPath
     * @param array $extraParams
     * @return void
     */
    public function __construct($tmplPath = null, $extraParams = array()) {
        $this->_smarty = new Smarty;
        //$this->_smarty->compile_dir = $_SERVER[''] . "/template_c";

        if (null !== $tmplPath) {
            $this->setScriptPath($tmplPath);
        }
//        Comm_TestDebug::T($extraParams,'exit');
        foreach ($extraParams as $key => $value) {
            $this->_smarty->$key = $value;
        }
        $this->_smarty->compile_dir = APPLICATION_PATH . "/compile_c/";

        if(!is_dir($this->_smarty->compile_dir)) {
            mkdir($this->_smarty->compile_dir);
        }
    }

    /**
     * Set the path to the templates
     *
     * @param string $path The directory to set as the path.
     * @return void
     */
    public function setScriptPath($path)
    {
        if (is_readable($path)) {
            $this->_smarty->template_dir = $path;
            return;
        }

        throw new Exception('Invalid path provided');
    }

    /**
     * Retrieve the current template directory
     *
     */
    public function getScriptPath()
    {
        return $this->_smarty->template_dir;
    }

    /**
    * Return the template engine object
    */
    public function getEngine() {
        return $this->_smarty;
    }

    /**
     * Assign a variable to the template
     *
     * @param string $key The variable name.
     * @param mixed $val The variable value.
     * @return void
     */
    public function __set($key, $val)
    {
        $this->_smarty->assign($key, $val);
    }

    /**
     * Allows testing with empty() and isset() to work
     *
     * @param string $key
     * @return boolean
     */
    public function __isset($key)
    {
        return (null !== $this->_smarty->get_template_vars($key));
    }

    /**
     * Allows unset() on object properties to work
     *
     * @param string $key
     * @return void
     */
    public function __unset($key)
    {
        $this->_smarty->clear_assign($key);
    }

    /**
     * Assign variables to the template
     *
     * Allows setting a specific key to the specified value, OR passing
     * an array of key => value pairs to set en masse.
     *
     * @see __set()
     * @param string|array $spec The assignment strategy to use (key or
     * array of key => value pairs)
     * @param mixed $value (Optional) If assigning a named variable,
     * use this as the value.
     * @return void
     */
    public function assign($spec, $value = null) {
        if (is_array($spec)) {
            $this->_smarty->assign($spec);
            return;
        }

        $this->_smarty->assign($spec, $value);
    }

    /**
     * Clear all assigned variables
     *
     * Clears all variables assigned to Yaf_View either via
     * {@link assign()} or property overloading
     * ({@link __get()}/{@link __set()}).
     *
     * @return void
     */
    public function clearVars() {
        $this->_smarty->clear_all_assign();
    }

    /**
     * Processes a template and returns the output.
     *
     * @param string $name The template to process.
     * @return string The output.
     */
    public function render($name, $value = NULL) {
        return $this->_smarty->fetch($name);
    }

    public function display($name, $value = NULL) {
        echo $this->_smarty->fetch($name);
    }

    public function layout($view,$data = array()){
        if (!empty($data)) {
            foreach($data as $k=>$v){
                $this->assign($k, $v);
            }
        }
        $this->display($view);
    }

}
