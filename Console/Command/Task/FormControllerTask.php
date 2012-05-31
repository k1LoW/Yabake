<?php

App::uses('AppShell', 'Console/Command');
App::uses('ControllerTask', 'Console/Command/Task');
App::uses('AppModel', 'Model');

/**
 * Task class for creating and updating controller files for Yabake.
 *
 */
class FormControllerTask extends ControllerTask {

    /**
     * Tasks to be loaded by this Task
     *
     * @var array
     */
    public $tasks = array('Model',
                          'Yabake.FormTemplate',
                          'DbConfig',
                          'Project');

    /**
     * Override initialize
     *
     * @return void
     */
    public function initialize() {
        $this->path = current(App::path('Controller'));
    }

    /**
     * execute
     *
     */
    public function execute(){
        /**
         * BakeTask::execute();
         **/
        foreach ($this->args as $i => $arg) {
            if (strpos($arg, '.')) {
                list($this->params['plugin'], $this->args[$i]) = pluginSplit($arg);
                break;
            }
        }
        if (isset($this->params['plugin'])) {
            $this->plugin = $this->params['plugin'];
        }
        /// BakeTask::execute();

        if (empty($this->args)) {
            return $this->_interactive();
        }

        if (isset($this->args[0])) {
            if (!isset($this->connection)) {
                $this->connection = 'default';
            }

            $controller = $this->_controllerName($this->args[0]);
            $actions = '';

            if (!empty($this->params['public'])) {
                $this->out(__d('Form', 'Baking basic crud methods for ') . $controller);
                $actions .= $this->formBakeActions($controller);
            }
            if (!empty($this->params['admin'])) {
                $admin = $this->Project->getPrefix();
                if ($admin) {
                    $this->out(__d('Form', 'Adding %s methods', $admin));
                    $actions .= "\n" . $this->formBakeActions($controller, $admin);
                }
            }
            if (empty($actions)) {
                $actions = 'scaffold';
            }

            $this->bake($controller, $actions);
        }
    }

    /**
     * bake
     *
     * @return
     */
    public function bake($controllerName, $actions = '', $helpers = null, $components = null) {
        $this->out("\n" . __d('Form', 'Baking controller class for %s...', $controllerName), 1, Shell::QUIET);

        $isScaffold = ($actions === 'scaffold') ? true : false;

        $this->FormTemplate->set(array(
                                       'plugin' => $this->plugin,
                                       'pluginPath' => empty($this->plugin) ? '' : $this->plugin . '.'
                                       ));
        $this->FormTemplate->set(compact('controllerName', 'actions', 'helpers', 'components', 'isScaffold'));
        $contents = $this->FormTemplate->generate('classes', 'controller');

        $path = $this->getPath();
        $filename = $path . $controllerName . 'Controller.php';

        if (is_file($filename) && $this->interactive === true) {
            $this->out(__d('Form', '<warning>File `%s` exists</warning>', $path));
            $key = $this->in(__d('Form', 'Do you want to [o]verwrite controller, [a]ppend actions or do [n]othing? [o,a,n,q]'), array('o', 'a', 'q'), 'n');

            if (strtolower($key) == 'q') {
                $this->out(__d('Form', '<error>Quitting</error>.'), 2);
                $this->_stop();
            } elseif (strtolower($key) == 'n') {
                $this->out(__d('Form', 'Skip `%s`', $path), 2);
                return false;
            } elseif (strtolower($key) == 'a') {
                if ($this->appendActions($filename, $actions)) {
                    return $contents;
                }
                return false;
            }
        } else {
            $this->out(__d('Form', 'Creating file %s', $path));
        }

        $this->interactive = false;
        if ($this->createFile($filename, $contents)) {
            return $contents;
        }
        return false;
    }

    /**
     * Interactive
     *
     * @return void
     */
    protected function _interactive() {
        $this->interactive = true;
        $this->hr();
        $this->out(__d('Form', "Bake Controller\nPath: %s", $this->getPath()));
        $this->hr();

        if (empty($this->connection)) {
            $this->connection = $this->DbConfig->getConfig();
        }
        $controllerName = $this->getName();
        $this->hr();
        $this->out(__d('Form', 'Baking %sController', $controllerName));
        $this->hr();

        $helpers = $components = array();
        $actions = '';
        $wannaUseSession = 'y';
        $wannaBakeAdminCrud = 'n';
        $useDynamicScaffold = 'n';
        $wannaBakeCrud = 'y';

        $question[] = __d('Form', "Would you like to build your controller interactively?");
        if (file_exists($this->path . $controllerName . 'Controller.php')) {
            $question[] = __d('Form', "Warning: Choosing no will overwrite or append the %sController.", $controllerName);
        }
        $doItInteractive = $this->in(implode("\n", $question), array('y', 'n'), 'y');

        if (strtolower($doItInteractive) == 'y') {
            $this->interactive = true;
            list($wannaBakeCrud, $wannaBakeAdminCrud) = $this->_askAboutMethods();

            $helpers = $this->doHelpers();
            $components = array_merge($this->doComponents(), array('Transition'));
        }

        if (strtolower($wannaBakeCrud) == 'y') {
            $actions = $this->formBakeActions($controllerName, null, strtolower($wannaUseSession) == 'y');
        }
        if (strtolower($wannaBakeAdminCrud) == 'y') {
            $admin = $this->Project->getPrefix();
            $actions .= $this->formBakeActions($controllerName, $admin, strtolower($wannaUseSession) == 'y');
        }

        $baked = false;
        if ($this->interactive === true) {
            $this->confirmController($controllerName, $useDynamicScaffold, $helpers, $components);
            $looksGood = $this->in(__d('Form', 'Look okay?'), array('y','n'), 'y');

            if (strtolower($looksGood) == 'y') {
                $baked = $this->bake($controllerName, $actions, $helpers, $components);
            }
        } else {
            $baked = $this->bake($controllerName, $actions, $helpers, $components);
        }
        return $baked;
    }

    public function doComponents() {
        return $this->_doPropertyChoices(
                                         __d('Form', "Would you like this controller to use any components besides TransitionComponent?"),
                                         __d('Form', "Please provide a comma separated list of the component names you'd like to use.\nExample: 'Acl, Security, RequestHandler'")
                                         );
    }

    public function formBakeActions($controllerName, $admin = null, $wannaUseSession = true) {
        $currentModelName = $modelImport = $this->_modelName($controllerName);
        $plugin = $this->plugin;
        if ($plugin) {
            $plugin .= '.';
        }
        App::uses($modelImport, $plugin . 'Model');
        if (!class_exists($modelImport)) {
            $this->err(__d('Form', 'You must have a model for this class to build basic methods. Please try again.'));
            $this->_stop();
        }

        $modelObj = ClassRegistry::init($currentModelName);
        $controllerPath = $this->_controllerPath($controllerName);
        $pluralName = $this->_pluralName($currentModelName);
        $singularName = Inflector::variable($currentModelName);
        $singularHumanName = $this->_singularHumanName($controllerName);
        $pluralHumanName = $this->_pluralName($controllerName);
        $displayField = $modelObj->displayField;
        $primaryKey = $modelObj->primaryKey;

        $this->FormTemplate->set(compact(
                                         'plugin', 'admin', 'controllerPath', 'pluralName', 'singularName',
                                         'singularHumanName', 'pluralHumanName', 'modelObj', 'wannaUseSession', 'currentModelName',
                                         'displayField', 'primaryKey'
                                         ));
        $actions = $this->FormTemplate->generate('form_actions', 'controller_actions');
        return $actions;
    }

    /**
     * appendActions
     *
     * @param $arg
     */
    public function appendActions($path, $actions) {
        $path = str_replace(DS . DS, DS, $path);

        $this->out();

        $this->out(__d('Form', 'Append actions to %s', $path));

        $File = new File($path, true);
        if ($File->exists() && $File->writable()) {
            $contents = $File->read();
            $contents = preg_replace('#}[^}]*$#', '', $contents) . $actions . "\n}";
            $data = $File->prepare($contents);
            $File->write($data);
            $this->out(__d('Form', '<success>Wrote</success> `%s`', $path));
            return true;
        } else {
            $this->err(__d('Form', '<error>Could not write to `%s`</error>.', $path), 2);
            return false;
        }
    }

    /**
     * Interact with the user and ask about which methods (admin or regular they want to bake)
     *
     * @return array Array containing (bakeRegular, bakeAdmin) answers
     */
    protected function _askAboutMethods() {
        $wannaBakeCrud = $this->in(
                                   __d('Form', "Would you like to create some basic class methods \n(form_front(), form_confirm(), form_save(), form_complete())?"),
                                   array('y','n'), 'n'
                                   );
        $wannaBakeAdminCrud = $this->in(
                                        __d('Form', "Would you like to create the basic class methods for admin routing?"),
                                        array('y','n'), 'n'
                                        );
        return array($wannaBakeCrud, $wannaBakeAdminCrud);
    }

}