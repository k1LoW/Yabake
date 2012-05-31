<?php
App::uses('ConsoleOptionParser', 'Console');
App::uses('BakeShell', 'Console/Command');
App::uses('Model', 'Model');

class FormBakeShell extends BakeShell {

    /**
     * Contains tasks to load and instantiate
     *
     * @var array
     */
    public $tasks = array('Project',
                          'DbConfig',
                          'Model',
                          'Yabake.FormController',
                          'Yabake.FormView');

    /**
     * Override main() to handle action
     *
     * @return mixed
     */
    public function main() {
        if (!is_dir($this->DbConfig->path)) {
            $path = $this->Project->execute();
            if (!empty($path)) {
                $this->DbConfig->path = $path . 'Config' . DS;
            } else {
                return false;
            }
        }

        if (!config('database')) {
            $this->out(__d('Form', 'Your database configuration was not found. Take a moment to create one.'));
            $this->args = null;
            return $this->DbConfig->execute();
        }
        $this->out(__d('Form', 'Interactive Form Bake Shell'));
        $this->hr();
        $this->out(__d('Form', '[V]iew'));
        $this->out(__d('Form', '[C]ontroller'));
        $this->out(__d('Form', '[Q]uit'));

        $classToBake = strtoupper($this->in(__d('Form', 'What would you like to Bake?'), array('D', 'M', 'V', 'C', 'P', 'Q')));
        switch ($classToBake) {
        case 'D':
            $this->DbConfig->execute();
            break;
        case 'M':
            $this->Model->execute();
            break;
        case 'V':
            $this->FormView->execute();
            break;
        case 'C':
            $this->FormController->execute();
            break;
        case 'P':
            $this->Project->execute();
            break;
        case 'Q':
            exit(0);
            break;
        default:
            $this->out(__d('Form', 'You have made an invalid selection. Please choose a type of class to Bake by entering D, M, V, F, T, or C.'));
        }
        $this->hr();
        $this->main();
    }

    /**
     * all
     *
     */
    public function all(){
        // remove bake all
    }

    /**
     * get the option parser.
     * Shell::getOptionParser()
     * @return void
     */
    public function getOptionParser() {
        $name = ($this->plugin ? $this->plugin . '.' : '') . $this->name;
        $parser = new ConsoleOptionParser($name);
        return $parser;
    }
}