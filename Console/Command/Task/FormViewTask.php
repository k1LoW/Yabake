<?php

App::uses('AppShell', 'Console/Command');
App::uses('Controller', 'Controller');
App::uses('ViewTask', 'Console/Command/Task');

class FormViewTask extends ViewTask {

    public $tasks = array('Project', 'Controller', 'DbConfig', 'Yabake.FormTemplate');

    public $scaffoldActions = array('form_front', 'form_confirm', 'form_complete');

    /**
     * Handles interactive baking
     *
     * @return void
     */
    protected function _interactive() {
        $this->hr();
        $this->out(sprintf("Bake View\nPath: %s", $this->getPath()));
        $this->hr();

        $this->DbConfig->interactive = $this->Controller->interactive = $this->interactive = true;

        if (empty($this->connection)) {
            $this->connection = $this->DbConfig->getConfig();
        }

        $this->Controller->connection = $this->connection;
        $this->controllerName = $this->Controller->getName();

        $prompt = __d('cake_console', "Would you like bake to build your views interactively?\nWarning: Choosing no will overwrite %s views if it exist.",  $this->controllerName);
        $interactive = $this->in($prompt, array('y', 'n'), 'n');

        if (strtolower($interactive) == 'n') {
            $this->interactive = false;
        }

        $prompt = __d('cake_console', "Would you like to create some form views\n(form_front, form_confirm, form_complete) for this controller?\nNOTE: Before doing so, you'll need to create your controller\nand model classes (including associated models).");
        $wannaDoScaffold = $this->in($prompt, array('y', 'n'), 'y');

        $wannaDoAdmin = $this->in(__d('cake_console', "Would you like to create the views for admin routing?"), array('y', 'n'), 'n');

        if (strtolower($wannaDoScaffold) == 'y' || strtolower($wannaDoAdmin) == 'y') {
            $vars = $this->_loadController();
            if (strtolower($wannaDoScaffold) == 'y') {
                $actions = $this->scaffoldActions;
                $this->bakeActions($actions, $vars);
            }
            if (strtolower($wannaDoAdmin) == 'y') {
                $admin = $this->Project->getPrefix();
                $regularActions = $this->scaffoldActions;
                $adminActions = array();
                foreach ($regularActions as $action) {
                    $adminActions[] = $admin . $action;
                }
                $this->bakeActions($adminActions, $vars);
            }
            $this->hr();
            $this->out();
            $this->out(__d('cake_console', "View Scaffolding Complete.\n"));
        }
    }

    /**
     * Gets the template name based on the action name
     *
     * @param string $action name
     * @return string template name
     */
    public function getTemplate($action) {
        if ($action != $this->template && in_array($action, $this->noTemplateActions)) {
            return false;
        }
        if (!empty($this->template) && $action != $this->template) {
            return $this->template;
        }
        $themePath = $this->FormTemplate->getThemePath();
        if (file_exists($themePath . 'views' . DS . $action . '.ctp')) {
            return $action;
        }
        $template = $action;
        $prefixes = Configure::read('Routing.prefixes');
        foreach ((array)$prefixes as $prefix) {
            if (strpos($template, $prefix) !== false) {
                $template = str_replace($prefix . '_', '', $template);
            }
        }
        return $template;
    }

}